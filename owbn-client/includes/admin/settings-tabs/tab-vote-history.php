<?php
/**
 * Settings Tab: Vote History
 *
 * Variables available from the orchestrator:
 *   $group     string  Settings group name.
 *   $page_url  string  Base settings page URL.
 *
 */

defined( 'ABSPATH' ) || exit;

$mode       = owc_get_mode( 'votes' );
$remote_url = get_option( owc_option_name( 'votes_remote_url' ), '' );
$remote_key = get_option( owc_option_name( 'votes_remote_api_key' ), '' );
?>

<h2><?php esc_html_e( 'Vote History', 'owbn-client' ); ?></h2>
<p class="description"><?php esc_html_e( 'Shows vote history on chronicle and coordinator detail pages.', 'owbn-client' ); ?></p>

<form method="post" action="options.php">
    <?php settings_fields( $group ); ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'Data Source', 'owbn-client' ); ?></th>
            <td>
                <fieldset>
                    <label>
                        <input type="radio"
                            name="<?php echo esc_attr( owc_option_name( 'votes_mode' ) ); ?>"
                            class="owc-votes-mode"
                            value="local"
                            <?php checked( $mode, 'local' ); ?> />
                        <?php esc_html_e( 'Local — Query wp-voting-plugin tables on this site', 'owbn-client' ); ?>
                    </label><br>
                    <label>
                        <input type="radio"
                            name="<?php echo esc_attr( owc_option_name( 'votes_mode' ) ); ?>"
                            class="owc-votes-mode"
                            value="remote"
                            <?php checked( $mode, 'remote' ); ?> />
                        <?php esc_html_e( 'Remote — Fetch from a remote gateway', 'owbn-client' ); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        <tr class="owc-votes-remote" <?php echo $mode === 'remote' ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'Remote URL Override', 'owbn-client' ); ?></th>
            <td>
                <input type="url"
                    name="<?php echo esc_attr( owc_option_name( 'votes_remote_url' ) ); ?>"
                    value="<?php echo esc_url( $remote_url ); ?>"
                    class="regular-text"
                    placeholder="<?php esc_attr_e( 'Leave empty to use default remote', 'owbn-client' ); ?>" />
                <p class="description"><?php esc_html_e( 'Only set if vote data comes from a different gateway than the default remote URL.', 'owbn-client' ); ?></p>
            </td>
        </tr>
        <tr class="owc-votes-remote" <?php echo $mode === 'remote' ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'API Key Override', 'owbn-client' ); ?></th>
            <td>
                <input type="text"
                    name="<?php echo esc_attr( owc_option_name( 'votes_remote_api_key' ) ); ?>"
                    value="<?php echo esc_attr( $remote_key ); ?>"
                    class="regular-text code"
                    placeholder="<?php esc_attr_e( 'Leave empty to use default key', 'owbn-client' ); ?>" />
            </td>
        </tr>
    </table>
    <?php submit_button(); ?>
</form>

<hr />

<h3><?php esc_html_e( 'Status', 'owbn-client' ); ?></h3>
<?php
$default_remote = get_option( owc_option_name( 'remote_url' ), '' );
$effective_url  = $remote_url ? $remote_url : $default_remote;
$vplugin_active = function_exists( 'owbn_gateway_query_entity_votes' ) && defined( 'WPVP_VERSION' );
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
            <th><?php esc_html_e( 'Voting Plugin', 'owbn-client' ); ?></th>
            <td><?php echo $vplugin_active ? esc_html__( 'Active', 'owbn-client' ) : esc_html__( 'Not installed', 'owbn-client' ); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ( $mode === 'remote' && $effective_url ) : ?>
        <tr>
            <th><?php esc_html_e( 'Remote URL', 'owbn-client' ); ?></th>
            <td><?php echo esc_html( $effective_url ); ?></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
