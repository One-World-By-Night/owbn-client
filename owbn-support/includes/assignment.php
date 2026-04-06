<?php
defined( "ABSPATH" ) || exit;

add_filter( "wpas_new_ticket_agent_id", "owbn_support_auto_assign", 10, 3 );

function owbn_support_auto_assign( $agent_id, $ticket_id, $default_agent_id ) {
    $resolved = owbn_support_resolve_agent( $ticket_id );
    return $resolved ? $resolved : $agent_id;
}

function owbn_support_resolve_agent( $ticket_id ) {
    $terms = wp_get_post_terms( $ticket_id, "department", [ "fields" => "slugs" ] );
    if ( is_wp_error( $terms ) || empty( $terms ) ) return false;

    $slug = $terms[0];

    // Try both coordinator/* and exec/* paths.
    $paths = [
        "coordinator/" . $slug . "/coordinator",
        "exec/" . $slug . "/coordinator",
    ];

    foreach ( $paths as $path ) {
        $user_id = owbn_support_find_user_by_role( $path );
        if ( $user_id ) return $user_id;
    }

    // Fallback: web coordinator.
    return owbn_support_find_user_by_role( "exec/web/coordinator" );
}

function owbn_support_find_user_by_role( $role_path ) {
    if ( ! function_exists( "owc_asc_get_users_by_role" ) ) return false;

    $users = owc_asc_get_users_by_role( "support", $role_path );
    if ( ! is_array( $users ) || empty( $users ) ) return false;

    $fallback = false;
    foreach ( $users as $user_data ) {
        $email = is_array( $user_data ) ? ( $user_data["email"] ?? "" ) : ( $user_data->email ?? "" );
        if ( ! $email ) continue;

        $wp_user = get_user_by( "email", $email );
        if ( ! $wp_user ) continue;

        if ( ! $fallback ) $fallback = $wp_user->ID;
        if ( user_can( $wp_user, "edit_ticket" ) ) return $wp_user->ID;
    }

    return $fallback;
}

add_action( "set_object_terms", "owbn_support_reassign_on_dept_change", 10, 4 );

function owbn_support_reassign_on_dept_change( $object_id, $terms, $tt_ids, $taxonomy ) {
    if ( "department" !== $taxonomy ) return;
    $post = get_post( $object_id );
    if ( ! $post || "ticket" !== $post->post_type ) return;

    $resolved = owbn_support_resolve_agent( $object_id );
    if ( $resolved ) {
        update_post_meta( $object_id, "_wpas_assignee", $resolved );
    }
}
