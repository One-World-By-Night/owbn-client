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
add_action( 'wp_ajax_owc_oat_process_action', 'owc_oat_ajax_process_action' );
add_action( 'wp_ajax_owc_oat_toggle_watch', 'owc_oat_ajax_toggle_watch' );
add_action( 'wp_ajax_owc_oat_get_domain_fields', 'owc_oat_ajax_get_domain_fields' );
add_action( 'wp_ajax_owc_oat_get_domain_forms', 'owc_oat_ajax_get_domain_forms' );
add_action( 'wp_ajax_owc_oat_search_characters', 'owc_oat_ajax_search_characters' );
add_action( 'wp_ajax_owc_oat_create_character', 'owc_oat_ajax_create_character' );
add_action( 'wp_ajax_owc_oat_lookup_hst', 'owc_oat_ajax_lookup_hst' );
add_action( 'wp_ajax_owc_oat_search_users', 'owc_oat_ajax_search_users' );
add_action( 'wp_ajax_owc_oat_get_coordinators_for_rules', 'owc_oat_ajax_get_coordinators_for_rules' );
add_action( 'wp_ajax_owc_oat_submit_entry_frontend', 'owc_oat_ajax_submit_entry_frontend' );
add_action( 'wp_ajax_owc_oat_get_recent_activity', 'owc_oat_ajax_get_recent_activity' );

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
    $watch_action = isset( $_POST['watch_action'] ) ? sanitize_text_field( $_POST['watch_action'] ) : 'add';

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

    if ( ! class_exists( 'OAT_Character' ) ) {
        wp_send_json( array() );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'oat_characters';
    $like  = '%' . $wpdb->esc_like( $term ) . '%';

    // Role-based scope filtering (P4a / CC-002).
    $scope           = isset( $_GET['scope'] ) ? sanitize_text_field( $_GET['scope'] ) : '';
    $chronicle_slug  = isset( $_GET['chronicle_slug'] ) ? sanitize_text_field( $_GET['chronicle_slug'] ) : '';
    $where_extra     = '';
    $prepare_args    = array( $like );

    if ( $scope === 'player' ) {
        $user = wp_get_current_user();
        if ( $user && $user->user_email ) {
            $where_extra = ' AND player_email = %s';
            $prepare_args[] = $user->user_email;
        }
    } elseif ( $scope === 'staff' && $chronicle_slug ) {
        $where_extra = ' AND chronicle_slug = %s';
        $prepare_args[] = $chronicle_slug;
    }
    // 'coordinator' and 'archivist' scopes have no filter — all characters.

    $sql = "SELECT uuid, character_name, chronicle_slug, player_name, pc_npc FROM {$table} WHERE character_name LIKE %s{$where_extra} ORDER BY character_name ASC LIMIT 15";
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

    wp_send_json( $results );
}

/**
 * AJAX: Create a new character inline (D-056).
 *
 * @return void
 */
function owc_oat_ajax_create_character() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    if ( ! class_exists( 'OAT_Character' ) ) {
        wp_send_json_error( 'OAT_Character model not available.' );
    }

    $char_name      = isset( $_POST['character_name'] ) ? sanitize_text_field( $_POST['character_name'] ) : '';
    $chronicle_slug = isset( $_POST['chronicle_slug'] ) ? sanitize_text_field( $_POST['chronicle_slug'] ) : '';
    $pc_npc         = isset( $_POST['pc_npc'] ) ? sanitize_text_field( $_POST['pc_npc'] ) : 'pc';

    if ( empty( $char_name ) ) {
        wp_send_json_error( 'Character name is required.' );
    }

    $user = wp_get_current_user();
    if ( ! $user || ! $user->ID ) {
        wp_send_json_error( 'You must be logged in.' );
    }

    $create_data = array(
        'character_name' => $char_name,
        'chronicle_slug' => $chronicle_slug,
        'player_email'   => $user->user_email,
        'player_name'    => $user->display_name,
        'wp_user_id'     => $user->ID,
    );
    if ( in_array( $pc_npc, array( 'pc', 'npc' ), true ) ) {
        $create_data['pc_npc'] = $pc_npc;
    }

    $id = OAT_Character::create( $create_data );

    if ( ! $id ) {
        wp_send_json_error( 'Failed to create character.' );
    }

    $char = OAT_Character::find( $id );
    if ( ! $char ) {
        wp_send_json_error( 'Character created but could not be retrieved.' );
    }

    wp_send_json_success( array(
        'uuid'           => $char->uuid,
        'character_name' => $char->character_name,
        'chronicle_slug' => $char->chronicle_slug ? $char->chronicle_slug : '',
        'pc_npc'         => isset( $char->pc_npc ) ? $char->pc_npc : 'pc',
    ) );
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
 * AJAX: Search users by name for reassign/delegate autocomplete.
 *
 * Returns users matching display_name or user_login, plus ASC role path
 * matches if the term looks like a role path (contains '/').
 *
 * @return void
 */
function owc_oat_ajax_search_users() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
    if ( strlen( $term ) < 2 ) {
        wp_send_json( array() );
    }

    $results = array();

    // Search WordPress users by display_name or user_login.
    $users = get_users( array(
        'search'         => '*' . $term . '*',
        'search_columns' => array( 'user_login', 'display_name', 'user_email' ),
        'number'         => 15,
        'orderby'        => 'display_name',
    ) );

    foreach ( $users as $user ) {
        $results[] = array(
            'id'    => $user->ID,
            'label' => $user->display_name . ' (' . $user->user_login . ')',
            'value' => $user->display_name,
            'type'  => 'user',
        );
    }

    // If term contains '/' it might be an ASC role path — resolve to user(s).
    if ( strpos( $term, '/' ) !== false && function_exists( 'owc_asc_get_users_by_role' ) ) {
        // Exact role path match first.
        $role_users = owc_asc_get_users_by_role( 'oat', $term );

        // No exact match — try prefix match (e.g. "coordinator/tremere" matches
        // users with "coordinator/tremere/coordinator").
        if ( empty( $role_users ) || is_wp_error( $role_users ) ) {
            $role_users = owc_oat_search_users_by_role_prefix( $term );
        }

        if ( is_array( $role_users ) ) {
            $seen_ids = array();
            foreach ( $results as $r ) {
                $seen_ids[ $r['id'] ] = true;
            }
            foreach ( $role_users as $ru ) {
                $ru  = (array) $ru;
                $uid = isset( $ru['user_id'] ) ? (int) $ru['user_id'] : 0;
                if ( ! $uid || isset( $seen_ids[ $uid ] ) ) {
                    continue;
                }
                $name = isset( $ru['display_name'] ) ? $ru['display_name'] : '';
                $path = isset( $ru['role_path'] ) ? $ru['role_path'] : $term;
                $results[] = array(
                    'id'    => $uid,
                    'label' => $name . ' (via ' . $path . ')',
                    'value' => $name,
                    'type'  => 'role',
                );
                $seen_ids[ $uid ] = true;
            }
        }
    }

    wp_send_json( $results );
}

/**
 * Search users whose cached ASC roles start with a given prefix.
 *
 * E.g. prefix "coordinator/tremere" matches users holding
 * "coordinator/tremere/coordinator" or "coordinator/tremere/sub-coordinator".
 *
 * @param string $prefix Role path prefix.
 * @return array Array of arrays with user_id, display_name, role_path.
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
        wp_send_json_error( __( 'You must be logged in to submit.', 'owbn-client' ) );
    }

    $domain    = isset( $_POST['oat_domain'] ) ? sanitize_key( $_POST['oat_domain'] ) : '';
    $form_slug = isset( $_POST['oat_form_slug'] ) ? sanitize_key( $_POST['oat_form_slug'] ) : '';

    if ( ! $domain ) {
        wp_send_json_error( __( 'Please select a domain.', 'owbn-client' ) );
    }

    $skip = array( 'action', 'nonce', '_wpnonce', 'oat_domain', 'oat_form_slug' );
    $meta = array();
    foreach ( $_POST as $key => $value ) {
        if ( in_array( $key, $skip, true ) ) {
            continue;
        }
        $clean_key = sanitize_key( $key );
        if ( is_array( $value ) ) {
            $meta[ $clean_key ] = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $value ) );
        } else {
            $meta[ $clean_key ] = sanitize_text_field( wp_unslash( $value ) );
        }
    }

    $data = array(
        'domain' => $domain,
        'meta'   => $meta,
    );
    if ( $form_slug ) {
        $data['form_slug'] = $form_slug;
    }

    $result = owc_oat_submit( $data );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    $entry_id = is_array( $result ) && isset( $result['entry_id'] ) ? (int) $result['entry_id'] : (int) $result;
    wp_send_json_success( array( 'entry_id' => $entry_id ) );
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
