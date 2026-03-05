<?php

/**
 * OWBN Gateway - OAT Read Handlers
 *
 * Handles read-only OAT gateway endpoints: inbox, entries, entry detail,
 * domains, and regulation rules search.
 *
 * All handlers call OAT models directly (local mode only — these handlers
 * only load on archivist.owbn.net where OAT is installed).
 *
 */

defined('ABSPATH') || exit;


/**
 * Build a user display name map from an array of user IDs.
 *
 * @param array $user_ids Array of user IDs (will be deduped).
 * @return array Associative array of user_id => display_name.
 */
function owbn_gateway_oat_build_user_map( $user_ids ) {
    $user_ids = array_unique( array_filter( array_map( 'absint', $user_ids ) ) );
    $map = array();

    if ( empty( $user_ids ) ) {
        return $map;
    }

    $users = get_users( array(
        'include' => $user_ids,
        'fields'  => array( 'ID', 'display_name' ),
    ) );

    foreach ( $users as $user ) {
        $map[ (int) $user->ID ] = $user->display_name;
    }

    return $map;
}

/**
 * Serialize an entry object to a flat array for API response.
 *
 * @param object $entry Entry row from database.
 * @return array Serialized entry data.
 */
function owbn_gateway_oat_serialize_entry( $entry ) {
    return array(
        'id'                 => (int) $entry->id,
        'domain'             => $entry->domain,
        'status'             => $entry->status,
        'current_step'       => $entry->current_step,
        'originator_id'      => (int) $entry->originator_id,
        'chronicle_slug'     => isset( $entry->chronicle_slug ) ? $entry->chronicle_slug : '',
        'character_id'       => isset( $entry->character_id ) ? (int) $entry->character_id : 0,
        'coordinator_genre'  => isset( $entry->coordinator_genre ) ? $entry->coordinator_genre : '',
        'created_at'         => $entry->created_at,
        'updated_at'         => $entry->updated_at,
    );
}


/**
 * Handle POST /owbn/v1/oat/inbox
 *
 * Returns the user's inbox: entries assigned to them (pending) plus
 * entries they are watching.
 *
 * Request body (optional):
 *   { "domain": "character_lifecycle" }
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_inbox( $request ) {
    $user_id = (int) $request->get_param( '_oat_user_id' );
    $body    = $request->get_json_params();
    $domain  = isset( $body['domain'] ) ? sanitize_text_field( $body['domain'] ) : '';

    // Get assignments (entries where user is a pending assignee).
    $assignments = OAT_Assignee::inbox( $user_id );
    $assigned_entries = array();
    $user_ids = array();

    foreach ( $assignments as $assignment ) {
        $entry = OAT_Entry::find( (int) $assignment->entry_id );
        if ( ! $entry ) {
            continue;
        }
        // Domain filter.
        if ( $domain !== '' && $entry->domain !== $domain ) {
            continue;
        }
        $serialized = owbn_gateway_oat_serialize_entry( $entry );
        $serialized['assignment_step']   = $assignment->step;
        $serialized['assignment_status'] = $assignment->status;
        $assigned_entries[] = $serialized;
        $user_ids[] = (int) $entry->originator_id;
    }

    // Get watched entries.
    $watched = OAT_Watcher::for_user( $user_id );
    $watched_entries = array();

    foreach ( $watched as $watch ) {
        $entry = OAT_Entry::find( (int) $watch->entry_id );
        if ( ! $entry ) {
            continue;
        }
        if ( $domain !== '' && $entry->domain !== $domain ) {
            continue;
        }
        $watched_entries[] = owbn_gateway_oat_serialize_entry( $entry );
        $user_ids[] = (int) $entry->originator_id;
    }

    // Get entries originated by this user.
    $originated = OAT_Entry::for_originator( $user_id );
    $my_entries = array();

    foreach ( $originated as $entry ) {
        if ( $domain !== '' && $entry->domain !== $domain ) {
            continue;
        }
        $my_entries[] = owbn_gateway_oat_serialize_entry( $entry );
    }

    $user_ids[] = $user_id;
    $user_map = owbn_gateway_oat_build_user_map( $user_ids );

    // Add entry meta titles to all entries.
    $all_entries = array_merge( $assigned_entries, $watched_entries, $my_entries );
    $meta_map = array();
    foreach ( $all_entries as $e ) {
        if ( ! isset( $meta_map[ $e['id'] ] ) ) {
            $title = OAT_Entry_Meta::get( $e['id'], 'title' );
            $meta_map[ $e['id'] ] = $title ? $title : '';
        }
    }

    // Attach titles.
    foreach ( $assigned_entries as &$e ) {
        $e['title'] = isset( $meta_map[ $e['id'] ] ) ? $meta_map[ $e['id'] ] : '';
    }
    unset( $e );
    foreach ( $watched_entries as &$e ) {
        $e['title'] = isset( $meta_map[ $e['id'] ] ) ? $meta_map[ $e['id'] ] : '';
    }
    unset( $e );
    foreach ( $my_entries as &$e ) {
        $e['title'] = isset( $meta_map[ $e['id'] ] ) ? $meta_map[ $e['id'] ] : '';
    }
    unset( $e );

    return owbn_gateway_respond( array(
        'assignments' => $assigned_entries,
        'watched'     => $watched_entries,
        'my_entries'  => $my_entries,
        'user_map'    => $user_map,
    ) );
}


/**
 * Handle POST /owbn/v1/oat/entries
 *
 * Paginated, filterable entry list.
 *
 * Request body:
 *   { "domain": "...", "status": "...", "page": 1, "per_page": 20 }
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_entries( $request ) {
    $body = $request->get_json_params();

    $args = array();
    if ( isset( $body['domain'] ) && $body['domain'] !== '' ) {
        $args['domain'] = sanitize_text_field( $body['domain'] );
    }
    if ( isset( $body['status'] ) && $body['status'] !== '' ) {
        $args['status'] = sanitize_text_field( $body['status'] );
    }
    if ( isset( $body['chronicle_slug'] ) && $body['chronicle_slug'] !== '' ) {
        $args['chronicle_slug'] = sanitize_text_field( $body['chronicle_slug'] );
    }
    if ( isset( $body['coordinator_genre'] ) && $body['coordinator_genre'] !== '' ) {
        $args['coordinator_genre'] = sanitize_text_field( $body['coordinator_genre'] );
    }

    $page     = isset( $body['page'] ) ? max( 1, (int) $body['page'] ) : 1;
    $per_page = isset( $body['per_page'] ) ? min( 100, max( 1, (int) $body['per_page'] ) ) : 20;

    $args['per_page'] = $per_page;
    $args['offset']   = ( $page - 1 ) * $per_page;
    $args['orderby']  = 'updated_at';
    $args['order']    = 'DESC';

    $entries = OAT_Entry::all( $args );
    $total   = OAT_Entry::count( $args );

    $data     = array();
    $user_ids = array();

    foreach ( $entries as $entry ) {
        $serialized = owbn_gateway_oat_serialize_entry( $entry );
        $serialized['title'] = OAT_Entry_Meta::get( (int) $entry->id, 'title' );
        if ( ! $serialized['title'] ) {
            $serialized['title'] = '';
        }
        $data[] = $serialized;
        $user_ids[] = (int) $entry->originator_id;
    }

    return owbn_gateway_respond( array(
        'entries'  => $data,
        'total'    => (int) $total,
        'page'     => $page,
        'per_page' => $per_page,
        'pages'    => (int) ceil( $total / $per_page ),
        'user_map' => owbn_gateway_oat_build_user_map( $user_ids ),
    ) );
}


/**
 * Handle POST /owbn/v1/oat/entry/{id}
 *
 * Full entry bundle: entry data, meta, assignees, timeline, rules,
 * available actions, timer state, BBP eligibility, and user_map.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_entry( $request ) {
    $entry_id = (int) $request->get_param( 'id' );
    $user_id  = (int) $request->get_param( '_oat_user_id' );

    $entry = OAT_Entry::find( $entry_id );
    if ( ! $entry ) {
        return owbn_gateway_respond( new WP_Error(
            'oat_entry_not_found',
            'Entry not found.',
            array( 'status' => 404 )
        ) );
    }

    // Collect all user IDs referenced in this entry for the user_map.
    $user_ids = array( (int) $entry->originator_id, $user_id );

    // Entry data.
    $entry_data = owbn_gateway_oat_serialize_entry( $entry );

    // Meta — flatten to key => value map.
    $meta_raw = OAT_Entry_Meta::get_all( $entry_id );
    $meta = array();
    foreach ( $meta_raw as $m ) {
        $meta[ $m->meta_key ] = $m->meta_value;
    }

    // Assignees.
    $assignees_raw = OAT_Assignee::for_entry( $entry_id );
    $assignees = array();
    foreach ( $assignees_raw as $a ) {
        $assignees[] = array(
            'id'       => (int) $a->id,
            'user_id'  => (int) $a->user_id,
            'step'     => $a->step,
            'status'   => $a->status,
        );
        $user_ids[] = (int) $a->user_id;
    }

    // Timeline.
    $timeline_raw = OAT_Timeline::for_entry( $entry_id );
    $timeline = array();
    foreach ( $timeline_raw as $t ) {
        $actor_id = 0;
        if ( isset( $t->actor_id ) ) {
            $actor_id = (int) $t->actor_id;
        } elseif ( isset( $t->user_id ) ) {
            $actor_id = (int) $t->user_id;
        }
        $timeline[] = array(
            'id'              => (int) $t->id,
            'action_type'     => $t->action_type,
            'visibility_tier' => isset( $t->visibility_tier ) ? $t->visibility_tier : '',
            'step'            => isset( $t->step ) ? $t->step : '',
            'note'            => isset( $t->note ) ? $t->note : '',
            'actor_id'        => $actor_id,
            'created_at'      => $t->created_at,
        );
        if ( $actor_id ) {
            $user_ids[] = $actor_id;
        }
    }

    // Rules.
    $rules = OAT_Entry_Rule::rules_for_entry( $entry_id );

    // Timer.
    $timer_obj = OAT_Timer::active_for_entry( $entry_id );
    $timer = null;
    if ( $timer_obj ) {
        $timer = array(
            'id'            => (int) $timer_obj->id,
            'step'          => $timer_obj->step,
            'auto_action'   => $timer_obj->auto_action,
            'status'        => $timer_obj->status,
            'bump_count'    => isset( $timer_obj->bump_count ) ? (int) $timer_obj->bump_count : 0,
            'bump_required' => isset( $timer_obj->bump_required ) ? (int) $timer_obj->bump_required : 0,
            'started_at'    => $timer_obj->started_at,
            'expires_at'    => $timer_obj->expires_at,
        );
    }

    // BBP eligibility: entry has active timer with bump conditions met.
    $bbp_eligible = false;
    if ( $timer_obj && isset( $timer_obj->bump_count ) && isset( $timer_obj->bump_required ) ) {
        $bump_met = (int) $timer_obj->bump_count >= (int) $timer_obj->bump_required;
        $time_met = strtotime( $timer_obj->expires_at ) <= time();
        $bbp_eligible = $bump_met && $time_met;

        // Elevation bars BBP.
        if ( $bbp_eligible && OAT_Entry_Rule::has_elevation( $entry_id ) ) {
            $bbp_eligible = false;
        }
    }

    // Available actions for this user.
    $available_actions = array();
    if ( class_exists( 'OAT_Authorization' ) ) {
        // Determine what actions the current user can perform.
        $available_actions = owbn_gateway_oat_get_available_actions( $entry, $user_id );
    }

    // Watchers.
    $is_watching = OAT_Watcher::is_watching( $entry_id, $user_id );

    // Domain info.
    $domain_label = $entry->domain;
    if ( class_exists( 'OAT_Domain_Registry' ) ) {
        $label = OAT_Domain_Registry::get_label( $entry->domain );
        if ( $label !== '' ) {
            $domain_label = $label;
        }
    }

    // Step info.
    $step_label = $entry->current_step;
    if ( class_exists( 'OAT_Workflow_Engine' ) ) {
        $step_config = OAT_Workflow_Engine::get_step_config( $entry );
        if ( $step_config && isset( $step_config['label'] ) ) {
            $step_label = $step_config['label'];
        }
    }

    // Build user map.
    $user_map = owbn_gateway_oat_build_user_map( $user_ids );

    return owbn_gateway_respond( array(
        'entry'             => $entry_data,
        'meta'              => $meta,
        'assignees'         => $assignees,
        'timeline'          => $timeline,
        'rules'             => $rules,
        'timer'             => $timer,
        'bbp_eligible'      => $bbp_eligible,
        'available_actions' => $available_actions,
        'is_watching'       => $is_watching,
        'domain_label'      => $domain_label,
        'step_label'        => $step_label,
        'user_map'          => $user_map,
    ) );
}

/**
 * Determine available actions for a user on an entry.
 *
 * Checks the user's role against the entry's current step and status
 * to determine which workflow actions they can perform.
 *
 * @param object $entry   Entry object.
 * @param int    $user_id User ID.
 * @return array List of available action type strings.
 */
function owbn_gateway_oat_get_available_actions( $entry, $user_id ) {
    $actions = array();
    $entry_id = (int) $entry->id;

    // Terminal statuses — no actions available (except council_override).
    if ( OAT_Constants::is_terminal_status( $entry->status ) ) {
        // Only council_override can act on terminal entries.
        if ( OAT_Authorization::check( 'oat_exec_oversight' ) ) {
            $actions[] = 'council_override';
        }
        return $actions;
    }

    // Is this user the originator?
    $is_originator = ( (int) $entry->originator_id === $user_id );

    // Is this user an assignee at the current step?
    $is_assignee = false;
    $current_assignees = OAT_Assignee::for_entry_step( $entry_id, $entry->current_step );
    foreach ( $current_assignees as $a ) {
        if ( (int) $a->user_id === $user_id && $a->status === 'pending' ) {
            $is_assignee = true;
            break;
        }
    }

    // Originator actions.
    if ( $is_originator ) {
        $actions[] = 'cancel';
        $actions[] = 'bump';
    }

    // Assignee actions (review step).
    if ( $is_assignee ) {
        $actions[] = 'approve';
        $actions[] = 'deny';
        $actions[] = 'request_changes';
        $actions[] = 'hold';
        $actions[] = 'reassign';
        $actions[] = 'delegate';
    }

    // Hold/resume toggling.
    if ( $entry->status === 'on_hold' && $is_assignee ) {
        $actions[] = 'resume';
        // Remove hold since it's already on hold.
        $actions = array_values( array_diff( $actions, array( 'hold' ) ) );
    }

    // Archivist actions.
    if ( OAT_Authorization::check( 'oat_archivist' ) ) {
        $actions[] = 'record';
    }

    // Exec actions.
    if ( OAT_Authorization::check( 'oat_exec_oversight' ) ) {
        $actions[] = 'council_override';
    }

    return array_values( array_unique( $actions ) );
}


/**
 * Handle POST /owbn/v1/oat/domains
 *
 * Returns list of registered OAT domains with labels.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_domains( $request ) {
    if ( ! class_exists( 'OAT_Domain_Registry' ) ) {
        return owbn_gateway_respond( array() );
    }

    $all = OAT_Domain_Registry::get_all();
    $domains = array();

    foreach ( $all as $slug => $domain ) {
        $domains[] = array(
            'slug'  => $slug,
            'label' => $domain['label'],
        );
    }

    return owbn_gateway_respond( $domains );
}


/**
 * Handle POST /owbn/v1/oat/form-fields
 *
 * Returns field definitions for a domain + context.
 *
 * Request body:
 *   { "domain": "chronicle_reporting", "context": "submit" }
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_form_fields( $request ) {
    $body    = $request->get_json_params();
    $domain  = isset( $body['domain'] ) ? sanitize_text_field( $body['domain'] ) : '';
    $context = isset( $body['context'] ) ? sanitize_text_field( $body['context'] ) : 'submit';

    if ( '' === $domain ) {
        return owbn_gateway_respond( new WP_Error(
            'oat_missing_domain',
            'domain parameter is required.',
            array( 'status' => 400 )
        ) );
    }

    if ( ! class_exists( 'OAT_Form_Field' ) ) {
        return owbn_gateway_respond( array( 'fields' => array() ) );
    }

    $fields = OAT_Form_Field::get_fields( $domain, $context );

    return owbn_gateway_respond( array( 'fields' => $fields ) );
}

/**
 * Handle POST /owbn/v1/oat/rules/search
 *
 * Regulation rule autocomplete search.
 *
 * Request body:
 *   { "q": "assamite", "genre": "vampire" }
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_rules_search( $request ) {
    $body  = $request->get_json_params();
    $q     = isset( $body['q'] ) ? sanitize_text_field( $body['q'] ) : '';
    $genre = isset( $body['genre'] ) ? sanitize_text_field( $body['genre'] ) : null;

    if ( empty( $q ) || strlen( $q ) < 2 ) {
        return owbn_gateway_respond( array() );
    }

    $results = OAT_Regulation_Rule::search( $q, $genre );

    // Format for jQuery UI autocomplete (needs id + label + value).
    $out = array();
    foreach ( $results as $rule ) {
        $label = sprintf(
            '%s — %s — %s',
            $rule->genre,
            $rule->category,
            $rule->condition_name ? $rule->condition_name : $rule->subcategory
        );
        $out[] = array(
            'id'          => (int) $rule->id,
            'label'       => $label,
            'value'       => $label,
            'genre'       => $rule->genre,
            'category'    => $rule->category,
            'subcategory' => $rule->subcategory,
            'condition'   => $rule->condition_name,
            'pc_level'    => $rule->pc_level,
            'npc_level'   => $rule->npc_level,
            'coordinator' => $rule->controlling_coordinator,
            'elevation'   => (int) $rule->elevation,
        );
    }

    return owbn_gateway_respond( $out );
}

/**
 * Handle POST /owbn/v1/oat/rules
 *
 * Returns all active regulation rules for client-side caching.
 * Clients cache this via transient and use it to resolve coordinator
 * display when the OAT toolkit is not installed locally.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_rules_list( $request ) {
    if ( ! class_exists( 'OAT_Regulation_Rule' ) ) {
        return owbn_gateway_respond( array() );
    }

    $rules = OAT_Regulation_Rule::all( array(
        'active'   => 1,
        'per_page' => 5000,
        'offset'   => 0,
    ) );

    $out = array();
    foreach ( $rules as $rule ) {
        $out[] = array(
            'id'          => (int) $rule->id,
            'genre'       => $rule->genre,
            'category'    => $rule->category,
            'subcategory' => $rule->subcategory,
            'condition'   => $rule->condition_name,
            'pc_level'    => $rule->pc_level,
            'npc_level'   => $rule->npc_level,
            'coordinator' => $rule->controlling_coordinator,
            'elevation'   => (int) $rule->elevation,
        );
    }

    return owbn_gateway_respond( $out );
}
