<?php

/**
 * OWBN-CC-Client Template Loader
 * 
 * @package OWBN-CC-Client
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

add_action('template_redirect', 'ccc_template_redirect');

function ccc_template_redirect()
{
    $route  = get_query_var('ccc_route');
    $action = get_query_var('ccc_action');
    $slug   = get_query_var('ccc_slug');

    if (empty($route)) {
        return;
    }

    if (!in_array($route, ['chronicles', 'coordinators'], true)) {
        return;
    }

    $option = $route === 'chronicles' ? 'enable_chronicles' : 'enable_coordinators';
    if (!get_option(ccc_option_name($option), false)) {
        return;
    }

    // Fetch and render
    if ($action === 'list') {
        $data = ccc_fetch_list($route);
        ccc_render_page($route, 'list', $data);
    } elseif ($action === 'detail' && !empty($slug)) {
        $data = ccc_fetch_detail($route, sanitize_title($slug));
        ccc_render_page($route, 'detail', $data, $slug);
    }

    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// RENDER PAGE
// ══════════════════════════════════════════════════════════════════════════════

function ccc_render_page(string $route, string $action, $data, string $slug = '')
{
    // Check for errors
    if (isset($data['error'])) {
        wp_die(esc_html($data['error']), esc_html__('Error', 'owbn-cc-client'), ['response' => 500]);
    }

    // Page title
    if ($action === 'list') {
        $title = $route === 'chronicles'
            ? __('Chronicles', 'owbn-cc-client')
            : __('Coordinators', 'owbn-cc-client');
    } else {
        $title = $data['title'] ?? ucfirst($slug);
    }

    // Render content
    if ($action === 'list') {
        $content = $route === 'chronicles'
            ? ccc_render_chronicles_list($data)
            : ccc_render_coordinators_list($data);
    } else {
        // Detail - JSON for now, will add render functions later
        $content = '<pre>' . esc_html(wp_json_encode($data, JSON_PRETTY_PRINT)) . '</pre>';
    }

    // Output with theme wrapper
    ccc_output_page($title, $content);
}

function ccc_output_page(string $title, string $content)
{
    // Minimal HTML wrapper - uses theme styles
?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>

    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo esc_html($title . ' - ' . get_bloginfo('name')); ?></title>
        <?php wp_head(); ?>
        <style>
            .ccc-chronicles-list,
            .ccc-coordinators-list {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }

            .ccc-list-header {
                display: none;
            }

            .ccc-list-row {
                display: grid;
                gap: 10px;
                padding: 15px;
                border-bottom: 1px solid #ddd;
            }

            .ccc-list-row:hover {
                background: #f9f9f9;
            }

            .ccc-col-title a,
            .ccc-col-office a {
                font-weight: 600;
                text-decoration: none;
            }

            .ccc-col-title a:hover,
            .ccc-col-office a:hover {
                text-decoration: underline;
            }

            [data-label]:before {
                content: attr(data-label) ": ";
                font-weight: 600;
            }

            /* Desktop: grid layout */
            @media (min-width: 768px) {
                .ccc-list-header {
                    display: grid;
                    gap: 10px;
                    padding: 10px 15px;
                    background: #f5f5f5;
                    font-weight: 600;
                    border-bottom: 2px solid #ddd;
                }

                .ccc-chronicles-list .ccc-list-header,
                .ccc-chronicles-list .ccc-list-row {
                    grid-template-columns: 2fr 1.5fr 1fr 1fr 1fr 1fr 1fr;
                }

                .ccc-coordinators-list .ccc-list-header,
                .ccc-coordinators-list .ccc-list-row {
                    grid-template-columns: 2fr 1.5fr 2fr;
                }

                [data-label]:before {
                    display: none;
                }
            }
        </style>
    </head>

    <body <?php body_class(); ?>>
        <?php wp_body_open(); ?>

        <div class="ccc-page-wrapper">
            <main class="ccc-main">
                <h1><?php echo esc_html($title); ?></h1>
                <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                ?>
            </main>
        </div>

        <?php wp_footer(); ?>
    </body>

    </html>
<?php
}

// ══════════════════════════════════════════════════════════════════════════════
// DATA FETCH
// ══════════════════════════════════════════════════════════════════════════════

function ccc_fetch_list(string $route)
{
    $mode = get_option(ccc_option_name($route . '_mode'), 'local');

    if ($mode === 'local') {
        return ccc_fetch_local_list($route);
    }
    return ccc_fetch_remote_list($route);
}

function ccc_fetch_detail(string $route, string $slug)
{
    $mode = get_option(ccc_option_name($route . '_mode'), 'local');

    if ($mode === 'local') {
        return ccc_fetch_local_detail($route, $slug);
    }
    return ccc_fetch_remote_detail($route, $slug);
}

// ══════════════════════════════════════════════════════════════════════════════
// LOCAL FETCH
// ══════════════════════════════════════════════════════════════════════════════

function ccc_fetch_local_list(string $route)
{
    $func = $route === 'chronicles' ? 'owbn_api_get_chronicles' : 'owbn_api_get_coordinators';

    if (!function_exists($func)) {
        return ['error' => 'Local source not available'];
    }

    $request = new WP_REST_Request('POST');
    $response = $func($request);

    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }

    return $response->get_data();
}

function ccc_fetch_local_detail(string $route, string $slug)
{
    $func = $route === 'chronicles' ? 'owbn_api_get_chronicle_detail' : 'owbn_api_get_coordinator_detail';

    if (!function_exists($func)) {
        return ['error' => 'Local source not available'];
    }

    $request = new WP_REST_Request('POST');
    $request->set_header('Content-Type', 'application/json');
    $request->set_body(wp_json_encode(['slug' => $slug]));
    $response = $func($request);

    return is_wp_error($response) ? ['error' => $response->get_error_message()] : $response->get_data();
}

// ══════════════════════════════════════════════════════════════════════════════
// REMOTE FETCH
// ══════════════════════════════════════════════════════════════════════════════

function ccc_fetch_remote_list(string $route)
{
    $url = get_option(ccc_option_name($route . '_url'), '');
    $key = get_option(ccc_option_name($route . '_api_key'), '');

    if (empty($url)) {
        return ['error' => 'Remote URL not configured'];
    }

    $response = wp_remote_post(trailingslashit($url) . $route, [
        'timeout' => 15,
        'headers' => [
            'Content-Type' => 'application/json',
            'x-api-key'    => $key,
        ],
        'body' => wp_json_encode([]),
    ]);

    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }

    return json_decode(wp_remote_retrieve_body($response), true) ?: [];
}

function ccc_fetch_remote_detail(string $route, string $slug)
{
    $url = get_option(ccc_option_name($route . '_url'), '');
    $key = get_option(ccc_option_name($route . '_api_key'), '');

    if (empty($url)) {
        return ['error' => 'Remote URL not configured'];
    }

    $endpoint = $route === 'chronicles' ? 'chronicle-detail' : 'coordinator-detail';

    $response = wp_remote_post(trailingslashit($url) . $endpoint, [
        'timeout' => 15,
        'headers' => [
            'Content-Type' => 'application/json',
            'x-api-key'    => $key,
        ],
        'body' => wp_json_encode(['slug' => $slug]),
    ]);

    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }

    return json_decode(wp_remote_retrieve_body($response), true) ?: ['error' => 'Not found'];
}
