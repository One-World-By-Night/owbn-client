<?php
/**
 * Archivist-side OAT helpers shared by the AJAX layer (local mode) and the gateway handlers (remote sites).
 *
 * Everything here runs where OAT itself is installed, so user ids are archivist account ids.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'owc_oat_section_key_allowed' ) ) :
	/**
	 * Whether the current user may open a registry section: 'mine', or a section key that
	 * OAT_Registry::get_registry_sections() offers them.
	 *
	 * @param string $section_key e.g. 'mine', 'chronicle-hartford', 'coordinator-vampire'.
	 * @return bool
	 */
	function owc_oat_section_key_allowed( $section_key ) {
		$section_key = (string) $section_key;
		if ( 'mine' === $section_key ) {
			return true;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id || ! class_exists( 'OAT_Registry' ) ) {
			return false;
		}

		if ( 0 === strpos( $section_key, 'chronicle-' ) ) {
			$scopes = array( 'chronicles', 'decommissioned' );
		} elseif ( 0 === strpos( $section_key, 'coordinator-' ) ) {
			$scopes = array( 'coordinators' );
		} else {
			return false;
		}

		foreach ( $scopes as $scope ) {
			foreach ( OAT_Registry::get_registry_sections( $user_id, $scope ) as $section ) {
				if ( isset( $section['key'] ) && $section['key'] === $section_key ) {
					return true;
				}
			}
		}

		return false;
	}
endif;

if ( ! function_exists( 'owc_oat_linked_user_label' ) ) :
	/**
	 * "Display Name (email)" for the account a character is linked to, or '' when it has none.
	 *
	 * @param int $wp_user_id Archivist account id.
	 * @return string
	 */
	function owc_oat_linked_user_label( $wp_user_id ) {
		$wp_user_id = (int) $wp_user_id;
		if ( ! $wp_user_id ) {
			return '';
		}
		$user = get_userdata( $wp_user_id );
		if ( ! $user ) {
			/* translators: %d: account id */
			return sprintf( __( 'Account #%d (not found)', 'owbn-archivist' ), $wp_user_id );
		}
		return $user->display_name . ' (' . $user->user_email . ')';
	}
endif;

if ( ! function_exists( 'owc_oat_search_users_by_role_prefix' ) ) :
	/**
	 * Accounts whose cached AccessSchema roles start with a prefix, e.g. "coordinator/tremere"
	 * matches "coordinator/tremere/coordinator" and "coordinator/tremere/sub-coordinator".
	 *
	 * @param string $prefix Role path prefix.
	 * @return array Arrays with user_id, display_name, role_path.
	 */
	function owc_oat_search_users_by_role_prefix( $prefix ) {
		if ( ! defined( 'OWC_ASC_CACHE_KEY' ) ) {
			return array();
		}

		global $wpdb;
		$prefix = sanitize_text_field( $prefix );
		$like   = '%' . $wpdb->esc_like( $prefix ) . '%';

		$user_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value LIKE %s",
			OWC_ASC_CACHE_KEY,
			$like
		) );

		if ( empty( $user_ids ) ) {
			return array();
		}

		$results    = array();
		$prefix_low = strtolower( $prefix );

		foreach ( $user_ids as $uid ) {
			$cached_roles = get_user_meta( (int) $uid, OWC_ASC_CACHE_KEY, true );
			if ( ! is_array( $cached_roles ) ) {
				continue;
			}
			foreach ( $cached_roles as $cached_path ) {
				if ( strpos( strtolower( $cached_path ), $prefix_low ) === 0 ) {
					$user = get_user_by( 'id', (int) $uid );
					if ( $user ) {
						$results[] = array(
							'user_id'      => $user->ID,
							'display_name' => $user->display_name,
							'role_path'    => $cached_path,
						);
					}
					break;
				}
			}
		}

		return $results;
	}
endif;

if ( ! function_exists( 'owc_oat_user_search_allowed' ) ) :
	/**
	 * Whether the current user may search accounts: site admins, and anyone OAT treats as staff,
	 * a coordinator or the Archivist. Plain players may not.
	 *
	 * @return bool
	 */
	function owc_oat_user_search_allowed() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		if ( ! class_exists( 'OAT_Authorization' ) ) {
			return false;
		}
		$roles = OAT_Authorization::get_character_search_roles();
		return isset( $roles[0] ) && 'player' !== $roles[0];
	}
endif;

if ( ! function_exists( 'owc_oat_search_link_users' ) ) :
	/**
	 * Archivist accounts for the account pickers (character link, reassign, delegate, user fields on forms): the one
	 * account with an email address searched in full, or accounts whose name or login holds the term, or holders of a
	 * role path.
	 *
	 * An account's email comes back only to a search for that exact address. A name or login that is itself an email
	 * address is neither matched by part nor shown.
	 *
	 * Role holders from AccessSchema carry AccessSchema ids, so they are matched to archivist
	 * accounts by email, then by login.
	 *
	 * @param string $term At least two characters.
	 * @return array Arrays with id, label, value, type, display_name, email.
	 */
	function owc_oat_search_link_users( $term ) {
		$term = trim( (string) $term );
		if ( strlen( $term ) < 2 || ! owc_oat_user_search_allowed() ) {
			return array();
		}

		$exact   = is_email( $term ) ? strtolower( $term ) : '';
		$results = array();
		$seen    = array();
		$add     = function ( $user, $type, $via = '' ) use ( &$results, &$seen, $exact ) {
			if ( ! $user || isset( $seen[ $user->ID ] ) ) {
				return;
			}
			$seen[ $user->ID ] = true;
			$is_match = '' !== $exact && strtolower( (string) $user->user_email ) === $exact;
			/* translators: %d: account id */
			$name      = ( ! $is_match && is_email( $user->display_name ) ) ? sprintf( __( 'Account #%d', 'owbn-archivist' ), (int) $user->ID ) : $user->display_name;
			$handle    = is_email( $user->user_login ) ? '#' . (int) $user->ID : $user->user_login;
			$results[] = array(
				'id'           => (int) $user->ID,
				'label'        => $name . ' (' . ( '' !== $via ? 'via ' . $via : $handle ) . ')',
				'value'        => $name,
				'type'         => $type,
				'display_name' => $name,
				'email'        => $is_match ? $user->user_email : '',
			);
		};

		if ( '' !== $exact ) {
			$add( get_user_by( 'email', $exact ), 'user' );
		} else {
			$users = get_users( array(
				'search'         => '*' . $term . '*',
				'search_columns' => array( 'user_login', 'display_name' ),
				'number'         => 15,
				'orderby'        => 'display_name',
			) );
			foreach ( $users as $user ) {
				$by_name  = ! is_email( $user->display_name ) && false !== stripos( (string) $user->display_name, $term );
				$by_login = ! is_email( $user->user_login ) && false !== stripos( (string) $user->user_login, $term );
				if ( $by_name || $by_login ) {
					$add( $user, 'user' );
				}
			}
		}

		if ( false !== strpos( $term, '/' ) ) {
			$holders = function_exists( 'owc_asc_get_users_by_role' ) ? owc_asc_get_users_by_role( 'oat', $term ) : array();
			if ( is_array( $holders ) && ! empty( $holders ) ) {
				foreach ( $holders as $holder ) {
					$holder = (array) $holder;
					$user   = ! empty( $holder['email'] ) ? get_user_by( 'email', $holder['email'] ) : false;
					$add( $user, 'role', $term );
				}
			} else {
				foreach ( owc_oat_search_users_by_role_prefix( $term ) as $match ) {
					$add( get_userdata( (int) $match['user_id'] ), 'role', $match['role_path'] );
				}
			}
		}

		return $results;
	}
endif;

if ( ! function_exists( 'owc_oat_cancel_refused_submission' ) ) :
	/**
	 * Keeps a request the submit step refused as cancelled, with the reason on its timeline, instead of open at
	 * Submission with no fields. A request the step moved on, or one already closed, is left as it is.
	 *
	 * @param int      $entry_id Request id.
	 * @param int      $user_id  The filer.
	 * @param WP_Error $error    Why the submit step refused it.
	 * @return void
	 */
	function owc_oat_cancel_refused_submission( $entry_id, $user_id, $error ) {
		if ( ! class_exists( 'OAT_Entry' ) || ! class_exists( 'OAT_Constants' ) || ! is_wp_error( $error ) ) {
			return;
		}
		$entry = OAT_Entry::find( (int) $entry_id );
		if ( ! $entry || 'submit' !== (string) $entry->current_step || OAT_Constants::is_terminal_status( $entry->status ) ) {
			return;
		}
		OAT_Entry::update_status( (int) $entry_id, OAT_Constants::STATUS_CANCELLED, 'submit' );
		if ( class_exists( 'OAT_Timeline' ) ) {
			OAT_Timeline::append( array(
				'entry_id'        => (int) $entry_id,
				'action_type'     => OAT_Constants::ACTION_CANCEL,
				'actor_id'        => (int) $user_id,
				'step'            => 'submit',
				'visibility_tier' => OAT_Constants::TIER_STAFF,
				'note'            => 'Not accepted at submission: ' . $error->get_error_message(),
			) );
		}
	}
endif;

if ( ! function_exists( 'owc_oat_user_is_player' ) ) :
	/**
	 * Whether a user is a player: they hold player/approved, or a Player role in any chronicle.
	 *
	 * @param int $user_id User id; 0 is the current user.
	 * @return bool
	 */
	function owc_oat_user_is_player( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		$roles = function_exists( 'owc_oat_get_user_asc_roles' ) ? owc_oat_get_user_asc_roles( $user_id ) : array();
		foreach ( (array) $roles as $role ) {
			$role = strtolower( (string) $role );
			if ( 'player/approved' === $role || preg_match( '#^chronicle/[^/]+/player$#', $role ) ) {
				return true;
			}
		}
		return false;
	}
endif;

if ( ! function_exists( 'owc_oat_chronicle_is_active' ) ) :
	/**
	 * Whether a slug is a chronicle that is still running: one of the chronicles and not decommissioned.
	 *
	 * @param string $slug Chronicle slug.
	 * @return bool
	 */
	function owc_oat_chronicle_is_active( $slug ) {
		$slug = (string) $slug;
		if ( '' === $slug || ! function_exists( 'owc_get_chronicles' ) ) {
			return false;
		}
		$chronicles = owc_get_chronicles();
		foreach ( is_array( $chronicles ) ? $chronicles : array() as $c ) {
			if ( isset( $c['slug'] ) && (string) $c['slug'] === $slug ) {
				return 'decommissioned' !== ( $c['status'] ?? '' );
			}
		}
		return false;
	}
endif;

if ( ! function_exists( 'owc_oat_create_scope' ) ) :
	/**
	 * How far the current user may create characters in a chronicle: 'any' for site admins, exec offices and
	 * Coordinators; 'staff' for the chronicle's HST, CM, staff or AST; 'player' for a player there, and for any other
	 * player in a chronicle that is still running, who registers their own PC for its staff to approve (their own PC
	 * only); '' for anyone else.
	 *
	 * @param string $chronicle_slug Chronicle slug.
	 * @return string
	 */
	function owc_oat_create_scope( $chronicle_slug ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return '';
		}
		if ( function_exists( 'owc_oat_is_super_user' ) && owc_oat_is_super_user( $user_id ) ) {
			return 'any';
		}
		$slug  = strtolower( (string) $chronicle_slug );
		$scope = '';
		$roles = function_exists( 'owc_oat_get_user_asc_roles' ) ? owc_oat_get_user_asc_roles( $user_id ) : array();
		foreach ( (array) $roles as $role ) {
			$role = strtolower( (string) $role );
			if ( preg_match( '#^exec/[^/]+/coordinator$#', $role ) || preg_match( '#^coordinator/[^/]+/(coordinator|sub-coordinator)$#', $role ) ) {
				return 'any';
			}
			if ( preg_match( '#^chronicle/([^/]+)/(hst|cm|staff|st|ast)$#', $role, $m ) && $m[1] === $slug ) {
				$scope = 'staff';
			} elseif ( '' === $scope && 'chronicle/' . $slug . '/player' === $role ) {
				$scope = 'player';
			}
		}
		if ( '' === $scope && '' !== $slug && owc_oat_user_is_player( $user_id ) && owc_oat_chronicle_is_active( $slug ) ) {
			$scope = 'player';
		}
		return $scope;
	}
endif;

if ( ! function_exists( 'owc_oat_creates_own_pc_only' ) ) :
	/**
	 * Whether the current user may only create their own PC: they hold no staff, exec or Coordinator role and aren't
	 * a site admin.
	 *
	 * @return bool
	 */
	function owc_oat_creates_own_pc_only() {
		$user_id = get_current_user_id();
		if ( ! $user_id || ( function_exists( 'owc_oat_is_super_user' ) && owc_oat_is_super_user( $user_id ) ) ) {
			return false;
		}
		$roles = function_exists( 'owc_oat_get_user_asc_roles' ) ? owc_oat_get_user_asc_roles( $user_id ) : array();
		foreach ( (array) $roles as $role ) {
			if ( preg_match( '#^(exec/[^/]+/coordinator|coordinator/[^/]+/(coordinator|sub-coordinator)|chronicle/[^/]+/(hst|cm|staff|st|ast))$#', strtolower( (string) $role ) ) ) {
				return false;
			}
		}
		return true;
	}
endif;

if ( ! function_exists( 'owc_oat_create_chronicle_choices' ) ) :
	/**
	 * The chronicles the create panel searches for the current user: every chronicle for site admins, exec offices and
	 * Coordinators; every chronicle still running for players; and the chronicles where they hold HST, CM, staff, ST,
	 * AST or player (the roles owc_oat_create_scope() accepts). The chosen chronicle is checked again when the
	 * character is created.
	 *
	 * @return array[] Each: value (chronicle slug), label (title).
	 */
	function owc_oat_create_chronicle_choices() {
		$titles = array();
		$is_any = 'any' === owc_oat_create_scope( '' );
		if ( $is_any || owc_oat_user_is_player() ) {
			$chronicles = function_exists( 'owc_get_chronicles' ) ? owc_get_chronicles() : array();
			foreach ( is_array( $chronicles ) ? $chronicles : array() as $c ) {
				$slug = isset( $c['slug'] ) ? (string) $c['slug'] : '';
				if ( '' === $slug || ( ! $is_any && 'decommissioned' === ( $c['status'] ?? '' ) ) ) {
					continue;
				}
				$titles[ $slug ] = ! empty( $c['title'] ) ? (string) $c['title'] : $slug;
			}
		}
		if ( ! $is_any && function_exists( '_owc_asc_get_user_entity_entries' ) ) {
			foreach ( _owc_asc_get_user_entity_entries( 'chronicle', array( 'HST', 'CM', 'Staff', 'ST', 'AST', 'Player' ) ) as $e ) {
				$titles[ $e['slug'] ] = ! empty( $e['title'] ) ? (string) $e['title'] : (string) $e['slug'];
			}
		}
		asort( $titles, SORT_NATURAL | SORT_FLAG_CASE );
		$choices = array();
		foreach ( $titles as $slug => $title ) {
			$choices[] = array( 'value' => (string) $slug, 'label' => $title );
		}
		return $choices;
	}
endif;

if ( ! function_exists( 'owc_oat_create_character_record' ) ) :
	/**
	 * Creates a character for the current user from the create panel's fields.
	 *
	 * A PC needs its player's name and email; it is linked to the archivist account with that
	 * email when one exists and left unlinked otherwise. An NPC has no player. A Coordinator
	 * office NPC needs the user to hold that office. A chronicle character needs the user to
	 * belong to that chronicle or to be a player (owc_oat_create_scope()); a player creates only
	 * their own PC, under their own name and email, and the chronicle's staff approve it.
	 *
	 * @param array $input character_name, chronicle_slug, pc_npc, npc_coordinator, player_name,
	 *                     player_email, creature_genre, creature_sub_type, creature_type, creature_variant.
	 * @return array|WP_Error uuid, character_name, chronicle_slug, pc_npc, linked.
	 */
	function owc_oat_create_character_record( array $input ) {
		if ( ! class_exists( 'OAT_Character' ) ) {
			return new WP_Error( 'oat_missing', __( 'OAT is not active on this site.', 'owbn-archivist' ) );
		}
		if ( ! get_current_user_id() ) {
			return new WP_Error( 'forbidden', __( 'You must be logged in.', 'owbn-archivist' ) );
		}

		// Loads the user's roles into the cache that the permission checks below read.
		if ( class_exists( 'OAT_Authorization' ) ) {
			OAT_Authorization::get_user_roles();
		}
		if ( ! function_exists( 'owc_oat_can_create_character' ) || ! owc_oat_can_create_character() ) {
			return new WP_Error( 'forbidden', __( 'You do not have permission to create characters.', 'owbn-archivist' ) );
		}

		$name = sanitize_text_field( (string) ( $input['character_name'] ?? '' ) );
		if ( '' === $name ) {
			return new WP_Error( 'invalid', __( 'Character name is required.', 'owbn-archivist' ) );
		}

		$pc_npc          = ( 'npc' === ( $input['pc_npc'] ?? 'pc' ) ) ? 'npc' : 'pc';
		$chronicle_slug  = strtolower( sanitize_text_field( (string) ( $input['chronicle_slug'] ?? '' ) ) );
		$npc_coordinator = strtolower( sanitize_text_field( (string) ( $input['npc_coordinator'] ?? '' ) ) );

		$data = array(
			'character_name' => $name,
			'player_name'    => '',
			'player_email'   => '',
		);

		if ( '' !== $npc_coordinator ) {
			$offices = function_exists( 'owc_oat_current_user_coordinator_offices' ) ? owc_oat_current_user_coordinator_offices() : array();
			if ( ! isset( $offices[ $npc_coordinator ] ) ) {
				return new WP_Error( 'forbidden', __( 'You are not authorized to create NPCs for that Coordinator office.', 'owbn-archivist' ) );
			}
			$pc_npc                  = 'npc';
			$data['npc_coordinator'] = $npc_coordinator;
			$data['npc_type']        = 'coordinator';
		} elseif ( '' === $chronicle_slug ) {
			return new WP_Error( 'invalid', __( 'Choose a Home Chronicle, or a Coordinator office for an office NPC.', 'owbn-archivist' ) );
		} else {
			$scope = owc_oat_create_scope( $chronicle_slug );
			if ( '' === $scope ) {
				return new WP_Error( 'forbidden', __( 'You can only create characters in a chronicle you belong to.', 'owbn-archivist' ) );
			}
			if ( 'player' === $scope ) {
				if ( 'npc' === $pc_npc ) {
					return new WP_Error( 'forbidden', __( 'Only staff can create NPCs.', 'owbn-archivist' ) );
				}
				$me                    = wp_get_current_user();
				$input['player_name']  = $me->display_name;
				$input['player_email'] = $me->user_email;
			}
			$data['chronicle_slug'] = $chronicle_slug;
			if ( 'npc' === $pc_npc ) {
				$data['npc_type'] = 'chronicle';
			}
		}
		$data['pc_npc'] = $pc_npc;

		if ( 'pc' === $pc_npc ) {
			$player_name  = sanitize_text_field( (string) ( $input['player_name'] ?? '' ) );
			$player_email = sanitize_email( (string) ( $input['player_email'] ?? '' ) );
			if ( '' === $player_name || '' === trim( (string) ( $input['player_email'] ?? '' ) ) ) {
				return new WP_Error( 'invalid', __( "A PC needs its player's name and email.", 'owbn-archivist' ) );
			}
			if ( ! is_email( $player_email ) ) {
				return new WP_Error( 'invalid', __( "That player email doesn't look right.", 'owbn-archivist' ) );
			}
			$data['player_name']  = $player_name;
			$data['player_email'] = $player_email;
			$account              = get_user_by( 'email', $player_email );
			if ( $account ) {
				$data['wp_user_id'] = (int) $account->ID;
			}
		}

		foreach ( array( 'creature_genre', 'creature_sub_type', 'creature_type', 'creature_variant' ) as $field ) {
			$value = sanitize_text_field( (string) ( $input[ $field ] ?? '' ) );
			if ( '' !== $value ) {
				$data[ $field ] = $value;
			}
		}

		$id = OAT_Character::create( $data );
		if ( ! $id ) {
			return new WP_Error( 'create_failed', __( 'Failed to create character.', 'owbn-archivist' ) );
		}

		$character = OAT_Character::find( $id );
		if ( ! $character ) {
			return new WP_Error( 'create_failed', __( 'Character created but could not be retrieved.', 'owbn-archivist' ) );
		}

		return array(
			'uuid'           => $character->uuid,
			'character_name' => $character->character_name,
			'chronicle_slug' => $character->chronicle_slug ? $character->chronicle_slug : '',
			'pc_npc'         => $character->pc_npc ? $character->pc_npc : 'pc',
			'linked'         => ! empty( $character->wp_user_id ),
		);
	}
endif;

if ( ! function_exists( 'owc_oat_link_characters_to_new_account' ) ) :
	/**
	 * Links unlinked PCs whose player email is a new account's email to that account, so a PC
	 * registered for someone without an account yet becomes theirs when they first sign in.
	 *
	 * @param int $user_id New account id.
	 * @return void
	 */
	function owc_oat_link_characters_to_new_account( $user_id ) {
		if ( ! class_exists( 'OAT_Character' ) ) {
			return;
		}
		$user = get_userdata( (int) $user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return;
		}
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}oat_characters SET wp_user_id = %d, updated_at = %d WHERE pc_npc = 'pc' AND player_email = %s AND ( wp_user_id IS NULL OR wp_user_id = 0 )",
			(int) $user->ID,
			time(),
			$user->user_email
		) );
	}
	add_action( 'user_register', 'owc_oat_link_characters_to_new_account', 20 );
endif;

if ( ! function_exists( 'owc_oat_timeline_actor_id' ) ) :
	/**
	 * The user who took a timeline action, whichever name the row carries it under.
	 *
	 * @param object $row Timeline row.
	 * @return int WordPress user id, or 0.
	 */
	function owc_oat_timeline_actor_id( $row ) {
		if ( isset( $row->user_id ) ) {
			return (int) $row->user_id;
		}
		return isset( $row->actor_id ) ? (int) $row->actor_id : 0;
	}
endif;

if ( ! function_exists( 'owc_oat_linked_rule_row' ) ) :
	/**
	 * One regulation rule linked to a request, in the shape the request's page shows it.
	 *
	 * @param object $rule A regulation rule row, or an entry-rule row carrying rule_id.
	 * @return array rule_id, genre, category, subcategory, condition, pc_level, coordinator, elevation.
	 */
	function owc_oat_linked_rule_row( $rule ) {
		if ( ! isset( $rule->condition_name ) && isset( $rule->rule_id ) && class_exists( 'OAT_Regulation_Rule' ) ) {
			$found = OAT_Regulation_Rule::find( (int) $rule->rule_id );
			$rule  = $found ? $found : $rule;
		}
		return array(
			'rule_id'     => (int) ( $rule->rule_id ?? $rule->id ?? 0 ),
			'genre'       => (string) ( $rule->genre ?? '' ),
			'category'    => (string) ( $rule->category ?? '' ),
			'subcategory' => (string) ( $rule->subcategory ?? '' ),
			'condition'   => (string) ( $rule->condition_name ?? '' ),
			'pc_level'    => (string) ( $rule->pc_level ?? '' ),
			'coordinator' => (string) ( $rule->controlling_coordinator ?? '' ),
			'elevation'   => (int) ( $rule->elevation ?? 0 ),
		);
	}
endif;

if ( ! function_exists( 'owc_oat_entry_character_map' ) ) :
	/**
	 * The characters a request's fields name, by uuid, so a site without OAT can show their names.
	 *
	 * @param array $meta The request's fields, key => value.
	 * @return array<string,array> uuid => id, name, chronicle_slug.
	 */
	function owc_oat_entry_character_map( $meta ) {
		$map = array();
		if ( ! class_exists( 'OAT_Character' ) ) {
			return $map;
		}
		foreach ( (array) $meta as $value ) {
			if ( ! is_string( $value ) || ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value ) || isset( $map[ $value ] ) ) {
				continue;
			}
			$character = OAT_Character::find_by_uuid( $value );
			if ( $character ) {
				$map[ $value ] = array(
					'id'             => (int) $character->id,
					'name'           => (string) $character->character_name,
					'chronicle_slug' => (string) $character->chronicle_slug,
				);
			}
		}
		return $map;
	}
endif;

if ( ! function_exists( 'owc_oat_search_characters_for_user' ) ) :
	/**
	 * The characters whose name matches a term, within what the current user may search: their own as a player,
	 * their chronicles' and their own as staff, everyone's as a Coordinator or the Archivist.
	 *
	 * @param string $term           At least two characters of the name.
	 * @param string $scope          The scope asked for; capped to the scopes the user holds.
	 * @param string $chronicle_slug For staff, the chronicle to narrow to when it is one of theirs.
	 * @return array[] uuid, character_name, chronicle_slug, chronicle_title, player_name, pc_npc.
	 */
	function owc_oat_search_characters_for_user( $term, $scope = '', $chronicle_slug = '' ) {
		if ( ! class_exists( 'OAT_Character' ) || strlen( (string) $term ) < 2 ) {
			return array();
		}
		global $wpdb;
		$table          = $wpdb->prefix . 'oat_characters';
		$like           = '%' . $wpdb->esc_like( (string) $term ) . '%';
		$scope          = (string) $scope;
		$chronicle_slug = strtolower( (string) $chronicle_slug );
		$where_extra    = '';
		$prepare_args   = array( $like );

		// Role-based scope filtering, capped to the roles the user actually holds.
		if ( class_exists( 'OAT_Authorization' ) ) {
			$allowed_scopes = OAT_Authorization::get_character_search_roles();
			if ( ! in_array( $scope, $allowed_scopes, true ) ) {
				$scope = $allowed_scopes[0];
			}
		} else {
			$scope = 'player';
		}

		// Staff: the requested chronicle when it is one of theirs, otherwise all of their chronicles.
		if ( 'staff' === $scope ) {
			$staff_slugs = array();
			foreach ( (array) OAT_Authorization::get_user_roles() as $role ) {
				if ( preg_match( '#^chronicle/([^/]+)/(hst|staff|cm|ast|sug|st(?=$|/))#i', (string) $role, $m ) ) {
					$staff_slugs[] = strtolower( $m[1] );
				}
			}
			$staff_slugs = array_values( array_unique( $staff_slugs ) );
			$narrowed = '' !== $chronicle_slug && in_array( $chronicle_slug, $staff_slugs, true );
			if ( $narrowed ) {
				$staff_slugs = array( $chronicle_slug );
			}
			if ( $staff_slugs ) {
				$in_chronicles = 'chronicle_slug IN (' . implode( ',', array_fill( 0, count( $staff_slugs ), '%s' ) ) . ')';
				$prepare_args  = array_merge( $prepare_args, $staff_slugs );
				$me            = wp_get_current_user();
				if ( ! $narrowed && $me && $me->ID && $me->user_email ) {
					// Their own characters too, in whatever chronicle they are.
					$where_extra    = ' AND ( ' . $in_chronicles . ' OR player_email = %s OR wp_user_id = %d )';
					$prepare_args[] = $me->user_email;
					$prepare_args[] = $me->ID;
				} else {
					$where_extra = ' AND ' . $in_chronicles;
				}
			} else {
				$scope = 'player';
			}
		}

		// Player: characters carrying their email or linked to their account.
		if ( 'player' === $scope ) {
			$user = wp_get_current_user();
			if ( ! $user || ! $user->ID || ! $user->user_email ) {
				return array();
			}
			$where_extra  = ' AND ( player_email = %s OR wp_user_id = %d )';
			$prepare_args = array( $like, $user->user_email, $user->ID );
		}
		// 'coordinator' and 'archivist' scopes have no filter — all characters.

		$sql  = "SELECT uuid, character_name, chronicle_slug, player_name, pc_npc FROM {$table} WHERE character_name LIKE %s{$where_extra} ORDER BY character_name ASC LIMIT 15";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $prepare_args ) );

		$results = array();
		foreach ( $rows as $row ) {
			$chron_title = '';
			if ( $row->chronicle_slug && function_exists( 'owc_entity_get_title' ) ) {
				$chron_title = owc_entity_get_title( 'chronicle', $row->chronicle_slug );
			}
			$results[] = array(
				'uuid'            => $row->uuid,
				'character_name'  => $row->character_name,
				'chronicle_slug'  => $row->chronicle_slug ? $row->chronicle_slug : '',
				'chronicle_title' => $chron_title ? $chron_title : ( $row->chronicle_slug ? $row->chronicle_slug : '' ),
				'player_name'     => $row->player_name,
				'pc_npc'          => isset( $row->pc_npc ) ? $row->pc_npc : 'pc',
			);
		}
		return $results;
	}
endif;

if ( ! function_exists( 'owc_oat_entries_waiting_on' ) ) :
	/**
	 * The open requests waiting on a user: first those at a step they are assigned to, then those at a step their
	 * roles let them act on. Assigned to Me and the Dashboard's Pending Actions both show this set.
	 *
	 * @param int    $user_id       WordPress user id.
	 * @param string $domain_filter Domain slug, or '' for every domain.
	 * @return object[] Entry rows, each request once.
	 */
	function owc_oat_entries_waiting_on( $user_id, $domain_filter = '' ) {
		if ( ! class_exists( 'OAT_Assignee' ) || ! class_exists( 'OAT_Entry' ) ) {
			return array();
		}
		$entries = array();
		foreach ( OAT_Assignee::inbox( (int) $user_id ) as $assignment ) {
			$entry_id = (int) $assignment->entry_id;
			if ( isset( $entries[ $entry_id ] ) || (string) $assignment->step !== (string) $assignment->current_step ) {
				continue;
			}
			if ( '' !== $domain_filter && $assignment->domain !== $domain_filter ) {
				continue;
			}
			$entry = OAT_Entry::find( $entry_id );
			if ( $entry ) {
				$entries[ $entry_id ] = $entry;
			}
		}
		if ( function_exists( 'owc_oat_capability_inbox_entries' ) ) {
			foreach ( owc_oat_capability_inbox_entries( (int) $user_id, $domain_filter ) as $entry ) {
				if ( ! isset( $entries[ (int) $entry->id ] ) ) {
					$entries[ (int) $entry->id ] = $entry;
				}
			}
		}
		return array_values( $entries );
	}
endif;
