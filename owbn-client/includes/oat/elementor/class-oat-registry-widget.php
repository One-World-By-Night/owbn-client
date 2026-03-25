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

		$this->add_control( 'scope', array(
			'label'   => __( 'Scope', 'owbn-client' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'all',
			'options' => array(
				'all'          => __( 'All (full registry)', 'owbn-client' ),
				'mine'         => __( 'My Characters only', 'owbn-client' ),
				'chronicles'   => __( 'My Chronicles only', 'owbn-client' ),
				'coordinators' => __( 'My Coordinator roles only', 'owbn-client' ),
			),
		) );

		$this->add_control( 'show_search', array(
			'label'   => __( 'Show Search', 'owbn-client' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		) );

		$this->add_control( 'show_section_filter', array(
			'label'   => __( 'Show Section Filter', 'owbn-client' ),
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

		$settings           = $this->get_settings_for_display();
		$show_search        = ( $settings['show_search'] ?? 'yes' ) === 'yes';
		$show_section_filter = ( $settings['show_section_filter'] ?? 'yes' ) === 'yes';
		$detail_base        = $settings['character_detail_url'] ?: '/oat-registry-detail/';
		// Preserve TranslatePress language prefix for links opened in new tabs.
		$detail_base = owc_oat_localize_url( $detail_base );
		$scope              = $settings['scope'] ?? 'all';

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
			if ( isset( $c['coordinator_grants'] ) && is_object( $c['coordinator_grants'] ) ) {
				$c['coordinator_grants'] = (array) $c['coordinator_grants'];
			}
			return $c;
		}, $characters );

		if ( ! function_exists( 'owc_oat_build_registry_sections' ) ) {
			require_once dirname( __DIR__ ) . '/pages/registry.php';
		}

		$sections    = owc_oat_build_registry_sections( $characters );

		// Filter sections by scope setting.
		if ( $scope !== 'all' ) {
			$sections = array_filter( $sections, function( $s ) use ( $scope ) {
				$key = $s['key'] ?? '';
				if ( $scope === 'mine' ) {
					return $key === 'mine';
				}
				if ( $scope === 'chronicles' ) {
					return strpos( $key, 'chronicle-' ) === 0;
				}
				if ( $scope === 'coordinators' ) {
					return strpos( $key, 'coordinator-' ) === 0;
				}
				return true;
			} );
			$sections = array_values( $sections );
		}

		$total_count = 0;
		foreach ( $sections as $s ) {
			$total_count += count( $s['characters'] );
		}

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
				<div style="display:flex;gap:12px;flex-wrap:wrap;margin:8px 0;align-items:center;">
					<?php if ( $show_section_filter && count( $sections ) > 3 ) : ?>
						<input type="text" class="oat-registry-section-filter" placeholder="<?php esc_attr_e( 'Filter sections...', 'owbn-client' ); ?>" style="flex:1;min-width:200px;max-width:300px;">
					<?php endif; ?>
					<?php if ( $show_search ) : ?>
						<input type="text" class="oat-registry-search" placeholder="<?php esc_attr_e( 'Search characters...', 'owbn-client' ); ?>" style="flex:1;min-width:200px;max-width:300px;">
					<?php endif; ?>
					<button type="button" class="oat-registry-clear" style="padding:4px 12px;cursor:pointer;border:1px solid #ccc;border-radius:4px;background:#fff;">Clear</button>
				</div>
			</div>

			<?php if ( empty( $sections ) ) : ?>
				<p><?php esc_html_e( 'No characters found.', 'owbn-client' ); ?></p>
			<?php endif; ?>

			<?php
			// Build JSON data for all sections — rendered by JS, not PHP.
			$sections_json = array();
			foreach ( $sections as $si => $section ) {
			$chars_json = array();
				foreach ( $section['characters'] as $char ) {
					$entry_total = $char['entry_counts'] ?? 0;
					if ( is_array( $entry_total ) ) { $entry_total = array_sum( $entry_total ); }
					$c_slug = $char['chronicle_slug'] ?? '';
					$c_title = '';
					if ( $c_slug && function_exists( 'owc_entity_get_title' ) ) {
						$c_title = owc_entity_get_title( 'chronicle', $c_slug );
					}
					$chars_json[] = array(
						'id'         => $char['id'] ?? 0,
						'name'       => $char['character_name'] ?? '(unnamed)',
						'slug'       => strtoupper( $c_slug ),
						'slug_title' => $c_title ?: strtoupper( $c_slug ),
						'creature'   => $char['creature_type'] ?? '',
						'pc_npc'     => strtoupper( $char['pc_npc'] ?? '' ),
						'status'     => ucfirst( $char['status'] ?? '' ),
						'entries'    => (int) $entry_total,
					);
				}
				$sections_json[] = array(
					'key'    => $section['key'],
					'label'  => $section['label'],
					'count'  => count( $section['characters'] ),
					'chars'  => $chars_json,
				);
			}
		?>

			<?php foreach ( $sections_json as $si => $sj ) : ?>
				<div class="oat-registry-section" data-section-label="<?php echo esc_attr( strtolower( $sj['label'] ) ); ?>" data-section-idx="<?php echo $si; ?>">
					<div class="oat-registry-section-header collapsed" style="cursor:pointer;padding:8px 12px;margin-top:12px;border:1px solid #ddd;border-radius:4px;background:#f7f7f7;">
						<strong><?php echo esc_html( $sj['label'] ); ?></strong>
						<span style="float:right;"><?php echo $sj['count']; ?></span>
					</div>
					<div class="oat-registry-section-body oat-collapsed">
						<table class="oat-registry-table" style="width:100%;border-collapse:collapse;">
							<thead>
								<tr>
									<th data-sort="0" style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;cursor:pointer;user-select:none;"><?php esc_html_e( 'Character', 'owbn-client' ); ?></th>
									<th data-sort="1" style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;cursor:pointer;user-select:none;"><?php esc_html_e( 'Chronicle', 'owbn-client' ); ?></th>
									<th data-sort="2" style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;cursor:pointer;user-select:none;"><?php esc_html_e( 'Type', 'owbn-client' ); ?></th>
									<th data-sort="3" style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;cursor:pointer;user-select:none;"><?php esc_html_e( 'PC/NPC', 'owbn-client' ); ?></th>
									<th data-sort="4" style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;cursor:pointer;user-select:none;"><?php esc_html_e( 'Status', 'owbn-client' ); ?></th>
									<th data-sort="5" style="text-align:center;padding:6px 8px;border-bottom:2px solid #ddd;cursor:pointer;user-select:none;"><?php esc_html_e( 'Entries', 'owbn-client' ); ?></th>
								</tr>
							</thead>
							<tbody></tbody>
						</table>
					</div>
				</div>
			<?php endforeach; ?>

			<script type="application/json" class="oat-registry-data"><?php echo wp_json_encode( $sections_json ); ?></script>
		</div>
		<script>
		(function(){
			var dataEl = document.querySelector('.oat-registry-data');
			if (!dataEl) return;
			var sections = JSON.parse(dataEl.textContent);
			var detailBase = <?php echo wp_json_encode( $detail_base ); ?>;
			var loaded = {};

			function renderRows(sectionEl, idx) {
				if (loaded[idx]) return;
				loaded[idx] = true;
				var chars = sections[idx].chars;
				var tbody = sectionEl.querySelector('tbody');
				var html = '';
				for (var i = 0; i < chars.length; i++) {
					var c = chars[i];
					var url = detailBase + '?character_id=' + c.id;
					html += '<tr class="oat-registry-row" data-name="' + c.name.toLowerCase() + '" data-chronicle="' + (c.slug||'').toLowerCase() + '">'
						+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;"><a href="' + url + '" target="_blank">' + c.name + '</a></td>'
						+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;" title="' + (c.slug_title||'') + '">' + (c.slug||'') + '</td>'
						+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' + (c.creature||'') + '</td>'
						+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' + (c.pc_npc||'') + '</td>'
						+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' + (c.status||'') + '</td>'
						+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:center;">' + c.entries + '</td>'
						+ '</tr>';
				}
				tbody.innerHTML = html;
			}

			// Click header to expand — render rows on first expand.
			document.querySelectorAll('.oat-registry-section-header').forEach(function(header) {
				header.addEventListener('click', function() {
					this.classList.toggle('collapsed');
					var body = this.nextElementSibling;
					body.classList.toggle('oat-collapsed');
					var section = this.parentElement;
					var idx = parseInt(section.getAttribute('data-section-idx'));
					if (!isNaN(idx)) renderRows(section, idx);
				});
			});

			// Background-fill all sections after page load.
			var bgIdx = 0;
			function bgFill() {
				if (bgIdx >= sections.length) return;
				var el = document.querySelector('[data-section-idx="' + bgIdx + '"]');
				if (el) renderRows(el, bgIdx);
				bgIdx++;
				requestAnimationFrame(bgFill);
			}
			setTimeout(bgFill, 500);

			// Section filter.
			var sectionFilter = document.querySelector('.oat-registry-section-filter');
			if (sectionFilter) {
				sectionFilter.addEventListener('input', function() {
					var term = this.value.toLowerCase();
					document.querySelectorAll('.oat-registry-section').forEach(function(section) {
						var label = section.getAttribute('data-section-label') || '';
						section.style.display = (!term || label.indexOf(term) !== -1) ? '' : 'none';
					});
				});
			}

			// Character search.
			var search = document.querySelector('.oat-registry-search');
			if (search) {
				search.addEventListener('input', function() {
					var term = this.value.toLowerCase();
					// Ensure all sections are rendered first.
					for (var i = 0; i < sections.length; i++) {
						var el = document.querySelector('[data-section-idx="' + i + '"]');
						if (el) renderRows(el, i);
					}
					document.querySelectorAll('.oat-registry-section').forEach(function(section) {
						var rows = section.querySelectorAll('.oat-registry-row');
						var visible = 0;
						rows.forEach(function(row) {
							var name = row.getAttribute('data-name') || '';
							var chron = row.getAttribute('data-chronicle') || '';
							var match = !term || name.indexOf(term) !== -1 || chron.indexOf(term) !== -1;
							row.style.display = match ? '' : 'none';
							if (match) visible++;
						});
						if (!term) {
							section.style.display = '';
							var body = section.querySelector('.oat-registry-section-body');
							var header = section.querySelector('.oat-registry-section-header');
							if (body) body.classList.add('oat-collapsed');
							if (header) header.classList.add('collapsed');
						} else if (visible > 0) {
							section.style.display = '';
							var body = section.querySelector('.oat-registry-section-body');
							var header = section.querySelector('.oat-registry-section-header');
							if (body) body.classList.remove('oat-collapsed');
							if (header) header.classList.remove('collapsed');
						} else {
							section.style.display = 'none';
						}
					});
				});
			}

			// Clear button.
			var clearBtn = document.querySelector('.oat-registry-clear');
			if (clearBtn) {
				clearBtn.addEventListener('click', function() {
					if (sectionFilter) sectionFilter.value = '';
					if (search) search.value = '';
					document.querySelectorAll('.oat-registry-section').forEach(function(section) {
						section.style.display = '';
						var body = section.querySelector('.oat-registry-section-body');
						var header = section.querySelector('.oat-registry-section-header');
						if (body) body.classList.add('oat-collapsed');
						if (header) header.classList.add('collapsed');
						section.querySelectorAll('.oat-registry-row').forEach(function(row) {
							row.style.display = '';
						});
					});
				});
			}

			// Column sorting within sections.
			document.querySelectorAll('th[data-sort]').forEach(function(th) {
				th.addEventListener('click', function() {
					var col = parseInt(this.getAttribute('data-sort'));
					var table = this.closest('table');
					var tbody = table.querySelector('tbody');
					var rows = Array.from(tbody.querySelectorAll('tr'));
					var asc = this.getAttribute('data-asc') !== 'true';
					this.setAttribute('data-asc', asc);

					rows.sort(function(a, b) {
						var aVal = a.cells[col] ? a.cells[col].textContent.trim().toLowerCase() : '';
						var bVal = b.cells[col] ? b.cells[col].textContent.trim().toLowerCase() : '';
						// Numeric sort for entries column
						if (col === 5) {
							return asc ? (parseInt(aVal)||0) - (parseInt(bVal)||0) : (parseInt(bVal)||0) - (parseInt(aVal)||0);
						}
						if (aVal < bVal) return asc ? -1 : 1;
						if (aVal > bVal) return asc ? 1 : -1;
						return 0;
					});

					rows.forEach(function(row) { tbody.appendChild(row); });
				});
			});
		})();
		</script>
		<?php
	}
}
