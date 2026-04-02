<?php

/**
 * OWBN-Client Coordinator Detail Render
 * location : includes/render/render-coordinator-detail.php
 */

defined('ABSPATH') || exit;

/**
 * Render coordinator detail.
 */
function owc_render_coordinator_detail(array $coordinator): string
{
    if (empty($coordinator) || isset($coordinator['error'])) {
        return '<p class="owc-error">' . esc_html($coordinator['error'] ?? __('Coordinator not found.', 'owbn-entities')) . '</p>';
    }

    $list_page_id = get_option(owc_option_name('coordinators_list_page'), 0);
    $back_url = $list_page_id ? get_permalink($list_page_id) : home_url('/');

    // Check if sidebar has any content
    $has_hosting = !empty($coordinator['hosting_chronicle']);
    $has_documents = !empty(array_filter($coordinator['document_links'] ?? [], fn($d) => !empty($d['url']) || !empty($d['link']) || !empty($d['file_id'])));
    $has_contacts = !empty(array_filter($coordinator['email_lists'] ?? [], fn($l) => !empty($l['list_name']) || !empty($l['email_address'])));
    $has_sidebar = $has_hosting || $has_documents || $has_contacts;

    ob_start();
?>
    <div id="owc-coordinator-detail" class="owc-coordinator-detail">

        <div id="owc-back-link" class="owc-back-link">
            <a href="<?php echo esc_url($back_url); ?>"><?php esc_html_e('← Back to Coordinators', 'owbn-entities'); ?></a>
        </div>

        <?php echo owc_render_coordinator_header($coordinator); ?>
        <?php echo owc_render_coordinator_description($coordinator); ?>

        <div class="owc-coord-row <?php echo $has_sidebar ? '' : 'owc-no-sidebar'; ?>">
            <div class="owc-coord-main">
                <?php echo owc_render_coordinator_info($coordinator); ?>
                <?php echo owc_render_coordinator_subcoords($coordinator); ?>
                <?php echo owc_render_coordinator_player_lists($coordinator); ?>
            </div>
            <?php if ($has_sidebar) : ?>
                <div class="owc-coord-sidebar">
                    <?php echo owc_render_coordinator_hosting_chronicle($coordinator); ?>
                    <?php echo owc_render_coordinator_documents($coordinator); ?>
                    <?php echo owc_render_coordinator_contact_lists($coordinator); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php echo owc_render_coordinator_territories($coordinator); ?>
        <?php echo owc_render_entity_vote_history('coordinator', $coordinator['slug'] ?? ''); ?>

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
    $content = $coordinator['content'] ?? '';
    $office  = $coordinator['office_description'] ?? '';

    if (empty(trim($content)) && empty(trim($office))) {
        return '';
    }

    ob_start();
?>
    <div class="owc-coordinator-description">
        <?php if (!empty(trim($content))) : ?>
            <div class="owc-content"><?php echo wp_kses_post($content); ?></div>
        <?php endif; ?>
        <?php if (!empty(trim($office))) : ?>
            <div class="owc-office-description">
                <h4><?php esc_html_e('Office Description', 'owbn-entities'); ?></h4>
                <div class="owc-content"><?php echo wp_kses_post($office); ?></div>
            </div>
        <?php endif; ?>
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
        <h3><?php esc_html_e('Coordinator', 'owbn-entities'); ?></h3>
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
        <h3><?php esc_html_e('Subcoordinators', 'owbn-entities'); ?></h3>
        <div class="owc-inline-table">
            <div class="owc-inline-row owc-inline-header">
                <span class="owc-inline-name"><?php esc_html_e('Name', 'owbn-entities'); ?></span>
                <span class="owc-inline-role"><?php esc_html_e('Role', 'owbn-entities'); ?></span>
                <span class="owc-inline-email"><?php esc_html_e('Contact', 'owbn-entities'); ?></span>
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
    // Resolve file_id to URL for uploaded documents.
    foreach ( $documents as &$d ) {
        if ( empty( $d['url'] ) && ! empty( $d['file_id'] ) ) {
            $d['url'] = wp_get_attachment_url( $d['file_id'] );
        }
        if ( empty( $d['url'] ) && ! empty( $d['link'] ) ) {
            $d['url'] = $d['link'];
        }
    }
    unset( $d );
    $documents = array_filter($documents, fn($d) => !empty($d['url']));

    if (empty($documents)) {
        return '';
    }

    $is_logged_in = is_user_logged_in();

    ob_start();
?>
    <div class="owc-coordinator-documents owc-info-box">
        <h3><?php esc_html_e('Genre Documents', 'owbn-entities'); ?></h3>
        <?php foreach ($documents as $doc) : ?>
            <div class="owc-document-item">
                <?php if ($is_logged_in) : ?>
                    <a href="<?php echo esc_url($doc['url']); ?>" target="_blank" rel="noopener"><?php echo esc_html($doc['title'] ?: $doc['url']); ?></a>
                <?php else : ?>
                    <?php echo esc_html($doc['title'] ?: __('Document', 'owbn-entities')); ?>
                <?php endif; ?>
                <?php if (!empty($doc['last_updated'])) : ?>
                    <span class="owc-document-updated">(<?php esc_html_e('Updated:', 'owbn-entities'); ?> <?php echo esc_html($doc['last_updated']); ?>)</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (!$is_logged_in) : ?>
            <p class="owc-auth-notice"><?php esc_html_e('Downloads only available to authenticated users.', 'owbn-entities'); ?></p>
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
        <h3><?php esc_html_e('Player Lists', 'owbn-entities'); ?></h3>
        <div class="owc-player-list-table">
            <div class="owc-player-list-row owc-player-list-header">
                <span class="owc-pl-name"><?php esc_html_e('Name', 'owbn-entities'); ?></span>
                <span class="owc-pl-access"><?php esc_html_e('Access', 'owbn-entities'); ?></span>
                <span class="owc-pl-type"><?php esc_html_e('IC/OOC', 'owbn-entities'); ?></span>
                <span class="owc-pl-moderator"><?php esc_html_e('Moderator', 'owbn-entities'); ?></span>
                <span class="owc-pl-link"><?php esc_html_e('Link', 'owbn-entities'); ?></span>
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
                            <a href="<?php echo esc_url($list['signup_url']); ?>" target="_blank" rel="noopener"><?php esc_html_e('Link', 'owbn-entities'); ?></a>
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
        <h3><?php esc_html_e('Contact Lists', 'owbn-entities'); ?></h3>
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
 * Render hosting chronicle link.
 */
function owc_render_coordinator_hosting_chronicle(array $coordinator): string
{
    $slug = $coordinator['hosting_chronicle'] ?? '';
    if (empty($slug)) {
        return '';
    }

    // Try to get chronicle title
    $chronicle = owc_get_chronicle_detail($slug);
    $title = is_array($chronicle) && !empty($chronicle['title']) ? $chronicle['title'] : strtoupper($slug);

    // Build URL using detail page setting
    $detail_page_id = get_option(owc_option_name('chronicles_detail_page'), 0);
    $base_url = $detail_page_id ? get_permalink($detail_page_id) : '';
    $url = $base_url ? add_query_arg('slug', $slug, $base_url) : '#';

    // Find House Rules from chronicle's document_links
    $house_rules_url = '';
    if (is_array($chronicle) && !empty($chronicle['document_links'])) {
        foreach ($chronicle['document_links'] as $doc) {
            $doc_title = $doc['title'] ?? '';
            if (stripos($doc_title, 'house rules') !== false && !empty($doc['url'])) {
                $house_rules_url = $doc['url'];
                break;
            }
        }
    }

    ob_start();
?>
    <div class="owc-coordinator-hosting-chronicle owc-info-box">
        <h3><?php esc_html_e('Hosting Chronicle', 'owbn-entities'); ?></h3>
        <div class="owc-hosting-link">
            <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
        </div>
        <?php if ($house_rules_url) : ?>
            <div class="owc-hosting-house-rules">
                <a href="<?php echo esc_url($house_rules_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('House Rules', 'owbn-entities'); ?></a>
            </div>
        <?php endif; ?>
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

    $territories = owc_fetch_territories_by_slug('coordinator/' . $slug);

    if (empty($territories) || isset($territories['error'])) {
        return '';
    }

    ob_start();
?>
    <div id="owc-coordinator-territories" class="owc-coordinator-territories">
        <h2><?php esc_html_e('Territories', 'owbn-entities'); ?></h2>
        <?php echo owc_render_territory_box($territories, 'coordinator', $coordinator['slug'] ?? ''); ?>
    </div>
<?php
    return ob_get_clean();
}
