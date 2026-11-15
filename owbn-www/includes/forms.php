<?php
/**
 * The public site's forms (definitions in forms-definitions.php): installing them with their review steps, filling
 * their pick-lists, routing their email, a per-visitor submission limit, and turning approved news and events into a
 * post or an owbn-board event.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/forms-definitions.php';

/**
 * Form ids by name, as the installer saved them.
 *
 * @return array<string, int>
 */
function owbn_www_form_ids() {
	return array_map( 'intval', (array) get_option( 'owbn_www_forms', array() ) );
}

/**
 * The name of one of these forms by its id, '' for any other form.
 *
 * @param int $form_id Gravity Forms form id.
 * @return string
 */
function owbn_www_form_key( $form_id ) {
	$key = array_search( (int) $form_id, owbn_www_form_ids(), true );
	return false === $key ? '' : (string) $key;
}

/**
 * Creates or updates the four forms, and the review step on news and events.
 *
 * @param int $approver_id User who reviews news and events.
 * @return array<string, int>|WP_Error Form ids by name.
 */
function owbn_www_install_forms( $approver_id ) {
	if ( ! class_exists( 'GFAPI' ) ) {
		return new WP_Error( 'owbn_www_no_gf', 'Gravity Forms is not active.' );
	}
	$ids = owbn_www_form_ids();
	foreach ( owbn_www_form_definitions() as $key => $form ) {
		$id = ! empty( $ids[ $key ] ) && GFAPI::form_id_exists( $ids[ $key ] ) ? (int) $ids[ $key ] : 0;
		if ( $id ) {
			$form['id'] = $id;
			$result     = GFAPI::update_form( $form, $id );
		} else {
			$result = GFAPI::add_form( $form );
			$id     = is_wp_error( $result ) ? 0 : (int) $result;
		}
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$ids[ $key ] = $id;
	}
	update_option( 'owbn_www_forms', $ids, false );
	foreach ( array( 'news' => 3, 'event' => 15 ) as $key => $email_field ) {
		$step = owbn_www_install_review_step( $ids[ $key ], (int) $approver_id, $email_field );
		if ( is_wp_error( $step ) ) {
			return $step;
		}
	}
	return $ids;
}

/**
 * The "Marketing review" approval step on a form: one reviewer, an email to them for each item, and on rejection an
 * email to the submitter with the reviewer's note.
 *
 * @param int $form_id     Form id.
 * @param int $approver_id Reviewer's user id.
 * @param int $email_field Id of the submitter's email field.
 * @return int|WP_Error Step id.
 */
function owbn_www_install_review_step( $form_id, $approver_id, $email_field ) {
	if ( ! class_exists( 'Gravity_Flow_API' ) ) {
		return new WP_Error( 'owbn_www_no_gflow', 'Gravity Flow is not active.' );
	}
	if ( ! $approver_id || ! get_userdata( $approver_id ) ) {
		return new WP_Error( 'owbn_www_no_approver', 'No reviewer user.' );
	}
	$meta = array(
		'step_name'                       => 'Marketing review',
		'step_type'                       => 'approval',
		'description'                     => '',
		'type'                            => 'select',
		'assignees'                       => array( 'user_id|' . $approver_id ),
		'assignee_policy'                 => 'any',
		'assignee_notification_enabled'   => '1',
		'assignee_notification_subject'   => 'For review on owbn.net: {form_title}',
		'assignee_notification_message'   => "Something new is waiting for your review.\n\n{workflow_entry_link}",
		'rejection_notification_enabled'  => '1',
		'rejection_notification_type'     => 'select',
		'rejection_notification_users'    => array( 'email_field|' . $email_field ),
		'rejection_notification_subject'  => 'About what you sent to owbn.net',
		'rejection_notification_message'  => "Thank you for sending this in. It won't run this time.\n\n{workflow_note}",
		'note_mode'                       => 'required_if_rejected',
		'destination_approved'            => 'next',
		'destination_rejected'            => 'complete',
	);
	$feeds = GFAPI::get_feeds( null, $form_id, 'gravityflow' );
	foreach ( is_wp_error( $feeds ) ? array() : $feeds as $feed ) {
		if ( 'Marketing review' === rgars( $feed, 'meta/step_name' ) ) {
			GFAPI::update_feed( $feed['id'], $meta, $form_id );
			return (int) $feed['id'];
		}
	}
	$api = new Gravity_Flow_API( $form_id );
	return (int) $api->add_step( $meta );
}

/**
 * [owbn_www_form key="news"]: one of the four forms, without AJAX (the page works with scripts blocked). The wrapper's
 * side padding takes up the negative margins of Gravity Forms' date and time rows, which would otherwise reach past a
 * phone's edge on a page without its own side padding.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function owbn_www_form_shortcode( $atts ) {
	static $styled = false;
	$atts = shortcode_atts( array( 'key' => '' ), $atts, 'owbn_www_form' );
	$ids  = owbn_www_form_ids();
	if ( empty( $ids[ $atts['key'] ] ) || ! function_exists( 'gravity_form' ) ) {
		return '';
	}
	$style  = $styled ? '' : '<style>.owbn-www-form{padding-inline:8px;max-width:100%;box-sizing:border-box}</style>';
	$styled = true;
	return $style . '<div class="owbn-www-form">' . gravity_form( $ids[ $atts['key'] ], false, false, false, null, false, 0, false ) . '</div>';
}
add_shortcode( 'owbn_www_form', 'owbn_www_form_shortcode' );

/**
 * [owbn_www_listing_link page="/find-a-game/listing-problem/"]: on the chronicle detail page (?slug=), a line linking
 * to the listing-problem form with that chronicle already picked. Nothing for a slug that isn't a listed chronicle.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function owbn_www_listing_link_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'page' => '/find-a-game/listing-problem/' ), $atts, 'owbn_www_listing_link' );
	$slug = isset( $_GET['slug'] ) ? sanitize_title( wp_unslash( $_GET['slug'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- read-only page parameter.
	if ( '' === $slug || ! in_array( 'chronicle/' . $slug, wp_list_pluck( owbn_www_form_choices( 'teams' ), 'value' ), true ) ) {
		return '';
	}
	$url = add_query_arg( 'chronicle', rawurlencode( $slug ), home_url( (string) $atts['page'] ) );
	return '<p class="owbn-www-listing-link">' . esc_html__( 'Something on this page out of date?', 'owbn-www' ) . ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Tell the Head Storyteller', 'owbn-www' ) . '</a></p>';
}
add_shortcode( 'owbn_www_listing_link', 'owbn_www_listing_link_shortcode' );

/**
 * Choices for a pick-list: 'teams' (chronicles, then coordinator offices), 'chronicles', or 'genres'.
 *
 * @param string $source Which list.
 * @return array<int, array{text: string, value: string}>
 */
function owbn_www_form_choices( $source ) {
	$out = array();
	if ( 'genres' === $source ) {
		$genres = function_exists( 'owbn_board_events_genre_choices' ) ? owbn_board_events_genre_choices() : array();
		foreach ( $genres as $g ) {
			$out[] = array( 'text' => $g, 'value' => $g );
		}
		return $out;
	}
	$chronicles = function_exists( 'owc_get_chronicles' ) ? owc_get_chronicles() : array();
	$rows       = array();
	foreach ( is_array( $chronicles ) ? $chronicles : array() as $c ) {
		$c = (array) $c;
		if ( 'publish' === ( $c['status'] ?? 'publish' ) && ! empty( $c['slug'] ) ) {
			$rows[] = array( 'text' => (string) $c['title'], 'value' => ( 'teams' === $source ? 'chronicle/' : '' ) . $c['slug'] );
		}
	}
	usort( $rows, static fn( $a, $b ) => strcasecmp( $a['text'], $b['text'] ) );
	$out = $rows;
	if ( 'teams' === $source ) {
		$offices = function_exists( 'owc_get_coordinators' ) ? owc_get_coordinators() : array();
		$rows    = array();
		foreach ( is_array( $offices ) ? $offices : array() as $c ) {
			$c = (array) $c;
			if ( ! empty( $c['slug'] ) ) {
				$rows[] = array( 'text' => (string) $c['title'], 'value' => 'coordinator/' . $c['slug'] );
			}
		}
		usort( $rows, static fn( $a, $b ) => strcasecmp( $a['text'], $b['text'] ) );
		$out = array_merge( $out, $rows );
	}
	return $out;
}

/**
 * Fills the pick-lists of these forms each time one is shown, checked or saved.
 *
 * @param array $form Gravity Forms form.
 * @return array
 */
function owbn_www_fill_choices( $form ) {
	if ( '' === owbn_www_form_key( rgar( $form, 'id' ) ) ) {
		return $form;
	}
	foreach ( $form['fields'] as $field ) {
		if ( ! preg_match( '/owbn-www-choices-(teams|chronicles|genres)/', (string) $field->cssClass, $m ) ) {
			continue;
		}
		$choices = owbn_www_form_choices( $m[1] );
		if ( 'checkbox' === $field->type ) {
			$inputs = array();
			$n      = 0;
			foreach ( $choices as $choice ) {
				$n++;
				if ( 0 === $n % 10 ) {
					$n++;
				}
				$inputs[] = array( 'id' => $field->id . '.' . $n, 'label' => $choice['text'] );
			}
			$field->choices = $choices;
			$field->inputs  = $inputs;
		} else {
			$field->choices = $choices;
		}
	}
	return $form;
}
foreach ( array( 'gform_pre_render', 'gform_pre_validation', 'gform_pre_submission_filter', 'gform_admin_pre_render' ) as $owbn_www_hook ) {
	add_filter( $owbn_www_hook, 'owbn_www_fill_choices' );
}
unset( $owbn_www_hook );

/**
 * A pick-list answer must be one of its choices (Gravity Forms accepts any posted value for a select or a checkbox).
 *
 * @param array $result Gravity Forms validation result.
 * @return array
 */
function owbn_www_validate_choices( $result ) {
	$form = $result['form'];
	if ( '' === owbn_www_form_key( rgar( $form, 'id' ) ) ) {
		return $result;
	}
	foreach ( $form['fields'] as $field ) {
		if ( ! preg_match( '/owbn-www-choices-/', (string) $field->cssClass ) ) {
			continue;
		}
		$allowed = array_map( 'strval', wp_list_pluck( (array) $field->choices, 'value' ) );
		$values  = array();
		if ( 'checkbox' === $field->type ) {
			foreach ( (array) $field->inputs as $input ) {
				$values[] = (string) rgpost( 'input_' . str_replace( '.', '_', (string) $input['id'] ) );
			}
		} else {
			$values[] = (string) rgpost( 'input_' . $field->id );
		}
		foreach ( array_filter( $values, 'strlen' ) as $value ) {
			if ( ! in_array( $value, $allowed, true ) ) {
				$field->failed_validation  = true;
				$field->validation_message = __( 'Please choose from the list.', 'owbn-www' );
				$result['is_valid']        = false;
				break;
			}
		}
	}
	$result['form'] = $form;
	return $result;
}
add_filter( 'gform_validation', 'owbn_www_validate_choices', 9 );

/**
 * Public addresses of coordinator offices, by office slug, from the coordinator list.
 *
 * @param string[] $slugs Office slugs.
 * @return string[]
 */
function owbn_www_office_emails( array $slugs ) {
	$out     = array();
	$offices = function_exists( 'owc_get_coordinators' ) ? owc_get_coordinators() : array();
	foreach ( is_array( $offices ) ? $offices : array() as $c ) {
		$c = (array) $c;
		if ( in_array( (string) ( $c['slug'] ?? '' ), $slugs, true ) ) {
			$email = (string) ( ( (array) ( $c['coord_info'] ?? array() ) )['display_email'] ?? '' );
			if ( is_email( $email ) ) {
				$out[] = $email;
			}
		}
	}
	return array_values( array_unique( $out ) );
}

/**
 * Sends the two private forms to the right people: a bad first game to the Executive Team (HC, AHC1, AHC2), a listing
 * problem to that chronicle's Head Storyteller with the web team copied. With the option owbn_www_forms_inbox set (a
 * site that isn't live yet), both go to that one address instead, with the intended recipients in the subject.
 *
 * @param array $notification Gravity Forms notification.
 * @param array $form         Form.
 * @param array $entry        Entry.
 * @return array
 */
function owbn_www_route_notification( $notification, $form, $entry ) {
	$id = (string) rgar( $notification, 'id' );
	if ( 'owbnwwwexec' === $id ) {
		$to                 = owbn_www_office_emails( array( 'hc', 'ahc1', 'ahc2' ) );
		$notification['to'] = $to ? implode( ',', $to ) : 'hc@owbn.net';
	} elseif ( 'owbnwwwhst' === $id ) {
		$web  = owbn_www_office_emails( array( 'web' ) );
		$web  = $web ? $web[0] : 'web@owbn.net';
		$slug = (string) rgar( $entry, '1' );
		$hst  = '';
		$list = function_exists( 'owc_get_chronicles' ) ? owc_get_chronicles() : array();
		foreach ( is_array( $list ) ? $list : array() as $c ) {
			$c = (array) $c;
			if ( $slug === ( $c['slug'] ?? '' ) ) {
				$info = (array) ( $c['hst_info'] ?? array() );
				$hst  = is_email( (string) ( $info['display_email'] ?? '' ) ) ? (string) $info['display_email'] : '';
			}
		}
		$notification['to'] = $hst ? $hst : $web;
		$notification['cc'] = $hst ? $web : '';
	}
	$inbox = sanitize_email( (string) get_option( 'owbn_www_forms_inbox', '' ) );
	if ( $inbox && in_array( $id, array( 'owbnwwwexec', 'owbnwwwhst' ), true ) ) {
		$notification['subject'] = '[' . wp_parse_url( home_url(), PHP_URL_HOST ) . ', for ' . $notification['to'] . '] ' . $notification['subject'];
		$notification['to']      = $inbox;
		$notification['cc']      = '';
	}
	return $notification;
}
add_filter( 'gform_notification', 'owbn_www_route_notification', 10, 3 );

/**
 * Gravity Forms drops a notification's Cc unless this says yes: yes for the listing-problem email.
 *
 * @param bool  $enabled      Cc on or off.
 * @param array $notification Notification.
 * @return bool
 */
function owbn_www_enable_cc( $enabled, $notification ) {
	return 'owbnwwwhst' === (string) rgar( $notification, 'id' ) ? true : $enabled;
}
add_filter( 'gform_notification_enable_cc', 'owbn_www_enable_cc', 10, 2 );

/**
 * A field people never see (moved off screen, skipped by the keyboard and screen readers) that bots fill in. It is
 * rendered by the server, so it works without scripts, unlike Gravity Forms' own honeypot.
 *
 * @param string $button Submit button HTML.
 * @param array  $form   Form.
 * @return string
 */
function owbn_www_honeypot_field( $button, $form ) {
	if ( '' === owbn_www_form_key( rgar( $form, 'id' ) ) ) {
		return $button;
	}
	$id = 'owbn_www_hp_' . (int) $form['id'];
	return '<div class="owbn-www-hp" aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;">'
		. '<label for="' . esc_attr( $id ) . '">' . esc_html__( 'Leave this field empty', 'owbn-www' ) . '</label>'
		. '<input type="text" id="' . esc_attr( $id ) . '" name="owbn_www_hp" value="" tabindex="-1" autocomplete="off" />'
		. '</div>' . $button;
}
add_filter( 'gform_submit_button', 'owbn_www_honeypot_field', 10, 2 );

/**
 * An entry whose hidden field was filled in is spam: saved as spam, no email, no review step.
 *
 * @param bool  $is_spam Spam so far.
 * @param array $form    Form.
 * @param array $entry   Entry.
 * @return bool
 */
function owbn_www_honeypot_check( $is_spam, $form, $entry ) {
	if ( $is_spam || '' === owbn_www_form_key( rgar( $form, 'id' ) ) ) {
		return $is_spam;
	}
	$value = isset( $_POST['owbn_www_hp'] ) ? trim( (string) wp_unslash( $_POST['owbn_www_hp'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- compared with '', never stored.
	if ( '' === $value ) {
		return false;
	}
	if ( method_exists( 'GFCommon', 'set_spam_filter' ) ) {
		GFCommon::set_spam_filter( (int) $form['id'], 'owbn-www honeypot', 'The hidden field was filled in.' );
	}
	return true;
}
add_filter( 'gform_entry_is_spam', 'owbn_www_honeypot_check', 10, 3 );

/**
 * The key a visitor's submissions to one form are counted under (the address is hashed, never stored).
 *
 * @param int $form_id Form id.
 * @return string
 */
function owbn_www_limit_key( $form_id ) {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- hashed, never output.
	return 'owbn_www_rl_' . md5( wp_salt( 'nonce' ) . '|' . $ip . '|' . (int) $form_id );
}

/**
 * At most five submissions an hour from one visitor to one of these forms.
 *
 * @param array $result Gravity Forms validation result.
 * @return array
 */
function owbn_www_limit_submissions( $result ) {
	$form = $result['form'];
	if ( '' === owbn_www_form_key( rgar( $form, 'id' ) ) || ! $result['is_valid'] ) {
		return $result;
	}
	$times = array_filter( (array) get_transient( owbn_www_limit_key( $form['id'] ) ), static fn( $t ) => (int) $t > time() - HOUR_IN_SECONDS );
	if ( count( $times ) >= 5 ) {
		$result['is_valid'] = false;
		foreach ( $form['fields'] as $field ) {
			$field->failed_validation  = true;
			$field->validation_message = __( 'You have sent this form several times in the last hour. Please try again later.', 'owbn-www' );
			break;
		}
		$result['form'] = $form;
	}
	return $result;
}
add_filter( 'gform_validation', 'owbn_www_limit_submissions' );

/**
 * Counts a submission towards the limit.
 *
 * @param array $entry Entry.
 * @param array $form  Form.
 */
function owbn_www_count_submission( $entry, $form ) {
	if ( '' === owbn_www_form_key( rgar( $form, 'id' ) ) ) {
		return;
	}
	$key     = owbn_www_limit_key( $form['id'] );
	$times   = array_filter( (array) get_transient( $key ), static fn( $t ) => (int) $t > time() - HOUR_IN_SECONDS );
	$times[] = time();
	set_transient( $key, array_values( $times ), HOUR_IN_SECONDS );
}
add_action( 'gform_after_submission', 'owbn_www_count_submission', 10, 2 );

/**
 * When the review step approves an item, publishes it: news as a post, an event as an owbn-board event. Runs once per
 * entry.
 *
 * @param int    $step_id  Step id.
 * @param int    $entry_id Entry id.
 * @param int    $form_id  Form id.
 * @param string $status   Step status.
 * @param object $step     Gravity Flow step.
 */
function owbn_www_publish_on_approval( $step_id, $entry_id, $form_id, $status, $step ) {
	$key = owbn_www_form_key( $form_id );
	if ( 'approved' !== $status || ! in_array( $key, array( 'news', 'event' ), true ) || ! is_object( $step ) || 'approval' !== $step->get_type() ) {
		return;
	}
	if ( gform_get_meta( $entry_id, 'owbn_www_post_id' ) ) {
		return;
	}
	$entry  = GFAPI::get_entry( $entry_id );
	$result = 'news' === $key ? owbn_www_publish_news( $entry ) : owbn_www_publish_event( $entry );
	if ( is_wp_error( $result ) ) {
		if ( class_exists( 'Gravity_Flow_API' ) ) {
			( new Gravity_Flow_API( $form_id ) )->add_timeline_note( $entry_id, 'Not published: ' . $result->get_error_message() );
		}
		return;
	}
	gform_update_meta( $entry_id, 'owbn_www_post_id', $result );
	owbn_www_purge_listing_pages();
}
add_action( 'gravityflow_step_complete', 'owbn_www_publish_on_approval', 10, 5 );

/**
 * Clears SiteGround's page cache for the pages that list news and events (Home and News & Events, in both languages),
 * so an approved item shows at once. Does nothing without the SiteGround Speed Optimizer plugin.
 */
function owbn_www_purge_listing_pages() {
	if ( ! function_exists( 'sg_cachepress_purge_cache' ) ) {
		return;
	}
	foreach ( (array) apply_filters( 'owbn_www_purge_paths', array( '/', '/news/', '/pt/', '/pt/news/' ) ) as $path ) {
		sg_cachepress_purge_cache( home_url( $path ) );
	}
}

/**
 * Who approved posts are written by: the reviewer, else the first administrator.
 *
 * @return int
 */
function owbn_www_post_author() {
	$id = get_current_user_id();
	if ( $id ) {
		return $id;
	}
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
	return (int) ( $admins[0] ?? 0 );
}

/**
 * An uploaded form file as a media library item attached to a post.
 *
 * @param string $url     The file's address as Gravity Forms stored it.
 * @param int    $post_id Post to attach to.
 * @return int Attachment id, 0 when it can't be read.
 */
function owbn_www_attach_upload( $url, $post_id ) {
	$url = trim( (string) $url );
	if ( '' === $url || ! class_exists( 'GFFormsModel' ) ) {
		return 0;
	}
	$path = GFFormsModel::get_physical_file_path( $url );
	if ( ! $path || ! is_readable( $path ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( basename( $path ) );
	if ( ! $tmp || ! copy( $path, $tmp ) ) {
		return 0;
	}
	$id = media_handle_sideload( array( 'name' => basename( $path ), 'tmp_name' => $tmp ), $post_id );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp );
		return 0;
	}
	return (int) $id;
}

/**
 * Publishes approved news as a post.
 *
 * @param array $entry Entry of the news form.
 * @return int|WP_Error Post id.
 */
function owbn_www_publish_news( $entry ) {
	$post_id = wp_insert_post(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'post_title'     => wp_strip_all_tags( (string) rgar( $entry, '1' ) ),
			'post_content'   => wpautop( esc_html( (string) rgar( $entry, '5' ) ) ),
			'post_author'    => owbn_www_post_author(),
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}
	update_post_meta( $post_id, 'owbn_www_submitted_by', sanitize_text_field( (string) rgar( $entry, '2' ) ) );
	update_post_meta( $post_id, 'owbn_www_team', sanitize_text_field( (string) rgar( $entry, '4' ) ) );
	update_post_meta( $post_id, 'owbn_www_entry', (int) $entry['id'] );
	$image = owbn_www_attach_upload( (string) rgar( $entry, '6' ), $post_id );
	if ( $image ) {
		set_post_thumbnail( $post_id, $image );
	}
	return (int) $post_id;
}

/**
 * A chronicle's time zone, for an event it hosts; the site's for anything else.
 *
 * @param string $host "chronicle/<slug>" or "coordinator/<slug>".
 * @return DateTimeZone
 */
function owbn_www_host_timezone( $host ) {
	if ( 0 === strpos( (string) $host, 'chronicle/' ) && function_exists( 'owc_get_chronicles' ) ) {
		$slug = substr( (string) $host, strlen( 'chronicle/' ) );
		foreach ( (array) owc_get_chronicles() as $c ) {
			$c  = (array) $c;
			$tz = (string) ( $c['timezone'] ?? '' );
			if ( $slug === ( $c['slug'] ?? '' ) && '' !== $tz && 'UTC' !== $tz && in_array( $tz, DateTimeZone::listIdentifiers(), true ) ) {
				return new DateTimeZone( $tz );
			}
		}
	}
	return wp_timezone();
}

/**
 * A date and a time from the form, in a time zone, as UTC ("2026-12-04 23:00:00").
 *
 * @param string       $date "2026-12-04".
 * @param string       $time "18:00" or "6:00 pm".
 * @param DateTimeZone $tz   Time zone.
 * @return string '' when they aren't a date and a time.
 */
function owbn_www_to_utc( $date, $time, DateTimeZone $tz ) {
	$when = date_create( trim( (string) $date ) . ' ' . trim( (string) $time ), $tz );
	if ( ! $when ) {
		return '';
	}
	return $when->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
}

/**
 * Publishes an approved event as an owbn-board event (this site must hold the events).
 *
 * @param array $entry Entry of the event form.
 * @return int|WP_Error Event post id.
 */
function owbn_www_publish_event( $entry ) {
	if ( ! function_exists( 'owbn_board_events_save_meta' ) || ! function_exists( 'owbn_board_events_is_host' ) || ! owbn_board_events_is_host() || ! post_type_exists( 'owbn_event' ) ) {
		return new WP_Error( 'owbn_www_no_events', 'this site does not hold the events (owbn-board\'s events module, owbn_board_events_host)' );
	}
	$host  = sanitize_text_field( (string) rgar( $entry, '2' ) );
	$tz    = owbn_www_host_timezone( $host );
	$start = owbn_www_to_utc( rgar( $entry, '5' ), rgar( $entry, '6' ), $tz );
	$end   = owbn_www_to_utc( rgar( $entry, '7' ), rgar( $entry, '8' ), $tz );
	if ( '' === $start ) {
		return new WP_Error( 'owbn_www_bad_date', 'the start date or time could not be read' );
	}
	$genres = array();
	foreach ( $entry as $k => $v ) {
		if ( preg_match( '/^3\.\d+$/', (string) $k ) && '' !== (string) $v ) {
			$genres[] = sanitize_text_field( (string) $v );
		}
	}
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'owbn_event',
			'post_status'  => 'publish',
			'post_title'   => wp_strip_all_tags( (string) rgar( $entry, '1' ) ),
			'post_content' => wpautop( esc_html( (string) rgar( $entry, '12' ) ) ),
			'post_author'  => owbn_www_post_author(),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}
	$banner = owbn_www_attach_upload( (string) rgar( $entry, '13' ), $post_id );
	owbn_board_events_save_meta(
		$post_id,
		array(
			'start_dt'         => $start,
			'end_dt'           => '' !== $end ? $end : $start,
			'timezone'         => $tz->getName(),
			'location'         => sanitize_text_field( (string) rgar( $entry, '4' ) ),
			'host_scope'       => $host,
			'registration_fee' => sanitize_text_field( (string) rgar( $entry, '9' ) ),
			'registration_url' => esc_url_raw( (string) rgar( $entry, '10' ) ),
			'website'          => esc_url_raw( (string) rgar( $entry, '11' ) ),
			'banner_image_id'  => $banner,
			'genres'           => $genres,
		)
	);
	return (int) $post_id;
}
