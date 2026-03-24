<?php

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class OWC_OAT_Registry_Widget extends Widget_Base {

	public function get_name() {
		return 'owc_oat_registry';
	}

	public function get_title() {
		return __( 'OAT Registry', 'owbn-client' );
	}

	public function get_icon() {
		return 'eicon-database';
	}

	public function get_categories() {
		return array( 'owbn-oat' );
	}

	public function get_keywords() {
		return array( 'oat', 'registry', 'characters', 'owbn' );
	}

	public function get_style_depends() {
		return array( 'owc-oat-client', 'owc-oat-frontend' );
	}

	public function get_script_depends() {
		return array( 'owc-oat-frontend' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'content_section', array(
			'label' => __( 'Settings', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'character_detail_url', array(
			'label'   => __( 'Character Detail Base URL', 'owbn-client' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '',
			'description' => __( 'Leave blank to link to wp-admin character detail.', 'owbn-client' ),
		) );

		$this->add_control( 'show_search', array(
			'label'   => __( 'Show Search', 'owbn-client' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		) );

		$this->end_controls_section();

		$this->start_controls_section( 'style_section', array(
			'label' => __( 'Table', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'header_bg', array(
			'label'     => __( 'Section Header Background', 'owbn-client' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-registry-section-header' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'row_hover', array(
			'label'     => __( 'Row Hover Background', 'owbn-client' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-registry-table tbody tr:hover' => 'background-color: {{VALUE}};',
			),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		if ( ! is_user_logged_in() ) {
			echo '<p class="oat-login-prompt">' . esc_html__( 'Please log in to view the registry.', 'owbn-client' ) . '</p>';
			return;
		}

		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<div style="padding:20px;border:1px dashed #ccc;text-align:center;color:#646970;">'
				. esc_html__( 'OAT Registry — preview not available in editor.', 'owbn-client' )
				. '</div>';
			return;
		}

		if ( ! function_exists( 'owc_oat_get_registry' ) ) {
			return;
		}

		$settings    = $this->get_settings_for_display();
		$show_search = ( $settings['show_search'] ?? 'yes' ) === 'yes';
		$detail_base = $settings['character_detail_url'] ?: '/oat-registry-detail/';

		$args   = array();
		$result = owc_oat_get_registry( $args );

		if ( is_wp_error( $result ) ) {
			echo '<div class="oat-registry-error">' . esc_html( $result->get_error_message() ) . '</div>';
			return;
		}

		$characters = isset( $result['characters'] ) ? $result['characters'] : array();
		$user_id    = get_current_user_id();

		$characters = array_map( function( $c ) use ( $user_id ) {
			if ( is_object( $c ) ) {
				$c = (array) $c;
			}
			if ( ! isset( $c['is_owner'] ) ) {
				$is_npc = isset( $c['pc_npc'] ) && $c['pc_npc'] === 'npc';
				$c['is_owner'] = ! $is_npc && isset( $c['wp_user_id'] ) && (int) $c['wp_user_id'] === $user_id;
			}
			if ( isset( $c['entry_counts'] ) && is_object( $c['entry_counts'] ) ) {
				$c['entry_counts'] = (array) $c['entry_counts'];
			}
			return $c;
		}, $characters );

		if ( ! function_exists( 'owc_oat_build_registry_sections' ) ) {
			require_once dirname( __DIR__ ) . '/pages/registry.php';
		}

		$sections    = owc_oat_build_registry_sections( $characters );
		$total_count = count( $characters );

		?>
		<style>
		.oat-registry-section-body.oat-collapsed { display: none; }
		.oat-registry-section-header { user-select: none; }
		.oat-registry-section-header::before { content: '\25BC'; margin-right: 8px; font-size: 0.8em; }
		.oat-registry-section-header.collapsed::before { content: '\25B6'; }
		</style>
		<div class="oat-registry-widget">
			<div class="oat-registry-header">
				<h3><?php printf( esc_html__( 'Registry (%d characters)', 'owbn-client' ), $total_count ); ?></h3>
				<?php if ( $show_search ) : ?>
					<input type="text" class="oat-registry-search" placeholder="<?php esc_attr_e( 'Search characters...', 'owbn-client' ); ?>">
				<?php endif; ?>
			</div>

			<?php if ( empty( $sections ) ) : ?>
				<p><?php esc_html_e( 'No characters found.', 'owbn-client' ); ?></p>
			<?php endif; ?>

			<?php foreach ( $sections as $section ) : ?>
				<div class="oat-registry-section">
					<div class="oat-registry-section-header collapsed" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('oat-collapsed');" style="cursor:pointer;padding:8px 12px;margin-top:12px;border:1px solid #ddd;border-radius:4px;background:#f7f7f7;">
						<strong><?php echo esc_html( $section['label'] ); ?></strong>
						<span style="float:right;"><?php echo count( $section['characters'] ); ?></span>
					</div>
					<div class="oat-registry-section-body oat-collapsed">
						<table class="oat-registry-table" style="width:100%;border-collapse:collapse;">
							<thead>
								<tr>
									<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;">Character</th>
									<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;">Chronicle</th>
									<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;">Type</th>
									<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;">PC/NPC</th>
									<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;">Status</th>
									<th style="text-align:center;padding:6px 8px;border-bottom:2px solid #ddd;">Entries</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $section['characters'] as $char ) :
									$counts = isset( $char['entry_counts'] ) && is_array( $char['entry_counts'] ) ? $char['entry_counts'] : array();
									$entry_total = array_sum( $counts );
									$char_id = $char['id'] ?? 0;
									$char_url = $detail_base
										? $detail_base . '?character_id=' . $char_id
										: admin_url( 'admin.php?page=owc-oat-registry-character&character_id=' . $char_id );
								?>
								<tr class="oat-registry-row" data-name="<?php echo esc_attr( strtolower( $char['character_name'] ?? '' ) ); ?>" data-chronicle="<?php echo esc_attr( strtolower( $char['chronicle_slug'] ?? '' ) ); ?>">
									<td style="padding:6px 8px;border-bottom:1px solid #eee;">
										<a href="<?php echo esc_url( $char_url ); ?>"><?php echo esc_html( $char['character_name'] ?? '(unnamed)' ); ?></a>
									</td>
									<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo esc_html( strtoupper( $char['chronicle_slug'] ?? '' ) ); ?></td>
									<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo esc_html( $char['creature_type'] ?? '' ); ?></td>
									<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo esc_html( strtoupper( $char['pc_npc'] ?? '' ) ); ?></td>
									<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo esc_html( ucfirst( $char['status'] ?? '' ) ); ?></td>
									<td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:center;"><?php echo (int) $entry_total; ?></td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<script>
		(function(){
			var search = document.querySelector('.oat-registry-search');
			if (!search) return;
			search.addEventListener('input', function() {
				var term = this.value.toLowerCase();
				document.querySelectorAll('.oat-registry-row').forEach(function(row) {
					var name = row.getAttribute('data-name') || '';
					var chron = row.getAttribute('data-chronicle') || '';
					row.style.display = (name.indexOf(term) !== -1 || chron.indexOf(term) !== -1) ? '' : 'none';
				});
			});
		})();
		</script>
		<?php
	}
}
