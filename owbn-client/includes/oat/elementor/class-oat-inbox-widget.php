<?php

/**
 * OAT Inbox Widget
 *
 * Elementor widget for displaying a filterable, sortable list of OAT entries
 * relevant to the current user. Replaces the admin Inbox page.
 *
 * location: includes/oat/elementor/class-oat-inbox-widget.php
 * @package OWBN-Client
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class OWC_OAT_Inbox_Widget extends Widget_Base
{
	public function get_name()
	{
		return 'owc_oat_inbox';
	}

	public function get_title()
	{
		return __( 'OAT Inbox', 'owbn-client' );
	}

	public function get_icon()
	{
		return 'eicon-post-list';
	}

	public function get_categories()
	{
		return array( 'owbn-oat' );
	}

	public function get_keywords()
	{
		return array( 'oat', 'inbox', 'submissions', 'owbn', 'archivist' );
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
			'label' => __( 'Content', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'default_tab', array(
			'label'   => __( 'Default Tab', 'owbn-client' ),
			'type'    => Controls_Manager::SELECT,
			'options' => array(
				'assigned'    => __( 'Assigned to Me', 'owbn-client' ),
				'submissions' => __( 'My Submissions', 'owbn-client' ),
				'watching'    => __( 'Watching', 'owbn-client' ),
			),
			'default' => 'assigned',
		) );

		$this->add_control( 'per_page', array(
			'label'   => __( 'Entries Per Page', 'owbn-client' ),
			'type'    => Controls_Manager::NUMBER,
			'min'     => 5,
			'max'     => 100,
			'default' => 20,
		) );

		$this->add_control( 'show_domain_filter', array(
			'label'        => __( 'Show Domain Filter', 'owbn-client' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'owbn-client' ),
			'label_off'    => __( 'Hide', 'owbn-client' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_status_filter', array(
			'label'        => __( 'Show Status Filter', 'owbn-client' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'owbn-client' ),
			'label_off'    => __( 'Hide', 'owbn-client' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_search', array(
			'label'        => __( 'Show Search', 'owbn-client' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'owbn-client' ),
			'label_off'    => __( 'Hide', 'owbn-client' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'entry_detail_page', array(
			'label'       => __( 'Entry Detail Page URL', 'owbn-client' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '/oat-entry/',
			'description' => __( 'Entry ID appended as ?oat_entry=ID', 'owbn-client' ),
		) );

		$this->end_controls_section();

		// ── Style Tab ─────────────────────────────────────────────────────

		$this->start_controls_section( 'style_table', array(
			'label' => __( 'Table', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'table_header_bg', array(
			'label'     => __( 'Header Background', 'owbn-client' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-inbox-table thead th' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'table_header_color', array(
			'label'     => __( 'Header Text Color', 'owbn-client' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-inbox-table thead th' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'table_row_hover', array(
			'label'     => __( 'Row Hover Background', 'owbn-client' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-inbox-table tbody tr:hover' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'table_typography',
			'selector' => '{{WRAPPER}} .oat-inbox-table',
		) );

		$this->end_controls_section();

		$this->start_controls_section( 'style_tabs', array(
			'label' => __( 'Tabs', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'tab_active_color', array(
			'label'     => __( 'Active Tab Color', 'owbn-client' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-inbox-tab.active'               => 'color: {{VALUE}};',
				'{{WRAPPER}} .oat-inbox-tab.active'               => 'border-bottom-color: {{VALUE}};',
			),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'tab_typography',
			'selector' => '{{WRAPPER}} .oat-inbox-tab',
		) );

		$this->end_controls_section();
	}

	// ── Render ───────────────────────────────────────────────────────────────

	protected function render()
	{
		if ( ! is_user_logged_in() ) {
			echo '<p class="oat-login-prompt">' . esc_html__( 'Please log in to access your inbox.', 'owbn-client' ) . '</p>';
			return;
		}

		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<div style="padding:20px;border:1px dashed #ccc;text-align:center;color:#646970;">' . esc_html__( 'OAT Inbox — preview not available in editor.', 'owbn-client' ) . '</div>';
			return;
		}

		if ( ! function_exists( 'owc_oat_get_inbox' ) || ! function_exists( 'owc_oat_get_domains' ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();

		$per_page       = absint( $settings['per_page'] ) ?: 20;
		$default_tab    = $settings['default_tab'] ?: 'assigned';
		$detail_base    = $settings['entry_detail_page'] ?: '/oat-entry/';
		$show_domain    = ( $settings['show_domain_filter'] ?? 'yes' ) === 'yes';
		$show_status    = ( $settings['show_status_filter'] ?? 'yes' ) === 'yes';
		$show_search    = ( $settings['show_search'] ?? 'yes' ) === 'yes';

		// Fetch data.
		$inbox   = owc_oat_get_inbox( '' );
		$domains = function_exists( 'owc_oat_get_domains' ) ? owc_oat_get_domains() : array();

		// Normalize: api returns assignments, my_entries, watched, user_map.
		$assignments = isset( $inbox['assignments'] ) ? $inbox['assignments'] : array();
		$my_entries  = isset( $inbox['my_entries'] ) ? $inbox['my_entries'] : array();
		$watched     = isset( $inbox['watched'] ) ? $inbox['watched'] : array();
		$user_map    = isset( $inbox['user_map'] ) && is_array( $inbox['user_map'] ) ? $inbox['user_map'] : array();

		// Collect all unique statuses for filter dropdown.
		$all_rows    = array_merge( $assignments, $my_entries, $watched );
		$statuses    = array();
		foreach ( $all_rows as $row ) {
			$s = isset( $row['status'] ) ? $row['status'] : '';
			if ( $s && ! isset( $statuses[ $s ] ) ) {
				$statuses[ $s ] = ucfirst( str_replace( '_', ' ', $s ) );
			}
		}
		?>
		<div class="oat-inbox-widget" data-per-page="<?php echo esc_attr( $per_page ); ?>">

			<!-- Tab navigation -->
			<nav class="oat-inbox-tabs" role="tablist">
				<?php $this->render_tab( 'assigned', __( 'Assigned to Me', 'owbn-client' ), count( $assignments ), $default_tab ); ?>
				<?php $this->render_tab( 'submissions', __( 'My Submissions', 'owbn-client' ), count( $my_entries ), $default_tab ); ?>
				<?php $this->render_tab( 'watching', __( 'Watching', 'owbn-client' ), count( $watched ), $default_tab ); ?>
			</nav>

			<!-- Filter bar -->
			<?php if ( $show_domain || $show_status || $show_search ) : ?>
				<div class="oat-inbox-filters">
					<?php if ( $show_domain && ! empty( $domains ) ) : ?>
						<select class="oat-filter-domain">
							<option value=""><?php esc_html_e( 'All Domains', 'owbn-client' ); ?></option>
							<?php foreach ( $domains as $d ) : ?>
								<option value="<?php echo esc_attr( $d['slug'] ); ?>">
									<?php echo esc_html( $d['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
					<?php if ( $show_status && ! empty( $statuses ) ) : ?>
						<select class="oat-filter-status">
							<option value=""><?php esc_html_e( 'All Statuses', 'owbn-client' ); ?></option>
							<?php foreach ( $statuses as $slug => $label ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>">
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
					<?php if ( $show_search ) : ?>
						<input type="text" class="oat-filter-search"
							placeholder="<?php esc_attr_e( 'Search entries…', 'owbn-client' ); ?>">
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<!-- Panels -->
			<?php echo $this->render_panel( 'assigned', $assignments, $detail_base, 'assigned' === $default_tab, $per_page, true, $user_map ); ?>
			<?php echo $this->render_panel( 'submissions', $my_entries, $detail_base, 'submissions' === $default_tab, $per_page, true, $user_map ); ?>
			<?php echo $this->render_panel( 'watching', $watched, $detail_base, 'watching' === $default_tab, $per_page, false, $user_map ); ?>

		</div>
		<?php
	}

	// ── Private Helpers ───────────────────────────────────────────────────────

	/**
	 * Render a single tab button.
	 */
	private function render_tab( $slug, $label, $count, $default_tab )
	{
		$active = ( $slug === $default_tab ) ? ' active' : '';
		?>
		<button type="button"
			class="oat-inbox-tab<?php echo esc_attr( $active ); ?>"
			data-tab="<?php echo esc_attr( $slug ); ?>"
			role="tab"
			aria-selected="<?php echo $active ? 'true' : 'false'; ?>">
			<?php echo esc_html( $label ); ?>
			<span class="oat-tab-count"><?php echo esc_html( $count ); ?></span>
		</button>
		<?php
	}

	/**
	 * Render a tab panel with its table.
	 *
	 * @param string $slug       Panel identifier.
	 * @param array  $rows       Entry rows.
	 * @param string $detail_url Base URL for entry detail links.
	 * @param bool   $visible    Whether this panel is visible by default.
	 * @param int    $per_page   Entries per page.
	 * @param bool   $show_step  Whether to include current step column.
	 * @return string
	 */
	private function render_panel( $slug, $rows, $detail_url, $visible, $per_page, $show_step, $user_map = array() )
	{
		ob_start();
		$display = $visible ? '' : ' style="display:none;"';
		?>
		<div class="oat-inbox-panel" data-panel="<?php echo esc_attr( $slug ); ?>"<?php echo $display; ?>>
			<?php if ( empty( $rows ) ) : ?>
				<p class="oat-inbox-empty"><?php esc_html_e( 'No entries found.', 'owbn-client' ); ?></p>
			<?php else : ?>
				<table class="oat-inbox-table">
					<thead>
						<tr>
							<th data-sort="id"><?php esc_html_e( 'Entry', 'owbn-client' ); ?> <span class="oat-sort-icon"></span></th>
							<th data-sort="domain"><?php esc_html_e( 'Domain', 'owbn-client' ); ?> <span class="oat-sort-icon"></span></th>
							<th data-sort="status"><?php esc_html_e( 'Status', 'owbn-client' ); ?> <span class="oat-sort-icon"></span></th>
							<?php if ( $show_step ) : ?>
								<th data-sort="step"><?php esc_html_e( 'Step', 'owbn-client' ); ?> <span class="oat-sort-icon"></span></th>
							<?php endif; ?>
							<th data-sort="date"><?php esc_html_e( 'Date', 'owbn-client' ); ?> <span class="oat-sort-icon"></span></th>
							<th><?php esc_html_e( 'Action', 'owbn-client' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $i => $row ) :
							$entry_id    = isset( $row['entry_id'] ) ? (int) $row['entry_id'] : 0;
							$domain_slug = isset( $row['domain'] ) ? $row['domain'] : '';
							$domain_lbl  = isset( $row['domain_label'] ) ? $row['domain_label'] : ucfirst( str_replace( '_', ' ', $domain_slug ) );
							$orig_id     = isset( $row['originator_id'] ) ? (int) $row['originator_id'] : 0;
							$orig_name   = isset( $user_map[ $orig_id ] ) ? $user_map[ $orig_id ] : '';
							$status      = isset( $row['status'] ) ? $row['status'] : '';
							$step        = isset( $row['current_step'] ) ? $row['current_step'] : '';
							$date_col    = isset( $row['updated_at'] ) ? $row['updated_at'] : ( isset( $row['created_at'] ) ? $row['created_at'] : '' );
							$entry_url   = trailingslashit( $detail_url ) . '?oat_entry=' . $entry_id;
							$row_hidden  = ( $i >= $per_page ) ? ' style="display:none;"' : '';
							$subject     = $orig_name ? $orig_name . ' &#8250; ' . $domain_lbl : '#' . $entry_id;
						?>
							<tr data-domain="<?php echo esc_attr( $domain_slug ); ?>"
								data-status="<?php echo esc_attr( $status ); ?>"
								<?php echo $row_hidden; ?>>
								<td data-label="<?php esc_attr_e( 'Entry', 'owbn-client' ); ?>"
									data-sort-value="<?php echo esc_attr( $entry_id ); ?>">
									<a href="<?php echo esc_url( $entry_url ); ?>">
										<strong><?php echo $subject; ?></strong>
									</a>
									<span style="color:#999;font-size:11px;margin-left:6px;">#<?php echo esc_html( $entry_id ); ?></span>
								</td>
								<td data-label="<?php esc_attr_e( 'Domain', 'owbn-client' ); ?>"
									data-sort-value="<?php echo esc_attr( $domain_lbl ); ?>">
									<?php echo esc_html( $domain_lbl ); ?>
								</td>
								<td data-label="<?php esc_attr_e( 'Status', 'owbn-client' ); ?>"
									data-sort-value="<?php echo esc_attr( $status ); ?>">
									<span class="oat-status oat-status-<?php echo esc_attr( $status ); ?>">
										<?php echo esc_html( ucfirst( str_replace( '_', ' ', $status ) ) ); ?>
									</span>
								</td>
								<?php if ( $show_step ) : ?>
									<td data-label="<?php esc_attr_e( 'Step', 'owbn-client' ); ?>"
										data-sort-value="<?php echo esc_attr( $step ); ?>">
										<?php echo esc_html( str_replace( '_', ' ', $step ) ); ?>
									</td>
								<?php endif; ?>
								<td data-label="<?php esc_attr_e( 'Date', 'owbn-client' ); ?>"
									data-sort-value="<?php echo esc_attr( $date_col ); ?>">
									<?php echo esc_html( $date_col ); ?>
								</td>
								<td>
									<a href="<?php echo esc_url( $entry_url ); ?>" class="oat-btn oat-btn-secondary" style="font-size:12px;padding:4px 10px;">
										<?php esc_html_e( 'View', 'owbn-client' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="oat-inbox-empty" style="display:none;"><?php esc_html_e( 'No entries match your filters.', 'owbn-client' ); ?></p>

				<?php if ( count( $rows ) > $per_page ) : ?>
					<?php echo $this->render_pagination( count( $rows ), $per_page ); ?>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render pagination buttons.
	 *
	 * @param int $total    Total number of rows.
	 * @param int $per_page Rows per page.
	 * @return string
	 */
	private function render_pagination( $total, $per_page )
	{
		$pages = (int) ceil( $total / $per_page );
		if ( $pages <= 1 ) {
			return '';
		}

		ob_start();
		?>
		<div class="oat-pagination">
			<button type="button" class="oat-page-btn disabled" data-page="0">&laquo;</button>
			<?php for ( $p = 1; $p <= $pages; $p++ ) : ?>
				<button type="button"
					class="oat-page-btn<?php echo 1 === $p ? ' active' : ''; ?>"
					data-page="<?php echo esc_attr( $p ); ?>">
					<?php echo esc_html( $p ); ?>
				</button>
			<?php endfor; ?>
			<button type="button" class="oat-page-btn<?php echo $pages <= 1 ? ' disabled' : ''; ?>" data-page="<?php echo esc_attr( $pages + 1 ); ?>">&raquo;</button>
		</div>
		<?php
		return ob_get_clean();
	}
}
