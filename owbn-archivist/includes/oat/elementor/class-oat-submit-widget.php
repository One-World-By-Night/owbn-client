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
				<div class="oat-field-row">
					<button type="submit" class="oat-submit-btn" id="oat-submit-btn">
						<?php echo esc_html( $btn_text ); ?>
					</button>
				</div>

			</form>
		</div>

		<script type="text/javascript">
		(function($) {
			'use strict';

			function loadFields(params) {
				var $container = $('#oat-domain-fields-container');
				$container.html('<p style="color:#646970;padding:10px 0;"><?php echo esc_js( __( 'Loading fields…', 'owbn-archivist' ) ); ?></p>');
				params.action = 'owc_oat_get_domain_fields';
				params.nonce  = owc_oat_ajax.nonce;
				$.post(owc_oat_ajax.url, params, function(response) {
					if (response.success && response.data && response.data.html) {
						$container.html(response.data.html);
						$(document).trigger('oat-fields-loaded', [$container]);
					} else {
						$container.html('<p style="color:#8b0000;"><?php echo esc_js( __( 'Could not load fields for this domain.', 'owbn-archivist' ) ); ?></p>');
					}
				}).fail(function() {
					$container.html('<p style="color:#8b0000;"><?php echo esc_js( __( 'Request failed. Please try again.', 'owbn-archivist' ) ); ?></p>');
				});
			}

			$('#oat-domain-select').on('change', function() {
				var domain = $(this).val();
				$('#oat-domain-fields-container').empty();
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
				if (!formSlug) { return; }
				loadFields({ form_slug: formSlug, domain: domain });
			});

			$('#oat-frontend-submit-form').on('submit', function(e) {
				e.preventDefault();
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
					}
				});
			}

		})(jQuery);
		</script>
		<?php
	}
}
