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
 *   $can_edit          bool   Whether current user can edit this character.
 *   $npc_role_options  array  NPC role options derived from current user's ASC roles.
 *   $character_id      int    Character ID.
 *   $notice            string Notice type from POST action.
 */

defined( 'ABSPATH' ) || exit;

$char_name       = isset( $character['character_name'] ) ? $character['character_name'] : '(unknown)';
$chronicle       = isset( $character['chronicle_slug'] ) ? $character['chronicle_slug'] : '';
$creature_genre  = isset( $character['creature_genre'] ) ? $character['creature_genre'] : '';
$creature        = isset( $character['creature_type'] ) ? $character['creature_type'] : '';
$creature_sub    = isset( $character['creature_sub_type'] ) ? $character['creature_sub_type'] : '';
$creature_variant = isset( $character['creature_variant'] ) ? $character['creature_variant'] : '';
$pc_npc          = isset( $character['pc_npc'] ) ? $character['pc_npc'] : '';
$char_status     = isset( $character['status'] ) ? $character['status'] : '';
$player_email    = isset( $character['player_email'] ) ? $character['player_email'] : '';
$player_name     = isset( $character['player_name'] ) ? $character['player_name'] : '';
$npc_coordinator = isset( $character['npc_coordinator'] ) ? $character['npc_coordinator'] : '';
$npc_type        = isset( $character['npc_type'] ) ? $character['npc_type'] : '';
$wp_user_id      = isset( $character['wp_user_id'] ) ? (int) $character['wp_user_id'] : 0;
$wp_user_display = '';
if ( $wp_user_id ) {
    $linked_user = get_userdata( $wp_user_id );
    $wp_user_display = $linked_user ? $linked_user->display_name . ' (' . $linked_user->user_email . ')' : "User #{$wp_user_id} (not found)";
}
?>
<div class="wrap">
    <h1><?php echo esc_html( $char_name ); ?> — Registry</h1>

    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=owc-oat-registry' ) ); ?>">&larr; Back to Registry</a>
    </p>

    <?php settings_errors( 'owc_oat_registry' ); ?>

    <?php if ( ! empty( $can_edit ) ) : ?>
        <!-- ── Editable Character Info ───────────────────────────────── -->
        <form method="post">
            <?php wp_nonce_field( 'owc_oat_update_character' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="character_name">Character Name</label></th>
                    <td><input type="text" name="character_name" id="character_name" value="<?php echo esc_attr( $char_name ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="player_name">Player Name</label></th>
                    <td><input type="text" name="player_name" id="player_name" value="<?php echo esc_attr( $player_name ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="player_email">Player Email</label></th>
                    <td><input type="email" name="player_email" id="player_email" value="<?php echo esc_attr( $player_email ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Linked WP User', 'owbn-archivist' ); ?></th>
                    <td>
                        <input type="hidden" name="wp_user_id" id="wp_user_id" value="<?php echo esc_attr( $wp_user_id ); ?>">
                        <span id="wp_user_display"><?php echo $wp_user_id ? esc_html( $wp_user_display ) : '<em>' . esc_html__( 'Not linked', 'owbn-archivist' ) . '</em>'; ?></span>
                        <button type="button" class="button button-small" id="owc-lookup-wp-user" style="margin-left:8px;"><?php esc_html_e( 'Lookup by Email', 'owbn-archivist' ); ?></button>
                        <?php if ( $wp_user_id ) : ?>
                            <button type="button" class="button button-small" id="owc-unlink-wp-user" style="margin-left:4px;"><?php esc_html_e( 'Unlink', 'owbn-archivist' ); ?></button>
                        <?php endif; ?>
                        <p class="description"><?php esc_html_e( 'Links this character to a WordPress user account for "My Characters" in the registry.', 'owbn-archivist' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="chronicle_slug">Chronicle</label></th>
                    <td><input type="text" name="chronicle_slug" id="chronicle_slug" value="<?php echo esc_attr( $chronicle ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Creature</th>
                    <td>
                        <div data-creature-picker>
                            <p><label>Genre<br>
                                <select name="creature_genre" data-value="<?php echo esc_attr( $creature_genre ); ?>" style="width:100%;max-width:25em;"></select>
                            </label></p>
                            <p><label>Faction<br>
                                <select name="creature_sub_type" data-value="<?php echo esc_attr( $creature_sub ); ?>" style="width:100%;max-width:25em;"></select>
                            </label></p>
                            <p><label>Type<br>
                                <select name="creature_type" data-value="<?php echo esc_attr( $creature ); ?>" style="width:100%;max-width:25em;"></select>
                            </label></p>
                            <p class="oat-creature-variant-wrap"><label>Variant<br>
                                <select name="creature_variant" data-value="<?php echo esc_attr( $creature_variant ); ?>" style="width:100%;max-width:25em;"></select>
                            </label></p>
                        </div>
                        <button type="button" class="button button-small oat-creature-clear" style="margin-top:4px;">Clear Creature</button>
                    </td>
                </tr>
                <tr>
                    <th><label for="pc_npc">PC/NPC</label></th>
                    <td>
                        <select name="pc_npc" id="pc_npc">
                            <option value="pc" <?php selected( $pc_npc, 'pc' ); ?>>PC</option>
                            <option value="npc" <?php selected( $pc_npc, 'npc' ); ?>>NPC</option>
                        </select>
                    </td>
                </tr>
                <?php if ( ! empty( $npc_role_options ) ) : ?>
                <tr id="npc-role-picker-row" style="<?php echo $pc_npc !== 'npc' ? 'display:none;' : ''; ?>">
                    <th><label for="npc_role_picker">NPC Owner</label></th>
                    <td>
                        <select id="npc_role_picker">
                            <option value="">— Select role —</option>
                            <?php foreach ( $npc_role_options as $i => $opt ) : ?>
                                <option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $opt['name'] . ' (' . $opt['email'] . ')' ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Auto-fill NPC owner fields from your roles.</p>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th><label for="status">Status</label></th>
                    <td>
                        <select name="status" id="status">
                            <option value="active" <?php selected( $char_status, 'active' ); ?>>Active</option>
                            <option value="inactive" <?php selected( $char_status, 'inactive' ); ?>>Inactive</option>
                            <option value="dead" <?php selected( $char_status, 'dead' ); ?>>Dead</option>
                            <option value="shelved" <?php selected( $char_status, 'shelved' ); ?>>Shelved</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="npc_coordinator">NPC Coordinator</label></th>
                    <td>
                        <input type="text" name="npc_coordinator" id="npc_coordinator" value="<?php echo esc_attr( $npc_coordinator ); ?>" class="regular-text">
                        <p class="description">Coordinator genre that controls this NPC (e.g. "assamite").</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="npc_type">NPC Type</label></th>
                    <td>
                        <input type="text" name="npc_type" id="npc_type" value="<?php echo esc_attr( $npc_type ); ?>" class="regular-text">
                        <p class="description">e.g. "chronicle", "coordinator", "shared"</p>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Update Character', 'primary', 'owc_oat_update_character' ); ?>
        </form>
    <?php else : ?>
        <!-- ── Read-Only Character Info ──────────────────────────────── -->
        <table class="form-table">
            <tr><th>Chronicle</th><td><?php echo esc_html( $chronicle ); ?></td></tr>
            <tr><th>Creature</th><td><?php echo esc_html( implode( ' / ', array_filter( array( $creature_genre, $creature_sub, $creature, $creature_variant ) ) ) ); ?></td></tr>
            <tr><th>PC/NPC</th><td><?php echo esc_html( strtoupper( $pc_npc ) ); ?></td></tr>
            <tr><th>Status</th><td><?php echo esc_html( ucfirst( $char_status ) ); ?></td></tr>
            <?php if ( $player_name ) : ?>
                <tr><th>Player</th><td><?php echo esc_html( $player_name ); ?></td></tr>
            <?php endif; ?>
        </table>
    <?php endif; ?>

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
    <?php
    // Build form slug → label and domain slug → label maps.
    $form_labels   = array();
    $domain_labels = array();
    if ( class_exists( 'OAT_Form' ) ) {
        foreach ( OAT_Form::get_all() as $f ) {
            $form_labels[ $f->slug ] = $f->label;
        }
    }
    if ( class_exists( 'OAT_Domain_Registry' ) ) {
        foreach ( OAT_Domain_Registry::get_all() as $d ) {
            $domain_labels[ $d['slug'] ] = $d['label'];
        }
    } elseif ( function_exists( 'owc_oat_get_domains' ) ) {
        $domains_list = owc_oat_get_domains();
        if ( ! is_wp_error( $domains_list ) ) {
            foreach ( $domains_list as $d ) {
                $domain_labels[ $d['slug'] ] = $d['label'];
            }
        }
    }
    ?>
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
                        <td><?php echo esc_html( $domain_labels[ $e_domain ] ?? ucwords( str_replace( '_', ' ', $e_domain ) ) ); ?></td>
                        <td><?php echo esc_html( $form_labels[ $e_form ] ?? ucwords( str_replace( '_', ' ', $e_form ) ) ); ?></td>
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

<?php if ( ! empty( $can_edit ) ) : ?>
<script>
(function() {
    var clearBtn = document.querySelector('.oat-creature-clear');
    if ( clearBtn ) {
        clearBtn.addEventListener('click', function() {
            var picker = document.querySelector('[data-creature-picker]');
            if ( ! picker ) return;
            var selects = picker.querySelectorAll('select');
            selects.forEach(function(sel) {
                sel.value = '';
                sel.disabled = true;
            });
            // Re-enable genre so user can re-pick.
            var genre = picker.querySelector('select[name="creature_genre"]');
            if ( genre ) genre.disabled = false;
        });
    }
})();
</script>
<?php endif; ?>

<?php if ( ! empty( $can_edit ) && ! empty( $npc_role_options ) ) : ?>
<script>
(function() {
    var npcRoleOptions = <?php echo wp_json_encode( $npc_role_options ); ?>;
    var pcNpcSelect    = document.getElementById('pc_npc');
    var pickerRow      = document.getElementById('npc-role-picker-row');
    var rolePicker     = document.getElementById('npc_role_picker');

    if ( ! pcNpcSelect || ! pickerRow || ! rolePicker ) return;

    pcNpcSelect.addEventListener('change', function() {
        if ( this.value === 'npc' ) {
            pickerRow.style.display = '';
            if ( npcRoleOptions.length === 1 ) {
                rolePicker.value = '0';
                applyRole( npcRoleOptions[0] );
            }
        } else {
            pickerRow.style.display = 'none';
            rolePicker.value = '';
        }
    });

    rolePicker.addEventListener('change', function() {
        var idx = this.value;
        if ( idx !== '' && npcRoleOptions[ idx ] ) {
            applyRole( npcRoleOptions[ idx ] );
        }
    });

    function applyRole( opt ) {
        setVal('player_email', opt.email);
        setVal('player_name', opt.name);
        setVal('npc_coordinator', opt.npc_coordinator);
        setVal('npc_type', opt.npc_type);
        if ( opt.chronicle_slug ) {
            setVal('chronicle_slug', opt.chronicle_slug);
        }
    }

    function setVal( id, value ) {
        var el = document.getElementById( id );
        if ( el ) el.value = value;
    }
})();
</script>
<?php endif; ?>

<?php if ( ! empty( $can_edit ) ) : ?>
<script>
jQuery(function($) {
    $('#owc-lookup-wp-user').on('click', function() {
        var email = $('#player_email').val();
        if (!email) { alert('Enter a player email first.'); return; }

        $(this).prop('disabled', true).text('Looking up...');
        $.post(ajaxurl, {
            action: 'owc_oat_lookup_wp_user',
            nonce: typeof owc_oat_ajax !== 'undefined' ? owc_oat_ajax.nonce : '',
            email: email
        }, function(resp) {
            $('#owc-lookup-wp-user').prop('disabled', false).text('Lookup by Email');
            if (resp.success && resp.data.user_id) {
                $('#wp_user_id').val(resp.data.user_id);
                $('#wp_user_display').text(resp.data.display_name + ' (' + resp.data.email + ')');
            } else {
                $('#wp_user_display').html('<em>No WP user found for that email</em>');
                $('#wp_user_id').val('0');
            }
        });
    });

    $('#owc-unlink-wp-user').on('click', function() {
        $('#wp_user_id').val('0');
        $('#wp_user_display').html('<em>Not linked</em>');
        $(this).remove();
    });
});
</script>
<?php endif; ?>
