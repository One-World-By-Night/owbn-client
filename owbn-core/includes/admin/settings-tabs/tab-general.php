<?php
/**
 * Settings Tab: General
 *
 * Gateway, Default Remote, Data Sources enable/disable, Cache, Status.
 *
 * Variables available from the orchestrator:
 *   $group     string  Settings group name.
 *   $page_url  string  Base settings page URL (no tab param).
 *
 */

defined( 'ABSPATH' ) || exit;

// Gateway (producer-side).
$gw_enabled   = get_option( 'owbn_gateway_enabled', false );
$gw_api_key   = get_option( 'owbn_gateway_api_key', '' );
$gw_auth      = get_option( 'owbn_gateway_auth_methods', array( 'api_key' ) );
$gw_whitelist = get_option( 'owbn_gateway_domain_whitelist', array() );
$gw_logging   = get_option( 'owbn_gateway_logging_enabled', false );
$gw_sso_url   = get_option( 'owbn_gateway_sso_url', '' );
$gw_sso_key   = get_option( 'owbn_gateway_sso_api_key', '' );
$gw_base_url  = rest_url( 'owbn/v1/' );

// Default remote gateway (consumer-side).
$remote_url     = get_option( owc_option_name( 'remote_url' ), '' );
$remote_api_key = get_option( owc_option_name( 'remote_api_key' ), '' );

// Cache TTL.
$cache_ttl = get_option( owc_option_name( 'cache_ttl' ), 3600 );

// Change notification email.
$notify_email = get_option( owc_option_name( 'change_notify_email' ), '' );
?>

<h2><?php esc_html_e( 'General Settings', 'owbn-client' ); ?></h2>

<form method="post" action="options.php">
    <?php settings_fields( $group ); ?>

    <!-- ── API Gateway ────────────────────────────────────────────────── -->
    <h3><?php esc_html_e( 'API Gateway', 'owbn-client' ); ?></h3>
    <p class="description"><?php esc_html_e( 'Expose local data via the unified owbn/v1/ REST namespace. Enable this on sites that serve data to other OWBN sites.', 'owbn-client' ); ?></p>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'Enable Gateway', 'owbn-client' ); ?></th>
            <td>
                <label>
                    <input type="hidden" name="owbn_gateway_enabled" value="0" />
                    <input type="checkbox"
                        name="owbn_gateway_enabled"
                        id="owbn_gateway_enabled"
                        value="1"
                        <?php checked( $gw_enabled ); ?> />
                    <?php esc_html_e( 'Enable the API Gateway', 'owbn-client' ); ?>
                </label>
            </td>
        </tr>
        <tr class="owbn-gateway-options" <?php echo $gw_enabled ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'Gateway Base URL', 'owbn-client' ); ?></th>
            <td>
                <code><?php echo esc_html( $gw_base_url ); ?></code>
                <p class="description"><?php esc_html_e( 'Read-only. This is the base URL consumers will use.', 'owbn-client' ); ?></p>
            </td>
        </tr>
        <tr class="owbn-gateway-options" <?php echo $gw_enabled ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'API Key', 'owbn-client' ); ?></th>
            <td>
                <input type="text"
                    name="owbn_gateway_api_key"
                    id="owbn_gateway_api_key"
                    value="<?php echo $gw_api_key ? esc_attr( str_repeat( '●', 12 ) . substr( $gw_api_key, -4 ) ) : ''; ?>"
                    placeholder="<?php echo esc_attr__( 'Enter API key', 'owbn-client' ); ?>"
                    class="regular-text code"
                    onfocus="if(this.value.indexOf('●')!==-1){this.value='';this.type='password';}"
                    autocomplete="new-password" />
                <button type="button" id="owbn_gateway_generate_key" class="button button-secondary" style="margin-left: 8px;">
                    <?php esc_html_e( 'Generate', 'owbn-client' ); ?>
                </button>
                <p class="description"><?php esc_html_e( 'One key for all endpoints. Share this with consumer sites.', 'owbn-client' ); ?></p>
            </td>
        </tr>
        <tr class="owbn-gateway-options" <?php echo $gw_enabled ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'Auth Methods', 'owbn-client' ); ?></th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox"
                            name="owbn_gateway_auth_methods[]"
                            value="api_key"
                            <?php checked( in_array( 'api_key', (array) $gw_auth, true ) ); ?> />
                        <?php esc_html_e( 'API Key (x-api-key header)', 'owbn-client' ); ?>
                    </label><br>
                    <label>
                        <input type="checkbox"
                            name="owbn_gateway_auth_methods[]"
                            value="app_password"
                            <?php checked( in_array( 'app_password', (array) $gw_auth, true ) ); ?> />
                        <?php esc_html_e( 'Application Password (Authorization: Basic)', 'owbn-client' ); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        <tr class="owbn-gateway-options" <?php echo $gw_enabled ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'Domain Whitelist', 'owbn-client' ); ?></th>
            <td>
                <textarea
                    name="owbn_gateway_domain_whitelist"
                    rows="4"
                    class="regular-text"
                    placeholder="council.owbn.net"><?php echo esc_textarea( implode( "\n", (array) $gw_whitelist ) ); ?></textarea>
                <p class="description"><?php esc_html_e( 'One domain per line. Leave empty to allow all origins.', 'owbn-client' ); ?></p>
            </td>
        </tr>
        <tr class="owbn-gateway-options" <?php echo $gw_enabled ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'Request Logging', 'owbn-client' ); ?></th>
            <td>
                <label>
                    <input type="hidden" name="owbn_gateway_logging_enabled" value="0" />
                    <input type="checkbox"
                        name="owbn_gateway_logging_enabled"
                        value="1"
                        <?php checked( $gw_logging ); ?> />
                    <?php esc_html_e( 'Log gateway requests to PHP error log', 'owbn-client' ); ?>
                </label>
            </td>
        </tr>
        <tr class="owbn-gateway-options" <?php echo $gw_enabled ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'SSO Server URL', 'owbn-client' ); ?></th>
            <td>
                <input type="url"
                    name="owbn_gateway_sso_url"
                    value="<?php echo esc_url( $gw_sso_url ); ?>"
                    class="regular-text"
                    placeholder="https://sso.owbn.net" />
                <p class="description"><?php esc_html_e( 'SSO server to verify users for JIT provisioning. Only needed on sites that receive OAT API requests.', 'owbn-client' ); ?></p>
            </td>
        </tr>
        <tr class="owbn-gateway-options" <?php echo $gw_enabled ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'SSO API Key', 'owbn-client' ); ?></th>
            <td>
                <input type="text"
                    name="owbn_gateway_sso_api_key"
                    value="<?php echo $gw_sso_key ? esc_attr( str_repeat( '●', 12 ) . substr( $gw_sso_key, -4 ) ) : ''; ?>"
                    placeholder="<?php echo esc_attr__( 'Enter API key', 'owbn-client' ); ?>"
                    class="regular-text code"
                    onfocus="if(this.value.indexOf('●')!==-1){this.value='';this.type='password';}"
                    autocomplete="new-password" />
                <p class="description"><?php esc_html_e( 'API key for the SSO server gateway.', 'owbn-client' ); ?></p>
            </td>
        </tr>
        <tr class="owbn-gateway-options" <?php echo $gw_enabled ? '' : 'style="display:none;"'; ?>>
            <th scope="row"><?php esc_html_e( 'Data Sources', 'owbn-client' ); ?></th>
            <td>
                <?php
                $gw_sources = apply_filters( 'owbn_gateway_data_sources', array() );
                if ( ! empty( $gw_sources ) ) :
                ?>
                <ul style="margin:0; padding:0; list-style:none;">
                    <?php foreach ( $gw_sources as $key => $source ) : ?>
                    <li>
                        <span style="color:#4CAF50;">&#10003;</span>
                        <?php echo esc_html( $source['label'] . ' — ' . $source['provider'] ); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else : ?>
                <p style="color:#999;"><?php esc_html_e( 'No data source plugins detected. Install Chronicle Manager and/or Territory Manager on this site.', 'owbn-client' ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <hr />

    <!-- ── Default Remote Gateway ─────────────────────────────────────── -->
    <h3><?php esc_html_e( 'Default Remote Gateway', 'owbn-client' ); ?></h3>
    <p class="description"><?php esc_html_e( 'Default remote gateway for data types set to "Remote" mode. Individual data types can override this with their own remote URL and key.', 'owbn-client' ); ?></p>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'Remote URL', 'owbn-client' ); ?></th>
            <td>
                <input type="url"
                    name="<?php echo esc_attr( owc_option_name( 'remote_url' ) ); ?>"
                    value="<?php echo esc_url( $remote_url ); ?>"
                    class="regular-text"
                    placeholder="https://chronicles.owbn.net" />
                <p class="description"><?php esc_html_e( 'Base URL of the default producer site. Used for any remote data type that does not specify its own URL.', 'owbn-client' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'API Key', 'owbn-client' ); ?></th>
            <td>
                <input type="text"
                    name="<?php echo esc_attr( owc_option_name( 'remote_api_key' ) ); ?>"
                    value="<?php echo $remote_api_key ? esc_attr( str_repeat( '●', 12 ) . substr( $remote_api_key, -4 ) ) : ''; ?>"
                    placeholder="<?php echo esc_attr__( 'Enter API key', 'owbn-client' ); ?>"
                    class="regular-text code"
                    onfocus="if(this.value.indexOf('●')!==-1){this.value='';this.type='password';}"
                    autocomplete="new-password" />
                <p class="description"><?php esc_html_e( 'API key for the default remote gateway.', 'owbn-client' ); ?></p>
            </td>
        </tr>
    </table>

    <hr />

    <!-- ── Data Sources Enable/Disable ────────────────────────────────── -->
    <h3><?php esc_html_e( 'Data Sources', 'owbn-client' ); ?></h3>
    <p class="description"><?php esc_html_e( 'Enable or disable data sources. Configure each source on its own tab.', 'owbn-client' ); ?></p>
    <?php
    $data_sources = array(
        array( 'label' => 'Chronicles',   'enable_key' => 'enable_chronicles',   'mode_key' => 'chronicles_mode' ),
        array( 'label' => 'Coordinators', 'enable_key' => 'enable_coordinators', 'mode_key' => 'coordinators_mode' ),
        array( 'label' => 'Territories',  'enable_key' => 'enable_territories',  'mode_key' => 'territories_mode' ),
        array( 'label' => 'Vote History', 'enable_key' => 'enable_vote_history', 'mode_key' => 'votes_mode' ),
        array( 'label' => 'Player ID',    'enable_key' => 'enable_player_id',    'mode_key' => 'player_id_mode' ),
        array( 'label' => 'OAT',          'enable_key' => 'enable_oat',          'mode_key' => 'oat_mode' ),
        array( 'label' => 'accessSchema', 'enable_key' => 'asc_enabled',         'mode_key' => 'asc_mode' ),
    );
    ?>
    <table class="widefat owc-data-sources-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Feature', 'owbn-client' ); ?></th>
                <th><?php esc_html_e( 'Enabled', 'owbn-client' ); ?></th>
                <th><?php esc_html_e( 'Mode', 'owbn-client' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $data_sources as $src ) :
                $enabled = (bool) get_option( owc_option_name( $src['enable_key'] ), false );
                $mode    = get_option( owc_option_name( $src['mode_key'] ), 'local' );
            ?>
            <tr>
                <td><strong><?php echo esc_html( $src['label'] ); ?></strong></td>
                <td>
                    <input type="hidden" name="<?php echo esc_attr( owc_option_name( $src['enable_key'] ) ); ?>" value="0" />
                    <input type="checkbox"
                        name="<?php echo esc_attr( owc_option_name( $src['enable_key'] ) ); ?>"
                        value="1"
                        <?php checked( $enabled ); ?> />
                </td>
                <td>
                    <?php if ( $enabled ) : ?>
                        <span class="owc-mode-badge owc-mode-badge--<?php echo esc_attr( $mode ); ?>">
                            <?php echo esc_html( ucfirst( $mode ) ); ?>
                        </span>
                    <?php else : ?>
                        <span style="color:#999;">&mdash;</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr />

    <!-- ── Cache Settings ─────────────────────────────────────────────── -->
    <h3><?php esc_html_e( 'Cache Settings', 'owbn-client' ); ?></h3>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'Cache TTL (seconds)', 'owbn-client' ); ?></th>
            <td>
                <input type="number"
                    name="<?php echo esc_attr( owc_option_name( 'cache_ttl' ) ); ?>"
                    value="<?php echo esc_attr( $cache_ttl ); ?>"
                    class="small-text"
                    min="0" />
                <p class="description"><?php esc_html_e( '0 = no caching. Default: 3600 (1 hour)', 'owbn-client' ); ?></p>
            </td>
        </tr>
    </table>

    <hr />

    <!-- ── Change Notifications ──────────────────────────────────────── -->
    <h3>Change Notifications</h3>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">Notification Email</th>
            <td>
                <input type="text" name="<?php echo esc_attr( owc_option_name( 'change_notify_email' ) ); ?>" value="<?php echo esc_attr( $notify_email ); ?>" class="regular-text" placeholder="web@owbn.net, admin@owbn.net" />
                <p class="description">Comma-separated list of email addresses to notify when chronicle or coordinator data changes. Leave blank to disable.</p>
            </td>
        </tr>
    </table>

    <?php submit_button(); ?>
</form>

<hr />

<!-- ── Status ─────────────────────────────────────────────────────────── -->
<h3><?php esc_html_e( 'Status', 'owbn-client' ); ?></h3>

<?php
// Plugin presence.
$manager_active = owc_manager_active();
$tm_active      = owc_territory_manager_active();

// Feature flags and modes.
$chron_enabled = (bool) get_option( owc_option_name( 'enable_chronicles' ), false );
$coord_enabled = (bool) get_option( owc_option_name( 'enable_coordinators' ), false );
$terr_enabled  = (bool) get_option( owc_option_name( 'enable_territories' ), false );
$vh_enabled    = (bool) get_option( owc_option_name( 'enable_vote_history' ), false );
$pid_enabled   = (bool) get_option( owc_option_name( 'enable_player_id' ), false );
$oat_enabled   = (bool) get_option( owc_option_name( 'enable_oat' ), false );
$asc_enabled   = (bool) get_option( owc_option_name( 'asc_enabled' ), false );

$chron_mode = owc_get_mode( 'chronicles' );
$coord_mode = owc_get_mode( 'coordinators' );
$terr_mode  = owc_get_mode( 'territories' );
$vh_mode    = owc_get_mode( 'votes' );
$pid_mode   = get_option( owc_option_name( 'player_id_mode' ), 'client' );
$oat_mode   = owc_get_mode( 'oat' );
$asc_mode   = get_option( owc_option_name( 'asc_mode' ), 'remote' );

// Cache data (read-only, no remote fetches).
$chronicles_cache   = get_transient( 'owc_chronicles_cache' );
$coordinators_cache = get_transient( 'owc_coordinators_cache' );
$territories_cache  = get_transient( 'owc_territories_cache' );
$asc_roles_cache    = get_transient( 'owc_asc_roles_all' );
$oat_rules_cache    = get_transient( 'owc_oat_rules_cache' );

$territory_cpt_count = 0;
if ( $tm_active ) {
    $territory_cpt_count = (int) wp_count_posts( 'owbn_territory' )->publish;
}

// Effective remote URLs.
$chron_remote_url = get_option( owc_option_name( 'chronicles_remote_url' ), '' );
$coord_remote_url = get_option( owc_option_name( 'coordinators_remote_url' ), '' );
$terr_remote_url  = get_option( owc_option_name( 'territories_remote_url' ), '' );
$vh_remote_url    = get_option( owc_option_name( 'votes_remote_url' ), '' );
$oat_remote_url   = get_option( owc_option_name( 'oat_remote_url' ), '' );
$asc_remote_url   = get_option( owc_option_name( 'asc_remote_url' ), '' );

$chron_effective_url = $chron_remote_url ? $chron_remote_url : $remote_url;
$coord_effective_url = $coord_remote_url ? $coord_remote_url : $remote_url;
$terr_effective_url  = $terr_remote_url ? $terr_remote_url : $remote_url;
$vh_effective_url    = $vh_remote_url ? $vh_remote_url : $remote_url;
$oat_effective_url   = $oat_remote_url ? $oat_remote_url : $remote_url;

$gw_data_sources  = apply_filters( 'owbn_gateway_data_sources', array() );
$elementor_active = did_action( 'elementor/loaded' );
?>

<table class="widefat owc-tab-status">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Feature', 'owbn-client' ); ?></th>
            <th><?php esc_html_e( 'Status', 'owbn-client' ); ?></th>
            <th><?php esc_html_e( 'Source', 'owbn-client' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <tr>
        <td><strong><?php esc_html_e( 'Chronicles', 'owbn-client' ); ?></strong></td>
        <td>
            <?php
            if ( ! $chron_enabled ) {
                esc_html_e( 'Disabled', 'owbn-client' );
            } elseif ( $chron_mode === 'local' && $manager_active ) {
                $count = is_array( $chronicles_cache ) ? count( $chronicles_cache ) : 0;
                echo absint( $count ) . ' ' . esc_html__( 'records cached', 'owbn-client' );
            } elseif ( is_array( $chronicles_cache ) ) {
                echo absint( count( $chronicles_cache ) ) . ' ' . esc_html__( 'records cached', 'owbn-client' );
            } else {
                esc_html_e( 'Enabled — no cached data', 'owbn-client' );
            }
            ?>
        </td>
        <td>
            <?php
            echo esc_html( ucfirst( $chron_mode ) );
            if ( $chron_mode === 'remote' && $chron_effective_url ) {
                echo ' — ' . esc_html( $chron_effective_url );
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e( 'Coordinators', 'owbn-client' ); ?></strong></td>
        <td>
            <?php
            if ( ! $coord_enabled ) {
                esc_html_e( 'Disabled', 'owbn-client' );
            } elseif ( is_array( $coordinators_cache ) ) {
                echo absint( count( $coordinators_cache ) ) . ' ' . esc_html__( 'records cached', 'owbn-client' );
            } else {
                esc_html_e( 'Enabled — no cached data', 'owbn-client' );
            }
            ?>
        </td>
        <td>
            <?php
            echo esc_html( ucfirst( $coord_mode ) );
            if ( $coord_mode === 'remote' && $coord_effective_url ) {
                echo ' — ' . esc_html( $coord_effective_url );
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e( 'Territories', 'owbn-client' ); ?></strong></td>
        <td>
            <?php
            if ( ! $terr_enabled ) {
                esc_html_e( 'Disabled', 'owbn-client' );
            } elseif ( $terr_mode === 'local' && $tm_active ) {
                echo absint( $territory_cpt_count ) . ' ' . esc_html__( 'published', 'owbn-client' );
            } elseif ( is_array( $territories_cache ) ) {
                echo absint( count( $territories_cache ) ) . ' ' . esc_html__( 'records cached', 'owbn-client' );
            } else {
                esc_html_e( 'Enabled — no cached data', 'owbn-client' );
            }
            ?>
        </td>
        <td>
            <?php
            echo esc_html( ucfirst( $terr_mode ) );
            if ( $terr_mode === 'remote' && $terr_effective_url ) {
                echo ' — ' . esc_html( $terr_effective_url );
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e( 'Vote History', 'owbn-client' ); ?></strong></td>
        <td colspan="2">
            <?php
            if ( ! $vh_enabled ) {
                esc_html_e( 'Disabled', 'owbn-client' );
            } else {
                echo esc_html( ucfirst( $vh_mode ) ) . ' ' . esc_html__( 'mode', 'owbn-client' );
                if ( $vh_mode === 'remote' && $vh_effective_url ) {
                    echo ' — ' . esc_html( $vh_effective_url );
                }
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e( 'Player ID', 'owbn-client' ); ?></strong></td>
        <td colspan="2">
            <?php
            if ( ! $pid_enabled ) {
                esc_html_e( 'Disabled', 'owbn-client' );
            } else {
                echo esc_html( ucfirst( $pid_mode ) ) . ' ' . esc_html__( 'mode', 'owbn-client' );
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e( 'OAT', 'owbn-client' ); ?></strong></td>
        <td colspan="2">
            <?php
            if ( ! $oat_enabled ) {
                esc_html_e( 'Disabled', 'owbn-client' );
            } else {
                echo esc_html( ucfirst( $oat_mode ) ) . ' ' . esc_html__( 'mode', 'owbn-client' );
                if ( $oat_mode === 'remote' && $oat_effective_url ) {
                    echo ' — ' . esc_html( $oat_effective_url );
                }
            }
            ?>
        </td>
    </tr>
    <?php if ( $oat_enabled && $oat_mode === 'remote' ) : ?>
    <tr>
        <td><strong><?php esc_html_e( 'OAT Rules Cache', 'owbn-client' ); ?></strong></td>
        <td colspan="2">
            <?php
            if ( is_array( $oat_rules_cache ) ) {
                printf( esc_html__( '%d rules cached', 'owbn-client' ), count( $oat_rules_cache ) );
            } else {
                esc_html_e( 'Not cached — will fetch on first use', 'owbn-client' );
            }
            ?>
        </td>
    </tr>
    <?php endif; ?>
    <tr>
        <td><strong><?php esc_html_e( 'accessSchema', 'owbn-client' ); ?></strong></td>
        <td>
            <?php
            if ( ! $asc_enabled ) {
                esc_html_e( 'Disabled', 'owbn-client' );
            } elseif ( is_array( $asc_roles_cache ) && isset( $asc_roles_cache['total'] ) ) {
                echo absint( $asc_roles_cache['total'] ) . ' ' . esc_html__( 'roles cached', 'owbn-client' );
            } else {
                esc_html_e( 'Enabled — no cached data', 'owbn-client' );
            }
            ?>
        </td>
        <td>
            <?php
            if ( $asc_enabled ) {
                echo esc_html( ucfirst( $asc_mode ) );
                if ( $asc_mode === 'remote' && $asc_remote_url ) {
                    echo ' — ' . esc_html( $asc_remote_url );
                }
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e( 'Default Remote', 'owbn-client' ); ?></strong></td>
        <td colspan="2">
            <?php
            if ( $remote_url ) {
                echo esc_html( $remote_url );
            } else {
                esc_html_e( 'Not configured', 'owbn-client' );
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e( 'Gateway (this site)', 'owbn-client' ); ?></strong></td>
        <td colspan="2">
            <?php
            if ( $gw_enabled ) {
                echo esc_html__( 'Active', 'owbn-client' ) . ' — ' . esc_html( $gw_base_url );
            } else {
                esc_html_e( 'Disabled', 'owbn-client' );
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e( 'C&C Manager', 'owbn-client' ); ?></strong></td>
        <td colspan="2">
            <?php echo $manager_active ? esc_html__( 'Active', 'owbn-client' ) : esc_html__( 'Not installed', 'owbn-client' ); ?>
        </td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e( 'Territory Manager', 'owbn-client' ); ?></strong></td>
        <td colspan="2">
            <?php echo $tm_active ? esc_html__( 'Active', 'owbn-client' ) : esc_html__( 'Not installed', 'owbn-client' ); ?>
        </td>
    </tr>
    <?php if ( ! empty( $gw_data_sources ) ) : ?>
    <tr>
        <td><strong><?php esc_html_e( 'Gateway Sources', 'owbn-client' ); ?></strong></td>
        <td colspan="2">
            <?php
            $labels = array();
            foreach ( $gw_data_sources as $source ) {
                $labels[] = esc_html( $source['label'] . ' (' . $source['provider'] . ')' );
            }
            echo implode( ', ', $labels );
            ?>
        </td>
    </tr>
    <?php endif; ?>
    <tr>
        <td><strong><?php esc_html_e( 'Elementor', 'owbn-client' ); ?></strong></td>
        <td colspan="2">
            <?php
            if ( $elementor_active ) {
                esc_html_e( 'Active — widgets available', 'owbn-client' );
            } else {
                esc_html_e( 'Not active — using shortcodes only', 'owbn-client' );
            }
            ?>
        </td>
    </tr>
    </tbody>
</table>

<hr />

<!-- ── Cache Management ───────────────────────────────────────────────── -->
<h3><?php esc_html_e( 'Cache Management', 'owbn-client' ); ?></h3>
<p class="description"><?php esc_html_e( 'Clear cached data to fetch fresh content from data sources.', 'owbn-client' ); ?></p>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e( 'Clear Cache', 'owbn-client' ); ?></th>
        <td>
            <form method="post" style="display:inline;">
                <?php wp_nonce_field( 'owc_clear_cache_action' ); ?>
                <?php submit_button( __( 'Clear All Cache', 'owbn-client' ), 'secondary', 'owc_clear_cache', false ); ?>
            </form>
            <p class="description"><?php esc_html_e( 'Removes all cached data. Next page load will fetch fresh data.', 'owbn-client' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( 'Refresh Cache', 'owbn-client' ); ?></th>
        <td>
            <form method="post" style="display:inline;">
                <?php wp_nonce_field( 'owc_refresh_cache_action' ); ?>
                <?php submit_button( __( 'Refresh All Cache', 'owbn-client' ), 'secondary', 'owc_refresh_cache', false ); ?>
            </form>
            <p class="description"><?php esc_html_e( 'Clears and immediately re-fetches all data from sources.', 'owbn-client' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( 'Entity Cache', 'owbn-client' ); ?></th>
        <td>
            <form method="post" style="display:inline;">
                <?php wp_nonce_field( 'owc_refresh_entity_cache_action' ); ?>
                <?php submit_button( __( 'Refresh Entity Cache', 'owbn-client' ), 'secondary', 'owc_refresh_entity_cache', false ); ?>
            </form>
            <p class="description"><?php esc_html_e( 'Rebuilds the slug↔title lookup index for chronicles and coordinators.', 'owbn-client' ); ?></p>
        </td>
    </tr>
</table>

