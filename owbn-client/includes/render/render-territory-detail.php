<?php

/**
 * OWBN-Client Render Territory Detail
 * location: includes/render/render-territory-detail.php
 * Full detail view for single territory.
 * 
 * @package OWBN-Client
 * @version 2.1.1
 */

defined('ABSPATH') || exit;

/**
 * Render territory detail view.
 *
 * @param array  $territory Territory data
 * @param string $context   'chronicle'|'coordinator'|'' for slug linking
 * @return string HTML
 */
function owc_render_territory_detail(array $territory, string $context = ''): string
{
    if (isset($territory['error'])) {
        return '<div class="owc-error">' . esc_html($territory['error']) . '</div>';
    }

    $title       = $territory['title'] ?? '';
    $countries   = $territory['countries'] ?? [];
    $region      = $territory['region'] ?? '';
    $location    = $territory['location'] ?? '';
    $detail      = $territory['detail'] ?? '';
    $description = $territory['description'] ?? '';
    $owner       = $territory['owner'] ?? '';
    $slugs       = $territory['slugs'] ?? [];
    $id          = $territory['id'] ?? 0;

    if (empty($title)) {
        return '<div class="owc-error">' . esc_html__('Territory not found.', 'owbn-client') . '</div>';
    }

    ob_start();
?>
    <div class="owc-territory-detail" data-id="<?php echo esc_attr($id); ?>">
        <h2 class="owc-territory-title"><?php echo esc_html($title); ?></h2>

        <div class="owc-territory-meta">
            <?php if (!empty($countries)) : ?>
                <div class="owc-territory-row">
                    <span class="owc-label"><?php esc_html_e('Country', 'owbn-client'); ?></span>
                    <span class="owc-value"><?php echo esc_html(owc_render_territory_countries($countries)); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($region) : ?>
                <div class="owc-territory-row">
                    <span class="owc-label"><?php esc_html_e('Region', 'owbn-client'); ?></span>
                    <span class="owc-value"><?php echo esc_html($region); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($location) : ?>
                <div class="owc-territory-row">
                    <span class="owc-label"><?php esc_html_e('Location', 'owbn-client'); ?></span>
                    <span class="owc-value"><?php echo esc_html($location); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($detail) : ?>
                <div class="owc-territory-row">
                    <span class="owc-label"><?php esc_html_e('Detail', 'owbn-client'); ?></span>
                    <span class="owc-value"><?php echo esc_html($detail); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($owner) : ?>
                <div class="owc-territory-row">
                    <span class="owc-label"><?php esc_html_e('Owner', 'owbn-client'); ?></span>
                    <span class="owc-value"><?php echo esc_html($owner); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($slugs)) : ?>
                <div class="owc-territory-row">
                    <span class="owc-label"><?php esc_html_e('Assigned To', 'owbn-client'); ?></span>
                    <span class="owc-value"><?php echo owc_render_territory_slugs($slugs, $context); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($description) : ?>
            <div class="owc-territory-description">
                <h3><?php esc_html_e('Description & Approval Parameters', 'owbn-client'); ?></h3>
                <div class="owc-content"><?php echo wp_kses_post(wpautop($description)); ?></div>
            </div>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}
