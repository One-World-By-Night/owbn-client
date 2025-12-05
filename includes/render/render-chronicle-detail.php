<?php

/**
 * OWBN-CC-Client Chronicle Detail Render
 * 
 * @package OWBN-CC-Client
 * @version 1.1.0
 */

defined('ABSPATH') || exit;

/**
 * Render chronicle detail.
 */
function ccc_render_chronicle_detail(array $chronicle): string
{
    if (empty($chronicle) || isset($chronicle['error'])) {
        return '<p class="ccc-error">' . esc_html($chronicle['error'] ?? __('Chronicle not found.', 'owbn-cc-client')) . '</p>';
    }

    $back_url = home_url('/' . ccc_get_chronicles_slug() . '/');

    ob_start();
?>
    <div id="ccc-chronicle-detail" class="ccc-chronicle-detail">

        <div id="ccc-back-link" class="ccc-back-link">
            <a href="<?php echo esc_url($back_url); ?>"><?php esc_html_e('← Back to Chronicles', 'owbn-cc-client'); ?></a>
        </div>

        <?php echo ccc_render_chronicle_header($chronicle); ?>

        <div class="ccc-main-content-wrapper">
            <?php echo ccc_render_in_brief($chronicle); ?>
            <?php echo ccc_render_chronicle_about($chronicle); ?>
        </div>

        <div class="ccc-clear"></div>

        <?php echo ccc_render_chronicle_narrative($chronicle); ?>

        <div class="ccc-staff-sessions-wrapper">
            <?php echo ccc_render_chronicle_staff($chronicle); ?>
            <?php echo ccc_render_game_sessions_box($chronicle); ?>
        </div>

        <div class="ccc-clear"></div>

        <?php echo ccc_render_chronicle_links($chronicle); ?>
        <?php echo ccc_render_chronicle_documents($chronicle); ?>
        <?php echo ccc_render_chronicle_player_lists($chronicle); ?>
        <?php echo ccc_render_satellite_parent($chronicle); ?>

    </div>
<?php
    return ob_get_clean();
}

/**
 * Render chronicle header (title + badges only).
 */
function ccc_render_chronicle_header(array $chronicle): string
{
    $title = $chronicle['title'] ?? '';

    $badges = [];
    if (!empty($chronicle['chronicle_probationary']) && $chronicle['chronicle_probationary'] !== '0') {
        $badges[] = '<span class="ccc-badge ccc-badge-probationary">' . esc_html__('Probationary', 'owbn-cc-client') . '</span>';
    }
    if (!empty($chronicle['chronicle_satellite']) && $chronicle['chronicle_satellite'] !== '0') {
        $badges[] = '<span class="ccc-badge ccc-badge-satellite">' . esc_html__('Satellite', 'owbn-cc-client') . '</span>';
    }

    ob_start();
?>
    <div id="ccc-chronicle-header" class="ccc-chronicle-header">
        <h1 id="ccc-chronicle-title" class="ccc-chronicle-title"><?php echo esc_html($title); ?></h1>
        <?php if (!empty($badges)) : ?>
            <div id="ccc-chronicle-badges" class="ccc-chronicle-badges"><?php echo implode(' ', $badges); ?></div>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render "In Brief" sidebar box.
 */
function ccc_render_in_brief(array $chronicle): string
{
    $ooc = $chronicle['ooc_locations'] ?? [];
    $location = ccc_format_location($ooc);
    $genres = $chronicle['genres'] ?? [];
    $genres_display = is_array($genres) ? implode(', ', $genres) : $genres;

    ob_start();
?>
    <div id="ccc-in-brief" class="ccc-info-box ccc-in-brief">
        <h3><?php esc_html_e('In Brief', 'owbn-cc-client'); ?></h3>
        <?php if ($location) : ?>
            <div class="ccc-brief-item"><?php echo esc_html($location); ?></div>
        <?php endif; ?>
        <?php if ($genres_display) : ?>
            <div class="ccc-brief-item"><strong><?php esc_html_e('Genre(s):', 'owbn-cc-client'); ?></strong> <?php echo esc_html($genres_display); ?></div>
        <?php endif; ?>
        <?php if (!empty($chronicle['game_type'])) : ?>
            <div class="ccc-brief-item"><strong><?php esc_html_e('Game Type:', 'owbn-cc-client'); ?></strong> <?php echo esc_html($chronicle['game_type']); ?></div>
        <?php endif; ?>
        <?php if (!empty($chronicle['active_player_count'])) : ?>
            <div class="ccc-brief-item"><strong><?php esc_html_e('Number of Players:', 'owbn-cc-client'); ?></strong> <?php echo esc_html($chronicle['active_player_count']); ?></div>
        <?php endif; ?>
        <?php if (!empty($chronicle['chronicle_region'])) : ?>
            <div class="ccc-brief-item"><strong><?php esc_html_e('OWBN Region:', 'owbn-cc-client'); ?></strong> <?php echo esc_html($chronicle['chronicle_region']); ?></div>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render About section (content field).
 */
function ccc_render_chronicle_about(array $chronicle): string
{
    $content = $chronicle['content'] ?? '';
    if (empty(trim($content))) {
        return '';
    }

    ob_start();
?>
    <div id="ccc-chronicle-about" class="ccc-chronicle-about">
        <h2><?php esc_html_e('About', 'owbn-cc-client'); ?></h2>
        <div class="ccc-content"><?php echo wp_kses_post($content); ?></div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render narrative sections (Premise, Theme, Mood, Traveler Info) - no wrapper heading.
 */
function ccc_render_chronicle_narrative(array $chronicle): string
{
    $premise = $chronicle['premise'] ?? '';
    $theme = $chronicle['game_theme'] ?? '';
    $mood = $chronicle['game_mood'] ?? '';
    $traveler = $chronicle['traveler_info'] ?? '';

    if (empty(trim($premise)) && empty(trim($theme)) && empty(trim($mood)) && empty(trim($traveler))) {
        return '';
    }

    ob_start();
?>
    <div id="ccc-chronicle-narrative" class="ccc-chronicle-narrative">
        <?php echo ccc_render_narrative_section(__('Premise', 'owbn-cc-client'), $premise); ?>
        <?php echo ccc_render_narrative_section(__('Theme', 'owbn-cc-client'), $theme); ?>
        <?php echo ccc_render_narrative_section(__('Mood', 'owbn-cc-client'), $mood); ?>
        <?php echo ccc_render_narrative_section(__('Information for Travelers', 'owbn-cc-client'), $traveler); ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render single narrative section (only if content exists).
 */
function ccc_render_narrative_section(string $title, string $content): string
{
    if (empty(trim($content))) {
        return '';
    }

    ob_start();
?>
    <div class="ccc-narrative-section">
        <h3><?php echo esc_html($title); ?></h3>
        <div class="ccc-content"><?php echo wp_kses_post($content); ?></div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render staff section.
 */
function ccc_render_chronicle_staff(array $chronicle): string
{
    $hst = $chronicle['hst_info'] ?? [];
    $cm = $chronicle['cm_info'] ?? [];
    $ast_list = $chronicle['ast_list'] ?? [];
    $admin = $chronicle['admin_contact'] ?? [];

    $has_staff = !empty($hst['display_name']) || !empty($cm['display_name']) ||
        !empty($admin['display_name']) || !empty(array_filter($ast_list, fn($a) => !empty($a['display_name'])));

    if (!$has_staff) {
        return '';
    }

    ob_start();
?>
    <div id="ccc-chronicle-staff" class="ccc-chronicle-staff">
        <h2><?php esc_html_e('Staff', 'owbn-cc-client'); ?></h2>
        <div class="ccc-staff-list">
            <?php echo ccc_render_staff_line(__('Head Storyteller', 'owbn-cc-client'), $hst); ?>
            <?php echo ccc_render_staff_line(__('Council Member', 'owbn-cc-client'), $cm); ?>
            <?php echo ccc_render_staff_line(__('Admin Contact', 'owbn-cc-client'), $admin); ?>
            <?php foreach ($ast_list as $ast) : ?>
                <?php echo ccc_render_staff_line($ast['role'] ?? __('AST', 'owbn-cc-client'), $ast); ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render single staff line.
 */
function ccc_render_staff_line(string $role, array $info): string
{
    if (empty($info['display_name'])) {
        return '';
    }

    ob_start();
?>
    <div class="ccc-staff-line">
        <span class="ccc-staff-role"><?php echo esc_html($role); ?>:</span>
        <span class="ccc-staff-name"><?php echo esc_html($info['display_name']); ?></span>
        <?php if (!empty($info['display_email'])) : ?>
            <a class="ccc-staff-email" href="mailto:<?php echo esc_attr($info['display_email']); ?>"><?php echo esc_html($info['display_email']); ?></a>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render Game Sessions box (floated, same style as In Brief).
 */
function ccc_render_game_sessions_box(array $chronicle): string
{
    $sessions = $chronicle['session_list'] ?? [];
    $sessions = array_filter($sessions, fn($s) => !empty($s['day']) || !empty($s['session_type']));

    if (empty($sessions)) {
        return '';
    }

    ob_start();
?>
    <div id="ccc-game-sessions-box" class="ccc-info-box ccc-game-sessions-box">
        <h3><?php esc_html_e('Game Sessions', 'owbn-cc-client'); ?></h3>
        <?php foreach ($sessions as $session) : ?>
            <div class="ccc-session-item">
                <?php
                $parts = array_filter([
                    $session['frequency'] ?? '',
                    $session['day'] ?? '',
                ]);
                if ($parts) : ?>
                    <div class="ccc-session-when"><?php echo esc_html(implode(' ', $parts)); ?></div>
                <?php endif; ?>
                <?php if (!empty($session['session_type'])) : ?>
                    <div class="ccc-session-type"><?php echo esc_html($session['session_type']); ?></div>
                <?php endif; ?>
                <?php if (!empty($session['checkin_time'])) : ?>
                    <div class="ccc-session-time"><?php esc_html_e('Check-in:', 'owbn-cc-client'); ?> <?php echo esc_html($session['checkin_time']); ?></div>
                <?php endif; ?>
                <?php if (!empty($session['start_time'])) : ?>
                    <div class="ccc-session-time"><?php esc_html_e('Start:', 'owbn-cc-client'); ?> <?php echo esc_html($session['start_time']); ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render Links & Resources.
 */
function ccc_render_chronicle_links(array $chronicle): string
{
    $web_url = $chronicle['web_url'] ?? '';
    $social_urls = array_filter($chronicle['social_urls'] ?? [], fn($s) => !empty($s['url']));
    $email_lists = array_filter($chronicle['email_lists'] ?? [], fn($e) => !empty($e['list_name']) || !empty($e['list_email']));

    if (empty($web_url) && empty($social_urls) && empty($email_lists)) {
        return '';
    }

    ob_start();
?>
    <div id="ccc-chronicle-links" class="ccc-chronicle-links">
        <h2><?php esc_html_e('Links & Resources', 'owbn-cc-client'); ?></h2>
        <?php if ($web_url) : ?>
            <div class="ccc-link-item"><a href="<?php echo esc_url($web_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($web_url); ?></a></div>
        <?php endif; ?>
        <?php if (!empty($social_urls)) : ?>
            <?php echo ccc_render_social_links($social_urls); ?>
        <?php endif; ?>
        <?php if (!empty($email_lists)) : ?>
            <?php echo ccc_render_email_lists($email_lists); ?>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render Documents section.
 */
function ccc_render_chronicle_documents(array $chronicle): string
{
    $docs = array_filter($chronicle['document_links'] ?? [], fn($d) => !empty($d['link']));

    if (empty($docs)) {
        return '';
    }

    ob_start();
?>
    <div id="ccc-chronicle-documents" class="ccc-chronicle-documents">
        <h2><?php esc_html_e('Documents', 'owbn-cc-client'); ?></h2>
        <?php echo ccc_render_document_links($docs); ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render Player Lists section.
 */
function ccc_render_chronicle_player_lists(array $chronicle): string
{
    $lists = $chronicle['player_lists'] ?? [];
    $lists = array_filter($lists, fn($l) => !empty($l['list_name']));

    if (empty($lists)) {
        return '';
    }

    ob_start();
?>
    <div id="ccc-chronicle-player-lists" class="ccc-chronicle-player-lists">
        <h2><?php esc_html_e('Player Lists', 'owbn-cc-client'); ?></h2>
        <?php echo ccc_render_player_lists($lists); ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render satellite parent link.
 */
function ccc_render_satellite_parent(array $chronicle): string
{
    if (empty($chronicle['chronicle_satellite']) || $chronicle['chronicle_satellite'] === '0') {
        return '';
    }

    $parent_slug = $chronicle['chronicle_parent'] ?? '';
    $parent_title = $chronicle['chronicle_parent_title'] ?? '';

    if (empty($parent_slug) || empty($parent_title)) {
        return '';
    }

    $detail_page_id = get_option(ccc_option_name('chronicles_detail_page'), 0);
    $base_url = $detail_page_id ? get_permalink($detail_page_id) : home_url('/chronicle-detail/');
    $parent_url = add_query_arg('slug', $parent_slug, $base_url);

    ob_start();
?>
    <div id="ccc-satellite-parent" class="ccc-satellite-parent">
        <strong><?php esc_html_e('Satellite Parent:', 'owbn-cc-client'); ?></strong>
        <a href="<?php echo esc_url($parent_url); ?>"><?php echo esc_html($parent_title); ?></a>
    </div>
<?php
    return ob_get_clean();
}
