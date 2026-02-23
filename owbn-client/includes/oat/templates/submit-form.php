<?php
/**
 * OAT Client - Submit Form Template
 * location: includes/oat/templates/submit-form.php
 *
 * Variables available:
 *   $domains         array  Domain list ({ slug, label }).
 *   $selected_domain string Currently selected domain slug.
 *   $domain_fields   array  Form field definitions for selected domain.
 *   $error           string Error message (if any).
 *   $success         string Success message (if any).
 *
 * @package OWBN-Client
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

        <!-- Domain-specific fields rendered by JS (AJAX) or server-side on reload -->
        <div id="owc-oat-domain-fields">
            <?php if ( ! empty( $domain_fields ) ) : ?>
                <table class="form-table">
                    <?php foreach ( $domain_fields as $field ) :
                        $cond_attrs = '';
                        if ( ! empty( $field['condition'] ) && is_array( $field['condition'] ) ) {
                            $cond_key = key( $field['condition'] );
                            $cond_val = $field['condition'][ $cond_key ];
                            $cond_attrs = ' data-condition-field="' . esc_attr( $cond_key ) . '" data-condition-value="' . esc_attr( $cond_val ) . '"';
                        }
                    ?>
                        <tr<?php echo $cond_attrs; ?>>
                            <th><label for="oat_meta_<?php echo esc_attr( $field['key'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
                            <td>
                                <?php if ( $field['type'] === 'select' && ! empty( $field['options'] ) ) : ?>
                                    <select name="oat_meta_<?php echo esc_attr( $field['key'] ); ?>" id="oat_meta_<?php echo esc_attr( $field['key'] ); ?>" <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>>
                                        <option value="">Select...</option>
                                        <?php foreach ( $field['options'] as $val => $label ) : ?>
                                            <option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ( $field['type'] === 'textarea' ) : ?>
                                    <textarea name="oat_meta_<?php echo esc_attr( $field['key'] ); ?>" id="oat_meta_<?php echo esc_attr( $field['key'] ); ?>" rows="4" class="large-text" <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>></textarea>
                                <?php elseif ( $field['type'] === 'rule_picker' ) : ?>
                                    <div class="oat-rule-picker">
                                        <input type="text" id="oat_rule_search" placeholder="Search regulation rules..." class="regular-text" autocomplete="off">
                                        <div id="oat-selected-rules"></div>
                                    </div>
                                <?php elseif ( $field['type'] === 'number' ) : ?>
                                    <input type="number" name="oat_meta_<?php echo esc_attr( $field['key'] ); ?>" id="oat_meta_<?php echo esc_attr( $field['key'] ); ?>" class="small-text" <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>>
                                <?php else : ?>
                                    <input type="text" name="oat_meta_<?php echo esc_attr( $field['key'] ); ?>" id="oat_meta_<?php echo esc_attr( $field['key'] ); ?>" class="regular-text" <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <table class="form-table">
            <tr>
                <th><label for="oat_chronicle_slug">Chronicle</label></th>
                <td><input type="text" name="oat_chronicle_slug" id="oat_chronicle_slug" class="regular-text" placeholder="Chronicle slug (optional)"></td>
            </tr>
            <tr>
                <th><label for="oat_coordinator_genre">Coordinator Genre</label></th>
                <td><input type="text" name="oat_coordinator_genre" id="oat_coordinator_genre" class="regular-text" placeholder="Genre (optional, auto-set from rules)"></td>
            </tr>
            <tr>
                <th><label for="oat_note">Note</label></th>
                <td><textarea name="oat_note" id="oat_note" rows="3" class="large-text" placeholder="Optional note with your submission"></textarea></td>
            </tr>
        </table>

        <?php submit_button( 'Submit Entry' ); ?>
    </form>
</div>
