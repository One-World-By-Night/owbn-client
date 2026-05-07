<?php
/**
 * Chronicle Staff Report — "By User" sub-view.
 *
 * Pivots the bulk ASC holders map (chronicle/%/cm) by user. Each user row
 * expands to all their chronicle/{slug}/cm tags, with per-tag validation
 * status against local cm_info. Supports multi-select bulk actions.
 *
 * Included from tab-chronicle-staff.php — relies on shared variables:
 * $is_admin, $is_local_host, $allow_user_links, $user_link, $all_rows,
 * $holders_map, $color_bg, $color_pill, $norm_name, $ignored_pairs.
 */
defined( 'ABSPATH' ) || exit;

if ( ! $is_admin ) {
    echo '<p>' . esc_html__( 'Admin only.', 'owbn-core' ) . '</p>';
    return;
}

if ( empty( $holders_map ) ) {
    echo '<p><em>' . esc_html__( 'No chronicle/{slug}/cm holders found in AccessSchema.', 'owbn-core' ) . '</em></p>';
    return;
}

// Pivot: holders_map[role_path][] = holder → users[uid] = { user, tags[] }
$users = array();
foreach ( $holders_map as $role_path => $holders ) {
    if ( ! is_array( $holders ) || empty( $holders ) ) continue;
    if ( ! preg_match( '#^chronicle/([^/]+)/cm$#', $role_path, $m ) ) continue;
    $slug = $m[1];

    foreach ( $holders as $h ) {
        $uid = (int) ( $h['user_id'] ?? 0 );
        if ( ! $uid ) continue;
        if ( ! isset( $users[ $uid ] ) ) {
            $users[ $uid ] = array(
                'user_id'      => $uid,
                'display_name' => $h['display_name'] ?? ( $h['user_login'] ?? '#' . $uid ),
                'email'        => $h['email'] ?? '',
                'tags'         => array(),
            );
        }

        // Determine status of this (uid, slug) pair against local cm_info.
        $row     = $all_rows[ $slug ] ?? null;
        $status  = 'red';
        $note    = __( 'no chronicle', 'owbn-core' );
        $local_uid = 0;
        $local_name = '';

        if ( $row ) {
            // Satellite resolves to parent's cm_info.
            $effective_cm_info = $row['cm_info'];
            if ( ! empty( $row['satellite'] ) && ! empty( $row['parent_id'] ) ) {
                $parent_slug = '';
                foreach ( $all_rows as $ps => $pr ) {
                    if ( (int) $pr['id'] === (int) $row['parent_id'] ) { $parent_slug = $ps; break; }
                }
                if ( $parent_slug && isset( $all_rows[ $parent_slug ]['cm_info'] ) ) {
                    $effective_cm_info = $all_rows[ $parent_slug ]['cm_info'];
                }
            }
            $local_uid  = (int) ( $effective_cm_info['user'] ?? 0 );
            $local_name = (string) ( $effective_cm_info['display_name'] ?? '' );

            if ( $local_uid && $local_uid === $uid ) {
                $status = 'green';
                $note   = __( 'matches local CM', 'owbn-core' );
            } elseif ( $local_uid ) {
                $status = 'red';
                $note   = sprintf( __( 'local CM is uid %d', 'owbn-core' ), $local_uid );
            } else {
                // No local CM. Try fuzzy name match between holder name and any local data.
                $a = $norm_name( $users[ $uid ]['display_name'] );
                $b = $norm_name( $local_name );
                if ( '' !== $a && '' !== $b && $a === $b ) {
                    $status = 'yellow';
                    $note   = __( 'name matches but no user link', 'owbn-core' );
                } else {
                    $status = 'red';
                    $note   = __( 'no local CM set', 'owbn-core' );
                }
            }
        }

        // Apply dismissal.
        if ( $status !== 'green' && isset( $ignored_pairs[ $slug . ':' . $uid ] ) ) {
            $status = 'ignored';
            $note   = __( 'ignored', 'owbn-core' );
        }

        $users[ $uid ]['tags'][] = array(
            'slug'      => $slug,
            'role_path' => $role_path,
            'status'    => $status,
            'note'      => $note,
            'title'     => $row['title'] ?? $slug,
        );
    }
}

// Roll-up per user: red > yellow > ignored > green.
foreach ( $users as &$u ) {
    $has_red = $has_yellow = $has_green = $has_ignored = false;
    foreach ( $u['tags'] as $t ) {
        if ( $t['status'] === 'red' ) $has_red = true;
        elseif ( $t['status'] === 'yellow' ) $has_yellow = true;
        elseif ( $t['status'] === 'ignored' ) $has_ignored = true;
        elseif ( $t['status'] === 'green' ) $has_green = true;
    }
    if ( $has_red ) $u['rollup'] = 'red';
    elseif ( $has_yellow ) $u['rollup'] = 'yellow';
    elseif ( $has_ignored && ! $has_green ) $u['rollup'] = 'ignored';
    else $u['rollup'] = 'green';

    // Sort tags by slug.
    usort( $u['tags'], function ( $a, $b ) { return strcmp( $a['slug'], $b['slug'] ); } );
}
unset( $u );

// Sort users: red first, then yellow, ignored, green; within each by display_name.
$rank = array( 'red' => 0, 'yellow' => 1, 'ignored' => 2, 'green' => 3 );
uasort( $users, function ( $a, $b ) use ( $rank ) {
    $cmp = ( $rank[ $a['rollup'] ] ?? 9 ) - ( $rank[ $b['rollup'] ] ?? 9 );
    if ( 0 !== $cmp ) return $cmp;
    return strcasecmp( $a['display_name'], $b['display_name'] );
} );

$can_bulk = $is_admin && $is_local_host;
?>

<style>
.owc-by-user-table { border-collapse: collapse; width: 100%; margin-top: 12px; }
.owc-by-user-table th, .owc-by-user-table td { padding: 8px 10px; border: 1px solid #c3c4c7; font-size: 13px; vertical-align: top; }
.owc-by-user-table th { background: #f6f7f7; text-align: left; font-weight: 600; }
.owc-by-user-table .owc-cb-cell { width: 28px; text-align: center; }
.owc-by-user-row.expanded td { border-bottom: none; }
.owc-by-user-tags td { padding: 0; border-top: none; background: #fafbfc; }
.owc-by-user-tags table { width: 100%; border-collapse: collapse; }
.owc-by-user-tags th, .owc-by-user-tags td { padding: 6px 10px; font-size: 12px; border: 1px solid #e0e2e5; }
.owc-by-user-tags th { background: #f0f1f3; }
.owc-toggle-tags { cursor: pointer; user-select: none; color: #2271b1; }
.owc-toggle-tags::before { content: '▶ '; display: inline-block; transition: transform 0.1s; }
.owc-by-user-row.expanded .owc-toggle-tags::before { transform: rotate(90deg); }
</style>

<p><?php esc_html_e( 'Each row is one SSO user holding at least one chronicle/{slug}/cm role. Expand to see their tags and per-tag match status.', 'owbn-core' ); ?></p>

<?php if ( $can_bulk ) : ?>
<div class="owc-bulk-toolbar" id="owc-user-bulk-toolbar">
    <strong><?php esc_html_e( 'Bulk:', 'owbn-core' ); ?></strong>
    <button type="button" class="button" id="owc-bulk-confirm-user" disabled><?php esc_html_e( 'Confirm match', 'owbn-core' ); ?></button>
    <button type="button" class="button" id="owc-bulk-revoke-user" disabled><?php esc_html_e( 'Revoke ASC role', 'owbn-core' ); ?></button>
    <button type="button" class="button" id="owc-bulk-ignore-user" disabled><?php esc_html_e( 'Ignore (dismiss)', 'owbn-core' ); ?></button>
    <span class="owc-bulk-count">0 <?php esc_html_e( 'tags selected', 'owbn-core' ); ?></span>
    <span class="owc-bulk-result" style="margin-left:auto;"></span>
</div>
<?php endif; ?>

<table class="owc-by-user-table">
    <thead>
        <tr>
            <?php if ( $can_bulk ) : ?><th class="owc-cb-cell"><input type="checkbox" id="owc-cb-all-user" title="<?php esc_attr_e( 'Select all tags', 'owbn-core' ); ?>"></th><?php endif; ?>
            <th><?php esc_html_e( 'User', 'owbn-core' ); ?></th>
            <th><?php esc_html_e( 'Email', 'owbn-core' ); ?></th>
            <th><?php esc_html_e( 'Tags', 'owbn-core' ); ?></th>
            <th><?php esc_html_e( 'Status', 'owbn-core' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $users as $u ) :
            $rollup_bg = $color_bg[ $u['rollup'] ] ?? '';
            $rollup_pill = $color_pill[ $u['rollup'] ] ?? '#eee';
            $tag_count = count( $u['tags'] );
            $row_id = 'owc-user-' . $u['user_id'];
        ?>
        <tr class="owc-by-user-row" id="<?php echo esc_attr( $row_id ); ?>" style="<?php echo $rollup_bg ? 'background:' . esc_attr( $rollup_bg ) . ';' : ''; ?>">
            <?php if ( $can_bulk ) : ?>
            <td class="owc-cb-cell">
                <input type="checkbox" class="owc-cb-user-master" data-user="<?php echo esc_attr( $u['user_id'] ); ?>" title="<?php esc_attr_e( 'Select all of this user\'s tags', 'owbn-core' ); ?>">
            </td>
            <?php endif; ?>
            <td><?php echo $user_link( $u['user_id'], $u['display_name'], $allow_user_links ); ?></td>
            <td><?php echo esc_html( $u['email'] ); ?></td>
            <td>
                <span class="owc-toggle-tags" data-target="<?php echo esc_attr( $row_id ); ?>"><?php echo esc_html( sprintf( _n( '%d tag', '%d tags', $tag_count, 'owbn-core' ), $tag_count ) ); ?></span>
            </td>
            <td>
                <span class="owc-badge" style="background:<?php echo esc_attr( $rollup_pill ); ?>;"><?php echo esc_html( ucfirst( $u['rollup'] ) ); ?></span>
            </td>
        </tr>
        <tr class="owc-by-user-tags" data-parent="<?php echo esc_attr( $row_id ); ?>" style="display:none;">
            <td colspan="<?php echo $can_bulk ? 5 : 4; ?>">
                <table>
                    <thead>
                        <tr>
                            <?php if ( $can_bulk ) : ?><th class="owc-cb-cell"></th><?php endif; ?>
                            <th><?php esc_html_e( 'Chronicle', 'owbn-core' ); ?></th>
                            <th><?php esc_html_e( 'Role tag', 'owbn-core' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'owbn-core' ); ?></th>
                            <th><?php esc_html_e( 'Note', 'owbn-core' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $u['tags'] as $t ) :
                            $tag_pill = $color_pill[ $t['status'] ] ?? '#eee';
                            $tag_bg   = $color_bg[ $t['status'] ] ?? '';
                        ?>
                        <tr style="<?php echo $tag_bg ? 'background:' . esc_attr( $tag_bg ) . ';' : ''; ?>">
                            <?php if ( $can_bulk ) : ?>
                            <td class="owc-cb-cell">
                                <input type="checkbox" class="owc-cb-user-tag"
                                    data-user="<?php echo esc_attr( $u['user_id'] ); ?>"
                                    data-email="<?php echo esc_attr( $u['email'] ); ?>"
                                    data-slug="<?php echo esc_attr( $t['slug'] ); ?>"
                                    data-role="<?php echo esc_attr( $t['role_path'] ); ?>"
                                    data-status="<?php echo esc_attr( $t['status'] ); ?>">
                            </td>
                            <?php endif; ?>
                            <td><?php echo esc_html( $t['title'] ); ?> <span style="color:#50575e;">(<code><?php echo esc_html( $t['slug'] ); ?></code>)</span></td>
                            <td><code><?php echo esc_html( $t['role_path'] ); ?></code></td>
                            <td><span class="owc-badge" style="background:<?php echo esc_attr( $tag_pill ); ?>;"><?php echo esc_html( $t['status'] ); ?></span></td>
                            <td><small><?php echo esc_html( $t['note'] ); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if ( $can_bulk ) : ?>
<script type="text/javascript">
jQuery(function($){
    var bulkNonce = '<?php echo esc_js( wp_create_nonce( 'owc_cm_bulk' ) ); ?>';

    // Expand/collapse user rows.
    $('.owc-toggle-tags').on('click', function(){
        var target = $(this).data('target');
        var $tagsRow = $('tr.owc-by-user-tags[data-parent="' + target + '"]');
        var $userRow = $('#' + target);
        $tagsRow.toggle();
        $userRow.toggleClass('expanded');
    });

    function refreshUserBulk(){
        var n = $('.owc-cb-user-tag:checked').length;
        $('#owc-user-bulk-toolbar .owc-bulk-count').text(n + ' <?php echo esc_js( __( 'tags selected', 'owbn-core' ) ); ?>');
        $('#owc-bulk-confirm-user, #owc-bulk-revoke-user, #owc-bulk-ignore-user').prop('disabled', n === 0);
    }

    // Master "select all" toggles all tag checkboxes.
    $('#owc-cb-all-user').on('change', function(){
        var checked = $(this).is(':checked');
        $('.owc-cb-user-tag, .owc-cb-user-master').prop('checked', checked);
        // Open all tag rows when selecting all so the user can see selections.
        if (checked) $('tr.owc-by-user-tags').show().each(function(){
            $('#' + $(this).data('parent')).addClass('expanded');
        });
        refreshUserBulk();
    });

    // User-level master selects all that user's tags.
    $(document).on('change', '.owc-cb-user-master', function(){
        var uid = $(this).data('user');
        var checked = $(this).is(':checked');
        $('.owc-cb-user-tag[data-user="' + uid + '"]').prop('checked', checked);
        if (checked) {
            // expand the user's tag rows so selection is visible
            var $userRow = $(this).closest('tr');
            $userRow.addClass('expanded');
            $('tr.owc-by-user-tags[data-parent="' + $userRow.attr('id') + '"]').show();
        }
        refreshUserBulk();
    });

    $(document).on('change', '.owc-cb-user-tag', refreshUserBulk);

    function collectUserPairs(){
        return $('.owc-cb-user-tag:checked').map(function(){
            return {
                slug:  $(this).data('slug'),
                user:  $(this).data('user'),
                email: $(this).data('email'),
                role:  $(this).data('role')
            };
        }).get();
    }

    function bulkPostUser(action, pairs, $msg){
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

    $('#owc-bulk-confirm-user').on('click', function(){
        var pairs = collectUserPairs();
        if (!pairs.length) return;
        bulkPostUser('owc_bulk_confirm_cm_match', pairs, $('#owc-user-bulk-toolbar .owc-bulk-result'));
    });
    $('#owc-bulk-revoke-user').on('click', function(){
        var pairs = collectUserPairs();
        if (!pairs.length) return;
        if (!confirm('<?php echo esc_js( __( 'Revoke the AccessSchema role for the selected tags? This cannot be undone from this page.', 'owbn-core' ) ); ?>')) return;
        bulkPostUser('owc_bulk_revoke_cm_role', pairs, $('#owc-user-bulk-toolbar .owc-bulk-result'));
    });
    $('#owc-bulk-ignore-user').on('click', function(){
        var pairs = collectUserPairs();
        if (!pairs.length) return;
        bulkPostUser('owc_bulk_ignore_cm_match', pairs, $('#owc-user-bulk-toolbar .owc-bulk-result'));
    });
});
</script>
<?php endif; ?>
