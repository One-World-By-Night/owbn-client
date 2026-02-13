<?php

/**
 * Elementor Migration Helper
 *
 * Scans for pages using old shortcodes and migrates them to Elementor widgets.
 *
 * location: includes/admin/migration-helper.php
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

/**
 * Render migration helper admin page.
 */
function owc_render_migration_page()
{
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have permission to access this page.', 'owbn-client'));
	}

	// Check if Elementor is active
	$elementor_active = did_action('elementor/loaded');

	?>
	<div class="wrap">
		<h1><?php esc_html_e('Migrate to Elementor', 'owbn-client'); ?></h1>

		<?php if (!$elementor_active) : ?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e('Elementor Not Active', 'owbn-client'); ?></strong><br>
					<?php esc_html_e('Elementor must be installed and activated before migrating pages.', 'owbn-client'); ?>
				</p>
			</div>
		<?php else : ?>
			<p><?php esc_html_e('This tool scans your site for pages using old shortcodes and helps you migrate them to Elementor widgets.', 'owbn-client'); ?></p>

			<?php
			// Handle migration actions
			if (isset($_POST['owc_migrate_action'], $_POST['owc_migrate_nonce'])) {
				check_admin_referer('owc_migrate_pages', 'owc_migrate_nonce');

				if ($_POST['owc_migrate_action'] === 'migrate_single') {
					$page_id = isset($_POST['page_id']) ? absint($_POST['page_id']) : 0;
					if ($page_id) {
						$result = owc_migrate_single_page($page_id);
						if ($result['success']) {
							echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
						} else {
							echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
						}
					}
				} elseif ($_POST['owc_migrate_action'] === 'migrate_bulk') {
					$page_ids = isset($_POST['page_ids']) ? array_map('absint', (array) $_POST['page_ids']) : [];
					$results = owc_migrate_bulk_pages($page_ids);

					echo '<div class="notice notice-success"><p>';
					echo esc_html(
						sprintf(
							__('Migrated %d of %d pages successfully.', 'owbn-client'),
							$results['success_count'],
							$results['total_count']
						)
					);
					echo '</p></div>';

					if (!empty($results['errors'])) {
						echo '<div class="notice notice-warning"><p><strong>' . esc_html__('Errors:', 'owbn-client') . '</strong></p><ul>';
						foreach ($results['errors'] as $error) {
							echo '<li>' . esc_html($error) . '</li>';
						}
						echo '</ul></div>';
					}
				}
			}

			// Scan for pages with shortcodes
			$pages_to_migrate = owc_scan_pages_for_migration();

			if (empty($pages_to_migrate)) {
				echo '<div class="notice notice-info"><p>';
				esc_html_e('No pages found with old shortcodes. All pages have been migrated or use Elementor widgets.', 'owbn-client');
				echo '</p></div>';
			} else {
				?>
				<h2><?php esc_html_e('Pages Ready for Migration', 'owbn-client'); ?></h2>
				<p><?php echo esc_html(sprintf(__('Found %d page(s) using old shortcodes:', 'owbn-client'), count($pages_to_migrate))); ?></p>

				<form method="post">
					<?php wp_nonce_field('owc_migrate_pages', 'owc_migrate_nonce'); ?>
					<input type="hidden" name="owc_migrate_action" value="migrate_bulk">

					<table class="widefat striped">
						<thead>
							<tr>
								<th style="width: 40px;"><input type="checkbox" id="owc-select-all"></th>
								<th><?php esc_html_e('Page', 'owbn-client'); ?></th>
								<th><?php esc_html_e('Current Shortcodes', 'owbn-client'); ?></th>
								<th><?php esc_html_e('Will Become', 'owbn-client'); ?></th>
								<th><?php esc_html_e('Actions', 'owbn-client'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($pages_to_migrate as $page) : ?>
								<tr>
									<td>
										<input type="checkbox" name="page_ids[]" value="<?php echo esc_attr($page['id']); ?>" class="owc-page-checkbox">
									</td>
									<td>
										<strong><?php echo esc_html($page['title']); ?></strong><br>
										<small><a href="<?php echo esc_url(get_permalink($page['id'])); ?>" target="_blank"><?php esc_html_e('View', 'owbn-client'); ?></a> |
										<a href="<?php echo esc_url(get_edit_post_link($page['id'])); ?>" target="_blank"><?php esc_html_e('Edit', 'owbn-client'); ?></a></small>
									</td>
									<td>
										<?php foreach ($page['shortcodes'] as $sc) : ?>
											<code><?php echo esc_html($sc); ?></code><br>
										<?php endforeach; ?>
									</td>
									<td>
										<?php foreach ($page['widgets'] as $widget) : ?>
											<span class="dashicons dashicons-admin-generic"></span> <?php echo esc_html($widget); ?><br>
										<?php endforeach; ?>
									</td>
									<td>
										<form method="post" style="display: inline;">
											<?php wp_nonce_field('owc_migrate_pages', 'owc_migrate_nonce'); ?>
											<input type="hidden" name="owc_migrate_action" value="migrate_single">
											<input type="hidden" name="page_id" value="<?php echo esc_attr($page['id']); ?>">
											<button type="submit" class="button button-primary"><?php esc_html_e('Migrate Now', 'owbn-client'); ?></button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<p class="submit">
						<button type="submit" class="button button-primary button-large" id="owc-bulk-migrate">
							<?php esc_html_e('Migrate Selected Pages', 'owbn-client'); ?>
						</button>
					</p>
				</form>

				<script>
				jQuery(document).ready(function($) {
					// Select all checkbox
					$('#owc-select-all').on('change', function() {
						$('.owc-page-checkbox').prop('checked', $(this).prop('checked'));
					});

					// Confirm bulk migration
					$('#owc-bulk-migrate').on('click', function(e) {
						var checked = $('.owc-page-checkbox:checked').length;
						if (checked === 0) {
							e.preventDefault();
							alert('<?php esc_html_e('Please select at least one page to migrate.', 'owbn-client'); ?>');
							return false;
						}
						if (!confirm('<?php esc_html_e('Are you sure you want to migrate the selected pages? A revision will be created for each page before migration.', 'owbn-client'); ?>')) {
							e.preventDefault();
							return false;
						}
					});
				});
				</script>
				<?php
			}
			?>

			<hr>

			<h2><?php esc_html_e('Migration Information', 'owbn-client'); ?></h2>
			<div class="notice notice-info inline">
				<p><strong><?php esc_html_e('What happens during migration:', 'owbn-client'); ?></strong></p>
				<ul style="margin-left: 20px;">
					<li><?php esc_html_e('A revision is created for each page (rollback safety)', 'owbn-client'); ?></li>
					<li><?php esc_html_e('Old shortcodes are detected and mapped to appropriate Elementor widgets', 'owbn-client'); ?></li>
					<li><?php esc_html_e('Elementor page data is built programmatically with default styling', 'owbn-client'); ?></li>
					<li><?php esc_html_e('The page is marked as "Built with Elementor"', 'owbn-client'); ?></li>
					<li><?php esc_html_e('You can customize widget styling in the Elementor editor after migration', 'owbn-client'); ?></li>
				</ul>
			</div>

			<div class="notice notice-warning inline">
				<p><strong><?php esc_html_e('Important Notes:', 'owbn-client'); ?></strong></p>
				<ul style="margin-left: 20px;">
					<li><?php esc_html_e('This migration is safe but irreversible (except via post revisions)', 'owbn-client'); ?></li>
					<li><?php esc_html_e('Field shortcodes ([owc-chronicle-field], [owc-coordinator-field]) are not migrated automatically — use Field widgets manually', 'owbn-client'); ?></li>
					<li><?php esc_html_e('Pages with mixed content (text + shortcodes) will preserve text blocks', 'owbn-client'); ?></li>
					<li><?php esc_html_e('You can re-run this tool — already-migrated pages won\'t appear in the list', 'owbn-client'); ?></li>
				</ul>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Scan all pages for old shortcodes.
 *
 * @return array Array of page data with shortcodes to migrate.
 */
function owc_scan_pages_for_migration(): array
{
	$pages_to_migrate = [];

	// Find all pages
	$pages = get_posts([
		'post_type'      => 'page',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	]);

	foreach ($pages as $page) {
		// Skip if already using Elementor
		if (get_post_meta($page->ID, '_elementor_edit_mode', true) === 'builder') {
			continue;
		}

		$content = $page->post_content;
		$shortcodes = [];
		$widgets = [];

		// Check for main owc-client shortcode
		if (preg_match_all('/\[owc-client\s+type="([^"]+)"[^\]]*\]/i', $content, $matches)) {
			foreach ($matches[0] as $idx => $full_match) {
				$type = $matches[1][$idx];
				$shortcodes[] = $full_match;
				$widgets[] = owc_map_shortcode_type_to_widget($type);
			}
		}

		// Check for legacy cc-client shortcode
		if (preg_match_all('/\[cc-client\s+type="([^"]+)"[^\]]*\]/i', $content, $matches)) {
			foreach ($matches[0] as $idx => $full_match) {
				$type = $matches[1][$idx];
				$shortcodes[] = $full_match;
				$widgets[] = owc_map_shortcode_type_to_widget($type);
			}
		}

		// Only include if we found shortcodes
		if (!empty($shortcodes)) {
			$pages_to_migrate[] = [
				'id'         => $page->ID,
				'title'      => $page->post_title ?: __('(no title)', 'owbn-client'),
				'shortcodes' => $shortcodes,
				'widgets'    => $widgets,
			];
		}
	}

	return $pages_to_migrate;
}

/**
 * Map shortcode type to widget name.
 */
function owc_map_shortcode_type_to_widget(string $type): string
{
	$map = [
		'chronicle-list'     => 'Chronicle List Widget',
		'coordinator-list'   => 'Coordinator List Widget',
		'territory-list'     => 'Territory List Widget',
		'chronicle-detail'   => 'Chronicle Detail Widget',
		'coordinator-detail' => 'Coordinator Detail Widget',
		'territory-detail'   => 'Territory Detail Widget',
	];

	return $map[$type] ?? 'Unknown Widget';
}

/**
 * Migrate a single page.
 *
 * @param int $page_id Page ID to migrate.
 * @return array Result with success/message.
 */
function owc_migrate_single_page(int $page_id): array
{
	// Verify Elementor is loaded
	if (!did_action('elementor/loaded')) {
		return [
			'success' => false,
			'message' => __('Elementor is not loaded.', 'owbn-client'),
		];
	}

	// Get page
	$page = get_post($page_id);
	if (!$page || $page->post_type !== 'page') {
		return [
			'success' => false,
			'message' => __('Invalid page ID.', 'owbn-client'),
		];
	}

	// Create revision
	wp_save_post_revision($page_id);

	// Build Elementor data from shortcodes
	$elementor_data = owc_build_elementor_data_from_content($page->post_content);

	if (empty($elementor_data)) {
		return [
			'success' => false,
			'message' => __('No shortcodes found to migrate.', 'owbn-client'),
		];
	}

	// Update page meta
	update_post_meta($page_id, '_elementor_edit_mode', 'builder');
	update_post_meta($page_id, '_elementor_data', wp_slash(wp_json_encode($elementor_data)));
	update_post_meta($page_id, '_elementor_page_settings', wp_slash(wp_json_encode([])));
	update_post_meta($page_id, '_elementor_version', ELEMENTOR_VERSION);

	// Clear Elementor cache
	if (class_exists('\Elementor\Plugin')) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	return [
		'success' => true,
		'message' => sprintf(
			__('Successfully migrated "%s" to Elementor.', 'owbn-client'),
			$page->post_title
		),
	];
}

/**
 * Migrate multiple pages in bulk.
 *
 * @param array $page_ids Array of page IDs.
 * @return array Results with counts and errors.
 */
function owc_migrate_bulk_pages(array $page_ids): array
{
	$results = [
		'total_count'   => count($page_ids),
		'success_count' => 0,
		'errors'        => [],
	];

	foreach ($page_ids as $page_id) {
		$result = owc_migrate_single_page($page_id);
		if ($result['success']) {
			$results['success_count']++;
		} else {
			$results['errors'][] = sprintf(
				__('Page #%d: %s', 'owbn-client'),
				$page_id,
				$result['message']
			);
		}
	}

	return $results;
}

/**
 * Build Elementor data structure from page content.
 *
 * @param string $content Page content with shortcodes.
 * @return array Elementor data structure.
 */
function owc_build_elementor_data_from_content(string $content): array
{
	$elements = [];

	// Parse shortcodes and build widgets
	// Pattern: [owc-client type="chronicle-list"] or [cc-client type="chronicle-list"]
	if (preg_match_all('/\[(owc-client|cc-client)\s+([^\]]+)\]/i', $content, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$atts_string = $match[2];
			$atts = [];

			// Parse attributes
			if (preg_match('/type="([^"]+)"/', $atts_string, $type_match)) {
				$atts['type'] = $type_match[1];
			}
			if (preg_match('/slug="([^"]+)"/', $atts_string, $slug_match)) {
				$atts['slug'] = $slug_match[1];
			}
			if (preg_match('/id="([^"]+)"/', $atts_string, $id_match)) {
				$atts['id'] = $id_match[1];
			}

			$widget = owc_create_widget_element($atts);
			if ($widget) {
				// Wrap widget in a section and column (Elementor requirement)
				$elements[] = [
					'id'       => owc_generate_element_id(),
					'elType'   => 'section',
					'settings' => [],
					'elements' => [
						[
							'id'       => owc_generate_element_id(),
							'elType'   => 'column',
							'settings' => ['_column_size' => 100],
							'elements' => [$widget],
						],
					],
				];
			}
		}
	}

	return $elements;
}

/**
 * Create widget element from shortcode attributes.
 *
 * @param array $atts Shortcode attributes.
 * @return array|null Widget element or null.
 */
function owc_create_widget_element(array $atts): ?array
{
	$type = $atts['type'] ?? '';

	$widget_map = [
		'chronicle-list'     => 'owc_chronicle_list',
		'coordinator-list'   => 'owc_coordinator_list',
		'territory-list'     => 'owc_territory_list',
		'chronicle-detail'   => 'owc_chronicle_detail',
		'coordinator-detail' => 'owc_coordinator_detail',
		'territory-detail'   => 'owc_territory_detail',
	];

	if (!isset($widget_map[$type])) {
		return null;
	}

	$widget_type = $widget_map[$type];
	$settings = ['widgetType' => $widget_type];

	// Map attributes to widget settings
	if (in_array($type, ['chronicle-detail', 'coordinator-detail'], true)) {
		// Detail widgets: can have fixed slug or dynamic from URL
		if (!empty($atts['slug'])) {
			$settings['slug_source'] = 'fixed';
			$settings['fixed_slug'] = $atts['slug'];
		} else {
			$settings['slug_source'] = 'url';
		}
	} elseif ($type === 'territory-detail') {
		// Territory detail: can have fixed ID or dynamic from URL
		if (!empty($atts['id'])) {
			$settings['id_source'] = 'fixed';
			$settings['fixed_id'] = (int) $atts['id'];
		} else {
			$settings['id_source'] = 'url';
		}
	}

	return [
		'id'       => owc_generate_element_id(),
		'elType'   => 'widget',
		'settings' => $settings,
		'elements' => [],
		'widgetType' => $widget_type,
	];
}

/**
 * Generate unique Elementor element ID.
 *
 * @return string Unique hex ID.
 */
function owc_generate_element_id(): string
{
	return dechex(mt_rand(268435456, 4294967295)); // 8-character hex
}
