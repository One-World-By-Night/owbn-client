<?php
/**
 * OAT Client - Submit Form Template
 *
 * Variables available:
 *   $domains         array  Domain list ({ slug, label }).
 *   $selected_domain string Currently selected domain slug.
 *   $domain_fields   array  Form field definitions for selected domain.
 *   $error           string Error message (if any).
 *   $success         string Success message (if any).
 *
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
    <h1>New Submission</h1>

    <?php if ( $error ) : ?>
        <div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
    <?php endif; ?>
    <?php if ( $success ) : ?>
        <div class="notice notice-success"><p><?php echo esc_html( $success ); ?></p></div>
    <?php endif; ?>

    <form method="post" id="owc-oat-submit-form">
        <?php wp_nonce_field( 'owc_oat_submit' ); ?>

        <table class="form-table">
            <tr>
                <th><label for="oat_domain">Domain</label></th>
                <td>
                    <select name="oat_domain" id="oat_domain" required>
                        <option value="">Select a domain...</option>
                        <?php foreach ( $domains as $d ) : ?>
                            <option value="<?php echo esc_attr( $d['slug'] ); ?>" <?php selected( $selected_domain, $d['slug'] ); ?>><?php echo esc_html( $d['label'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr id="oat-form-picker-row" style="display:none;">
                <th><label for="oat_form_slug">Form</label></th>
                <td>
                    <select name="oat_form_slug" id="oat_form_slug">
                        <option value="">Select a form...</option>
                    </select>
                </td>
            </tr>
        </table>

        <!-- Domain-specific fields rendered by server (AJAX or page reload) -->
        <div id="owc-oat-domain-fields">
            <?php
            if ( ! empty( $domain_fields ) ) {
                owc_oat_render_fields( $domain_fields );
            }
            ?>
        </div>

        <?php submit_button( 'Submit Entry' ); ?>
    </form>
</div>

<script>
jQuery(function($) {
    function loadFields(params) {
        var $container = $('#owc-oat-domain-fields');
        $container.html('<p>Loading fields...</p>');
        params.action = 'owc_oat_get_domain_fields';
        params.nonce = typeof owc_oat_ajax !== 'undefined' ? owc_oat_ajax.nonce : '';
        var url = typeof owc_oat_ajax !== 'undefined' ? owc_oat_ajax.url : ajaxurl;
        $.post(url, params, function(response) {
            if (response.success && response.data && response.data.html) {
                $container.html(response.data.html);
                $(document).trigger('oat-fields-loaded', [$container]);
            } else {
                $container.html('<p>Could not load fields.</p>');
            }
        });
    }

    function checkFormsAndLoad(domain) {
        $('#owc-oat-domain-fields').empty();
        $('#oat-form-picker-row').hide();
        $('#oat_form_slug').html('<option value="">Select a form...</option>');

        if (!domain) return;

        var url = typeof owc_oat_ajax !== 'undefined' ? owc_oat_ajax.url : ajaxurl;
        var nonce = typeof owc_oat_ajax !== 'undefined' ? owc_oat_ajax.nonce : '';

        $.post(url, {
            action: 'owc_oat_get_domain_forms',
            nonce: nonce,
            domain_slug: domain
        }, function(response) {
            var forms = (response.success && response.data) ? response.data : [];
            if (forms.length > 1) {
                var $sel = $('#oat_form_slug');
                $sel.html('<option value="">Select a form...</option>');
                $.each(forms, function(i, f) {
                    $sel.append('<option value="' + f.slug + '">' + f.label + '</option>');
                });
                $('#oat-form-picker-row').show();
            } else if (forms.length === 1) {
                $('#oat_form_slug').html('<option value="' + forms[0].slug + '">' + forms[0].label + '</option>');
                loadFields({ form_slug: forms[0].slug, domain: domain });
            } else {
                loadFields({ domain: domain });
            }
        }).fail(function() {
            loadFields({ domain: domain });
        });
    }

    $('#oat_domain').on('change', function() {
        checkFormsAndLoad($(this).val());
    });

    $('#oat_form_slug').on('change', function() {
        var formSlug = $(this).val();
        var domain = $('#oat_domain').val();
        $('#owc-oat-domain-fields').empty();
        if (formSlug) {
            loadFields({ form_slug: formSlug, domain: domain });
        }
    });

    // If domain is pre-selected on page load, check for forms
    var preselected = $('#oat_domain').val();
    if (preselected) {
        checkFormsAndLoad(preselected);
    }
});
</script>
