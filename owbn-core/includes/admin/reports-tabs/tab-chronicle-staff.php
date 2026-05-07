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

// ── Filter & sort params (GET) ────────────────────────────────────────
$allowed_active  = array( 'active', 'inactive', 'all' );
$allowed_status  = array( 'all', 'full', 'probationary', 'satellite' );
$allowed_orderby = array( 'title', 'hst_name', 'cm_name', 'probationary', 'satellite' );
$allowed_order   = array( 'asc', 'desc' );

$f_active  = isset( $_GET['active'] )  && in_array( $_GET['active'],  $allowed_active, true )  ? $_GET['active']  : 'active';
$f_status  = isset( $_GET['status'] )  && in_array( $_GET['status'],  $allowed_status, true )  ? $_GET['status']  : 'all';
$f_orderby = isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], $allowed_orderby, true ) ? $_GET['orderby'] : 'title';
$f_order   = isset( $_GET['order'] )   && in_array( strtolower( $_GET['order'] ), $allowed_order, true ) ? strtolower( $_GET['order'] ) : 'asc';

$handle_refresh = $is_admin && isset( $_GET['refresh'] ) && $_GET['refresh'] === '1';

// Fetch chronicles via the remote-aware client API so this report works on any
// site (local on chronicles/council, gateway-fetched on remote sites). The Refresh
// button forces a fresh pull of both chronicles + the ASC holders map.
if ( ! function_exists( 'owc_get_chronicles' ) ) {
    echo '<p>' . esc_html__( 'owbn-core client API not available on this site.', 'owbn-core' ) . '</p>';
    return;
}

$chronicles = owc_get_chronicles( $handle_refresh );

if ( is_wp_error( $chronicles ) ) {
    echo '<div class="notice notice-error inline"><p>' . esc_html( sprintf(
        __( 'Unable to fetch chronicles: %s', 'owbn-core' ),
        $chronicles->get_error_message()
    ) ) . '</p></div>';
    return;
}

if ( empty( $chronicles ) ) {
    echo '<p>' . esc_html__( 'No chronicles found.', 'owbn-core' ) . '</p>';
    return;
}

// Build the master row map from the client API payload.
$all_rows   = array();
$id_to_slug = array();
foreach ( $chronicles as $c ) {
    $c            = is_object( $c ) ? (array) $c : $c;
    $id           = isset( $c['id'] ) ? (int) $c['id'] : 0;
    $slug         = isset( $c['slug'] ) ? (string) $c['slug'] : '';
    if ( '' === $slug ) {
        continue;
    }
    $hst_info     = is_array( $c['hst_info'] ?? null ) ? $c['hst_info'] : array();
    $cm_info      = is_array( $c['cm_info'] ?? null ) ? $c['cm_info'] : array();
    $probationary = ! empty( $c['chronicle_probationary'] );
    $satellite    = ! empty( $c['chronicle_satellite'] );
    $all_rows[ $slug ] = array(
        'id'           => $id,
        'post_status'  => isset( $c['status'] ) ? (string) $c['status'] : 'publish',
        'title'        => isset( $c['title'] ) ? (string) $c['title'] : $slug,
        'slug'         => $slug,
        'probationary' => $probationary,
        'satellite'    => $satellite,
        'parent_id'    => (int) ( $c['chronicle_parent'] ?? 0 ),
        'hst_info'     => $hst_info,
        'cm_info'      => $cm_info,
        'hst_name'     => strtolower( (string) ( $hst_info['display_name'] ?? '' ) ),
        'cm_name'      => strtolower( (string) ( $cm_info['display_name'] ?? '' ) ),
    );
    if ( $id ) {
        $id_to_slug[ $id ] = $slug;
    }
}

// Apply user filters to derive the visible row set. $all_rows is preserved
// for satellite → parent CM inheritance lookups below.
$rows = $all_rows;

if ( 'active' === $f_active ) {
    $rows = array_filter( $rows, function ( $r ) { return $r['post_status'] === 'publish'; } );
} elseif ( 'inactive' === $f_active ) {
    $rows = array_filter( $rows, function ( $r ) { return $r['post_status'] === 'decommissioned'; } );
}

if ( 'full' === $f_status ) {
    $rows = array_filter( $rows, function ( $r ) { return ! $r['probationary'] && ! $r['satellite']; } );
} elseif ( 'probationary' === $f_status ) {
    $rows = array_filter( $rows, function ( $r ) { return $r['probationary']; } );
} elseif ( 'satellite' === $f_status ) {
    $rows = array_filter( $rows, function ( $r ) { return $r['satellite']; } );
}

// Admin-only: bulk-fetch all chronicle/*/cm holders in one call. Cached.
$holders_map = array();
$fetch_error = '';
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
    'green'   => '',         // no tint — white is fine for matched rows
    'yellow'  => '#fff3cd',
    'red'     => '#f8d7da',
    'ignored' => '#eef0f2',  // grey for dismissed
);
$color_pill = array(
    'green'   => '#e6f4ea',
    'yellow'  => '#fff3cd',
    'red'     => '#f8d7da',
    'ignored' => '#dcdfe2',
);

// Only link to wp-admin user edits when we're on the local host (chronicles/council)
// — the user IDs in hst_info/cm_info come from the chronicles site's user table, so
// links only resolve correctly there. On remote sites show plain text.
$is_local_host    = post_type_exists( 'owbn_chronicle' );
$allow_user_links = $is_admin && $is_local_host;

$user_link = function ( $user_id, $label, $allow ) {
    if ( ! $user_id || ! $allow ) return esc_html( $label );
    $url = admin_url( 'user-edit.php?user_id=' . (int) $user_id );
    return '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
};

// Tallies
$tally = array( 'green' => 0, 'yellow' => 0, 'red' => 0, 'ignored' => 0 );

// Sort rows. Boolean columns (probationary/satellite) tiebreak by title asc so
// rows within each group stay alphabetical.
$sort_dir = ( 'desc' === $f_order ) ? -1 : 1;
uasort( $rows, function ( $a, $b ) use ( $f_orderby, $sort_dir ) {
    switch ( $f_orderby ) {
        case 'hst_name':
            $cmp = strcmp( $a['hst_name'], $b['hst_name'] );
            break;
        case 'cm_name':
            $cmp = strcmp( $a['cm_name'], $b['cm_name'] );
            break;
        case 'probationary':
            $cmp = ( (int) $a['probationary'] ) - ( (int) $b['probationary'] );
            if ( 0 === $cmp ) {
                return strcmp( $a['title'], $b['title'] );
            }
            break;
        case 'satellite':
            $cmp = ( (int) $a['satellite'] ) - ( (int) $b['satellite'] );
            if ( 0 === $cmp ) {
                return strcmp( $a['title'], $b['title'] );
            }
            break;
        case 'title':
        default:
            $cmp = strcmp( $a['title'], $b['title'] );
            break;
    }
    return $cmp * $sort_dir;
} );

$client_id = owc_get_client_id();
$page_slug = $client_id . '-owc-reports';
$base_args = array( 'page' => $page_slug, 'tab' => 'chronicle-staff' );

// Sub-tab: chronicle (default) | user
$subtab = ( isset( $_GET['subtab'] ) && $_GET['subtab'] === 'user' ) ? 'user' : 'chronicle';

// Dismissed (slug:user_id) pairs — admin can dismiss known-OK CM mismatches.
$ignored_pairs = get_option( 'owc_cm_match_ignored', array() );
if ( ! is_array( $ignored_pairs ) ) $ignored_pairs = array();

// Helper: build a sortable header link that toggles order when the active column is re-clicked.
$sort_link = function ( $column, $label ) use ( $f_orderby, $f_order, $f_active, $f_status, $base_args ) {
    $is_active = ( $f_orderby === $column );
    $next_order = ( $is_active && $f_order === 'asc' ) ? 'desc' : 'asc';
    $url = add_query_arg( array_merge( $base_args, array(
        'active'  => $f_active,
        'status'  => $f_status,
        'orderby' => $column,
        'order'   => $next_order,
    ) ), admin_url( 'admin.php' ) );
    $arrow = '';
    if ( $is_active ) {
        $arrow = ( $f_order === 'asc' ) ? ' ↑' : ' ↓';
    }
    return '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . esc_html( $arrow ) . '</a>';
};

?>
<style>
.owc-staff-report { border-collapse: collapse; width: 100%; margin-top: 16px; table-layout: fixed; }
.owc-staff-report th, .owc-staff-report td { padding: 8px 10px; border: 1px solid #c3c4c7; font-size: 13px; vertical-align: top; word-wrap: break-word; }
.owc-staff-report th { background: #f6f7f7; text-align: left; font-weight: 600; }
.owc-staff-report th a { text-decoration: none; color: inherit; }
.owc-staff-report col.owc-col-chron  { width: 26%; }
.owc-staff-report col.owc-col-name   { width: 12%; }
.owc-staff-report col.owc-col-email  { width: 16%; }
.owc-staff-report col.owc-col-flag   { width: 5%; }
.owc-staff-report col.owc-col-cmrole { width: 14%; }
.owc-staff-report td.owc-flag-cell   { text-align: center; font-weight: 600; }
.owc-staff-report .owc-badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600; margin-left: 4px; }
.owc-staff-summary { display: flex; gap: 12px; margin: 12px 0; }
.owc-staff-summary .pill { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.owc-staff-filters { display: flex; gap: 12px; align-items: flex-end; margin: 12px 0; padding: 10px; background: #f6f7f7; border: 1px solid #dcdcde; }
.owc-staff-filters label { display: flex; flex-direction: column; font-size: 12px; font-weight: 600; color: #50575e; }
.owc-staff-filters select { min-width: 140px; }
.owc-substab-nav { display: flex; gap: 4px; border-bottom: 1px solid #c3c4c7; margin: 12px 0 0; }
.owc-substab-nav a { padding: 8px 14px; border: 1px solid #c3c4c7; border-bottom: none; background: #f6f7f7; text-decoration: none; color: #1d2327; font-weight: 600; border-radius: 4px 4px 0 0; position: relative; top: 1px; }
.owc-substab-nav a.active { background: #fff; border-bottom: 1px solid #fff; }
.owc-bulk-toolbar { display: flex; gap: 8px; align-items: center; margin: 12px 0; padding: 8px 10px; background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 3px; }
.owc-bulk-toolbar .owc-bulk-count { color: #50575e; font-size: 12px; }
.owc-bulk-toolbar button[disabled] { opacity: 0.5; cursor: not-allowed; }
.owc-staff-report .owc-cb-cell { width: 28px; text-align: center; }
</style>

<?php
// Sub-tab nav
$subtab_chron_url = add_query_arg( array_merge( $base_args, array(
    'active'  => $f_active,
    'status'  => $f_status,
    'orderby' => $f_orderby,
    'order'   => $f_order,
    'subtab'  => 'chronicle',
) ), admin_url( 'admin.php' ) );
$subtab_user_url = add_query_arg( array_merge( $base_args, array(
    'subtab'  => 'user',
) ), admin_url( 'admin.php' ) );
?>
<nav class="owc-substab-nav">
    <a href="<?php echo esc_url( $subtab_chron_url ); ?>" class="<?php echo $subtab === 'chronicle' ? 'active' : ''; ?>"><?php esc_html_e( 'By Chronicle', 'owbn-core' ); ?></a>
    <a href="<?php echo esc_url( $subtab_user_url ); ?>" class="<?php echo $subtab === 'user' ? 'active' : ''; ?>"><?php esc_html_e( 'By User', 'owbn-core' ); ?></a>
</nav>

<?php
if ( $subtab === 'user' ) {
    include __DIR__ . '/tab-chronicle-staff-by-user.php';
    return;
}
?>

<p><?php esc_html_e( 'Each row shows a chronicle and its HST/CM staff. The CM role cell reflects who currently holds chronicle/{slug}/cm in AccessSchema.', 'owbn-core' ); ?></p>

<?php if ( $is_admin && $fetch_error ) : ?>
    <div class="notice notice-error inline"><p><?php echo esc_html( $fetch_error ); ?></p></div>
<?php endif; ?>

<form method="get" class="owc-staff-filters">
    <input type="hidden" name="page" value="<?php echo esc_attr( $page_slug ); ?>">
    <input type="hidden" name="tab" value="chronicle-staff">
    <input type="hidden" name="orderby" value="<?php echo esc_attr( $f_orderby ); ?>">
    <input type="hidden" name="order" value="<?php echo esc_attr( $f_order ); ?>">
    <label>
        <?php esc_html_e( 'Active', 'owbn-core' ); ?>
        <select name="active">
            <option value="active"   <?php selected( $f_active, 'active' ); ?>><?php esc_html_e( 'Active (Published)', 'owbn-core' ); ?></option>
            <option value="inactive" <?php selected( $f_active, 'inactive' ); ?>><?php esc_html_e( 'Inactive (Decommissioned)', 'owbn-core' ); ?></option>
            <option value="all"      <?php selected( $f_active, 'all' ); ?>><?php esc_html_e( 'All', 'owbn-core' ); ?></option>
        </select>
    </label>
    <label>
        <?php esc_html_e( 'Game Status', 'owbn-core' ); ?>
        <select name="status">
            <option value="all"          <?php selected( $f_status, 'all' ); ?>><?php esc_html_e( 'All', 'owbn-core' ); ?></option>
            <option value="full"         <?php selected( $f_status, 'full' ); ?>><?php esc_html_e( 'Full', 'owbn-core' ); ?></option>
            <option value="probationary" <?php selected( $f_status, 'probationary' ); ?>><?php esc_html_e( 'Probationary', 'owbn-core' ); ?></option>
            <option value="satellite"    <?php selected( $f_status, 'satellite' ); ?>><?php esc_html_e( 'Satellite', 'owbn-core' ); ?></option>
        </select>
    </label>
    <button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'owbn-core' ); ?></button>
    <?php if ( $is_admin ) :
        $refresh_url = add_query_arg( array_merge( $base_args, array(
            'active'  => $f_active,
            'status'  => $f_status,
            'orderby' => $f_orderby,
            'order'   => $f_order,
            'refresh' => '1',
        ) ), admin_url( 'admin.php' ) );
    ?>
        <a href="<?php echo esc_url( $refresh_url ); ?>" class="button"><?php esc_html_e( 'Refresh ASC Data', 'owbn-core' ); ?></a>
        <span style="margin-left:8px; color:#50575e;"><em><?php esc_html_e( 'Cached 4 hours. Green: matched. Yellow: fuzzy. Red: missing / multiple / mismatch.', 'owbn-core' ); ?></em></span>
    <?php endif; ?>
</form>

<?php if ( empty( $rows ) ) : ?>
    <p><em><?php esc_html_e( 'No chronicles match the current filters.', 'owbn-core' ); ?></em></p>
    <?php return; ?>
<?php endif; ?>

<?php
$can_bulk = $is_admin && $is_local_host;
if ( $can_bulk ) :
?>
<div class="owc-bulk-toolbar" id="owc-chron-bulk-toolbar">
    <strong><?php esc_html_e( 'Bulk:', 'owbn-core' ); ?></strong>
    <button type="button" class="button" id="owc-bulk-confirm-chron" disabled><?php esc_html_e( 'Confirm match', 'owbn-core' ); ?></button>
    <button type="button" class="button" id="owc-bulk-ignore-chron" disabled><?php esc_html_e( 'Ignore (dismiss)', 'owbn-core' ); ?></button>
    <span class="owc-bulk-count">0 <?php esc_html_e( 'selected', 'owbn-core' ); ?></span>
    <span class="owc-bulk-result" style="margin-left:auto;"></span>
</div>
<?php endif; ?>

<table class="owc-staff-report">
    <colgroup>
        <?php if ( $can_bulk ) : ?><col style="width:28px;"><?php endif; ?>
        <col class="owc-col-chron">
        <col class="owc-col-name">
        <col class="owc-col-email">
        <col class="owc-col-name">
        <col class="owc-col-email">
        <col class="owc-col-flag">
        <col class="owc-col-flag">
        <?php if ( $is_admin ) : ?><col class="owc-col-cmrole"><?php endif; ?>
    </colgroup>
    <thead>
    <tr>
        <?php if ( $can_bulk ) : ?>
        <th class="owc-cb-cell"><input type="checkbox" id="owc-cb-all-chron" title="<?php esc_attr_e( 'Select all', 'owbn-core' ); ?>"></th>
        <?php endif; ?>
        <th><?php echo $sort_link( 'title', __( 'Chronicle', 'owbn-core' ) ); ?></th>
        <th><?php echo $sort_link( 'hst_name', __( 'HST', 'owbn-core' ) ); ?></th>
        <th><?php esc_html_e( 'HST Email', 'owbn-core' ); ?></th>
        <th><?php echo $sort_link( 'cm_name', __( 'CM', 'owbn-core' ) ); ?></th>
        <th><?php esc_html_e( 'CM Email', 'owbn-core' ); ?></th>
        <th title="<?php esc_attr_e( 'Probationary', 'owbn-core' ); ?>"><?php echo $sort_link( 'probationary', __( 'Prob', 'owbn-core' ) ); ?></th>
        <th title="<?php esc_attr_e( 'Satellite', 'owbn-core' ); ?>"><?php echo $sort_link( 'satellite', __( 'Sat', 'owbn-core' ) ); ?></th>
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

        // Satellite: resolve to parent chronicle's cm_info + slug. Parent lookup
        // reads from $all_rows so inheritance works even when the parent has been
        // filtered out of the visible $rows set.
        if ( $r['satellite'] && $r['parent_id'] && isset( $id_to_slug[ $r['parent_id'] ] ) ) {
            $parent_slug = $id_to_slug[ $r['parent_id'] ];
            if ( isset( $all_rows[ $parent_slug ] ) ) {
                $cm_info = $all_rows[ $parent_slug ]['cm_info'];
                $cm_slug = $parent_slug;
                $cm_note = sprintf( __( '(from parent: %s)', 'owbn-core' ), $parent_slug );
            }
        }

        $hst_uid  = isset( $hst_info['user'] ) && is_numeric( $hst_info['user'] ) ? (int) $hst_info['user'] : 0;
        $hst_name = $hst_info['display_name'] ?? '';
        $hst_mail = $hst_info['actual_email'] ?? '';

        $cm_uid  = isset( $cm_info['user'] ) && is_numeric( $cm_info['user'] ) ? (int) $cm_info['user'] : 0;
        $cm_name = $cm_info['display_name'] ?? '';
        // Locked {slug}-cm@owbn.net for display; resolved real email for matching.
        $cm_mail_display = $cm_info['actual_email'] ?? '';
        $cm_user = $cm_uid ? get_userdata( $cm_uid ) : null;
        $cm_mail = ( $cm_user && ! empty( $cm_user->user_email ) )
            ? $cm_user->user_email
            : $cm_mail_display;

        $holders = $holders_map[ 'chronicle/' . $cm_slug . '/cm' ] ?? array();

        $color = 'red'; $note = '—'; $matched_uid = 0;
        if ( $is_admin ) {
            list( $color, $note, $matched_uid ) = $match_holder( $cm_name, $cm_mail, $holders );

            // Apply dismissals: a (slug, holder_uid) pair flagged as ignored is
            // visually suppressed (treated as ignored, not counted as red).
            if ( $color !== 'green' && count( $holders ) === 1 ) {
                $h0 = (int) ( $holders[0]['user_id'] ?? 0 );
                if ( $h0 && isset( $ignored_pairs[ $cm_slug . ':' . $h0 ] ) ) {
                    $color = 'ignored';
                    $note  = __( 'ignored', 'owbn-core' );
                    $matched_uid = $h0;
                }
            }
            if ( ! isset( $tally[ $color ] ) ) $tally[ $color ] = 0;
            $tally[ $color ]++;
        }
        $row_bg = $is_admin ? ( $color_bg[ $color ] ?? '' ) : '';

        // Bulk-eligible rows: exactly one holder, status not green/ignored, on local host.
        $bulk_eligible = $can_bulk
            && ( 'green' !== $color ) && ( 'ignored' !== $color )
            && ( count( $holders ) === 1 )
            && ( $cm_slug === $slug );
        $bulk_uid = $bulk_eligible ? (int) ( $holders[0]['user_id'] ?? 0 ) : 0;

        $detail_url = home_url( '/chronicle-detail/?slug=' . rawurlencode( $slug ) );
        ?>
        <tr<?php echo $row_bg ? ' style="background:' . esc_attr( $row_bg ) . ';"' : ''; ?>>
            <?php if ( $can_bulk ) : ?>
            <td class="owc-cb-cell">
                <?php if ( $bulk_eligible && $bulk_uid ) : ?>
                <input type="checkbox" class="owc-cb-chron"
                    data-slug="<?php echo esc_attr( $slug ); ?>"
                    data-user="<?php echo esc_attr( $bulk_uid ); ?>">
                <?php endif; ?>
            </td>
            <?php endif; ?>
            <td>
                <a href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html( $title ); ?></a>
                <span style="color:#50575e;">(<code><?php echo esc_html( $slug ); ?></code>)</span>
            </td>
            <td><?php echo $user_link( $hst_uid, $hst_name, $allow_user_links ); ?></td>
            <td><?php echo esc_html( $hst_mail ); ?></td>
            <td>
                <?php echo $user_link( $cm_uid, $cm_name, $allow_user_links ); ?>
                <?php if ( $cm_note ) : ?><br><small><?php echo esc_html( $cm_note ); ?></small><?php endif; ?>
            </td>
            <td><?php echo esc_html( $cm_mail_display ); ?></td>
            <td class="owc-flag-cell"><?php echo $r['probationary'] ? '✓' : '—'; ?></td>
            <td class="owc-flag-cell"><?php echo $r['satellite'] ? '✓' : '—'; ?></td>
            <?php if ( $is_admin ) : ?>
            <td>
                <?php if ( empty( $holders ) ) : ?>
                    —
                <?php else :
                    // "Confirm as CM" writes to local yni_posts via the owc_confirm_cm_match
                    // AJAX handler, which only works on the chronicles host. Hide the button
                    // on remote sites — admins can still see the mismatch report.
                    $can_confirm = $is_local_host && ( 'green' !== $color ) && ( $cm_slug === $slug );
                    foreach ( $holders as $h ) :
                        $hid   = (int) ( $h['user_id'] ?? 0 );
                        $hname = $h['display_name'] ?? ( $h['user_login'] ?? '#' . $hid );
                        ?>
                        <div style="margin-bottom:4px;">
                            <?php echo $user_link( $hid, $hname, $allow_user_links ); ?>
                            <br><small><?php echo esc_html( $h['email'] ?? '' ); ?></small>
                            <?php if ( $can_confirm && $hid ) : ?>
                                <br>
                                <button type="button"
                                    class="button button-small owc-confirm-cm"
                                    data-slug="<?php echo esc_attr( $slug ); ?>"
                                    data-user="<?php echo esc_attr( $hid ); ?>"
                                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'owc_confirm_cm_' . $slug ) ); ?>"
                                    style="margin-top:2px;">
                                    <?php esc_html_e( 'Confirm as CM', 'owbn-core' ); ?>
                                </button>
                                <span class="owc-confirm-result" style="font-size:11px; margin-left:4px;"></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach;
                endif; ?>
                <span class="owc-badge" style="background:<?php echo esc_attr( $color_pill[ $color ] ); ?>;">
                    <?php echo esc_html( $note ); ?>
                </span>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php if ( $is_admin ) : ?>
<script type="text/javascript">
jQuery(function($){
    var bulkNonce = '<?php echo esc_js( wp_create_nonce( 'owc_cm_bulk' ) ); ?>';

    $('.owc-confirm-cm').on('click', function(){
        var $btn = $(this);
        var $result = $btn.siblings('.owc-confirm-result');
        $btn.prop('disabled', true);
        $result.text('');
        $.post(ajaxurl, {
            action: 'owc_confirm_cm_match',
            slug:   $btn.data('slug'),
            user:   $btn.data('user'),
            nonce:  $btn.data('nonce')
        }, function(response){
            if (response.success) {
                $result.css('color','#2e7d32').text('✓ ' + (response.data.message || 'saved'));
                $btn.closest('tr').css('background','');
            } else {
                $result.css('color','#d63638').text(response.data || 'Failed');
                $btn.prop('disabled', false);
            }
        }).fail(function(){
            $result.css('color','#d63638').text('Request failed');
            $btn.prop('disabled', false);
        });
    });

    // Bulk selection — By Chronicle
    function refreshBulkChron(){
        var n = $('.owc-cb-chron:checked').length;
        $('#owc-chron-bulk-toolbar .owc-bulk-count').text(n + ' <?php echo esc_js( __( 'selected', 'owbn-core' ) ); ?>');
        $('#owc-bulk-confirm-chron, #owc-bulk-ignore-chron').prop('disabled', n === 0);
    }
    $('#owc-cb-all-chron').on('change', function(){
        $('.owc-cb-chron').prop('checked', $(this).is(':checked'));
        refreshBulkChron();
    });
    $(document).on('change', '.owc-cb-chron', refreshBulkChron);

    function collectChronPairs(){
        return $('.owc-cb-chron:checked').map(function(){
            return { slug: $(this).data('slug'), user: $(this).data('user') };
        }).get();
    }

    function bulkPost(action, pairs, $msg){
        $msg.css('color','#50575e').text('<?php echo esc_js( __( 'Working...', 'owbn-core' ) ); ?>');
        $.post(ajaxurl, {
            action: action,
            pairs: JSON.stringify(pairs),
            nonce: bulkNonce
        }, function(response){
            if (response.success) {
                $msg.css('color','#2e7d32').text('✓ ' + (response.data.message || 'done'));
                setTimeout(function(){ window.location.reload(); }, 800);
            } else {
                $msg.css('color','#d63638').text(response.data || 'Failed');
            }
        }).fail(function(){
            $msg.css('color','#d63638').text('Request failed');
        });
    }

    $('#owc-bulk-confirm-chron').on('click', function(){
        var pairs = collectChronPairs();
        if (!pairs.length) return;
        bulkPost('owc_bulk_confirm_cm_match', pairs, $('#owc-chron-bulk-toolbar .owc-bulk-result'));
    });
    $('#owc-bulk-ignore-chron').on('click', function(){
        var pairs = collectChronPairs();
        if (!pairs.length) return;
        bulkPost('owc_bulk_ignore_cm_match', pairs, $('#owc-chron-bulk-toolbar .owc-bulk-result'));
    });
});
</script>
<div class="owc-staff-summary">
    <span class="pill" style="background:<?php echo esc_attr( $color_pill['green'] ); ?>;"><?php echo esc_html( sprintf( __( 'Matched: %d', 'owbn-core' ), $tally['green'] ) ); ?></span>
    <span class="pill" style="background:<?php echo esc_attr( $color_bg['yellow'] ); ?>;"><?php echo esc_html( sprintf( __( 'Fuzzy: %d', 'owbn-core' ), $tally['yellow'] ) ); ?></span>
    <span class="pill" style="background:<?php echo esc_attr( $color_bg['red'] ); ?>;"><?php echo esc_html( sprintf( __( 'Problems: %d', 'owbn-core' ), $tally['red'] ) ); ?></span>
    <?php if ( $tally['ignored'] > 0 ) : ?>
    <span class="pill" style="background:<?php echo esc_attr( $color_bg['ignored'] ); ?>;"><?php echo esc_html( sprintf( __( 'Ignored: %d', 'owbn-core' ), $tally['ignored'] ) ); ?></span>
    <?php endif; ?>
</div>
<?php endif; ?>
