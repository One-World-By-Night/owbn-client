<?php
/**
 * Change Notification System
 *
 * Sends HTML email when chronicle or coordinator data changes.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Send a change notification email.
 *
 * @param string $entity_type  'Chronicle' or 'Coordinator'
 * @param string $entity_title The entity title (e.g., 'Sabbat', 'KONY')
 * @param string $slug         The entity slug
 * @param array  $changes      Array of ['field_label' => ['before' => ..., 'after' => ...]]
 * @param string $changed_by   Email or display name of the user who made the change
 * @param int    $post_id      The post ID for the edit link
 * @param bool   $pending      Whether this change requires approval
 * @return bool Whether the email was sent
 */
function owc_send_change_notification( $entity_type, $entity_title, $slug, $changes, $changed_by, $post_id, $pending = false ) {
    $to = get_option( owc_option_name( 'change_notify_email' ), '' );
    if ( empty( $to ) || ! is_email( $to ) ) {
        return false;
    }

    if ( empty( $changes ) ) {
        return false;
    }

    $site_name = get_bloginfo( 'name' );
    $status = $pending ? ' (Pending Approval)' : '';
    $subject = "[{$site_name}] {$entity_type} Updated: {$entity_title}{$status}";

    // Build edit link
    $edit_url = admin_url( "post.php?post={$post_id}&action=edit" );

    // Build HTML
    $html = '<div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; max-width: 600px;">';

    if ( $pending ) {
        $html .= '<p style="margin-bottom: 16px;"><a href="' . esc_url( $edit_url ) . '" style="display: inline-block; padding: 10px 20px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold;">Review &amp; Approve &rarr;</a></p>';
    } else {
        $html .= '<p style="margin-bottom: 16px;"><a href="' . esc_url( $edit_url ) . '" style="color: #0073aa; text-decoration: none;">View in Admin &rarr;</a></p>';
    }

    $html .= '<p><strong>Changed by:</strong> ' . esc_html( $changed_by ) . '<br>';
    $html .= '<strong>Date:</strong> ' . esc_html( current_time( 'Y-m-d H:i T' ) ) . '</p>';

    $html .= '<table style="border-collapse: collapse; width: 100%; margin-top: 12px;">';
    $html .= '<thead><tr>';
    $html .= '<th style="text-align: left; padding: 8px 12px; border: 1px solid #ddd; background: #f5f5f5;">Field</th>';
    $html .= '<th style="text-align: left; padding: 8px 12px; border: 1px solid #ddd; background: #f5f5f5;">Before</th>';
    $html .= '<th style="text-align: left; padding: 8px 12px; border: 1px solid #ddd; background: #f5f5f5;">After</th>';
    $html .= '</tr></thead><tbody>';

    foreach ( $changes as $field_label => $diff ) {
        $before = isset( $diff['before'] ) ? $diff['before'] : '';
        $after  = isset( $diff['after'] ) ? $diff['after'] : '';

        // Format arrays/objects as readable strings
        if ( is_array( $before ) ) {
            $before = owc_format_change_value( $before );
        }
        if ( is_array( $after ) ) {
            $after = owc_format_change_value( $after );
        }

        $html .= '<tr>';
        $html .= '<td style="padding: 8px 12px; border: 1px solid #ddd; font-weight: bold; white-space: nowrap;">' . esc_html( $field_label ) . '</td>';
        $html .= '<td style="padding: 8px 12px; border: 1px solid #ddd; color: #999;">' . esc_html( $before ?: '(empty)' ) . '</td>';
        $html .= '<td style="padding: 8px 12px; border: 1px solid #ddd;">' . esc_html( $after ?: '(empty)' ) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    $html .= '</div>';

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    return wp_mail( $to, $subject, $html, $headers );
}

/**
 * Format a complex value (array/object) into a readable string for email display.
 *
 * @param mixed $value The value to format.
 * @return string Human-readable string.
 */
function owc_format_change_value( $value ) {
    if ( ! is_array( $value ) ) {
        return (string) $value;
    }

    // user_info type: { user: "123", display_name: "John", email: "john@example.com" }
    if ( isset( $value['user'] ) ) {
        $parts = array();
        if ( ! empty( $value['display_name'] ) ) {
            $parts[] = $value['display_name'];
        }
        if ( ! empty( $value['email'] ) ) {
            $parts[] = $value['email'];
        }
        if ( empty( $parts ) && ! empty( $value['user'] ) ) {
            $user = get_user_by( 'id', $value['user'] );
            if ( $user ) {
                $parts[] = $user->display_name;
                $parts[] = $user->user_email;
            }
        }
        return implode( ' — ', $parts ) ?: '(unset)';
    }

    // ast_group type: array of rows with user info
    if ( isset( $value[0] ) && is_array( $value[0] ) ) {
        $names = array();
        foreach ( $value as $row ) {
            if ( ! empty( $row['display_name'] ) ) {
                $names[] = $row['display_name'];
            } elseif ( ! empty( $row['user'] ) ) {
                $user = get_user_by( 'id', $row['user'] );
                $names[] = $user ? $user->display_name : "User #{$row['user']}";
            }
        }
        return implode( ', ', $names ) ?: '(empty list)';
    }

    // Generic array
    return implode( ', ', array_filter( array_map( 'strval', $value ) ) ) ?: '(empty)';
}
