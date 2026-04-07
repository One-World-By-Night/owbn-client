<?php
/**
 * Settings Tab: Coordinators
 *
 * Variables available from the orchestrator:
 *   $group     string  Settings group name.
 *   $page_url  string  Base settings page URL.
 *
 */

defined( 'ABSPATH' ) || exit;

$mode        = owc_get_mode( 'coordinators' );
$remote_url  = get_option( owc_option_name( 'coordinators_remote_url' ), '' );
$remote_key  = get_option( owc_option_name( 'coordinators_remote_api_key' ), '' );
$list_page   = get_option( owc_option_name( 'coordinators_list_page' ), 0 );
$detail_page = get_option( owc_option_name( 'coordinators_detail_page' ), 0 );
?>

<h2><?php esc_html_e( 'Coordinators', 'owbn-entities' ); ?></h2>

<form method="post" action="options.php">
    <?php settings_fields( $group ); ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'Data Source', 'owbn-entities' ); ?></th>
            <td>
                <fieldset>
                    <label>
                        <input type="radio"
                            name="<?php echo esc_attr( owc_option_name( 'coordinators_mode' ) ); ?>"
                            class="owc-coordinators-mode"
                            value="local"
                            <?php checked( $mode, 'local' ); ?> />
                        <?php esc_html_e( 'Local — Serve from Chronicle Manager CPTs on this site', 'owbn-entities' ); ?>
                    </label><br>
                    <label>
                        <input type="radio"
                            name="<?php echo esc_attr( owc_option_name( 'coordinators_mode' ) ); ?>"
                            class="owc-coordinators-mode"
                            value="remote"
                            <?php checked( $mode, 'remote' ); ?> />
                        <?php esc_html_e( 'Remote — Fetch from a remote gateway', 'owbn-entities' ); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        <tr class="owc-coordinators-remote" <?php echo $mode === 'remote' ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'Remote URL Override', 'owbn-entities' ); ?></th>
            <td>
                <?php $default_url = get_option( owc_option_name( 'remote_url' ), '' ); ?>
                <input type="url"
                    name="<?php echo esc_attr( owc_option_name( 'coordinators_remote_url' ) ); ?>"
                    value="<?php echo esc_url( $remote_url ); ?>"
                    class="regular-text"
                    placeholder="<?php echo esc_attr( $default_url ? $default_url . ' (default)' : 'No default set' ); ?>" />
                <p class="description"><?php esc_html_e( 'Only set if coordinators come from a different gateway than the default.', 'owbn-entities' ); ?></p>
            </td>
        </tr>
        <tr class="owc-coordinators-remote" <?php echo $mode === 'remote' ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'API Key Override', 'owbn-entities' ); ?></th>
            <td>
                <?php $default_key = get_option( owc_option_name( 'remote_api_key' ), '' ); ?>
                <input type="text"
                    name="<?php echo esc_attr( owc_option_name( 'coordinators_remote_api_key' ) ); ?>"
                    value="<?php echo $remote_key ? esc_attr( str_repeat( '●', 12 ) . substr( $remote_key, -4 ) ) : ''; ?>"
                    placeholder="<?php echo esc_attr( $default_key ? str_repeat( '●', 12 ) . substr( $default_key, -4 ) . ' (default)' : 'Enter API key' ); ?>"
                    class="regular-text code"
                    onfocus="if(this.value.indexOf('●')!==-1){this.value='';this.type='password';}"
                    autocomplete="new-password" />
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Coordinators List Page', 'owbn-entities' ); ?></th>
            <td>
                <?php wp_dropdown_pages( array(
                    'name'              => owc_option_name( 'coordinators_list_page' ),
                    'selected'          => $list_page,
                    'show_option_none'  => __( '— Select Page —', 'owbn-entities' ),
                    'option_none_value' => 0,
                ) ); ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Coordinators Detail Page', 'owbn-entities' ); ?></th>
            <td>
                <?php wp_dropdown_pages( array(
                    'name'              => owc_option_name( 'coordinators_detail_page' ),
                    'selected'          => $detail_page,
                    'show_option_none'  => __( '— Select Page —', 'owbn-entities' ),
                    'option_none_value' => 0,
                ) ); ?>
            </td>
        </tr>
    </table>
    <?php submit_button(); ?>
</form>

<hr />

<h3><?php esc_html_e( 'Status', 'owbn-entities' ); ?></h3>
<?php
$manager_active     = owc_manager_active();
$coordinators_cache = get_transient( 'owc_coordinators_cache' );
$default_remote     = get_option( owc_option_name( 'remote_url' ), '' );
$effective_url      = $remote_url ? $remote_url : $default_remote;
?>
<table class="widefat owc-tab-status">
    <tbody>
        <tr>
            <th style="width:200px"><?php esc_html_e( 'Mode', 'owbn-entities' ); ?></th>
            <td>
                <span class="owc-mode-badge owc-mode-badge--<?php echo esc_attr( $mode ); ?>">
                    <?php echo esc_html( ucfirst( $mode ) ); ?>
                </span>
            </td>
        </tr>
        <?php if ( $mode === 'local' ) : ?>
        <tr>
            <th><?php esc_html_e( 'C&C Manager', 'owbn-entities' ); ?></th>
            <td><?php echo $manager_active ? esc_html__( 'Active', 'owbn-entities' ) : esc_html__( 'Not installed', 'owbn-entities' ); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ( $mode === 'remote' && $effective_url ) : ?>
        <tr>
            <th><?php esc_html_e( 'Remote URL', 'owbn-entities' ); ?></th>
            <td><?php echo esc_html( $effective_url ); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th><?php esc_html_e( 'Cached Records', 'owbn-entities' ); ?></th>
            <td><?php echo is_array( $coordinators_cache ) ? absint( count( $coordinators_cache ) ) : esc_html__( 'None', 'owbn-entities' ); ?></td>
        </tr>
    </tbody>
</table>

<div class="owc-data-search" data-action="owc_search_coordinators">
    <h3><?php esc_html_e( 'Search Data', 'owbn-entities' ); ?></h3>
    <input type="text" class="owc-data-search-input"
        placeholder="<?php esc_attr_e( 'Search coordinators...', 'owbn-entities' ); ?>"
        data-columns='[{"key":"title","label":"Title"},{"key":"slug","label":"Slug"},{"key":"source","label":"Source"}]' />
    <div class="owc-data-search-results"></div>
</div>
