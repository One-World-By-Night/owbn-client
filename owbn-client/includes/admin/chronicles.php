<?php

/**
 * OWBN-Client Chronicles Dashboard
 *
 * Shows data source, cache status, record count, and gateway registration
 * for the Chronicles data type. Provides cache clear/refresh actions.
 *
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

function owc_render_chronicles_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // ── Cache actions ──────────────────────────────────────────────────────────
    if (isset($_POST['owc_clear_chronicles_cache']) && check_admin_referer('owc_chronicles_cache_action')) {
        delete_transient('owc_chronicles_cache');
        add_settings_error('owc_chronicles', 'cache_cleared', __('Chronicles cache cleared.', 'owbn-client'), 'success');
    }

    if (isset($_POST['owc_refresh_chronicles_cache']) && check_admin_referer('owc_chronicles_cache_action')) {
        delete_transient('owc_chronicles_cache');
        $result = owc_get_chronicles(true);
        if (is_wp_error($result)) {
            add_settings_error('owc_chronicles', 'refresh_failed', $result->get_error_message(), 'error');
        } else {
            add_settings_error('owc_chronicles', 'cache_refreshed', __('Chronicles cache refreshed.', 'owbn-client'), 'success');
        }
    }

    // ── Status data ────────────────────────────────────────────────────────────
    $is_local    = owc_manager_active() && owc_chronicles_enabled();
    $remote_base = owc_get_remote_base();
    $cache       = get_transient('owc_chronicles_cache');
    $cache_ttl   = owc_get_cache_ttl();

    $gw_enabled = (bool) get_option('owbn_gateway_enabled', false);
    $gw_sources = apply_filters('owbn_gateway_data_sources', []);
    $gw_has_type = false;
    foreach ($gw_sources as $source) {
        if (isset($source['types']) && in_array('chronicle', $source['types'], true)) {
            $gw_has_type = true;
            break;
        }
    }

    if ($is_local) {
        $source_label = __('Local (Chronicle Manager active)', 'owbn-client');
    } elseif ($remote_base) {
        $source_label = sprintf(__('Remote: %s', 'owbn-client'), rtrim($remote_base, '/'));
    } else {
        $source_label = __('Not configured', 'owbn-client');
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Chronicles', 'owbn-client'); ?></h1>

        <?php settings_errors('owc_chronicles'); ?>

        <table class="widefat striped" style="max-width:640px; margin-top:16px;">
            <tbody>
                <tr>
                    <th style="width:180px;"><?php esc_html_e('Data Source', 'owbn-client'); ?></th>
                    <td><?php echo esc_html($source_label); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Records', 'owbn-client'); ?></th>
                    <td><?php
                        if (is_array($cache)) {
                            echo absint(count($cache)) . ' ' . esc_html__('chronicles cached', 'owbn-client');
                        } else {
                            esc_html_e('No cached data', 'owbn-client');
                        }
                    ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Cache TTL', 'owbn-client'); ?></th>
                    <td><?php echo absint($cache_ttl); ?> <?php esc_html_e('seconds', 'owbn-client'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Gateway', 'owbn-client'); ?></th>
                    <td><?php
                        if ($gw_enabled && $gw_has_type) {
                            echo '<span style="color:#4CAF50; font-weight:600;">&#10003; ' . esc_html__('Registered', 'owbn-client') . '</span>';
                        } elseif ($gw_enabled) {
                            esc_html_e('Enabled — no chronicle source registered', 'owbn-client');
                        } else {
                            esc_html_e('Gateway disabled', 'owbn-client');
                        }
                    ?></td>
                </tr>
            </tbody>
        </table>

        <h2 style="margin-top:24px;"><?php esc_html_e('Cache Actions', 'owbn-client'); ?></h2>
        <form method="post" style="display:inline-block; margin-right:8px;">
            <?php wp_nonce_field('owc_chronicles_cache_action'); ?>
            <?php submit_button(__('Clear Cache', 'owbn-client'), 'secondary', 'owc_clear_chronicles_cache', false); ?>
        </form>
        <form method="post" style="display:inline-block;">
            <?php wp_nonce_field('owc_chronicles_cache_action'); ?>
            <?php submit_button(__('Refresh Cache', 'owbn-client'), 'secondary', 'owc_refresh_chronicles_cache', false); ?>
        </form>
    </div>
    <?php
}
