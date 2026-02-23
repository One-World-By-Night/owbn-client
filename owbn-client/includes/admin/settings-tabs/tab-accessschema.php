<?php
/**
 * Settings Tab: accessSchema
 * location: includes/admin/settings-tabs/tab-accessschema.php
 *
 * Variables available from the orchestrator:
 *   $group     string  Settings group name.
 *   $page_url  string  Base settings page URL.
 *
 * @package OWBN-Client
 */

defined( 'ABSPATH' ) || exit;

$mode       = get_option( owc_option_name( 'asc_mode' ), 'remote' );
$remote_url = get_option( owc_option_name( 'asc_remote_url' ), '' );
$remote_key = get_option( owc_option_name( 'asc_remote_api_key' ), '' );
$cache_ttl  = get_option( owc_option_name( 'asc_cache_ttl' ), 3600 );
?>

<h2><?php esc_html_e( 'accessSchema', 'owbn-client' ); ?></h2>
<p class="description"><?php esc_html_e( 'Centralized accessSchema client. Provides role-based access control for all OWBN plugins through a single configuration.', 'owbn-client' ); ?></p>

<form method="post" action="options.php">
    <?php settings_fields( $group ); ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'Mode', 'owbn-client' ); ?></th>
            <td>
                <fieldset>
                    <label>
                        <input type="radio"
                            name="<?php echo esc_attr( owc_option_name( 'asc_mode' ) ); ?>"
                            class="owc-asc-mode"
                            value="local"
                            <?php checked( $mode, 'local' ); ?> />
                        <?php esc_html_e( 'Local — accessSchema server plugin is installed on this site', 'owbn-client' ); ?>
                    </label><br>
                    <label>
                        <input type="radio"
                            name="<?php echo esc_attr( owc_option_name( 'asc_mode' ) ); ?>"
                            class="owc-asc-mode"
                            value="remote"
                            <?php checked( $mode, 'remote' ); ?> />
                        <?php esc_html_e( 'Remote — Connect to a remote accessSchema server', 'owbn-client' ); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        <tr class="owc-asc-remote" <?php echo $mode === 'remote' ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'Server URL', 'owbn-client' ); ?></th>
            <td>
                <input type="url"
                    name="<?php echo esc_attr( owc_option_name( 'asc_remote_url' ) ); ?>"
                    value="<?php echo esc_url( $remote_url ); ?>"
                    class="regular-text"
                    placeholder="https://council.owbn.net" />
                <p class="description"><?php esc_html_e( 'Base URL of the site running the accessSchema server plugin.', 'owbn-client' ); ?></p>
            </td>
        </tr>
        <tr class="owc-asc-remote" <?php echo $mode === 'remote' ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'API Key', 'owbn-client' ); ?></th>
            <td>
                <input type="text"
                    name="<?php echo esc_attr( owc_option_name( 'asc_remote_api_key' ) ); ?>"
                    value="<?php echo esc_attr( $remote_key ); ?>"
                    class="regular-text code" />
                <p class="description"><?php esc_html_e( 'API key for the accessSchema server.', 'owbn-client' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Role Cache TTL (seconds)', 'owbn-client' ); ?></th>
            <td>
                <input type="number"
                    name="<?php echo esc_attr( owc_option_name( 'asc_cache_ttl' ) ); ?>"
                    value="<?php echo esc_attr( $cache_ttl ); ?>"
                    class="small-text"
                    min="0" />
                <p class="description"><?php esc_html_e( 'How long to cache user roles. 0 = no caching. Default: 3600 (1 hour)', 'owbn-client' ); ?></p>
            </td>
        </tr>
    </table>
    <?php submit_button(); ?>
</form>

<hr />

<?php
$asc_roles_cache = get_transient( 'owc_asc_roles_all' );
?>
<h3><?php esc_html_e( 'Status', 'owbn-client' ); ?></h3>
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
        <?php if ( $mode === 'remote' && $remote_url ) : ?>
        <tr>
            <th><?php esc_html_e( 'Server URL', 'owbn-client' ); ?></th>
            <td><?php echo esc_html( $remote_url ); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th><?php esc_html_e( 'Roles Cache', 'owbn-client' ); ?></th>
            <td>
                <?php
                if ( is_array( $asc_roles_cache ) && isset( $asc_roles_cache['total'] ) ) {
                    echo absint( $asc_roles_cache['total'] ) . ' ' . esc_html__( 'roles cached', 'owbn-client' );
                } else {
                    esc_html_e( 'Empty — will populate on first access', 'owbn-client' );
                }
                ?>
            </td>
        </tr>
        <?php if ( function_exists( 'owc_asc_get_clients' ) ) : ?>
        <tr>
            <th><?php esc_html_e( 'Registered Clients', 'owbn-client' ); ?></th>
            <td>
                <?php
                $asc_clients = owc_asc_get_clients();
                if ( ! empty( $asc_clients ) ) :
                ?>
                <ul style="margin:0; padding:0; list-style:none;">
                    <?php foreach ( $asc_clients as $cid => $clabel ) : ?>
                    <li>
                        <span style="color:#4CAF50;">&#10003;</span>
                        <?php echo esc_html( $cid . ' — ' . $clabel ); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else : ?>
                <p style="color:#999;"><?php esc_html_e( 'No plugins have registered with the centralized ASC module yet.', 'owbn-client' ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="owc-data-search" data-action="owc_search_asc_roles">
    <h3><?php esc_html_e( 'Search Data', 'owbn-client' ); ?></h3>
    <input type="text" class="owc-data-search-input"
        placeholder="<?php esc_attr_e( 'Search roles...', 'owbn-client' ); ?>"
        data-columns='[{"key":"path","label":"Path"},{"key":"name","label":"Name"},{"key":"depth","label":"Depth"}]' />
    <div class="owc-data-search-results"></div>
</div>
