<?php

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class OWC_OAT_Registry_Widget extends Widget_Base {

	public function get_name() {
		return 'owc_oat_registry';
	}

	public function get_title() {
		return __( 'Archivist Registry', 'owbn-client' );
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

		$this->add_control( 'show_last_activity', array(
			'label'   => __( 'Show Last Activity Column', 'owbn-client' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => '',
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

		$settings    = $this->get_settings_for_display();
		$show_search = ( $settings['show_search'] ?? 'yes' ) === 'yes';
		$detail_base = $settings['character_detail_url'] ?: '/oat-registry-detail/';
		if ( function_exists( 'owc_oat_localize_url' ) ) {
			$detail_base = owc_oat_localize_url( $detail_base );
		}
		$widget_scope = $settings['scope'] ?? 'all';

		// Determine which tabs to show based on widget scope setting.
		$all_tabs = array(
			'mine'           => __( 'My Characters', 'owbn-client' ),
			'chronicles'     => __( 'Chronicles', 'owbn-client' ),
			'coordinators'   => __( 'Coordinators', 'owbn-client' ),
			'decommissioned' => __( 'Decommissioned', 'owbn-client' ),
		);

		if ( 'all' !== $widget_scope ) {
			$all_tabs = array( $widget_scope => $all_tabs[ $widget_scope ] ?? 'Registry' );
		}

		$show_activity = ( $settings['show_last_activity'] ?? '' ) === 'yes';
		$ajax_url = admin_url( 'admin-ajax.php' );
		$nonce    = wp_create_nonce( 'owc_oat_nonce' );

		?>
		<style>
		.oat-registry-section-body.oat-collapsed { display: none; }
		.oat-registry-section-header { user-select: none; }
		.oat-registry-section-header::before { content: '\25B6'; margin-right: 8px; font-size: 0.8em; }
		.oat-registry-section-header.oat-expanded::before { content: '\25BC'; }
		.oat-registry-tab.active { font-weight: bold; background: #fff; border-bottom-color: #fff; }
		.oat-registry-tab { display: inline-block; padding: 8px 16px; border: 1px solid #ddd; border-bottom: none; margin-right: 2px; cursor: pointer; border-radius: 4px 4px 0 0; background: #f7f7f7; text-decoration: none; color: inherit; }
		.oat-registry-loading { padding: 20px; text-align: center; color: #666; }
		.oat-registry-table th[data-sort] { cursor: pointer; user-select: none; }
		.oat-registry-table th[data-sort]:hover { opacity: 0.7; }
		.oat-registry-table th[data-sort]::after { content: ' \2195'; font-size: 0.7em; opacity: 0.4; }
		.oat-registry-table th[data-sort].sort-asc::after { content: ' \2191'; opacity: 1; }
		.oat-registry-table th[data-sort].sort-desc::after { content: ' \2193'; opacity: 1; }
		</style>
		<div class="oat-registry-widget">
			<div class="oat-registry-header">
				<h3><?php esc_html_e( 'Registry', 'owbn-client' ); ?></h3>
				<?php if ( count( $all_tabs ) > 1 ) : ?>
					<nav class="oat-registry-tabs" style="margin:8px 0;border-bottom:1px solid #ddd;">
						<?php foreach ( $all_tabs as $tab_key => $tab_label ) : ?>
							<a href="#" class="oat-registry-tab" data-scope="<?php echo esc_attr( $tab_key ); ?>"><?php echo esc_html( $tab_label ); ?></a>
						<?php endforeach; ?>
					</nav>
				<?php endif; ?>
				<div style="display:flex;gap:12px;flex-wrap:wrap;margin:8px 0;align-items:center;">
					<?php if ( $show_search ) : ?>
						<input type="text" class="oat-registry-search" placeholder="<?php esc_attr_e( 'Search characters...', 'owbn-client' ); ?>" style="flex:1;min-width:200px;max-width:300px;">
					<?php endif; ?>
					<button type="button" class="oat-registry-clear" style="padding:4px 12px;cursor:pointer;border:1px solid #ccc;border-radius:4px;background:#fff;">Clear</button>
				</div>
			</div>
			<div class="oat-registry-content"></div>
		</div>
		<script>
		(function(){
			var widget = document.querySelector('.oat-registry-widget');
			if (!widget) return;
			var content   = widget.querySelector('.oat-registry-content');
			var ajaxUrl   = <?php echo wp_json_encode( $ajax_url ); ?>;
			var nonce     = <?php echo wp_json_encode( $nonce ); ?>;
			var detailBase = <?php echo wp_json_encode( $detail_base ); ?>;
			var tabs      = widget.querySelectorAll('.oat-registry-tab');
			var firstScope = <?php echo wp_json_encode( array_key_first( $all_tabs ) ); ?>;
			var showActivity = <?php echo $show_activity ? 'true' : 'false'; ?>;
			var i18n = {
				character:    <?php echo wp_json_encode( __( 'Character', 'owbn-client' ) ); ?>,
				chronicle:    <?php echo wp_json_encode( __( 'Chronicle', 'owbn-client' ) ); ?>,
				type:         <?php echo wp_json_encode( __( 'Type', 'owbn-client' ) ); ?>,
				pcNpc:        <?php echo wp_json_encode( __( 'PC/NPC', 'owbn-client' ) ); ?>,
				status:       <?php echo wp_json_encode( __( 'Status', 'owbn-client' ) ); ?>,
				entries:      <?php echo wp_json_encode( __( 'Entries', 'owbn-client' ) ); ?>,
				lastActivity: <?php echo wp_json_encode( __( 'Last Activity', 'owbn-client' ) ); ?>,
				loading:      <?php echo wp_json_encode( __( 'Loading...', 'owbn-client' ) ); ?>,
				searching:    <?php echo wp_json_encode( __( 'Searching...', 'owbn-client' ) ); ?>,
				noSections:   <?php echo wp_json_encode( __( 'No sections found.', 'owbn-client' ) ); ?>,
				noChars:      <?php echo wp_json_encode( __( 'No characters.', 'owbn-client' ) ); ?>,
				noResults:    <?php echo wp_json_encode( __( 'No characters found.', 'owbn-client' ) ); ?>,
			};
			var thStyle = 'style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;"';
			var thStyleC = 'style="text-align:center;padding:6px 8px;border-bottom:2px solid #ddd;"';
			var activityCol = showActivity ? '<th data-sort="activity" ' + thStyle + '>' + i18n.lastActivity + '</th>' : '';
			var activityColCount = showActivity ? 7 : 6;
			var headerRow = '<thead><tr>'
				+ '<th data-sort="name" ' + thStyle + '>' + i18n.character + '</th>'
				+ '<th data-sort="chronicle" ' + thStyle + '>' + i18n.chronicle + '</th>'
				+ '<th data-sort="type" ' + thStyle + '>' + i18n.type + '</th>'
				+ '<th data-sort="pcnpc" ' + thStyle + '>' + i18n.pcNpc + '</th>'
				+ '<th data-sort="status" ' + thStyle + '>' + i18n.status + '</th>'
				+ '<th data-sort="entries" ' + thStyleC + '>' + i18n.entries + '</th>'
				+ activityCol + '</tr></thead>';
			var loadedSections = {};

			function post(action, data, cb) {
				var fd = new FormData();
				fd.append('action', action);
				fd.append('nonce', nonce);
				for (var k in data) fd.append(k, data[k]);
				fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
					.then(function(r) { return r.json(); })
					.then(function(r) { cb(r.success ? r.data : null); })
					.catch(function() { cb(null); });
			}

			function loadTab(scope) {
				content.innerHTML = '<div class="oat-registry-loading">' + i18n.loading + '</div>';
				loadedSections = {};
				tabs.forEach(function(t) { t.classList.toggle('active', t.getAttribute('data-scope') === scope); });

				post('owc_oat_registry_sections', { scope: scope }, function(sections) {
					if (!sections || !sections.length) {
						content.innerHTML = '<p>' + i18n.noSections + '</p>';
						return;
					}
					var html = '';
					for (var i = 0; i < sections.length; i++) {
						var s = sections[i];
						html += '<div class="oat-registry-section" data-section-key="' + s.key + '" data-section-label="' + s.label.toLowerCase() + '">'
							+ '<div class="oat-registry-section-header" style="cursor:pointer;padding:8px 12px;margin-top:8px;border:1px solid #ddd;border-radius:4px;background:#f7f7f7;">'
							+ '<strong>' + s.label + '</strong>'
							+ '<span style="float:right;">' + s.count + '</span>'
							+ '</div>'
							+ '<div class="oat-registry-section-body oat-collapsed">'
							+ '<table class="oat-registry-table" style="width:100%;border-collapse:collapse;">'
							+ headerRow
							+ '<tbody><tr><td colspan="' + activityColCount + '" class="oat-registry-loading">' + i18n.loading + '</td></tr></tbody>'
							+ '</table></div></div>';
					}
					content.innerHTML = html;

					// Bind section expand/collapse + lazy character load.
					content.querySelectorAll('.oat-registry-section-header').forEach(function(header) {
						header.addEventListener('click', function() {
							var section = this.parentElement;
							var body = section.querySelector('.oat-registry-section-body');
							var key = section.getAttribute('data-section-key');
							var isOpen = this.classList.contains('oat-expanded');

							if (isOpen) {
								this.classList.remove('oat-expanded');
								body.classList.add('oat-collapsed');
							} else {
								this.classList.add('oat-expanded');
								body.classList.remove('oat-collapsed');
								if (!loadedSections[key]) {
									loadedSections[key] = true;
									loadSectionCharacters(section, key);
								}
							}
						});
					});
				});
			}

			function loadSectionCharacters(sectionEl, key) {
				post('owc_oat_registry_section', { section_key: key }, function(data) {
					var tbody = sectionEl.querySelector('tbody');
					if (!data || !data.characters || !data.characters.length) {
						tbody.innerHTML = '<tr><td colspan="' + activityColCount + '" style="padding:8px;color:#666;">' + i18n.noChars + '</td></tr>';
						return;
					}
					var html = '';
					for (var i = 0; i < data.characters.length; i++) {
						var c = data.characters[i];
						var name = c.character_name || '(unnamed)';
						var slug = (c.chronicle_slug || '').toUpperCase();
						var url = detailBase + '?character_id=' + (c.id || 0);
						var entries = c.entry_counts || 0;
						if (typeof entries === 'object') {
							var sum = 0; for (var k in entries) sum += parseInt(entries[k])||0;
							entries = sum;
						}
						var lastAct = c.last_activity || '';
						var lastActDisplay = '';
						if (lastAct) {
							var d = new Date(parseInt(lastAct) * 1000);
							lastActDisplay = d.toLocaleDateString();
						}
						html += '<tr class="oat-registry-row"'
							+ ' data-name="' + name.toLowerCase() + '"'
							+ ' data-chronicle="' + slug.toLowerCase() + '"'
							+ ' data-type="' + (c.creature_type||'').toLowerCase() + '"'
							+ ' data-pcnpc="' + (c.pc_npc||'').toLowerCase() + '"'
							+ ' data-status="' + (c.status||'').toLowerCase() + '"'
							+ ' data-entries="' + entries + '"'
							+ ' data-activity="' + (lastAct||'0') + '">'
							+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;"><a href="' + url + '" target="_blank">' + name + '</a></td>'
							+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' + slug + '</td>'
							+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' + (c.creature_type||'') + '</td>'
							+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' + (c.pc_npc||'').toUpperCase() + '</td>'
							+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' + (c.status ? c.status.charAt(0).toUpperCase() + c.status.slice(1) : '') + '</td>'
							+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:center;">' + entries + '</td>'
							+ (showActivity ? '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' + lastActDisplay + '</td>' : '')
							+ '</tr>';
					}
					tbody.innerHTML = html;
				});
			}

			// Tab clicks.
			tabs.forEach(function(tab) {
				tab.addEventListener('click', function(e) {
					e.preventDefault();
					loadTab(this.getAttribute('data-scope'));
				});
			});

			// Character search — server-side query, replaces section view with flat results.
			var search = widget.querySelector('.oat-registry-search');
			var searchTimer = null;
			var searchActive = false;
			if (search) {
				search.addEventListener('input', function() {
					var term = this.value.trim();
					clearTimeout(searchTimer);
					if (term.length < 2) {
						if (term.length === 0 && searchActive) {
							searchActive = false;
							loadTab(firstScope);
						}
						return;
					}
					searchTimer = setTimeout(function() {
						searchActive = true;
						content.innerHTML = '<div class="oat-registry-loading">' + i18n.searching + '</div>';
						post('owc_oat_registry_search', { q: term }, function(data) {
							if (!data || !data.length) {
								content.innerHTML = '<p>' + i18n.noResults + '</p>';
								return;
							}
							var html = '<table class="oat-registry-table" style="width:100%;border-collapse:collapse;">'
								+ headerRow + '<tbody>';
							for (var i = 0; i < data.length; i++) {
								var c = data[i];
								var url = detailBase + '?character_id=' + (c.id || 0);
								var entries = c.entry_counts || 0;
								if (typeof entries === 'object') { var sum = 0; for (var k in entries) sum += parseInt(entries[k])||0; entries = sum; }
								var sLastAct = c.last_activity || '';
								var sLastDisp = sLastAct ? new Date(parseInt(sLastAct)*1000).toLocaleDateString() : '';
								html += '<tr class="oat-registry-row"'
									+ ' data-name="' + (c.character_name||'').toLowerCase() + '"'
									+ ' data-chronicle="' + (c.chronicle_slug||'').toLowerCase() + '"'
									+ ' data-type="' + (c.creature_type||'').toLowerCase() + '"'
									+ ' data-pcnpc="' + (c.pc_npc||'').toLowerCase() + '"'
									+ ' data-status="' + (c.status||'').toLowerCase() + '"'
									+ ' data-entries="' + entries + '"'
									+ ' data-activity="' + (sLastAct||'0') + '">'
									+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;"><a href="' + url + '" target="_blank">' + (c.character_name||'') + '</a></td>'
									+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' + (c.chronicle_slug||'').toUpperCase() + '</td>'
									+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' + (c.creature_type||'') + '</td>'
									+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' + (c.pc_npc||'').toUpperCase() + '</td>'
									+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' + (c.status ? c.status.charAt(0).toUpperCase() + c.status.slice(1) : '') + '</td>'
									+ '<td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:center;">' + entries + '</td>'
									+ (showActivity ? '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' + sLastDisp + '</td>' : '')
									+ '</tr>';
							}
							html += '</tbody></table>';
							content.innerHTML = html;
						});
					}, 300);
				});
			}

			// Clear button.
			var clearBtn = widget.querySelector('.oat-registry-clear');
			if (clearBtn) {
				clearBtn.addEventListener('click', function() {
					if (search) search.value = '';
					content.querySelectorAll('.oat-registry-section').forEach(function(section) {
						section.style.display = '';
						section.querySelectorAll('.oat-registry-row').forEach(function(row) { row.style.display = ''; });
						var body = section.querySelector('.oat-registry-section-body');
						var header = section.querySelector('.oat-registry-section-header');
						if (body) body.classList.add('oat-collapsed');
						if (header) header.classList.remove('oat-expanded');
					});
				});
			}

			// Sort handler — click th[data-sort] to sort rows within that section.
			content.addEventListener('click', function(e) {
				var th = e.target.closest('th[data-sort]');
				if (!th) return;
				var key = th.getAttribute('data-sort');
				var tbody = th.closest('table').querySelector('tbody');
				if (!tbody) return;
				var rows = Array.from(tbody.querySelectorAll('tr.oat-registry-row'));
				if (!rows.length) return;

				// Toggle direction.
				var asc = !th.classList.contains('sort-asc');
				th.closest('thead').querySelectorAll('th[data-sort]').forEach(function(h) {
					h.classList.remove('sort-asc', 'sort-desc');
				});
				th.classList.add(asc ? 'sort-asc' : 'sort-desc');

				var numeric = (key === 'entries' || key === 'activity');
				rows.sort(function(a, b) {
					var va = a.getAttribute('data-' + key) || '';
					var vb = b.getAttribute('data-' + key) || '';
					if (numeric) {
						va = parseFloat(va) || 0;
						vb = parseFloat(vb) || 0;
						return asc ? va - vb : vb - va;
					}
					return asc ? va.localeCompare(vb) : vb.localeCompare(va);
				});
				rows.forEach(function(row) { tbody.appendChild(row); });
			});

			// Load first tab on ready.
			loadTab(firstScope);
		})();
		</script>
		<?php
	}
}
