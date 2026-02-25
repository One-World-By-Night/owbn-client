<?php
/**
 * OAT Client - Entry Detail Template
 * location: includes/oat/templates/entry-detail.php
 *
 * Variables available (all arrays from api.php):
 *   $entry             array  Entry record.
 *   $meta              array  Key => value meta.
 *   $assignees         array  Assignee records.
 *   $timeline          array  Timeline events.
 *   $rules             array  Linked regulation rules.
 *   $timer             array|null  Active timer info.
 *   $bbp_eligible      bool   Whether BBP auto-approve is available.
 *   $available_actions array  Action type strings.
 *   $is_watching       bool   Whether current user is watching.
 *   $domain_label      string Human-readable domain label.
 *   $step_label        string Human-readable step label.
 *   $user_map          array  user_id => display_name.
 *   $domain_fields     array  Form field definitions for read-only rendering.
 *   $created           bool   Whether entry was just created (show success notice).
 *   $entry_id          int    Entry ID.
 *
 * @package OWBN-Client
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve a user ID to display name using the user_map.
 *
 * @param int   $uid
 * @param array $map
 * @return string
 */
function owc_oat_user_name( $uid, $map ) {
    $uid = (int) $uid;
    return isset( $map[ $uid ] ) ? $map[ $uid ] : '#' . $uid;
}
?>
<div class="wrap">
    <h1>Entry #<?php echo esc_html( $entry['id'] ); ?> &mdash; <?php echo esc_html( $domain_label ); ?></h1>

    <?php if ( $created ) : ?>
        <div class="notice notice-success"><p>Entry created successfully.</p></div>
    <?php endif; ?>

    <!-- Watch Toggle -->
    <p>
        <button type="button"
                class="button owc-oat-watch-toggle"
                data-entry-id="<?php echo esc_attr( $entry['id'] ); ?>"
                data-watching="<?php echo $is_watching ? '1' : '0'; ?>">
            <?php echo $is_watching ? 'Unwatch' : 'Watch'; ?>
        </button>
    </p>

    <!-- Entry Header -->
    <div class="oat-entry-header">
        <table class="form-table">
            <tr>
                <th>Status</th>
                <td><span class="oat-status oat-status-<?php echo esc_attr( $entry['status'] ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $entry['status'] ) ) ); ?></span></td>
            </tr>
            <tr>
                <th>Current Step</th>
                <td><?php echo esc_html( $step_label ); ?></td>
            </tr>
            <tr>
                <th>Originator</th>
                <td><?php echo esc_html( owc_oat_user_name( $entry['originator_id'], $user_map ) ); ?></td>
            </tr>
            <?php if ( ! empty( $entry['chronicle_slug'] ) ) : ?>
                <tr><th>Chronicle</th><td><?php echo esc_html( $entry['chronicle_slug'] ); ?></td></tr>
            <?php endif; ?>
            <?php if ( ! empty( $entry['coordinator_genre'] ) ) : ?>
                <tr><th>Coordinator Genre</th><td><?php echo esc_html( $entry['coordinator_genre'] ); ?></td></tr>
            <?php endif; ?>
            <tr><th>Created</th><td><?php echo esc_html( $entry['created_at'] ); ?></td></tr>
            <tr><th>Updated</th><td><?php echo esc_html( $entry['updated_at'] ); ?></td></tr>
        </table>
    </div>

    <!-- Meta Summary -->
    <?php if ( ! empty( $meta ) ) : ?>
        <h2>Details</h2>
        <?php if ( ! empty( $domain_fields ) ) : ?>
            <?php owc_oat_render_fields_readonly( $domain_fields, $meta ); ?>
        <?php else : ?>
            <table class="widefat fixed striped">
                <tbody>
                    <?php foreach ( $meta as $key => $value ) : ?>
                        <tr>
                            <th style="width:200px"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?></th>
                            <td><?php echo esc_html( $value ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Linked Rules -->
    <?php if ( ! empty( $rules ) ) : ?>
        <h2>Linked Regulation Rules</h2>
        <table class="widefat fixed striped">
            <thead><tr><th>ID</th><th>Genre</th><th>Category</th><th>Condition</th><th>PC Level</th><th>Elevated</th></tr></thead>
            <tbody>
                <?php foreach ( $rules as $r ) : ?>
                    <tr>
                        <td><?php echo esc_html( $r['rule_id'] ); ?></td>
                        <td><?php echo esc_html( $r['genre'] ); ?></td>
                        <td><?php echo esc_html( $r['category'] ); ?></td>
                        <td><?php echo esc_html( $r['condition'] ); ?></td>
                        <td><?php echo esc_html( $r['pc_level'] ? ucfirst( str_replace( '_', ' ', $r['pc_level'] ) ) : '—' ); ?></td>
                        <td><?php echo (int) $r['elevation'] ? '<strong>Yes</strong>' : 'No'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Timer Info -->
    <?php if ( $timer ) : ?>
        <h2>Active Timer</h2>
        <table class="form-table">
            <tr><th>Type</th><td><?php echo esc_html( $timer['type'] ); ?></td></tr>
            <tr><th>Expires</th><td><?php echo esc_html( $timer['expires_at'] ); ?></td></tr>
            <tr><th>Status</th><td><?php echo esc_html( $timer['status'] ); ?></td></tr>
        </table>
    <?php endif; ?>

    <!-- Actions -->
    <?php if ( ! empty( $available_actions ) ) : ?>
        <h2>Actions</h2>
        <div class="oat-actions">
            <?php if ( $bbp_eligible ) : ?>
                <div class="oat-action-card oat-bbp-card">
                    <h3>Auto-Approve (BBP)</h3>
                    <p>This entry is eligible for Bump Bump Pass auto-approval.</p>
                    <form method="post" class="owc-oat-action-form">
                        <?php wp_nonce_field( 'owc_oat_entry_action' ); ?>
                        <input type="hidden" name="oat_action" value="auto_approve">
                        <input type="hidden" name="entry_id" value="<?php echo esc_attr( $entry['id'] ); ?>">
                        <textarea name="oat_note" placeholder="Note (optional)" rows="2" class="large-text"></textarea>
                        <button type="submit" class="button button-primary">Invoke Auto-Approve</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php
            $action_labels = array(
                'approve'          => 'Approve',
                'deny'             => 'Deny',
                'request_changes'  => 'Request Changes',
                'cancel'           => 'Cancel / Withdraw',
                'bump'             => 'Bump',
                'reassign'         => 'Reassign',
                'delegate'         => 'Delegate',
                'hold'             => 'Hold',
                'resume'           => 'Resume',
                'record'           => 'Record',
                'council_override' => 'Council Override',
                'timer_extend'     => 'Extend Timer',
            );
            ?>

            <?php foreach ( $available_actions as $action_type ) :
                if ( $action_type === 'auto_approve' ) continue;
                $label = isset( $action_labels[ $action_type ] ) ? $action_labels[ $action_type ] : ucfirst( $action_type );
            ?>
                <div class="oat-action-card">
                    <form method="post" class="owc-oat-action-form">
                        <?php wp_nonce_field( 'owc_oat_entry_action' ); ?>
                        <input type="hidden" name="oat_action" value="<?php echo esc_attr( $action_type ); ?>">
                        <input type="hidden" name="entry_id" value="<?php echo esc_attr( $entry['id'] ); ?>">

                        <strong><?php echo esc_html( $label ); ?></strong>

                        <?php if ( $action_type === 'council_override' ) : ?>
                            <input type="text" name="vote_reference" placeholder="Vote reference (required)" class="large-text" required>
                        <?php endif; ?>

                        <?php if ( $action_type === 'timer_extend' ) : ?>
                            <div class="oat-timer-extend-fields">
                                <label>Days: <input type="number" name="extend_days" min="0" max="90" value="0" class="small-text"></label>
                                <label>Hours: <input type="number" name="extend_hours" min="0" max="23" value="0" class="small-text"></label>
                                <input type="hidden" name="additional_seconds" value="0">
                            </div>
                        <?php endif; ?>

                        <?php if ( $action_type === 'reassign' ) : ?>
                            <div class="oat-user-picker">
                                <input type="text" class="oat-user-search large-text" placeholder="Search by name, login, or role path (e.g. Coordinator/Tremere/Coordinator)">
                                <input type="hidden" name="new_user_id" value="" required>
                                <span class="oat-user-picked"></span>
                            </div>
                        <?php endif; ?>

                        <?php if ( $action_type === 'delegate' ) : ?>
                            <div class="oat-user-picker">
                                <input type="text" class="oat-user-search large-text" placeholder="Search by name, login, or role path (e.g. Coordinator/Tremere/Coordinator)">
                                <input type="hidden" name="delegate_user_id" value="" required>
                                <span class="oat-user-picked"></span>
                            </div>
                        <?php endif; ?>

                        <?php $note_required = ( $action_type !== 'bump' ); ?>
                        <textarea name="oat_note" placeholder="Note (<?php echo $note_required ? 'required' : 'optional'; ?>)" rows="2" class="large-text" <?php echo $note_required ? 'required' : ''; ?>></textarea>
                        <button type="submit" class="button"><?php echo esc_html( $label ); ?></button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Assignees -->
    <?php if ( ! empty( $assignees ) ) : ?>
        <h2>Assignees</h2>
        <table class="widefat fixed striped">
            <thead><tr><th>User</th><th>Step</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ( $assignees as $a ) : ?>
                    <tr>
                        <td><?php echo esc_html( owc_oat_user_name( $a['user_id'], $user_map ) ); ?></td>
                        <td><?php echo esc_html( str_replace( '_', ' ', $a['step'] ) ); ?></td>
                        <td><?php echo esc_html( ucfirst( $a['status'] ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Timeline -->
    <?php if ( ! empty( $timeline ) ) : ?>
        <h2>Timeline</h2>
        <div class="oat-timeline">
            <?php foreach ( $timeline as $event ) : ?>
                <div class="oat-timeline-event oat-tier-<?php echo esc_attr( $event['visibility_tier'] ); ?>">
                    <div class="oat-timeline-meta">
                        <span class="oat-timeline-date"><?php echo esc_html( $event['created_at'] ); ?></span>
                        <span class="oat-timeline-action"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $event['action_type'] ) ) ); ?></span>
                        <span class="oat-timeline-actor">by <?php echo esc_html( owc_oat_user_name( $event['actor_id'], $user_map ) ); ?></span>
                        <span class="oat-timeline-tier">[<?php echo esc_html( $event['visibility_tier'] ); ?>]</span>
                    </div>
                    <?php if ( ! empty( $event['note'] ) ) : ?>
                        <div class="oat-timeline-note"><?php echo esc_html( $event['note'] ); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
