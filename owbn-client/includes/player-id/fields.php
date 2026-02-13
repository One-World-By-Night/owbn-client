<?php
/**
 * Player ID — Profile Field, Validation, Admin Column, Shortcode
 *
 * @package OWBN-Client
 * @since 4.1.0
 */

defined('ABSPATH') || exit;

$owc_pid_mode = get_option(owc_option_name('player_id_mode'), 'client');

// ── Profile Field ────────────────────────────────────────────────────────────

add_action('show_user_profile', 'owc_pid_show_field');
add_action('edit_user_profile', 'owc_pid_show_field');

function owc_pid_show_field($user) {
    $player_id = get_user_meta($user->ID, OWC_PLAYER_ID_META_KEY, true);
    $mode      = get_option(owc_option_name('player_id_mode'), 'client');
    $can_edit  = ($mode === 'server') && current_user_can('manage_options');
    ?>
    <h3><?php esc_html_e('Player Information', 'owbn-client'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="owc_player_id"><?php esc_html_e('Player ID', 'owbn-client'); ?></label></th>
            <td>
                <?php if ($can_edit) : ?>
                    <input type="text"
                           name="owc_player_id"
                           id="owc_player_id"
                           value="<?php echo esc_attr($player_id); ?>"
                           class="regular-text" />
                    <p class="description"><?php esc_html_e('Must be unique across all users.', 'owbn-client'); ?></p>
                <?php else : ?>
                    <strong><?php echo esc_html($player_id ?: __('Not set', 'owbn-client')); ?></strong>
                    <?php if ($mode === 'client') : ?>
                        <p class="description"><?php esc_html_e('Managed by SSO server.', 'owbn-client'); ?></p>
                    <?php else : ?>
                        <p class="description"><?php esc_html_e('Contact an administrator to change.', 'owbn-client'); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <?php
}

// ── Save (Server Mode Only) ──────────────────────────────────────────────────

add_action('personal_options_update', 'owc_pid_save_field');
add_action('edit_user_profile_update', 'owc_pid_save_field');

function owc_pid_save_field($user_id) {
    if (get_option(owc_option_name('player_id_mode'), 'client') !== 'server') {
        return;
    }
    if (!current_user_can('manage_options')) {
        return;
    }
    if (!isset($_POST['owc_player_id'])) {
        return;
    }

    $new_id = sanitize_text_field(wp_unslash($_POST['owc_player_id']));

    if ($new_id !== '' && !owc_pid_is_unique($new_id, $user_id)) {
        add_action('user_profile_update_errors', function ($errors) use ($new_id) {
            $errors->add(
                'owc_player_id_duplicate',
                sprintf(
                    /* translators: %s: the duplicate player ID */
                    __('Player ID "%s" is already in use.', 'owbn-client'),
                    esc_html($new_id)
                )
            );
        });
        return;
    }

    update_user_meta($user_id, OWC_PLAYER_ID_META_KEY, $new_id);
}

// ── Uniqueness Check ─────────────────────────────────────────────────────────

function owc_pid_is_unique($player_id, $exclude_user_id = 0) {
    if (empty($player_id)) {
        return false;
    }

    $users = get_users([
        'meta_key'   => OWC_PLAYER_ID_META_KEY,
        'meta_value' => $player_id,
        'exclude'    => [$exclude_user_id],
        'number'     => 1,
        'fields'     => 'ID',
    ]);

    return empty($users);
}

// ── Registration Form (Server Mode Only) ─────────────────────────────────────

if ($owc_pid_mode === 'server') {
    add_action('register_form', 'owc_pid_registration_field');
    add_filter('registration_errors', 'owc_pid_registration_errors', 10, 3);
    add_action('user_register', 'owc_pid_registration_save');
}

function owc_pid_registration_field() {
    $value = isset($_POST['owc_player_id']) ? sanitize_text_field(wp_unslash($_POST['owc_player_id'])) : '';
    ?>
    <p>
        <label for="owc_player_id"><?php esc_html_e('Player ID', 'owbn-client'); ?><br />
            <input type="text" name="owc_player_id" id="owc_player_id"
                   class="input" value="<?php echo esc_attr($value); ?>"
                   size="25" required />
        </label>
    </p>
    <?php
}

function owc_pid_registration_errors($errors, $sanitized_user_login, $user_email) {
    if (empty($_POST['owc_player_id'])) {
        $errors->add('owc_pid_empty', __('Player ID is required.', 'owbn-client'));
        return $errors;
    }

    $player_id = sanitize_text_field(wp_unslash($_POST['owc_player_id']));
    if (!owc_pid_is_unique($player_id)) {
        $errors->add('owc_pid_duplicate', sprintf(
            __('Player ID "%s" is already in use.', 'owbn-client'),
            esc_html($player_id)
        ));
    }

    return $errors;
}

function owc_pid_registration_save($user_id) {
    if (!empty($_POST['owc_player_id'])) {
        update_user_meta($user_id, OWC_PLAYER_ID_META_KEY, sanitize_text_field(wp_unslash($_POST['owc_player_id'])));
    }
}

// ── Admin User List Column ───────────────────────────────────────────────────

add_filter('manage_users_columns', function ($columns) {
    $columns['player_id'] = __('Player ID', 'owbn-client');
    return $columns;
});

add_filter('manage_users_custom_column', function ($value, $column, $user_id) {
    if ('player_id' === $column) {
        return esc_html(get_user_meta($user_id, OWC_PLAYER_ID_META_KEY, true) ?: '—');
    }
    return $value;
}, 10, 3);

// ── Shortcode ────────────────────────────────────────────────────────────────

add_shortcode('player_id', function () {
    $user = wp_get_current_user();
    if ($user->ID) {
        return esc_html(get_user_meta($user->ID, OWC_PLAYER_ID_META_KEY, true));
    }
    return '';
});

// ── Login Notice (Missing Player ID) ─────────────────────────────────────────

add_action('admin_notices', function () {
    if (!is_admin()) {
        return;
    }
    $user_id   = get_current_user_id();
    $player_id = get_user_meta($user_id, OWC_PLAYER_ID_META_KEY, true);

    if (empty($player_id)) {
        echo '<div class="notice notice-warning"><p>';
        esc_html_e('Your Player ID is not set. Contact an administrator or log in via SSO to sync it.', 'owbn-client');
        echo '</p></div>';
    }
});
