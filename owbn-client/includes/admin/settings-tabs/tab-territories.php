<?php
/**
 * Settings Tab: Territories
 *
 * Variables available from the orchestrator:
 *   $group     string  Settings group name.
 *   $page_url  string  Base settings page URL.
 *
 */

defined( 'ABSPATH' ) || exit;

$mode       = owc_get_mode( 'territories' );
$remote_url = get_option( owc_option_name( 'territories_remote_url' ), '' );
$remote_key = get_option( owc_option_name( 'territories_remote_api_key' ), '' );
?>

<h2><?php esc_html_e( 'Territories', 'owbn-client' ); ?></h2>

<form method="post" action="options.php">
    <?php settings_fields( $group ); ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'Data Source', 'owbn-client' ); ?></th>
            <td>
                <fieldset>
                    <label>
                        <input type="radio"
                            name="<?php echo esc_attr( owc_option_name( 'territories_mode' ) ); ?>"
                            class="owc-territories-mode"
                            value="local"
                            <?php checked( $mode, 'local' ); ?> />
                        <?php esc_html_e( 'Local — Serve from Territory Manager CPTs on this site', 'owbn-client' ); ?>
                    </label><br>
                    <label>
                        <input type="radio"
                            name="<?php echo esc_attr( owc_option_name( 'territories_mode' ) ); ?>"
                            class="owc-territories-mode"
                            value="remote"
                            <?php checked( $mode, 'remote' ); ?> />
                        <?php esc_html_e( 'Remote — Fetch from a remote gateway', 'owbn-client' ); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        <tr class="owc-territories-remote" <?php echo $mode === 'remote' ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'Remote URL Override', 'owbn-client' ); ?></th>
            <td>
                <?php $default_url = get_option( owc_option_name( 'remote_url' ), '' ); ?>
                <input type="url"
                    name="<?php echo esc_attr( owc_option_name( 'territories_remote_url' ) ); ?>"
                    value="<?php echo esc_url( $remote_url ); ?>"
                    class="regular-text"
                    placeholder="<?php echo esc_attr( $default_url ? $default_url . ' (default)' : 'No default set' ); ?>" />
                <p class="description"><?php esc_html_e( 'Only set if territories come from a different gateway than the default.', 'owbn-client' ); ?></p>
            </td>
        </tr>
        <tr class="owc-territories-remote" <?php echo $mode === 'remote' ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'API Key Override', 'owbn-client' ); ?></th>
            <td>
                <?php $default_key = get_option( owc_option_name( 'remote_api_key' ), '' ); ?>
                <input type="text"
                    name="<?php echo esc_attr( owc_option_name( 'territories_remote_api_key' ) ); ?>"
                    value="<?php echo $remote_key ? esc_attr( str_repeat( '●', 12 ) . substr( $remote_key, -4 ) ) : ''; ?>"
                    placeholder="<?php echo esc_attr( $default_key ? str_repeat( '●', 12 ) . substr( $default_key, -4 ) . ' (default)' : 'Enter API key' ); ?>"
                    class="regular-text code"
                    onfocus="if(this.value.indexOf('●')!==-1){this.value='';this.type='password';}"
                    autocomplete="new-password" />
            </td>
        </tr>
    </table>
    <?php submit_button(); ?>
</form>

<hr />

<h3><?php esc_html_e( 'Status', 'owbn-client' ); ?></h3>
<?php
$tm_active         = owc_territory_manager_active();
$territories_cache = get_transient( 'owc_territories_cache' );
$default_remote    = get_option( owc_option_name( 'remote_url' ), '' );
$effective_url     = $remote_url ? $remote_url : $default_remote;

$territory_cpt_count = 0;
if ( $tm_active ) {
    $territory_cpt_count = (int) wp_count_posts( 'owbn_territory' )->publish;
}
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
            <th><?php esc_html_e( 'Territory Manager', 'owbn-client' ); ?></th>
            <td><?php echo $tm_active ? esc_html__( 'Active', 'owbn-client' ) : esc_html__( 'Not installed', 'owbn-client' ); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Published Territories', 'owbn-client' ); ?></th>
            <td><?php echo absint( $territory_cpt_count ); ?></td>
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
            <td><?php echo is_array( $territories_cache ) ? absint( count( $territories_cache ) ) : esc_html__( 'None', 'owbn-client' ); ?></td>
        </tr>
    </tbody>
</table>

<div class="owc-data-search" data-action="owc_search_territories">
    <h3><?php esc_html_e( 'Search Data', 'owbn-client' ); ?></h3>
    <input type="text" class="owc-data-search-input"
        placeholder="<?php esc_attr_e( 'Search territories...', 'owbn-client' ); ?>"
        data-columns='[{"key":"title","label":"Title"},{"key":"owner","label":"Owner"},{"key":"location","label":"Location"},{"key":"region","label":"Region"}]' />
    <div class="owc-data-search-results"></div>
</div>
