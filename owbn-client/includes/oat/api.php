<?php

/**
 * OAT Client API
 * location: includes/oat/api.php
 *
 * Local/remote mode switching for OAT data access.
 * Local mode: calls OAT models directly (archivist.owbn.net).
 * Remote mode: HTTP POST to OAT gateway endpoints.
 *
 * @package OWBN-Client
 */

defined( 'ABSPATH' ) || exit;

// ══════════════════════════════════════════════════════════════════════════════
// MODE DETECTION
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Check if OAT should use local mode.
 *
 * Local requires both: mode set to 'local' AND OAT plugin classes available.
 *
 * @return bool
 */
function owc_oat_is_local() {
    return owc_get_mode( 'oat' ) === 'local' && class_exists( 'OAT_Entry' );
}

// ══════════════════════════════════════════════════════════════════════════════
// TIMESTAMP FORMATTING
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Format a Unix timestamp or datetime string for display.
 *
 * @param mixed $value Unix timestamp or datetime string.
 * @return string Formatted date or empty string.
 */
function owc_oat_format_date( $value ) {
    if ( empty( $value ) ) {
        return '';
    }
    if ( is_numeric( $value ) ) {
        return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $value );
    }
    return $value;
}

/**
 * Format known timestamp fields in a remote API response.
 *
 * Applies owc_oat_format_date() to standard timestamp fields so remote mode
 * data matches the formatting applied in local mode.
 *
 * @param array $data Response data from the gateway.
 * @return array Data with timestamps formatted.
 */
function owc_oat_format_remote_timestamps( $data ) {
    $ts_fields = array( 'created_at', 'updated_at', 'expires_at' );

    // Format top-level entry timestamps.
    if ( isset( $data['entry'] ) && is_array( $data['entry'] ) ) {
        foreach ( $ts_fields as $f ) {
            if ( isset( $data['entry'][ $f ] ) ) {
                $data['entry'][ $f ] = owc_oat_format_date( $data['entry'][ $f ] );
            }
        }
    }

    // Format timeline timestamps.
    if ( isset( $data['timeline'] ) && is_array( $data['timeline'] ) ) {
        foreach ( $data['timeline'] as &$t ) {
            foreach ( $ts_fields as $f ) {
                if ( isset( $t[ $f ] ) ) {
                    $t[ $f ] = owc_oat_format_date( $t[ $f ] );
                }
            }
        }
        unset( $t );
    }

    // Format timer timestamps.
    if ( isset( $data['timer'] ) && is_array( $data['timer'] ) ) {
        foreach ( $ts_fields as $f ) {
            if ( isset( $data['timer'][ $f ] ) ) {
                $data['timer'][ $f ] = owc_oat_format_date( $data['timer'][ $f ] );
            }
        }
    }

    // Format assignments/watched arrays.
    foreach ( array( 'assignments', 'watched' ) as $list_key ) {
        if ( isset( $data[ $list_key ] ) && is_array( $data[ $list_key ] ) ) {
            foreach ( $data[ $list_key ] as &$item ) {
                foreach ( $ts_fields as $f ) {
                    if ( isset( $item[ $f ] ) ) {
                        $item[ $f ] = owc_oat_format_date( $item[ $f ] );
                    }
                }
            }
            unset( $item );
        }
    }

    return $data;
}

// ══════════════════════════════════════════════════════════════════════════════
// REMOTE REQUEST HELPER
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Make a remote OAT gateway request.
 *
 * Sends POST with API key + user email headers for authentication.
 *
 * @param string $endpoint  Endpoint path (e.g. 'inbox', 'entry/42').
 * @param array  $body      Request body.
 * @return array|WP_Error   Response data or error.
 */
function owc_oat_request( $endpoint, $body = array() ) {
    $base = owc_get_remote_base( 'oat' );
    if ( empty( $base ) ) {
        return new WP_Error( 'oat_no_remote', 'OAT remote gateway URL is not configured.' );
    }

    $api_key = owc_get_remote_key( 'oat' );
    if ( empty( $api_key ) ) {
        return new WP_Error( 'oat_no_key', 'OAT remote API key is not configured.' );
    }

    $url = $base . 'oat/' . ltrim( $endpoint, '/' );

    $current_user = wp_get_current_user();
    $email = $current_user && $current_user->ID ? $current_user->user_email : '';

    $headers = array(
        'Content-Type'      => 'application/json',
        'x-api-key'         => $api_key,
        'x-oat-user-email'  => $email,
    );

    // Include player_id if available (for JIT provisioning on remote).
    if ( defined( 'OWC_PLAYER_ID_META_KEY' ) && $current_user && $current_user->ID ) {
        $player_id = get_user_meta( $current_user->ID, OWC_PLAYER_ID_META_KEY, true );
        if ( $player_id ) {
            $headers['x-oat-player-id'] = $player_id;
        }
    }

    $response = wp_remote_post( $url, array(
        'timeout' => 30,
        'headers' => $headers,
        'body'    => wp_json_encode( $body ),
    ) );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code( $response );
    $raw  = wp_remote_retrieve_body( $response );
    $data = json_decode( $raw, true );

    if ( $code !== 200 ) {
        $message = isset( $data['message'] ) ? $data['message'] : 'OAT gateway request failed.';
        return new WP_Error( 'oat_api_error', $message, array( 'status' => $code ) );
    }

    if ( ! is_array( $data ) ) {
        return new WP_Error( 'oat_api_error', 'Invalid JSON response from OAT gateway.' );
    }

    return $data;
}

// ══════════════════════════════════════════════════════════════════════════════
// DOMAINS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get all OAT domains.
 *
 * @return array List of { slug, label } objects.
 */
function owc_oat_get_domains() {
    if ( owc_oat_is_local() ) {
        $domains = OAT_Domain_Registry::get_all();
        $out = array();
        foreach ( $domains as $slug => $domain ) {
            $out[] = array(
                'slug'  => $slug,
                'label' => $domain['label'],
            );
        }
        return $out;
    }

    return owc_oat_request( 'domains' );
}

// ══════════════════════════════════════════════════════════════════════════════
// INBOX
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get the current user's inbox data.
 *
 * @param string $domain_filter Optional domain slug to filter assignments.
 * @return array|WP_Error { assignments, watched, my_entries, user_map }
 */
function owc_oat_get_inbox( $domain_filter = '' ) {
    if ( owc_oat_is_local() ) {
        $user_id = get_current_user_id();

        // Assignments.
        $assignments_raw = OAT_Assignee::inbox( $user_id );
        if ( $domain_filter ) {
            $assignments_raw = array_filter( $assignments_raw, function ( $a ) use ( $domain_filter ) {
                return isset( $a->domain ) && $a->domain === $domain_filter;
            } );
            $assignments_raw = array_values( $assignments_raw );
        }

        // Deduplicate assignments by entry_id (user may have multiple step assignments).
        $assignments = array();
        $seen_entries = array();
        foreach ( $assignments_raw as $a ) {
            $eid = (int) $a->entry_id;
            if ( isset( $seen_entries[ $eid ] ) ) {
                continue;
            }
            $seen_entries[ $eid ] = true;
            $assignments[] = array(
                'entry_id'     => $eid,
                'domain'       => $a->domain,
                'domain_label' => OAT_Domain_Registry::get_label( $a->domain ) ?: $a->domain,
                'status'       => isset( $a->entry_status ) ? $a->entry_status : $a->status,
                'current_step' => isset( $a->current_step ) ? $a->current_step : '',
                'title'        => isset( $a->title ) ? $a->title : '',
                'created_at'   => owc_oat_format_date( isset( $a->created_at ) ? $a->created_at : '' ),
            );
        }

        // Watched entries.
        $watched_raw = OAT_Watcher::for_user( $user_id );
        $watched = array();
        foreach ( $watched_raw as $w ) {
            $watched[] = array(
                'entry_id'     => (int) $w->entry_id,
                'domain'       => $w->domain,
                'domain_label' => OAT_Domain_Registry::get_label( $w->domain ) ?: $w->domain,
                'status'       => $w->status,
                'current_step' => isset( $w->current_step ) ? $w->current_step : '',
                'title'        => isset( $w->title ) ? $w->title : '',
                'updated_at'   => owc_oat_format_date( isset( $w->updated_at ) ? $w->updated_at : '' ),
            );
        }

        // User's own entries (recent).
        $my_entries_raw = OAT_Entry::for_originator( $user_id, array( 'per_page' => 10 ) );
        $my_entries = array();
        foreach ( $my_entries_raw as $e ) {
            $my_entries[] = array(
                'entry_id'     => (int) $e->id,
                'domain'       => $e->domain,
                'domain_label' => OAT_Domain_Registry::get_label( $e->domain ) ?: $e->domain,
                'status'       => $e->status,
                'current_step' => $e->current_step,
                'created_at'   => owc_oat_format_date( $e->created_at ),
            );
        }

        // Build user map.
        $user_ids = array();
        foreach ( $assignments_raw as $a ) {
            if ( isset( $a->originator_id ) ) {
                $user_ids[] = (int) $a->originator_id;
            }
        }
        $user_ids[] = $user_id;
        $user_ids = array_unique( array_filter( $user_ids ) );

        $user_map = array();
        foreach ( $user_ids as $uid ) {
            $u = get_userdata( $uid );
            if ( $u ) {
                $user_map[ $uid ] = $u->display_name;
            }
        }

        return array(
            'assignments' => $assignments,
            'watched'     => $watched,
            'my_entries'  => $my_entries,
            'user_map'    => $user_map,
        );
    }

    $body = array();
    if ( $domain_filter ) {
        $body['domain'] = $domain_filter;
    }
    $result = owc_oat_request( 'inbox', $body );
    return is_wp_error( $result ) ? $result : owc_oat_format_remote_timestamps( $result );
}

// ══════════════════════════════════════════════════════════════════════════════
// ENTRIES LIST
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get paginated entry list.
 *
 * @param array $args { domain, status, page, per_page }
 * @return array|WP_Error { entries, total, page, per_page, user_map }
 */
function owc_oat_get_entries( $args = array() ) {
    if ( owc_oat_is_local() ) {
        $page     = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;
        $per_page = isset( $args['per_page'] ) ? max( 1, min( 100, (int) $args['per_page'] ) ) : 20;
        $domain   = isset( $args['domain'] ) ? $args['domain'] : '';
        $status   = isset( $args['status'] ) ? $args['status'] : '';

        $offset = ( $page - 1 ) * $per_page;

        $query_args = array(
            'per_page' => $per_page,
            'offset'   => $offset,
        );
        if ( $domain ) {
            $query_args['domain'] = $domain;
        }
        if ( $status ) {
            $query_args['status'] = $status;
        }

        $entries_raw = OAT_Entry::all( $query_args );
        $total       = OAT_Entry::count( $query_args );

        $entries  = array();
        $user_ids = array();
        foreach ( $entries_raw as $e ) {
            $entries[] = array(
                'id'            => (int) $e->id,
                'domain'        => $e->domain,
                'domain_label'  => OAT_Domain_Registry::get_label( $e->domain ) ?: $e->domain,
                'status'        => $e->status,
                'current_step'  => $e->current_step,
                'originator_id' => (int) $e->originator_id,
                'created_at'    => owc_oat_format_date( $e->created_at ),
                'updated_at'    => owc_oat_format_date( $e->updated_at ),
            );
            $user_ids[] = (int) $e->originator_id;
        }

        $user_ids = array_unique( array_filter( $user_ids ) );
        $user_map = array();
        foreach ( $user_ids as $uid ) {
            $u = get_userdata( $uid );
            if ( $u ) {
                $user_map[ $uid ] = $u->display_name;
            }
        }

        return array(
            'entries'  => $entries,
            'total'    => (int) $total,
            'page'     => $page,
            'per_page' => $per_page,
            'user_map' => $user_map,
        );
    }

    $result = owc_oat_request( 'entries', $args );
    if ( is_wp_error( $result ) ) {
        return $result;
    }
    // Format timestamps in each entry row.
    if ( isset( $result['entries'] ) && is_array( $result['entries'] ) ) {
        foreach ( $result['entries'] as &$e ) {
            if ( isset( $e['created_at'] ) ) {
                $e['created_at'] = owc_oat_format_date( $e['created_at'] );
            }
            if ( isset( $e['updated_at'] ) ) {
                $e['updated_at'] = owc_oat_format_date( $e['updated_at'] );
            }
        }
        unset( $e );
    }
    return $result;
}

// ══════════════════════════════════════════════════════════════════════════════
// ENTRY DETAIL
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get full entry detail bundle.
 *
 * @param int $entry_id
 * @return array|WP_Error Full entry bundle.
 */
function owc_oat_get_entry( $entry_id ) {
    if ( owc_oat_is_local() ) {
        $entry = OAT_Entry::find( $entry_id );
        if ( ! $entry ) {
            return new WP_Error( 'oat_not_found', 'Entry not found.', array( 'status' => 404 ) );
        }

        $user_id = get_current_user_id();

        // Meta.
        $meta_raw = OAT_Entry_Meta::all( $entry_id );
        $meta = array();
        foreach ( $meta_raw as $m ) {
            $meta[ $m->meta_key ] = $m->meta_value;
        }

        // Assignees.
        $assignees_raw = OAT_Assignee::for_entry( $entry_id );
        $assignees = array();
        foreach ( $assignees_raw as $a ) {
            $assignees[] = array(
                'user_id' => (int) $a->user_id,
                'step'    => $a->step,
                'status'  => $a->status,
            );
        }

        // Determine visibility tier.
        $tier = OAT_Constants::TIER_STAFF;
        if ( class_exists( 'OAT_Authorization' ) ) {
            if ( OAT_Authorization::check( OAT_Constants::CAP_ARCHIVIST ) ) {
                $tier = OAT_Constants::TIER_ARCHIVIST;
            } elseif ( OAT_Authorization::check( OAT_Constants::CAP_COORD_REVIEW ) ) {
                $tier = OAT_Constants::TIER_COORDINATOR;
            }
        }

        // Timeline (tier-filtered).
        $timeline_raw = OAT_Timeline::for_entry( $entry_id, $tier );
        $timeline = array();
        foreach ( $timeline_raw as $t ) {
            $timeline[] = array(
                'action_type'     => $t->action_type,
                'actor_id'        => (int) $t->actor_id,
                'step'            => $t->step,
                'visibility_tier' => $t->visibility_tier,
                'note'            => $t->note,
                'created_at'      => owc_oat_format_date( $t->created_at ),
            );
        }

        // Rules.
        $rules_raw = OAT_Entry_Rule::for_entry( $entry_id );
        $rules = array();
        foreach ( $rules_raw as $r ) {
            $rule_detail = OAT_Regulation_Rule::find( $r->rule_id );
            $rules[] = array(
                'rule_id'      => (int) $r->rule_id,
                'genre'        => $rule_detail ? $rule_detail->genre : '',
                'category'     => $rule_detail ? $rule_detail->category : '',
                'subcategory'  => $rule_detail ? $rule_detail->subcategory : '',
                'condition'    => $rule_detail ? $rule_detail->condition_name : '',
                'pc_level'     => $rule_detail ? $rule_detail->pc_level : '',
                'coordinator'  => $rule_detail ? $rule_detail->controlling_coordinator : '',
                'elevation'    => $rule_detail ? (int) $rule_detail->elevation : 0,
            );
        }

        // Timer.
        $timer_raw = OAT_Timer::active_for_entry( $entry_id );
        $timer = null;
        if ( $timer_raw ) {
            $timer = array(
                'type'       => $timer_raw->timer_type,
                'expires_at' => owc_oat_format_date( $timer_raw->expires_at ),
                'status'     => $timer_raw->status,
            );
        }

        // BBP eligible.
        $bbp_eligible = false;
        if ( (int) $entry->originator_id === $user_id ) {
            $bbp_eligible = OAT_Timer_Engine::check_bbp_eligible( $entry_id );
        }

        // Available actions.
        $available_actions = array();
        if ( class_exists( 'OAT_Page_Entry' ) ) {
            $available_actions = OAT_Page_Entry::get_available_actions( $entry, $user_id, $assignees_raw );
        } else {
            // Inline fallback mirroring gateway handler logic.
            $available_actions = owc_oat_compute_available_actions( $entry, $user_id, $assignees_raw );
        }

        // Is watching.
        $watchers_raw = OAT_Watcher::for_entry( $entry_id );
        $is_watching = false;
        foreach ( $watchers_raw as $w ) {
            if ( (int) $w->user_id === $user_id ) {
                $is_watching = true;
                break;
            }
        }

        // Step label.
        $step_config = OAT_Workflow_Engine::get_step_config( $entry );
        $step_label = $step_config && isset( $step_config['label'] ) ? $step_config['label'] : $entry->current_step;

        // User map.
        $user_ids = array( (int) $entry->originator_id, $user_id );
        foreach ( $assignees_raw as $a ) {
            $user_ids[] = (int) $a->user_id;
        }
        foreach ( $timeline_raw as $t ) {
            $user_ids[] = (int) $t->actor_id;
        }
        foreach ( $watchers_raw as $w ) {
            $user_ids[] = (int) $w->user_id;
        }
        $user_ids = array_unique( array_filter( $user_ids ) );
        $user_map = array();
        foreach ( $user_ids as $uid ) {
            $u = get_userdata( $uid );
            if ( $u ) {
                $user_map[ $uid ] = $u->display_name;
            }
        }

        return array(
            'entry'             => array(
                'id'              => (int) $entry->id,
                'domain'          => $entry->domain,
                'status'          => $entry->status,
                'current_step'    => $entry->current_step,
                'originator_id'   => (int) $entry->originator_id,
                'chronicle_slug'  => isset( $entry->chronicle_slug ) ? $entry->chronicle_slug : '',
                'coordinator_genre' => isset( $entry->coordinator_genre ) ? $entry->coordinator_genre : '',
                'character_id'    => isset( $entry->character_id ) ? (int) $entry->character_id : 0,
                'created_at'      => owc_oat_format_date( $entry->created_at ),
                'updated_at'      => owc_oat_format_date( $entry->updated_at ),
            ),
            'meta'              => $meta,
            'assignees'         => $assignees,
            'timeline'          => $timeline,
            'rules'             => $rules,
            'timer'             => $timer,
            'bbp_eligible'      => $bbp_eligible,
            'available_actions' => $available_actions,
            'is_watching'       => $is_watching,
            'domain_label'      => OAT_Domain_Registry::get_label( $entry->domain ) ?: $entry->domain,
            'step_label'        => $step_label,
            'user_map'          => $user_map,
        );
    }

    $result = owc_oat_request( 'entry/' . (int) $entry_id );
    return is_wp_error( $result ) ? $result : owc_oat_format_remote_timestamps( $result );
}

// ══════════════════════════════════════════════════════════════════════════════
// SUBMIT
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Submit a new OAT entry.
 *
 * @param array $data {
 *     domain, title, description, chronicle_slug, coordinator_genre,
 *     rules (array of rule IDs), meta (array of key => value), note
 * }
 * @return array|WP_Error { entry_id, status, message }
 */
function owc_oat_submit( $data ) {
    if ( owc_oat_is_local() ) {
        $user_id = get_current_user_id();
        $domain_slug = isset( $data['domain'] ) ? sanitize_text_field( $data['domain'] ) : '';

        if ( empty( $domain_slug ) ) {
            return new WP_Error( 'oat_missing_domain', 'Domain is required.' );
        }

        $domain = OAT_Domain_Registry::get( $domain_slug );
        if ( ! $domain ) {
            return new WP_Error( 'oat_invalid_domain', 'Invalid domain: ' . $domain_slug );
        }

        // Build entry data.
        $entry_data = array(
            'domain'        => $domain_slug,
            'status'        => 'pending',
            'current_step'  => 'submit',
            'originator_id' => $user_id,
        );

        if ( ! empty( $data['chronicle_slug'] ) ) {
            $entry_data['chronicle_slug'] = sanitize_text_field( $data['chronicle_slug'] );
        }
        if ( ! empty( $data['coordinator_genre'] ) ) {
            $entry_data['coordinator_genre'] = sanitize_text_field( $data['coordinator_genre'] );
        }

        $entry_id = OAT_Entry::create( $entry_data );
        if ( ! $entry_id ) {
            return new WP_Error( 'oat_create_failed', 'Failed to create entry.' );
        }

        // Build meta.
        $meta = array();
        if ( ! empty( $data['title'] ) ) {
            $meta['title'] = sanitize_text_field( $data['title'] );
        }
        if ( ! empty( $data['description'] ) ) {
            $meta['description'] = wp_kses_post( $data['description'] );
        }
        if ( ! empty( $data['meta'] ) && is_array( $data['meta'] ) ) {
            foreach ( $data['meta'] as $key => $value ) {
                $safe_key = sanitize_key( $key );
                if ( $safe_key !== '' ) {
                    $meta[ $safe_key ] = wp_kses_post( $value );
                }
            }
        }

        // Attach regulation rules.
        if ( ! empty( $data['rules'] ) && is_array( $data['rules'] ) ) {
            foreach ( $data['rules'] as $rule_id ) {
                $rule_id = (int) $rule_id;
                if ( $rule_id > 0 ) {
                    OAT_Entry_Rule::create( $entry_id, $rule_id );
                }
            }

            // Set regulation meta from rules.
            owc_oat_set_regulation_meta( $entry_id, $data['rules'] );
        }

        // Fire submit action through workflow engine.
        $result = OAT_Workflow_Engine::process_action( $entry_id, 'submit', $user_id, array(
            'note' => isset( $data['note'] ) ? sanitize_textarea_field( $data['note'] ) : '',
            'meta' => $meta,
        ) );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Auto-watch the originator.
        OAT_Watcher::add( $entry_id, $user_id, $user_id );

        $entry = OAT_Entry::find( $entry_id );
        return array(
            'entry_id' => $entry_id,
            'status'   => $entry ? $entry->status : 'pending',
            'message'  => 'Entry created successfully.',
        );
    }

    return owc_oat_request( 'submit', $data );
}

// ══════════════════════════════════════════════════════════════════════════════
// ACTION
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Execute a workflow action on an entry.
 *
 * @param int    $entry_id
 * @param string $action_type
 * @param string $note
 * @param array  $extra_data  Optional extra data (vote_reference, additional_seconds, target_user_id).
 * @return array|WP_Error { entry_id, status, step, message }
 */
function owc_oat_execute_action( $entry_id, $action_type, $note = '', $extra_data = array() ) {
    if ( owc_oat_is_local() ) {
        $user_id = get_current_user_id();

        $entry = OAT_Entry::find( $entry_id );
        if ( ! $entry ) {
            return new WP_Error( 'oat_not_found', 'Entry not found.', array( 'status' => 404 ) );
        }

        $action_data = array_merge( $extra_data, array( 'note' => $note ) );
        $result = OAT_Workflow_Engine::process_action( $entry_id, $action_type, $user_id, $action_data );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $entry = OAT_Entry::find( $entry_id );
        return array(
            'entry_id' => $entry_id,
            'status'   => $entry ? $entry->status : '',
            'step'     => $entry ? $entry->current_step : '',
            'message'  => 'Action executed successfully.',
        );
    }

    $body = array(
        'entry_id' => (int) $entry_id,
        'action'   => $action_type,
        'note'     => $note,
    );
    if ( ! empty( $extra_data ) ) {
        $body['data'] = $extra_data;
    }
    return owc_oat_request( 'action', $body );
}

// ══════════════════════════════════════════════════════════════════════════════
// WATCH
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Toggle watch on an entry.
 *
 * @param int    $entry_id
 * @param string $watch_action 'add' or 'remove'.
 * @return array|WP_Error { entry_id, watching }
 */
function owc_oat_toggle_watch( $entry_id, $watch_action = 'add' ) {
    if ( owc_oat_is_local() ) {
        $user_id = get_current_user_id();

        $entry = OAT_Entry::find( $entry_id );
        if ( ! $entry ) {
            return new WP_Error( 'oat_not_found', 'Entry not found.', array( 'status' => 404 ) );
        }

        if ( $watch_action === 'remove' ) {
            OAT_Watcher::remove( $entry_id, $user_id );
            $watching = false;
        } else {
            OAT_Watcher::add( $entry_id, $user_id, $user_id );
            $watching = true;
        }

        return array(
            'entry_id' => $entry_id,
            'watching' => $watching,
        );
    }

    return owc_oat_request( 'watch', array(
        'entry_id' => (int) $entry_id,
        'action'   => $watch_action,
    ) );
}

// ══════════════════════════════════════════════════════════════════════════════
// RULES SEARCH
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Search regulation rules for autocomplete.
 *
 * @param string $term  Search term (min 2 chars).
 * @param int    $limit Max results.
 * @return array|WP_Error List of rule results.
 */
function owc_oat_search_rules( $term, $limit = 20 ) {
    if ( owc_oat_is_local() ) {
        $results = OAT_Regulation_Rule::search( $term );
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
        return $out;
    }

    return owc_oat_request( 'rules/search', array(
        'term'  => $term,
        'limit' => $limit,
    ) );
}

// ══════════════════════════════════════════════════════════════════════════════
// FORM FIELDS (DB-driven, per domain + context)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get form field definitions for a domain + context.
 *
 * Local mode: queries OAT_Form_Field model directly.
 * Remote mode: calls gateway /oat/form-fields endpoint.
 *
 * Falls back to legacy get_meta_keys() if oat_form_fields table has no rows
 * for the requested domain (backward compat during migration).
 *
 * @param string $domain_slug Domain slug.
 * @param string $context     Context: 'submit', 'review', 'escalate', 'resolve'.
 * @return array Array of field definition arrays.
 */
function owc_oat_get_form_fields( $domain_slug, $context = 'submit' ) {
    if ( owc_oat_is_local() ) {
        // Try DB-driven fields first.
        if ( class_exists( 'OAT_Form_Field' ) ) {
            $fields = OAT_Form_Field::get_fields( $domain_slug, $context );
            if ( ! empty( $fields ) ) {
                return $fields;
            }
        }

        // Fallback: legacy get_meta_keys() for domains not yet seeded.
        if ( 'submit' === $context && class_exists( 'OAT_Domain_Registry' ) ) {
            $domain = OAT_Domain_Registry::get_php_domain( $domain_slug );
            if ( $domain && method_exists( $domain, 'get_meta_keys' ) ) {
                $meta_keys = $domain->get_meta_keys();
                $fields = array();
                $order = 10;
                foreach ( $meta_keys as $key => $config ) {
                    $fields[] = array(
                        'key'         => $key,
                        'type'        => isset( $config['type'] ) ? $config['type'] : 'text',
                        'label'       => isset( $config['label'] ) ? $config['label'] : ucfirst( str_replace( '_', ' ', $key ) ),
                        'required'    => isset( $config['required'] ) ? (bool) $config['required'] : false,
                        'sort_order'  => $order,
                        'options'     => isset( $config['options'] ) ? $config['options'] : null,
                        'condition'   => isset( $config['condition'] ) ? $config['condition'] : null,
                        'placeholder' => isset( $config['placeholder'] ) ? $config['placeholder'] : '',
                        'help_text'   => isset( $config['description'] ) ? $config['description'] : null,
                        'default'     => isset( $config['default'] ) ? $config['default'] : '',
                        'validation'  => null,
                        'attributes'  => isset( $config['attributes'] ) ? $config['attributes'] : null,
                    );
                    $order += 10;
                }
                return $fields;
            }
        }

        return array();
    }

    // Remote mode: call the gateway.
    $result = owc_oat_request( 'form-fields', array(
        'domain'  => $domain_slug,
        'context' => $context,
    ) );

    if ( is_wp_error( $result ) ) {
        return array();
    }

    return isset( $result['fields'] ) ? $result['fields'] : array();
}

/**
 * Legacy wrapper — calls owc_oat_get_form_fields with 'submit' context.
 *
 * @param string $domain_slug
 * @return array
 */
function owc_oat_get_domain_fields( $domain_slug ) {
    return owc_oat_get_form_fields( $domain_slug, 'submit' );
}

// ══════════════════════════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Compute available actions for a user on an entry.
 *
 * Used in local mode when OAT_Page_Entry class is not loaded.
 * Mirrors the gateway handler logic.
 *
 * @param object $entry
 * @param int    $user_id
 * @param array  $assignees Raw assignee objects.
 * @return array
 */
function owc_oat_compute_available_actions( $entry, $user_id, $assignees ) {
    $actions = array();

    if ( OAT_Constants::is_terminal_status( $entry->status ) ) {
        if ( $entry->status === OAT_Constants::STATUS_DENIED ) {
            if ( class_exists( 'OAT_Authorization' ) && OAT_Authorization::check( OAT_Constants::CAP_EXEC_OVERSIGHT ) ) {
                $actions[] = 'council_override';
            }
        }
        return $actions;
    }

    $is_assignee = false;
    foreach ( $assignees as $a ) {
        if ( (int) $a->user_id === $user_id && $a->step === $entry->current_step && $a->status === 'pending' ) {
            $is_assignee = true;
            break;
        }
    }

    if ( $is_assignee ) {
        $actions[] = 'approve';
        $actions[] = 'deny';
        $actions[] = 'request_changes';
        $actions[] = 'reassign';
        $actions[] = 'delegate';
    }

    if ( (int) $entry->originator_id === $user_id ) {
        $actions[] = 'cancel';
        $actions[] = 'bump';
    }

    if ( class_exists( 'OAT_Authorization' ) && OAT_Authorization::check( OAT_Constants::CAP_EXEC_OVERSIGHT ) ) {
        $actions[] = 'hold';
        $actions[] = 'timer_extend';
    }

    if ( $entry->status === OAT_Constants::STATUS_HELD ) {
        $actions[] = 'resume';
    }

    if ( $is_assignee && class_exists( 'OAT_Authorization' ) && OAT_Authorization::check( OAT_Constants::CAP_ARCHIVIST ) ) {
        $actions[] = 'record';
    }

    return array_unique( $actions );
}

/**
 * Set regulation meta from linked rules.
 *
 * Determines requires_coord and regulation_level from highest rule priority.
 *
 * @param int   $entry_id
 * @param array $rule_ids
 * @return void
 */
function owc_oat_set_regulation_meta( $entry_id, $rule_ids ) {
    $requires_coord = '0';
    $regulation_level = '';

    $priority = array(
        'council_vote'         => 4,
        'disallowed'           => 3,
        'coordinator_approval' => 2,
        'coordinator_notify'   => 1,
    );
    $highest = 0;

    foreach ( $rule_ids as $rule_id ) {
        $rule = OAT_Regulation_Rule::find( (int) $rule_id );
        if ( ! $rule ) {
            continue;
        }

        $level = $rule->pc_level;
        if ( $level && isset( $priority[ $level ] ) && $priority[ $level ] > $highest ) {
            $highest = $priority[ $level ];
            $regulation_level = $level;
        }
    }

    if ( $highest >= 2 ) {
        $requires_coord = '1';
    }

    OAT_Entry_Meta::set( $entry_id, 'requires_coord', $requires_coord );
    if ( $regulation_level ) {
        OAT_Entry_Meta::set( $entry_id, 'regulation_level', $regulation_level );
    }
}
