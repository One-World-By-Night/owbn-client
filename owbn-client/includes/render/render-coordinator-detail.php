<?php

/**
 * OWBN-Client Coordinator Detail Render
 * location : includes/render/render-coordinator-detail.php
 * @package OWBN-Client
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Render coordinator detail.
 */
function owc_render_coordinator_detail(array $coordinator): string
{
    if (empty($coordinator) || isset($coordinator['error'])) {
        return '<p class="owc-error">' . esc_html($coordinator['error'] ?? __('Coordinator not found.', 'owbn-client')) . '</p>';
    }

    $back_url = home_url('/' . owc_get_coordinators_slug() . '/');
    $has_documents = !empty(array_filter($coordinator['document_links'] ?? [], fn($d) => !empty($d['url'])));

    ob_start();
?>
    <div id="owc-coordinator-detail" class="owc-coordinator-detail">

        <div id="owc-back-link" class="owc-back-link">
            <a href="<?php echo esc_url($back_url); ?>"><?php esc_html_e('← Back to Coordinators', 'owbn-client'); ?></a>
        </div>

        <?php echo owc_render_coordinator_header($coordinator); ?>
        <?php echo owc_render_coordinator_description($coordinator); ?>

        <div class="owc-coord-row <?php echo $has_documents ? '' : 'owc-no-sidebar'; ?>">
            <div class="owc-coord-main">
                <?php echo owc_render_coordinator_info($coordinator); ?>
                <?php echo owc_render_coordinator_subcoords($coordinator); ?>
            </div>
            <?php if ($has_documents) : ?>
                <div class="owc-coord-sidebar">
                    <?php echo owc_render_coordinator_documents($coordinator); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="owc-coord-row">
            <div class="owc-coord-main">
                <?php echo owc_render_coordinator_player_lists($coordinator); ?>
            </div>
            <div class="owc-coord-sidebar">
                <?php echo owc_render_coordinator_contact_lists($coordinator); ?>
            </div>
        </div>

        <?php echo owc_render_coordinator_territories($coordinator); ?>

    </div>
<?php
    return ob_get_clean();
}

/**
 * Render coordinator header (title only).
 */
function owc_render_coordinator_header(array $coordinator): string
{
    $title = $coordinator['title'] ?? $coordinator['coordinator_title'] ?? '';

    ob_start();
?>
    <div class="owc-coordinator-header">
        <h1 class="owc-coordinator-title"><?php echo esc_html($title); ?></h1>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render office description (no header).
 */
function owc_render_coordinator_description(array $coordinator): string
{
    $description = $coordinator['office_description'] ?? '';
    if (empty(trim($description))) {
        return '';
    }

    ob_start();
?>
    <div class="owc-coordinator-description">
        <div class="owc-content"><?php echo wp_kses_post($description); ?></div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render coordinator info (Name | Email).
 */
function owc_render_coordinator_info(array $coordinator): string
{
    $coord_info = $coordinator['coord_info'] ?? [];
    $name = $coord_info['display_name'] ?? '';
    $email = $coord_info['display_email'] ?? '';

    if (empty($name) && empty($email)) {
        return '';
    }

    ob_start();
?>
    <div class="owc-coordinator-info">
        <h3><?php esc_html_e('Coordinator', 'owbn-client'); ?></h3>
        <div class="owc-inline-table">
            <div class="owc-inline-row">
                <span class="owc-inline-name"><?php echo esc_html($name); ?></span>
                <?php if ($email) : ?>
                    <a class="owc-inline-email" href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render sub-coordinators (Name | Role | Contact) - no header.
 */
function owc_render_coordinator_subcoords(array $coordinator): string
{
    $subcoords = $coordinator['subcoord_list'] ?? [];
    $subcoords = array_filter($subcoords, fn($s) => !empty($s['display_name']));

    if (empty($subcoords)) {
        return '';
    }

    ob_start();
?>
    <div class="owc-coordinator-subcoords">
        <h3><?php esc_html_e('Subcoordinators', 'owbn-client'); ?></h3>
        <div class="owc-inline-table">
            <div class="owc-inline-row owc-inline-header">
                <span class="owc-inline-name"><?php esc_html_e('Name', 'owbn-client'); ?></span>
                <span class="owc-inline-role"><?php esc_html_e('Role', 'owbn-client'); ?></span>
                <span class="owc-inline-email"><?php esc_html_e('Contact', 'owbn-client'); ?></span>
            </div>
            <?php foreach ($subcoords as $subcoord) : ?>
                <div class="owc-inline-row">
                    <span class="owc-inline-name"><?php echo esc_html($subcoord['display_name']); ?></span>
                    <span class="owc-inline-role"><?php echo esc_html($subcoord['role'] ?? ''); ?></span>
                    <span class="owc-inline-email">
                        <?php if (!empty($subcoord['display_email'])) : ?>
                            <a href="mailto:<?php echo esc_attr($subcoord['display_email']); ?>"><?php echo esc_html($subcoord['display_email']); ?></a>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render genre documents sidebar.
 */
function owc_render_coordinator_documents(array $coordinator): string
{
    $documents = $coordinator['document_links'] ?? [];
    $documents = array_filter($documents, fn($d) => !empty($d['url']));

    if (empty($documents)) {
        return '';
    }

    $is_logged_in = is_user_logged_in();

    ob_start();
?>
    <div class="owc-coordinator-documents owc-info-box">
        <h3><?php esc_html_e('Genre Documents', 'owbn-client'); ?></h3>
        <?php foreach ($documents as $doc) : ?>
            <div class="owc-document-item">
                <?php if ($is_logged_in) : ?>
                    <a href="<?php echo esc_url($doc['url']); ?>" target="_blank" rel="noopener"><?php echo esc_html($doc['title'] ?: $doc['url']); ?></a>
                <?php else : ?>
                    <?php echo esc_html($doc['title'] ?: __('Document', 'owbn-client')); ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (!$is_logged_in) : ?>
            <p class="owc-auth-notice"><?php esc_html_e('Downloads only available to authenticated users.', 'owbn-client'); ?></p>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render player lists (Name | Access | IC/OOC | Moderator | Link).
 */
function owc_render_coordinator_player_lists(array $coordinator): string
{
    $lists = $coordinator['player_lists'] ?? [];
    $lists = array_filter($lists, fn($l) => !empty($l['list_name']));

    if (empty($lists)) {
        return '';
    }

    ob_start();
?>
    <div class="owc-coordinator-player-lists">
        <h3><?php esc_html_e('Player Lists', 'owbn-client'); ?></h3>
        <div class="owc-player-list-table">
            <div class="owc-player-list-row owc-player-list-header">
                <span class="owc-pl-name"><?php esc_html_e('Name', 'owbn-client'); ?></span>
                <span class="owc-pl-access"><?php esc_html_e('Access', 'owbn-client'); ?></span>
                <span class="owc-pl-type"><?php esc_html_e('IC/OOC', 'owbn-client'); ?></span>
                <span class="owc-pl-moderator"><?php esc_html_e('Moderator', 'owbn-client'); ?></span>
                <span class="owc-pl-link"><?php esc_html_e('Link', 'owbn-client'); ?></span>
            </div>
            <?php foreach ($lists as $list) : ?>
                <div class="owc-player-list-row">
                    <span class="owc-pl-name">
                        <?php if (!empty($list['address'])) : ?>
                            <a href="mailto:<?php echo esc_attr($list['address']); ?>"><?php echo esc_html($list['list_name']); ?></a>
                        <?php else : ?>
                            <?php echo esc_html($list['list_name']); ?>
                        <?php endif; ?>
                    </span>
                    <span class="owc-pl-access"><?php echo esc_html($list['access'] ?? ''); ?></span>
                    <span class="owc-pl-type"><?php echo esc_html($list['ic_ooc'] ?? ''); ?></span>
                    <span class="owc-pl-moderator">
                        <?php if (!empty($list['moderate_address'])) : ?>
                            <a href="mailto:<?php echo esc_attr($list['moderate_address']); ?>"><?php echo esc_html($list['moderate_address']); ?></a>
                        <?php endif; ?>
                    </span>
                    <span class="owc-pl-link">
                        <?php if (!empty($list['signup_url'])) : ?>
                            <a href="<?php echo esc_url($list['signup_url']); ?>" target="_blank" rel="noopener"><?php esc_html_e('Link', 'owbn-client'); ?></a>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render contact lists (email lists).
 */
function owc_render_coordinator_contact_lists(array $coordinator): string
{
    $lists = $coordinator['email_lists'] ?? [];
    $lists = array_filter($lists, fn($l) => !empty($l['list_name']) || !empty($l['email_address']));

    if (empty($lists)) {
        return '';
    }

    ob_start();
?>
    <div class="owc-coordinator-contact-lists owc-info-box">
        <h3><?php esc_html_e('Contact Lists', 'owbn-client'); ?></h3>
        <?php foreach ($lists as $list) : ?>
            <div class="owc-contact-item">
                <?php if (!empty($list['email_address'])) : ?>
                    <a href="mailto:<?php echo esc_attr($list['email_address']); ?>"><?php echo esc_html($list['list_name'] ?: $list['email_address']); ?></a>
                <?php else : ?>
                    <?php echo esc_html($list['list_name']); ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render coordinator territories section.
 */
function owc_render_coordinator_territories(array $coordinator): string
{
    $slug = $coordinator['slug'] ?? '';
    if (empty($slug)) {
        return '';
    }

    $territories = owc_fetch_territories_by_slug($slug);

    if (empty($territories) || isset($territories['error'])) {
        return '';
    }

    ob_start();
?>
    <div id="owc-coordinator-territories" class="owc-coordinator-territories">
        <h2><?php esc_html_e('Territories', 'owbn-client'); ?></h2>
        <?php echo owc_render_territory_box($territories, 'coordinator', $coordinator['slug'] ?? ''); ?>
    </div>
<?php
    return ob_get_clean();
}
