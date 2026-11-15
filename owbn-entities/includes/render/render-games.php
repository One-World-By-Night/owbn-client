<?php
/**
 * Games this week and OWBN by the numbers, drawn from owbn-core's owc_get_sessions_in_window() and
 * owc_get_chronicle_stats().
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the stylesheet and the script that shows game times in the visitor's own timezone and language.
 */
function owc_register_games_assets() {
	$css_url = defined( 'OWC_ENTITIES_CSS_URL' ) ? OWC_ENTITIES_CSS_URL : OWC_PLUGIN_URL . 'includes/assets/css/';
	$js_url  = defined( 'OWC_ENTITIES_JS_URL' ) ? OWC_ENTITIES_JS_URL : OWC_PLUGIN_URL . 'includes/assets/js/';
	$version = defined( 'OWC_ENTITIES_VERSION' ) ? OWC_ENTITIES_VERSION : OWC_VERSION;
	if ( ! wp_style_is( 'owc-games', 'registered' ) ) {
		wp_register_style( 'owc-games', $css_url . 'owc-games.css', array(), $version );
	}
	if ( ! wp_script_is( 'owc-games', 'registered' ) ) {
		wp_register_script( 'owc-games', $js_url . 'owc-games.js', array(), $version, true );
	}
}
add_action( 'wp_enqueue_scripts', 'owc_register_games_assets' );

/**
 * Enqueues the games stylesheet and script, printing the stylesheet at once when the page head has already gone out.
 */
function owc_enqueue_games_assets() {
	owc_register_games_assets();
	wp_enqueue_style( 'owc-games' );
	wp_enqueue_script( 'owc-games' );
	if ( did_action( 'wp_head' ) && ! wp_style_is( 'owc-games', 'done' ) ) {
		wp_print_styles( array( 'owc-games' ) );
	}
}

/**
 * The chronicle detail page's address for a slug: the page given, else owbn-core's chronicle detail page; '' when
 * neither is set.
 *
 * @param string $slug Chronicle slug.
 * @param int    $page Detail page id, or 0.
 * @return string
 */
function owc_games_detail_url( $slug, $page = 0 ) {
	$page = (int) $page ? (int) $page : (int) get_option( owc_option_name( 'chronicles_detail_page' ), 0 );
	$base = $page ? get_permalink( $page ) : '';
	return ( $base && '' !== (string) $slug ) ? add_query_arg( 'slug', rawurlencode( (string) $slug ), $base ) : '';
}

/**
 * A timezone's place name, e.g. "New York" for America/New_York.
 *
 * @param string $tz Timezone identifier.
 * @return string
 */
function owc_games_timezone_label( $tz ) {
	$parts = explode( '/', (string) $tz );
	return str_replace( '_', ' ', (string) end( $parts ) );
}

/**
 * Games this week: every published chronicle's games in the next few days, grouped by day, soonest first. Each time
 * shows in the chronicle's own timezone; the games script rewrites the days and times into the visitor's.
 *
 * @param array $args {
 *     @type int    $days        Days ahead, 1 to 31 (default 7).
 *     @type string $genre       Comma-separated genre names (owc_genres_match()).
 *     @type string $region      OWBN region.
 *     @type string $country     Country code.
 *     @type string $game_type   In-Person, Virtual or Hybrid.
 *     @type int    $limit       Most games to show, 0 for all.
 *     @type int    $detail_page Chronicle detail page id, 0 for owbn-core's.
 *     @type string $empty       Text for a window with no games; %d is the number of days.
 * }
 * @return string HTML.
 */
function owc_render_games_this_week( array $args = array() ) {
	if ( ! function_exists( 'owc_get_sessions_in_window' ) ) {
		return '';
	}
	$args = wp_parse_args(
		$args,
		array(
			'days'        => 7,
			'genre'       => '',
			'region'      => '',
			'country'     => '',
			'game_type'   => '',
			'limit'       => 0,
			'detail_page' => 0,
			'empty'       => '',
		)
	);
	$days = max( 1, min( 31, (int) $args['days'] ) );
	$now  = time();
	$rows = owc_get_sessions_in_window(
		$now,
		$now + $days * DAY_IN_SECONDS,
		array(
			'genre'     => (string) $args['genre'],
			'region'    => (string) $args['region'],
			'country'   => (string) $args['country'],
			'game_type' => (string) $args['game_type'],
		)
	);
	if ( (int) $args['limit'] > 0 ) {
		$rows = array_slice( $rows, 0, (int) $args['limit'] );
	}
	owc_enqueue_games_assets();

	if ( ! $rows ) {
		/* translators: %d: number of days */
		$empty = '' !== (string) $args['empty'] ? (string) $args['empty'] : _n( 'No games are scheduled in the next %d day.', 'No games are scheduled in the next %d days.', $days, 'owbn-entities' );
		return '<div class="owc-games owc-games--empty"><p class="owc-games__empty">' . esc_html( sprintf( $empty, $days ) ) . '</p></div>';
	}

	$site_tz = wp_timezone();
	$groups  = array();
	foreach ( $rows as $row ) {
		$groups[ wp_date( 'Y-m-d', $row['start_utc'], $site_tz ) ][] = $row;
	}

	$html  = '<div class="owc-games" data-owc-games>';
	$html .= '<p class="owc-games__note owc-games__note--chronicle">' . esc_html__( 'Times are each chronicle\'s local time.', 'owbn-entities' ) . '</p>';
	$html .= '<p class="owc-games__note owc-games__note--local">' . esc_html__( 'Times are in your time zone.', 'owbn-entities' ) . '</p>';
	$html .= '<div class="owc-games__days">';
	foreach ( $groups as $group ) {
		$html .= '<section class="owc-games__day">';
		$html .= '<h3 class="owc-games__date">' . esc_html( wp_date( 'l, j F', $group[0]['start_utc'], $site_tz ) ) . '</h3>';
		$html .= '<ul class="owc-games__list">';
		foreach ( $group as $row ) {
			$tz    = new DateTimeZone( $row['timezone'] );
			$url   = owc_games_detail_url( $row['slug'], (int) $args['detail_page'] );
			$name  = esc_html( $row['chronicle'] );
			$name  = $url ? '<a href="' . esc_url( $url ) . '">' . $name . '</a>' : $name;
			$where = implode( ', ', array_filter( array( $row['city'], $row['region'], $row['country'] ), 'strlen' ) );
			$what  = implode( ' · ', array_filter( array( implode( ', ', $row['genres'] ), $row['game_type'] ), 'strlen' ) );

			$html .= '<li class="owc-games__item" data-utc="' . esc_attr( (string) $row['start_utc'] ) . '">';
			$html .= '<time class="owc-games__time" datetime="' . esc_attr( gmdate( 'c', $row['start_utc'] ) ) . '">' . esc_html( wp_date( get_option( 'time_format', 'g:i a' ), $row['start_utc'], $tz ) ) . '</time>';
			$html .= ' <span class="owc-games__tz">' . esc_html( owc_games_timezone_label( $row['timezone'] ) ) . '</span>';
			$html .= ' <span class="owc-games__name">' . $name . ( '' !== $row['title'] ? ' <span class="owc-games__title">' . esc_html( $row['title'] ) . '</span>' : '' ) . '</span>';
			if ( '' !== $where ) {
				$html .= ' <span class="owc-games__where">' . esc_html( $where ) . '</span>';
			}
			if ( '' !== $what ) {
				$html .= ' <span class="owc-games__what">' . esc_html( $what ) . '</span>';
			}
			$html .= '</li>';
		}
		$html .= '</ul></section>';
	}
	$html .= '</div></div>';
	return $html;
}

/**
 * OWBN by the numbers: published chronicles, countries, OWBN regions and genres, and optionally the chronicles in each
 * genre.
 *
 * @param array $args { @type bool $show_genres List the chronicles per genre. }
 * @return string HTML, or '' when the chronicle list can't be read.
 */
function owc_render_owbn_numbers( array $args = array() ) {
	if ( ! function_exists( 'owc_get_chronicle_stats' ) ) {
		return '';
	}
	$stats = owc_get_chronicle_stats();
	if ( ! $stats ) {
		return '';
	}
	owc_enqueue_games_assets();

	$figures = array(
		array( (int) $stats['chronicles'], _n( 'chronicle', 'chronicles', (int) $stats['chronicles'], 'owbn-entities' ) ),
		array( (int) $stats['countries'], _n( 'country', 'countries', (int) $stats['countries'], 'owbn-entities' ) ),
		array( (int) $stats['regions'], _n( 'region', 'regions', (int) $stats['regions'], 'owbn-entities' ) ),
		array( (int) $stats['genres'], _n( 'genre', 'genres', (int) $stats['genres'], 'owbn-entities' ) ),
	);

	$html = '<div class="owc-numbers"><ul class="owc-numbers__figures">';
	foreach ( $figures as $figure ) {
		$html .= '<li class="owc-numbers__figure"><span class="owc-numbers__value">' . esc_html( number_format_i18n( $figure[0] ) ) . '</span> <span class="owc-numbers__label">' . esc_html( $figure[1] ) . '</span></li>';
	}
	$html .= '</ul>';
	if ( ! empty( $args['show_genres'] ) && ! empty( $stats['per_genre'] ) ) {
		$html .= '<ul class="owc-numbers__genres">';
		foreach ( $stats['per_genre'] as $genre => $count ) {
			$html .= '<li class="owc-numbers__genre"><span class="owc-numbers__genre-name">' . esc_html( (string) $genre ) . '</span> <span class="owc-numbers__genre-count">' . esc_html( number_format_i18n( (int) $count ) ) . '</span></li>';
		}
		$html .= '</ul>';
	}
	$html .= '</div>';
	return $html;
}
