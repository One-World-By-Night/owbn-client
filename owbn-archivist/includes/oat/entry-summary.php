<?php
/**
 * The summary at the top of a request's page: who is asking for what, the bylaw it rests on, and where it stands.
 *
 * Built from the bundle the page already renders, so it reads the same on archivist and on the sites that fetch the
 * bundle from it. A bundle from an older archivist lacks the character record; the summary then falls back to the
 * character map and the typed name.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'owc_oat_summary_place' ) ) :
	/**
	 * A chronicle or coordinator office as a reader would say it.
	 *
	 * @param string $value A slug, or "chronicle/<slug>" or "coordinator/<slug>".
	 * @return string The title when the entity lookup knows it, otherwise the slug in words.
	 */
	function owc_oat_summary_place( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		$kind = 'chronicle';
		if ( preg_match( '#^(chronicle|coordinator)/(.+)$#i', $value, $m ) ) {
			$kind  = strtolower( $m[1] );
			$value = $m[2];
		}
		$title = function_exists( 'owc_entity_get_title' ) ? (string) owc_entity_get_title( $kind, $value ) : '';
		return '' !== $title ? $title : ucwords( str_replace( array( '-', '_' ), ' ', $value ) );
	}
endif;

if ( ! function_exists( 'owc_oat_summary_level' ) ) :
	/**
	 * A regulation level in words: "coordinator_approval" is "Coordinator Approval".
	 *
	 * @param string $level The stored level.
	 * @return string '' when none.
	 */
	function owc_oat_summary_level( $level ) {
		$level = trim( (string) $level );
		return '' === $level ? '' : ucwords( str_replace( '_', ' ', $level ) );
	}
endif;

if ( ! function_exists( 'owc_oat_summary_rule' ) ) :
	/**
	 * One linked regulation rule as the summary lists it.
	 *
	 * @param array|object $rule A rule row from the bundle.
	 * @param string       $kind 'pc' or 'npc': which level applies to the character.
	 * @return array ref, wording, needs, coordinator, elevated.
	 */
	function owc_oat_summary_rule( $rule, $kind = 'pc' ) {
		$rule    = (array) $rule;
		$parts   = array_filter( array( (string) ( $rule['category'] ?? '' ), (string) ( $rule['subcategory'] ?? '' ), (string) ( $rule['condition'] ?? $rule['condition_name'] ?? '' ) ), 'strlen' );
		$level   = 'npc' === $kind ? ( $rule['npc_level'] ?? '' ) : ( $rule['pc_level'] ?? '' );
		return array(
			'ref'         => (string) ( $rule['section_ref'] ?? '' ),
			'wording'     => implode( ' › ', $parts ),
			'condition'   => (string) ( $rule['condition'] ?? $rule['condition_name'] ?? '' ),
			'needs'       => owc_oat_summary_level( $level ),
			'coordinator' => (string) ( $rule['coordinator'] ?? $rule['controlling_coordinator'] ?? '' ),
			'elevated'    => ! empty( $rule['elevation'] ),
		);
	}
endif;

if ( ! function_exists( 'owc_oat_entry_summary_character' ) ) :
	/**
	 * The character the request is about, from the bundle.
	 *
	 * @param array $bundle The request's bundle.
	 * @return array|null id, name, player_name, chronicle_slug; null when the request names no character.
	 */
	function owc_oat_entry_summary_character( array $bundle ) {
		if ( ! empty( $bundle['character']['name'] ) ) {
			$c = (array) $bundle['character'];
			return array(
				'id'             => (int) ( $c['id'] ?? 0 ),
				'name'           => (string) $c['name'],
				'player_name'    => (string) ( $c['player_name'] ?? '' ),
				'chronicle_slug' => (string) ( $c['chronicle_slug'] ?? '' ),
			);
		}
		$entry = (array) ( $bundle['entry'] ?? array() );
		$meta  = (array) ( $bundle['meta'] ?? array() );
		$known = (array) ( $bundle['characters'] ?? array() );
		foreach ( array( 'character_name', 'character' ) as $key ) {
			$value = isset( $meta[ $key ] ) ? trim( (string) $meta[ $key ] ) : '';
			if ( '' === $value ) {
				continue;
			}
			if ( isset( $known[ $value ] ) ) {
				$c = (array) $known[ $value ];
				return array(
					'id'             => (int) ( $c['id'] ?? 0 ),
					'name'           => (string) ( $c['name'] ?? '' ),
					'player_name'    => (string) ( $c['player_name'] ?? '' ),
					'chronicle_slug' => (string) ( $c['chronicle_slug'] ?? '' ),
				);
			}
			if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value ) ) {
				return array(
					'id'             => (int) ( $entry['character_id'] ?? 0 ),
					'name'           => $value,
					'player_name'    => '',
					'chronicle_slug' => (string) ( $entry['chronicle_slug'] ?? '' ),
				);
			}
		}
		return null;
	}
endif;

if ( ! function_exists( 'owc_oat_entry_summary_request' ) ) :
	/**
	 * What the request asks for, as a phrase that follows "is requesting" (or "is submitting").
	 *
	 * @param string $form  Form slug, or the domain slug when the request has no form.
	 * @param array  $meta  The request's fields.
	 * @param array  $rules The summary's rule rows.
	 * @param string $label The domain's label, for a kind of request this does not know.
	 * @return string
	 */
	function owc_oat_entry_summary_request( $form, array $meta, array $rules, $label ) {
		$names = array();
		foreach ( $rules as $r ) {
			$name = '' !== $r['condition'] ? $r['condition'] : $r['wording'];
			if ( '' !== $name && ! in_array( $name, $names, true ) ) {
				$names[] = $name;
			}
		}
		$shown = array_slice( $names, 0, 3 );
		$items = implode( '; ', $shown );
		if ( count( $names ) > 3 ) {
			/* translators: %d: how many more items there are */
			$items .= ' ' . sprintf( __( 'and %d more', 'owbn-archivist' ), count( $names ) - 3 );
		}
		$get = function ( $key ) use ( $meta ) {
			return isset( $meta[ $key ] ) ? trim( wp_strip_all_tags( (string) $meta[ $key ] ) ) : '';
		};

		switch ( $form ) {
			case 'cl_ru_request':
				/* translators: %s: the rules asked about */
				return '' !== $items ? sprintf( __( 'R&U approval for %s', 'owbn-archivist' ), $items ) : __( 'R&U approval', 'owbn-archivist' );

			case 'cl_registration':
				/* translators: %s: the rules asked about */
				return '' !== $items ? sprintf( __( 'registration, with R&U approval for %s', 'owbn-archivist' ), $items ) : __( 'registration', 'owbn-archivist' );

			case 'cl_transfer':
				$to = owc_oat_summary_place( $get( 'transfer_to_chronicle' ) );
				/* translators: %s: the chronicle or office the character moves to */
				return '' !== $to ? sprintf( __( 'a transfer to %s', 'owbn-archivist' ), $to ) : __( 'a transfer', 'owbn-archivist' );

			case 'cl_death':
				return __( 'a death record', 'owbn-archivist' );

			case 'cl_learn_custom_content':
				$teacher = $get( 'teacher_name' );
				/* translators: %s: the teacher's name */
				return '' !== $teacher ? sprintf( __( 'approval to learn custom content from %s', 'owbn-archivist' ), $teacher ) : __( 'approval to learn custom content', 'owbn-archivist' );

			case 'cl_fame_registry':
				$level = $get( 'fame_level' );
				/* translators: %s: the fame level */
				return '' !== $level ? sprintf( __( 'a Fame Registry listing at level %s', 'owbn-archivist' ), $level ) : __( 'a Fame Registry listing', 'owbn-archivist' );

			case 'custom_content':
				$name = $get( 'content_name' );
				$type = $get( 'content_type' );
				if ( '' === $name ) {
					return __( 'approval of custom content', 'owbn-archivist' );
				}
				/* translators: 1: the content's name, 2: its type */
				return '' !== $type ? sprintf( __( 'approval of custom content: %1$s (%2$s)', 'owbn-archivist' ), $name, $type ) : sprintf( __( 'approval of custom content: %s', 'owbn-archivist' ), $name );

			case 'ca_reporting':
				$chronicle = owc_oat_summary_place( $get( 'chronicle_slug' ) );
				/* translators: %s: the chronicle */
				return '' !== $chronicle ? sprintf( __( 'the Chronicle Report for %s', 'owbn-archivist' ), $chronicle ) : __( 'a Chronicle Report', 'owbn-archivist' );

			case 'ca_manage_satellites':
				$what = $get( 'satellite_name' );
				/* translators: %s: the satellite chronicle's name */
				return '' !== $what ? sprintf( __( 'a satellite change: %s', 'owbn-archivist' ), $what ) : __( 'a satellite change', 'owbn-archivist' );

			case 'binding_agreements':
				return __( 'a binding agreement', 'owbn-archivist' );

			case 'da_chronicle':
			case 'da_global':
			case 'disciplinary_actions':
				$who = $get( 'player_name' );
				/* translators: %s: the player the record is about */
				return '' !== $who ? sprintf( __( 'a disciplinary action record for %s', 'owbn-archivist' ), $who ) : __( 'a disciplinary action record', 'owbn-archivist' );

			case 'governance_records':
				return __( 'a governance record', 'owbn-archivist' );

			case 'player_actions':
				$what = $get( 'action_type' );
				/* translators: %s: the kind of player action */
				return '' !== $what ? sprintf( __( 'a player action: %s', 'owbn-archivist' ), ucwords( str_replace( '_', ' ', $what ) ) ) : __( 'a player action', 'owbn-archivist' );
		}
		/* translators: %s: the kind of request, such as "Custom Content" */
		return '' !== $label ? sprintf( __( 'a %s request', 'owbn-archivist' ), $label ) : __( 'a request', 'owbn-archivist' );
	}
endif;

if ( ! function_exists( 'owc_oat_entry_summary' ) ) :
	/**
	 * The summary of a request: the character and player, what is asked for, the bylaw rules, who it waits on.
	 *
	 * @param array $bundle The request's bundle (owc_oat_get_entry()).
	 * @return array character (id, name, player_name, chronicle_slug or null), chronicle, request, with_character (bool),
	 *               rules (ref, wording, needs, coordinator, elevated), status, step, waiting (names), filer, filed_on.
	 */
	function owc_oat_entry_summary( array $bundle ) {
		$entry     = (array) ( $bundle['entry'] ?? array() );
		$meta      = (array) ( $bundle['meta'] ?? array() );
		$users     = (array) ( $bundle['user_map'] ?? array() );
		$form      = (string) ( ! empty( $entry['form_slug'] ) ? $entry['form_slug'] : ( $entry['domain'] ?? '' ) );
		$character = owc_oat_entry_summary_character( $bundle );
		$kind      = 'npc' === strtolower( (string) ( $meta['pc_npc'] ?? '' ) ) ? 'npc' : 'pc';

		$rules = array();
		foreach ( (array) ( $bundle['rules'] ?? array() ) as $rule ) {
			$rules[] = owc_oat_summary_rule( $rule, $kind );
		}

		$current = (string) ( $entry['current_step'] ?? '' );
		$waiting = array();
		foreach ( (array) ( $bundle['assignees'] ?? array() ) as $a ) {
			$a = (array) $a;
			if ( 'pending' === ( $a['status'] ?? '' ) && (string) ( $a['step'] ?? '' ) === $current ) {
				$name = isset( $users[ $a['user_id'] ?? 0 ] ) ? (string) $users[ $a['user_id'] ] : '';
				if ( '' !== $name && ! in_array( $name, $waiting, true ) ) {
					$waiting[] = $name;
				}
			}
		}

		$status    = (string) ( $entry['status'] ?? '' );
		$chronicle = $character && '' !== $character['chronicle_slug'] ? $character['chronicle_slug'] : (string) ( $entry['chronicle_slug'] ?? '' );
		$originator = (int) ( $entry['originator_id'] ?? 0 );

		return array(
			'character'      => $character,
			'with_character' => null !== $character,
			'chronicle'      => owc_oat_summary_place( $chronicle ),
			'request'        => owc_oat_entry_summary_request( $form, $meta, $rules, (string) ( $bundle['domain_label'] ?? '' ) ),
			'rules'          => $rules,
			'status'         => ucfirst( str_replace( '_', ' ', $status ) ),
			'status_slug'    => $status,
			'step'           => (string) ( $bundle['step_label'] ?? '' ),
			'waiting'        => $waiting,
			'filer'          => isset( $users[ $originator ] ) ? (string) $users[ $originator ] : '',
			'filed_on'       => (string) ( $entry['created_at'] ?? '' ),
		);
	}
endif;
