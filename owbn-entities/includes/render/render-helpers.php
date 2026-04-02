<?php

/**
 * OWBN-Client Render Helpers
 * Reusable display components for frontend rendering.
 * 
 */

defined('ABSPATH') || exit;


/**
 * Render a staff member block.
 *
 * @param array  $user  User data with display_name, display_email
 * @param string $role  Role label (e.g., "Head Storyteller")
 * @param string $id    Optional HTML ID
 * @return string HTML output
 */
function owc_render_staff_block(array $user, string $role, string $id = ''): string
{
    if (empty($user['display_name'])) {
        return '';
    }

    $id_attr = $id ? ' id="' . esc_attr($id) . '"' : '';

    ob_start();
?>
    <div class="owc-staff-item" <?php echo $id_attr; ?>>
        <span class="owc-staff-role"><?php echo esc_html($role); ?></span>
        <span class="owc-staff-name"><?php echo esc_html($user['display_name']); ?></span>
        <?php if (!empty($user['display_email'])) : ?>
            <a class="owc-staff-email" href="mailto:<?php echo esc_attr($user['display_email']); ?>">
                <?php echo esc_html($user['display_email']); ?>
            </a>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render AST list.
 *
 * @param array $ast_list Array of AST data
 * @return string HTML output
 */
function owc_render_ast_list(array $ast_list): string
{
    $ast_list = array_filter($ast_list, fn($a) => !empty($a['display_name']));

    if (empty($ast_list)) {
        return '';
    }

    ob_start();
?>
    <div id="owc-staff-ast-list" class="owc-staff-ast-list">
        <h3><?php esc_html_e('Assistant Storytellers', 'owbn-entities'); ?></h3>
        <div class="owc-ast-grid">
            <?php foreach ($ast_list as $ast) : ?>
                <div class="owc-ast-item">
                    <span class="owc-ast-name"><?php echo esc_html($ast['display_name']); ?></span>
                    <?php if (!empty($ast['role']) && $ast['role'] !== 'AST') : ?>
                        <span class="owc-ast-role">(<?php echo esc_html($ast['role']); ?>)</span>
                    <?php endif; ?>
                    <?php if (!empty($ast['display_email'])) : ?>
                        <a class="owc-ast-email" href="mailto:<?php echo esc_attr($ast['display_email']); ?>">
                            <?php echo esc_html($ast['display_email']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}


/**
 * Render session list.
 *
 * @param array $sessions Array of session data
 * @return string HTML output
 */
function owc_render_session_list(array $sessions): string
{
    $sessions = array_filter($sessions, fn($s) => !empty($s['day']) || !empty($s['session_type']));

    if (empty($sessions)) {
        return '';
    }

    ob_start();
?>
    <div id="owc-sessions" class="owc-sessions-list">
        <?php foreach ($sessions as $session) : ?>
            <?php echo owc_render_session_item($session); ?>
        <?php endforeach; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render single session item.
 *
 * @param array $session Session data
 * @return string HTML output
 */
function owc_render_session_item(array $session): string
{
    ob_start();
?>
    <div class="owc-session-item">
        <?php
        $when_parts = array_filter([
            $session['frequency'] ?? '',
            $session['day'] ?? '',
        ]);
        ?>
        <?php if ($when_parts) : ?>
            <span class="owc-session-when"><?php echo esc_html(implode(' ', $when_parts)); ?></span>
        <?php endif; ?>

        <?php if (!empty($session['session_type'])) : ?>
            <span class="owc-session-type"><?php echo esc_html($session['session_type']); ?></span>
        <?php endif; ?>

        <?php if (!empty($session['checkin_time']) || !empty($session['start_time'])) : ?>
            <span class="owc-session-times">
                <?php if (!empty($session['checkin_time'])) : ?>
                    <span class="owc-session-checkin">
                        <?php esc_html_e('Check-in:', 'owbn-entities'); ?> <?php echo esc_html($session['checkin_time']); ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($session['start_time'])) : ?>
                    <span class="owc-session-start">
                        <?php esc_html_e('Start:', 'owbn-entities'); ?> <?php echo esc_html($session['start_time']); ?>
                    </span>
                <?php endif; ?>
            </span>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}


/**
 * Format location parts into display string.
 *
 * @param array $location Location data with city, region, country, address
 * @return string Formatted location string
 */
function owc_format_location(array $location): string
{
    $parts = array_filter([
        $location['address'] ?? '',
        $location['city'] ?? '',
        $location['region'] ?? '',
        $location['country'] ?? '',
    ]);

    return implode(', ', $parts);
}

/**
 * Render document links list.
 *
 * @param array $links Array of document link data
 * @return string HTML output
 */
function owc_render_document_links(array $links): string
{
    // Resolve file_id to URL for uploaded documents.
    foreach ( $links as &$l ) {
        if ( empty( $l['url'] ) && empty( $l['link'] ) && ! empty( $l['file_id'] ) ) {
            $l['url'] = wp_get_attachment_url( $l['file_id'] );
        }
    }
    unset( $l );

    $links = array_filter($links, fn($l) => !empty($l['url']) || !empty($l['link']));

    if (empty($links)) {
        return '';
    }

    ob_start();
?>
    <ul class="owc-document-list">
        <?php foreach ($links as $doc) : ?>
            <li>
                <?php $url = $doc['url'] ?? $doc['link'] ?? ''; ?>
                <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">
                    <?php echo esc_html($doc['title'] ?: $url); ?>
                </a>
                <?php if (!empty($doc['last_updated'])) : ?>
                    <span class="owc-document-updated">(<?php esc_html_e('Updated:', 'owbn-entities'); ?> <?php echo esc_html($doc['last_updated']); ?>)</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php
    return ob_get_clean();
}

/**
 * Render social links.
 *
 * @param array $links Array of social link data
 * @return string HTML output
 */
function owc_render_social_links(array $links): string
{
    $links = array_filter($links, fn($l) => !empty($l['url']));

    if (empty($links)) {
        return '';
    }

    ob_start();
?>
    <div class="owc-social-grid">
        <?php foreach ($links as $social) : ?>
            <div class="owc-social-item">
                <span class="owc-social-platform"><?php echo esc_html(ucfirst($social['platform'] ?? 'Link')); ?></span>
                <a href="<?php echo esc_url($social['url']); ?>" target="_blank" rel="noopener">
                    <?php echo esc_html($social['url']); ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render email lists.
 *
 * @param array $lists Array of email list data
 * @return string HTML output
 */
function owc_render_email_lists(array $lists): string
{
    $lists = array_filter($lists, fn($l) => !empty($l['list_name']) || !empty($l['email_address']));

    if (empty($lists)) {
        return '';
    }

    ob_start();
?>
    <ul class="owc-email-list">
        <?php foreach ($lists as $list) : ?>
            <li>
                <?php if (!empty($list['list_name'])) : ?>
                    <span class="owc-list-name"><?php echo esc_html($list['list_name']); ?></span>
                <?php endif; ?>
                <?php if (!empty($list['email_address'])) : ?>
                    <a href="mailto:<?php echo esc_attr($list['email_address']); ?>">
                        <?php echo esc_html($list['email_address']); ?>
                    </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php
    return ob_get_clean();
}


/**
 * Render info item (label + value pair).
 *
 * @param string $label Label text
 * @param string $value Value text
 * @param string $id    Optional HTML ID
 * @return string HTML output
 */
function owc_render_info_item(string $label, string $value, string $id = ''): string
{
    if (empty(trim($value))) {
        return '';
    }

    $id_attr = $id ? ' id="' . esc_attr($id) . '"' : '';

    ob_start();
?>
    <div class="owc-info-item" <?php echo $id_attr; ?>>
        <span class="owc-info-label"><?php echo esc_html($label); ?></span>
        <span class="owc-info-value"><?php echo esc_html($value); ?></span>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render content section with header.
 *
 * @param string $title   Section title
 * @param string $content HTML content
 * @param string $id      Optional HTML ID
 * @return string HTML output
 */
function owc_render_content_section(string $title, string $content, string $id = ''): string
{
    if (empty(trim($content))) {
        return '';
    }

    $id_attr = $id ? ' id="' . esc_attr($id) . '"' : '';

    ob_start();
?>
    <div class="owc-content-section" <?php echo $id_attr; ?>>
        <h3><?php echo esc_html($title); ?></h3>
        <div class="owc-content"><?php echo wp_kses_post($content); ?></div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render player lists.
 *
 * @param array $lists Array of player list data
 * @return string HTML output
 */
function owc_render_player_lists(array $lists): string
{
    $lists = array_filter($lists, fn($l) => !empty($l['list_name']));

    if (empty($lists)) {
        return '';
    }

    ob_start();
?>
    <div class="owc-player-lists">
        <?php foreach ($lists as $list) : ?>
            <div class="owc-player-list-item">
                <div class="owc-player-list-name"><?php echo esc_html($list['list_name']); ?></div>
                <div class="owc-player-list-meta">
                    <?php if (!empty($list['access'])) : ?>
                        <span class="owc-player-list-access"><?php echo esc_html($list['access']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($list['ic_ooc'])) : ?>
                        <span class="owc-player-list-type"><?php echo esc_html($list['ic_ooc']); ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($list['address'])) : ?>
                    <a class="owc-player-list-email" href="mailto:<?php echo esc_attr($list['address']); ?>"><?php echo esc_html($list['address']); ?></a>
                <?php endif; ?>
                <?php if (!empty($list['signup_url'])) : ?>
                    <a class="owc-player-list-signup" href="<?php echo esc_url($list['signup_url']); ?>" target="_blank" rel="noopener"><?php esc_html_e('Sign Up', 'owbn-entities'); ?></a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php
    return ob_get_clean();
}


/**
 * Determine slug type from cached CC data.
 *
 * @param string $slug
 * @return string 'chronicle'|'coordinator'|''
 */
function owc_get_slug_type(string $slug): string
{
    static $cache = null;

    if ($cache === null) {
        $cache = ['chronicles' => [], 'coordinators' => []];

        $chronicles = owc_fetch_list('chronicles');
        if (!isset($chronicles['error']) && is_array($chronicles)) {
            foreach ($chronicles as $c) {
                if (!empty($c['slug'])) {
                    $cache['chronicles'][$c['slug']] = true;
                }
            }
        }

        $coordinators = owc_fetch_list('coordinators');
        if (!isset($coordinators['error']) && is_array($coordinators)) {
            foreach ($coordinators as $c) {
                if (!empty($c['slug'])) {
                    $cache['coordinators'][$c['slug']] = true;
                }
            }
        }
    }

    if (isset($cache['chronicles'][$slug])) return 'chronicle';
    if (isset($cache['coordinators'][$slug])) return 'coordinator';
    return '';
}

/**
 * Render single slug as link.
 *
 * @param string $slug
 * @param string $context Force type: 'chronicle'|'coordinator'|'' (auto-detect)
 * @return string HTML
 */
function owc_render_territory_slug_link(string $slug, string $context = ''): string
{
    if (empty($slug)) return '';

    $type = $context ?: owc_get_slug_type($slug);

    if ($type === 'chronicle') {
        $base = owc_get_chronicles_slug();
        return sprintf('<a href="/%s/%s/">%s</a>', esc_attr($base), esc_attr($slug), esc_html($slug));
    }

    if ($type === 'coordinator') {
        $base = owc_get_coordinators_slug();
        return sprintf('<a href="/%s/%s/">%s</a>', esc_attr($base), esc_attr($slug), esc_html($slug));
    }

    return esc_html($slug);
}

/**
 * Render multiple slugs as linked list.
 *
 * @param array  $slugs
 * @param string $context Force type for all slugs
 * @return string HTML
 */
function owc_render_territory_slugs(array $slugs, string $context = ''): string
{
    if (empty($slugs)) return '';

    $links = array_map(fn($s) => owc_render_territory_slug_link($s, $context), $slugs);
    return implode(', ', $links);
}

/**
 * Render country codes as names.
 *
 * @param array $codes ISO country codes
 * @return string Comma-separated names
 */
function owc_render_territory_countries(array $codes): string
{
    if (empty($codes)) return '';

    if (function_exists('owc_tm_format_countries')) {
        return owc_tm_format_countries($codes);
    }

    // Fallback if territory-manager not active
    return implode(', ', $codes);
}
