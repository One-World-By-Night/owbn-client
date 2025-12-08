<?php

/**
 * OWBN-Client Chronicle Detail Render
 * location: includes/render/render-chronicle-detail.php
 * @package OWBN-Client
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Render chronicle detail.
 */
function owc_render_chronicle_detail(array $chronicle): string
{
    if (empty($chronicle) || isset($chronicle['error'])) {
        return '<p class="owc-error">' . esc_html($chronicle['error'] ?? __('Chronicle not found.', 'owbn-client')) . '</p>';
    }

    // Use list page if configured, otherwise fall back to slug-based URL
    $list_page_id = get_option(owc_option_name('chronicles_list_page'), 0);
    $back_url = $list_page_id ? get_permalink($list_page_id) : home_url('/' . owc_get_chronicles_slug() . 's/');

    ob_start();
?>
    <div id="owc-chronicle-detail" class="owc-chronicle-detail">

        <div id="owc-back-link" class="owc-back-link">
            <a href="<?php echo esc_url($back_url); ?>"><?php esc_html_e('← Back to Chronicles', 'owbn-client'); ?></a>
        </div>

        <?php echo owc_render_chronicle_header($chronicle); ?>

        <div class="owc-main-content-wrapper">
            <?php echo owc_render_in_brief($chronicle); ?>
            <?php echo owc_render_chronicle_about($chronicle); ?>
        </div>

        <div class="owc-clear"></div>

        <?php echo owc_render_chronicle_narrative($chronicle); ?>

        <div class="owc-staff-sessions-wrapper">
            <?php echo owc_render_chronicle_staff($chronicle); ?>
            <?php echo owc_render_game_sessions_box($chronicle); ?>
        </div>

        <div class="owc-clear"></div>

        <?php echo owc_render_chronicle_links($chronicle); ?>
        <?php echo owc_render_chronicle_documents($chronicle); ?>
        <?php echo owc_render_chronicle_player_lists($chronicle); ?>
        <?php echo owc_render_satellite_parent($chronicle); ?>
        <?php echo owc_render_chronicle_territories($chronicle); ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render chronicle header (title + badges only).
 */
function owc_render_chronicle_header(array $chronicle): string
{
    $title = $chronicle['title'] ?? '';

    $badges = [];
    if (!empty($chronicle['chronicle_probationary']) && $chronicle['chronicle_probationary'] !== '0') {
        $badges[] = '<span class="owc-badge owc-badge-probationary">' . esc_html__('Probationary', 'owbn-client') . '</span>';
    }
    if (!empty($chronicle['chronicle_satellite']) && $chronicle['chronicle_satellite'] !== '0') {
        $badges[] = '<span class="owc-badge owc-badge-satellite">' . esc_html__('Satellite', 'owbn-client') . '</span>';
    }

    ob_start();
?>
    <div id="owc-chronicle-header" class="owc-chronicle-header">
        <h1 id="owc-chronicle-title" class="owc-chronicle-title"><?php echo esc_html($title); ?></h1>
        <?php if (!empty($badges)) : ?>
            <div id="owc-chronicle-badges" class="owc-chronicle-badges"><?php echo implode(' ', $badges); ?></div>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render "In Brief" sidebar box.
 */
function owc_render_in_brief(array $chronicle): string
{
    $ooc = $chronicle['ooc_locations'] ?? [];
    $location = owc_format_location($ooc);
    $genres = $chronicle['genres'] ?? [];
    $genres_display = is_array($genres) ? implode(', ', $genres) : $genres;

    ob_start();
?>
    <div id="owc-in-brief" class="owc-info-box owc-in-brief">
        <h3><?php esc_html_e('In Brief', 'owbn-client'); ?></h3>
        <?php if ($location) : ?>
            <div class="owc-brief-item"><?php echo esc_html($location); ?></div>
        <?php endif; ?>
        <?php if ($genres_display) : ?>
            <div class="owc-brief-item"><strong><?php esc_html_e('Genre(s):', 'owbn-client'); ?></strong> <?php echo esc_html($genres_display); ?></div>
        <?php endif; ?>
        <?php if (!empty($chronicle['game_type'])) : ?>
            <div class="owc-brief-item"><strong><?php esc_html_e('Game Type:', 'owbn-client'); ?></strong> <?php echo esc_html($chronicle['game_type']); ?></div>
        <?php endif; ?>
        <?php if (!empty($chronicle['active_player_count'])) : ?>
            <div class="owc-brief-item"><strong><?php esc_html_e('Number of Players:', 'owbn-client'); ?></strong> <?php echo esc_html($chronicle['active_player_count']); ?></div>
        <?php endif; ?>
        <?php if (!empty($chronicle['chronicle_region'])) : ?>
            <div class="owc-brief-item"><strong><?php esc_html_e('OWBN Region:', 'owbn-client'); ?></strong> <?php echo esc_html($chronicle['chronicle_region']); ?></div>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render About section (content field).
 */
function owc_render_chronicle_about(array $chronicle): string
{
    $content = $chronicle['content'] ?? '';
    if (empty(trim($content))) {
        return '';
    }

    ob_start();
?>
    <div id="owc-chronicle-about" class="owc-chronicle-about">
        <h2><?php esc_html_e('About', 'owbn-client'); ?></h2>
        <div class="owc-content"><?php echo wp_kses_post($content); ?></div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render narrative sections (Premise, Theme, Mood, Traveler Info) - no wrapper heading.
 */
function owc_render_chronicle_narrative(array $chronicle): string
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
    <div id="owc-chronicle-narrative" class="owc-chronicle-narrative">
        <?php echo owc_render_narrative_section(__('Premise', 'owbn-client'), $premise); ?>
        <?php echo owc_render_narrative_section(__('Theme', 'owbn-client'), $theme); ?>
        <?php echo owc_render_narrative_section(__('Mood', 'owbn-client'), $mood); ?>
        <?php echo owc_render_narrative_section(__('Information for Travelers', 'owbn-client'), $traveler); ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render single narrative section (only if content exists).
 */
function owc_render_narrative_section(string $title, string $content): string
{
    if (empty(trim($content))) {
        return '';
    }

    ob_start();
?>
    <div class="owc-narrative-section">
        <h3><?php echo esc_html($title); ?></h3>
        <div class="owc-content"><?php echo wp_kses_post($content); ?></div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render staff section.
 */
function owc_render_chronicle_staff(array $chronicle): string
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
    <div id="owc-chronicle-staff" class="owc-chronicle-staff">
        <h2><?php esc_html_e('Staff', 'owbn-client'); ?></h2>
        <div class="owc-staff-list">
            <?php echo owc_render_staff_line(__('Head Storyteller', 'owbn-client'), $hst); ?>
            <?php echo owc_render_staff_line(__('Council Member', 'owbn-client'), $cm); ?>
            <?php echo owc_render_staff_line(__('Admin Contact', 'owbn-client'), $admin); ?>
            <?php foreach ($ast_list as $ast) : ?>
                <?php echo owc_render_staff_line($ast['role'] ?? __('AST', 'owbn-client'), $ast); ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render single staff line.
 */
function owc_render_staff_line(string $role, array $info): string
{
    if (empty($info['display_name'])) {
        return '';
    }

    ob_start();
?>
    <div class="owc-staff-line">
        <span class="owc-staff-role"><?php echo esc_html($role); ?>:</span>
        <span class="owc-staff-name"><?php echo esc_html($info['display_name']); ?></span>
        <?php if (!empty($info['display_email'])) : ?>
            <a class="owc-staff-email" href="mailto:<?php echo esc_attr($info['display_email']); ?>"><?php echo esc_html($info['display_email']); ?></a>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render Game Sessions box (floated, same style as In Brief).
 */
function owc_render_game_sessions_box(array $chronicle): string
{
    $sessions = $chronicle['session_list'] ?? [];
    $sessions = array_filter($sessions, fn($s) => !empty($s['day']) || !empty($s['session_type']));

    if (empty($sessions)) {
        return '';
    }

    ob_start();
?>
    <div id="owc-game-sessions-box" class="owc-info-box owc-game-sessions-box">
        <h3><?php esc_html_e('Game Sessions', 'owbn-client'); ?></h3>
        <?php foreach ($sessions as $session) : ?>
            <div class="owc-session-item">
                <?php
                $parts = array_filter([
                    $session['frequency'] ?? '',
                    $session['day'] ?? '',
                ]);
                if ($parts) : ?>
                    <div class="owc-session-when"><?php echo esc_html(implode(' ', $parts)); ?></div>
                <?php endif; ?>
                <?php if (!empty($session['session_type'])) : ?>
                    <div class="owc-session-type"><?php echo esc_html($session['session_type']); ?></div>
                <?php endif; ?>
                <?php if (!empty($session['checkin_time'])) : ?>
                    <div class="owc-session-time"><?php esc_html_e('Check-in:', 'owbn-client'); ?> <?php echo esc_html($session['checkin_time']); ?></div>
                <?php endif; ?>
                <?php if (!empty($session['start_time'])) : ?>
                    <div class="owc-session-time"><?php esc_html_e('Start:', 'owbn-client'); ?> <?php echo esc_html($session['start_time']); ?></div>
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
function owc_render_chronicle_links(array $chronicle): string
{
    $web_url = $chronicle['web_url'] ?? '';
    $social_urls = array_filter($chronicle['social_urls'] ?? [], fn($s) => !empty($s['url']));
    $email_lists = array_filter($chronicle['email_lists'] ?? [], fn($e) => !empty($e['list_name']) || !empty($e['list_email']));

    if (empty($web_url) && empty($social_urls) && empty($email_lists)) {
        return '';
    }

    ob_start();
?>
    <div id="owc-chronicle-links" class="owc-chronicle-links">
        <h2><?php esc_html_e('Links & Resources', 'owbn-client'); ?></h2>
        <?php if ($web_url) : ?>
            <div class="owc-link-item"><a href="<?php echo esc_url($web_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($web_url); ?></a></div>
        <?php endif; ?>
        <?php if (!empty($social_urls)) : ?>
            <?php echo owc_render_social_links($social_urls); ?>
        <?php endif; ?>
        <?php if (!empty($email_lists)) : ?>
            <?php echo owc_render_email_lists($email_lists); ?>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render Documents section.
 */
function owc_render_chronicle_documents(array $chronicle): string
{
    $docs = array_filter($chronicle['document_links'] ?? [], fn($d) => !empty($d['link']));

    if (empty($docs)) {
        return '';
    }

    ob_start();
?>
    <div id="owc-chronicle-documents" class="owc-chronicle-documents">
        <h2><?php esc_html_e('Documents', 'owbn-client'); ?></h2>
        <?php echo owc_render_document_links($docs); ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render Player Lists section.
 */
function owc_render_chronicle_player_lists(array $chronicle): string
{
    $lists = $chronicle['player_lists'] ?? [];
    $lists = array_filter($lists, fn($l) => !empty($l['list_name']));

    if (empty($lists)) {
        return '';
    }

    ob_start();
?>
    <div id="owc-chronicle-player-lists" class="owc-chronicle-player-lists">
        <h2><?php esc_html_e('Player Lists', 'owbn-client'); ?></h2>
        <?php echo owc_render_player_lists($lists); ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render satellite parent link.
 */
function owc_render_satellite_parent(array $chronicle): string
{
    if (empty($chronicle['chronicle_satellite']) || $chronicle['chronicle_satellite'] === '0') {
        return '';
    }

    $parent_slug = $chronicle['chronicle_parent'] ?? '';
    $parent_title = $chronicle['chronicle_parent_title'] ?? '';

    if (empty($parent_slug) || empty($parent_title)) {
        return '';
    }

    $detail_page_id = get_option(owc_option_name('chronicles_detail_page'), 0);
    $base_url = $detail_page_id ? get_permalink($detail_page_id) : home_url('/chronicle-detail/');
    $parent_url = add_query_arg('slug', $parent_slug, $base_url);

    ob_start();
?>
    <div id="owc-satellite-parent" class="owc-satellite-parent">
        <strong><?php esc_html_e('Satellite Parent:', 'owbn-client'); ?></strong>
        <a href="<?php echo esc_url($parent_url); ?>"><?php echo esc_html($parent_title); ?></a>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render chronicle territories section.
 */
function owc_render_chronicle_territories(array $chronicle): string
{
    $slug = $chronicle['slug'] ?? '';
    if (empty($slug)) {
        return '';
    }

    $territories = owc_fetch_territories_by_slug($slug);

    if (empty($territories) || isset($territories['error'])) {
        return '';
    }

    ob_start();
?>
    <div id="owc-chronicle-territories" class="owc-chronicle-territories">
        <h2><?php esc_html_e('Territories', 'owbn-client'); ?></h2>
        <?php echo owc_render_territory_box($territories, 'chronicle', $chronicle['slug'] ?? ''); ?>
    </div>
<?php
    return ob_get_clean();
}
