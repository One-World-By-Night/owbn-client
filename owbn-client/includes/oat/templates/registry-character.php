<?php
/**
 * OAT Client - Registry Character Detail Template
 *
 * Variables available:
 *   $character       array  Character data (id, character_name, chronicle_slug, etc.).
 *   $entries         array  Approved registry entries for this character.
 *   $active_grants   array  Currently active grants.
 *   $expired_grants  array  Expired/future grants.
 *   $can_manage      bool   Whether current user can add/revoke grants.
 *   $character_id    int    Character ID.
 *   $notice          string Notice type from POST action.
 */

defined( 'ABSPATH' ) || exit;

$char_name    = isset( $character['character_name'] ) ? $character['character_name'] : '(unknown)';
$chronicle    = isset( $character['chronicle_slug'] ) ? $character['chronicle_slug'] : '';
$creature     = isset( $character['creature_type'] ) ? $character['creature_type'] : '';
$pc_npc       = isset( $character['pc_npc'] ) ? strtoupper( $character['pc_npc'] ) : '';
$char_status  = isset( $character['status'] ) ? $character['status'] : '';
?>
<div class="wrap">
    <h1><?php echo esc_html( $char_name ); ?> — Registry</h1>

    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=owc-oat-registry' ) ); ?>">&larr; Back to Registry</a>
    </p>

    <?php settings_errors( 'owc_oat_registry' ); ?>

    <table class="form-table">
        <tr><th>Chronicle</th><td><?php echo esc_html( $chronicle ); ?></td></tr>
        <tr><th>Creature Type</th><td><?php echo esc_html( $creature ); ?></td></tr>
        <tr><th>PC/NPC</th><td><?php echo esc_html( $pc_npc ); ?></td></tr>
        <tr><th>Status</th><td><?php echo esc_html( ucfirst( $char_status ) ); ?></td></tr>
    </table>

    <hr>

    <!-- ── Active Grants ────────────────────────────────────────── -->
    <h2>Active Grants (<?php echo count( $active_grants ); ?>)</h2>

    <?php if ( empty( $active_grants ) ) : ?>
        <p>No active grants.</p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Granted By</th>
                    <th>Created</th>
                    <th>Expires</th>
                    <?php if ( $can_manage ) : ?><th>Action</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $active_grants as $g ) :
                    $g_id        = isset( $g['id'] ) ? (int) $g['id'] : 0;
                    $g_type      = isset( $g['grant_type'] ) ? $g['grant_type'] : '';
                    $g_value     = isset( $g['grant_value'] ) ? $g['grant_value'] : '';
                    $g_by        = isset( $g['granted_by'] ) ? $g['granted_by'] : '';
                    $g_created   = isset( $g['created_at'] ) ? $g['created_at'] : '';
                    $g_expires   = isset( $g['expires_at'] ) ? $g['expires_at'] : '';
                ?>
                    <tr>
                        <td><?php echo esc_html( ucfirst( $g_type ) ); ?></td>
                        <td><?php echo esc_html( $g_value ); ?></td>
                        <td><?php echo $g_by ? esc_html( $g_by ) : '<em>system</em>'; ?></td>
                        <td><?php echo esc_html( is_numeric( $g_created ) ? owc_oat_format_date( $g_created ) : $g_created ); ?></td>
                        <td><?php echo $g_expires ? esc_html( is_numeric( $g_expires ) ? owc_oat_format_date( $g_expires ) : $g_expires ) : '<em>never</em>'; ?></td>
                        <?php if ( $can_manage ) : ?>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field( 'owc_oat_revoke_grant' ); ?>
                                    <input type="hidden" name="grant_id" value="<?php echo $g_id; ?>">
                                    <button type="submit" name="owc_oat_revoke_grant" value="1" class="button button-small" onclick="return confirm('Revoke this grant?');">Revoke</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- ── Add Grant ────────────────────────────────────────────── -->
    <?php if ( $can_manage ) : ?>
        <h3>Add Grant</h3>
        <form method="post">
            <?php wp_nonce_field( 'owc_oat_create_grant' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="grant_type">Grant Type</label></th>
                    <td>
                        <select name="grant_type" id="grant_type">
                            <option value="chronicle">Chronicle</option>
                            <option value="coordinator">Coordinator</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="grant_value">Value</label></th>
                    <td>
                        <input type="text" name="grant_value" id="grant_value" placeholder="Chronicle slug or genre slug" class="regular-text" required>
                        <p class="description">Chronicle slug (e.g. "mckn") or coordinator genre (e.g. "assamite").</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="expires_at">Expires</label></th>
                    <td>
                        <input type="date" name="expires_at" id="expires_at">
                        <p class="description">Leave blank for no expiry.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Add Grant', 'secondary', 'owc_oat_create_grant' ); ?>
        </form>
    <?php endif; ?>

    <!-- ── Expired / Historical Grants ──────────────────────────── -->
    <?php if ( ! empty( $expired_grants ) ) : ?>
        <h2>Grant History (<?php echo count( $expired_grants ); ?>)</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Granted By</th>
                    <th>Created</th>
                    <th>Expired</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $expired_grants as $g ) :
                    $g_type    = isset( $g['grant_type'] ) ? $g['grant_type'] : '';
                    $g_value   = isset( $g['grant_value'] ) ? $g['grant_value'] : '';
                    $g_by      = isset( $g['granted_by'] ) ? $g['granted_by'] : '';
                    $g_created = isset( $g['created_at'] ) ? $g['created_at'] : '';
                    $g_expires = isset( $g['expires_at'] ) ? $g['expires_at'] : '';
                ?>
                    <tr>
                        <td><?php echo esc_html( ucfirst( $g_type ) ); ?></td>
                        <td><?php echo esc_html( $g_value ); ?></td>
                        <td><?php echo $g_by ? esc_html( $g_by ) : '<em>system</em>'; ?></td>
                        <td><?php echo esc_html( is_numeric( $g_created ) ? owc_oat_format_date( $g_created ) : $g_created ); ?></td>
                        <td><?php echo $g_expires ? esc_html( is_numeric( $g_expires ) ? owc_oat_format_date( $g_expires ) : $g_expires ) : '—'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <hr>

    <!-- ── Registry Entries ─────────────────────────────────────── -->
    <h2>Registry Entries (<?php echo count( $entries ); ?>)</h2>

    <?php if ( empty( $entries ) ) : ?>
        <p>No approved entries for this character.</p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Domain</th>
                    <th>Form</th>
                    <th>Status</th>
                    <th>Coord Genre</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $entries as $entry ) :
                    $e_id     = isset( $entry['id'] ) ? (int) $entry['id'] : 0;
                    $e_domain = isset( $entry['domain'] ) ? $entry['domain'] : '';
                    $e_form   = isset( $entry['form_slug'] ) ? $entry['form_slug'] : '';
                    $e_status = isset( $entry['status'] ) ? $entry['status'] : '';
                    $e_genre  = isset( $entry['coordinator_genre'] ) ? $entry['coordinator_genre'] : '';
                    $e_created = isset( $entry['created_at'] ) ? $entry['created_at'] : '';
                    $entry_url = admin_url( 'admin.php?page=owc-oat-entry&entry_id=' . $e_id );
                ?>
                    <tr>
                        <td><a href="<?php echo esc_url( $entry_url ); ?>"><strong>#<?php echo esc_html( $e_id ); ?></strong></a></td>
                        <td><?php echo esc_html( $e_domain ); ?></td>
                        <td><?php echo esc_html( str_replace( '_', ' ', $e_form ) ); ?></td>
                        <td><span class="oat-status oat-status-<?php echo esc_attr( $e_status ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $e_status ) ) ); ?></span></td>
                        <td><?php echo esc_html( $e_genre ); ?></td>
                        <td><?php echo esc_html( is_numeric( $e_created ) ? owc_oat_format_date( $e_created ) : $e_created ); ?></td>
                        <td><a href="<?php echo esc_url( $entry_url ); ?>" class="button button-small">View Entry</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
