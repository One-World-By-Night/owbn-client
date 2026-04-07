<?php

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class OWC_CCHub_Categories_Widget extends Widget_Base {

	public function get_name() { return 'owc_cchub_categories'; }
	public function get_title() { return __( 'ccHub Categories', 'owbn-archivist' ); }
	public function get_icon() { return 'eicon-folder-o'; }
	public function get_categories() { return array( 'owbn-oat' ); }
	public function get_keywords() { return array( 'cchub', 'custom', 'content', 'categories' ); }
	public function get_style_depends() { return array( 'owc-oat-client' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array(
			'label' => __( 'Settings', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );
		$this->add_control( 'browse_url', array(
			'label'   => __( 'Browse Page URL', 'owbn-archivist' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '/cchub/browse/',
		) );
		$this->add_control( 'show_counts', array(
			'label'   => __( 'Show Counts', 'owbn-archivist' ),
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

		// Query categories via API wrapper (supports local + remote mode).
		$categories = function_exists( 'owc_oat_get_cchub_categories' )
			? owc_oat_get_cchub_categories()
			: array();

		if ( is_wp_error( $categories ) || empty( $categories ) ) {
			echo '<p>No custom content found.</p>';
			return;
		}

		$total = 0;
		foreach ( $categories as $c ) { $total += (int) ( is_array( $c ) ? $c['count'] : $c->cnt ); }

		?>
		<div class="cchub-categories">
			<h3>Custom Content Database <?php if ( $show_counts ) : ?><span style="color:#888;">(<?php echo $total; ?> items)</span><?php endif; ?></h3>
			<ul style="list-style:none;padding:0;margin:0;">
				<?php foreach ( $categories as $cat ) :
					$cat_type  = is_array( $cat ) ? $cat['content_type'] : $cat->content_type;
					$cat_count = is_array( $cat ) ? (int) $cat['count'] : (int) $cat->cnt;
					$url = $browse_url . '?type=' . rawurlencode( $cat_type );
				?>
					<li style="padding:6px 0;border-bottom:1px solid #eee;">
						<a href="<?php echo esc_url( $url ); ?>" style="text-decoration:none;display:flex;justify-content:space-between;">
							<span><?php echo esc_html( $cat_type ); ?></span>
							<?php if ( $show_counts ) : ?>
								<span style="color:#888;">(<?php echo $cat_count; ?>)</span>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
