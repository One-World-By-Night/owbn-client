<?php

/**
 * OAT Client API
 *
 * Local/remote mode switching for OAT data access.
 * Local mode: calls OAT models directly (archivist.owbn.net).
 * Remote mode: HTTP POST to OAT gateway endpoints.
 *
 */

defined( 'ABSPATH' ) || exit;


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


/**
 * Prepend the current TranslatePress language prefix to a root-relative URL.
 *
 * Detects the language prefix from the current request URI (e.g. /pt/ from /pt/oat-registry/)
 * and prepends it to the given path so links opened in new tabs preserve the language.
 *
 * @param string $url Root-relative URL (e.g. /oat-registry-detail/).
 * @return string URL with language prefix if applicable.
 */
function owc_oat_localize_url( $url ) {
    // Detect language prefix from multiple sources.
    $lang = '';

    // 1. TranslatePress global.
    global $TRP_LANGUAGE;
    if ( ! empty( $TRP_LANGUAGE ) && class_exists( 'TRP_Translate_Press' ) ) {
        $trp      = TRP_Translate_Press::get_trp_instance();
        $settings = $trp->get_component( 'settings' )->get_settings();
        $default  = $settings['default-language'] ?? 'en_US';
        if ( $TRP_LANGUAGE !== $default ) {
            // Map locale to URL slug (e.g. pt_BR -> pt).
            $slugs = $settings['url-slugs'] ?? array();
            $lang  = isset( $slugs[ $TRP_LANGUAGE ] ) ? $slugs[ $TRP_LANGUAGE ] : '';
        }
    }

    // 2. Fallback: detect from REQUEST_URI.
    if ( ! $lang ) {
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
        if ( preg_match( '#^/([a-z]{2}(?:-[a-z]{2})?)/#i', $request_uri, $m ) ) {
            $default_lang = apply_filters( 'owc_oat_default_language', 'en' );
            if ( $m[1] !== $default_lang ) {
                $lang = $m[1];
            }
        }
    }

    if ( $lang && strpos( $url, '/' . $lang . '/' ) !== 0 ) {
        $url = '/' . $lang . rtrim( $url, '/' ) . '/';
    }

    return $url;
}


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

function owc_oat_get_forms_for_domain( $domain_slug ) {
    if ( owc_oat_is_local() ) {
        if ( ! class_exists( 'OAT_Domain_Registry' ) ) {
            return array();
        }
        $forms = OAT_Domain_Registry::get_forms( $domain_slug );
        $out = array();
        foreach ( $forms as $form ) {
            // Check form-level access restrictions
            if ( ! owc_oat_user_can_access_form( $form->slug ) ) {
                continue;
            }
            $out[] = array(
                'id'    => (int) $form->id,
                'slug'  => $form->slug,
                'label' => $form->label,
            );
        }
        return $out;
    }

    $result = owc_oat_request( 'domain-forms', array( 'domain' => $domain_slug ) );
    if ( is_wp_error( $result ) ) {
        return array();
    }
    return isset( $result['forms'] ) ? $result['forms'] : ( is_array( $result ) ? $result : array() );
}

/**
 * Check if the current user can access a specific form.
 *
 * Forms can define access rules via the owc_oat_form_access_rules filter.
 * Default: all forms are accessible.
 *
 * @param string $form_slug The form slug.
 * @return bool
 */
function owc_oat_user_can_access_form( $form_slug ) {
    // ca_manage_satellites: only HST/CM of non-probationary, non-satellite chronicles
    if ( $form_slug === 'ca_manage_satellites' ) {
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }
        if ( ! function_exists( '_owc_asc_get_user_entity_entries' ) ) {
            return false;
        }
        $entries = _owc_asc_get_user_entity_entries( 'chronicle', array( 'HST', 'CM' ) );
        if ( empty( $entries ) ) {
            return false;
        }
        // Check if any of the user's chronicles are non-probationary and non-satellite
        $chronicles = function_exists( 'owc_get_chronicles' ) ? owc_get_chronicles() : array();
        if ( is_wp_error( $chronicles ) || ! is_array( $chronicles ) ) {
            return false;
        }
        $chron_lookup = array();
        foreach ( $chronicles as $c ) {
            $chron_lookup[ $c['slug'] ] = $c;
        }
        foreach ( $entries as $entry ) {
            $slug = $entry['slug'];
            if ( ! isset( $chron_lookup[ $slug ] ) ) {
                continue;
            }
            $c = $chron_lookup[ $slug ];
            $is_probationary = ! empty( $c['chronicle_probationary'] ) && $c['chronicle_probationary'] !== '0';
            $is_satellite = ! empty( $c['chronicle_satellite'] ) && $c['chronicle_satellite'] !== '0';
            if ( ! $is_probationary && ! $is_satellite ) {
                return true;
            }
        }
        return false;
    }

    return true;
}


/**
 * Get the current user's inbox data.
 *
 * @param string $domain_filter Optional domain slug to filter assignments.
 * @return array|WP_Error { assignments, watched, my_entries, user_map }
 */
function owc_oat_get_inbox( $domain_filter = '' ) {
    if ( owc_oat_is_local() ) {
        $user_id = get_current_user_id();

        // Assignments: explicit assignee rows.
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
                'entry_id'      => $eid,
                'domain'        => $a->domain,
                'domain_label'  => OAT_Domain_Registry::get_label( $a->domain ) ?: $a->domain,
                'originator_id' => isset( $a->originator_id ) ? (int) $a->originator_id : 0,
                'status'        => isset( $a->entry_status ) ? $a->entry_status : $a->status,
                'current_step'  => isset( $a->current_step ) ? $a->current_step : '',
                'title'         => isset( $a->title ) ? $a->title : '',
                'created_at'    => owc_oat_format_date( isset( $a->created_at ) ? $a->created_at : '' ),
            );
        }

        // Capability-based inbox: entries at steps the user can act on.
        $cap_entries = owc_oat_capability_inbox_entries( $user_id, $domain_filter );
        foreach ( $cap_entries as $ce ) {
            $eid = (int) $ce->id;
            if ( isset( $seen_entries[ $eid ] ) ) {
                continue;
            }
            $seen_entries[ $eid ] = true;
            $assignments[] = array(
                'entry_id'      => $eid,
                'domain'        => $ce->domain,
                'domain_label'  => OAT_Domain_Registry::get_label( $ce->domain ) ?: $ce->domain,
                'originator_id' => isset( $ce->originator_id ) ? (int) $ce->originator_id : 0,
                'status'        => $ce->status,
                'current_step'  => $ce->current_step,
                'title'         => isset( $ce->title ) ? $ce->title : '',
                'created_at'    => owc_oat_format_date( $ce->created_at ),
            );
        }

        // Watched entries.
        $watched_raw = OAT_Watcher::for_user( $user_id );
        $watched = array();
        foreach ( $watched_raw as $w ) {
            $watched[] = array(
                'entry_id'      => (int) $w->entry_id,
                'domain'        => $w->domain,
                'domain_label'  => OAT_Domain_Registry::get_label( $w->domain ) ?: $w->domain,
                'originator_id' => isset( $w->originator_id ) ? (int) $w->originator_id : 0,
                'status'        => $w->status,
                'current_step'  => isset( $w->current_step ) ? $w->current_step : '',
                'title'         => isset( $w->title ) ? $w->title : '',
                'updated_at'    => owc_oat_format_date( isset( $w->updated_at ) ? $w->updated_at : '' ),
            );
        }

        // User's own entries (recent).
        $my_entries_raw = OAT_Entry::for_originator( $user_id, array( 'per_page' => 10 ) );
        $my_entries = array();
        foreach ( $my_entries_raw as $e ) {
            $my_entries[] = array(
                'entry_id'      => (int) $e->id,
                'domain'        => $e->domain,
                'domain_label'  => OAT_Domain_Registry::get_label( $e->domain ) ?: $e->domain,
                'originator_id' => (int) $e->originator_id,
                'status'        => $e->status,
                'current_step'  => $e->current_step,
                'created_at'    => owc_oat_format_date( $e->created_at ),
            );
        }

        // Build user map.
        $user_ids = array();
        foreach ( $assignments_raw as $a ) {
            if ( isset( $a->originator_id ) ) {
                $user_ids[] = (int) $a->originator_id;
            }
        }
        foreach ( $cap_entries as $ce ) {
            if ( isset( $ce->originator_id ) ) {
                $user_ids[] = (int) $ce->originator_id;
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
        $available_actions = owc_oat_compute_available_actions( $entry, $user_id, $assignees_raw );

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

        // D4: Relationships (me-too, parent/child links).
        $relationships = array( 'children' => array(), 'parents' => array() );
        if ( class_exists( 'OAT_Entry_Relationship' ) ) {
            $children_raw = OAT_Entry_Relationship::for_source( $entry_id );
            foreach ( $children_raw as $rel ) {
                $relationships['children'][] = array(
                    'entry_id' => (int) $rel->target_entry_id,
                    'type'     => $rel->relationship_type,
                );
            }
            $parents_raw = OAT_Entry_Relationship::for_target( $entry_id );
            foreach ( $parents_raw as $rel ) {
                $relationships['parents'][] = array(
                    'entry_id' => (int) $rel->source_entry_id,
                    'type'     => $rel->relationship_type,
                );
            }
        }

        return array(
            'entry'             => array(
                'id'              => (int) $entry->id,
                'domain'          => $entry->domain,
                'form_slug'       => isset( $entry->form_slug ) ? $entry->form_slug : '',
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
            'relationships'     => $relationships,
        );
    }

    $result = owc_oat_request( 'entry/' . (int) $entry_id );
    return is_wp_error( $result ) ? $result : owc_oat_format_remote_timestamps( $result );
}


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

        $entry_data = array(
            'domain'        => $domain_slug,
            'status'        => 'pending',
            'current_step'  => 'submit',
            'originator_id' => $user_id,
        );

        if ( ! empty( $data['form_slug'] ) ) {
            $entry_data['form_slug'] = sanitize_text_field( $data['form_slug'] );
        }
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

        // Resolve character UUID → character_id and coordinator_genre on the entry.
        if ( class_exists( 'OAT_Character' ) ) {
            $char_uuid = '';
            // Check common character field keys.
            foreach ( array( 'character_name', 'character' ) as $ck ) {
                if ( ! empty( $meta[ $ck ] ) ) {
                    $char_uuid = $meta[ $ck ];
                    break;
                }
            }
            if ( $char_uuid ) {
                $char = OAT_Character::find_by_uuid( $char_uuid );
                if ( $char ) {
                    $update_entry = array();
                    if ( empty( $entry_data['character_id'] ) ) {
                        $update_entry['character_id'] = (int) $char->id;
                    }
                    if ( empty( $entry_data['coordinator_genre'] ) ) {
                        // Use explicit coordinator_genre from meta if set, otherwise derive from creature type.
                        if ( ! empty( $meta['coordinator_genre'] ) ) {
                            $update_entry['coordinator_genre'] = strtolower( $meta['coordinator_genre'] );
                        } elseif ( ! empty( $char->creature_type ) ) {
                            $update_entry['coordinator_genre'] = strtolower( $char->creature_type );
                        }
                    }
                    if ( ! empty( $update_entry ) ) {
                        OAT_Entry::update( $entry_id, $update_entry );
                    }
                    // Also store character_id in meta for downstream use.
                    if ( empty( $meta['character_id'] ) ) {
                        $meta['character_id'] = (string) $char->id;
                    }
                }
            }
        }

        // Resolve coordinator_genre and item_description from linked custom content (Learn CC).
        if ( ! empty( $meta['learned_content'] ) && class_exists( 'OAT_Entry' ) ) {
            $cc_data = is_string( $meta['learned_content'] ) ? json_decode( $meta['learned_content'], true ) : $meta['learned_content'];
            if ( is_array( $cc_data ) && ! empty( $cc_data['entry_id'] ) ) {
                $cc_entry = OAT_Entry::find( (int) $cc_data['entry_id'] );
                if ( $cc_entry ) {
                    // Set coordinator_genre from the CC entry if not already set.
                    if ( ! empty( $cc_entry->coordinator_genre ) ) {
                        OAT_Entry::update( $entry_id, array( 'coordinator_genre' => $cc_entry->coordinator_genre ) );
                    }
                    // Set item_description from CC item label (fixes "form name" display).
                    if ( ! empty( $cc_data['label'] ) && empty( $meta['item_description'] ) ) {
                        $meta['item_description'] = sanitize_text_field( $cc_data['label'] );
                        OAT_Entry_Meta::set( $entry_id, 'item_description', $meta['item_description'] );
                    }
                }
            }
        }

        // Attach regulation rules.
        if ( ! empty( $data['rules'] ) && is_array( $data['rules'] ) ) {
            foreach ( $data['rules'] as $rule_id ) {
                if ( is_array( $rule_id ) ) {
                    // Free-text rule: store as item_description if not already set.
                    if ( isset( $rule_id['text'] ) && empty( $meta['item_description'] ) ) {
                        $meta['item_description'] = sanitize_text_field( $rule_id['text'] );
                        OAT_Entry_Meta::set( $entry_id, 'item_description', $meta['item_description'] );
                    }
                    continue;
                }
                $rule_id = (int) $rule_id;
                if ( $rule_id > 0 ) {
                    OAT_Entry_Rule::create( $entry_id, $rule_id );
                    // Auto-set item_description from the first linked rule if not already set.
                    if ( empty( $meta['item_description'] ) && class_exists( 'OAT_Regulation_Rule' ) ) {
                        $rule_obj = OAT_Regulation_Rule::find( $rule_id );
                        if ( $rule_obj ) {
                            $parts = array_filter( array( $rule_obj->category, $rule_obj->subcategory, $rule_obj->condition_name ) );
                            $meta['item_description'] = implode( ': ', $parts );
                            OAT_Entry_Meta::set( $entry_id, 'item_description', $meta['item_description'] );
                        }
                    }
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

        // Admin Edit: save meta fields without advancing workflow.
        if ( 'admin_edit' === $action_type ) {
            if ( ! function_exists( 'owc_oat_is_super_user' ) || ! owc_oat_is_super_user( $user_id ) ) {
                return new WP_Error( 'oat_forbidden', 'Admin edit requires archivist or admin privileges.' );
            }
            if ( empty( $note ) ) {
                return new WP_Error( 'oat_missing_note', 'A reason for the edit is required.' );
            }

            // Collect meta from POST.
            $updated_keys = array();
            foreach ( $_POST as $k => $v ) {
                if ( strpos( $k, 'oat_meta_' ) === 0 ) {
                    $meta_key = sanitize_key( substr( $k, 9 ) );
                    if ( $meta_key ) {
                        $old_val = OAT_Entry_Meta::get( $entry_id, $meta_key );
                        $new_val = wp_kses_post( $v );
                        if ( $old_val !== $new_val ) {
                            OAT_Entry_Meta::set( $entry_id, $meta_key, $new_val );
                            $updated_keys[] = $meta_key;
                        }
                    }
                }
            }

            // If character field was changed, re-resolve character_id on the entry.
            $char_changed = array_intersect( array( 'character_name', 'character' ), $updated_keys );
            if ( ! empty( $char_changed ) && class_exists( 'OAT_Character' ) ) {
                $char_key      = reset( $char_changed );
                $new_char_uuid = OAT_Entry_Meta::get( $entry_id, $char_key );
                if ( $new_char_uuid ) {
                    $new_char = OAT_Character::find_by_uuid( $new_char_uuid );
                    if ( $new_char && (int) $new_char->id !== (int) $entry->character_id ) {
                        OAT_Entry::update( $entry_id, array( 'character_id' => (int) $new_char->id ) );
                        $updated_keys[] = 'character_id';
                    }
                }
            }

            // Log timeline.
            $changes_note = $note;
            if ( ! empty( $updated_keys ) ) {
                $changes_note .= ' [Fields: ' . implode( ', ', $updated_keys ) . ']';
            }
            OAT_Timeline::append( array(
                'entry_id'        => $entry_id,
                'action_type'     => 'record',
                'actor_id'        => $user_id,
                'step'            => $entry->current_step,
                'visibility_tier' => OAT_Constants::TIER_ARCHIVIST,
                'note'            => 'Admin edit: ' . $changes_note,
            ) );

            // Touch entry timestamp via a status re-set (updated_at alone is not in allowed list).
            OAT_Entry::update_status( $entry_id, $entry->status, $entry->current_step );

            return array(
                'entry_id' => $entry_id,
                'status'   => $entry->status,
                'step'     => $entry->current_step,
                'message'  => 'Entry updated. ' . count( $updated_keys ) . ' field(s) changed.',
            );
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


/**
 * Toggle watch on an entry.
 *
 * @param int    $entry_id
 * @param string $watch_action 'add' or 'remove'.
 * @return array|WP_Error { entry_id, watching }
 */
function owc_oat_toggle_watch( $entry_id, $watch_action = 'toggle' ) {
    if ( owc_oat_is_local() ) {
        $user_id = get_current_user_id();

        $entry = OAT_Entry::find( $entry_id );
        if ( ! $entry ) {
            return new WP_Error( 'oat_not_found', 'Entry not found.', array( 'status' => 404 ) );
        }

        // Auto-detect: if already watching, remove; otherwise add.
        if ( $watch_action === 'toggle' ) {
            $watch_action = OAT_Watcher::is_watching( $entry_id, $user_id ) ? 'remove' : 'add';
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
            $ref   = isset( $rule->section_ref ) && $rule->section_ref ? $rule->section_ref : '';
            $name  = $rule->condition_name ? $rule->condition_name : $rule->subcategory;
            $label = $ref
                ? sprintf( '[%s] %s — %s — %s', $ref, $rule->genre, $rule->category, $name )
                : sprintf( '%s — %s — %s', $rule->genre, $rule->category, $name );
            $out[] = array(
                'id'          => (int) $rule->id,
                'label'       => $label,
                'value'       => $label,
                'section_ref' => $ref,
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
        'q'     => $term,
        'limit' => $limit,
    ) );
}


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
        // Admin edit available even on terminal entries for data cleanup.
        if ( function_exists( 'owc_oat_is_super_user' ) && owc_oat_is_super_user( $user_id ) ) {
            $actions[] = 'admin_edit';
        }
        return $actions;
    }

    // Check explicit assignee row.
    $is_assignee = false;
    foreach ( $assignees as $a ) {
        if ( (int) $a->user_id === $user_id && $a->step === $entry->current_step && $a->status === 'pending' ) {
            $is_assignee = true;
            break;
        }
    }

    // Role-path-based: user holds the ASC role matching the step's assignee_role.
    if ( ! $is_assignee ) {
        $is_assignee = owc_oat_user_can_act_on_step( $entry, $user_id );
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
        // Originator can resubmit when entry is sent back to submit step.
        if ( $entry->current_step === 'submit' && $entry->status === 'pending' ) {
            $actions[] = 'submit';
        }
        // Self-approve: originator at archivist step with self-approve privilege.
        if ( $entry->current_step === 'archivist' && ! in_array( 'approve', $actions, true ) && owc_oat_can_self_approve( $user_id ) ) {
            $actions[] = 'approve';
            $actions[] = 'deny';
            $actions[] = 'request_changes';
        }
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

    // Admin edit: WP Admin or exec/archivist/coordinator can edit any entry's meta.
    if ( function_exists( 'owc_oat_is_super_user' ) && owc_oat_is_super_user( $user_id ) ) {
        $actions[] = 'admin_edit';
    }

    return array_unique( $actions );
}

/**
 * Get a user's cached ASC role paths, lowercase-normalized.
 *
 * @param int $user_id WordPress user ID.
 * @return array Array of lowercase role path strings.
 */
function owc_oat_get_user_asc_roles( $user_id ) {
    if ( ! defined( 'OWC_ASC_CACHE_KEY' ) ) {
        return array();
    }
    $cached = get_user_meta( $user_id, OWC_ASC_CACHE_KEY, true );
    if ( ! is_array( $cached ) ) {
        return array();
    }
    return array_map( 'strtolower', $cached );
}

/**
 * Resolve a step's assignee_role pattern against entry data.
 *
 * Replaces {chronicle_slug} and {coordinator_genre} placeholders
 * with actual entry values.
 *
 * @param object $entry       Entry row object.
 * @param array  $step_config Step configuration array.
 * @return string Resolved lowercase role path, or empty string.
 */
function owc_oat_resolve_step_role( $entry, $step_config ) {
    $pattern = isset( $step_config['assignee_role'] ) ? $step_config['assignee_role'] : '';
    if ( empty( $pattern ) ) {
        return '';
    }
    $resolved = str_replace(
        array( '{chronicle_slug}', '{coordinator_genre}' ),
        array(
            isset( $entry->chronicle_slug ) ? $entry->chronicle_slug : '',
            isset( $entry->coordinator_genre ) ? $entry->coordinator_genre : '',
        ),
        $pattern
    );
    return strtolower( $resolved );
}

/**
 * Check if any of the user's roles grant archivist oversight.
 *
 * @param array $user_roles Lowercase-normalized role paths.
 * @return bool
 */
function owc_oat_is_archivist_oversight( $user_roles ) {
    $oversight_paths = apply_filters( 'oat_archivist_oversight_paths', array(
        'exec/archivist/coordinator',
    ) );
    foreach ( $oversight_paths as $path ) {
        if ( in_array( strtolower( $path ), $user_roles, true ) ) {
            return true;
        }
    }
    return false;
}

/**
 * Check if a user qualifies as a "super user" for fast-track submissions.
 *
 * Super users: WP Admin, exec/archivist/coordinator, exec/web/coordinator, exec/admin/coordinator.
 *
 * @param int $user_id WordPress user ID.
 * @return bool
 */
function owc_oat_is_super_user( $user_id ) {
    $user = get_userdata( $user_id );
    if ( $user && $user->has_cap( 'manage_options' ) ) {
        return true;
    }
    $roles = owc_oat_get_user_asc_roles( $user_id );
    $super_roles = array(
        'exec/archivist/coordinator',
        'exec/web/coordinator',
        'exec/admin/coordinator',
    );
    foreach ( $super_roles as $sr ) {
        if ( in_array( $sr, $roles, true ) ) {
            return true;
        }
    }
    return false;
}

/**
 * Check if a user can self-approve their own fast-tracked entries.
 *
 * Only WP Admin and exec/archivist/coordinator can self-approve.
 * exec/web and exec/admin can fast-track but NOT self-approve.
 *
 * @param int $user_id WordPress user ID.
 * @return bool
 */
function owc_oat_can_self_approve( $user_id ) {
    $user = get_userdata( $user_id );
    if ( $user && $user->has_cap( 'manage_options' ) ) {
        return true;
    }
    $roles = owc_oat_get_user_asc_roles( $user_id );
    return in_array( 'exec/archivist/coordinator', $roles, true );
}

/**
 * Check if the current user can create characters.
 *
 * Reads the oat_character_create_roles option (locally or via gateway).
 * If empty, all authenticated users are allowed.
 * Patterns support * as wildcard for a slug segment.
 *
 * @return bool
 */
function owc_oat_can_create_character() {
    if ( ! is_user_logged_in() ) {
        return false;
    }

    // WP admin always allowed.
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }

    $patterns = owc_oat_get_create_role_patterns();

    // Empty = allow all authenticated users.
    if ( empty( $patterns ) ) {
        return true;
    }

    $user_id = get_current_user_id();
    $roles   = owc_oat_get_user_asc_roles( $user_id );
    if ( empty( $roles ) ) {
        return false;
    }

    foreach ( $patterns as $pattern ) {
        $pattern = strtolower( trim( $pattern ) );
        if ( '' === $pattern ) {
            continue;
        }
        // Convert wildcard pattern to regex: * matches any single slug segment.
        $regex = '#^' . str_replace( '\\*', '[^/]+', preg_quote( $pattern, '#' ) ) . '$#i';
        foreach ( $roles as $role ) {
            if ( preg_match( $regex, $role ) ) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Fetch the character creation allowed role patterns.
 *
 * Local: reads get_option directly.
 * Remote: fetches via gateway and caches in transient for 5 minutes.
 *
 * @return array
 */
function owc_oat_get_create_role_patterns() {
    if ( owc_oat_is_local() ) {
        $roles = get_option( 'oat_character_create_roles', array() );
        return is_array( $roles ) ? $roles : array();
    }

    // Remote: cache via transient.
    $cache_key = 'owc_oat_create_role_patterns';
    $cached    = get_transient( $cache_key );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    $response = owc_oat_request( 'oat/settings/create-roles' );
    if ( is_wp_error( $response ) || ! isset( $response['roles'] ) ) {
        return array();
    }

    $roles = is_array( $response['roles'] ) ? $response['roles'] : array();
    set_transient( $cache_key, $roles, 5 * MINUTE_IN_SECONDS );
    return $roles;
}

/**
 * Check if a user holds the resolved step role.
 *
 * @param array  $user_roles    Lowercase-normalized role paths.
 * @param string $resolved_role Resolved lowercase role path.
 * @return bool
 */
function owc_oat_user_has_step_role( $user_roles, $resolved_role ) {
    if ( empty( $resolved_role ) ) {
        return false;
    }
    return in_array( $resolved_role, $user_roles, true );
}

/**
 * Check if a user can act on the entry's current step based on
 * ASC role-path matching.
 *
 * @param object $entry   Entry row object.
 * @param int    $user_id WordPress user ID. Defaults to current user.
 * @return bool
 */
function owc_oat_user_can_act_on_step( $entry, $user_id = 0 ) {
    if ( ! class_exists( 'OAT_Workflow_Engine' ) ) {
        return false;
    }
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    $user_roles = owc_oat_get_user_asc_roles( $user_id );
    if ( empty( $user_roles ) ) {
        return false;
    }
    if ( owc_oat_is_archivist_oversight( $user_roles ) ) {
        return true;
    }
    $step_config = OAT_Workflow_Engine::get_step_config( $entry );
    if ( ! $step_config ) {
        return false;
    }
    $resolved_role = owc_oat_resolve_step_role( $entry, $step_config );
    return owc_oat_user_has_step_role( $user_roles, $resolved_role );
}

/**
 * Get pending entries the user can act on based on ASC role-path matching.
 *
 * Queries all non-terminal entries, then filters to those whose current step's
 * resolved assignee_role matches one of the user's ASC roles.
 * Archivist oversight (exec/archivist/coordinator) sees all entries.
 *
 * @param int    $user_id       WordPress user ID.
 * @param string $domain_filter Optional domain slug filter.
 * @return array Array of entry row objects.
 */
function owc_oat_capability_inbox_entries( $user_id, $domain_filter = '' ) {
    if ( ! class_exists( 'OAT_Constants' ) || ! class_exists( 'OAT_Workflow_Engine' ) ) {
        return array();
    }

    $user_roles = owc_oat_get_user_asc_roles( $user_id );
    if ( empty( $user_roles ) ) {
        return array();
    }

    $is_archivist = owc_oat_is_archivist_oversight( $user_roles );

    // Query non-terminal entries.
    global $wpdb;
    $table = $wpdb->prefix . 'oat_entries';
    $terminal = array(
        OAT_Constants::STATUS_APPROVED,
        OAT_Constants::STATUS_DENIED,
        OAT_Constants::STATUS_CANCELLED,
        OAT_Constants::STATUS_AUTO_APPROVED,
        OAT_Constants::STATUS_AUTO_DENIED,
    );
    $placeholders = implode( ', ', array_fill( 0, count( $terminal ), '%s' ) );
    $sql = "SELECT * FROM {$table} WHERE status NOT IN ({$placeholders})";
    $args = $terminal;

    if ( $domain_filter ) {
        $sql .= ' AND domain = %s';
        $args[] = $domain_filter;
    }
    $sql .= ' ORDER BY created_at DESC';

    $entries = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
    if ( ! $entries ) {
        return array();
    }

    // Archivist oversight: return all non-terminal entries.
    if ( $is_archivist ) {
        return $entries;
    }

    // Filter to entries whose resolved step role matches user's ASC roles.
    $result     = array();
    $step_cache = array();

    foreach ( $entries as $entry ) {
        $cache_key = $entry->domain . '::' . $entry->current_step;
        if ( ! isset( $step_cache[ $cache_key ] ) ) {
            $step_cache[ $cache_key ] = OAT_Workflow_Engine::get_step_config( $entry );
        }
        $step_config = $step_cache[ $cache_key ];
        if ( ! $step_config ) {
            continue;
        }
        $resolved_role = owc_oat_resolve_step_role( $entry, $step_config );
        if ( owc_oat_user_has_step_role( $user_roles, $resolved_role ) ) {
            $result[] = $entry;
        }
    }

    return $result;
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

    // D3: Use pc_level or npc_level based on character's PC/NPC type.
    $pc_npc = 'pc';
    if ( class_exists( 'OAT_Entry_Meta' ) ) {
        $stored = OAT_Entry_Meta::get( $entry_id, 'pc_npc' );
        if ( $stored ) {
            $pc_npc = $stored;
        }
    }

    $priority = array(
        'council_vote'         => 4,
        'disallowed'           => 3,
        'coordinator_approval' => 2,
        'coordinator_notify'   => 1,
    );
    $highest = 0;

    foreach ( $rule_ids as $rule_id ) {
        // Skip free-text entries (super user): only linked rules affect routing.
        if ( is_array( $rule_id ) ) {
            continue;
        }
        $rule = OAT_Regulation_Rule::find( (int) $rule_id );
        if ( ! $rule ) {
            continue;
        }

        $level = ( 'npc' === $pc_npc && ! empty( $rule->npc_level ) ) ? $rule->npc_level : $rule->pc_level;
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


/**
 * Get entry counts for a user's dashboard.
 *
 * Returns:
 *   ['assigned' => int, 'submissions' => int, 'watching' => int]
 *
 * Local mode:  queries oat_assignees, oat_entries, oat_watchers directly.
 * Remote mode: POST /oat/dashboard-counts.
 *
 * @param int $user_id WordPress user ID.
 * @return array|WP_Error
 */
function owc_oat_get_dashboard_counts( $user_id ) {
    $user_id = (int) $user_id;

    if ( owc_oat_is_local() ) {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $assigned = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT entry_id) FROM {$prefix}oat_assignees
             WHERE user_id = %d AND status = 'active'",
            $user_id
        ) );

        $submissions = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}oat_entries
             WHERE originator_id = %d AND status NOT IN ('approved','denied','cancelled')",
            $user_id
        ) );

        $watching = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}oat_watchers WHERE user_id = %d",
            $user_id
        ) );

        return array(
            'assigned'    => $assigned,
            'submissions' => $submissions,
            'watching'    => $watching,
        );
    }

    // Remote mode.
    $response = owc_oat_request( 'POST', '/oat/dashboard-counts', array( 'user_id' => $user_id ) );
    if ( is_wp_error( $response ) ) {
        return $response;
    }
    return isset( $response['counts'] ) ? $response['counts'] : $response;
}


/**
 * Get recent timeline activity visible to a user.
 *
 * Returns array of event arrays:
 *   [['entry_id', 'domain', 'domain_label', 'action_type', 'actor_name', 'note', 'created_at'], ...]
 *
 * Local mode:  joins oat_timeline + oat_entries, filters by user's accessible entries.
 * Remote mode: POST /oat/recent-activity.
 *
 * @param int    $user_id WordPress user ID.
 * @param int    $limit   Maximum number of events to return (default 10).
 * @param string $domain  Optional domain slug to filter by (empty = all).
 * @return array|WP_Error
 */
function owc_oat_get_recent_activity( $user_id, $limit = 10, $domain = '' ) {
    $user_id = (int) $user_id;
    $limit   = max( 1, (int) $limit );
    $domain  = sanitize_key( $domain );

    if ( owc_oat_is_local() ) {
        global $wpdb;
        $prefix = $wpdb->prefix;

        // Collect entry IDs accessible to this user:
        //   - entries they originated
        //   - entries they are assigned to
        //   - entries they are watching
        $entry_ids_sql =
            "SELECT DISTINCT e.id FROM {$prefix}oat_entries e
             WHERE e.originator_id = {$user_id}
             UNION
             SELECT DISTINCT a.entry_id FROM {$prefix}oat_assignees a WHERE a.user_id = {$user_id}
             UNION
             SELECT DISTINCT w.entry_id FROM {$prefix}oat_watchers w WHERE w.user_id = {$user_id}";

        $domain_clause = $domain ? $wpdb->prepare( "AND e.domain = %s", $domain ) : '';

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT t.entry_id, e.domain, t.action_type, t.actor_id, t.note, t.created_at
             FROM {$prefix}oat_timeline t
             INNER JOIN {$prefix}oat_entries e ON e.id = t.entry_id
             WHERE t.entry_id IN ({$entry_ids_sql})
               AND t.visibility_tier IN ('public','player')
               {$domain_clause}
             ORDER BY t.created_at DESC
             LIMIT %d",
            $limit
        ), ARRAY_A );

        if ( ! $rows ) {
            return array();
        }

        // Resolve actor names.
        $actor_ids = array_unique( array_column( $rows, 'actor_id' ) );
        $actor_map = array();
        foreach ( $actor_ids as $aid ) {
            $u = get_user_by( 'id', $aid );
            $actor_map[ $aid ] = $u ? $u->display_name : '#' . $aid;
        }

        // Collect domain labels.
        $domain_labels = array();
        if ( function_exists( 'owc_oat_get_domains' ) ) {
            $domains = owc_oat_get_domains();
            foreach ( $domains as $d ) {
                $domain_labels[ $d['slug'] ] = $d['label'];
            }
        }

        $events = array();
        foreach ( $rows as $row ) {
            $events[] = array(
                'entry_id'     => (int) $row['entry_id'],
                'domain'       => $row['domain'],
                'domain_label' => isset( $domain_labels[ $row['domain'] ] ) ? $domain_labels[ $row['domain'] ] : ucfirst( str_replace( '_', ' ', $row['domain'] ) ),
                'action_type'  => $row['action_type'],
                'actor_name'   => isset( $actor_map[ $row['actor_id'] ] ) ? $actor_map[ $row['actor_id'] ] : '',
                'note'         => $row['note'],
                'created_at'   => owc_oat_format_date( $row['created_at'] ),
            );
        }

        return $events;
    }

    // Remote mode.
    $response = owc_oat_request( 'POST', '/oat/recent-activity', array(
        'user_id' => $user_id,
        'limit'   => $limit,
        'domain'  => $domain,
    ) );
    if ( is_wp_error( $response ) ) {
        return $response;
    }
    return isset( $response['events'] ) ? $response['events'] : $response;
}

/**
 * Get all active regulation rules, with transient caching.
 *
 * In local mode (OAT toolkit active), queries the DB directly.
 * In remote mode, fetches from archivist via /oat/rules and caches
 * the result in a transient for efficient client-side use.
 *
 * @param bool $force_refresh Skip the transient cache when true.
 * @return array Array of rule arrays (id, genre, category, subcategory,
 *               condition, pc_level, npc_level, coordinator, elevation).
 */
function owc_oat_get_regulation_rules( $force_refresh = false ) {
    $cache_key = 'owc_oat_rules_cache';

    if ( ! $force_refresh ) {
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }
    }

    if ( owc_oat_is_local() ) {
        if ( ! class_exists( 'OAT_Regulation_Rule' ) ) {
            return array();
        }
        $rows = OAT_Regulation_Rule::all( array( 'active' => 1, 'per_page' => 5000 ) );
        $data = array();
        foreach ( $rows as $rule ) {
            $data[ (int) $rule->id ] = array(
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
    } else {
        $response = owc_oat_request( 'rules', array() );
        if ( is_wp_error( $response ) || ! is_array( $response ) ) {
            return array();
        }
        $data = array();
        foreach ( $response as $rule ) {
            $id = isset( $rule['id'] ) ? (int) $rule['id'] : 0;
            if ( $id ) {
                $data[ $id ] = $rule;
            }
        }
    }

    $ttl = defined( 'OWC_CACHE_TTL' ) ? (int) OWC_CACHE_TTL : 3600;
    if ( $ttl > 0 ) {
        set_transient( $cache_key, $data, $ttl );
    }

    return $data;
}

/**
 * Find a single regulation rule by ID, using the cached rule list.
 *
 * @param int $id Rule ID.
 * @return array|null Rule data array or null if not found.
 */
function owc_oat_find_cached_rule( $id ) {
    $id    = (int) $id;
    $rules = owc_oat_get_regulation_rules();
    return isset( $rules[ $id ] ) ? $rules[ $id ] : null;
}


// ── Registry Visibility API ─────────────────────────────────────────


/**
 * Fetch scoped registry for current user.
 *
 * Local mode: full scoped registry (own + chronicle + coordinator characters).
 * Remote mode: own characters only (My Characters).
 *
 * @param array $args Optional 'chronicle' and/or 'genre' (string or array, local mode only).
 * @return array|WP_Error
 */
function owc_oat_get_registry( $args = array() ) {
    if ( owc_oat_is_local() ) {
        $user_id = get_current_user_id();

        $chronicles = isset( $args['chronicle'] ) ? (array) $args['chronicle'] : array();
        $genres     = isset( $args['genre'] ) ? (array) $args['genre'] : array();

        if ( $chronicles || $genres ) {
            $characters = array();
            foreach ( $chronicles as $slug ) {
                foreach ( OAT_Registry::get_characters_for_chronicle( $slug ) as $c ) {
                    $characters[ $c->id ] = $c;
                }
            }
            foreach ( $genres as $genre ) {
                foreach ( OAT_Registry::get_characters_for_coordinator( $genre ) as $c ) {
                    $characters[ $c->id ] = $c;
                }
            }
            $characters = array_values( $characters );
            foreach ( $characters as $char ) {
                $char->entry_counts = OAT_Registry::get_entry_counts_by_domain( (int) $char->id );
            }
            return array( 'characters' => $characters, 'count' => count( $characters ) );
        }

        $characters = OAT_Registry::get_scoped_registry( $user_id );
        return array( 'characters' => $characters, 'count' => count( $characters ) );
    }

    $params = array();
    if ( ! empty( $args['chronicle'] ) ) {
        $params['chronicle'] = is_array( $args['chronicle'] ) ? implode( ',', $args['chronicle'] ) : $args['chronicle'];
    }
    if ( ! empty( $args['genre'] ) ) {
        $params['genre'] = is_array( $args['genre'] ) ? implode( ',', $args['genre'] ) : $args['genre'];
    }

    return owc_oat_request( 'registry', $params );
}

/**
 * Fetch registry section headers with counts for a scope.
 *
 * @param string $scope mine|chronicles|coordinators|decommissioned
 * @return array|WP_Error
 */
function owc_oat_get_registry_sections( $scope ) {
    if ( owc_oat_is_local() ) {
        $user_id = get_current_user_id();
        return OAT_Registry::get_registry_sections( $user_id, $scope );
    }
    return owc_oat_request( 'registry/sections', array( 'scope' => $scope ) );
}

/**
 * Fetch characters for a single registry section.
 *
 * @param string $section_key e.g. 'mine', 'chronicle-hartford', 'coordinator-vampire'
 * @return array|WP_Error
 */
function owc_oat_get_section_characters( $section_key ) {
    if ( owc_oat_is_local() ) {
        $user_id    = get_current_user_id();
        $characters = OAT_Registry::get_section_characters( $user_id, $section_key );
        // Normalize to arrays for consistency with remote mode.
        return array( 'characters' => array_map( function( $c ) {
            return (array) $c;
        }, $characters ) );
    }
    return owc_oat_request( 'registry/section-characters', array( 'section_key' => $section_key ) );
}

/**
 * Search registry characters by name or chronicle slug.
 *
 * Scoped to current user's access — same visibility as registry sections.
 *
 * @param string $q Search term (min 2 chars).
 * @return array|WP_Error Array of character arrays.
 */
function owc_oat_registry_search( $q ) {
    if ( owc_oat_is_local() ) {
        $user_id    = get_current_user_id();
        $characters = OAT_Registry::search_characters( $user_id, $q );
        return array_map( function( $c ) { return (array) $c; }, $characters );
    }
    return owc_oat_request( 'registry/search', array( 'q' => $q ) );
}

/**
 * Fetch registry entries for one character.
 *
 * @param int $character_id
 * @return array|WP_Error
 */
function owc_oat_get_character_registry( $character_id ) {
    if ( owc_oat_is_local() ) {
        $character = OAT_Character::find( $character_id );
        if ( ! $character ) {
            return new WP_Error( 'not_found', 'Character not found.' );
        }

        $entries = OAT_Registry::get_registry_entries( $character_id );
        $grants  = OAT_Registry_Access::find_by_character( $character_id );

        return array(
            'character' => $character,
            'grants'    => $grants,
            'entries'   => $entries,
        );
    }

    return owc_oat_request( 'registry/character/' . (int) $character_id );
}

/**
 * Fetch public registry fields for one character.
 *
 * @param int $character_id
 * @return array|WP_Error
 */
function owc_oat_get_public_registry( $character_id ) {
    if ( owc_oat_is_local() ) {
        $character = OAT_Character::find( $character_id );
        if ( ! $character ) {
            return new WP_Error( 'not_found', 'Character not found.' );
        }

        return array(
            'character_id'   => $character_id,
            'character_name' => $character->character_name,
            'public_fields'  => OAT_Registry::get_public_registry( $character_id ),
        );
    }

    return owc_oat_request( 'registry/public/' . (int) $character_id );
}

/**
 * Create an explicit grant for a character.
 *
 * @param int         $character_id
 * @param string      $grant_type  'chronicle' or 'coordinator'.
 * @param string      $grant_value Chronicle slug or genre slug.
 * @param int|null    $expires_at  Unix timestamp or null for permanent.
 * @return array|WP_Error
 */
function owc_oat_grant_access( $character_id, $grant_type, $grant_value, $expires_at = null ) {
    if ( owc_oat_is_local() ) {
        $grant_id = OAT_Registry_Access::ensure_grant(
            $character_id,
            $grant_type,
            $grant_value,
            get_current_user_id()
        );

        if ( $expires_at && $grant_id ) {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'oat_registry_access',
                array( 'expires_at' => $expires_at ),
                array( 'id' => $grant_id ),
                array( '%d' ),
                array( '%d' )
            );
        }

        return array( 'success' => true, 'grant_id' => $grant_id );
    }

    return owc_oat_request( 'registry/grant', array(
        'character_id' => $character_id,
        'grant_type'   => $grant_type,
        'grant_value'  => $grant_value,
        'expires_at'   => $expires_at ?: 0,
    ) );
}

/**
 * Update a character's fields.
 *
 * @param int   $character_id
 * @param array $data Associative array of fields to update.
 * @return array|WP_Error
 */
function owc_oat_update_character( $character_id, $data ) {
    if ( owc_oat_is_local() ) {
        $character = OAT_Character::find( $character_id );
        if ( ! $character ) {
            return new WP_Error( 'not_found', 'Character not found.' );
        }

        $result = OAT_Character::update( $character_id, $data );
        if ( ! $result ) {
            return new WP_Error( 'update_failed', 'Character update failed.' );
        }

        return array( 'success' => true, 'character' => (array) OAT_Character::find( $character_id ) );
    }

    return owc_oat_request( 'registry/character/' . (int) $character_id . '/update', $data );
}

/**
 * Expire (revoke) a grant.
 *
 * @param int $grant_id
 * @return array|WP_Error
 */
function owc_oat_revoke_access( $grant_id ) {
    if ( owc_oat_is_local() ) {
        OAT_Registry_Access::expire( $grant_id );
        return array( 'success' => true );
    }

    return owc_oat_request( 'registry/grant/' . (int) $grant_id . '/revoke' );
}


// ── ccHub Custom Content API ─────────────────────────────────────────


/**
 * Fetch ccHub content type categories with counts.
 *
 * @return array|WP_Error
 */
function owc_oat_get_cchub_categories() {
    if ( owc_oat_is_local() ) {
        global $wpdb;
        $entries = $wpdb->prefix . 'oat_entries';
        $meta    = $wpdb->prefix . 'oat_entry_meta';

        $rows = $wpdb->get_results( "
            SELECT m.meta_value as content_type, COUNT(DISTINCT e.id) as cnt
            FROM {$entries} e
            JOIN {$meta} m ON e.id = m.entry_id AND m.meta_key = 'content_type'
            WHERE e.domain = 'custom_content' AND e.status = 'approved'
            AND m.meta_value != ''
            GROUP BY m.meta_value
            ORDER BY m.meta_value ASC
        " );

        $out = array();
        foreach ( $rows as $cat ) {
            $out[] = array(
                'content_type' => $cat->content_type,
                'count'        => (int) $cat->cnt,
            );
        }
        return $out;
    }

    return owc_oat_request( 'cchub/categories' );
}


/**
 * Fetch ccHub browse data for a content type.
 *
 * Returns lightweight rows for table display (id, name, type, bm_cat, xp, coord, chron).
 *
 * @param string $type Content type filter (empty = all).
 * @return array|WP_Error
 */
function owc_oat_get_cchub_browse( $type = '' ) {
    if ( owc_oat_is_local() ) {
        global $wpdb;
        $entries = $wpdb->prefix . 'oat_entries';
        $meta    = $wpdb->prefix . 'oat_entry_meta';

        $where     = "e.domain = 'custom_content' AND e.status = 'approved'";
        $join_type = '';
        if ( $type ) {
            $join_type = $wpdb->prepare(
                "JOIN {$meta} mt ON e.id = mt.entry_id AND mt.meta_key = 'content_type' AND mt.meta_value = %s",
                $type
            );
        }

        $sql = "
            SELECT e.id, e.coordinator_genre, e.chronicle_slug,
                mn.meta_value as content_name,
                mx.meta_value as xp_cost,
                mct.meta_value as content_type,
                mbm.meta_value as bm_category
            FROM {$entries} e
            {$join_type}
            LEFT JOIN {$meta} mn ON e.id = mn.entry_id AND mn.meta_key = 'content_name'
            LEFT JOIN {$meta} mx ON e.id = mx.entry_id AND mx.meta_key = 'xp_cost'
            LEFT JOIN {$meta} mct ON e.id = mct.entry_id AND mct.meta_key = 'content_type'
            LEFT JOIN {$meta} mbm ON e.id = mbm.entry_id AND mbm.meta_key = 'blood_magic_category'
            WHERE {$where}
            ORDER BY mn.meta_value ASC
        ";
        $rows = $wpdb->get_results( $sql );

        $items = array();
        foreach ( $rows as $r ) {
            $items[] = array(
                'id'     => (int) $r->id,
                'name'   => $r->content_name ?: '(unnamed)',
                'type'   => $r->content_type ?: '',
                'bm_cat' => $r->bm_category ?: '',
                'xp'     => $r->xp_cost ?: '',
                'coord'  => $r->coordinator_genre ? ucfirst( $r->coordinator_genre ) : '',
                'chron'  => strtoupper( $r->chronicle_slug ?: '' ),
            );
        }
        return $items;
    }

    $body = array();
    if ( $type ) {
        $body['type'] = $type;
    }
    return owc_oat_request( 'cchub/browse', $body );
}


/**
 * Fetch a single ccHub entry with all meta and resolved titles.
 *
 * @param int $entry_id
 * @return array|WP_Error
 */
function owc_oat_get_cchub_entry( $entry_id ) {
    if ( owc_oat_is_local() ) {
        global $wpdb;
        $entries = $wpdb->prefix . 'oat_entries';
        $meta    = $wpdb->prefix . 'oat_entry_meta';

        $entry = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$entries} WHERE id = %d AND domain = 'custom_content'",
            $entry_id
        ) );

        if ( ! $entry ) {
            return new WP_Error( 'not_found', 'Entry not found.' );
        }

        $meta_rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$meta} WHERE entry_id = %d",
            $entry_id
        ) );

        $coord_slug  = $entry->coordinator_genre ?: '';
        $chron_slug  = $entry->chronicle_slug ?: '';
        $coord_title = '';
        $chron_title = '';
        if ( function_exists( 'owc_entity_get_title' ) ) {
            if ( $coord_slug ) { $coord_title = owc_entity_get_title( 'coordinator', $coord_slug ); }
            if ( $chron_slug ) { $chron_title = owc_entity_get_title( 'chronicle', $chron_slug ); }
        }

        $data = array(
            'id'                => (int) $entry->id,
            'coordinator_genre' => $coord_slug,
            'coordinator_title' => $coord_title ?: ucfirst( $coord_slug ),
            'chronicle_slug'    => $chron_slug,
            'chronicle_title'   => $chron_title ?: strtoupper( $chron_slug ),
        );
        foreach ( $meta_rows as $m ) {
            if ( strpos( $m->meta_key, '_oat_' ) === 0 || $m->meta_key === 'drupal_cc_id' ) {
                continue;
            }
            $data[ $m->meta_key ] = $m->meta_value;
        }
        return $data;
    }

    return owc_oat_request( 'cchub/entry/' . (int) $entry_id );
}
