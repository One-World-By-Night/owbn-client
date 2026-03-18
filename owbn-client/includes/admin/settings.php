<?php

/**
 * OWBN-Client Settings Page
 * location : includes/admin/settings.php
 *
 * Tab registry, settings registration, cache-clear hook, and tab router.
 *
 */

defined('ABSPATH') || exit;

/**
 * Sanitize a remote URL setting with SSRF protection.
 *
 * @param string $url Raw URL value from settings form.
 * @return string Sanitized URL, or empty string if blocked.
 */
function owc_sanitize_remote_url( $url ) {
    $url = esc_url_raw( $url );
    if ( empty( $url ) ) {
        return '';
    }
    if ( function_exists( 'owc_validate_remote_url' ) && ! owc_validate_remote_url( $url ) ) {
        add_settings_error(
            'owc_remote_url',
            'owc_ssrf_blocked',
            __( 'The remote URL was rejected because it points to a local or private network address.', 'owbn-client' ),
            'error'
        );
        return '';
    }
    return $url;
}


function owc_get_settings_tabs() {
    $base = owc_get_client_id() . '_owc';
    return array(
        'general'      => array(
            'label'     => __( 'General', 'owbn-client' ),
            'icon'      => 'dashicons-admin-settings',
            'always_on' => true,
            'group'     => $base . '_general',
            'partial'   => __DIR__ . '/settings-tabs/tab-general.php',
        ),
        'chronicles'   => array(
            'label'      => __( 'Chronicles', 'owbn-client' ),
            'icon'       => 'dashicons-book-alt',
            'enable_key' => 'enable_chronicles',
            'group'      => $base . '_chronicles',
            'partial'    => __DIR__ . '/settings-tabs/tab-chronicles.php',
        ),
        'coordinators' => array(
            'label'      => __( 'Coordinators', 'owbn-client' ),
            'icon'       => 'dashicons-groups',
            'enable_key' => 'enable_coordinators',
            'group'      => $base . '_coordinators',
            'partial'    => __DIR__ . '/settings-tabs/tab-coordinators.php',
        ),
        'territories'  => array(
            'label'      => __( 'Territories', 'owbn-client' ),
            'icon'       => 'dashicons-location-alt',
            'enable_key' => 'enable_territories',
            'group'      => $base . '_territories',
            'partial'    => __DIR__ . '/settings-tabs/tab-territories.php',
        ),
        'vote-history' => array(
            'label'      => __( 'Vote History', 'owbn-client' ),
            'icon'       => 'dashicons-chart-bar',
            'enable_key' => 'enable_vote_history',
            'group'      => $base . '_votes',
            'partial'    => __DIR__ . '/settings-tabs/tab-vote-history.php',
        ),
        'player-id'    => array(
            'label'      => __( 'Player ID', 'owbn-client' ),
            'icon'       => 'dashicons-id-alt',
            'enable_key' => 'enable_player_id',
            'group'      => $base . '_player_id',
            'partial'    => __DIR__ . '/settings-tabs/tab-player-id.php',
        ),
        'oat'          => array(
            'label'      => __( 'OAT', 'owbn-client' ),
            'icon'       => 'dashicons-archive',
            'enable_key' => 'enable_oat',
            'group'      => $base . '_oat',
            'partial'    => __DIR__ . '/settings-tabs/tab-oat.php',
        ),
        'accessschema' => array(
            'label'      => __( 'accessSchema', 'owbn-client' ),
            'icon'       => 'dashicons-shield',
            'enable_key' => 'asc_enabled',
            'group'      => $base . '_asc',
            'partial'    => __DIR__ . '/settings-tabs/tab-accessschema.php',
        ),
    );
}


add_action('admin_init', function () {
    $tabs = owc_get_settings_tabs();

    $mode_sanitize = function ($value) {
        return in_array($value, ['local', 'remote'], true) ? $value : 'local';
    };

    // ── General tab ──────────────────────────────────────────────────────
    $g = $tabs['general']['group'];

    register_setting($g, 'owbn_gateway_enabled', [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($g, 'owbn_gateway_api_key', [
        'type'              => 'string',
        'sanitize_callback' => function( $new_val ) {
            $option_name = 'owbn_gateway_api_key';
            $new_val = sanitize_text_field( $new_val );
            return $new_val !== '' ? $new_val : get_option( $option_name, '' );
        },
    ]);
    register_setting($g, 'owbn_gateway_auth_methods', [
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
    register_setting($g, 'owbn_gateway_domain_whitelist', [
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
    register_setting($g, 'owbn_gateway_logging_enabled', [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($g, 'owbn_gateway_sso_url', [
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    register_setting($g, 'owbn_gateway_sso_api_key', [
        'type'              => 'string',
        'sanitize_callback' => function( $new_val ) {
            $option_name = 'owbn_gateway_sso_api_key';
            $new_val = sanitize_text_field( $new_val );
            return $new_val !== '' ? $new_val : get_option( $option_name, '' );
        },
    ]);
    register_setting($g, owc_option_name('remote_url'), [
        'type'              => 'string',
        'sanitize_callback' => 'owc_sanitize_remote_url',
    ]);
    register_setting($g, owc_option_name('remote_api_key'), [
        'type'              => 'string',
        'sanitize_callback' => function( $new_val ) {
            $option_name = owc_option_name( 'remote_api_key' );
            $new_val = sanitize_text_field( $new_val );
            return $new_val !== '' ? $new_val : get_option( $option_name, '' );
        },
    ]);
    register_setting($g, owc_option_name('enable_chronicles'), [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($g, owc_option_name('enable_coordinators'), [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($g, owc_option_name('enable_territories'), [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($g, owc_option_name('enable_vote_history'), [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($g, owc_option_name('enable_player_id'), [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($g, owc_option_name('enable_oat'), [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($g, owc_option_name('asc_enabled'), [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    register_setting($g, owc_option_name('cache_ttl'), [
        'type'              => 'integer',
        'default'           => 3600,
        'sanitize_callback' => 'absint',
    ]);

    // ── Chronicles tab ───────────────────────────────────────────────────
    $g = $tabs['chronicles']['group'];

    register_setting($g, owc_option_name('chronicles_mode'), [
        'type'              => 'string',
        'default'           => 'local',
        'sanitize_callback' => $mode_sanitize,
    ]);
    register_setting($g, owc_option_name('chronicles_remote_url'), [
        'type'              => 'string',
        'sanitize_callback' => 'owc_sanitize_remote_url',
    ]);
    register_setting($g, owc_option_name('chronicles_remote_api_key'), [
        'type'              => 'string',
        'sanitize_callback' => function( $new_val ) {
            $option_name = owc_option_name( 'chronicles_remote_api_key' );
            $new_val = sanitize_text_field( $new_val );
            return $new_val !== '' ? $new_val : get_option( $option_name, '' );
        },
    ]);
    register_setting($g, owc_option_name('chronicles_list_page'), [
        'type'              => 'integer',
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ]);
    register_setting($g, owc_option_name('chronicles_detail_page'), [
        'type'              => 'integer',
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ]);

    // ── Coordinators tab ─────────────────────────────────────────────────
    $g = $tabs['coordinators']['group'];

    register_setting($g, owc_option_name('coordinators_mode'), [
        'type'              => 'string',
        'default'           => 'local',
        'sanitize_callback' => $mode_sanitize,
    ]);
    register_setting($g, owc_option_name('coordinators_remote_url'), [
        'type'              => 'string',
        'sanitize_callback' => 'owc_sanitize_remote_url',
    ]);
    register_setting($g, owc_option_name('coordinators_remote_api_key'), [
        'type'              => 'string',
        'sanitize_callback' => function( $new_val ) {
            $option_name = owc_option_name( 'coordinators_remote_api_key' );
            $new_val = sanitize_text_field( $new_val );
            return $new_val !== '' ? $new_val : get_option( $option_name, '' );
        },
    ]);
    register_setting($g, owc_option_name('coordinators_list_page'), [
        'type'              => 'integer',
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ]);
    register_setting($g, owc_option_name('coordinators_detail_page'), [
        'type'              => 'integer',
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ]);

    // ── Territories tab ──────────────────────────────────────────────────
    $g = $tabs['territories']['group'];

    register_setting($g, owc_option_name('territories_mode'), [
        'type'              => 'string',
        'default'           => 'local',
        'sanitize_callback' => $mode_sanitize,
    ]);
    register_setting($g, owc_option_name('territories_remote_url'), [
        'type'              => 'string',
        'sanitize_callback' => 'owc_sanitize_remote_url',
    ]);
    register_setting($g, owc_option_name('territories_remote_api_key'), [
        'type'              => 'string',
        'sanitize_callback' => function( $new_val ) {
            $option_name = owc_option_name( 'territories_remote_api_key' );
            $new_val = sanitize_text_field( $new_val );
            return $new_val !== '' ? $new_val : get_option( $option_name, '' );
        },
    ]);

    // ── Vote History tab ─────────────────────────────────────────────────
    $g = $tabs['vote-history']['group'];

    register_setting($g, owc_option_name('votes_mode'), [
        'type'              => 'string',
        'default'           => 'local',
        'sanitize_callback' => $mode_sanitize,
    ]);
    register_setting($g, owc_option_name('votes_remote_url'), [
        'type'              => 'string',
        'sanitize_callback' => 'owc_sanitize_remote_url',
    ]);
    register_setting($g, owc_option_name('votes_remote_api_key'), [
        'type'              => 'string',
        'sanitize_callback' => function( $new_val ) {
            $option_name = owc_option_name( 'votes_remote_api_key' );
            $new_val = sanitize_text_field( $new_val );
            return $new_val !== '' ? $new_val : get_option( $option_name, '' );
        },
    ]);

    // ── Player ID tab ────────────────────────────────────────────────────
    $g = $tabs['player-id']['group'];

    register_setting($g, owc_option_name('player_id_mode'), [
        'type'              => 'string',
        'default'           => 'client',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting($g, owc_option_name('player_id_sso_url'), [
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
    ]);

    // ── OAT tab ──────────────────────────────────────────────────────────
    $g = $tabs['oat']['group'];

    register_setting($g, owc_option_name('oat_mode'), [
        'type'              => 'string',
        'default'           => 'local',
        'sanitize_callback' => $mode_sanitize,
    ]);
    register_setting($g, owc_option_name('oat_remote_url'), [
        'type'              => 'string',
        'sanitize_callback' => 'owc_sanitize_remote_url',
    ]);
    register_setting($g, owc_option_name('oat_remote_api_key'), [
        'type'              => 'string',
        'sanitize_callback' => function( $new_val ) {
            $option_name = owc_option_name( 'oat_remote_api_key' );
            $new_val = sanitize_text_field( $new_val );
            return $new_val !== '' ? $new_val : get_option( $option_name, '' );
        },
    ]);

    // ── accessSchema tab ─────────────────────────────────────────────────
    $g = $tabs['accessschema']['group'];

    register_setting($g, owc_option_name('asc_mode'), [
        'type'              => 'string',
        'default'           => 'remote',
        'sanitize_callback' => function ($value) {
            return in_array($value, ['local', 'remote'], true) ? $value : 'remote';
        },
    ]);
    register_setting($g, owc_option_name('asc_remote_url'), [
        'type'              => 'string',
        'sanitize_callback' => 'owc_sanitize_remote_url',
    ]);
    register_setting($g, owc_option_name('asc_remote_api_key'), [
        'type'              => 'string',
        'sanitize_callback' => function( $new_val ) {
            $option_name = owc_option_name( 'asc_remote_api_key' );
            $new_val = sanitize_text_field( $new_val );
            return $new_val !== '' ? $new_val : get_option( $option_name, '' );
        },
    ]);
    register_setting($g, owc_option_name('asc_cache_ttl'), [
        'type'              => 'integer',
        'default'           => 3600,
        'sanitize_callback' => 'absint',
    ]);
});


add_action( 'updated_option', function ( $option, $old_value, $new_value ) {
    if ( $old_value === $new_value ) {
        return;
    }

    // Map option names to the transient key(s) they affect.
    $cache_map = array(
        owc_option_name('chronicles_mode')          => array( 'owc_chronicles_cache' ),
        owc_option_name('chronicles_remote_url')     => array( 'owc_chronicles_cache' ),
        owc_option_name('enable_chronicles')         => array( 'owc_chronicles_cache' ),
        owc_option_name('coordinators_mode')         => array( 'owc_coordinators_cache' ),
        owc_option_name('coordinators_remote_url')   => array( 'owc_coordinators_cache' ),
        owc_option_name('enable_coordinators')       => array( 'owc_coordinators_cache' ),
        owc_option_name('territories_mode')          => array( 'owc_territories_cache' ),
        owc_option_name('territories_remote_url')    => array( 'owc_territories_cache' ),
        owc_option_name('enable_territories')        => array( 'owc_territories_cache' ),
        owc_option_name('remote_url')                => array( 'owc_chronicles_cache', 'owc_coordinators_cache', 'owc_territories_cache' ),
    );

    if ( isset( $cache_map[ $option ] ) ) {
        foreach ( $cache_map[ $option ] as $transient_key ) {
            delete_transient( $transient_key );
        }
    }
}, 10, 3 );


function owc_render_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle cache clear.
    if (isset($_POST['owc_clear_cache']) && check_admin_referer('owc_clear_cache_action')) {
        owc_clear_all_caches();
        add_settings_error('owc_settings', 'cache_cleared', __('Cache cleared successfully.', 'owbn-client'), 'success');
    }

    // Handle cache refresh.
    if (isset($_POST['owc_refresh_cache']) && check_admin_referer('owc_refresh_cache_action')) {
        $result = owc_refresh_all_caches();
        if (is_wp_error($result)) {
            add_settings_error('owc_settings', 'cache_refresh_failed', $result->get_error_message(), 'error');
        } else {
            add_settings_error('owc_settings', 'cache_refreshed', __('Cache refreshed successfully.', 'owbn-client'), 'success');
        }
    }

    // Handle entity cache refresh.
    if (isset($_POST['owc_refresh_entity_cache']) && check_admin_referer('owc_refresh_entity_cache_action')) {
        if ( function_exists( 'owc_entity_refresh' ) ) {
            owc_entity_refresh();
            add_settings_error('owc_settings', 'entity_cache_refreshed', __('Entity resolution cache refreshed.', 'owbn-client'), 'success');
        } else {
            add_settings_error('owc_settings', 'entity_cache_unavailable', __('Entity resolution module not loaded.', 'owbn-client'), 'error');
        }
    }

    $client_id  = owc_get_client_id();
    $tabs       = owc_get_settings_tabs();
    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

    if ( ! isset( $tabs[ $active_tab ] ) ) {
        $active_tab = 'general';
    }

    $page_url = admin_url( 'admin.php?page=' . $client_id . '-owc-settings' );

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('OWBN Client Settings', 'owbn-client'); ?></h1>

        <?php settings_errors(); ?>

        <div class="owc-settings-wrap">
            <!-- Left: vertical tab nav -->
            <ul class="owc-settings-tabs">
                <?php foreach ( $tabs as $slug => $tab ) :
                    $is_enabled = ! empty( $tab['always_on'] )
                        || (bool) get_option( owc_option_name( $tab['enable_key'] ), false );
                    $classes = array( 'owc-settings-tab' );
                    if ( $slug === $active_tab ) {
                        $classes[] = 'owc-settings-tab--active';
                    }
                    if ( ! $is_enabled && empty( $tab['always_on'] ) ) {
                        $classes[] = 'owc-settings-tab--disabled';
                    }
                ?>
                <li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
                    <a href="<?php echo esc_url( add_query_arg( 'tab', $slug, $page_url ) ); ?>">
                        <span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span>
                        <?php echo esc_html( $tab['label'] ); ?>
                        <?php if ( ! $is_enabled && empty( $tab['always_on'] ) ) : ?>
                            <span class="owc-tab-badge"><?php esc_html_e( 'Off', 'owbn-client' ); ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>

            <!-- Right: tab content -->
            <div class="owc-settings-content">
                <?php
                $tab_config = $tabs[ $active_tab ];
                $is_enabled = ! empty( $tab_config['always_on'] )
                    || (bool) get_option( owc_option_name( $tab_config['enable_key'] ), false );

                if ( ! $is_enabled && empty( $tab_config['always_on'] ) ) {
                    // Show disabled message.
                    printf(
                        '<div class="owc-tab-disabled-notice"><p>%s</p><p><a href="%s" class="button">%s</a></p></div>',
                        esc_html( sprintf(
                            /* translators: %s: feature name */
                            __( '%s is currently disabled. Enable it on the General tab to configure.', 'owbn-client' ),
                            $tab_config['label']
                        ) ),
                        esc_url( add_query_arg( 'tab', 'general', $page_url ) ),
                        esc_html__( 'Go to General Settings', 'owbn-client' )
                    );
                } else {
                    $group = $tab_config['group'];
                    include $tab_config['partial'];
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}
