<?php

/**
 * OAT Activity Feed Widget
 *
 * Elementor widget showing a chronological feed of recent OAT timeline events
 * visible to the current user. Supports auto-refresh.
 *
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

class OWC_OAT_Activity_Widget extends Widget_Base
{
	public function get_name()
	{
		return 'owc_oat_activity';
	}

	public function get_title()
	{
		return __( 'Archivist Activity Feed', 'owbn-archivist' );
	}

	public function get_icon()
	{
		return 'eicon-history';
	}

	public function get_categories()
	{
		return array( 'owbn-oat' );
	}

	public function get_keywords()
	{
		return array( 'oat', 'activity', 'feed', 'timeline', 'owbn', 'archivist' );
	}

	public function get_style_depends()
	{
		return array( 'owc-oat-client', 'owc-oat-frontend' );
	}

	public function get_script_depends()
	{
		return array( 'owc-oat-client', 'owc-oat-frontend' );
	}

	// ── Controls ─────────────────────────────────────────────────────────────

	protected function register_controls()
	{
		// ── Content Tab ───────────────────────────────────────────────────

		$this->start_controls_section( 'content_section', array(
			'label' => __( 'Feed Settings', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'item_count', array(
			'label'   => __( 'Number of Items', 'owbn-archivist' ),
			'type'    => Controls_Manager::NUMBER,
			'min'     => 1,
			'max'     => 50,
			'default' => 10,
		) );

		$this->add_control( 'domain_filter', array(
			'label'       => __( 'Filter by Domain', 'owbn-archivist' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => __( 'Leave blank for all domains', 'owbn-archivist' ),
			'default'     => '',
		) );

		$this->add_control( 'auto_refresh', array(
			'label'       => __( 'Auto-Refresh Interval (seconds)', 'owbn-archivist' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 300,
			'default'     => 0,
			'description' => __( 'Set to 0 to disable auto-refresh.', 'owbn-archivist' ),
		) );

		$this->add_control( 'entry_detail_page', array(
			'label'   => __( 'Entry Detail Page URL', 'owbn-archivist' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '/oat-entry/',
		) );

		$this->end_controls_section();

		// ── Style Tab ─────────────────────────────────────────────────────

		$this->start_controls_section( 'style_items', array(
			'label' => __( 'Feed Items', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'item_background', array(
			'label'     => __( 'Item Background', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-activity-item' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'item_border',
			'selector' => '{{WRAPPER}} .oat-activity-item',
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'timestamp_typography',
			'label'    => __( 'Timestamp Typography', 'owbn-archivist' ),
			'selector' => '{{WRAPPER}} .oat-activity-time',
		) );

		$this->add_control( 'timestamp_color', array(
			'label'     => __( 'Timestamp Color', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-activity-time' => 'color: {{VALUE}};',
			),
		) );

		$this->end_controls_section();
	}

	// ── Render ───────────────────────────────────────────────────────────────

	protected function render()
	{
		if ( ! is_user_logged_in() ) {
			echo '<p class="oat-login-prompt">' . esc_html__( 'Please log in to view activity.', 'owbn-archivist' ) . '</p>';
			return;
		}

		$settings     = $this->get_settings_for_display();
		$limit        = absint( $settings['item_count'] ) ?: 10;
		$domain       = sanitize_key( $settings['domain_filter'] ?: '' );
		$refresh      = absint( $settings['auto_refresh'] ) ?: 0;
		$detail_url   = $settings['entry_detail_page'] ?: '/oat-entry/';
		$user_id      = get_current_user_id();

		// Editor placeholder.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<div style="padding:20px;border:1px dashed #ccc;text-align:center;color:#646970;">' . esc_html__( 'OAT Activity Feed — preview not available in editor.', 'owbn-archivist' ) . '</div>';
			return;
		}

		$items = array();
		if ( function_exists( 'owc_oat_get_recent_activity' ) ) {
			$result = owc_oat_get_recent_activity( $user_id, $limit, $domain );
			if ( is_array( $result ) && ! is_wp_error( $result ) ) {
				$items = $result;
			}
		}
		?>
		<div class="oat-activity-widget"
			data-refresh="<?php echo esc_attr( $refresh ); ?>"
			data-limit="<?php echo esc_attr( $limit ); ?>"
			data-domain="<?php echo esc_attr( $domain ); ?>">

			<?php if ( empty( $items ) ) : ?>
				<p class="oat-inbox-empty"><?php esc_html_e( 'No recent activity.', 'owbn-archivist' ); ?></p>
			<?php else : ?>
				<ul class="oat-activity-feed">
					<?php echo $this->render_items( $items, $detail_url ); ?>
				</ul>
			<?php endif; ?>

		</div>
		<?php
	}

	/**
	 * Render activity feed items HTML.
	 * Used both for initial render and AJAX refresh response.
	 *
	 * @param array  $items      Activity event items.
	 * @param string $detail_url Base URL for entry links.
	 * @return string
	 */
	public static function render_items( $items, $detail_url )
	{
		ob_start();

		$action_colors = array(
			'approve'         => '#006505',
			'deny'            => '#8b0000',
			'request_changes' => '#996800',
			'submit'          => '#2271b1',
			'bump'            => '#005a87',
			'hold'            => '#996800',
			'resume'          => '#2271b1',
			'cancel'          => '#646970',
			'record'          => '#005a87',
		);

		foreach ( $items as $item ) {
			$entry_id   = isset( $item['entry_id'] ) ? (int) $item['entry_id'] : 0;
			$domain     = isset( $item['domain'] ) ? $item['domain'] : '';
			$domain_lbl = isset( $item['domain_label'] ) ? $item['domain_label'] : ucfirst( str_replace( '_', ' ', $domain ) );
			$action     = isset( $item['action_type'] ) ? $item['action_type'] : '';
			$action_lbl = ucfirst( str_replace( '_', ' ', $action ) );
			$actor      = isset( $item['actor_name'] ) ? $item['actor_name'] : '';
			$note       = isset( $item['note'] ) ? $item['note'] : '';
			$date       = isset( $item['created_at'] ) ? $item['created_at'] : '';
			$entry_url  = trailingslashit( $detail_url ) . '?oat_entry=' . $entry_id;
			$badge_bg   = isset( $action_colors[ $action ] ) ? $action_colors[ $action ] : '#646970';
			?>
			<li class="oat-activity-item">
				<span class="oat-activity-time"><?php echo esc_html( $date ); ?></span>
				<div class="oat-activity-body">
					<span class="oat-activity-action" style="background:<?php echo esc_attr( $badge_bg ); ?>;color:#fff;">
						<?php echo esc_html( $action_lbl ); ?>
					</span>
					<?php if ( $actor ) : ?>
						<span style="font-size:13px;color:#646970;margin-left:6px;"><?php printf( esc_html__( 'by %s', 'owbn-archivist' ), esc_html( $actor ) ); ?></span>
					<?php endif; ?>
					<?php if ( $entry_id ) : ?>
						&mdash;
						<a href="<?php echo esc_url( $entry_url ); ?>" style="font-size:13px;">
							#<?php echo esc_html( $entry_id ); ?> <?php echo esc_html( $domain_lbl ? '(' . $domain_lbl . ')' : '' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( $note ) : ?>
						<div class="oat-activity-note"><?php echo esc_html( $note ); ?></div>
					<?php endif; ?>
				</div>
			</li>
			<?php
		}

		return ob_get_clean();
	}
}
