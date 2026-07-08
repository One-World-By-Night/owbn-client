<?php
/**
 * Conflicts Report tab — duplicate / overlapping chronicle staff.
 *
 * Surfaces accounts that hold more than one chronicle staff position (HST / CM /
 * AST) across active chronicles. Highest severity first:
 *   - HST of more than one chronicle
 *   - HST and CM of the SAME chronicle
 *   - accounts with a missing/placeholder email (data glitch)
 *   - any other multi-position holder (e.g. AST in several chronicles)
 *
 * Uses the remote-aware client API (owc_get_chronicles) so it works on any site.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'owc_get_chronicles' ) ) {
    echo '<p>' . esc_html__( 'owbn-core client API not available on this site.', 'owbn-core' ) . '</p>';
    return;
}

$is_admin    = current_user_can( 'manage_options' );
$is_local    = post_type_exists( 'owbn_chronicle' );
$do_refresh  = $is_admin && isset( $_GET['refresh'] ) && $_GET['refresh'] === '1';
$chronicles  = owc_get_chronicles( $do_refresh );

if ( is_wp_error( $chronicles ) ) {
    echo '<div class="notice notice-error inline"><p>' . esc_html( $chronicles->get_error_message() ) . '</p></div>';
    return;
}
if ( empty( $chronicles ) ) {
    echo '<p>' . esc_html__( 'No chronicles found.', 'owbn-core' ) . '</p>';
    return;
}

// ── Build person → positions map ──────────────────────────────────────────
$people = array();
$add_position = function ( $info, $role, $slug, $title ) use ( &$people ) {
    if ( ! is_array( $info ) ) {
        return;
    }
    $uid   = isset( $info['user'] ) && is_numeric( $info['user'] ) ? (int) $info['user'] : 0;
    $name  = trim( (string) ( $info['display_name'] ?? '' ) );
    $email = strtolower( trim( (string) ( $info['actual_email'] ?? '' ) ) );
    if ( ! $uid && '' === $name && '' === $email ) {
        return;
    }
    $key = $uid ? 'u:' . $uid : ( $email ? 'e:' . $email : 'n:' . strtolower( $name ) );
    if ( ! isset( $people[ $key ] ) ) {
        $people[ $key ] = array( 'uid' => $uid, 'name' => $name, 'email' => $email, 'positions' => array() );
    }
    if ( '' === $people[ $key ]['name'] && '' !== $name ) {
        $people[ $key ]['name'] = $name;
    }
    if ( '' === $people[ $key ]['email'] && '' !== $email ) {
        $people[ $key ]['email'] = $email;
    }
    $people[ $key ]['positions'][] = array( 'slug' => $slug, 'title' => $title, 'role' => $role );
};

foreach ( $chronicles as $c ) {
    $c = is_object( $c ) ? (array) $c : $c;
    $slug = (string) ( $c['slug'] ?? '' );
    if ( '' === $slug ) {
        continue;
    }
    if ( ( $c['status'] ?? 'publish' ) !== 'publish' ) {
        continue; // active chronicles only
    }
    $title = (string) ( $c['title'] ?? $slug );
    $add_position( $c['hst_info'] ?? null, 'HST', $slug, $title );
    $add_position( $c['cm_info'] ?? null, 'CM', $slug, $title );
    if ( is_array( $c['ast_list'] ?? null ) ) {
        foreach ( $c['ast_list'] as $a ) {
            $add_position( $a, 'AST', $slug, $title );
        }
    }
}

// ── Reduce to conflicts (> 1 position) + score severity ───────────────────
$conflicts = array();
foreach ( $people as $key => $p ) {
    if ( count( $p['positions'] ) < 2 ) {
        continue;
    }
    $by_slug = array();
    $hst_cnt = 0;
    foreach ( $p['positions'] as $pos ) {
        $by_slug[ $pos['slug'] ][] = $pos['role'];
        if ( 'HST' === $pos['role'] ) {
            $hst_cnt++;
        }
    }
    $same_chron_hst_cm = false;
    foreach ( $by_slug as $roles ) {
        if ( in_array( 'HST', $roles, true ) && in_array( 'CM', $roles, true ) ) {
            $same_chron_hst_cm = true;
        }
    }
    $multi_hst = $hst_cnt > 1;
    $no_email  = '' === $p['email'] || false !== strpos( $p['email'], 'noemail' );

    $p['multi_hst']         = $multi_hst;
    $p['same_chron_hst_cm'] = $same_chron_hst_cm;
    $p['no_email']          = $no_email;
    $p['score']            = ( $multi_hst ? 1000 : 0 ) + ( $same_chron_hst_cm ? 500 : 0 ) + ( $no_email ? 100 : 0 ) + count( $p['positions'] );
    $conflicts[ $key ]     = $p;
}

uasort( $conflicts, function ( $a, $b ) {
    if ( $a['score'] !== $b['score'] ) {
        return $b['score'] - $a['score'];
    }
    return strcasecmp( $a['name'], $b['name'] );
} );

$allow_user_links = $is_admin && $is_local;
$user_link = function ( $uid, $label ) use ( $allow_user_links ) {
    if ( ! $uid || ! $allow_user_links ) {
        return esc_html( $label ?: '#' . $uid );
    }
    return '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . (int) $uid ) ) . '">' . esc_html( $label ?: '#' . $uid ) . '</a>';
};

// Each position links to that chronicle's detail page so an admin can go act on
// the staffing. Only on the local host (where /chronicle-detail/ resolves and
// the slugs are real); plain pill on remote report views.
$pos_span = function ( $pos ) use ( $is_local ) {
    $cls   = 'owc-conf-pos ' . strtolower( $pos['role'] );
    $label = $pos['role'] . ' · ' . $pos['slug'];
    if ( $is_local ) {
        $url = home_url( '/chronicle-detail/?slug=' . rawurlencode( $pos['slug'] ) );
        return '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
    }
    return '<span class="' . esc_attr( $cls ) . '">' . esc_html( $label ) . '</span>';
};

// Acknowledged (dismissed) conflicts — admins can flag a known-OK overlap so it
// drops out of the active list. Display-only + reversible; keyed by person key.
$ack_map = get_option( 'owc_conflict_ack', array() );
if ( ! is_array( $ack_map ) ) {
    $ack_map = array();
}
$active_conflicts = array();
$ack_conflicts    = array();
foreach ( $conflicts as $ckey => $cp ) {
    if ( isset( $ack_map[ $ckey ] ) ) {
        $ack_conflicts[ $ckey ] = $cp;
    } else {
        $active_conflicts[ $ckey ] = $cp;
    }
}

// Shared row renderer for both the active and acknowledged tables. $cb_class is
// the checkbox class ('' = no checkbox column, e.g. for non-admins).
$render_conflict_row = function ( $key, $p, $cb_class ) use ( $user_link, $pos_span, $is_admin ) {
    $row_bg = ( $p['multi_hst'] || $p['same_chron_hst_cm'] ) ? '#fdeaea' : '';
    ob_start();
    ?>
    <tr<?php echo $row_bg ? ' style="background:' . esc_attr( $row_bg ) . ';"' : ''; ?>>
        <?php if ( $is_admin && $cb_class ) : ?>
        <td class="owc-conf-cb"><input type="checkbox" class="<?php echo esc_attr( $cb_class ); ?>" data-key="<?php echo esc_attr( $key ); ?>"></td>
        <?php endif; ?>
        <td><?php echo $user_link( $p['uid'], $p['name'] ); ?></td>
        <td><?php echo esc_html( $p['email'] ?: '—' ); ?></td>
        <td style="text-align:center;font-weight:600;"><?php echo (int) count( $p['positions'] ); ?></td>
        <td>
            <?php if ( $p['multi_hst'] ) : ?><span class="owc-conf-flag crit"><?php esc_html_e( 'Multiple HST', 'owbn-core' ); ?></span><?php endif; ?>
            <?php if ( $p['same_chron_hst_cm'] ) : ?><span class="owc-conf-flag crit"><?php esc_html_e( 'HST+CM same chronicle', 'owbn-core' ); ?></span><?php endif; ?>
            <?php if ( $p['no_email'] ) : ?><span class="owc-conf-flag warn"><?php esc_html_e( 'No/placeholder email', 'owbn-core' ); ?></span><?php endif; ?>
            <?php if ( ! $p['multi_hst'] && ! $p['same_chron_hst_cm'] && ! $p['no_email'] ) : ?><span class="owc-conf-flag warn"><?php esc_html_e( 'Overlap', 'owbn-core' ); ?></span><?php endif; ?>
        </td>
        <td><?php foreach ( $p['positions'] as $pos ) { echo $pos_span( $pos ); } ?></td>
    </tr>
    <?php
    return ob_get_clean();
};

$client_id = function_exists( 'owc_get_client_id' ) ? owc_get_client_id() : 'owc';
$page_slug = $client_id . '-owc-reports';
?>
<style>
.owc-conf-report { border-collapse: collapse; width: 100%; margin-top: 12px; }
.owc-conf-report th, .owc-conf-report td { padding: 8px 10px; border: 1px solid #c3c4c7; font-size: 13px; vertical-align: top; }
.owc-conf-report th { background: #f6f7f7; text-align: left; font-weight: 600; }
.owc-conf-pos { display: inline-block; margin: 1px 4px 1px 0; padding: 1px 6px; border-radius: 3px; background: #eef0f2; font-size: 12px; }
.owc-conf-pos.hst { background: #d7e8ff; }
.owc-conf-pos.cm  { background: #e6f4ea; }
.owc-conf-flag { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 11px; font-weight: 600; margin-right: 4px; }
.owc-conf-flag.crit { background: #f8d7da; color: #842029; }
.owc-conf-flag.warn { background: #fff3cd; color: #664d03; }
.owc-conf-summary { margin: 12px 0; color: #50575e; }
a.owc-conf-pos { text-decoration: none; color: #1d2327; }
a.owc-conf-pos:hover { text-decoration: underline; }
.owc-conf-report td.owc-conf-cb, .owc-conf-report th.owc-conf-cb { width: 28px; text-align: center; }
.owc-conf-bulk { display: flex; gap: 8px; align-items: center; margin: 12px 0; padding: 8px 10px; background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 3px; }
.owc-conf-bulk .owc-conf-bulk-count { color: #50575e; font-size: 12px; }
.owc-conf-bulk button[disabled] { opacity: 0.5; cursor: not-allowed; }
.owc-conf-bulk .owc-conf-bulk-result { margin-left: auto; font-size: 12px; }
.owc-conf-ack { margin-top: 24px; }
.owc-conf-ack summary { cursor: pointer; font-weight: 600; color: #50575e; }
.owc-conf-ack .owc-conf-report tbody tr { opacity: 0.7; }
</style>

<p><?php esc_html_e( 'Accounts holding more than one chronicle staff position across active chronicles. Most severe first: HST of multiple chronicles, then HST+CM of the same chronicle.', 'owbn-core' ); ?></p>

<?php if ( $is_admin ) :
    $refresh_url = add_query_arg( array( 'page' => $page_slug, 'tab' => 'conflicts', 'refresh' => '1' ), admin_url( 'admin.php' ) );
?>
<p><a href="<?php echo esc_url( $refresh_url ); ?>" class="button"><?php esc_html_e( 'Refresh Data', 'owbn-core' ); ?></a></p>
<?php endif; ?>

<?php if ( empty( $conflicts ) ) : ?>
    <p><em><?php esc_html_e( 'No overlapping staff assignments found.', 'owbn-core' ); ?></em></p>
    <?php return; ?>
<?php endif; ?>

<?php
$active_count = count( $active_conflicts );
$ack_count    = count( $ack_conflicts );
?>

<p class="owc-conf-summary">
    <strong><?php echo esc_html( sprintf( _n( '%d account holds multiple staff positions.', '%d accounts hold multiple staff positions.', $active_count, 'owbn-core' ), $active_count ) ); ?></strong>
    <?php if ( $ack_count ) : ?>
        <span> — <?php echo esc_html( sprintf( _n( '%d acknowledged (hidden below).', '%d acknowledged (hidden below).', $ack_count, 'owbn-core' ), $ack_count ) ); ?></span>
    <?php endif; ?>
</p>

<?php if ( $is_admin && $active_count ) : ?>
<div class="owc-conf-bulk" id="owc-conf-bulk">
    <strong><?php esc_html_e( 'Bulk:', 'owbn-core' ); ?></strong>
    <button type="button" class="button" id="owc-conf-ack-btn" disabled><?php esc_html_e( 'Acknowledge (dismiss)', 'owbn-core' ); ?></button>
    <span class="owc-conf-bulk-count">0 <?php esc_html_e( 'selected', 'owbn-core' ); ?></span>
    <span class="owc-conf-bulk-result"></span>
</div>
<?php endif; ?>

<?php if ( $active_count ) : ?>
<table class="owc-conf-report">
    <thead>
    <tr>
        <?php if ( $is_admin ) : ?><th class="owc-conf-cb"><input type="checkbox" id="owc-conf-all-active" title="<?php esc_attr_e( 'Select all', 'owbn-core' ); ?>"></th><?php endif; ?>
        <th style="width:22%;"><?php esc_html_e( 'Account', 'owbn-core' ); ?></th>
        <th style="width:20%;"><?php esc_html_e( 'Email', 'owbn-core' ); ?></th>
        <th style="width:6%;text-align:center;"><?php esc_html_e( '#', 'owbn-core' ); ?></th>
        <th style="width:22%;"><?php esc_html_e( 'Issues', 'owbn-core' ); ?></th>
        <th><?php esc_html_e( 'Positions', 'owbn-core' ); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ( $active_conflicts as $ckey => $p ) {
        echo $render_conflict_row( $ckey, $p, 'owc-conf-cb-active' );
    } ?>
    </tbody>
</table>
<?php elseif ( $ack_count ) : ?>
    <p><em><?php esc_html_e( 'All flagged overlaps have been acknowledged.', 'owbn-core' ); ?></em></p>
<?php endif; ?>

<?php if ( $is_admin && $ack_count ) : ?>
<details class="owc-conf-ack"<?php echo $active_count ? '' : ' open'; ?>>
    <summary><?php echo esc_html( sprintf( __( 'Acknowledged (%d)', 'owbn-core' ), $ack_count ) ); ?></summary>
    <div class="owc-conf-bulk" id="owc-conf-bulk-restore">
        <strong><?php esc_html_e( 'Bulk:', 'owbn-core' ); ?></strong>
        <button type="button" class="button" id="owc-conf-unack-btn" disabled><?php esc_html_e( 'Restore to active', 'owbn-core' ); ?></button>
        <span class="owc-conf-bulk-count">0 <?php esc_html_e( 'selected', 'owbn-core' ); ?></span>
        <span class="owc-conf-bulk-result"></span>
    </div>
    <table class="owc-conf-report">
        <thead>
        <tr>
            <th class="owc-conf-cb"><input type="checkbox" id="owc-conf-all-ack" title="<?php esc_attr_e( 'Select all', 'owbn-core' ); ?>"></th>
            <th style="width:22%;"><?php esc_html_e( 'Account', 'owbn-core' ); ?></th>
            <th style="width:20%;"><?php esc_html_e( 'Email', 'owbn-core' ); ?></th>
            <th style="width:6%;text-align:center;"><?php esc_html_e( '#', 'owbn-core' ); ?></th>
            <th style="width:22%;"><?php esc_html_e( 'Issues', 'owbn-core' ); ?></th>
            <th><?php esc_html_e( 'Positions', 'owbn-core' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ( $ack_conflicts as $ckey => $p ) {
            echo $render_conflict_row( $ckey, $p, 'owc-conf-cb-ack' );
        } ?>
        </tbody>
    </table>
</details>
<?php endif; ?>

<?php if ( $is_admin ) : ?>
<script type="text/javascript">
jQuery(function($){
    var confNonce = '<?php echo esc_js( wp_create_nonce( 'owc_conflict_bulk' ) ); ?>';

    function wire( allSel, cbSel, btnSel, toolbarSel ){
        function refresh(){
            var n = $(cbSel + ':checked').length;
            $(toolbarSel + ' .owc-conf-bulk-count').text(n + ' <?php echo esc_js( __( 'selected', 'owbn-core' ) ); ?>');
            $(btnSel).prop('disabled', n === 0);
        }
        $(document).on('change', allSel, function(){
            $(cbSel).prop('checked', $(this).is(':checked'));
            refresh();
        });
        $(document).on('change', cbSel, refresh);
    }
    wire('#owc-conf-all-active', '.owc-conf-cb-active', '#owc-conf-ack-btn', '#owc-conf-bulk');
    wire('#owc-conf-all-ack', '.owc-conf-cb-ack', '#owc-conf-unack-btn', '#owc-conf-bulk-restore');

    function collect(cbSel){
        return $(cbSel + ':checked').map(function(){ return $(this).data('key'); }).get();
    }
    function bulkPost(action, keys, $msg){
        if (!keys.length) return;
        $msg.css('color','#50575e').text('<?php echo esc_js( __( 'Working...', 'owbn-core' ) ); ?>');
        $.post(ajaxurl, { action: action, keys: JSON.stringify(keys), nonce: confNonce }, function(r){
            if (r.success) {
                $msg.css('color','#2e7d32').text('✓ ' + (r.data.message || 'done'));
                setTimeout(function(){ window.location.reload(); }, 700);
            } else {
                $msg.css('color','#d63638').text((r.data && r.data.message) ? r.data.message : (r.data || 'Failed'));
            }
        }).fail(function(){ $msg.css('color','#d63638').text('Request failed'); });
    }

    $('#owc-conf-ack-btn').on('click', function(){
        bulkPost('owc_bulk_ack_conflict', collect('.owc-conf-cb-active'), $('#owc-conf-bulk .owc-conf-bulk-result'));
    });
    $('#owc-conf-unack-btn').on('click', function(){
        bulkPost('owc_bulk_unack_conflict', collect('.owc-conf-cb-ack'), $('#owc-conf-bulk-restore .owc-conf-bulk-result'));
    });
});
</script>
<?php endif; ?>
