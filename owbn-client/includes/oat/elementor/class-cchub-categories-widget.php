<?php

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class OWC_CCHub_Categories_Widget extends Widget_Base {

	public function get_name() { return 'owc_cchub_categories'; }
	public function get_title() { return __( 'ccHub Categories', 'owbn-client' ); }
	public function get_icon() { return 'eicon-folder-o'; }
	public function get_categories() { return array( 'owbn-oat' ); }
	public function get_keywords() { return array( 'cchub', 'custom', 'content', 'categories' ); }
	public function get_style_depends() { return array( 'owc-oat-client' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array(
			'label' => __( 'Settings', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );
		$this->add_control( 'browse_url', array(
			'label'   => __( 'Browse Page URL', 'owbn-client' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '/cchub/browse/',
		) );
		$this->add_control( 'show_counts', array(
			'label'   => __( 'Show Counts', 'owbn-client' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		) );
		$this->end_controls_section();
	}

	protected function render() {
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<div style="padding:20px;border:1px dashed #ccc;text-align:center;">ccHub Categories</div>';
			return;
		}

		$settings    = $this->get_settings_for_display();
		$browse_url  = $settings['browse_url'] ?: '/cchub/browse/';
		$show_counts = ( $settings['show_counts'] ?? 'yes' ) === 'yes';

		// Query categories from oat_entries
		global $wpdb;
		$entries = $wpdb->prefix . 'oat_entries';
		$meta    = $wpdb->prefix . 'oat_entry_meta';

		$categories = $wpdb->get_results( "
			SELECT m.meta_value as content_type, COUNT(DISTINCT e.id) as cnt
			FROM {$entries} e
			JOIN {$meta} m ON e.id = m.entry_id AND m.meta_key = 'content_type'
			WHERE e.domain = 'custom_content' AND e.status = 'approved'
			AND m.meta_value != ''
			GROUP BY m.meta_value
			ORDER BY m.meta_value ASC
		" );

		if ( empty( $categories ) ) {
			echo '<p>No custom content found.</p>';
			return;
		}

		$total = 0;
		foreach ( $categories as $c ) { $total += (int) $c->cnt; }

		?>
		<div class="cchub-categories">
			<h3>Custom Content Database <?php if ( $show_counts ) : ?><span style="color:#888;">(<?php echo $total; ?> items)</span><?php endif; ?></h3>
			<ul style="list-style:none;padding:0;margin:0;">
				<?php foreach ( $categories as $cat ) :
					$url = $browse_url . '?type=' . rawurlencode( $cat->content_type );
				?>
					<li style="padding:6px 0;border-bottom:1px solid #eee;">
						<a href="<?php echo esc_url( $url ); ?>" style="text-decoration:none;display:flex;justify-content:space-between;">
							<span><?php echo esc_html( $cat->content_type ); ?></span>
							<?php if ( $show_counts ) : ?>
								<span style="color:#888;">(<?php echo (int) $cat->cnt; ?>)</span>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
