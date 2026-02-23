<?php
/**
 * Settings Tab: Player ID
 * location: includes/admin/settings-tabs/tab-player-id.php
 *
 * Variables available from the orchestrator:
 *   $group     string  Settings group name.
 *   $page_url  string  Base settings page URL.
 *
 * @package OWBN-Client
 */

defined( 'ABSPATH' ) || exit;

$mode    = get_option( owc_option_name( 'player_id_mode' ), 'client' );
$sso_url = get_option( owc_option_name( 'player_id_sso_url' ), '' );
?>

<h2><?php esc_html_e( 'Player ID', 'owbn-client' ); ?></h2>

<form method="post" action="options.php">
    <?php settings_fields( $group ); ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'Mode', 'owbn-client' ); ?></th>
            <td>
                <fieldset>
                    <label>
                        <input type="radio"
                            name="<?php echo esc_attr( owc_option_name( 'player_id_mode' ) ); ?>"
                            class="owc-player-id-mode"
                            value="server"
                            <?php checked( $mode, 'server' ); ?> />
                        <?php esc_html_e( 'Server — This site manages Player IDs (SSO server)', 'owbn-client' ); ?>
                    </label><br>
                    <label>
                        <input type="radio"
                            name="<?php echo esc_attr( owc_option_name( 'player_id_mode' ) ); ?>"
                            class="owc-player-id-mode"
                            value="client"
                            <?php checked( $mode, 'client' ); ?> />
                        <?php esc_html_e( 'Client — Capture Player ID from SSO login', 'owbn-client' ); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        <tr class="owc-player-id-remote" <?php echo $mode === 'client' ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'SSO Server URL', 'owbn-client' ); ?></th>
            <td>
                <input type="url"
                    name="<?php echo esc_attr( owc_option_name( 'player_id_sso_url' ) ); ?>"
                    value="<?php echo esc_url( $sso_url ); ?>"
                    class="regular-text"
                    placeholder="https://sso.owbn.net" />
                <p class="description"><?php esc_html_e( 'Base URL of the SSO server. Only OAuth responses from this URL will be intercepted.', 'owbn-client' ); ?></p>
            </td>
        </tr>
    </table>
    <?php submit_button(); ?>
</form>

<hr />

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
        <?php if ( $mode === 'client' && $sso_url ) : ?>
        <tr>
            <th><?php esc_html_e( 'SSO Server', 'owbn-client' ); ?></th>
            <td><?php echo esc_html( $sso_url ); ?></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
