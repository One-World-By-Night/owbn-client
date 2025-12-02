<?php

/**
 * OWBN-CC-Client Settings Page
 * 
 * @package OWBN-CC-Client
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

// ══════════════════════════════════════════════════════════════════════════════
// REGISTER SETTINGS
// ══════════════════════════════════════════════════════════════════════════════

add_action('admin_init', function () {
    $group = ccc_get_client_id() . '_ccc_settings';

    // Chronicles
    register_setting($group, ccc_option_name('enable_chronicles'), [
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($group, ccc_option_name('chronicles_mode'), [
        'type' => 'string',
        'default' => 'local',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting($group, ccc_option_name('chronicles_url'), [
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    register_setting($group, ccc_option_name('chronicles_api_key'), [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    // Coordinators
    register_setting($group, ccc_option_name('enable_coordinators'), [
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($group, ccc_option_name('coordinators_mode'), [
        'type' => 'string',
        'default' => 'local',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting($group, ccc_option_name('coordinators_url'), [
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    register_setting($group, ccc_option_name('coordinators_api_key'), [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
});

// ══════════════════════════════════════════════════════════════════════════════
// RENDER SETTINGS PAGE
// ══════════════════════════════════════════════════════════════════════════════

function ccc_render_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $client_id = ccc_get_client_id();
    $group = $client_id . '_ccc_settings';

    // Current values
    $chron_enabled = get_option(ccc_option_name('enable_chronicles'), false);
    $chron_mode    = get_option(ccc_option_name('chronicles_mode'), 'local');
    $chron_url     = get_option(ccc_option_name('chronicles_url'), '');
    $chron_key     = get_option(ccc_option_name('chronicles_api_key'), '');

    $coord_enabled = get_option(ccc_option_name('enable_coordinators'), false);
    $coord_mode    = get_option(ccc_option_name('coordinators_mode'), 'local');
    $coord_url     = get_option(ccc_option_name('coordinators_url'), '');
    $coord_key     = get_option(ccc_option_name('coordinators_api_key'), '');

?>
    <div class="wrap">
        <h1><?php esc_html_e('OWBN CC Client Settings', 'owbn-cc-client'); ?></h1>

        <?php settings_errors(); ?>

        <form method="post" action="options.php">
            <?php settings_fields($group); ?>

            <!-- CHRONICLES -->
            <h2><?php esc_html_e('Chronicles', 'owbn-cc-client'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable', 'owbn-cc-client'); ?></th>
                    <td>
                        <label>
                            <input type="hidden" name="<?php echo esc_attr(ccc_option_name('enable_chronicles')); ?>" value="0" />
                            <input type="checkbox"
                                name="<?php echo esc_attr(ccc_option_name('enable_chronicles')); ?>"
                                id="ccc_enable_chronicles"
                                value="1"
                                <?php checked($chron_enabled); ?> />
                            <?php esc_html_e('Enable Chronicles', 'owbn-cc-client'); ?>
                        </label>
                    </td>
                </tr>
                <tr class="ccc-chronicles-options" <?php echo $chron_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Data Source', 'owbn-cc-client'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio"
                                    name="<?php echo esc_attr(ccc_option_name('chronicles_mode')); ?>"
                                    class="ccc-chronicles-mode"
                                    value="local"
                                    <?php checked($chron_mode, 'local'); ?> />
                                <?php esc_html_e('Local (same site)', 'owbn-cc-client'); ?>
                            </label><br>
                            <label>
                                <input type="radio"
                                    name="<?php echo esc_attr(ccc_option_name('chronicles_mode')); ?>"
                                    class="ccc-chronicles-mode"
                                    value="remote"
                                    <?php checked($chron_mode, 'remote'); ?> />
                                <?php esc_html_e('Remote API', 'owbn-cc-client'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr class="ccc-chronicles-options ccc-chronicles-remote" <?php echo ($chron_enabled && $chron_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('API URL', 'owbn-cc-client'); ?></th>
                    <td>
                        <input type="url"
                            name="<?php echo esc_attr(ccc_option_name('chronicles_url')); ?>"
                            value="<?php echo esc_url($chron_url); ?>"
                            class="regular-text"
                            placeholder="https://example.com/wp-json/owbn-cc/v1/" />
                    </td>
                </tr>
                <tr class="ccc-chronicles-options ccc-chronicles-remote" <?php echo ($chron_enabled && $chron_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('API Key', 'owbn-cc-client'); ?></th>
                    <td>
                        <input type="text"
                            name="<?php echo esc_attr(ccc_option_name('chronicles_api_key')); ?>"
                            value="<?php echo esc_attr($chron_key); ?>"
                            class="regular-text code" />
                    </td>
                </tr>
                <tr class="ccc-chronicles-options ccc-chronicles-remote" <?php echo ($chron_enabled && $chron_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"></th>
                    <td>
                        <button type="button" class="button" id="ccc_test_chronicles_api">
                            <?php esc_html_e('Test Connection', 'owbn-cc-client'); ?>
                        </button>
                        <span id="ccc_chronicles_test_result"></span>
                    </td>
                </tr>
            </table>

            <hr />

            <!-- COORDINATORS -->
            <h2><?php esc_html_e('Coordinators', 'owbn-cc-client'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable', 'owbn-cc-client'); ?></th>
                    <td>
                        <label>
                            <input type="hidden" name="<?php echo esc_attr(ccc_option_name('enable_coordinators')); ?>" value="0" />
                            <input type="checkbox"
                                name="<?php echo esc_attr(ccc_option_name('enable_coordinators')); ?>"
                                id="ccc_enable_coordinators"
                                value="1"
                                <?php checked($coord_enabled); ?> />
                            <?php esc_html_e('Enable Coordinators', 'owbn-cc-client'); ?>
                        </label>
                    </td>
                </tr>
                <tr class="ccc-coordinators-options" <?php echo $coord_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Data Source', 'owbn-cc-client'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio"
                                    name="<?php echo esc_attr(ccc_option_name('coordinators_mode')); ?>"
                                    class="ccc-coordinators-mode"
                                    value="local"
                                    <?php checked($coord_mode, 'local'); ?> />
                                <?php esc_html_e('Local (same site)', 'owbn-cc-client'); ?>
                            </label><br>
                            <label>
                                <input type="radio"
                                    name="<?php echo esc_attr(ccc_option_name('coordinators_mode')); ?>"
                                    class="ccc-coordinators-mode"
                                    value="remote"
                                    <?php checked($coord_mode, 'remote'); ?> />
                                <?php esc_html_e('Remote API', 'owbn-cc-client'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr class="ccc-coordinators-options ccc-coordinators-remote" <?php echo ($coord_enabled && $coord_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('API URL', 'owbn-cc-client'); ?></th>
                    <td>
                        <input type="url"
                            name="<?php echo esc_attr(ccc_option_name('coordinators_url')); ?>"
                            value="<?php echo esc_url($coord_url); ?>"
                            class="regular-text"
                            placeholder="https://example.com/wp-json/owbn-cc/v1/" />
                    </td>
                </tr>
                <tr class="ccc-coordinators-options ccc-coordinators-remote" <?php echo ($coord_enabled && $coord_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('API Key', 'owbn-cc-client'); ?></th>
                    <td>
                        <input type="text"
                            name="<?php echo esc_attr(ccc_option_name('coordinators_api_key')); ?>"
                            value="<?php echo esc_attr($coord_key); ?>"
                            class="regular-text code" />
                    </td>
                </tr>
                <tr class="ccc-coordinators-options ccc-coordinators-remote" <?php echo ($coord_enabled && $coord_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"></th>
                    <td>
                        <button type="button" class="button" id="ccc_test_coordinators_api">
                            <?php esc_html_e('Test Connection', 'owbn-cc-client'); ?>
                        </button>
                        <span id="ccc_coordinators_test_result"></span>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    <script>
        (function($) {
            // Chronicles toggle
            $('#ccc_enable_chronicles').on('change', function() {
                $('.ccc-chronicles-options').toggle(this.checked);
                if (!this.checked) {
                    $('.ccc-chronicles-remote').hide();
                } else {
                    var isRemote = $('.ccc-chronicles-mode:checked').val() === 'remote';
                    $('.ccc-chronicles-remote').toggle(isRemote);
                }
            });

            $('.ccc-chronicles-mode').on('change', function() {
                $('.ccc-chronicles-remote').toggle(this.value === 'remote');
            });

            // Coordinators toggle
            $('#ccc_enable_coordinators').on('change', function() {
                $('.ccc-coordinators-options').toggle(this.checked);
                if (!this.checked) {
                    $('.ccc-coordinators-remote').hide();
                } else {
                    var isRemote = $('.ccc-coordinators-mode:checked').val() === 'remote';
                    $('.ccc-coordinators-remote').toggle(isRemote);
                }
            });

            $('.ccc-coordinators-mode').on('change', function() {
                $('.ccc-coordinators-remote').toggle(this.value === 'remote');
            });

            // Test API buttons
            $('#ccc_test_chronicles_api').on('click', function() {
                ccc_test_api($(this), $('#ccc_chronicles_test_result'), 'chronicles',
                    $('input[name="<?php echo esc_js(ccc_option_name('chronicles_url')); ?>"]').val(),
                    $('input[name="<?php echo esc_js(ccc_option_name('chronicles_api_key')); ?>"]').val()
                );
            });

            $('#ccc_test_coordinators_api').on('click', function() {
                ccc_test_api($(this), $('#ccc_coordinators_test_result'), 'coordinators',
                    $('input[name="<?php echo esc_js(ccc_option_name('coordinators_url')); ?>"]').val(),
                    $('input[name="<?php echo esc_js(ccc_option_name('coordinators_api_key')); ?>"]').val()
                );
            });

            function ccc_test_api($btn, $result, type, url, key) {
                $btn.prop('disabled', true);
                $result.html('<span style="color:#666;"><?php echo esc_js(__('Testing...', 'owbn-cc-client')); ?></span>');

                $.post(ajaxurl, {
                    action: 'ccc_test_api',
                    nonce: '<?php echo wp_create_nonce('ccc_test_api_nonce'); ?>',
                    type: type,
                    url: url,
                    api_key: key
                }, function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        $result.html('<span style="color:green;">✓ ' + response.data.message + '</span>');
                    } else {
                        $result.html('<span style="color:red;">✗ ' + response.data.message + '</span>');
                    }
                }).fail(function() {
                    $btn.prop('disabled', false);
                    $result.html('<span style="color:red;">✗ <?php echo esc_js(__('Request failed.', 'owbn-cc-client')); ?></span>');
                });
            }
        })(jQuery);
    </script>
<?php
}
