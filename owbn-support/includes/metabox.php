<?php
defined( "ABSPATH" ) || exit;

add_action( "add_meta_boxes", function() {
    add_meta_box(
        "owbn_support_context",
        __( "OWBN Context", "owbn-support" ),
        "owbn_support_render_metabox",
        "ticket",
        "side",
        "high"
    );
} );

add_action( "admin_enqueue_scripts", function( $hook ) {
    if ( ! in_array( $hook, [ "post.php", "post-new.php" ] ) ) return;
    if ( get_post_type() !== "ticket" ) return;
    wp_enqueue_style( "owbn-select2", WPAS_URL . "assets/admin/css/vendor/select2/select2-4-0-5/select2.min.css", [], "4.0.5" );
    wp_enqueue_script( "owbn-select2", WPAS_URL . "assets/admin/js/vendor/select2/select2-4-0-5/select2.min.js", [ "jquery" ], "4.0.5", true );
} );

// Save LAST so we overwrite anything AS sets from its own custom fields tab.
add_action( "save_post_ticket", "owbn_support_save_context", 99, 1 );

function owbn_support_save_context( $post_id ) {
    if ( defined( "DOING_AUTOSAVE" ) && DOING_AUTOSAVE ) return;
    if ( ! isset( $_POST["_owbn_context_nonce"] ) ) return;
    if ( ! wp_verify_nonce( $_POST["_owbn_context_nonce"], "owbn_context_" . $post_id ) ) return;
    if ( ! current_user_can( "edit_ticket", $post_id ) ) return;

    // Department — set terms and trigger reassignment.
    if ( isset( $_POST["owbn_department"] ) ) {
        $dept = absint( $_POST["owbn_department"] );
        wp_set_object_terms( $post_id, $dept ? $dept : [], "department" );

        // Auto-reassign based on new department.
        if ( $dept && function_exists( "owbn_support_resolve_agent" ) ) {
            $agent = owbn_support_resolve_agent( $post_id );
            if ( $agent ) {
                update_post_meta( $post_id, "_wpas_assignee", $agent );
            }
        }
    }

    // Category — our save overrides AS custom fields tab.
    if ( isset( $_POST["owbn_support_category"] ) ) {
        $cat = absint( $_POST["owbn_support_category"] );
        if ( $cat ) {
            wp_set_object_terms( $post_id, $cat, "support_category" );
        }
    }

    if ( isset( $_POST["owbn_chronicle"] ) ) {
        update_post_meta( $post_id, "_wpas_owbn_chronicle", sanitize_text_field( $_POST["owbn_chronicle"] ) );
    }
    if ( isset( $_POST["owbn_coordinator"] ) ) {
        update_post_meta( $post_id, "_wpas_owbn_coordinator", sanitize_text_field( $_POST["owbn_coordinator"] ) );
    }
    if ( isset( $_POST["owbn_character"] ) ) {
        update_post_meta( $post_id, "_wpas_owbn_character", sanitize_text_field( $_POST["owbn_character"] ) );
    }
}

function owbn_support_render_metabox( $post ) {
    wp_nonce_field( "owbn_context_" . $post->ID, "_owbn_context_nonce" );

    $chronicle   = get_post_meta( $post->ID, "_wpas_owbn_chronicle", true );
    $coordinator = get_post_meta( $post->ID, "_wpas_owbn_coordinator", true );
    $character   = get_post_meta( $post->ID, "_wpas_owbn_character", true );
    $sender      = get_post_meta( $post->ID, "_wpas_sender_email", true );

    // Sender email.
    if ( $sender ) {
        echo "<p style=\"font-size:13px;margin:0 0 10px;padding:8px;background:#f0f6fc;border-left:3px solid #2271b1;\"><strong>" . esc_html__( "Email from:", "owbn-support" ) . "</strong><br>" . esc_html( $sender ) . "</p>";
    }

    // Department.
    if ( taxonomy_exists( "department" ) ) {
        $current = wp_get_object_terms( $post->ID, "department", [ "fields" => "ids" ] );
        $current_id = ( ! is_wp_error( $current ) && ! empty( $current ) ) ? $current[0] : 0;
        $terms = get_terms( [ "taxonomy" => "department", "hide_empty" => false, "orderby" => "name" ] );
        echo "<p style=\"margin:8px 0 4px;\"><strong>" . esc_html__( "Department", "owbn-support" ) . "</strong></p>";
        echo "<select name=\"owbn_department\" class=\"owbn-ctx-select\" style=\"width:100%;\">";
        echo "<option value=\"0\">" . esc_html__( "— None —", "owbn-support" ) . "</option>";
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $t ) {
                echo "<option value=\"" . esc_attr( $t->term_id ) . "\" " . selected( $t->term_id, $current_id, false ) . ">" . esc_html( $t->name ) . "</option>";
            }
        }
        echo "</select>";
    }

    // Category.
    if ( taxonomy_exists( "support_category" ) ) {
        $current = wp_get_object_terms( $post->ID, "support_category", [ "fields" => "ids" ] );
        $current_id = ( ! is_wp_error( $current ) && ! empty( $current ) ) ? $current[0] : 0;
        $terms = get_terms( [ "taxonomy" => "support_category", "hide_empty" => false, "orderby" => "name" ] );
        echo "<p style=\"margin:8px 0 4px;\"><strong>" . esc_html__( "Category", "owbn-support" ) . "</strong></p>";
        echo "<select name=\"owbn_support_category\" class=\"owbn-ctx-select\" style=\"width:100%;\">";
        echo "<option value=\"0\">" . esc_html__( "— None —", "owbn-support" ) . "</option>";
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $t ) {
                echo "<option value=\"" . esc_attr( $t->term_id ) . "\" " . selected( $t->term_id, $current_id, false ) . ">" . esc_html( $t->name ) . "</option>";
            }
        }
        echo "</select>";
    }

    // Chronicle.
    echo "<p style=\"margin:8px 0 4px;\"><strong>" . esc_html__( "Related Chronicle", "owbn-support" ) . "</strong></p>";
    if ( function_exists( "owc_get_chronicles" ) ) {
        $list = array_filter( owc_get_chronicles(), function( $ch ) { return ( $ch["status"] ?? "" ) === "publish"; } );
        usort( $list, function( $a, $b ) { return strcasecmp( $a["title"] ?? "", $b["title"] ?? "" ); } );
        echo "<select name=\"owbn_chronicle\" class=\"owbn-ctx-select\" style=\"width:100%;\">";
        echo "<option value=\"\">" . esc_html__( "— None —", "owbn-support" ) . "</option>";
        foreach ( $list as $ch ) {
            $s = $ch["slug"] ?? ""; $n = $ch["title"] ?? $s;
            echo "<option value=\"" . esc_attr( $s ) . "\" " . selected( $s, $chronicle, false ) . ">" . esc_html( $n ) . "</option>";
        }
        echo "</select>";
    } else {
        echo "<input type=\"text\" name=\"owbn_chronicle\" value=\"" . esc_attr( $chronicle ) . "\" style=\"width:100%;\">";
    }

    // Coordinator.
    echo "<p style=\"margin:8px 0 4px;\"><strong>" . esc_html__( "Related Coordinator", "owbn-support" ) . "</strong></p>";
    if ( function_exists( "owc_get_coordinators" ) ) {
        $list = owc_get_coordinators();
        echo "<select name=\"owbn_coordinator\" class=\"owbn-ctx-select\" style=\"width:100%;\">";
        echo "<option value=\"\">" . esc_html__( "— None —", "owbn-support" ) . "</option>";
        foreach ( $list as $co ) {
            $co = (array) $co; $s = $co["slug"] ?? ""; $n = $co["title"] ?? $s;
            echo "<option value=\"" . esc_attr( $s ) . "\" " . selected( $s, $coordinator, false ) . ">" . esc_html( $n ) . "</option>";
        }
        echo "</select>";
    } else {
        echo "<input type=\"text\" name=\"owbn_coordinator\" value=\"" . esc_attr( $coordinator ) . "\" style=\"width:100%;\">";
    }

    // Character.
    echo "<p style=\"margin:8px 0 4px;\"><strong>" . esc_html__( "Related Character", "owbn-support" ) . "</strong></p>";
    echo "<input type=\"text\" name=\"owbn_character\" value=\"" . esc_attr( $character ) . "\" style=\"width:100%;\" placeholder=\"" . esc_attr__( "Character ID or name", "owbn-support" ) . "\">";

    // Roles (read-only).
    $roles = get_post_meta( $post->ID, "_wpas_owbn_roles", true );
    if ( $roles ) {
        $rl = array_filter( explode( "\n", $roles ) );
        if ( ! empty( $rl ) ) {
            echo "<p style=\"margin:10px 0 4px;\"><strong>" . esc_html__( "Submitter Roles", "owbn-support" ) . "</strong></p>";
            echo "<ul style=\"margin:0;padding-left:16px;font-family:monospace;font-size:11px;\">";
            foreach ( $rl as $r ) { echo "<li>" . esc_html( $r ) . "</li>"; }
            echo "</ul>";
        }
    }

    echo "<script>jQuery(function(\$){ \$(\".owbn-ctx-select\").select2({ width: \"100%\", allowClear: true, placeholder: \"— None —\" }); });</script>";
}
