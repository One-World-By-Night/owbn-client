<?php

/**
 * OAT Submit Form Widget
 *
 * Elementor widget for submitting a new OAT entry. Replaces the admin Submit page.
 * Domain fields are loaded via AJAX (owc_oat_get_domain_fields endpoint).
 * All 25 field types are rendered by owc_oat_render_fields() from fields.php.
 *
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

class OWC_OAT_Submit_Widget extends Widget_Base
{
	public function get_name()
	{
		return 'owc_oat_submit';
	}

	public function get_title()
	{
		return __( 'Archivist Submit Form', 'owbn-archivist' );
	}

	public function get_icon()
	{
		return 'eicon-form-horizontal';
	}

	public function get_categories()
	{
		return array( 'owbn-oat' );
	}

	public function get_keywords()
	{
		return array( 'oat', 'submit', 'form', 'owbn', 'archivist' );
	}

	public function get_style_depends()
	{
		return array( 'owc-oat-client', 'owc-oat-frontend' );
	}

	public function get_script_depends()
	{
		return array( 'owc-oat-client', 'owc-oat-frontend', 'owc-oat-regulation-picker', 'jquery-ui-autocomplete' );
	}

	// ── Controls ─────────────────────────────────────────────────────────────

	protected function register_controls()
	{
		// ── Content Tab ───────────────────────────────────────────────────

		$this->start_controls_section( 'content_section', array(
			'label' => __( 'Form Settings', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'domain_mode', array(
			'label'   => __( 'Domain Mode', 'owbn-archivist' ),
			'type'    => Controls_Manager::SELECT,
			'options' => array(
				'selector' => __( 'User Selects Domain', 'owbn-archivist' ),
				'fixed'    => __( 'Fixed Domain', 'owbn-archivist' ),
			),
			'default' => 'selector',
		) );

		$this->add_control( 'fixed_domain', array(
			'label'       => __( 'Fixed Domain Slug', 'owbn-archivist' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => 'character_lifecycle',
			'condition'   => array( 'domain_mode' => 'fixed' ),
		) );

		$this->add_control( 'submit_button_text', array(
			'label'   => __( 'Submit Button Text', 'owbn-archivist' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Submit Request', 'owbn-archivist' ),
		) );

		$this->add_control( 'redirect_url', array(
			'label'       => __( 'Redirect After Submit', 'owbn-archivist' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '/oat-entry/',
			'description' => __( 'Entry ID appended as ?oat_entry=ID', 'owbn-archivist' ),
		) );

		$this->end_controls_section();

		// ── Style Tab ─────────────────────────────────────────────────────

		$this->start_controls_section( 'style_form', array(
			'label' => __( 'Form', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'form_background', array(
			'label'     => __( 'Form Background', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-frontend-form' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'label_typography',
			'label'    => __( 'Label Typography', 'owbn-archivist' ),
			'selector' => '{{WRAPPER}} .oat-field-label',
		) );

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'input_border',
			'label'    => __( 'Input Border', 'owbn-archivist' ),
			'selector' => '{{WRAPPER}} .oat-frontend-form input, {{WRAPPER}} .oat-frontend-form select, {{WRAPPER}} .oat-frontend-form textarea',
		) );

		$this->add_control( 'input_focus_color', array(
			'label'     => __( 'Input Focus Color', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-frontend-form input:focus,
				 {{WRAPPER}} .oat-frontend-form select:focus,
				 {{WRAPPER}} .oat-frontend-form textarea:focus' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}};',
			),
		) );

		$this->add_control( 'button_background', array(
			'label'     => __( 'Button Background', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-submit-btn' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'button_background_hover', array(
			'label'     => __( 'Button Background (Hover)', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-submit-btn:hover' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'error_color', array(
			'label'     => __( 'Error Message Color', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-submit-error' => 'color: {{VALUE}};',
				'{{WRAPPER}} .oat-field-error'  => 'color: {{VALUE}};',
			),
		) );

		$this->end_controls_section();
	}

	// ── Render ───────────────────────────────────────────────────────────────

	protected function render()
	{
		if ( ! is_user_logged_in() ) {
			echo '<p class="oat-login-prompt">' . esc_html__( 'Please log in to submit a request.', 'owbn-archivist' ) . '</p>';
			return;
		}

		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<div style="padding:20px;border:1px dashed #ccc;text-align:center;color:#646970;">' . esc_html__( 'OAT Submit Form — preview not available in editor.', 'owbn-archivist' ) . '</div>';
			return;
		}

		if ( ! function_exists( 'owc_oat_get_domains' ) || ! function_exists( 'owc_oat_render_fields' ) ) {
			return;
		}

		$settings    = $this->get_settings_for_display();
		$domain_mode = $settings['domain_mode'] ?: 'selector';
		$fixed_slug  = sanitize_key( $settings['fixed_domain'] ?: '' );
		$btn_text    = $settings['submit_button_text'] ?: __( 'Submit Request', 'owbn-archivist' );
		$redirect    = esc_url( $settings['redirect_url'] ?: '/oat-entry/' );

		// Enqueue extras needed for rich field types.
		wp_enqueue_editor();
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );

		$domains        = owc_oat_get_domains();
		$domain_fields  = array();
		$selected_slug  = '';

		// A link such as the "Submit another" button can name the domain and form to open (?oat_domain=...&oat_form=...).
		// The page script only uses them when the lists it loads offer them.
		$preselect = array(
			'domain' => isset( $_GET['oat_domain'] ) ? sanitize_key( wp_unslash( $_GET['oat_domain'] ) ) : '',
			'form'   => isset( $_GET['oat_form'] ) ? sanitize_key( wp_unslash( $_GET['oat_form'] ) ) : '',
		);

		if ( 'fixed' === $domain_mode && $fixed_slug ) {
			$selected_slug = $fixed_slug;
			// Only pre-load fields if domain has a single form (multi-form domains use JS picker)
			$domain_forms = OAT_Domain_Registry::get_forms( $fixed_slug );
			if ( count( $domain_forms ) <= 1 ) {
				$domain_fields = function_exists( 'owc_oat_get_form_fields' ) ? owc_oat_get_form_fields( $fixed_slug, 'submit' ) : array();
			}
		}

		?>
		<div class="oat-submit-widget">
			<div id="oat-submit-feedback"></div>

			<div id="oat-list-panel" class="oat-list-panel" style="display:none;">
				<div class="oat-list-head"><strong><?php esc_html_e( 'Your list', 'owbn-archivist' ); ?></strong></div>
				<p class="oat-list-note"><?php esc_html_e( 'Each row is its own request with its own number. Submit them together and the reviewers see them as one group, each acting on the lines assigned to them.', 'owbn-archivist' ); ?></p>
				<ol id="oat-list-rows" class="oat-list-rows"></ol>
				<p class="oat-list-actions">
					<button type="button" class="oat-submit-btn" id="oat-list-submit-all"></button>
					<button type="button" class="button-link" id="oat-list-clear"><?php esc_html_e( 'Clear the list', 'owbn-archivist' ); ?></button>
				</p>
			</div>

			<form id="oat-frontend-submit-form"
				class="oat-frontend-form"
				data-redirect="<?php echo esc_attr( $redirect ); ?>">

				<?php if ( 'selector' === $domain_mode ) : ?>
					<div class="oat-field-row">
						<label class="oat-field-label" for="oat-domain-select">
							<?php esc_html_e( 'Domain', 'owbn-archivist' ); ?>
							<span class="oat-required">*</span>
						</label>
						<select id="oat-domain-select" name="oat_domain" required>
							<option value=""><?php esc_html_e( 'Select a domain…', 'owbn-archivist' ); ?></option>
							<?php foreach ( $domains as $d ) : ?>
								<option value="<?php echo esc_attr( $d['slug'] ); ?>">
									<?php echo esc_html( $d['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php else : ?>
					<input type="hidden" name="oat_domain" value="<?php echo esc_attr( $selected_slug ); ?>">
				<?php endif; ?>

				<div id="oat-form-picker-row" class="oat-field-row" style="display:none;">
					<label class="oat-field-label" for="oat-form-select">
						<?php esc_html_e( 'Form', 'owbn-archivist' ); ?>
						<span class="oat-required">*</span>
					</label>
					<select id="oat-form-select" name="oat_form_slug">
						<option value=""><?php esc_html_e( 'Select a form…', 'owbn-archivist' ); ?></option>
					</select>
				</div>

				<div id="oat-domain-fields-container">
					<?php if ( ! empty( $domain_fields ) ) : ?>
						<?php owc_oat_render_fields( $domain_fields ); ?>
					<?php endif; ?>
				</div>

					<!-- Submit -->
				<div class="oat-field-row oat-submit-row">
					<button type="submit" class="oat-submit-btn" id="oat-submit-btn">
						<?php echo esc_html( $btn_text ); ?>
					</button>
					<button type="button" class="button button-secondary oat-add-to-list" id="oat-add-to-list" style="display:none;"></button>
				</div>

			</form>
		</div>

		<style>
			.oat-list-panel { border: 1px solid #c3c4c7; border-radius: 4px; padding: 12px 16px; margin: 0 0 16px; background: #f6f7f7; }
			.oat-list-note { margin: 4px 0 8px; color: #50575e; }
			.oat-list-rows { margin: 0 0 8px 20px; padding: 0; }
			.oat-list-row { margin: 4px 0; }
			.oat-list-error { color: #8b0000; margin: 2px 0 4px; }
			.oat-list-row .button-link { margin-left: 6px; }
			.oat-list-actions { margin: 8px 0 0; }
			.oat-list-actions .button-link { margin-left: 12px; }
			.oat-submit-row .oat-add-to-list { margin-left: 8px; }
			.oat-list-editing #oat-domain-select, .oat-list-editing #oat-form-select { pointer-events: none; opacity: .6; }
		</style>
		<script type="text/javascript">
		(function($) {
			'use strict';

			var preselect = <?php echo wp_json_encode( $preselect ); ?>;

			// ── A list of rows: fill a form, add it to the list, fill another, then file them together. ─────────────────
			// Each row is the real form. A row that is set aside keeps its fields (and their pickers) out of the page, so only one form
			// is ever live; Edit puts it back. Submit all files the rows in order, and every request after the first joins the group of
			// the ones before it.
			var listRows   = [];
			var listFiled  = [];
			var listCarry  = null;
			var listEditAt = -1;
			var listPristine = '';
			var listSending = false;
			var listBtnText = <?php echo wp_json_encode( $btn_text ); ?>;
			var LT = {
				add:        <?php echo wp_json_encode( __( 'Add to list', 'owbn-archivist' ) ); ?>,
				putBack:    <?php echo wp_json_encode( __( 'Put back in the list', 'owbn-archivist' ) ); ?>,
				edit:       <?php echo wp_json_encode( __( 'Edit', 'owbn-archivist' ) ); ?>,
				remove:     <?php echo wp_json_encode( __( 'Remove', 'owbn-archivist' ) ); ?>,
				submitAll:  <?php echo wp_json_encode( __( 'Submit all (%d)', 'owbn-archivist' ) ); ?>,
				submitWith: <?php echo wp_json_encode( __( 'Submit this form with the list (%d)', 'owbn-archivist' ) ); ?>,
				submitting: <?php echo wp_json_encode( __( 'Submitting…', 'owbn-archivist' ) ); ?>,
				clearAsk:   <?php echo wp_json_encode( __( 'Remove every row from the list?', 'owbn-archivist' ) ); ?>,
				discardAsk: <?php echo wp_json_encode( __( 'Discard the form you are filling in and edit this row instead?', 'owbn-archivist' ) ); ?>,
				leaveAsk:   <?php echo wp_json_encode( __( 'Rows in your list have not been submitted.', 'owbn-archivist' ) ); ?>,
				failed:     <?php echo wp_json_encode( __( 'This row could not be filed.', 'owbn-archivist' ) ); ?>,
				netFailed:  <?php echo wp_json_encode( __( 'Request failed. Please try again.', 'owbn-archivist' ) ); ?>,
				filedHead:  <?php echo wp_json_encode( __( 'Filed together:', 'owbn-archivist' ) ); ?>,
				leftHead:   <?php echo wp_json_encode( __( 'These rows were not filed and are still in your list:', 'owbn-archivist' ) ); ?>,
				editing:    <?php echo wp_json_encode( __( '(being edited below)', 'owbn-archivist' ) ); ?>
			};

			function listFieldsPresent() {
				return $('#oat-domain-fields-container .oat-form-fields').length > 0;
			}

			function listSyncAdd() {
				var on = listFieldsPresent();
				$('#oat-add-to-list').toggle(on).text(listEditAt >= 0 ? LT.putBack : LT.add);
				$('#oat-frontend-submit-form').toggleClass('oat-list-editing', listEditAt >= 0);
			}

			function listKind() {
				var f = $.trim($('#oat-form-select option:selected').text());
				if (f && $('#oat-form-select').val()) { return f; }
				var d = $.trim($('#oat-domain-select option:selected').text());
				return d || <?php echo wp_json_encode( __( 'Request', 'owbn-archivist' ) ); ?>;
			}

			function listSummary($scope, kind) {
				var bits = [];
				$scope.find('.oat-character-selected-name').each(function() {
					var t = $.trim($(this).text());
					if (t && $(this).closest('.oat-character-selected').is(':visible')) { bits.push(t); }
				});
				$scope.find('.oat-cchub-selected .oat-rule-tag, .oat-rule-selected .oat-rule-tag').each(function() {
					var t = $.trim($(this).clone().children().remove().end().text());
					if (t) { bits.push(t); }
				});
				var name = $.trim($scope.find('[name="oat_meta_content_name"]').val() || '');
				if (name) { bits.push(name); }
				var text = kind + (bits.length ? ': ' + bits.join(' - ') : '');
				return text.length > 160 ? text.substring(0, 157) + '...' : text;
			}

			function listCaptureCarry($c) {
				var $uuid = $c.find('input.oat-character-uuid[name="oat_meta_character_name"], input.oat-character-uuid[name="oat_meta_character"]').first();
				var uuid  = $.trim($uuid.val() || '');
				if (!uuid) { return listCarry; }
				var $wrap = $uuid.closest('.oat-character-picker-wrap');
				return {
					uuid:      uuid,
					name:      $.trim($wrap.find('.oat-character-selected-name').text()),
					pcNpc:     $wrap.find('.oat-cc-pc-npc-val').val() || '',
					chronicle: $c.find('[name="oat_meta_chronicle_slug"]').val() || '',
					status:    $c.find('[name="oat_meta_ru_sub_status"]').val() || ''
				};
			}

			function listApplyCarry($c) {
				if (!listCarry || !listRows.length) { return; }
				var $uuid = $c.find('input.oat-character-uuid[name="oat_meta_character_name"], input.oat-character-uuid[name="oat_meta_character"]').first();
				if (!$uuid.length || $.trim($uuid.val() || '')) { return; }
				// A picker scoped by another field (Submitting As) is cleared when that field changes, so it is left for the filer to pick.
				if ($uuid.closest('.oat-character-picker-wrap').data('filter-by')) { return; }
				$uuid.closest('.oat-character-picker-wrap').trigger('oat-set-character', [listCarry.uuid, listCarry.name, listCarry.pcNpc, listCarry.chronicle]);
				if (listCarry.status) {
					var $st = $c.find('[name="oat_meta_ru_sub_status"]');
					if ($st.length && $st.find('option').filter(function() { return this.value === listCarry.status; }).length) { $st.val(listCarry.status); }
				}
			}

			// The picker scripts run on the same event, so the carry-over waits until they have finished.
			function listFieldsLoaded($c) {
				listSyncAdd();
				Promise.resolve().then(function() {
					listApplyCarry($c);
					listPristine = $('#oat-frontend-submit-form').serialize();
				});
			}

			function listReinitEditors(ids) {
				if (!window.tinyMCE || typeof tinyMCEPreInit === 'undefined') { return; }
				$.each(ids, function(i, id) {
					var base = tinyMCEPreInit.mceInit && tinyMCEPreInit.mceInit[id];
					if (!base) { return; }
					var settings = $.extend({}, base);
					settings.selector = '#' + id;
					tinyMCE.init(settings);
					if (typeof quicktags !== 'undefined' && tinyMCEPreInit.qtInit && tinyMCEPreInit.qtInit[id]) { quicktags(tinyMCEPreInit.qtInit[id]); }
				});
			}

			function listDropEditors($c) {
				var ids = [];
				if (window.tinyMCE) {
					$c.find('.wp-editor-area').each(function() {
						var ed = tinyMCE.get(this.id);
						if (ed) { ed.remove(); }
						ids.push(this.id);
					});
				}
				return ids;
			}

			// Sets the form being filled aside as a row.
			function listStash() {
				var $c   = $('#oat-domain-fields-container');
				var form = document.getElementById('oat-frontend-submit-form');
				if (window.tinyMCE) { try { tinyMCE.triggerSave(); } catch (err) {} }
				var kind = listKind();
				var row  = {
					kind:      kind,
					summary:   listSummary($c, kind),
					data:      $(form).serializeArray(),
					domain:    $('#oat-domain-select').val() || $('input[name="oat_domain"]').val() || '',
					formSlug:  $('#oat-form-select').val() || '',
					formHtml:  $('#oat-form-select').html(),
					pickerRow: $('#oat-form-picker-row').is(':visible'),
					error:     '',
					ids:       []
				};
				listCarry = listCaptureCarry($c);
				row.editors = listDropEditors($c);
				row.$nodes  = $c.contents().detach();
				return row;
			}

			// Adds the form being filled to the list (back at its place when it was being edited), then opens a fresh one of the same kind.
			function listAddCurrent(validated) {
				var form = document.getElementById('oat-frontend-submit-form');
				if (!listFieldsPresent()) { return false; }
				if (!validated && form.reportValidity && !form.reportValidity()) { return false; }
				var row = listStash();
				if (listEditAt >= 0) { listRows.splice(listEditAt, 0, row); listEditAt = -1; } else { listRows.push(row); }
				listRender();
				if (row.formSlug || row.domain) {
					var params = { domain: row.domain };
					if (row.formSlug) { params.form_slug = row.formSlug; }
					loadFields(params);
				} else {
					listSyncAdd();
				}
				return true;
			}

			function listRender() {
				var $ol = $('#oat-list-rows').empty();
				$.each(listRows, function(i, r) {
					var $li = $('<li class="oat-list-row"/>');
					$li.append($('<span class="oat-list-text"/>').text(r.summary));
					$li.append(' ');
					$li.append($('<button type="button" class="button-link oat-list-edit"/>').attr('data-i', i).text(LT.edit));
					$li.append(' ');
					$li.append($('<button type="button" class="button-link oat-list-remove"/>').attr('data-i', i).text(LT.remove));
					if (r.error) { $li.append($('<div class="oat-list-error"/>').text(r.error)); }
					$ol.append($li);
				});
				var n = listRows.length;
				$('#oat-list-panel').toggle(n > 0 || listEditAt >= 0);
				$('#oat-list-submit-all').text(LT.submitAll.replace('%d', n)).prop('disabled', n === 0 || listEditAt >= 0);
				$('#oat-submit-btn').text(n > 0 || listEditAt >= 0 ? LT.submitWith.replace('%d', n) : listBtnText);
			}

			function listBusy(on) {
				$('#oat-list-submit-all, #oat-submit-btn, #oat-add-to-list, #oat-list-clear, .oat-list-edit, .oat-list-remove').prop('disabled', on);
				if (on) { $('#oat-list-submit-all, #oat-submit-btn').text(LT.submitting); } else { listRender(); }
			}

			function listSubmitAll() {
				var rows = listRows.slice();
				if (!rows.length || listSending) { return; }
				listSending = true;
				var $feedback = $('#oat-submit-feedback').empty();
				var redirect  = $('#oat-frontend-submit-form').data('redirect') || '/oat-entry/';
				var filed     = [];
				var at        = 0;
				listBusy(true);

				function next() {
					if (at >= rows.length) { return finish(); }
					var r = rows[at++];
					r.error = '';
					var payload = r.data.slice();
					payload.push({ name: 'action', value: 'owc_oat_submit_entry_frontend' });
					payload.push({ name: 'nonce',  value: owc_oat_ajax.nonce });
					payload.push({ name: 'oat_batch_mode', value: 'list' });
					$.each(listFiled, function(k, id) { payload.push({ name: 'oat_batch_prior[]', value: id }); });
					$.post(owc_oat_ajax.url, payload, function(resp) {
						if (resp && resp.success && resp.data && resp.data.entry_id) {
							r.ids = resp.data.ids && resp.data.ids.length ? resp.data.ids : [resp.data.entry_id];
							listFiled = listFiled.concat(r.ids);
							filed.push(r);
						} else {
							r.error = (resp && typeof resp.data === 'string' && resp.data) ? resp.data : LT.failed;
						}
						next();
					}).fail(function() {
						r.error = LT.netFailed;
						next();
					});
				}

				function finish() {
					var left = [];
					$.each(listRows, function(i, r) {
						if (r.ids && r.ids.length) { if (r.$nodes) { r.$nodes.remove(); } } else { left.push(r); }
					});
					listRows = left;
					var total = 0;
					$.each(filed, function(i, r) { total += r.ids.length; });
					if (!left.length && total === 1) {
						window.location.href = redirect + '?oat_entry=' + filed[0].ids[0] + '&created=1';
						return;
					}
					if (filed.length) {
						var $ok = $('<div style="background:#d7f0d7;border:1px solid #006505;border-radius:4px;padding:16px;margin:12px 0;"/>');
						$ok.append($('<strong style="color:#006505;"/>').text(LT.filedHead));
						var $ul = $('<ul style="margin:8px 0 0 16px;"/>');
						$.each(filed, function(i, r) {
							var $li = $('<li/>').text(r.summary + ' ');
							$.each(r.ids, function(k, id) {
								$li.append($('<a target="_blank"/>').attr('href', redirect + '?oat_entry=' + id).text('#' + id + ' '));
							});
							$ul.append($li);
						});
						$ok.append($ul);
						$feedback.append($ok);
					}
					if (left.length) {
						var $bad = $('<div class="oat-submit-error"/>').text(LT.leftHead);
						$feedback.append($bad);
					} else {
						listFiled = [];
						listCarry = null;
					}
					listSending = false;
					listBusy(false);
					listSyncAdd();
					window.scrollTo(0, 0);
				}

				next();
			}

			$(document).on('click', '#oat-add-to-list', function() { listAddCurrent(false); });
			$(document).on('click', '#oat-list-submit-all', function() { listSubmitAll(); });
			$(document).on('click', '#oat-list-clear', function() {
				if (!window.confirm(LT.clearAsk)) { return; }
				$.each(listRows, function(i, r) { if (r.$nodes) { r.$nodes.remove(); } });
				listRows = []; listFiled = []; listCarry = null;
				listRender();
			});
			$(document).on('click', '.oat-list-remove', function() {
				var r = listRows.splice(parseInt($(this).attr('data-i'), 10), 1)[0];
				if (r && r.$nodes) { r.$nodes.remove(); }
				listRender();
			});
			$(document).on('click', '.oat-list-edit', function() {
				var i = parseInt($(this).attr('data-i'), 10);
				var row = listRows[i];
				if (!row || listEditAt >= 0) { return; }
				if (listFieldsPresent() && $('#oat-frontend-submit-form').serialize() !== listPristine && !window.confirm(LT.discardAsk)) { return; }
				var $c = $('#oat-domain-fields-container');
				listDropEditors($c);
				$c.empty();
				listRows.splice(i, 1);
				listEditAt = i;
				if ($('#oat-domain-select').length) { $('#oat-domain-select').val(row.domain); }
				$('#oat-form-select').html(row.formHtml).val(row.formSlug);
				$('#oat-form-picker-row').toggle(row.pickerRow);
				$c.append(row.$nodes);
				listReinitEditors(row.editors);
				listRender();
				listSyncAdd();
			});
			$(window).on('beforeunload', function() {
				if (listRows.length || listEditAt >= 0) { return LT.leaveAsk; }
			});
			listRender();
			listSyncAdd();

			function loadFields(params) {
				var $container = $('#oat-domain-fields-container');
				$container.html('<p style="color:#646970;padding:10px 0;"><?php echo esc_js( __( 'Loading fields…', 'owbn-archivist' ) ); ?></p>');
				params.action = 'owc_oat_get_domain_fields';
				params.nonce  = owc_oat_ajax.nonce;
				$.post(owc_oat_ajax.url, params, function(response) {
					if (response.success && response.data && response.data.html) {
						$container.html(response.data.html);
						$(document).trigger('oat-fields-loaded', [$container]);
						listFieldsLoaded($container);
					} else {
						$container.html('<p style="color:#8b0000;"><?php echo esc_js( __( 'Could not load fields for this domain.', 'owbn-archivist' ) ); ?></p>');
						listSyncAdd();
					}
				}).fail(function() {
					$container.html('<p style="color:#8b0000;"><?php echo esc_js( __( 'Request failed. Please try again.', 'owbn-archivist' ) ); ?></p>');
					listSyncAdd();
				});
			}

			$('#oat-domain-select').on('change', function() {
				var domain = $(this).val();
				$('#oat-domain-fields-container').empty();
				listSyncAdd();
				$('#oat-form-picker-row').hide();
				$('#oat-form-select').html('<option value=""><?php echo esc_js( __( 'Select a form…', 'owbn-archivist' ) ); ?></option>');

				if (!domain) { return; }

				$.post(owc_oat_ajax.url, {
					action: 'owc_oat_get_domain_forms',
					nonce:  owc_oat_ajax.nonce,
					domain_slug: domain
				}, function(response) {
					var forms = (response.success && response.data) ? response.data : [];
					if (forms.length > 1) {
						var $sel = $('#oat-form-select');
						$sel.html('<option value=""><?php echo esc_js( __( 'Select a form…', 'owbn-archivist' ) ); ?></option>');
						$.each(forms, function(i, f) {
							$sel.append('<option value="' + f.slug + '">' + f.label + '</option>');
						});
						$('#oat-form-picker-row').show();
						if (preselect.form && domain === preselect.domain && $sel.find('option').filter(function() { return this.value === preselect.form; }).length) {
							$sel.val(preselect.form).trigger('change');
						}
						preselect.form = '';
					} else if (forms.length === 1) {
						$('#oat-form-select').html('<option value="' + forms[0].slug + '">' + forms[0].label + '</option>');
						$('#oat-form-picker-row').hide();
						loadFields({ form_slug: forms[0].slug, domain: domain });
					} else {
						$('#oat-form-picker-row').hide();
						loadFields({ domain: domain });
					}
				}).fail(function() {
					loadFields({ domain: domain });
				});
			});

			$('#oat-form-select').on('change', function() {
				var formSlug = $(this).val();
				var domain   = $('#oat-domain-select').val() || $('input[name="oat_domain"]').val();
				$('#oat-domain-fields-container').empty();
				listSyncAdd();
				if (!formSlug) { return; }
				loadFields({ form_slug: formSlug, domain: domain });
			});

			$('#oat-frontend-submit-form').on('submit', function(e) {
				e.preventDefault();
				// With rows in the list, the form being filled joins them and everything is filed together.
				if (listRows.length || listEditAt >= 0) {
					if (listSending) { return; }
					if (listFieldsPresent()) { listAddCurrent(true); }
					listSubmitAll();
					return;
				}
				var $form = $(this);
				var $btn  = $('#oat-submit-btn');
				var $feedback = $('#oat-submit-feedback');

				$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Submitting…', 'owbn-archivist' ) ); ?>');
				$feedback.html('');

				var formData = $form.serializeArray();
				formData.push({ name: 'action', value: 'owc_oat_submit_entry_frontend' });
				formData.push({ name: 'nonce',  value: owc_oat_ajax.nonce });

				$.post(owc_oat_ajax.url, formData, function(response) {
					if (response.success && response.data && response.data.entry_id) {
						var redirect = $form.data('redirect') || '/oat-entry/';
						if (response.data.batch && response.data.ids && response.data.ids.length > 1) {
							var html = '<div style="background:#d7f0d7;border:1px solid #006505;border-radius:4px;padding:16px;margin:12px 0;">';
							html += '<strong style="color:#006505;">' + response.data.count + ' entries created:</strong><ul style="margin:8px 0 0 16px;">';
							for (var i = 0; i < response.data.ids.length; i++) {
								html += '<li><a href="' + redirect + '?oat_entry=' + response.data.ids[i] + '" target="_blank">Entry #' + response.data.ids[i] + ' &#x29C9;</a></li>';
							}
							html += '</ul></div>';
							$feedback.html(html);
							$btn.prop('disabled', false).text(<?php echo wp_json_encode( $btn_text ); ?>);
						} else {
							window.location.href = redirect + '?oat_entry=' + response.data.entry_id + '&created=1';
						}
					} else {
						var msg = response.data || '<?php echo esc_js( __( 'Submission failed. Please check the form and try again.', 'owbn-archivist' ) ); ?>';
						$feedback.html('<div class="oat-submit-error">' + msg + '</div>');
						$btn.prop('disabled', false).text(<?php echo wp_json_encode( $btn_text ); ?>);
					}
				}).fail(function() {
					$feedback.html('<div class="oat-submit-error"><?php echo esc_js( __( 'Request failed. Please try again.', 'owbn-archivist' ) ); ?></div>');
					$btn.prop('disabled', false).text(<?php echo wp_json_encode( $btn_text ); ?>);
				});
			});

			// For fixed/pre-selected domains: check for multiple forms on load.
			var fixedDomain = $('input[name="oat_domain"]').val();
			if (fixedDomain) {
				$.post(owc_oat_ajax.url, {
					action: 'owc_oat_get_domain_forms',
					nonce:  owc_oat_ajax.nonce,
					domain_slug: fixedDomain
				}, function(response) {
					var forms = (response.success && response.data) ? response.data : [];
					if (forms.length > 1) {
						var $sel = $('#oat-form-select');
						$sel.html('<option value=""><?php echo esc_js( __( 'Select a form…', 'owbn-archivist' ) ); ?></option>');
						$.each(forms, function(i, f) {
							$sel.append('<option value="' + f.slug + '">' + f.label + '</option>');
						});
						$('#oat-form-picker-row').show();
						$('#oat-domain-fields-container').empty();
						if (preselect.form && (!preselect.domain || preselect.domain === fixedDomain) && $sel.find('option').filter(function() { return this.value === preselect.form; }).length) {
							$sel.val(preselect.form).trigger('change');
						}
						preselect.form = '';
					}
				});
			}

			// A link can name the domain to open (the form is chosen once that domain's forms have loaded). The page's ajax
			// settings are printed with the footer scripts, so this waits for the page to be ready.
			$(function() {
				if (preselect.domain && $('#oat-domain-select option').filter(function() { return this.value === preselect.domain; }).length) {
					$('#oat-domain-select').val(preselect.domain).trigger('change');
				}
			});

		})(jQuery);
		</script>
		<?php
	}
}
