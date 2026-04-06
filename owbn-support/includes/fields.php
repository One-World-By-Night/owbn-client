<?php
/**
 * OWBN Support — Field Registration
 *
 * Registers custom fields and taxonomy for Awesome Support tickets.
 */

defined( 'ABSPATH' ) || exit;

// Load custom field type classes.
require_once OWC_SUPPORT_DIR . 'includes/field-types/class-cf-chronicle-picker.php';
require_once OWC_SUPPORT_DIR . 'includes/field-types/class-cf-coordinator-picker.php';
require_once OWC_SUPPORT_DIR . 'includes/field-types/class-cf-character-picker.php';

// Register field types with AS.
add_filter( 'wpas_cf_field_types', function( $types ) {
    $types['chronicle-picker']   = 'WPAS_CF_Chronicle_Picker';
    $types['coordinator-picker'] = 'WPAS_CF_Coordinator_Picker';
    $types['character-picker']   = 'WPAS_CF_Character_Picker';
    return $types;
} );

// Register taxonomy + seed terms.
add_action( 'init', 'owbn_support_register_taxonomy', 5 );

function owbn_support_register_taxonomy() {
    register_taxonomy( 'support_category', 'ticket', array(
        'labels'            => array( 'name' => __( 'Categories', 'owbn-support' ), 'singular_name' => __( 'Category', 'owbn-support' ) ),
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'hierarchical'      => true,
        'rewrite'           => false,
    ) );
    owbn_support_seed_categories();
}

// Register AS custom fields on init (after AS loads).
add_action( 'init', 'owbn_support_register_fields', 20 );

// Preserve support_category on ticket save — AS doesn't handle our taxonomy.
add_action( 'save_post_ticket', 'owbn_support_save_category', 10, 2 );

function owbn_support_save_category( $post_id, $post ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    // AS prefixes all field names with wpas_
    $key = isset( $_POST['wpas_support_category'] ) ? 'wpas_support_category' : 'support_category';
    if ( isset( $_POST[ $key ] ) ) {
        $term_id = absint( $_POST[ $key ] );
        if ( $term_id ) {
            wp_set_object_terms( $post_id, $term_id, 'support_category' );
        }
    }
}

function owbn_support_register_fields() {
    if ( ! function_exists( 'wpas_add_custom_field' ) ) return;

    // Category taxonomy.
    wpas_add_custom_taxonomy( 'support_category', array(
        'label'              => __( 'Category', 'owbn-support' ),
        'label_plural'       => __( 'Categories', 'owbn-support' ),
        'required'           => false,
        'show_column'        => true,
        'taxo_std'           => true,
        'sortable_column'    => true,
        'filterable'         => true,
        'order'              => 1,
    ) );

    // Player ID — auto-filled, read-only.
    wpas_add_custom_field( 'owbn_player_id', array(
        'field_type'    => 'text',
        'label'         => __( 'Player ID', 'owbn-support' ),
        'show_column'   => true,
        'readonly'      => true,
        'log'           => false,
        'order'         => 5,
        'default'       => owbn_support_get_player_id(),
    ) );

    // ASC Roles — auto-filled, read-only.
    wpas_add_custom_field( 'owbn_roles', array(
        'field_type'    => 'textarea',
        'label'         => __( 'Your Roles', 'owbn-support' ),
        'show_column'   => false,
        'readonly'      => true,
        'log'           => false,
        'order'         => 6,
        'default'       => owbn_support_get_roles(),
    ) );

    // Related Chronicle — optional picker.
    wpas_add_custom_field( 'owbn_chronicle', array(
        'field_type'      => 'chronicle-picker',
        'label'           => __( 'Related Chronicle', 'owbn-support' ),
        'required'        => false,
        'show_column'     => true,
        'sortable_column' => true,
        'order'           => 10,
    ) );

    // Related Coordinator — optional picker.
    wpas_add_custom_field( 'owbn_coordinator', array(
        'field_type'      => 'coordinator-picker',
        'label'           => __( 'Related Coordinator', 'owbn-support' ),
        'required'        => false,
        'show_column'     => true,
        'sortable_column' => true,
        'order'           => 11,
    ) );

    // Related Character — optional picker.
    wpas_add_custom_field( 'owbn_character', array(
        'field_type'      => 'character-picker',
        'label'           => __( 'Related Character', 'owbn-support' ),
        'required'        => false,
        'show_column'     => true,
        'sortable_column' => true,
        'order'           => 12,
    ) );
}

/**
 * Seed default support categories if they don't exist.
 */
function owbn_support_seed_categories() {
    $terms = array(
        'account-issues'      => 'Account Issues',
        'access-roles'        => 'Access / Roles',
        'chronicle-support'   => 'Chronicle Support',
        'coordinator-support' => 'Coordinator Support',
        'technical-website'   => 'Technical / Website',
        'other'               => 'Other',
    );

    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, 'support_category' ) ) {
            wp_insert_term( $name, 'support_category', array( 'slug' => $slug ) );
        }
    }
}

/**
 * Get current user's player ID.
 */
function owbn_support_get_player_id() {
    if ( ! is_user_logged_in() ) return '';
    $key = defined( 'OWC_PLAYER_ID_META_KEY' ) ? OWC_PLAYER_ID_META_KEY : 'player_id';
    return get_user_meta( get_current_user_id(), $key, true );
}

/**
 * Get current user's ASC roles as newline-separated string.
 */
function owbn_support_get_roles() {
    if ( ! is_user_logged_in() ) return '';

    $user = wp_get_current_user();
    $roles = array();

    if ( function_exists( 'owc_asc_get_user_roles' ) ) {
        $asc = owc_asc_get_user_roles( 'oat', $user->user_email );
        if ( ! is_wp_error( $asc ) && isset( $asc['roles'] ) && is_array( $asc['roles'] ) ) {
            $roles = $asc['roles'];
        }
    } elseif ( defined( 'OWC_ASC_CACHE_KEY' ) ) {
        $cached = get_user_meta( $user->ID, OWC_ASC_CACHE_KEY, true );
        if ( is_array( $cached ) ) {
            $roles = $cached;
        }
    }

    return implode( "\n", $roles );
}
