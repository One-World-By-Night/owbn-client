<?php

/**
 * OWBN-Client Vote History Render
 * location: includes/render/render-vote-history.php
 *
 * Table styling mirrors wp-voting-plugin's wpvp-vote-table so vote history
 * looks consistent whether viewed on council (producer) or chronicles (consumer).
 *
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

/**
 * Render vote history section for a chronicle or coordinator detail page.
 *
 * Fetches vote data via owc_get_entity_votes() (which handles local/remote
 * routing and caching) and renders a table of public-safe vote summaries.
 *
 * @param string $entity_type 'chronicle' or 'coordinator'.
 * @param string $entity_slug The entity slug.
 * @return string HTML output, or empty string if disabled/no data.
 */
function owc_render_entity_vote_history($entity_type, $entity_slug)
{
    if (empty($entity_slug)) {
        return '';
    }

    $data = owc_get_entity_votes($entity_type, $entity_slug);

    if (is_wp_error($data) || empty($data['votes'])) {
        return '';
    }

    $votes           = $data['votes'];
    $vote_record_url = !empty($data['vote_record_url']) ? $data['vote_record_url'] : '';

    ob_start();
?>
    <div id="owc-vote-history" class="owc-vote-history">
        <h2><?php esc_html_e('Vote History', 'owbn-client'); ?></h2>
        <table class="owc-vote-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Title', 'owbn-client'); ?></th>
                    <th><?php esc_html_e('Start Date', 'owbn-client'); ?></th>
                    <th><?php esc_html_e('End Date', 'owbn-client'); ?></th>
                    <th><?php esc_html_e('Status', 'owbn-client'); ?></th>
                    <th><?php esc_html_e('Vote', 'owbn-client'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($votes as $vote) : ?>
                    <tr>
                        <td class="owc-vote-table__title" data-label="<?php esc_attr_e('Title', 'owbn-client'); ?>">
                            <?php if (!empty($vote['vote_url'])) : ?>
                                <a href="<?php echo esc_url($vote['vote_url']); ?>" target="_blank" rel="noopener" class="owc-vote-table__link"><?php echo esc_html($vote['title']); ?></a>
                            <?php else : ?>
                                <?php echo esc_html($vote['title']); ?>
                            <?php endif; ?>
                        </td>
                        <td class="owc-vote-table__date" data-label="<?php esc_attr_e('Start', 'owbn-client'); ?>">
                            <?php echo esc_html(owc_format_vote_date($vote['open_date'])); ?>
                        </td>
                        <td class="owc-vote-table__date" data-label="<?php esc_attr_e('End', 'owbn-client'); ?>">
                            <?php echo esc_html(owc_format_vote_date($vote['close_date'])); ?>
                        </td>
                        <td class="owc-vote-table__status" data-label="<?php esc_attr_e('Status', 'owbn-client'); ?>">
                            <?php echo owc_render_vote_stage_badge($vote['stage']); ?>
                        </td>
                        <td class="owc-vote-table__choice" data-label="<?php esc_attr_e('Vote', 'owbn-client'); ?>">
                            <?php echo esc_html($vote['choice']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($vote_record_url) : ?>
            <p class="owc-vote-history-footer">
                <?php
                printf(
                    /* translators: %1$s is opening <a> tag, %2$s is closing </a> tag */
                    esc_html__('For additional vote-specific records, %1$sclick here%2$s.', 'owbn-client'),
                    '<a href="' . esc_url($vote_record_url) . '" target="_blank" rel="noopener">',
                    '</a>'
                );
                ?>
            </p>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Format a single vote date for display.
 *
 * Uses the WordPress site date format for consistency.
 *
 * @param string $date Date string (Y-m-d H:i:s format).
 * @return string Formatted date or em-dash if empty.
 */
function owc_format_vote_date($date)
{
    if (empty($date)) {
        return "\xE2\x80\x94"; // em-dash
    }

    $date_format = get_option('date_format', 'M j, Y');
    return date_i18n($date_format, strtotime($date));
}

/**
 * Render a vote stage badge.
 *
 * Badge styling matches wp-voting-plugin's wpvp-badge pattern.
 *
 * @param string $stage Vote stage (open, completed, archived).
 * @return string HTML badge span.
 */
function owc_render_vote_stage_badge($stage)
{
    $labels = array(
        'open'      => __('Open', 'owbn-client'),
        'completed' => __('Completed', 'owbn-client'),
        'archived'  => __('Archived', 'owbn-client'),
    );

    $label = isset($labels[$stage]) ? $labels[$stage] : ucfirst($stage);
    $class = 'owc-badge owc-badge--' . sanitize_html_class($stage);

    return '<span class="' . esc_attr($class) . '">' . esc_html($label) . '</span>';
}
