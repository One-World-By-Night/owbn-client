<?php
/**
 * AJAX Data Search Endpoints
 *
 * Quick search across cached data sources for the settings page.
 * All searches read from existing transient caches — no new remote fetches.
 *
 */

defined( 'ABSPATH' ) || exit;


add_action( 'wp_ajax_owc_search_chronicles', 'owc_ajax_search_chronicles' );
add_action( 'wp_ajax_owc_search_coordinators', 'owc_ajax_search_coordinators' );
add_action( 'wp_ajax_owc_search_territories', 'owc_ajax_search_territories' );
add_action( 'wp_ajax_owc_search_asc_roles', 'owc_ajax_search_asc_roles' );

/**
 * Search chronicles via entity resolution.
 */
function owc_ajax_search_chronicles() {
	check_ajax_referer( 'owc_data_search_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized' );
	}

	$term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
	if ( strlen( $term ) < 2 ) {
		wp_send_json( array() );
	}

	$results = function_exists( 'owc_entity_search' )
		? owc_entity_search( 'chronicle', $term, 15 )
		: array();

	wp_send_json( $results );
}

/**
 * Search coordinators via entity resolution.
 */
function owc_ajax_search_coordinators() {
	check_ajax_referer( 'owc_data_search_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized' );
	}

	$term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
	if ( strlen( $term ) < 2 ) {
		wp_send_json( array() );
	}

	$results = function_exists( 'owc_entity_search' )
		? owc_entity_search( 'coordinator', $term, 15 )
		: array();

	wp_send_json( $results );
}

/**
 * Search territories from cached data.
 */
function owc_ajax_search_territories() {
	check_ajax_referer( 'owc_data_search_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized' );
	}

	$term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
	if ( strlen( $term ) < 2 ) {
		wp_send_json( array() );
	}

	wp_send_json( owc_territory_search( $term, 15 ) );
}

/**
 * Search accessSchema roles from cached data.
 */
function owc_ajax_search_asc_roles() {
	check_ajax_referer( 'owc_data_search_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized' );
	}

	$term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
	if ( strlen( $term ) < 2 ) {
		wp_send_json( array() );
	}

	wp_send_json( owc_asc_role_search( $term, 15 ) );
}


/**
 * Search territories by title, owner, or location.
 *
 * @param string $query Search query.
 * @param int    $limit Max results.
 * @return array
 */
function owc_territory_search( $query, $limit = 10 ) {
	if ( ! function_exists( 'owc_get_territories' ) ) {
		return array();
	}

	$items = owc_get_territories();
	if ( ! is_array( $items ) ) {
		return array();
	}

	$query   = strtolower( trim( $query ) );
	$results = array();

	foreach ( $items as $item ) {
		$item  = (array) $item;
		$title = isset( $item['title'] ) ? strtolower( (string) $item['title'] ) : '';
		$owner = isset( $item['owner'] ) ? strtolower( (string) $item['owner'] ) : '';
		$loc   = isset( $item['location'] ) ? strtolower( (string) $item['location'] ) : '';

		$score      = 0;
		$match_type = '';

		// Title matching.
		if ( $title === $query ) {
			$score = 100;
			$match_type = 'exact';
		} elseif ( 0 === strpos( $title, $query ) ) {
			$score = 80;
			$match_type = 'starts_with';
		} elseif ( false !== strpos( $title, $query ) ) {
			$score = 60;
			$match_type = 'contains';
		} elseif ( false !== strpos( $owner, $query ) ) {
			$score = 50;
			$match_type = 'owner_match';
		} elseif ( false !== strpos( $loc, $query ) ) {
			$score = 40;
			$match_type = 'location_match';
		}

		if ( $score > 0 ) {
			$results[] = array(
				'title'    => isset( $item['title'] ) ? (string) $item['title'] : '',
				'owner'    => isset( $item['owner'] ) ? (string) $item['owner'] : '',
				'location' => isset( $item['location'] ) ? (string) $item['location'] : '',
				'region'   => isset( $item['region'] ) ? (string) $item['region'] : '',
				'match_type' => $match_type,
				'_score'   => $score,
			);
		}
	}

	usort( $results, function ( $a, $b ) {
		if ( $a['_score'] !== $b['_score'] ) {
			return $b['_score'] - $a['_score'];
		}
		return strcmp( $a['title'], $b['title'] );
	} );

	$results = array_slice( $results, 0, (int) $limit );
	foreach ( $results as &$r ) {
		unset( $r['_score'] );
	}

	return $results;
}

/**
 * Search accessSchema roles by path or name.
 *
 * @param string $query Search query.
 * @param int    $limit Max results.
 * @return array
 */
function owc_asc_role_search( $query, $limit = 15 ) {
	if ( ! function_exists( 'owc_asc_get_all_roles' ) ) {
		return array();
	}

	$data = owc_asc_get_all_roles( 'owc' );
	if ( is_wp_error( $data ) || ! is_array( $data ) || ! isset( $data['roles'] ) ) {
		return array();
	}

	$query   = strtolower( trim( $query ) );
	$results = array();

	foreach ( $data['roles'] as $role ) {
		$role = (array) $role;
		$path = isset( $role['path'] ) ? strtolower( (string) $role['path'] ) : '';
		$name = isset( $role['name'] ) ? strtolower( (string) $role['name'] ) : '';

		$score      = 0;
		$match_type = '';

		// Path matching (primary).
		if ( $path === $query ) {
			$score = 100;
			$match_type = 'exact';
		} elseif ( 0 === strpos( $path, $query ) ) {
			$score = 80;
			$match_type = 'starts_with';
		} elseif ( false !== strpos( $path, $query ) ) {
			$score = 60;
			$match_type = 'contains';
		} elseif ( false !== strpos( $name, $query ) ) {
			$score = 50;
			$match_type = 'name_match';
		}

		if ( $score > 0 ) {
			$results[] = array(
				'path'       => isset( $role['path'] ) ? (string) $role['path'] : '',
				'name'       => isset( $role['name'] ) ? (string) $role['name'] : '',
				'depth'      => isset( $role['depth'] ) ? (int) $role['depth'] : 0,
				'match_type' => $match_type,
				'_score'     => $score,
			);
		}
	}

	usort( $results, function ( $a, $b ) {
		if ( $a['_score'] !== $b['_score'] ) {
			return $b['_score'] - $a['_score'];
		}
		return strcmp( $a['path'], $b['path'] );
	} );

	$results = array_slice( $results, 0, (int) $limit );
	foreach ( $results as &$r ) {
		unset( $r['_score'] );
	}

	return $results;
}
