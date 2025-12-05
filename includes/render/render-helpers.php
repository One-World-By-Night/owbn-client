<?php

/**
 * OWBN-CC-Client Render Helpers
 * 
 * Reusable display components for frontend rendering.
 * 
 * @package OWBN-CC-Client
 * @version 1.1.0
 */

defined('ABSPATH') || exit;

// ══════════════════════════════════════════════════════════════════════════════
// USER/STAFF DISPLAY
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Render a staff member block.
 *
 * @param array  $user  User data with display_name, display_email
 * @param string $role  Role label (e.g., "Head Storyteller")
 * @param string $id    Optional HTML ID
 * @return string HTML output
 */
function ccc_render_staff_block(array $user, string $role, string $id = ''): string
{
    if (empty($user['display_name'])) {
        return '';
    }

    $id_attr = $id ? ' id="' . esc_attr($id) . '"' : '';

    ob_start();
?>
    <div class="ccc-staff-item" <?php echo $id_attr; ?>>
        <span class="ccc-staff-role"><?php echo esc_html($role); ?></span>
        <span class="ccc-staff-name"><?php echo esc_html($user['display_name']); ?></span>
        <?php if (!empty($user['display_email'])) : ?>
            <a class="ccc-staff-email" href="mailto:<?php echo esc_attr($user['display_email']); ?>">
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
function ccc_render_ast_list(array $ast_list): string
{
    $ast_list = array_filter($ast_list, fn($a) => !empty($a['display_name']));

    if (empty($ast_list)) {
        return '';
    }

    ob_start();
?>
    <div id="ccc-staff-ast-list" class="ccc-staff-ast-list">
        <h3><?php esc_html_e('Assistant Storytellers', 'owbn-cc-client'); ?></h3>
        <div class="ccc-ast-grid">
            <?php foreach ($ast_list as $ast) : ?>
                <div class="ccc-ast-item">
                    <span class="ccc-ast-name"><?php echo esc_html($ast['display_name']); ?></span>
                    <?php if (!empty($ast['role']) && $ast['role'] !== 'AST') : ?>
                        <span class="ccc-ast-role">(<?php echo esc_html($ast['role']); ?>)</span>
                    <?php endif; ?>
                    <?php if (!empty($ast['display_email'])) : ?>
                        <a class="ccc-ast-email" href="mailto:<?php echo esc_attr($ast['display_email']); ?>">
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

// ══════════════════════════════════════════════════════════════════════════════
// SESSION DISPLAY
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Render session list.
 *
 * @param array $sessions Array of session data
 * @return string HTML output
 */
function ccc_render_session_list(array $sessions): string
{
    $sessions = array_filter($sessions, fn($s) => !empty($s['day']) || !empty($s['session_type']));

    if (empty($sessions)) {
        return '';
    }

    ob_start();
?>
    <div id="ccc-sessions" class="ccc-sessions-list">
        <?php foreach ($sessions as $session) : ?>
            <?php echo ccc_render_session_item($session); ?>
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
function ccc_render_session_item(array $session): string
{
    ob_start();
?>
    <div class="ccc-session-item">
        <?php
        $when_parts = array_filter([
            $session['frequency'] ?? '',
            $session['day'] ?? '',
        ]);
        ?>
        <?php if ($when_parts) : ?>
            <span class="ccc-session-when"><?php echo esc_html(implode(' ', $when_parts)); ?></span>
        <?php endif; ?>

        <?php if (!empty($session['session_type'])) : ?>
            <span class="ccc-session-type"><?php echo esc_html($session['session_type']); ?></span>
        <?php endif; ?>

        <?php if (!empty($session['checkin_time']) || !empty($session['start_time'])) : ?>
            <span class="ccc-session-times">
                <?php if (!empty($session['checkin_time'])) : ?>
                    <span class="ccc-session-checkin">
                        <?php esc_html_e('Check-in:', 'owbn-cc-client'); ?> <?php echo esc_html($session['checkin_time']); ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($session['start_time'])) : ?>
                    <span class="ccc-session-start">
                        <?php esc_html_e('Start:', 'owbn-cc-client'); ?> <?php echo esc_html($session['start_time']); ?>
                    </span>
                <?php endif; ?>
            </span>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

// ══════════════════════════════════════════════════════════════════════════════
// LOCATION DISPLAY
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Format location parts into display string.
 *
 * @param array $location Location data with city, region, country, address
 * @return string Formatted location string
 */
function ccc_format_location(array $location): string
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
 * Render location item.
 *
 * @param array  $location Location data
 * @param string $type     'game_site' or 'ic_location'
 * @return string HTML output
 */
function ccc_render_location_item(array $location, string $type = 'game_site'): string
{
    $has_content = !empty($location['name']) || !empty($location['city']) || !empty($location['url']);

    if (!$has_content) {
        return '';
    }

    ob_start();
?>
    <div class="ccc-location-item ccc-<?php echo esc_attr($type); ?>">
        <?php if (!empty($location['name'])) : ?>
            <span class="ccc-location-name"><?php echo esc_html($location['name']); ?></span>
        <?php endif; ?>

        <?php $address = ccc_format_location($location); ?>
        <?php if ($address) : ?>
            <span class="ccc-location-address"><?php echo esc_html($address); ?></span>
        <?php endif; ?>

        <?php if (!empty($location['url'])) : ?>
            <a class="ccc-location-url" href="<?php echo esc_url($location['url']); ?>" target="_blank" rel="noopener">
                <?php echo esc_html($location['url']); ?>
            </a>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render location list.
 *
 * @param array  $locations Array of location data
 * @param string $type      'game_site' or 'ic_location'
 * @return string HTML output
 */
function ccc_render_location_list(array $locations, string $type = 'game_site'): string
{
    $locations = array_filter($locations, fn($l) => !empty($l['name']) || !empty($l['city']) || !empty($l['url']));

    if (empty($locations)) {
        return '';
    }

    ob_start();
?>
    <div class="ccc-locations-grid">
        <?php foreach ($locations as $location) : ?>
            <?php echo ccc_render_location_item($location, $type); ?>
        <?php endforeach; ?>
    </div>
<?php
    return ob_get_clean();
}

// ══════════════════════════════════════════════════════════════════════════════
// LINKS DISPLAY
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Render document links list.
 *
 * @param array $links Array of document link data
 * @return string HTML output
 */
function ccc_render_document_links(array $links): string
{
    $links = array_filter($links, fn($l) => !empty($l['url']) || !empty($l['link']));

    if (empty($links)) {
        return '';
    }

    ob_start();
?>
    <ul class="ccc-document-list">
        <?php foreach ($links as $doc) : ?>
            <li>
                <?php $url = $doc['url'] ?? $doc['link'] ?? ''; ?>
                <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">
                    <?php echo esc_html($doc['title'] ?: $url); ?>
                </a>
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
function ccc_render_social_links(array $links): string
{
    $links = array_filter($links, fn($l) => !empty($l['url']));

    if (empty($links)) {
        return '';
    }

    ob_start();
?>
    <div class="ccc-social-grid">
        <?php foreach ($links as $social) : ?>
            <div class="ccc-social-item">
                <span class="ccc-social-platform"><?php echo esc_html(ucfirst($social['platform'] ?? 'Link')); ?></span>
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
function ccc_render_email_lists(array $lists): string
{
    $lists = array_filter($lists, fn($l) => !empty($l['list_name']) || !empty($l['list_email']));

    if (empty($lists)) {
        return '';
    }

    ob_start();
?>
    <ul class="ccc-email-list">
        <?php foreach ($lists as $list) : ?>
            <li>
                <?php if (!empty($list['list_name'])) : ?>
                    <span class="ccc-list-name"><?php echo esc_html($list['list_name']); ?></span>
                <?php endif; ?>
                <?php if (!empty($list['list_email'])) : ?>
                    <a href="mailto:<?php echo esc_attr($list['list_email']); ?>">
                        <?php echo esc_html($list['list_email']); ?>
                    </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php
    return ob_get_clean();
}

// ══════════════════════════════════════════════════════════════════════════════
// INFO DISPLAY
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Render info item (label + value pair).
 *
 * @param string $label Label text
 * @param string $value Value text
 * @param string $id    Optional HTML ID
 * @return string HTML output
 */
function ccc_render_info_item(string $label, string $value, string $id = ''): string
{
    if (empty(trim($value))) {
        return '';
    }

    $id_attr = $id ? ' id="' . esc_attr($id) . '"' : '';

    ob_start();
?>
    <div class="ccc-info-item" <?php echo $id_attr; ?>>
        <span class="ccc-info-label"><?php echo esc_html($label); ?></span>
        <span class="ccc-info-value"><?php echo esc_html($value); ?></span>
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
function ccc_render_content_section(string $title, string $content, string $id = ''): string
{
    if (empty(trim($content))) {
        return '';
    }

    $id_attr = $id ? ' id="' . esc_attr($id) . '"' : '';

    ob_start();
?>
    <div class="ccc-content-section" <?php echo $id_attr; ?>>
        <h3><?php echo esc_html($title); ?></h3>
        <div class="ccc-content"><?php echo wp_kses_post($content); ?></div>
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
function ccc_render_player_lists(array $lists): string
{
    $lists = array_filter($lists, fn($l) => !empty($l['list_name']));

    if (empty($lists)) {
        return '';
    }

    ob_start();
?>
    <div class="ccc-player-lists">
        <?php foreach ($lists as $list) : ?>
            <div class="ccc-player-list-item">
                <div class="ccc-player-list-name"><?php echo esc_html($list['list_name']); ?></div>
                <div class="ccc-player-list-meta">
                    <?php if (!empty($list['access'])) : ?>
                        <span class="ccc-player-list-access"><?php echo esc_html($list['access']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($list['ic_ooc'])) : ?>
                        <span class="ccc-player-list-type"><?php echo esc_html($list['ic_ooc']); ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($list['address'])) : ?>
                    <a class="ccc-player-list-email" href="mailto:<?php echo esc_attr($list['address']); ?>"><?php echo esc_html($list['address']); ?></a>
                <?php endif; ?>
                <?php if (!empty($list['signup_url'])) : ?>
                    <a class="ccc-player-list-signup" href="<?php echo esc_url($list['signup_url']); ?>" target="_blank" rel="noopener"><?php esc_html_e('Sign Up', 'owbn-cc-client'); ?></a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php
    return ob_get_clean();
}
