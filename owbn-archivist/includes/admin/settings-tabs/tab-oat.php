<?php
/**
 * Settings Tab: OAT (Archivist Toolkit)
 *
 * Variables available from the orchestrator:
 *   $group     string  Settings group name.
 *   $page_url  string  Base settings page URL.
 *
 */

defined( 'ABSPATH' ) || exit;

$mode       = owc_get_mode( 'oat' );
$remote_url = get_option( owc_option_name( 'oat_remote_url' ), '' );
$remote_key = get_option( owc_option_name( 'oat_remote_api_key' ), '' );
?>

<h2><?php esc_html_e( 'Archivist Toolkit (OAT)', 'owbn-archivist' ); ?></h2>
<p class="description"><?php esc_html_e( 'OAT pages: Inbox, Submit, Entry Detail.', 'owbn-archivist' ); ?></p>

<form method="post" action="options.php">
    <?php settings_fields( $group ); ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'Data Source', 'owbn-archivist' ); ?></th>
            <td>
                <fieldset>
                    <label>
                        <input type="radio"
                            name="<?php echo esc_attr( owc_option_name( 'oat_mode' ) ); ?>"
                            class="owc-oat-mode"
                            value="local"
                            <?php checked( $mode, 'local' ); ?> />
                        <?php esc_html_e( 'Local — OAT plugin is installed on this site', 'owbn-archivist' ); ?>
                    </label><br>
                    <label>
                        <input type="radio"
                            name="<?php echo esc_attr( owc_option_name( 'oat_mode' ) ); ?>"
                            class="owc-oat-mode"
                            value="remote"
                            <?php checked( $mode, 'remote' ); ?> />
                        <?php esc_html_e( 'Remote — Fetch from archivist.owbn.net gateway', 'owbn-archivist' ); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        <tr class="owc-oat-remote" <?php echo $mode === 'remote' ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'Remote URL Override', 'owbn-archivist' ); ?></th>
            <td>
                <?php $default_url = get_option( owc_option_name( 'remote_url' ), '' ); ?>
                <input type="url"
                    name="<?php echo esc_attr( owc_option_name( 'oat_remote_url' ) ); ?>"
                    value="<?php echo esc_url( $remote_url ); ?>"
                    class="regular-text"
                    placeholder="<?php echo esc_attr( $default_url ? $default_url . ' (default)' : 'No default set' ); ?>" />
                <p class="description"><?php esc_html_e( 'Only set if OAT data comes from a different gateway than the default remote URL.', 'owbn-archivist' ); ?></p>
            </td>
        </tr>
        <tr class="owc-oat-remote" <?php echo $mode === 'remote' ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'API Key Override', 'owbn-archivist' ); ?></th>
            <td>
                <?php $default_key = get_option( owc_option_name( 'remote_api_key' ), '' ); ?>
                <input type="text"
                    name="<?php echo esc_attr( owc_option_name( 'oat_remote_api_key' ) ); ?>"
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

<h3><?php esc_html_e( 'Status', 'owbn-archivist' ); ?></h3>
<?php
$oat_plugin_active = class_exists( 'OAT_Entry' );
$default_remote    = get_option( owc_option_name( 'remote_url' ), '' );
$effective_url     = $remote_url ? $remote_url : $default_remote;
?>
<table class="widefat owc-tab-status">
    <tbody>
        <tr>
            <th style="width:200px"><?php esc_html_e( 'Mode', 'owbn-archivist' ); ?></th>
            <td>
                <span class="owc-mode-badge owc-mode-badge--<?php echo esc_attr( $mode ); ?>">
                    <?php echo esc_html( ucfirst( $mode ) ); ?>
                </span>
            </td>
        </tr>
        <?php if ( $mode === 'local' ) : ?>
        <tr>
            <th><?php esc_html_e( 'OAT Plugin', 'owbn-archivist' ); ?></th>
            <td><?php echo $oat_plugin_active ? esc_html__( 'Active', 'owbn-archivist' ) : esc_html__( 'Not installed', 'owbn-archivist' ); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ( $mode === 'remote' && $effective_url ) : ?>
        <tr>
            <th><?php esc_html_e( 'Remote URL', 'owbn-archivist' ); ?></th>
            <td><?php echo esc_html( $effective_url ); ?></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
