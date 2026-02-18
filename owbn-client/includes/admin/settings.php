<?php

/**
 * OWBN-Client Settings Page
 * location : includes/admin/settings.php
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

// ══════════════════════════════════════════════════════════════════════════════
// REGISTER SETTINGS
// ══════════════════════════════════════════════════════════════════════════════

add_action('admin_init', function () {
    $group = owc_get_client_id() . '_owc_settings';

    // Gateway (producer-side)
    register_setting($group, 'owbn_gateway_enabled', [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($group, 'owbn_gateway_api_key', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting($group, 'owbn_gateway_auth_methods', [
        'type'              => 'array',
        'default'           => ['api_key'],
        'sanitize_callback' => function ($value) {
            if (!is_array($value)) {
                return ['api_key'];
            }
            $allowed = ['api_key', 'app_password'];
            return array_values(array_intersect($value, $allowed));
        },
    ]);
    register_setting($group, 'owbn_gateway_domain_whitelist', [
        'type'              => 'array',
        'default'           => [],
        'sanitize_callback' => function ($value) {
            if (!is_array($value)) {
                $lines = preg_split('/[\r\n]+/', (string) $value);
            } else {
                $lines = $value;
            }
            $domains = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $domains[] = sanitize_text_field($line);
                }
            }
            return $domains;
        },
    ]);
    register_setting($group, 'owbn_gateway_logging_enabled', [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);

    // Remote gateway (consumer-side)
    register_setting($group, owc_option_name('remote_url'), [
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    register_setting($group, owc_option_name('remote_api_key'), [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    // Feature enable flags
    register_setting($group, owc_option_name('enable_chronicles'), [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($group, owc_option_name('enable_coordinators'), [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($group, owc_option_name('enable_territories'), [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);

    // Page settings
    register_setting($group, owc_option_name('chronicles_list_page'), [
        'type'              => 'integer',
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ]);
    register_setting($group, owc_option_name('chronicles_detail_page'), [
        'type'              => 'integer',
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ]);
    register_setting($group, owc_option_name('coordinators_list_page'), [
        'type'              => 'integer',
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ]);
    register_setting($group, owc_option_name('coordinators_detail_page'), [
        'type'              => 'integer',
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ]);

    // Player ID
    register_setting($group, owc_option_name('enable_player_id'), [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($group, owc_option_name('player_id_mode'), [
        'type'              => 'string',
        'default'           => 'client',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting($group, owc_option_name('player_id_sso_url'), [
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
    ]);

    // Cache
    register_setting($group, owc_option_name('cache_ttl'), [
        'type'              => 'integer',
        'default'           => 3600,
        'sanitize_callback' => 'absint',
    ]);
});

// ══════════════════════════════════════════════════════════════════════════════
// RENDER SETTINGS PAGE
// ══════════════════════════════════════════════════════════════════════════════

function owc_render_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle cache clear
    if (isset($_POST['owc_clear_cache']) && check_admin_referer('owc_clear_cache_action')) {
        owc_clear_all_caches();
        add_settings_error('owc_settings', 'cache_cleared', __('Cache cleared successfully.', 'owbn-client'), 'success');
    }

    // Handle cache refresh
    if (isset($_POST['owc_refresh_cache']) && check_admin_referer('owc_refresh_cache_action')) {
        $result = owc_refresh_all_caches();
        if (is_wp_error($result)) {
            add_settings_error('owc_settings', 'cache_refresh_failed', $result->get_error_message(), 'error');
        } else {
            add_settings_error('owc_settings', 'cache_refreshed', __('Cache refreshed successfully.', 'owbn-client'), 'success');
        }
    }

    $client_id = owc_get_client_id();
    $group     = $client_id . '_owc_settings';

    // Gateway (producer-side)
    $gw_enabled   = get_option('owbn_gateway_enabled', false);
    $gw_api_key   = get_option('owbn_gateway_api_key', '');
    $gw_auth      = get_option('owbn_gateway_auth_methods', ['api_key']);
    $gw_whitelist = get_option('owbn_gateway_domain_whitelist', []);
    $gw_logging   = get_option('owbn_gateway_logging_enabled', false);
    $gw_base_url  = rest_url('owbn/v1/');

    // Remote gateway (consumer-side)
    $remote_url     = get_option(owc_option_name('remote_url'), '');
    $remote_api_key = get_option(owc_option_name('remote_api_key'), '');

    // Plugin presence
    $manager_active = owc_manager_active();
    $tm_active      = owc_territory_manager_active();

    // Feature flags
    $chron_enabled = (bool) get_option(owc_option_name('enable_chronicles'), false);
    $coord_enabled = (bool) get_option(owc_option_name('enable_coordinators'), false);
    $terr_enabled  = (bool) get_option(owc_option_name('enable_territories'), false);

    // Page settings
    $chron_list_page   = get_option(owc_option_name('chronicles_list_page'), 0);
    $chron_detail_page = get_option(owc_option_name('chronicles_detail_page'), 0);
    $coord_list_page   = get_option(owc_option_name('coordinators_list_page'), 0);
    $coord_detail_page = get_option(owc_option_name('coordinators_detail_page'), 0);

    $cache_ttl = get_option(owc_option_name('cache_ttl'), 3600);

?>
    <div class="wrap">
        <h1><?php esc_html_e('OWBN Client Settings', 'owbn-client'); ?></h1>

        <?php settings_errors(); ?>

        <form method="post" action="options.php">
            <?php settings_fields($group); ?>

            <!-- API GATEWAY (producer-side) -->
            <h2><?php esc_html_e('API Gateway', 'owbn-client'); ?></h2>
            <p class="description"><?php esc_html_e('Expose local data via the unified owbn/v1/ REST namespace. Enable this on producer sites (e.g. chronicles.owbn.net).', 'owbn-client'); ?></p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable Gateway', 'owbn-client'); ?></th>
                    <td>
                        <label>
                            <input type="hidden" name="owbn_gateway_enabled" value="0" />
                            <input type="checkbox"
                                name="owbn_gateway_enabled"
                                id="owbn_gateway_enabled"
                                value="1"
                                <?php checked($gw_enabled); ?> />
                            <?php esc_html_e('Enable the API Gateway', 'owbn-client'); ?>
                        </label>
                    </td>
                </tr>
                <tr class="owbn-gateway-options" <?php echo $gw_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Gateway Base URL', 'owbn-client'); ?></th>
                    <td>
                        <code><?php echo esc_html($gw_base_url); ?></code>
                        <p class="description"><?php esc_html_e('Read-only. This is the base URL consumers will use.', 'owbn-client'); ?></p>
                    </td>
                </tr>
                <tr class="owbn-gateway-options" <?php echo $gw_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('API Key', 'owbn-client'); ?></th>
                    <td>
                        <input type="text"
                            name="owbn_gateway_api_key"
                            id="owbn_gateway_api_key"
                            value="<?php echo esc_attr($gw_api_key); ?>"
                            class="regular-text code" />
                        <button type="button" id="owbn_gateway_generate_key" class="button button-secondary" style="margin-left: 8px;">
                            <?php esc_html_e('Generate', 'owbn-client'); ?>
                        </button>
                        <p class="description"><?php esc_html_e('One key for all endpoints. Share this with consumer sites.', 'owbn-client'); ?></p>
                    </td>
                </tr>
                <tr class="owbn-gateway-options" <?php echo $gw_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Auth Methods', 'owbn-client'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox"
                                    name="owbn_gateway_auth_methods[]"
                                    value="api_key"
                                    <?php checked(in_array('api_key', (array) $gw_auth, true)); ?> />
                                <?php esc_html_e('API Key (x-api-key header)', 'owbn-client'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox"
                                    name="owbn_gateway_auth_methods[]"
                                    value="app_password"
                                    <?php checked(in_array('app_password', (array) $gw_auth, true)); ?> />
                                <?php esc_html_e('Application Password (Authorization: Basic)', 'owbn-client'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr class="owbn-gateway-options" <?php echo $gw_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Domain Whitelist', 'owbn-client'); ?></th>
                    <td>
                        <textarea
                            name="owbn_gateway_domain_whitelist"
                            rows="4"
                            class="regular-text"
                            placeholder="council.owbn.net"><?php echo esc_textarea(implode("\n", (array) $gw_whitelist)); ?></textarea>
                        <p class="description"><?php esc_html_e('One domain per line. Leave empty to allow all origins.', 'owbn-client'); ?></p>
                    </td>
                </tr>
                <tr class="owbn-gateway-options" <?php echo $gw_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Request Logging', 'owbn-client'); ?></th>
                    <td>
                        <label>
                            <input type="hidden" name="owbn_gateway_logging_enabled" value="0" />
                            <input type="checkbox"
                                name="owbn_gateway_logging_enabled"
                                value="1"
                                <?php checked($gw_logging); ?> />
                            <?php esc_html_e('Log gateway requests to PHP error log', 'owbn-client'); ?>
                        </label>
                    </td>
                </tr>
                <tr class="owbn-gateway-options" <?php echo $gw_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Data Sources', 'owbn-client'); ?></th>
                    <td>
                        <?php
                        $gw_sources = apply_filters('owbn_gateway_data_sources', []);
                        if (!empty($gw_sources)) :
                        ?>
                        <ul style="margin:0; padding:0; list-style:none;">
                            <?php foreach ($gw_sources as $key => $source) : ?>
                            <li>
                                <span style="color:#4CAF50;">&#10003;</span>
                                <?php echo esc_html($source['label'] . ' — ' . $source['provider']); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else : ?>
                        <p style="color:#999;"><?php esc_html_e('No data source plugins detected. Install Chronicle Manager and/or Territory Manager on this site.', 'owbn-client'); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <hr />

            <!-- REMOTE GATEWAY (consumer-side) -->
            <h2><?php esc_html_e('Remote Gateway', 'owbn-client'); ?></h2>
            <p class="description"><?php esc_html_e('Configure when this site fetches chronicle, coordinator, and territory data from a remote producer site. Leave empty to use only local data sources.', 'owbn-client'); ?></p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Remote URL', 'owbn-client'); ?></th>
                    <td>
                        <input type="url"
                            name="<?php echo esc_attr(owc_option_name('remote_url')); ?>"
                            value="<?php echo esc_url($remote_url); ?>"
                            class="regular-text"
                            placeholder="https://chronicles.owbn.net" />
                        <p class="description"><?php esc_html_e('Base URL of the producer site (e.g. https://chronicles.owbn.net). The gateway path is appended automatically.', 'owbn-client'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('API Key', 'owbn-client'); ?></th>
                    <td>
                        <input type="text"
                            name="<?php echo esc_attr(owc_option_name('remote_api_key')); ?>"
                            value="<?php echo esc_attr($remote_api_key); ?>"
                            class="regular-text code" />
                        <p class="description"><?php esc_html_e('API key issued by the producer site. Used for all endpoint requests.', 'owbn-client'); ?></p>
                    </td>
                </tr>
            </table>

            <hr />

            <!-- CHRONICLES -->
            <h2><?php esc_html_e('Chronicles', 'owbn-client'); ?></h2>
            <p class="description">
                <?php
                if ($manager_active && $chron_enabled) {
                    esc_html_e('Chronicle Manager is active — chronicle data is served from local CPTs.', 'owbn-client');
                } elseif ($manager_active) {
                    esc_html_e('Chronicle Manager is installed but Chronicles are not enabled. Enable below to serve chronicle data from local CPTs.', 'owbn-client');
                } elseif ($remote_url) {
                    esc_html_e('No local Chronicle Manager detected — chronicle data will be fetched from the remote gateway.', 'owbn-client');
                } else {
                    esc_html_e('No local Chronicle Manager and no remote gateway configured.', 'owbn-client');
                }
                ?>
            </p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable', 'owbn-client'); ?></th>
                    <td>
                        <label>
                            <input type="hidden" name="<?php echo esc_attr(owc_option_name('enable_chronicles')); ?>" value="0" />
                            <input type="checkbox"
                                name="<?php echo esc_attr(owc_option_name('enable_chronicles')); ?>"
                                id="owc_enable_chronicles"
                                value="1"
                                <?php checked($chron_enabled); ?> />
                            <?php esc_html_e('Enable Chronicles', 'owbn-client'); ?>
                        </label>
                    </td>
                </tr>
                <tr class="owc-chronicles-options" <?php echo $chron_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Chronicles List Page', 'owbn-client'); ?></th>
                    <td>
                        <?php wp_dropdown_pages([
                            'name'              => owc_option_name('chronicles_list_page'),
                            'selected'          => $chron_list_page,
                            'show_option_none'  => __('— Select Page —', 'owbn-client'),
                            'option_none_value' => 0,
                        ]); ?>
                    </td>
                </tr>
                <tr class="owc-chronicles-options" <?php echo $chron_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Chronicles Detail Page', 'owbn-client'); ?></th>
                    <td>
                        <?php wp_dropdown_pages([
                            'name'              => owc_option_name('chronicles_detail_page'),
                            'selected'          => $chron_detail_page,
                            'show_option_none'  => __('— Select Page —', 'owbn-client'),
                            'option_none_value' => 0,
                        ]); ?>
                    </td>
                </tr>
            </table>

            <hr />

            <!-- COORDINATORS -->
            <h2><?php esc_html_e('Coordinators', 'owbn-client'); ?></h2>
            <p class="description">
                <?php
                if ($manager_active && $coord_enabled) {
                    esc_html_e('Chronicle Manager is active — coordinator data is served from local CPTs.', 'owbn-client');
                } elseif ($manager_active) {
                    esc_html_e('Chronicle Manager is installed but Coordinators are not enabled. Enable below to serve coordinator data from local CPTs.', 'owbn-client');
                } elseif ($remote_url) {
                    esc_html_e('No local Chronicle Manager detected — coordinator data will be fetched from the remote gateway.', 'owbn-client');
                } else {
                    esc_html_e('No local Chronicle Manager and no remote gateway configured.', 'owbn-client');
                }
                ?>
            </p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable', 'owbn-client'); ?></th>
                    <td>
                        <label>
                            <input type="hidden" name="<?php echo esc_attr(owc_option_name('enable_coordinators')); ?>" value="0" />
                            <input type="checkbox"
                                name="<?php echo esc_attr(owc_option_name('enable_coordinators')); ?>"
                                id="owc_enable_coordinators"
                                value="1"
                                <?php checked($coord_enabled); ?> />
                            <?php esc_html_e('Enable Coordinators', 'owbn-client'); ?>
                        </label>
                    </td>
                </tr>
                <tr class="owc-coordinators-options" <?php echo $coord_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Coordinators List Page', 'owbn-client'); ?></th>
                    <td>
                        <?php wp_dropdown_pages([
                            'name'              => owc_option_name('coordinators_list_page'),
                            'selected'          => $coord_list_page,
                            'show_option_none'  => __('— Select Page —', 'owbn-client'),
                            'option_none_value' => 0,
                        ]); ?>
                    </td>
                </tr>
                <tr class="owc-coordinators-options" <?php echo $coord_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Coordinators Detail Page', 'owbn-client'); ?></th>
                    <td>
                        <?php wp_dropdown_pages([
                            'name'              => owc_option_name('coordinators_detail_page'),
                            'selected'          => $coord_detail_page,
                            'show_option_none'  => __('— Select Page —', 'owbn-client'),
                            'option_none_value' => 0,
                        ]); ?>
                    </td>
                </tr>
            </table>

            <hr />

            <!-- TERRITORIES -->
            <h2><?php esc_html_e('Territories', 'owbn-client'); ?></h2>
            <p class="description">
                <?php
                if ($tm_active) {
                    esc_html_e('Territory Manager is active — territory data is served from local CPTs.', 'owbn-client');
                } elseif ($remote_url) {
                    esc_html_e('No local Territory Manager detected — territory data will be fetched from the remote gateway.', 'owbn-client');
                } else {
                    esc_html_e('No local Territory Manager and no remote gateway configured.', 'owbn-client');
                }
                ?>
            </p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable', 'owbn-client'); ?></th>
                    <td>
                        <label>
                            <input type="hidden" name="<?php echo esc_attr(owc_option_name('enable_territories')); ?>" value="0" />
                            <input type="checkbox"
                                name="<?php echo esc_attr(owc_option_name('enable_territories')); ?>"
                                id="owc_enable_territories"
                                value="1"
                                <?php checked($terr_enabled); ?> />
                            <?php esc_html_e('Enable Territories', 'owbn-client'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <hr />

            <!-- PLAYER ID -->
            <h2><?php esc_html_e('Player ID', 'owbn-client'); ?></h2>
            <?php
                $pid_enabled = get_option(owc_option_name('enable_player_id'), false);
                $pid_mode    = get_option(owc_option_name('player_id_mode'), 'client');
                $pid_sso_url = get_option(owc_option_name('player_id_sso_url'), '');
            ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable', 'owbn-client'); ?></th>
                    <td>
                        <label>
                            <input type="hidden" name="<?php echo esc_attr(owc_option_name('enable_player_id')); ?>" value="0" />
                            <input type="checkbox"
                                name="<?php echo esc_attr(owc_option_name('enable_player_id')); ?>"
                                id="owc_enable_player_id"
                                value="1"
                                <?php checked($pid_enabled); ?> />
                            <?php esc_html_e('Enable Player ID', 'owbn-client'); ?>
                        </label>
                    </td>
                </tr>
                <tr class="owc-player-id-options" <?php echo $pid_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Mode', 'owbn-client'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio"
                                    name="<?php echo esc_attr(owc_option_name('player_id_mode')); ?>"
                                    class="owc-player-id-mode"
                                    value="server"
                                    <?php checked($pid_mode, 'server'); ?> />
                                <?php esc_html_e('Server — This site manages Player IDs (SSO server)', 'owbn-client'); ?>
                            </label><br>
                            <label>
                                <input type="radio"
                                    name="<?php echo esc_attr(owc_option_name('player_id_mode')); ?>"
                                    class="owc-player-id-mode"
                                    value="client"
                                    <?php checked($pid_mode, 'client'); ?> />
                                <?php esc_html_e('Client — Capture Player ID from SSO login', 'owbn-client'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr class="owc-player-id-options owc-player-id-client" <?php echo ($pid_enabled && $pid_mode === 'client') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('SSO Server URL', 'owbn-client'); ?></th>
                    <td>
                        <input type="url"
                            name="<?php echo esc_attr(owc_option_name('player_id_sso_url')); ?>"
                            value="<?php echo esc_url($pid_sso_url); ?>"
                            class="regular-text"
                            placeholder="https://sso.owbn.net" />
                        <p class="description"><?php esc_html_e('Base URL of the SSO server. Only OAuth responses from this URL will be intercepted.', 'owbn-client'); ?></p>
                    </td>
                </tr>
            </table>

            <hr />

            <!-- CACHE SETTINGS -->
            <h2><?php esc_html_e('Cache Settings', 'owbn-client'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Cache TTL (seconds)', 'owbn-client'); ?></th>
                    <td>
                        <input type="number"
                            name="<?php echo esc_attr(owc_option_name('cache_ttl')); ?>"
                            value="<?php echo esc_attr($cache_ttl); ?>"
                            class="small-text"
                            min="0" />
                        <p class="description"><?php esc_html_e('0 = no caching. Default: 3600 (1 hour)', 'owbn-client'); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>

        <hr />

        <!-- STATUS -->
        <h2><?php esc_html_e('Status', 'owbn-client'); ?></h2>

        <?php
        // Determine data source for each type
        $chron_source = $manager_active ? 'local' : ($remote_url ? 'remote' : 'none');
        $terr_source  = $tm_active      ? 'local' : ($remote_url ? 'remote' : 'none');

        // Read from cache only — avoid triggering remote requests on settings page load
        $chronicles_cache   = get_transient('owc_chronicles_cache');
        $coordinators_cache = get_transient('owc_coordinators_cache');
        $territories_cache  = get_transient('owc_territories_cache');

        // Count local territory CPTs directly
        $territory_cpt_count = 0;
        if ($tm_active) {
            $territory_cpt_count = (int) wp_count_posts('owbn_territory')->publish;
        }

        $pid_on           = get_option(owc_option_name('enable_player_id'), false);
        $pid_current_mode = get_option(owc_option_name('player_id_mode'), 'client');
        $elementor_active = did_action('elementor/loaded');

        // Gateway data sources
        $gw_data_sources = apply_filters('owbn_gateway_data_sources', []);
        ?>

        <table class="widefat" style="max-width:600px;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Feature', 'owbn-client'); ?></th>
                    <th><?php esc_html_e('Status', 'owbn-client'); ?></th>
                    <th><?php esc_html_e('Source', 'owbn-client'); ?></th>
                </tr>
            </thead>
            <tbody>
            <tr>
                <td><strong><?php esc_html_e('Chronicles', 'owbn-client'); ?></strong></td>
                <td>
                    <?php
                    if (!$chron_enabled) {
                        esc_html_e('Disabled', 'owbn-client');
                    } elseif (is_array($chronicles_cache)) {
                        echo absint(count($chronicles_cache)) . ' ' . esc_html__('records cached', 'owbn-client');
                    } else {
                        esc_html_e('Enabled — no cached data', 'owbn-client');
                    }
                    ?>
                </td>
                <td><?php echo esc_html(ucfirst($chron_source)); ?></td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Coordinators', 'owbn-client'); ?></strong></td>
                <td>
                    <?php
                    if (!$coord_enabled) {
                        esc_html_e('Disabled', 'owbn-client');
                    } elseif (is_array($coordinators_cache)) {
                        echo absint(count($coordinators_cache)) . ' ' . esc_html__('records cached', 'owbn-client');
                    } else {
                        esc_html_e('Enabled — no cached data', 'owbn-client');
                    }
                    ?>
                </td>
                <td><?php echo esc_html(ucfirst($chron_source)); ?></td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Territories', 'owbn-client'); ?></strong></td>
                <td>
                    <?php
                    if (!$terr_enabled) {
                        esc_html_e('Disabled', 'owbn-client');
                    } elseif ($tm_active) {
                        echo absint($territory_cpt_count) . ' ' . esc_html__('published', 'owbn-client');
                    } elseif (is_array($territories_cache)) {
                        echo absint(count($territories_cache)) . ' ' . esc_html__('records cached', 'owbn-client');
                    } else {
                        esc_html_e('Enabled — no cached data', 'owbn-client');
                    }
                    ?>
                </td>
                <td><?php echo esc_html(ucfirst($terr_source)); ?></td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Player ID', 'owbn-client'); ?></strong></td>
                <td colspan="2">
                    <?php
                    if (!$pid_on) {
                        esc_html_e('Disabled', 'owbn-client');
                    } else {
                        echo esc_html(ucfirst($pid_current_mode)) . ' ' . esc_html__('mode', 'owbn-client');
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Remote Gateway', 'owbn-client'); ?></strong></td>
                <td colspan="2">
                    <?php
                    if ($remote_url) {
                        echo esc_html($remote_url);
                    } else {
                        esc_html_e('Not configured', 'owbn-client');
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Gateway (this site)', 'owbn-client'); ?></strong></td>
                <td colspan="2">
                    <?php
                    if ($gw_enabled) {
                        echo esc_html__('Active', 'owbn-client') . ' — ' . esc_html($gw_base_url);
                    } else {
                        esc_html_e('Disabled', 'owbn-client');
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('C&C Manager', 'owbn-client'); ?></strong></td>
                <td colspan="2">
                    <?php echo $manager_active ? esc_html__('Active', 'owbn-client') : esc_html__('Not installed', 'owbn-client'); ?>
                </td>
            </tr>
            <tr>
                <td><strong><?php esc_html_e('Territory Manager', 'owbn-client'); ?></strong></td>
                <td colspan="2">
                    <?php echo $tm_active ? esc_html__('Active', 'owbn-client') : esc_html__('Not installed', 'owbn-client'); ?>
                </td>
            </tr>
            <?php if (!empty($gw_data_sources)) : ?>
            <tr>
                <td><strong><?php esc_html_e('Gateway Sources', 'owbn-client'); ?></strong></td>
                <td colspan="2">
                    <?php
                    $labels = [];
                    foreach ($gw_data_sources as $source) {
                        $labels[] = esc_html($source['label'] . ' (' . $source['provider'] . ')');
                    }
                    echo implode(', ', $labels);
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td><strong><?php esc_html_e('Elementor', 'owbn-client'); ?></strong></td>
                <td colspan="2">
                    <?php
                    if ($elementor_active) {
                        esc_html_e('Active — widgets available', 'owbn-client');
                    } else {
                        esc_html_e('Not active — using shortcodes only', 'owbn-client');
                    }
                    ?>
                </td>
            </tr>
            </tbody>
        </table>

        <hr />

        <!-- CACHE MANAGEMENT -->
        <h2><?php esc_html_e('Cache Management', 'owbn-client'); ?></h2>
        <p class="description"><?php esc_html_e('Clear cached data to fetch fresh content from data sources.', 'owbn-client'); ?></p>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Clear Cache', 'owbn-client'); ?></th>
                <td>
                    <form method="post" style="display:inline;">
                        <?php wp_nonce_field('owc_clear_cache_action'); ?>
                        <?php submit_button(__('Clear All Cache', 'owbn-client'), 'secondary', 'owc_clear_cache', false); ?>
                    </form>
                    <p class="description"><?php esc_html_e('Removes all cached data. Next page load will fetch fresh data.', 'owbn-client'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Refresh Cache', 'owbn-client'); ?></th>
                <td>
                    <form method="post" style="display:inline;">
                        <?php wp_nonce_field('owc_refresh_cache_action'); ?>
                        <?php submit_button(__('Refresh All Cache', 'owbn-client'), 'secondary', 'owc_refresh_cache', false); ?>
                    </form>
                    <p class="description"><?php esc_html_e('Clears and immediately re-fetches all data from sources.', 'owbn-client'); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <script>
        (function($) {
            // Gateway enable/disable toggle
            $('#owbn_gateway_enabled').on('change', function() {
                $('.owbn-gateway-options').toggle(this.checked);
            });

            // Generate API key
            $('#owbn_gateway_generate_key').on('click', function() {
                var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                var key = '';
                for (var i = 0; i < 48; i++) {
                    key += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                $('#owbn_gateway_api_key').val(key);
            });

            // Chronicles enable toggle
            $('#owc_enable_chronicles').on('change', function() {
                $('.owc-chronicles-options').toggle(this.checked);
            });

            // Coordinators enable toggle
            $('#owc_enable_coordinators').on('change', function() {
                $('.owc-coordinators-options').toggle(this.checked);
            });

            // Player ID toggle
            var $pidEnable = $('#owc_enable_player_id');
            $pidEnable.on('change', function() {
                $('.owc-player-id-options').toggle(this.checked);
                if (!this.checked) {
                    $('.owc-player-id-client').hide();
                } else {
                    var isClient = $('.owc-player-id-mode:checked').val() === 'client';
                    $('.owc-player-id-client').toggle(isClient);
                }
            });
            $('.owc-player-id-mode').on('change', function() {
                $('.owc-player-id-client').toggle(this.value === 'client');
            });
        })(jQuery);
    </script>
<?php
}
