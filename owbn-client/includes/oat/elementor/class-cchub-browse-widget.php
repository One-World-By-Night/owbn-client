<?php

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class OWC_CCHub_Browse_Widget extends Widget_Base {

	public function get_name() { return 'owc_cchub_browse'; }
	public function get_title() { return __( 'ccHub Browse', 'owbn-client' ); }
	public function get_icon() { return 'eicon-table'; }
	public function get_categories() { return array( 'owbn-oat' ); }
	public function get_keywords() { return array( 'cchub', 'custom', 'content', 'browse', 'table' ); }
	public function get_style_depends() { return array( 'owc-oat-client' ); }
	public function get_script_depends() { return array( 'owc-oat-frontend' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array(
			'label' => __( 'Settings', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );
		$this->add_control( 'per_page', array(
			'label'   => __( 'Items per page', 'owbn-client' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 25,
			'min'     => 10,
			'max'     => 100,
		) );
		$this->add_control( 'categories_url', array(
			'label'   => __( 'Back to Categories URL', 'owbn-client' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '/cchub/',
		) );
		$this->end_controls_section();
	}

	protected function render() {
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<div style="padding:20px;border:1px dashed #ccc;text-align:center;">ccHub Browse Table — reads ?type= from URL</div>';
			return;
		}

		$settings  = $this->get_settings_for_display();
		$per_page  = absint( $settings['per_page'] ) ?: 25;
		$cat_url   = $settings['categories_url'] ?: '/cchub/';
		$type      = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '';

		global $wpdb;
		$entries = $wpdb->prefix . 'oat_entries';
		$meta    = $wpdb->prefix . 'oat_entry_meta';

		// Build query
		$where = "e.domain = 'custom_content' AND e.status = 'approved'";
		$join_type = '';
		if ( $type ) {
			$join_type = $wpdb->prepare(
				"JOIN {$meta} mt ON e.id = mt.entry_id AND mt.meta_key = 'content_type' AND mt.meta_value = %s",
				$type
			);
		}

		$sql = "
			SELECT e.id, e.coordinator_genre, e.chronicle_slug,
				mn.meta_value as content_name,
				mx.meta_value as xp_cost,
				mct.meta_value as content_type,
				mbm.meta_value as bm_category
			FROM {$entries} e
			{$join_type}
			LEFT JOIN {$meta} mn ON e.id = mn.entry_id AND mn.meta_key = 'content_name'
			LEFT JOIN {$meta} mx ON e.id = mx.entry_id AND mx.meta_key = 'xp_cost'
			LEFT JOIN {$meta} mct ON e.id = mct.entry_id AND mct.meta_key = 'content_type'
			LEFT JOIN {$meta} mbm ON e.id = mbm.entry_id AND mbm.meta_key = 'blood_magic_category'
			WHERE {$where}
			ORDER BY mn.meta_value ASC
		";
		$rows = $wpdb->get_results( $sql );

		// Build JSON for client-side pagination/search/modal
		$items = array();
		foreach ( $rows as $r ) {
			$items[] = array(
				'id'       => (int) $r->id,
				'name'     => $r->content_name ?: '(unnamed)',
				'type'     => $r->content_type ?: '',
				'bm_cat'   => $r->bm_category ?: '',
				'xp'       => $r->xp_cost ?: '',
				'coord'    => $r->coordinator_genre ? ucfirst( $r->coordinator_genre ) : '',
				'chron'    => strtoupper( $r->chronicle_slug ?: '' ),
			);
		}

		$is_bm = strpos( strtolower( $type ), 'blood magic' ) !== false;
		$title  = $type ?: 'All Custom Content';
		?>
		<div class="cchub-browse">
			<p><a href="<?php echo esc_url( $cat_url ); ?>">&larr; All Categories</a></p>
			<h3><?php echo esc_html( $title ); ?> <span style="color:#888;">(<?php echo count( $items ); ?>)</span></h3>

			<div style="display:flex;gap:12px;margin:8px 0;align-items:center;">
				<input type="text" class="cchub-search" placeholder="Search by name..." style="flex:1;max-width:400px;padding:4px 8px;">
				<span class="cchub-page-info" style="color:#888;"></span>
				<button type="button" class="cchub-prev" style="padding:2px 8px;">&laquo; Prev</button>
				<button type="button" class="cchub-next" style="padding:2px 8px;">Next &raquo;</button>
			</div>

			<table class="cchub-table" style="width:100%;border-collapse:collapse;font-size:0.9em;">
				<thead>
					<tr style="background:#f5f5f5;">
						<th data-sort="name" style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;cursor:pointer;">Name</th>
						<?php if ( $is_bm ) : ?>
							<th data-sort="bm_cat" style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;cursor:pointer;">Tradition</th>
						<?php endif; ?>
						<th data-sort="xp" style="text-align:center;padding:6px 8px;border-bottom:2px solid #ddd;cursor:pointer;width:60px;">XP</th>
						<th data-sort="coord" style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;cursor:pointer;">Coordinator</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>

			<!-- Modal overlay -->
			<div class="cchub-modal-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:9999;overflow-y:auto;">
				<div class="cchub-modal" style="background:#fff;max-width:800px;margin:40px auto;border-radius:8px;padding:24px;position:relative;">
					<button class="cchub-modal-close" style="position:absolute;top:12px;right:16px;font-size:1.5em;background:none;border:none;cursor:pointer;">&times;</button>
					<div class="cchub-modal-content"></div>
				</div>
			</div>

			<script type="application/json" class="cchub-data"><?php echo wp_json_encode( $items ); ?></script>
			<script>
			(function(){
				var items = JSON.parse(document.querySelector('.cchub-data').textContent);
				var perPage = <?php echo $per_page; ?>;
				var isBM = <?php echo $is_bm ? 'true' : 'false'; ?>;
				var filtered = items;
				var page = 0;
				var sortCol = 'name';
				var sortAsc = true;
				var tbody = document.querySelector('.cchub-table tbody');
				var search = document.querySelector('.cchub-search');
				var pageInfo = document.querySelector('.cchub-page-info');
				var ajaxUrl = '<?php echo admin_url( "admin-ajax.php" ); ?>';
				var nonce = '<?php echo wp_create_nonce( "owc_oat_nonce" ); ?>';

				function renderPage() {
					var start = page * perPage;
					var end = Math.min(start + perPage, filtered.length);
					var html = '';
					for (var i = start; i < end; i++) {
						var it = filtered[i];
						html += '<tr class="cchub-row" data-id="' + it.id + '" style="cursor:pointer;border-bottom:1px solid #eee;">'
							+ '<td style="padding:5px 8px;">' + it.name + '</td>';
						if (isBM) html += '<td style="padding:5px 8px;">' + (it.bm_cat||'') + '</td>';
						html += '<td style="padding:5px 8px;text-align:center;">' + (it.xp||'') + '</td>'
							+ '<td style="padding:5px 8px;">' + (it.coord||'') + '</td>'
							+ '</tr>';
					}
					tbody.innerHTML = html;
					var totalPages = Math.ceil(filtered.length / perPage);
					pageInfo.textContent = (filtered.length > 0) ? 'Page ' + (page+1) + ' of ' + totalPages + ' (' + filtered.length + ' items)' : 'No results';

					// Row click → modal
					tbody.querySelectorAll('.cchub-row').forEach(function(row) {
						row.addEventListener('click', function() { openModal(parseInt(this.getAttribute('data-id'))); });
					});
				}

				function sortItems() {
					filtered.sort(function(a, b) {
						var av = (a[sortCol]||'').toString().toLowerCase();
						var bv = (b[sortCol]||'').toString().toLowerCase();
						if (sortCol === 'xp') { av = parseInt(av)||0; bv = parseInt(bv)||0; return sortAsc ? av-bv : bv-av; }
						if (av < bv) return sortAsc ? -1 : 1;
						if (av > bv) return sortAsc ? 1 : -1;
						return 0;
					});
				}

				search.addEventListener('input', function() {
					var term = this.value.toLowerCase();
					filtered = items.filter(function(it) {
						return !term || it.name.toLowerCase().indexOf(term) !== -1 || (it.bm_cat||'').toLowerCase().indexOf(term) !== -1;
					});
					page = 0;
					sortItems();
					renderPage();
				});

				document.querySelector('.cchub-prev').addEventListener('click', function() { if (page > 0) { page--; renderPage(); } });
				document.querySelector('.cchub-next').addEventListener('click', function() { if ((page+1)*perPage < filtered.length) { page++; renderPage(); } });

				document.querySelectorAll('.cchub-table th[data-sort]').forEach(function(th) {
					th.addEventListener('click', function() {
						var col = this.getAttribute('data-sort');
						if (sortCol === col) { sortAsc = !sortAsc; } else { sortCol = col; sortAsc = true; }
						sortItems();
						page = 0;
						renderPage();
					});
				});

				// Modal
				var overlay = document.querySelector('.cchub-modal-overlay');
				var modalContent = document.querySelector('.cchub-modal-content');

				function openModal(entryId) {
					modalContent.innerHTML = '<p style="text-align:center;">Loading...</p>';
					overlay.style.display = '';

					var xhr = new XMLHttpRequest();
					xhr.open('POST', ajaxUrl);
					xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
					xhr.onload = function() {
						if (xhr.status !== 200) { modalContent.innerHTML = '<p>Error loading entry.</p>'; return; }
						var resp = JSON.parse(xhr.responseText);
						if (!resp.success) { modalContent.innerHTML = '<p>Entry not found.</p>'; return; }
						var d = resp.data;
						var html = '<h2>' + (d.content_name||'') + '</h2>';
						html += '<table style="width:100%;border-collapse:collapse;margin:12px 0;">';
						if (d.content_type) html += '<tr><td style="padding:3px 8px;font-weight:bold;width:150px;">Category</td><td>' + d.content_type + '</td></tr>';
						if (d.blood_magic_category) html += '<tr><td style="padding:3px 8px;font-weight:bold;">Tradition</td><td>' + d.blood_magic_category + '</td></tr>';
						if (d.xp_cost) html += '<tr><td style="padding:3px 8px;font-weight:bold;">XP Cost</td><td>' + d.xp_cost + '</td></tr>';
						if (d.coordinator_genre) html += '<tr><td style="padding:3px 8px;font-weight:bold;">Coordinator</td><td>' + d.coordinator_genre + '</td></tr>';
						if (d.chronicle_slug) html += '<tr><td style="padding:3px 8px;font-weight:bold;">Source Chronicle</td><td>' + d.chronicle_slug + '</td></tr>';
						if (d.source_hst) html += '<tr><td style="padding:3px 8px;font-weight:bold;">Source HST</td><td>' + d.source_hst + '</td></tr>';
						if (d.archival_date) html += '<tr><td style="padding:3px 8px;font-weight:bold;">Archival Date</td><td>' + d.archival_date + '</td></tr>';
						html += '</table>';

						if (d.discipline_requirements) {
							try {
								var reqs = JSON.parse(d.discipline_requirements);
								if (reqs.length) {
									html += '<h4>Discipline Requirements</h4><table style="width:100%;border-collapse:collapse;">';
									html += '<tr><th style="text-align:left;padding:3px 8px;border-bottom:1px solid #ddd;">Discipline</th><th style="text-align:center;padding:3px 8px;border-bottom:1px solid #ddd;">Level</th></tr>';
									reqs.forEach(function(r) { html += '<tr><td style="padding:3px 8px;">' + r.name + '</td><td style="text-align:center;padding:3px 8px;">' + r.level + '</td></tr>'; });
									html += '</table>';
								}
							} catch(e) {}
						}

						if (d.teachable_abilities) {
							try {
								var ta = JSON.parse(d.teachable_abilities);
								if (ta.length) html += '<p><strong>Teachable Abilities:</strong> ' + ta.join(', ') + '</p>';
							} catch(e) {}
						}

						if (d.met_rules) {
							html += '<h4>MET Mechanics</h4><div style="padding:8px;background:#f9f9f9;border:1px solid #eee;border-radius:4px;">' + d.met_rules + '</div>';
						}

						if (d.summary) {
							html += '<h4>Summary</h4><div style="padding:8px;background:#f9f9f9;border:1px solid #eee;border-radius:4px;">' + d.summary + '</div>';
						}

						modalContent.innerHTML = html;
					};
					xhr.send('action=owc_cchub_get_entry&nonce=' + nonce + '&entry_id=' + entryId);
				}

				overlay.addEventListener('click', function(e) { if (e.target === overlay) overlay.style.display = 'none'; });
				document.querySelector('.cchub-modal-close').addEventListener('click', function() { overlay.style.display = 'none'; });
				document.addEventListener('keydown', function(e) { if (e.key === 'Escape') overlay.style.display = 'none'; });

				sortItems();
				renderPage();
			})();
			</script>
		</div>
		<?php
	}
}
