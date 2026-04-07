<?php
/**
 * OAT Registry — shared HTML shell + JS config.
 *
 * Called by both the Elementor widget and the WP admin template.
 * Outputs the Display Options, tabs, search, content container,
 * CSS, and the oatRegistryConfig JSON that oat-registry.js reads.
 */

defined( 'ABSPATH' ) || exit;

function owc_oat_render_registry_shell( $args = array() ) {
    $detail_base = $args['detail_base'] ?? admin_url( 'admin.php?page=owc-oat-registry-character&character_id=' );
    $first_scope = $args['first_scope'] ?? 'mine';
    $show_search = $args['show_search'] ?? true;
    $embedded    = $args['embedded'] ?? false;
    $tabs        = $args['tabs'] ?? array(
        'mine'           => __( 'My Characters', 'owbn-archivist' ),
        'chronicles'     => __( 'Chronicles', 'owbn-archivist' ),
        'coordinators'   => __( 'Coordinators', 'owbn-archivist' ),
        'decommissioned' => __( 'Decommissioned', 'owbn-archivist' ),
    );

    $ajax_url = admin_url( 'admin-ajax.php' );
    $nonce    = wp_create_nonce( 'owc_oat_nonce' );

    $saved_cols = get_user_meta( get_current_user_id(), 'owc_oat_registry_columns', true );
    if ( ! is_array( $saved_cols ) || empty( $saved_cols ) ) {
        $saved_cols = array( 'character', 'chronicle', 'status' );
    }

    // Column definitions — all i18n'd.
    $columns = array(
        array( 'key' => 'character',     'dataKey' => 'name',          'label' => __( 'Character', 'owbn-archivist' ),      'mandatory' => true,  'sortType' => 'string' ),
        array( 'key' => 'player',        'dataKey' => 'player',        'label' => __( 'Player', 'owbn-archivist' ),         'mandatory' => false, 'sortType' => 'string' ),
        array( 'key' => 'chronicle',     'dataKey' => 'chronicle',     'label' => __( 'Chronicle', 'owbn-archivist' ),      'mandatory' => true,  'sortType' => 'string' ),
        array( 'key' => 'type',          'dataKey' => 'type',          'label' => __( 'Type', 'owbn-archivist' ),           'mandatory' => false, 'sortType' => 'string' ),
        array( 'key' => 'pcnpc',         'dataKey' => 'pcnpc',         'label' => __( 'PC/NPC', 'owbn-archivist' ),        'mandatory' => false, 'sortType' => 'string' ),
        array( 'key' => 'status',        'dataKey' => 'status',        'label' => __( 'Status', 'owbn-archivist' ),         'mandatory' => true,  'sortType' => 'string' ),
        array( 'key' => 'entries',       'dataKey' => 'entries',       'label' => __( 'Entries', 'owbn-archivist' ),        'mandatory' => false, 'sortType' => 'number' ),
        array( 'key' => 'my_entries',    'dataKey' => 'my_entries',    'label' => __( 'My Entries', 'owbn-archivist' ),     'mandatory' => false, 'sortType' => 'number' ),
        array( 'key' => 'last_activity', 'dataKey' => 'last_activity', 'label' => __( 'Last Activity', 'owbn-archivist' ), 'mandatory' => false, 'sortType' => 'number' ),
    );

    $i18n = array(
        'loading'    => __( 'Loading...', 'owbn-archivist' ),
        'searching'  => __( 'Searching...', 'owbn-archivist' ),
        'noSections' => __( 'No sections found.', 'owbn-archivist' ),
        'noChars'    => __( 'No characters.', 'owbn-archivist' ),
        'noResults'  => __( 'No characters found.', 'owbn-archivist' ),
        'required'   => __( 'required', 'owbn-archivist' ),
        'filter'     => __( 'Filter...', 'owbn-archivist' ),
    );

    // Enqueue the shared JS.
    $js_url = defined( 'OWC_ARCHIVIST_URL' ) ? OWC_ARCHIVIST_URL . 'includes/oat/assets/js/' : '';
    $version = defined( 'OWC_ARCHIVIST_VERSION' ) ? OWC_ARCHIVIST_VERSION : '1.0.0';
    wp_enqueue_script( 'oat-registry', $js_url . 'oat-registry.js', array(), $version, true );

    ?>
    <style>
    .oat-registry-section-body.oat-collapsed { display: none; }
    .oat-registry-section-header { user-select: none; }
    .oat-registry-section-header::before { content: '\25B6'; margin-right: 8px; font-size: 0.8em; }
    .oat-registry-section-header.oat-expanded::before { content: '\25BC'; }
    .oat-registry-tab.active, .oat-registry-tab.nav-tab-active { font-weight: bold; }
    .oat-registry-tab { display: inline-block; padding: 8px 16px; border: 1px solid #ddd; border-bottom: none; margin-right: 2px; cursor: pointer; border-radius: 4px 4px 0 0; background: #f7f7f7; text-decoration: none; color: inherit; }
    .oat-registry-loading { padding: 20px; text-align: center; color: #666; }
    .oat-registry-table th[data-sort] { cursor: pointer; user-select: none; white-space: nowrap; }
    .oat-registry-table th[data-sort]:hover { opacity: 0.7; }
    .oat-registry-table th[data-sort]::after { content: ' \2195'; font-size: 0.7em; opacity: 0.4; }
    .oat-registry-table th[data-sort].sort-asc::after { content: ' \2191'; opacity: 1; }
    .oat-registry-table th[data-sort].sort-desc::after { content: ' \2193'; opacity: 1; }
    .oat-display-opts { border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; }
    .oat-display-opts summary { padding: 8px 12px; cursor: pointer; font-size: 13px; font-weight: 600; }
    .oat-display-opts-inner { padding: 8px 12px; display: flex; flex-wrap: wrap; gap: 8px 16px; font-size: 13px; }
    .oat-col-mandatory { opacity: 0.6; }
    </style>

    <div class="oat-registry-widget">
        <details class="oat-display-opts">
            <summary><?php esc_html_e( 'Display Options', 'owbn-archivist' ); ?></summary>
            <div class="oat-display-opts-inner" id="oat-col-toggles"></div>
        </details>

        <div class="oat-registry-header">
            <?php if ( ! $embedded ) : ?>
                <h3><?php esc_html_e( 'Registry', 'owbn-archivist' ); ?></h3>
            <?php endif; ?>
            <?php if ( count( $tabs ) > 1 ) : ?>
                <nav class="oat-registry-tabs" style="margin:8px 0;border-bottom:1px solid #ddd;">
                    <?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
                        <a href="#" class="oat-registry-tab" data-scope="<?php echo esc_attr( $tab_key ); ?>"><?php echo esc_html( $tab_label ); ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
            <div style="display:flex;gap:12px;flex-wrap:wrap;margin:8px 0;align-items:center;">
                <?php if ( $show_search ) : ?>
                    <input type="text" class="oat-registry-search" placeholder="<?php esc_attr_e( 'Search characters...', 'owbn-archivist' ); ?>" style="flex:1;min-width:200px;max-width:300px;">
                <?php endif; ?>
                <button type="button" class="oat-registry-clear" style="padding:4px 12px;cursor:pointer;border:1px solid #ccc;border-radius:4px;background:#fff;"><?php esc_html_e( 'Clear', 'owbn-archivist' ); ?></button>
            </div>
        </div>
        <div class="oat-registry-content"></div>
    </div>

    <script>
    window.oatRegistryConfig = <?php echo wp_json_encode( array(
        'ajaxUrl'    => $ajax_url,
        'nonce'      => $nonce,
        'detailBase' => $detail_base,
        'firstScope' => $first_scope,
        'columns'    => $columns,
        'activeCols' => $saved_cols,
        'i18n'       => $i18n,
    ) ); ?>;
    </script>
    <?php
}
