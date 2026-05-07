<?php
/**
 * Lock the WP user email field on satellite sites.
 *
 * Satellites (player_id_mode = 'client') source identity from SSO. Allowing
 * local edits creates the divergence problem player_id was built to handle —
 * we close the gap at the entry layer too.
 *
 * On satellites:
 *   - The email input on user-edit.php / profile.php is read-only.
 *   - Server-side, `wp_pre_insert_user_data` reverts any email change on
 *     update — so even if someone POSTs a new value (browser DOM hack,
 *     direct curl), the DB value is preserved.
 *   - The "change email" confirmation flow is disabled.
 *
 * On the SSO host (player_id_mode = 'server'), nothing changes.
 */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'owc_email_lock_is_satellite' ) ) {
	function owc_email_lock_is_satellite(): bool {
		return get_option( owc_option_name( 'player_id_mode' ), 'client' ) !== 'server';
	}
}

/**
 * Render the email field as read-only on profile screens for satellites.
 * WP renders the input via core; we swap it via JS + CSS so we don't fight
 * the screen's structure. The server-side filter is the actual enforcement.
 */
function owc_email_lock_render_notice( $user ) {
	if ( ! owc_email_lock_is_satellite() ) return;
	?>
	<style>
		#email { background: #f0f0f1 !important; color: #555 !important; cursor: not-allowed; }
		.user-email-wrap .description { color: #d63638; font-weight: 600; }
	</style>
	<script>
	jQuery(function ($) {
		var $email = $('#email');
		if (!$email.length) return;
		$email.prop('readonly', true).attr('aria-readonly', 'true');
		// Some themes use a "change email" confirm flow — defang the submit on this row.
		$email.closest('tr').find('button, input[type="button"]').prop('disabled', true);
		// Prepend a clear notice.
		var msg = '<?php echo esc_js( __( 'This email is sourced from SSO and cannot be changed here. Update it on sso.owbn.net.', 'owbn-core' ) ); ?>';
		if (!$email.siblings('.owc-email-locked').length) {
			$email.after('<p class="description owc-email-locked">' + msg + '</p>');
		}
	});
	</script>
	<?php
}

add_action( 'show_user_profile', 'owc_email_lock_render_notice' );
add_action( 'edit_user_profile', 'owc_email_lock_render_notice' );

/**
 * Server-side enforcement: revert any attempt to change user_email on update.
 *
 * `wp_pre_insert_user_data` fires for both insert and update; the `$update`
 * arg distinguishes them. We only revert on update — new user creation still
 * needs an email.
 */
function owc_email_lock_revert_changes( $data, $update, $user_id, $userdata ) {
	if ( ! owc_email_lock_is_satellite() ) return $data;
	if ( ! $update || ! $user_id ) return $data;

	$existing = get_userdata( $user_id );
	if ( ! $existing instanceof WP_User ) return $data;

	if ( isset( $data['user_email'] ) && $data['user_email'] !== $existing->user_email ) {
		$data['user_email'] = $existing->user_email;
	}

	return $data;
}
add_filter( 'wp_pre_insert_user_data', 'owc_email_lock_revert_changes', 10, 4 );

/**
 * Cancel the "change email confirmation" flow on satellites. WP stores the
 * pending new email under user_meta key `_new_email`; clear it before the
 * confirmation handler reads it.
 */
function owc_email_lock_cancel_pending_email_change( $user_id ) {
	if ( ! owc_email_lock_is_satellite() ) return;
	delete_user_meta( $user_id, '_new_email' );
}
add_action( 'personal_options_update', 'owc_email_lock_cancel_pending_email_change' );
add_action( 'edit_user_profile_update', 'owc_email_lock_cancel_pending_email_change' );
