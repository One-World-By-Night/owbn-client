<?php

/**
 * OAT Client AJAX Handlers
 *
 * AJAX endpoints for OAT client pages. All calls proxy through
 * the api.php layer which handles local/remote mode switching.
 *
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_owc_oat_search_rules', 'owc_oat_ajax_search_rules' );
add_action( 'wp_ajax_owc_oat_search_cchub', 'owc_oat_ajax_search_cchub' );
add_action( 'wp_ajax_owc_oat_process_action', 'owc_oat_ajax_process_action' );
add_action( 'wp_ajax_owc_oat_toggle_watch', 'owc_oat_ajax_toggle_watch' );
add_action( 'wp_ajax_owc_oat_get_domain_fields', 'owc_oat_ajax_get_domain_fields' );
add_action( 'wp_ajax_owc_oat_get_domain_forms', 'owc_oat_ajax_get_domain_forms' );
add_action( 'wp_ajax_owc_oat_search_characters', 'owc_oat_ajax_search_characters' );
add_action( 'wp_ajax_owc_oat_create_character', 'owc_oat_ajax_create_character' );
add_action( 'wp_ajax_owc_oat_creature_taxonomy', 'owc_oat_ajax_creature_taxonomy' );
add_action( 'wp_ajax_owc_oat_lookup_hst', 'owc_oat_ajax_lookup_hst' );
add_action( 'wp_ajax_owc_oat_search_users', 'owc_oat_ajax_search_users' );
add_action( 'wp_ajax_owc_oat_get_coordinators_for_rules', 'owc_oat_ajax_get_coordinators_for_rules' );
add_action( 'wp_ajax_owc_oat_submit_entry_frontend', 'owc_oat_ajax_submit_entry_frontend' );
add_action( 'wp_ajax_owc_oat_get_recent_activity', 'owc_oat_ajax_get_recent_activity' );
add_action( 'wp_ajax_owc_oat_registry_sections', 'owc_oat_ajax_registry_sections' );
add_action( 'wp_ajax_owc_oat_registry_section', 'owc_oat_ajax_registry_section' );
add_action( 'wp_ajax_owc_oat_registry_search', 'owc_oat_ajax_registry_search' );
add_action( 'wp_ajax_owc_oat_save_registry_columns', 'owc_oat_ajax_save_registry_columns' );
add_action( 'wp_ajax_owc_cchub_get_entry', 'owc_cchub_ajax_get_entry' );
add_action( 'wp_ajax_nopriv_owc_cchub_get_entry', 'owc_cchub_ajax_get_entry' );

/**
 * AJAX: Search regulation rules for autocomplete.
 *
 * @return void
 */
function owc_oat_ajax_search_rules() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
    if ( strlen( $term ) < 2 ) {
        wp_send_json( array() );
    }

    $results = owc_oat_search_rules( $term );

    if ( is_wp_error( $results ) ) {
        wp_send_json_error( $results->get_error_message() );
    }

    wp_send_json( $results );
}

/**
 * AJAX: Search approved custom content entries (ccHub) for the cchub_picker field.
 *
 * @return void
 */
function owc_oat_ajax_search_cchub() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
    if ( strlen( $term ) < 2 ) {
        wp_send_json( array() );
    }

    global $wpdb;
    $prefix = $wpdb->prefix;
    $like   = '%' . $wpdb->esc_like( $term ) . '%';

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT e.id, m_name.meta_value AS content_name, m_type.meta_value AS content_type, e.coordinator_genre
         FROM {$prefix}oat_entries e
         JOIN {$prefix}oat_entry_meta m_name ON e.id = m_name.entry_id AND m_name.meta_key = 'content_name'
         LEFT JOIN {$prefix}oat_entry_meta m_type ON e.id = m_type.entry_id AND m_type.meta_key = 'content_type'
         WHERE e.domain = 'custom_content' AND e.status = 'approved'
           AND m_name.meta_value LIKE %s
         ORDER BY m_name.meta_value ASC
         LIMIT 20",
        $like
    ) );

    $results = array();
    foreach ( $rows as $r ) {
        $label = $r->content_type ? 'Custom ' . $r->content_type . ': ' . $r->content_name : $r->content_name;
        if ( $r->coordinator_genre && function_exists( 'owc_entity_get_title' ) ) {
            $coord_title = owc_entity_get_title( 'coordinator', $r->coordinator_genre );
            if ( $coord_title ) {
                $label .= ' — ' . $coord_title;
            }
        }
        $results[] = array(
            'id'    => (int) $r->id,
            'label' => $label,
            'value' => $r->content_name,
        );
    }

    wp_send_json( $results );
}

/**
 * AJAX: Execute a workflow action on an entry.
 *
 * @return void
 */
function owc_oat_ajax_process_action() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $entry_id    = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
    $action_type = isset( $_POST['action_type'] ) ? sanitize_text_field( $_POST['action_type'] ) : '';
    $note        = isset( $_POST['note'] ) ? sanitize_textarea_field( $_POST['note'] ) : '';

    if ( ! $entry_id || ! $action_type ) {
        wp_send_json_error( 'Missing entry_id or action_type.' );
    }

    // Collect extra data for specific actions.
    $extra_data = array();
    if ( ! empty( $_POST['vote_reference'] ) ) {
        $extra_data['vote_reference'] = sanitize_text_field( $_POST['vote_reference'] );
    }
    if ( ! empty( $_POST['additional_seconds'] ) ) {
        $extra_data['additional_seconds'] = absint( $_POST['additional_seconds'] );
    }
    if ( ! empty( $_POST['new_user_id'] ) ) {
        $extra_data['target_user_id'] = absint( $_POST['new_user_id'] );
    }
    if ( ! empty( $_POST['delegate_user_id'] ) ) {
        $extra_data['target_user_id'] = absint( $_POST['delegate_user_id'] );
    }
    if ( ! empty( $_POST['coordinators'] ) ) {
        $extra_data['coordinators'] = sanitize_text_field( wp_unslash( $_POST['coordinators'] ) );
    }

    $result = owc_oat_execute_action( $entry_id, $action_type, $note, $extra_data );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    wp_send_json_success( $result );
}

/**
 * AJAX: Toggle watch on an entry.
 *
 * @return void
 */
function owc_oat_ajax_toggle_watch() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $entry_id     = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
    $watch_action = isset( $_POST['watch_action'] ) ? sanitize_text_field( $_POST['watch_action'] ) : 'toggle';

    if ( ! $entry_id ) {
        wp_send_json_error( 'Missing entry_id.' );
    }

    $result = owc_oat_toggle_watch( $entry_id, $watch_action );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    wp_send_json_success( $result );
}

/**
 * AJAX: Get rendered form fields for a domain.
 *
 * Returns server-rendered HTML so the client can insert it directly
 * into #owc-oat-domain-fields without client-side field rendering.
 *
 * @return void
 */
function owc_oat_ajax_get_domain_fields() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $domain    = isset( $_REQUEST['domain'] ) ? sanitize_text_field( $_REQUEST['domain'] ) : '';
    $form_slug = isset( $_REQUEST['form_slug'] ) ? sanitize_text_field( $_REQUEST['form_slug'] ) : '';

    if ( empty( $domain ) && empty( $form_slug ) ) {
        wp_send_json_error( 'Missing domain or form_slug.' );
    }

    $slug   = $form_slug ? $form_slug : $domain;
    $fields = owc_oat_get_form_fields( $slug, 'submit' );

    if ( is_wp_error( $fields ) ) {
        wp_send_json_error( $fields->get_error_message() );
    }

    if ( empty( $fields ) ) {
        wp_send_json_success( array( 'html' => '' ) );
    }

    ob_start();
    owc_oat_render_fields( $fields );
    if ( class_exists( '_WP_Editors', false ) ) {
        _WP_Editors::editor_js();
    }
    $html = ob_get_clean();

    wp_send_json_success( array( 'html' => $html ) );
}

function owc_oat_ajax_get_domain_forms() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $domain_slug = isset( $_REQUEST['domain_slug'] ) ? sanitize_text_field( $_REQUEST['domain_slug'] ) : '';
    if ( empty( $domain_slug ) ) {
        wp_send_json_error( 'Missing domain_slug.' );
    }

    $forms = owc_oat_get_forms_for_domain( $domain_slug );
    wp_send_json_success( $forms );
}

/**
 * AJAX: Search characters by name for autocomplete (D-056).
 *
 * @return void
 */
function owc_oat_ajax_search_characters() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
    if ( strlen( $term ) < 2 ) {
        wp_send_json( array() );
    }
    $scope          = isset( $_GET['scope'] ) ? sanitize_text_field( $_GET['scope'] ) : '';
    $chronicle_slug = isset( $_GET['chronicle_slug'] ) ? strtolower( sanitize_text_field( $_GET['chronicle_slug'] ) ) : '';

    if ( owc_oat_is_local() ) {
        wp_send_json( owc_oat_search_characters_for_user( $term, $scope, $chronicle_slug ) );
    }
    $results = owc_oat_request( 'characters/search', array( 'term' => $term, 'scope' => $scope, 'chronicle_slug' => $chronicle_slug ) );
    wp_send_json( is_array( $results ) ? array_values( $results ) : array() );
}

/**
 * AJAX: The creature taxonomy for the pickers, on every site (no gateway key in the browser).
 *
 * @return void
 */
function owc_oat_ajax_creature_taxonomy() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );
    wp_send_json_success( owc_oat_get_creature_taxonomy() );
}

/**
 * AJAX: Create a new character inline from the create panel.
 *
 * The create itself runs on archivist (owc_oat_create_character_record()), directly or
 * through the gateway, so the player link is an archivist account on every site.
 *
 * @return void
 */
function owc_oat_ajax_create_character() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    if ( ! owc_oat_can_create_character() ) {
        wp_send_json_error( 'You do not have permission to create characters.' );
    }

    $input = array();
    foreach ( array( 'character_name', 'chronicle_slug', 'pc_npc', 'npc_coordinator', 'player_name', 'player_email', 'creature_genre', 'creature_sub_type', 'creature_type', 'creature_variant' ) as $field ) {
        $input[ $field ] = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
    }

    $result = owc_oat_create_character( $input );
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    wp_send_json_success( $result );
}
/**
 * AJAX: Look up HST display name for a chronicle slug (D-058).
 *
 * Resolves `chronicle/{slug}/hst` role via accessSchema to find the HST.
 *
 * @return void
 */
function owc_oat_ajax_lookup_hst() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $slug = isset( $_GET['chronicle_slug'] ) ? sanitize_text_field( $_GET['chronicle_slug'] ) : '';
    if ( empty( $slug ) ) {
        wp_send_json_error( 'Missing chronicle_slug.' );
    }

    // Build the role path: chronicle/{slug}/hst
    $role_path = 'chronicle/' . $slug . '/hst';

    // Try to find who holds this role via ASC.
    if ( ! function_exists( 'owc_asc_get_all_roles' ) ) {
        wp_send_json_success( array( 'found' => false, 'name' => '' ) );
    }

    $data = owc_asc_get_all_roles( 'owc' );
    if ( is_wp_error( $data ) || ! is_array( $data ) || ! isset( $data['roles'] ) ) {
        wp_send_json_success( array( 'found' => false, 'name' => '' ) );
    }

    // Search for the HST role and find who holds it.
    $hst_name = '';
    foreach ( $data['roles'] as $role ) {
        $role = (array) $role;
        $path = isset( $role['path'] ) ? strtolower( (string) $role['path'] ) : '';
        if ( $path === strtolower( $role_path ) ) {
            $hst_name = isset( $role['name'] ) ? (string) $role['name'] : '';
            break;
        }
    }

    if ( $hst_name ) {
        wp_send_json_success( array( 'found' => true, 'name' => $hst_name ) );
    }

    wp_send_json_success( array( 'found' => false, 'name' => '' ) );
}

/**
 * AJAX: Search accounts for the account pickers (character link, reassign, delegate, user fields).
 *
 * Returns archivist accounts, matched by name, login or email, or by a role path, and only to
 * site admins, staff, coordinators and the Archivist.
 *
 * @return void
 */
function owc_oat_ajax_search_users() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $term = isset( $_REQUEST['term'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['term'] ) ) : '';
    if ( strlen( $term ) < 2 ) {
        wp_send_json( array() );
    }

    $results = owc_oat_find_link_users( $term );
    wp_send_json( is_array( $results ) ? array_values( $results ) : array() );
}
/**
 * AJAX: Get controlling coordinator(s) for a set of regulation rule IDs.
 *
 * Accepts a JSON array of rule IDs, queries OAT_Regulation_Rule for each,
 * and returns unique coordinator genre slugs with human-readable names.
 *
 * @return void
 */
function owc_oat_ajax_get_coordinators_for_rules() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $rule_ids_raw = isset( $_GET['rule_ids'] ) ? sanitize_text_field( $_GET['rule_ids'] ) : '[]';
    $rule_ids     = json_decode( $rule_ids_raw, true );
    // D3: PC/NPC determines which level column to use.
    $pc_npc       = isset( $_GET['pc_npc'] ) ? sanitize_text_field( $_GET['pc_npc'] ) : 'pc';

    if ( ! is_array( $rule_ids ) || empty( $rule_ids ) ) {
        wp_send_json_success( array( 'coordinators' => array(), 'requires_coord' => false ) );
    }

    $rule_ids = array_map( 'absint', $rule_ids );
    $seen     = array();
    $results  = array();

    // D3: Track highest regulation level for requires_coord determination.
    $priority = array(
        'council_vote'         => 4,
        'disallowed'           => 3,
        'coordinator_approval' => 2,
        'coordinator_notify'   => 1,
    );
    $highest = 0;

    foreach ( $rule_ids as $rid ) {
        if ( ! $rid ) {
            continue;
        }

        // Resolve rule data: prefer local DB (archivist), fall back to remote cache.
        $genre     = '';
        $pc_level  = '';
        $npc_level = '';

        if ( class_exists( 'OAT_Regulation_Rule' ) ) {
            $rule = OAT_Regulation_Rule::find( $rid );
            if ( ! $rule || empty( $rule->genre ) ) {
                continue;
            }
            $genre     = $rule->genre;
            $pc_level  = $rule->pc_level;
            $npc_level = $rule->npc_level;
        } elseif ( function_exists( 'owc_oat_find_cached_rule' ) ) {
            $rule = owc_oat_find_cached_rule( $rid );
            if ( ! $rule || empty( $rule['coordinator'] ) ) {
                continue;
            }
            $genre     = $rule['coordinator'];
            $pc_level  = isset( $rule['pc_level'] ) ? $rule['pc_level'] : '';
            $npc_level = isset( $rule['npc_level'] ) ? $rule['npc_level'] : '';
        } else {
            continue;
        }

        // D3: Use pc_level or npc_level based on character type.
        $level = ( 'npc' === $pc_npc && ! empty( $npc_level ) ) ? $npc_level : $pc_level;
        if ( $level && isset( $priority[ $level ] ) && $priority[ $level ] > $highest ) {
            $highest = $priority[ $level ];
        }

        if ( isset( $seen[ $genre ] ) ) {
            continue;
        }
        $seen[ $genre ] = true;

        $name = $genre;
        if ( function_exists( 'owc_entity_get_title' ) ) {
            $title = owc_entity_get_title( 'coordinator', $genre );
            if ( $title ) {
                $name = $title;
            }
        }

        $results[] = array(
            'genre' => $genre,
            'name'  => $name,
        );
    }

    wp_send_json_success( array(
        'coordinators'   => $results,
        'requires_coord' => $highest >= 2,
    ) );
}

/**
 * AJAX: Frontend entry submission from Elementor Submit Form widget.
 *
 * Collects POST data, strips framework fields, passes meta to owc_oat_submit().
 * Returns entry_id on success.
 *
 * @return void
 */
function owc_oat_ajax_submit_entry_frontend() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( __( 'You must be logged in to submit.', 'owbn-archivist' ) );
    }

    $domain    = isset( $_POST['oat_domain'] ) ? sanitize_key( $_POST['oat_domain'] ) : '';
    $form_slug = isset( $_POST['oat_form_slug'] ) ? sanitize_key( $_POST['oat_form_slug'] ) : '';

    if ( ! $domain ) {
        wp_send_json_error( __( 'Please select a domain.', 'owbn-archivist' ) );
    }

    $skip = array( 'action', 'nonce', '_wpnonce', 'oat_domain', 'oat_form_slug' );
    $meta = array();
    foreach ( $_POST as $key => $value ) {
        if ( in_array( $key, $skip, true ) ) {
            continue;
        }
        // Strip oat_meta_ prefix so keys match domain field names.
        $clean_key = sanitize_key( $key );
        if ( strpos( $clean_key, 'oat_meta_' ) === 0 ) {
            $clean_key = substr( $clean_key, 9 );
        }
        if ( '' === $clean_key ) {
            continue;
        }
        if ( is_array( $value ) ) {
            $meta[ $clean_key ] = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $value ) );
        } else {
            $meta[ $clean_key ] = sanitize_text_field( wp_unslash( $value ) );
        }
    }

    // Parse rules from meta.
    $parsed_rules = array();
    foreach ( array( 'regulation_rules', 'ru_rules' ) as $rk ) {
        if ( ! empty( $meta[ $rk ] ) ) {
            $decoded = is_string( $meta[ $rk ] ) ? json_decode( $meta[ $rk ], true ) : $meta[ $rk ];
            if ( is_array( $decoded ) && ! empty( $decoded ) ) {
                $parsed_rules = $decoded;
            }
            break;
        }
    }

    // Batch split: character_lifecycle with multiple rules → one entry per rule.
    $is_cl    = ( 'character_lifecycle' === $domain );
    $do_split = $is_cl && count( $parsed_rules ) > 1;

    $batches = array();
    if ( $do_split ) {
        foreach ( $parsed_rules as $rule ) {
            $entry_meta = $meta;
            foreach ( array( 'regulation_rules', 'ru_rules' ) as $rk ) {
                if ( isset( $entry_meta[ $rk ] ) ) {
                    $entry_meta[ $rk ] = wp_json_encode( array( $rule ) );
                }
            }
            // Build item_description from the rule.
            if ( is_array( $rule ) && isset( $rule['text'] ) ) {
                $entry_meta['item_description'] = sanitize_text_field( $rule['text'] );
            } elseif ( is_numeric( $rule ) && class_exists( 'OAT_Regulation_Rule' ) ) {
                $rule_obj = OAT_Regulation_Rule::find( (int) $rule );
                if ( $rule_obj ) {
                    $parts = array_filter( array( $rule_obj->category, $rule_obj->subcategory, $rule_obj->condition_name ) );
                    $entry_meta['item_description'] = implode( ': ', $parts );
                }
            }
            $batches[] = array( 'meta' => $entry_meta, 'rules' => array( $rule ) );
        }
    } else {
        $batches[] = array( 'meta' => $meta, 'rules' => $parsed_rules );
    }

    $created_ids = array();
    foreach ( $batches as $batch ) {
        $data = array(
            'domain' => $domain,
            'meta'   => $batch['meta'],
            'rules'  => $batch['rules'],
        );
        if ( $form_slug ) {
            $data['form_slug'] = $form_slug;
        }
        if ( ! empty( $batch['meta']['chronicle_slug'] ) ) {
            $data['chronicle_slug'] = $batch['meta']['chronicle_slug'];
        }
        if ( ! empty( $batch['meta']['coordinator_genre'] ) ) {
            $data['coordinator_genre'] = $batch['meta']['coordinator_genre'];
        }
        // Later lines are told which lines came before, so those with the same approvers join one group.
        if ( $created_ids ) {
            $data['batch'] = array( 'prior' => $created_ids );
        }

        $result = owc_oat_submit( $data );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        $eid = is_array( $result ) && isset( $result['entry_id'] ) ? (int) $result['entry_id'] : (int) $result;
        $created_ids[] = $eid;
    }

    // Lines that all joined one group open as that group, on its first line.
    $first_bundle = count( $created_ids ) > 1 && function_exists( 'owc_oat_get_entry' ) ? owc_oat_get_entry( $created_ids[0] ) : null;
    $grouped      = is_array( $first_bundle ) && ! empty( $first_bundle['batch']['lines'] ) ? count( $first_bundle['batch']['lines'] ) : 0;

    if ( count( $created_ids ) === 1 || ( $grouped && $grouped === count( $created_ids ) ) ) {
        wp_send_json_success( array( 'entry_id' => $created_ids[0] ) );
    } else {
        wp_send_json_success( array(
            'entry_id' => $created_ids[0],
            'batch'    => true,
            'count'    => count( $created_ids ),
            'ids'      => $created_ids,
        ) );
    }
}

/**
 * AJAX: Recent activity feed for Elementor Activity Feed widget auto-refresh.
 *
 * @return void
 */
function owc_oat_ajax_get_recent_activity() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in.' );
    }

    $user_id = get_current_user_id();
    $limit   = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 10;
    $domain  = isset( $_POST['domain'] ) ? sanitize_key( $_POST['domain'] ) : '';

    $items = owc_oat_get_recent_activity( $user_id, $limit, $domain );

    if ( is_wp_error( $items ) ) {
        wp_send_json_error( $items->get_error_message() );
    }

    // Render HTML for AJAX refresh using the Activity widget's static method.
    $detail_url = '/oat-entry/';
    if ( class_exists( 'OWC_OAT_Activity_Widget' ) ) {
        $html = OWC_OAT_Activity_Widget::render_items( $items, $detail_url );
        wp_send_json_success( array( 'html' => $html ) );
    }

    wp_send_json_success( array( 'items' => $items ) );
}

/**
 * AJAX: Get a single custom content entry for the ccHub modal.
 */
function owc_cchub_ajax_get_entry() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $entry_id = absint( $_REQUEST['entry_id'] ?? 0 );
    if ( ! $entry_id ) {
        wp_send_json_error( 'No entry ID.' );
    }

    if ( ! function_exists( 'owc_oat_get_cchub_entry' ) ) {
        wp_send_json_error( 'ccHub API not available.' );
    }

    $data = owc_oat_get_cchub_entry( $entry_id );

    if ( is_wp_error( $data ) ) {
        wp_send_json_error( $data->get_error_message() );
    }

    // Build modal HTML server-side and translate it via TP for the requested
    // language. The frontend reads `data.html` directly so the modal mirrors
    // whatever language the page is currently showing. Falls back to the raw
    // data fields for any caller that hasn't been updated yet.
    $lang_slug   = isset( $_REQUEST['lang'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['lang'] ) ) : '';
    $lang_prefix = $lang_slug ? '/' . $lang_slug : '';

    if ( function_exists( 'owc_cchub_render_entry_html' ) ) {
        $html = owc_cchub_render_entry_html( $data, $lang_prefix );
        if ( function_exists( 'owc_cchub_translate_html' ) && function_exists( 'owc_oat_resolve_trp_language' ) ) {
            $target_locale = owc_oat_resolve_trp_language( $lang_slug );
            $html = owc_cchub_translate_html( $html, $target_locale );
        }
        $data['html'] = $html;
    }

    wp_send_json_success( $data );
}

/**
 * AJAX: Get registry section headers with counts for a scope.
 *
 * POST params: scope (mine|chronicles|coordinators|decommissioned)
 */
function owc_oat_ajax_registry_sections() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $scope = isset( $_POST['scope'] ) ? sanitize_text_field( $_POST['scope'] ) : 'mine';
    $valid = array( 'mine', 'chronicles', 'coordinators', 'decommissioned' );
    if ( ! in_array( $scope, $valid, true ) ) {
        wp_send_json_error( 'Invalid scope.' );
    }

    $data = owc_oat_get_registry_sections( $scope );
    if ( is_wp_error( $data ) ) {
        wp_send_json_error( $data->get_error_message() );
    }

    wp_send_json_success( $data );
}

/**
 * AJAX: Get characters for a single registry section.
 *
 * POST params: section_key (e.g. 'mine', 'chronicle-hartford', 'coordinator-vampire')
 */
function owc_oat_ajax_registry_section() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $section_key = isset( $_POST['section_key'] ) ? sanitize_text_field( $_POST['section_key'] ) : '';
    if ( ! $section_key ) {
        wp_send_json_error( 'Missing section_key.' );
    }

    $data = owc_oat_get_section_characters( $section_key );
    if ( is_wp_error( $data ) ) {
        wp_send_json_error( $data->get_error_message() );
    }

    wp_send_json_success( $data );
}

/**
 * AJAX: Search registry characters by name or chronicle.
 *
 * POST params: q (search term, min 2 chars)
 * Returns scoped results — user only sees characters they have access to.
 */
function owc_oat_ajax_registry_search() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $q = isset( $_POST['q'] ) ? sanitize_text_field( $_POST['q'] ) : '';
    if ( strlen( $q ) < 2 ) {
        wp_send_json_error( 'Search term too short.' );
    }

    $data = owc_oat_registry_search( $q );
    if ( is_wp_error( $data ) ) {
        wp_send_json_error( $data->get_error_message() );
    }

    wp_send_json_success( $data );
}

/**
 * AJAX: Save registry column preferences for current user.
 */
function owc_oat_ajax_save_registry_columns() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $columns = isset( $_POST['columns'] ) ? json_decode( stripslashes( $_POST['columns'] ), true ) : array();
    if ( ! is_array( $columns ) ) {
        wp_send_json_error( 'Invalid columns.' );
    }

    // Sanitize — only allow known column keys.
    $allowed = array( 'character', 'player', 'chronicle', 'type', 'pcnpc', 'status', 'entries', 'my_entries', 'last_activity' );
    $columns = array_values( array_intersect( $columns, $allowed ) );

    // Ensure mandatory columns.
    foreach ( array( 'character', 'chronicle', 'status' ) as $mandatory ) {
        if ( ! in_array( $mandatory, $columns, true ) ) {
            $columns[] = $mandatory;
        }
    }

    update_user_meta( get_current_user_id(), 'owc_oat_registry_columns', $columns );
    wp_send_json_success();
}

