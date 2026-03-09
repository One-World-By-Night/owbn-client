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
