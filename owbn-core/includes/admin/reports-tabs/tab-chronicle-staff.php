<?php
/**
 * Chronicle Staff Report tab.
 *
 * Columns: chronicle | slug | HST | HST email | CM | CM email | CM role (ASC)
 *
 * Single bulk ASC fetch for all chronicle/%/cm holders, cached 15 min.
 * Satellite chronicles inherit CM from parent (parent stored as post ID).
 * Fuzzy name match: exact → first-word+last-word → last-word+first-prefix.
 */

defined( 'ABSPATH' ) || exit;

$is_admin = current_user_can( 'manage_options' );

if ( ! post_type_exists( 'owbn_chronicle' ) ) {
    echo '<p>' . esc_html__( 'Chronicle post type not available on this site.', 'owbn-core' ) . '</p>';
    return;
}

$posts = get_posts( array(
    'post_type'      => 'owbn_chronicle',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
) );

if ( empty( $posts ) ) {
    echo '<p>' . esc_html__( 'No chronicles found.', 'owbn-core' ) . '</p>';
    return;
}

update_postmeta_cache( wp_list_pluck( $posts, 'ID' ) );

$rows       = array();
$id_to_slug = array();
foreach ( $posts as $p ) {
    $slug = get_post_meta( $p->ID, 'chronicle_slug', true ) ?: $p->post_name;
    $rows[ $slug ] = array(
        'id'        => $p->ID,
        'title'     => $p->post_title,
        'slug'      => $slug,
        'satellite' => ! empty( get_post_meta( $p->ID, 'chronicle_satellite', true ) ),
        'parent_id' => (int) get_post_meta( $p->ID, 'chronicle_parent', true ),
        'hst_info'  => get_post_meta( $p->ID, 'hst_info', true ) ?: array(),
        'cm_info'   => get_post_meta( $p->ID, 'cm_info', true ) ?: array(),
    );
    $id_to_slug[ $p->ID ] = $slug;
}

// Admin-only: bulk-fetch all chronicle/*/cm holders in one call. Cached.
$holders_map = array();
$fetch_error = '';
$handle_refresh = $is_admin && isset( $_GET['refresh'] ) && $_GET['refresh'] === '1';
if ( $is_admin ) {
    $cache_key = 'owc_cm_holders_map_v1';
    if ( $handle_refresh ) {
        delete_transient( $cache_key );
    }
    $cached = get_transient( $cache_key );
    if ( is_array( $cached ) ) {
        $holders_map = $cached;
    } elseif ( function_exists( 'owc_asc_get_holders_by_pattern' ) ) {
        $result = owc_asc_get_holders_by_pattern( 'ccs', 'chronicle/%/cm' );
        if ( is_wp_error( $result ) ) {
            $fetch_error = $result->get_error_message();
        } elseif ( is_array( $result ) ) {
            $holders_map = $result;
            set_transient( $cache_key, $holders_map, 4 * HOUR_IN_SECONDS );
        }
    }
}

// -- helpers --
$norm_name = function ( $s ) {
    $s = strtolower( (string) $s );
    $s = preg_replace( '/["\'"()\[\]]/', '', $s );
    $s = preg_replace( '/\s+/', ' ', $s );
    return trim( $s );
};

$match_holder = function ( $cm_name, $cm_email, array $holders ) use ( $norm_name ) {
    // Returns [ 'color', 'note', 'matched_user_id' ]
    $n_count = count( $holders );
    if ( 0 === $n_count ) return array( 'red', __( 'no holder', 'owbn-core' ), 0 );
    if ( $n_count > 1 )  return array( 'red', sprintf( __( '%d holders!', 'owbn-core' ), $n_count ), 0 );

    $h     = $holders[0];
    $h_uid = (int) ( $h['user_id'] ?? 0 );

    // Email match is strongest (and actual_email from meta is sometimes real).
    $cm_email_n = strtolower( trim( (string) $cm_email ) );
    $h_email_n  = strtolower( trim( (string) ( $h['email'] ?? '' ) ) );
    if ( $cm_email_n && $h_email_n && $cm_email_n === $h_email_n ) {
        return array( 'green', __( 'email match', 'owbn-core' ), $h_uid );
    }

    // Name fuzzy match.
    $a = $norm_name( $cm_name );
    $b = $norm_name( $h['display_name'] ?? '' );
    if ( '' === $a || '' === $b ) return array( 'red', __( 'mismatch', 'owbn-core' ), 0 );

    if ( $a === $b ) return array( 'green', __( 'matched', 'owbn-core' ), $h_uid );

    // Tokenise and compare.
    $ta = preg_split( '/\s+/', $a );
    $tb = preg_split( '/\s+/', $b );
    $last_a = end( $ta );
    $last_b = end( $tb );
    $first_a = $ta[0];
    $first_b = $tb[0];

    // Shared surname.
    if ( $last_a === $last_b && strlen( $last_a ) >= 3 ) {
        // First-name prefix match (handles Gabe/Gabriel, Rosa/Rosicler, Jeff/Jeffrey).
        $min = min( strlen( $first_a ), strlen( $first_b ) );
        if ( $min >= 3 && substr( $first_a, 0, 3 ) === substr( $first_b, 0, 3 ) ) {
            return array( 'yellow', __( 'fuzzy match', 'owbn-core' ), $h_uid );
        }
    }

    // Last name is first_a (single-word meta name) matching anywhere in holder.
    if ( count( $ta ) === 1 && in_array( $first_a, $tb, true ) ) {
        return array( 'yellow', __( 'fuzzy match', 'owbn-core' ), $h_uid );
    }

    return array( 'red', __( 'mismatch', 'owbn-core' ), 0 );
};

$color_bg = array(
    'green'  => '#d4edda',
    'yellow' => '#fff3cd',
    'red'    => '#f8d7da',
);

$user_link = function ( $user_id, $label, $allow ) {
    if ( ! $user_id || ! $allow ) return esc_html( $label );
    $url = admin_url( 'user-edit.php?user_id=' . (int) $user_id );
    return '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
};

// Tallies
$tally = array( 'green' => 0, 'yellow' => 0, 'red' => 0 );

// Sort rows by title
uasort( $rows, function ( $a, $b ) { return strcmp( $a['title'], $b['title'] ); } );

?>
<style>
.owc-staff-report { border-collapse: collapse; width: 100%; margin-top: 16px; table-layout: fixed; }
.owc-staff-report th, .owc-staff-report td { padding: 8px 10px; border: 1px solid #c3c4c7; font-size: 13px; vertical-align: top; word-wrap: break-word; }
.owc-staff-report th { background: #f6f7f7; text-align: left; font-weight: 600; }
.owc-staff-report col.owc-col-chron { width: 22%; }
.owc-staff-report col.owc-col-slug  { width: 6%; }
.owc-staff-report col.owc-col-name  { width: 12%; }
.owc-staff-report col.owc-col-email { width: 16%; }
.owc-staff-report col.owc-col-cmrole { width: 16%; }
.owc-staff-report .owc-badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600; margin-left: 4px; }
.owc-staff-summary { display: flex; gap: 12px; margin: 12px 0; }
.owc-staff-summary .pill { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
</style>

<p><?php esc_html_e( 'Each row shows a chronicle and its HST/CM staff. The CM role cell reflects who currently holds chronicle/{slug}/cm in AccessSchema.', 'owbn-core' ); ?></p>

<?php if ( $is_admin && $fetch_error ) : ?>
    <div class="notice notice-error inline"><p><?php echo esc_html( $fetch_error ); ?></p></div>
<?php endif; ?>

<?php if ( $is_admin ) :
    $refresh_url = add_query_arg( array( 'page' => owc_get_client_id() . '-owc-reports', 'tab' => 'chronicle-staff', 'refresh' => '1' ), admin_url( 'admin.php' ) );
?>
<p>
    <a href="<?php echo esc_url( $refresh_url ); ?>" class="button"><?php esc_html_e( 'Refresh ASC Data', 'owbn-core' ); ?></a>
    <span style="margin-left:8px; color:#50575e;"><em><?php esc_html_e( 'Cached 4 hours. Green: matched. Yellow: fuzzy match. Red: missing / multiple / mismatch.', 'owbn-core' ); ?></em></span>
</p>
<?php endif; ?>

<table class="owc-staff-report">
    <colgroup>
        <col class="owc-col-chron">
        <col class="owc-col-slug">
        <col class="owc-col-name">
        <col class="owc-col-email">
        <col class="owc-col-name">
        <col class="owc-col-email">
        <?php if ( $is_admin ) : ?><col class="owc-col-cmrole"><?php endif; ?>
    </colgroup>
    <thead>
    <tr>
        <th><?php esc_html_e( 'Chronicle', 'owbn-core' ); ?></th>
        <th><?php esc_html_e( 'Slug', 'owbn-core' ); ?></th>
        <th><?php esc_html_e( 'HST', 'owbn-core' ); ?></th>
        <th><?php esc_html_e( 'HST Email', 'owbn-core' ); ?></th>
        <th><?php esc_html_e( 'CM', 'owbn-core' ); ?></th>
        <th><?php esc_html_e( 'CM Email', 'owbn-core' ); ?></th>
        <?php if ( $is_admin ) : ?>
        <th><?php esc_html_e( 'CM Role (ASC)', 'owbn-core' ); ?></th>
        <?php endif; ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach ( $rows as $slug => $r ) :
        $title    = $r['title'];
        $hst_info = $r['hst_info'];
        $cm_info  = $r['cm_info'];
        $cm_slug  = $slug;
        $cm_note  = '';

        // Satellite: resolve to parent chronicle's cm_info + slug.
        if ( $r['satellite'] && $r['parent_id'] && isset( $id_to_slug[ $r['parent_id'] ] ) ) {
            $parent_slug = $id_to_slug[ $r['parent_id'] ];
            if ( isset( $rows[ $parent_slug ] ) ) {
                $cm_info = $rows[ $parent_slug ]['cm_info'];
                $cm_slug = $parent_slug;
                $cm_note = sprintf( __( '(from parent: %s)', 'owbn-core' ), $parent_slug );
            }
        }

        $hst_uid  = isset( $hst_info['user'] ) && is_numeric( $hst_info['user'] ) ? (int) $hst_info['user'] : 0;
        $hst_name = $hst_info['display_name'] ?? '';
        $hst_mail = $hst_info['actual_email'] ?? '';

        $cm_uid  = isset( $cm_info['user'] ) && is_numeric( $cm_info['user'] ) ? (int) $cm_info['user'] : 0;
        $cm_name = $cm_info['display_name'] ?? '';
        $cm_mail = $cm_info['actual_email'] ?? '';

        $holders = $holders_map[ 'chronicle/' . $cm_slug . '/cm' ] ?? array();

        $color = 'red'; $note = '—'; $matched_uid = 0;
        if ( $is_admin ) {
            list( $color, $note, $matched_uid ) = $match_holder( $cm_name, $cm_mail, $holders );
            $tally[ $color ]++;
        }
        $row_bg = $is_admin ? ( $color_bg[ $color ] ?? '' ) : '';
        ?>
        <tr<?php echo $row_bg ? ' style="background:' . esc_attr( $row_bg ) . ';"' : ''; ?>>
            <td><?php echo esc_html( $title ); ?></td>
            <td><code><?php echo esc_html( $slug ); ?></code></td>
            <td><?php echo $user_link( $hst_uid, $hst_name, $is_admin ); ?></td>
            <td><?php echo esc_html( $hst_mail ); ?></td>
            <td>
                <?php echo $user_link( $cm_uid, $cm_name, $is_admin ); ?>
                <?php if ( $cm_note ) : ?><br><small><?php echo esc_html( $cm_note ); ?></small><?php endif; ?>
            </td>
            <td><?php echo esc_html( $cm_mail ); ?></td>
            <?php if ( $is_admin ) : ?>
            <td>
                <?php if ( empty( $holders ) ) : ?>
                    —
                <?php else : ?>
                    <?php foreach ( $holders as $h ) :
                        $hid   = (int) ( $h['user_id'] ?? 0 );
                        $hname = $h['display_name'] ?? ( $h['user_login'] ?? '#' . $hid );
                        echo $user_link( $hid, $hname, true );
                        echo '<br><small>' . esc_html( $h['email'] ?? '' ) . '</small><br>';
                    endforeach; ?>
                <?php endif; ?>
                <span class="owc-badge" style="background:<?php echo esc_attr( $color_bg[ $color ] ); ?>;">
                    <?php echo esc_html( $note ); ?>
                </span>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php if ( $is_admin ) : ?>
<div class="owc-staff-summary">
    <span class="pill" style="background:<?php echo esc_attr( $color_bg['green'] ); ?>;"><?php echo esc_html( sprintf( __( 'Matched: %d', 'owbn-core' ), $tally['green'] ) ); ?></span>
    <span class="pill" style="background:<?php echo esc_attr( $color_bg['yellow'] ); ?>;"><?php echo esc_html( sprintf( __( 'Fuzzy: %d', 'owbn-core' ), $tally['yellow'] ) ); ?></span>
    <span class="pill" style="background:<?php echo esc_attr( $color_bg['red'] ); ?>;"><?php echo esc_html( sprintf( __( 'Problems: %d', 'owbn-core' ), $tally['red'] ) ); ?></span>
</div>
<?php endif; ?>
