<?php
/**
 * Archivist alerts: what is waiting on the signed-in member and which requests they watch changed in the last week,
 * from the same inbox data the OAT inbox shows. Drawn at the top of the OAT dashboard widget and as a tile on
 * owbn-board's My Dashboard. On the Archivist itself the member also chooses their emails there: one per event, or
 * one a day (the toolkit's OAT_Notify_Mail).
 *
 * @package owbn-archivist
 */

defined( 'ABSPATH' ) || exit;

/**
 * The member's inbox, cached for five minutes on sites that ask the Archivist for it.
 *
 * @param int $user_id Member.
 * @return array|WP_Error
 */
function owc_oat_alerts_inbox( $user_id ) {
	if ( ! function_exists( 'owc_oat_get_inbox' ) ) {
		return new WP_Error( 'owc_oat_alerts_no_inbox', 'OAT inbox unavailable.' );
	}
	if ( function_exists( 'owc_oat_is_local' ) && owc_oat_is_local() ) {
		return owc_oat_get_inbox( '' );
	}
	$key    = 'owc_oat_alerts_' . (int) $user_id;
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$inbox = owc_oat_get_inbox( '' );
	if ( is_array( $inbox ) ) {
		set_transient( $key, $inbox, 5 * MINUTE_IN_SECONDS );
	}
	return $inbox;
}

/**
 * The alerts from inbox data: requests waiting on the member, and watched requests changed in the last $days days,
 * newest first. Reads both the Archivist's own inbox shape and the gateway's.
 *
 * @param array $inbox Inbox data.
 * @param int   $days  How far back a change counts.
 * @param int   $now   Current time (Unix).
 * @return array { waiting: array, changed: array }, each item { id, label, title, status, step, ts }.
 */
function owc_oat_alerts_data( $inbox, $days = 7, $now = 0 ) {
	$now  = $now ? (int) $now : time();
	$item = static function ( $e ) {
		$e = (array) $e;
		return array(
			'id'     => (int) ( $e['entry_id'] ?? $e['id'] ?? 0 ),
			'label'  => (string) ( $e['domain_label'] ?? $e['domain'] ?? '' ),
			'title'  => (string) ( $e['title'] ?? '' ),
			'status' => (string) ( $e['status'] ?? '' ),
			'step'   => (string) ( $e['current_step'] ?? $e['assignment_step'] ?? '' ),
			'ts'     => (int) ( $e['updated_ts'] ?? 0 ),
		);
	};
	$waiting = array();
	foreach ( (array) ( $inbox['assignments'] ?? array() ) as $e ) {
		$i = $item( $e );
		if ( $i['id'] > 0 ) {
			$waiting[ $i['id'] ] = $i;
		}
	}
	$changed = array();
	foreach ( (array) ( $inbox['watched'] ?? array() ) as $e ) {
		$i = $item( $e );
		if ( $i['id'] > 0 && $i['ts'] > 0 && $i['ts'] >= $now - $days * DAY_IN_SECONDS && ! isset( $waiting[ $i['id'] ] ) ) {
			$changed[ $i['id'] ] = $i;
		}
	}
	uasort( $changed, static fn( $a, $b ) => $b['ts'] <=> $a['ts'] );
	return array(
		'waiting' => array_values( $waiting ),
		'changed' => array_values( $changed ),
	);
}

/**
 * Where a request opens: this site's own OAT entry page when it has one, else the Archivist through sign-on.
 *
 * @param int $id Request number.
 * @return string
 */
function owc_oat_alerts_entry_url( $id ) {
	$path = '/oat-entry/?oat_entry=' . (int) $id;
	if ( ( function_exists( 'owc_oat_is_local' ) && owc_oat_is_local() ) || get_page_by_path( 'oat-entry' ) ) {
		return home_url( $path );
	}
	return owc_oat_alerts_archivist_url( $path );
}

/**
 * An address on the Archivist that signs the member in and opens $path.
 *
 * @param string $path Path on the Archivist.
 * @return string '' when no Archivist is set.
 */
function owc_oat_alerts_archivist_url( $path ) {
	$base = function_exists( 'owc_get_remote_base' ) ? (string) owc_get_remote_base( 'oat' ) : '';
	$root = preg_replace( '#/wp-json/owbn/v1/?$#', '', $base );
	return '' === $root ? '' : untrailingslashit( $root ) . '/?auth=sso&redirect_uri=' . rawurlencode( $path );
}

/**
 * The alerts panel.
 *
 * @param array $args { compact: bool (the board tile), limit: int }.
 * @return string HTML; '' for a visitor.
 */
function owc_oat_render_alerts( $args = array() ) {
	if ( ! is_user_logged_in() ) {
		return '';
	}
	$args    = wp_parse_args( $args, array( 'compact' => false, 'limit' => 5 ) );
	$limit   = max( 1, (int) $args['limit'] );
	$user_id = get_current_user_id();
	$inbox   = owc_oat_alerts_inbox( $user_id );
	$html    = '<div class="oat-alerts' . ( $args['compact'] ? ' oat-alerts--compact' : '' ) . '">';
	if ( is_wp_error( $inbox ) || ! is_array( $inbox ) ) {
		return $html . '<p class="oat-alerts__empty">' . esc_html__( 'Your Archivist alerts can\'t be shown right now.', 'owbn-archivist' ) . '</p></div>';
	}
	$data = owc_oat_alerts_data( $inbox );
	$list = static function ( $items, $heading, $empty ) use ( $limit ) {
		$out = '<h4 class="oat-alerts__heading">' . esc_html( sprintf( $heading, count( $items ) ) ) . '</h4>';
		if ( ! $items ) {
			return $out . '<p class="oat-alerts__empty">' . esc_html( $empty ) . '</p>';
		}
		$out .= '<ul class="oat-alerts__list">';
		foreach ( array_slice( $items, 0, $limit ) as $i ) {
			$ref  = trim( $i['label'] . ' #' . $i['id'] );
			$ago  = $i['ts'] ? sprintf( /* translators: %s: how long ago */ __( '%s ago', 'owbn-archivist' ), human_time_diff( $i['ts'] ) ) : '';
			$name = '' !== $i['title'] ? $i['title'] : $ref;
			$meta = implode( ' · ', array_filter( array( '' !== $i['title'] ? '#' . $i['id'] : '', $ago ) ) );
			$out .= '<li class="oat-alerts__item"><a href="' . esc_url( owc_oat_alerts_entry_url( $i['id'] ) ) . '">' . esc_html( $name ) . '</a> <span class="oat-alerts__meta">' . esc_html( $meta ) . '</span></li>';
		}
		return $out . '</ul>';
	};
	/* translators: %d: number of requests */
	$html .= $list( $data['waiting'], __( 'Waiting on you (%d)', 'owbn-archivist' ), __( 'Nothing is waiting on you.', 'owbn-archivist' ) );
	/* translators: %d: number of requests */
	$html .= $list( $data['changed'], __( 'Changed this week on requests you watch (%d)', 'owbn-archivist' ), __( 'Nothing you watch changed this week.', 'owbn-archivist' ) );
	$html .= owc_oat_render_notify_choice();
	return $html . '</div>';
}

/**
 * The member's email choice: a form on the Archivist itself, a link to it elsewhere.
 *
 * @return string HTML.
 */
function owc_oat_render_notify_choice() {
	if ( ! ( function_exists( 'owc_oat_is_local' ) && owc_oat_is_local() && class_exists( 'OAT_Notify_Mail' ) ) ) {
		$url = owc_oat_alerts_archivist_url( '/oat-dashboard/' );
		return '' === $url ? '' : '<p class="oat-alerts__email"><a href="' . esc_url( $url ) . '">' . esc_html__( 'Archivist email settings', 'owbn-archivist' ) . '</a></p>';
	}
	$mode  = OAT_Notify_Mail::mode( get_current_user_id() );
	$back  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/oat-dashboard/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$html  = '<form class="oat-alerts__email" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	$html .= '<input type="hidden" name="action" value="owc_oat_notify_mode" />';
	$html .= wp_nonce_field( 'owc_oat_notify_mode', '_owcnonce', true, false );
	$html .= '<input type="hidden" name="back" value="' . esc_attr( $back ) . '" />';
	$html .= '<fieldset><legend>' . esc_html__( 'Archivist emails', 'owbn-archivist' ) . '</legend>';
	foreach ( array( 'each' => __( 'One for each update', 'owbn-archivist' ), 'daily' => __( 'One a day, as a summary', 'owbn-archivist' ) ) as $value => $label ) {
		$html .= '<label><input type="radio" name="mode" value="' . esc_attr( $value ) . '"' . checked( $mode, $value, false ) . ' /> ' . esc_html( $label ) . '</label> ';
	}
	$html .= '<button type="submit" class="oat-btn">' . esc_html__( 'Save', 'owbn-archivist' ) . '</button></fieldset></form>';
	return $html;
}

/**
 * Saves the email choice from the form, then returns to the page it came from.
 */
function owc_oat_save_notify_mode() {
	if ( ! is_user_logged_in() || ! check_admin_referer( 'owc_oat_notify_mode', '_owcnonce' ) || ! class_exists( 'OAT_Notify_Mail' ) ) {
		wp_die( esc_html__( 'That didn\'t work. Go back and try again.', 'owbn-archivist' ), '', array( 'response' => 403 ) );
	}
	$mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'each';
	OAT_Notify_Mail::set_mode( get_current_user_id(), $mode );
	$back = isset( $_POST['back'] ) ? esc_url_raw( wp_unslash( $_POST['back'] ) ) : '';
	wp_safe_redirect( wp_validate_redirect( $back, home_url( '/oat-dashboard/' ) ) );
	exit;
}
add_action( 'admin_post_owc_oat_notify_mode', 'owc_oat_save_notify_mode' );

/**
 * The Archivist alerts tile on owbn-board's My Dashboard, for every signed-in member.
 */
function owc_oat_register_alerts_tile() {
	if ( ! function_exists( 'owbn_board_register_tile' ) ) {
		return;
	}
	owbn_board_register_tile(
		array(
			'id'          => 'oat:alerts',
			'title'       => __( 'Archivist alerts', 'owbn-archivist' ),
			'icon'        => 'dashicons-bell',
			'read_roles'  => array(),
			'write_roles' => array(),
			'size'        => '1x2',
			'category'    => 'communication',
			'tab'         => 'comms',
			'priority'    => 5,
			'render'      => 'owc_oat_render_alerts_tile',
		)
	);
}
add_action( 'owbn_board_register_tiles', 'owc_oat_register_alerts_tile' );

/**
 * Draws the tile.
 *
 * @param array $tile      Tile.
 * @param int   $user_id   Member.
 * @param bool  $can_write Unused.
 */
function owc_oat_render_alerts_tile( $tile = array(), $user_id = 0, $can_write = false ) {
	echo owc_oat_render_alerts( array( 'compact' => true, 'limit' => 5 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in owc_oat_render_alerts().
}
