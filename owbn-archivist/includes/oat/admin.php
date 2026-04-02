<?php

/**
 * OAT Client Admin
 *
 * Registers admin menu items and enqueues OAT assets.
 * Menu uses 'read' capability — real authorization is server-side.
 *
 */

defined( 'ABSPATH' ) || exit;


add_action( 'admin_menu', 'owc_oat_register_menus' );

/**
 * Register OAT admin menu pages.
 *
 * @return void
 */
function owc_oat_register_menus() {
    // When OAT plugin is installed (local mode), add as submenus under its menu.
    // When running standalone (remote mode), create our own top-level menu.
    $oat_local = class_exists( 'OAT_Admin' );
    $parent    = $oat_local ? 'oat-entries' : 'owc-oat-inbox';

    if ( ! $oat_local ) {
        add_menu_page(
            'Archivist Dashboard',
            'Archivist',
            'read',
            'owc-oat-workspace',
            'owc_oat_render_workspace',
            'dashicons-clipboard',
            31
        );
    }

    add_submenu_page(
        $parent,
        'Workspace',
        'Workspace',
        'read',
        'owc-oat-workspace',
        'owc_oat_render_workspace'
    );

    // Hidden pages: standalone versions for backward-compatible links.
    add_submenu_page( null, 'Inbox', 'Inbox', 'read', 'owc-oat-inbox', 'owc_oat_render_inbox' );
    add_submenu_page( null, 'New Submission', 'New Submission', 'read', 'owc-oat-submit', 'owc_oat_render_submit' );
    add_submenu_page( null, 'Registry', 'Registry', 'read', 'owc-oat-registry', 'owc_oat_render_registry' );

    // Reports: only on local OAT host (archivist), not remote sites.
    if ( $oat_local ) {
        require_once __DIR__ . '/pages/reports.php';
        add_submenu_page(
            $parent,
            'Reports',
            'Reports',
            'read',
            'owc-oat-reports',
            'owc_oat_page_reports'
        );
    }

    // Hidden page: entry detail (no menu item, accessed via link).
    add_submenu_page(
        null,
        'Entry Detail',
        'Entry Detail',
        'read',
        'owc-oat-entry',
        'owc_oat_render_entry'
    );

    // Hidden page: registry character detail (no menu item, accessed via link).
    add_submenu_page(
        null,
        'Character Registry',
        'Character Registry',
        'read',
        'owc-oat-registry-character',
        'owc_oat_render_registry_character'
    );
}


/**
 * Render the Workspace page — Inbox, Submit, Registry as tabs.
 *
 * @return void
 */
function owc_oat_render_workspace() {
    $tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'inbox';

    $tabs = array(
        'inbox'    => 'Inbox',
        'submit'   => 'New Submission',
        'registry' => 'Registry',
    );

    ?>
    <div class="wrap">
        <h1>Archivist Dashboard</h1>
        <nav class="nav-tab-wrapper">
            <?php foreach ( $tabs as $slug => $label ) :
                $url    = admin_url( 'admin.php?page=owc-oat-workspace&tab=' . $slug );
                $active = ( $tab === $slug ) ? ' nav-tab-active' : '';
            ?>
                <a href="<?php echo esc_url( $url ); ?>" class="nav-tab<?php echo $active; ?>">
                    <?php echo esc_html( $label ); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div style="margin-top:15px;">
            <?php
            switch ( $tab ) {
                case 'inbox':
                    require_once __DIR__ . '/pages/inbox.php';
                    owc_oat_page_inbox( true );
                    break;
                case 'submit':
                    require_once __DIR__ . '/pages/submit.php';
                    owc_oat_page_submit( true );
                    break;
                case 'registry':
                    require_once __DIR__ . '/pages/registry.php';
                    owc_oat_page_registry( true );
                    break;
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Render the inbox page (standalone).
 *
 * @return void
 */
function owc_oat_render_inbox() {
    require_once __DIR__ . '/pages/inbox.php';
    owc_oat_page_inbox();
}

/**
 * Render the submit page (standalone).
 *
 * @return void
 */
function owc_oat_render_submit() {
    require_once __DIR__ . '/pages/submit.php';
    owc_oat_page_submit();
}

/**
 * Render the entry detail page.
 *
 * @return void
 */
function owc_oat_render_entry() {
    require_once __DIR__ . '/pages/entry.php';
    owc_oat_page_entry();
}

/**
 * Render the registry page.
 *
 * @return void
 */
function owc_oat_render_registry() {
    require_once __DIR__ . '/pages/registry.php';
    owc_oat_page_registry();
}

/**
 * Render the registry character detail page.
 *
 * @return void
 */
function owc_oat_render_registry_character() {
    require_once __DIR__ . '/pages/registry-character.php';
    owc_oat_page_registry_character();
}


add_action( 'admin_enqueue_scripts', 'owc_oat_enqueue_assets' );

/**
 * Enqueue OAT CSS and JS on OAT pages only.
 *
 * @param string $hook The current admin page hook suffix.
 * @return void
 */
function owc_oat_enqueue_assets( $hook ) {
    if ( strpos( $hook, 'owc-oat-' ) === false ) {
        return;
    }

    $base_url = plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'includes/oat/assets/';
    $version  = defined( 'OWC_VERSION' ) ? OWC_VERSION : '1.0.6';

    wp_enqueue_style(
        'owc-oat-client',
        $base_url . 'css/oat-client.css',
        array(),
        $version
    );

    wp_enqueue_script(
        'owc-oat-client',
        $base_url . 'js/oat-client.js',
        array( 'jquery', 'jquery-ui-autocomplete' ),
        $version,
        true
    );

    $current_user = wp_get_current_user();
    $is_super = $current_user && $current_user->ID && function_exists( 'owc_oat_is_super_user' )
        ? owc_oat_is_super_user( $current_user->ID )
        : false;
    // Build creature taxonomy URL for remote sites.
    $oat_local             = class_exists( 'OAT_Admin' );
    $creature_taxonomy_url = '';
    if ( ! $oat_local ) {
        $gw_base = owc_get_remote_base( 'oat' );
        if ( $gw_base ) {
            $creature_taxonomy_url = $gw_base . 'oat/creature-taxonomy';
        }
    }

    wp_localize_script( 'owc-oat-client', 'owc_oat_ajax', array(
        'url'                   => admin_url( 'admin-ajax.php' ),
        'nonce'                 => wp_create_nonce( 'owc_oat_nonce' ),
        'creature_nonce'        => wp_create_nonce( 'oat_creature_picker' ),
        'creatureTaxonomyUrl'   => $creature_taxonomy_url,
        'apiKey'                => $oat_local ? '' : owc_get_remote_key( 'oat' ),
        'currentUserName'       => $current_user && $current_user->ID ? $current_user->display_name : '',
        'currentUserId'         => $current_user && $current_user->ID ? $current_user->ID : 0,
        'isSuperUser'           => $is_super ? '1' : '0',
        'canCreateCharacter'    => owc_oat_can_create_character() ? '1' : '0',
        'i18n' => array(
            'processing'    => __( 'Processing...', 'owbn-client' ),
            'submit'        => __( 'Submit', 'owbn-client' ),
            'watch'         => __( 'Watch', 'owbn-client' ),
            'unwatch'       => __( 'Unwatch', 'owbn-client' ),
            'watching'      => __( 'Watching', 'owbn-client' ),
            'creating'      => __( 'Creating...', 'owbn-client' ),
            'charRequired'  => __( 'Character name is required.', 'owbn-client' ),
            'requestFailed' => __( 'Request failed.', 'owbn-client' ),
            'showTimeline'  => __( 'Show Timeline', 'owbn-client' ),
            'hideTimeline'  => __( 'Hide Timeline', 'owbn-client' ),
            'error'         => __( 'Error', 'owbn-client' ),
        ),
    ) );

    // Creature type picker (works local via AJAX or remote via gateway).
    wp_enqueue_script(
        'owc-oat-creature-picker',
        $base_url . 'js/oat-creature-picker.js',
        array( 'jquery', 'owc-oat-client' ),
        $version,
        true
    );

    // Submit page: preload editor scripts so AJAX-loaded htmlarea fields work.
    // Also load on workspace since submit tab can be active.
    if ( strpos( $hook, 'owc-oat-submit' ) !== false || strpos( $hook, 'owc-oat-workspace' ) !== false ) {
        wp_enqueue_editor();
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
        wp_enqueue_script(
            'owc-oat-regulation-picker',
            $base_url . 'js/oat-regulation-picker.js',
            array( 'jquery', 'jquery-ui-autocomplete', 'owc-oat-client' ),
            $version,
            true
        );
    }

    // Entry detail page: autocomplete for reassign/delegate user pickers.
    if ( strpos( $hook, 'owc-oat-entry' ) !== false ) {
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
    }
}
