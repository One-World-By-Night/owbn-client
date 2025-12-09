<?php

/**
 * OWBN-Client Settings Page
 * location : includes/admin/settings.php
 * @package OWBN-Client
 * @version 2.1.1
 */

defined('ABSPATH') || exit;

// ══════════════════════════════════════════════════════════════════════════════
// REGISTER SETTINGS
// ══════════════════════════════════════════════════════════════════════════════

add_action('admin_init', function () {
    $group = owc_get_client_id() . '_owc_settings';

    // Chronicles
    register_setting($group, owc_option_name('enable_chronicles'), [
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($group, owc_option_name('chronicles_mode'), [
        'type' => 'string',
        'default' => 'local',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting($group, owc_option_name('chronicles_url'), [
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    register_setting($group, owc_option_name('chronicles_api_key'), [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    // Coordinators
    register_setting($group, owc_option_name('enable_coordinators'), [
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($group, owc_option_name('coordinators_mode'), [
        'type' => 'string',
        'default' => 'local',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting($group, owc_option_name('coordinators_url'), [
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    register_setting($group, owc_option_name('coordinators_api_key'), [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    // Territories
    register_setting($group, owc_option_name('enable_territories'), [
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($group, owc_option_name('territories_mode'), [
        'type' => 'string',
        'default' => 'local',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting($group, owc_option_name('territories_url'), [
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    register_setting($group, owc_option_name('territories_api_key'), [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    // Page Settings
    register_setting($group, owc_option_name('chronicles_list_page'), [
        'type' => 'integer',
        'default' => 0,
        'sanitize_callback' => 'absint',
    ]);
    register_setting($group, owc_option_name('chronicles_detail_page'), [
        'type' => 'integer',
        'default' => 0,
        'sanitize_callback' => 'absint',
    ]);
    register_setting($group, owc_option_name('coordinators_list_page'), [
        'type' => 'integer',
        'default' => 0,
        'sanitize_callback' => 'absint',
    ]);
    register_setting($group, owc_option_name('coordinators_detail_page'), [
        'type' => 'integer',
        'default' => 0,
        'sanitize_callback' => 'absint',
    ]);
    register_setting($group, owc_option_name('territories_list_page'), [
        'type' => 'integer',
        'default' => 0,
        'sanitize_callback' => 'absint',
    ]);
    register_setting($group, owc_option_name('territories_detail_page'), [
        'type' => 'integer',
        'default' => 0,
        'sanitize_callback' => 'absint',
    ]);

    // Cache
    register_setting($group, owc_option_name('cache_ttl'), [
        'type' => 'integer',
        'default' => 3600,
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
    $group = $client_id . '_owc_settings';

    // Current values
    $chron_enabled = get_option(owc_option_name('enable_chronicles'), false);
    $chron_mode    = get_option(owc_option_name('chronicles_mode'), 'local');
    $chron_url     = get_option(owc_option_name('chronicles_url'), '');
    $chron_key     = get_option(owc_option_name('chronicles_api_key'), '');

    $coord_enabled = get_option(owc_option_name('enable_coordinators'), false);
    $coord_mode    = get_option(owc_option_name('coordinators_mode'), 'local');
    $coord_url     = get_option(owc_option_name('coordinators_url'), '');
    $coord_key     = get_option(owc_option_name('coordinators_api_key'), '');

    $terr_enabled = get_option(owc_option_name('enable_territories'), false);
    $terr_mode    = get_option(owc_option_name('territories_mode'), 'local');
    $terr_url     = get_option(owc_option_name('territories_url'), '');
    $terr_key     = get_option(owc_option_name('territories_api_key'), '');

    // Page settings
    $chron_list_page   = get_option(owc_option_name('chronicles_list_page'), 0);
    $chron_detail_page = get_option(owc_option_name('chronicles_detail_page'), 0);
    $coord_list_page   = get_option(owc_option_name('coordinators_list_page'), 0);
    $coord_detail_page = get_option(owc_option_name('coordinators_detail_page'), 0);
    $terr_list_page    = get_option(owc_option_name('territories_list_page'), 0);
    $terr_detail_page  = get_option(owc_option_name('territories_detail_page'), 0);

    $cache_ttl = get_option(owc_option_name('cache_ttl'), 3600);

?>
    <div class="wrap">
        <h1><?php esc_html_e('OWBN Client Settings', 'owbn-client'); ?></h1>

        <?php settings_errors(); ?>

        <form method="post" action="options.php">
            <?php settings_fields($group); ?>

            <!-- CHRONICLES -->
            <h2><?php esc_html_e('Chronicles', 'owbn-client'); ?></h2>
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
                    <th scope="row"><?php esc_html_e('Data Source', 'owbn-client'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio"
                                    name="<?php echo esc_attr(owc_option_name('chronicles_mode')); ?>"
                                    class="owc-chronicles-mode"
                                    value="local"
                                    <?php checked($chron_mode, 'local'); ?> />
                                <?php esc_html_e('Local (same site)', 'owbn-client'); ?>
                            </label><br>
                            <label>
                                <input type="radio"
                                    name="<?php echo esc_attr(owc_option_name('chronicles_mode')); ?>"
                                    class="owc-chronicles-mode"
                                    value="remote"
                                    <?php checked($chron_mode, 'remote'); ?> />
                                <?php esc_html_e('Remote API', 'owbn-client'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr class="owc-chronicles-options owc-chronicles-remote" <?php echo ($chron_enabled && $chron_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('API URL', 'owbn-client'); ?></th>
                    <td>
                        <input type="url"
                            name="<?php echo esc_attr(owc_option_name('chronicles_url')); ?>"
                            value="<?php echo esc_url($chron_url); ?>"
                            class="regular-text"
                            placeholder="https://example.com/wp-json/owbn-cc/v1/" />
                    </td>
                </tr>
                <tr class="owc-chronicles-options owc-chronicles-remote" <?php echo ($chron_enabled && $chron_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('API Key', 'owbn-client'); ?></th>
                    <td>
                        <input type="text"
                            name="<?php echo esc_attr(owc_option_name('chronicles_api_key')); ?>"
                            value="<?php echo esc_attr($chron_key); ?>"
                            class="regular-text code" />
                    </td>
                </tr>
            </table>

            <hr />

            <!-- COORDINATORS -->
            <h2><?php esc_html_e('Coordinators', 'owbn-client'); ?></h2>
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
                    <th scope="row"><?php esc_html_e('Data Source', 'owbn-client'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio"
                                    name="<?php echo esc_attr(owc_option_name('coordinators_mode')); ?>"
                                    class="owc-coordinators-mode"
                                    value="local"
                                    <?php checked($coord_mode, 'local'); ?> />
                                <?php esc_html_e('Local (same site)', 'owbn-client'); ?>
                            </label><br>
                            <label>
                                <input type="radio"
                                    name="<?php echo esc_attr(owc_option_name('coordinators_mode')); ?>"
                                    class="owc-coordinators-mode"
                                    value="remote"
                                    <?php checked($coord_mode, 'remote'); ?> />
                                <?php esc_html_e('Remote API', 'owbn-client'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr class="owc-coordinators-options owc-coordinators-remote" <?php echo ($coord_enabled && $coord_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('API URL', 'owbn-client'); ?></th>
                    <td>
                        <input type="url"
                            name="<?php echo esc_attr(owc_option_name('coordinators_url')); ?>"
                            value="<?php echo esc_url($coord_url); ?>"
                            class="regular-text"
                            placeholder="https://example.com/wp-json/owbn-cc/v1/" />
                    </td>
                </tr>
                <tr class="owc-coordinators-options owc-coordinators-remote" <?php echo ($coord_enabled && $coord_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('API Key', 'owbn-client'); ?></th>
                    <td>
                        <input type="text"
                            name="<?php echo esc_attr(owc_option_name('coordinators_api_key')); ?>"
                            value="<?php echo esc_attr($coord_key); ?>"
                            class="regular-text code" />
                    </td>
                </tr>
            </table>

            <hr />

            <!-- TERRITORIES -->
            <h2><?php esc_html_e('Territories', 'owbn-client'); ?></h2>
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
                <tr class="owc-territories-options" <?php echo $terr_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Data Source', 'owbn-client'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio"
                                    name="<?php echo esc_attr(owc_option_name('territories_mode')); ?>"
                                    class="owc-territories-mode"
                                    value="local"
                                    <?php checked($terr_mode, 'local'); ?> />
                                <?php esc_html_e('Local (same site)', 'owbn-client'); ?>
                            </label><br>
                            <label>
                                <input type="radio"
                                    name="<?php echo esc_attr(owc_option_name('territories_mode')); ?>"
                                    class="owc-territories-mode"
                                    value="remote"
                                    <?php checked($terr_mode, 'remote'); ?> />
                                <?php esc_html_e('Remote API', 'owbn-client'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr class="owc-territories-options owc-territories-remote" <?php echo ($terr_enabled && $terr_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('API URL', 'owbn-client'); ?></th>
                    <td>
                        <input type="url"
                            name="<?php echo esc_attr(owc_option_name('territories_url')); ?>"
                            value="<?php echo esc_url($terr_url); ?>"
                            class="regular-text"
                            placeholder="https://example.com/wp-json/owbn-tm/v1/" />
                    </td>
                </tr>
                <tr class="owc-territories-options owc-territories-remote" <?php echo ($terr_enabled && $terr_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('API Key', 'owbn-client'); ?></th>
                    <td>
                        <input type="text"
                            name="<?php echo esc_attr(owc_option_name('territories_api_key')); ?>"
                            value="<?php echo esc_attr($terr_key); ?>"
                            class="regular-text code" />
                    </td>
                </tr>
            </table>

            <hr />

            <!-- PAGE SETTINGS -->
            <h2><?php esc_html_e('Page Settings', 'owbn-client'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
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
                <tr>
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
                <tr>
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
                <tr>
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
                <tr>
                    <th scope="row"><?php esc_html_e('Territories List Page', 'owbn-client'); ?></th>
                    <td>
                        <?php wp_dropdown_pages([
                            'name'              => owc_option_name('territories_list_page'),
                            'selected'          => $terr_list_page,
                            'show_option_none'  => __('— Select Page —', 'owbn-client'),
                            'option_none_value' => 0,
                        ]); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Territories Detail Page', 'owbn-client'); ?></th>
                    <td>
                        <?php wp_dropdown_pages([
                            'name'              => owc_option_name('territories_detail_page'),
                            'selected'          => $terr_detail_page,
                            'show_option_none'  => __('— Select Page —', 'owbn-client'),
                            'option_none_value' => 0,
                        ]); ?>
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
            // Generic toggle handler
            function setupToggle(prefix) {
                $('#owc_enable_' + prefix).on('change', function() {
                    $('.owc-' + prefix + '-options').toggle(this.checked);
                    if (!this.checked) {
                        $('.owc-' + prefix + '-remote').hide();
                    } else {
                        var isRemote = $('.owc-' + prefix + '-mode:checked').val() === 'remote';
                        $('.owc-' + prefix + '-remote').toggle(isRemote);
                    }
                });

                $('.owc-' + prefix + '-mode').on('change', function() {
                    $('.owc-' + prefix + '-remote').toggle(this.value === 'remote');
                });
            }

            setupToggle('chronicles');
            setupToggle('coordinators');
            setupToggle('territories');
        })(jQuery);
    </script>
<?php
}
