<?php
/**
 * Chronicle Staff Report tab.
 *
 * Columns: chronicle | slug | HST | HST email | CM | CM email | CM role (ASC)
 *
 * Match logic (per-row status color):
 *   green  = exactly 1 user holds chronicle/{slug}/cm AND that user matches cm_info
 *   yellow = cm_info can't be confirmed (user=__new__, match by email only), or
 *            exactly 1 holder but no cm_info link
 *   red    = 0 holders, 2+ holders, or explicit mismatch with cm_info
 *
 * Satellite chronicles show the parent's CM + parent's CM role with a
 * "(from parent: <slug>)" annotation.
 */

defined( 'ABSPATH' ) || exit;

$is_admin     = current_user_can( 'manage_options' );
$client_id    = owc_get_client_id();
$chronicles   = function_exists( 'owc_get_local_chronicles' ) ? owc_get_local_chronicles() : array();
$all_by_slug  = array();

if ( is_wp_error( $chronicles ) ) {
    echo '<p>' . esc_html( $chronicles->get_error_message() ) . '</p>';
    return;
}
if ( empty( $chronicles ) ) {
    echo '<p>' . esc_html__( 'No chronicles found.', 'owbn-core' ) . '</p>';
    return;
}

// Index by slug for satellite parent lookups.
foreach ( $chronicles as $c ) {
    $all_by_slug[ $c['slug'] ] = $c;
}

// Filter to published only.
$rows = array_filter( $chronicles, function ( $c ) {
    return ( $c['status'] ?? '' ) === 'publish';
} );

usort( $rows, function ( $a, $b ) {
    return strcmp( $a['title'] ?? '', $b['title'] ?? '' );
} );

// -- helpers --

$resolve_staff = function ( array $info ) {
    // Returns [ 'user_id' => int|null, 'name' => string, 'email' => string ].
    $uid = 0;
    if ( isset( $info['user'] ) && is_numeric( $info['user'] ) ) {
        $uid = (int) $info['user'];
    }
    return array(
        'user_id' => $uid > 0 ? $uid : null,
        'name'    => $info['display_name'] ?? '',
        'email'   => $info['actual_email'] ?? ( $info['display_email'] ?? '' ),
    );
};

$user_link = function ( $user_id, $label, $allow_edit_link ) {
    if ( ! $user_id ) {
        return esc_html( $label );
    }
    if ( $allow_edit_link ) {
        $url = admin_url( 'user-edit.php?user_id=' . (int) $user_id );
        return '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
    }
    return esc_html( $label );
};

$match_cm = function ( $cm_info_resolved, array $asc_holders ) {
    // Returns [ 'color' => 'green'|'yellow'|'red', 'note' => string ].
    $count = count( $asc_holders );
    if ( 0 === $count ) {
        return array( 'color' => 'red', 'note' => __( 'no holder', 'owbn-core' ) );
    }
    if ( $count > 1 ) {
        return array( 'color' => 'red', 'note' => sprintf( __( '%d holders!', 'owbn-core' ), $count ) );
    }
    // Exactly one holder.
    $holder = $asc_holders[0];
    $holder_id    = isset( $holder['user_id'] ) ? (int) $holder['user_id'] : 0;
    $holder_email = strtolower( $holder['email'] ?? '' );

    if ( $cm_info_resolved['user_id'] && $holder_id && $holder_id === $cm_info_resolved['user_id'] ) {
        return array( 'color' => 'green', 'note' => __( 'matched', 'owbn-core' ) );
    }
    // Fall back to email match (handles user=__new__ cases).
    $cm_email = strtolower( $cm_info_resolved['email'] ?? '' );
    if ( $cm_email && $holder_email && $cm_email === $holder_email ) {
        return array( 'color' => 'yellow', 'note' => __( 'email match', 'owbn-core' ) );
    }
    return array( 'color' => 'red', 'note' => __( 'mismatch', 'owbn-core' ) );
};

$color_bg = array(
    'green'  => '#d4edda',
    'yellow' => '#fff3cd',
    'red'    => '#f8d7da',
);

?>
<style>
.owc-staff-report { border-collapse: collapse; width: 100%; margin-top: 16px; }
.owc-staff-report th, .owc-staff-report td { padding: 8px 10px; border: 1px solid #c3c4c7; font-size: 13px; vertical-align: top; }
.owc-staff-report th { background: #f6f7f7; text-align: left; font-weight: 600; }
.owc-staff-report td.owc-cm-cell { min-width: 180px; }
.owc-staff-report .owc-badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600; margin-left: 4px; }
</style>
<p><?php esc_html_e( 'Each row shows a chronicle and its HST/CM staff. The CM role cell reflects who currently holds chronicle/{slug}/cm in AccessSchema.', 'owbn-core' ); ?></p>
<p><em><?php esc_html_e( 'Green: matched. Yellow: email-only match or satellite inherited. Red: no holder, multiple holders, or mismatch.', 'owbn-core' ); ?></em></p>

<table class="owc-staff-report">
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
    <?php foreach ( $rows as $c ) :
        $slug       = $c['slug'] ?? '';
        $title      = $c['title'] ?? '';
        $is_sat     = ! empty( $c['chronicle_satellite'] );
        $parent     = $c['chronicle_parent'] ?? '';

        // HST always from this chronicle.
        $hst = $resolve_staff( $c['hst_info'] ?? array() );

        // CM: for satellites, pull from parent.
        $cm_source = $c;
        $cm_note   = '';
        if ( $is_sat && $parent && isset( $all_by_slug[ $parent ] ) ) {
            $cm_source = $all_by_slug[ $parent ];
            $cm_note   = sprintf( __( '(from parent: %s)', 'owbn-core' ), $parent );
        }
        $cm      = $resolve_staff( $cm_source['cm_info'] ?? array() );
        $cm_slug = $cm_source['slug'] ?? $slug;

        // Query ASC for cm role holders (admin only — this is the slow part).
        $holders = array();
        if ( $is_admin && function_exists( 'owc_asc_get_users_by_role' ) ) {
            $h = owc_asc_get_users_by_role( $client_id, 'chronicle/' . $cm_slug . '/cm' );
            if ( is_array( $h ) ) {
                $holders = $h;
            }
        }

        $match = $match_cm( $cm, $holders );
        $row_bg = $is_admin ? ( $color_bg[ $match['color'] ] ?? '' ) : '';
        ?>
        <tr<?php echo $row_bg ? ' style="background:' . esc_attr( $row_bg ) . ';"' : ''; ?>>
            <td><?php echo esc_html( $title ); ?></td>
            <td><code><?php echo esc_html( $slug ); ?></code></td>
            <td><?php echo $user_link( $hst['user_id'], $hst['name'], $is_admin ); ?></td>
            <td><?php echo esc_html( $hst['email'] ); ?></td>
            <td>
                <?php echo $user_link( $cm['user_id'], $cm['name'], $is_admin ); ?>
                <?php if ( $cm_note ) : ?><br><small><?php echo esc_html( $cm_note ); ?></small><?php endif; ?>
            </td>
            <td><?php echo esc_html( $cm['email'] ); ?></td>
            <?php if ( $is_admin ) : ?>
            <td class="owc-cm-cell">
                <?php if ( empty( $holders ) ) : ?>
                    —
                <?php else : ?>
                    <?php foreach ( $holders as $h ) :
                        $hid    = (int) ( $h['user_id'] ?? 0 );
                        $hname  = $h['display_name'] ?? ( $h['user_login'] ?? '#' . $hid );
                        echo $user_link( $hid, $hname, true );
                        echo '<br><small>' . esc_html( $h['email'] ?? '' ) . '</small><br>';
                    endforeach; ?>
                <?php endif; ?>
                <span class="owc-badge" style="background:<?php echo esc_attr( $color_bg[ $match['color'] ] ); ?>;">
                    <?php echo esc_html( $match['note'] ); ?>
                </span>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
