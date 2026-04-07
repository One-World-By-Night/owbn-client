<?php
/**
 * OWBN Core — Outbound Webhook Dispatcher
 *
 * Provides a simple mechanism for firing webhook events. The primary mechanism
 * is WordPress action hooks (do_action) so that other plugins can listen.
 * An optional HTTP POST to registered URLs is available for future use.
 *
 * @package OWBNCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fire a webhook event.
 *
 * Triggers a WordPress action hook `owc_webhook_{$event}` and optionally
 * POSTs JSON to any registered external webhook URLs for that event.
 *
 * @param string $event Event name (e.g. 'chronicle_updated').
 * @param array  $data  Payload data to pass to listeners.
 */
function owc_fire_webhook( $event, $data = array() ) {
    /**
     * Primary mechanism — WordPress action hook.
     *
     * @param array  $data  Event payload.
     * @param string $event Event name.
     */
    do_action( 'owc_webhook_' . $event, $data, $event );

    // Optional: POST to registered external URLs.
    $urls = owc_get_webhook_urls( $event );
    if ( empty( $urls ) ) {
        return;
    }

    $payload = wp_json_encode( array(
        'event'     => $event,
        'data'      => $data,
        'timestamp' => time(),
        'site'      => home_url(),
    ) );

    foreach ( $urls as $url ) {
        wp_remote_post( $url, array(
            'timeout'  => 5,
            'blocking' => false,
            'headers'  => array(
                'Content-Type' => 'application/json',
            ),
            'body'     => $payload,
        ) );
    }
}

/**
 * Register an external webhook URL for a specific event.
 *
 * @param string $event Event name.
 * @param string $url   URL to POST to when the event fires.
 */
function owc_register_webhook_url( $event, $url ) {
    $url = esc_url_raw( $url );
    if ( empty( $url ) ) {
        return;
    }

    $option_name = 'owc_webhook_urls';
    $all_urls    = get_option( $option_name, array() );
    if ( ! is_array( $all_urls ) ) {
        $all_urls = array();
    }

    if ( ! isset( $all_urls[ $event ] ) || ! is_array( $all_urls[ $event ] ) ) {
        $all_urls[ $event ] = array();
    }

    if ( ! in_array( $url, $all_urls[ $event ], true ) ) {
        $all_urls[ $event ][] = $url;
        update_option( $option_name, $all_urls );
    }
}

/**
 * Get registered webhook URLs for an event.
 *
 * @param string $event Event name.
 * @return array List of URLs.
 */
function owc_get_webhook_urls( $event ) {
    $all_urls = get_option( 'owc_webhook_urls', array() );
    if ( ! is_array( $all_urls ) || ! isset( $all_urls[ $event ] ) ) {
        return array();
    }

    return (array) $all_urls[ $event ];
}
