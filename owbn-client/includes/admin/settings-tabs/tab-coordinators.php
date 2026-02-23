<?php
/**
 * Settings Tab: Coordinators
 * location: includes/admin/settings-tabs/tab-coordinators.php
 *
 * Variables available from the orchestrator:
 *   $group     string  Settings group name.
 *   $page_url  string  Base settings page URL.
 *
 * @package OWBN-Client
 */

defined( 'ABSPATH' ) || exit;

$mode        = owc_get_mode( 'coordinators' );
$remote_url  = get_option( owc_option_name( 'coordinators_remote_url' ), '' );
$remote_key  = get_option( owc_option_name( 'coordinators_remote_api_key' ), '' );
$list_page   = get_option( owc_option_name( 'coordinators_list_page' ), 0 );
$detail_page = get_option( owc_option_name( 'coordinators_detail_page' ), 0 );
?>

<h2><?php esc_html_e( 'Coordinators', 'owbn-client' ); ?></h2>

<form method="post" action="options.php">
    <?php settings_fields( $group ); ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'Data Source', 'owbn-client' ); ?></th>
            <td>
                <fieldset>
                    <label>
                        <input type="radio"
                            name="<?php echo esc_attr( owc_option_name( 'coordinators_mode' ) ); ?>"
                            class="owc-coordinators-mode"
                            value="local"
                            <?php checked( $mode, 'local' ); ?> />
                        <?php esc_html_e( 'Local — Serve from Chronicle Manager CPTs on this site', 'owbn-client' ); ?>
                    </label><br>
                    <label>
                        <input type="radio"
                            name="<?php echo esc_attr( owc_option_name( 'coordinators_mode' ) ); ?>"
                            class="owc-coordinators-mode"
                            value="remote"
                            <?php checked( $mode, 'remote' ); ?> />
                        <?php esc_html_e( 'Remote — Fetch from a remote gateway', 'owbn-client' ); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        <tr class="owc-coordinators-remote" <?php echo $mode === 'remote' ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'Remote URL Override', 'owbn-client' ); ?></th>
            <td>
                <input type="url"
                    name="<?php echo esc_attr( owc_option_name( 'coordinators_remote_url' ) ); ?>"
                    value="<?php echo esc_url( $remote_url ); ?>"
                    class="regular-text"
                    placeholder="<?php esc_attr_e( 'Leave empty to use default remote', 'owbn-client' ); ?>" />
                <p class="description"><?php esc_html_e( 'Only set if coordinators come from a different gateway than the default.', 'owbn-client' ); ?></p>
            </td>
        </tr>
        <tr class="owc-coordinators-remote" <?php echo $mode === 'remote' ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'API Key Override', 'owbn-client' ); ?></th>
            <td>
                <input type="text"
                    name="<?php echo esc_attr( owc_option_name( 'coordinators_remote_api_key' ) ); ?>"
                    value="<?php echo esc_attr( $remote_key ); ?>"
                    class="regular-text code"
                    placeholder="<?php esc_attr_e( 'Leave empty to use default key', 'owbn-client' ); ?>" />
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Coordinators List Page', 'owbn-client' ); ?></th>
            <td>
                <?php wp_dropdown_pages( array(
                    'name'              => owc_option_name( 'coordinators_list_page' ),
                    'selected'          => $list_page,
                    'show_option_none'  => __( '— Select Page —', 'owbn-client' ),
                    'option_none_value' => 0,
                ) ); ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Coordinators Detail Page', 'owbn-client' ); ?></th>
            <td>
                <?php wp_dropdown_pages( array(
                    'name'              => owc_option_name( 'coordinators_detail_page' ),
                    'selected'          => $detail_page,
                    'show_option_none'  => __( '— Select Page —', 'owbn-client' ),
                    'option_none_value' => 0,
                ) ); ?>
            </td>
        </tr>
    </table>
    <?php submit_button(); ?>
</form>

<hr />

<h3><?php esc_html_e( 'Status', 'owbn-client' ); ?></h3>
<?php
$manager_active     = owc_manager_active();
$coordinators_cache = get_transient( 'owc_coordinators_cache' );
$default_remote     = get_option( owc_option_name( 'remote_url' ), '' );
$effective_url      = $remote_url ? $remote_url : $default_remote;
?>
<table class="widefat owc-tab-status">
    <tbody>
        <tr>
            <th style="width:200px"><?php esc_html_e( 'Mode', 'owbn-client' ); ?></th>
            <td>
                <span class="owc-mode-badge owc-mode-badge--<?php echo esc_attr( $mode ); ?>">
                    <?php echo esc_html( ucfirst( $mode ) ); ?>
                </span>
            </td>
        </tr>
        <?php if ( $mode === 'local' ) : ?>
        <tr>
            <th><?php esc_html_e( 'C&C Manager', 'owbn-client' ); ?></th>
            <td><?php echo $manager_active ? esc_html__( 'Active', 'owbn-client' ) : esc_html__( 'Not installed', 'owbn-client' ); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ( $mode === 'remote' && $effective_url ) : ?>
        <tr>
            <th><?php esc_html_e( 'Remote URL', 'owbn-client' ); ?></th>
            <td><?php echo esc_html( $effective_url ); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th><?php esc_html_e( 'Cached Records', 'owbn-client' ); ?></th>
            <td><?php echo is_array( $coordinators_cache ) ? absint( count( $coordinators_cache ) ) : esc_html__( 'None', 'owbn-client' ); ?></td>
        </tr>
    </tbody>
</table>

<div class="owc-data-search" data-action="owc_search_coordinators">
    <h3><?php esc_html_e( 'Search Data', 'owbn-client' ); ?></h3>
    <input type="text" class="owc-data-search-input"
        placeholder="<?php esc_attr_e( 'Search coordinators...', 'owbn-client' ); ?>"
        data-columns='[{"key":"title","label":"Title"},{"key":"slug","label":"Slug"},{"key":"source","label":"Source"}]' />
    <div class="owc-data-search-results"></div>
</div>
