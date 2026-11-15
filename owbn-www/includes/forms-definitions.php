<?php
/**
 * The public site's four Gravity Forms, as definitions kept in git: submit news, submit an event, report a bad first
 * game, and something wrong with a chronicle's listing. Every field works with JavaScript blocked (the site's
 * content-security policy only allows the inline scripts that were in the page as served): plain date and time
 * inputs, single file uploads, no conditional logic, no AJAX. Gravity Forms' own honeypot is off because it needs its
 * scripts (forms.php has one that doesn't).
 */

defined( 'ABSPATH' ) || exit;

/**
 * A select field whose choices are filled when the form is shown (see owbn_www_form_choices()).
 *
 * @param int    $id       Field id.
 * @param string $label    Label.
 * @param string $source   'teams' (chronicles and coordinator offices), 'chronicles' or 'genres'.
 * @param bool   $required Required or not.
 * @param array  $extra    More field settings.
 * @return array
 */
function owbn_www_form_choice_field( $id, $label, $source, $required = true, array $extra = array() ) {
	return array_merge(
		array(
			'type'        => 'genres' === $source ? 'checkbox' : 'select',
			'id'          => $id,
			'label'       => $label,
			'isRequired'  => $required,
			'cssClass'    => 'owbn-www-choices-' . $source,
			'choices'     => array( array( 'text' => '', 'value' => '' ) ),
			'placeholder' => 'genres' === $source ? '' : __( 'Choose one', 'owbn-www' ),
		),
		$extra
	);
}

/**
 * The four forms, keyed by a stable name; the installer creates or updates each one.
 *
 * @return array<string, array>
 */
function owbn_www_form_definitions() {
	$image  = static function ( $id, $label ) {
		return array( 'type' => 'fileupload', 'id' => $id, 'label' => $label, 'isRequired' => false, 'allowedExtensions' => 'jpg,jpeg,png,webp', 'maxFileSize' => 5, 'multipleFiles' => false, 'description' => __( 'Optional. JPG, PNG or WebP, up to 5 MB.', 'owbn-www' ) );
	};
	$common = array(
		'markupVersion'        => 2,
		'labelPlacement'       => 'top_label',
		'descriptionPlacement' => 'below',
		'enableHoneypot'       => false,
		'requireLogin'         => false,
		'is_active'            => '1',
	);
	$confirm = static function ( $message ) {
		return array( 'owbnwww0' => array( 'id' => 'owbnwww0', 'name' => 'Default Confirmation', 'isDefault' => true, 'type' => 'message', 'message' => $message, 'disableAutoformat' => false ) );
	};
	return array(
		'news'            => $common + array(
			'title'         => 'Submit news',
			'description'   => __( 'News from a chronicle or coordinator team. Marketing reviews every item before it goes up and writes back if there is a question.', 'owbn-www' ),
			'button'        => array( 'type' => 'text', 'text' => __( 'Send for review', 'owbn-www' ) ),
			'fields'        => array(
				array( 'type' => 'text', 'id' => 1, 'label' => __( 'Title', 'owbn-www' ), 'isRequired' => true, 'maxLength' => 120 ),
				array( 'type' => 'text', 'id' => 2, 'label' => __( 'Your name', 'owbn-www' ), 'isRequired' => true, 'defaultValue' => '{user:display_name}' ),
				array( 'type' => 'email', 'id' => 3, 'label' => __( 'Your email', 'owbn-www' ), 'isRequired' => true, 'defaultValue' => '{user:user_email}', 'description' => __( 'Only Marketing sees it, to ask a question or say why an item didn\'t run.', 'owbn-www' ) ),
				owbn_www_form_choice_field( 4, __( 'Chronicle or coordinator team', 'owbn-www' ), 'teams' ),
				array( 'type' => 'textarea', 'id' => 5, 'label' => __( 'The news', 'owbn-www' ), 'isRequired' => true, 'useRichTextEditor' => false ),
				$image( 6, __( 'Image', 'owbn-www' ) ),
			),
			'confirmations' => $confirm( __( 'Thank you. Marketing will review your news and email you if there is a question.', 'owbn-www' ) ),
			'notifications' => array(),
		),
		'event'           => $common + array(
			'title'         => 'Submit an event',
			'description'   => __( 'A convention, special game or other event players can plan around. Marketing reviews every event before it goes up.', 'owbn-www' ),
			'button'        => array( 'type' => 'text', 'text' => __( 'Send for review', 'owbn-www' ) ),
			'fields'        => array(
				array( 'type' => 'text', 'id' => 1, 'label' => __( 'Name of the event', 'owbn-www' ), 'isRequired' => true, 'maxLength' => 120 ),
				owbn_www_form_choice_field( 2, __( 'Hosted by', 'owbn-www' ), 'teams' ),
				owbn_www_form_choice_field( 3, __( 'Genres', 'owbn-www' ), 'genres', false ),
				array( 'type' => 'text', 'id' => 4, 'label' => __( 'Location', 'owbn-www' ), 'isRequired' => true, 'description' => __( 'Venue and city, or "Online".', 'owbn-www' ) ),
				array( 'type' => 'date', 'id' => 5, 'label' => __( 'Start date', 'owbn-www' ), 'isRequired' => true, 'dateType' => 'datefield', 'dateFormat' => 'ymd_dash' ),
				array( 'type' => 'time', 'id' => 6, 'label' => __( 'Start time', 'owbn-www' ), 'isRequired' => true, 'timeFormat' => '24' ),
				array( 'type' => 'date', 'id' => 7, 'label' => __( 'End date', 'owbn-www' ), 'isRequired' => true, 'dateType' => 'datefield', 'dateFormat' => 'ymd_dash' ),
				array( 'type' => 'time', 'id' => 8, 'label' => __( 'End time', 'owbn-www' ), 'isRequired' => true, 'timeFormat' => '24', 'description' => __( 'Times in the host chronicle\'s own time zone.', 'owbn-www' ) ),
				array( 'type' => 'text', 'id' => 9, 'label' => __( 'Cost', 'owbn-www' ), 'isRequired' => false, 'description' => __( 'For example "$25", "Free".', 'owbn-www' ) ),
				array( 'type' => 'website', 'id' => 10, 'label' => __( 'Registration link', 'owbn-www' ), 'isRequired' => false ),
				array( 'type' => 'website', 'id' => 11, 'label' => __( 'Event website', 'owbn-www' ), 'isRequired' => false ),
				array( 'type' => 'textarea', 'id' => 12, 'label' => __( 'About the event', 'owbn-www' ), 'isRequired' => true, 'useRichTextEditor' => false, 'description' => __( 'What players need: the schedule, sign-in times, payment methods, a room block, anything else.', 'owbn-www' ) ),
				$image( 13, __( 'Banner image', 'owbn-www' ) ),
				array( 'type' => 'text', 'id' => 14, 'label' => __( 'Your name', 'owbn-www' ), 'isRequired' => true, 'defaultValue' => '{user:display_name}' ),
				array( 'type' => 'email', 'id' => 15, 'label' => __( 'Your email', 'owbn-www' ), 'isRequired' => true, 'defaultValue' => '{user:user_email}', 'description' => __( 'Only Marketing sees it.', 'owbn-www' ) ),
			),
			'confirmations' => $confirm( __( 'Thank you. Marketing will review the event and email you if there is a question.', 'owbn-www' ) ),
			'notifications' => array(),
		),
		'bad-first-game'  => $common + array(
			'title'         => 'Tell us about a bad first game',
			'description'   => __( 'This goes privately to OWBN\'s Executive Team, never to the chronicle.', 'owbn-www' ),
			'button'        => array( 'type' => 'text', 'text' => __( 'Send', 'owbn-www' ) ),
			'fields'        => array(
				array( 'type' => 'text', 'id' => 1, 'label' => __( 'Your name', 'owbn-www' ), 'isRequired' => false, 'description' => __( 'Optional.', 'owbn-www' ) ),
				array( 'type' => 'email', 'id' => 2, 'label' => __( 'Your email', 'owbn-www' ), 'isRequired' => true, 'description' => __( 'So the Executive Team can write back.', 'owbn-www' ) ),
				owbn_www_form_choice_field( 3, __( 'Which chronicle?', 'owbn-www' ), 'chronicles' ),
				array( 'type' => 'textarea', 'id' => 4, 'label' => __( 'What happened?', 'owbn-www' ), 'isRequired' => true, 'useRichTextEditor' => false ),
			),
			'confirmations' => $confirm( __( 'Thank you for telling us. The Executive Team reads every report.', 'owbn-www' ) ),
			'notifications' => array(
				'owbnwwwexec' => array( 'id' => 'owbnwwwexec', 'name' => 'owbn-www: Executive Team', 'event' => 'form_submission', 'isActive' => true, 'toType' => 'email', 'to' => 'hc@owbn.net', 'subject' => 'A bad first game at {Which chronicle?:3}', 'message' => '{all_fields}', 'replyTo' => '{Your email:2}', 'from' => '{admin_email}', 'fromName' => 'OWBN website' ),
			),
		),
		'listing-problem' => $common + array(
			'title'         => 'Something wrong with this listing?',
			'description'   => __( 'Tell the chronicle\'s Head Storyteller what is out of date. The web team gets a copy.', 'owbn-www' ),
			'button'        => array( 'type' => 'text', 'text' => __( 'Send', 'owbn-www' ) ),
			'fields'        => array(
				owbn_www_form_choice_field( 1, __( 'Chronicle', 'owbn-www' ), 'chronicles', true, array( 'allowsPrepopulate' => true, 'inputName' => 'chronicle' ) ),
				array( 'type' => 'textarea', 'id' => 2, 'label' => __( 'What\'s wrong?', 'owbn-www' ), 'isRequired' => true, 'useRichTextEditor' => false ),
				array( 'type' => 'email', 'id' => 3, 'label' => __( 'Your email', 'owbn-www' ), 'isRequired' => false, 'description' => __( 'Optional, if you\'d like an answer.', 'owbn-www' ) ),
			),
			'confirmations' => $confirm( __( 'Thank you. The chronicle\'s Head Storyteller and the web team have it.', 'owbn-www' ) ),
			'notifications' => array(
				'owbnwwwhst' => array( 'id' => 'owbnwwwhst', 'name' => 'owbn-www: Head Storyteller', 'event' => 'form_submission', 'isActive' => true, 'toType' => 'email', 'to' => 'web@owbn.net', 'subject' => 'Your listing on owbn.net: something to fix', 'message' => '{all_fields}', 'replyTo' => '{Your email:3}', 'from' => '{admin_email}', 'fromName' => 'OWBN website' ),
			),
		),
	);
}
