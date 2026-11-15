<?php
/**
 * Chronicle sessions and statistics, drawn from the cached chronicle list: the dated sessions inside a window of time
 * (Games this week) and the counts behind the numbers box.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'owc_session_is_placeable' ) ) :
	/**
	 * Whether a schedule rule can be put on a calendar: a weekday, an H:MM start time, a frequency of 1st to 5th, Every,
	 * or Every Other with a valid anchor_date, and a real timezone. "Every Other" with no anchor_date can't be: its weeks
	 * would be a guess.
	 *
	 * @param array  $session One session_list row.
	 * @param string $tz_name The chronicle's timezone.
	 * @return bool
	 */
	function owc_session_is_placeable( array $session, $tz_name ) {
		$weekdays  = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
		$frequency = (string) ( $session['frequency'] ?? '' );
		if ( ! in_array( (string) ( $session['day'] ?? '' ), $weekdays, true )
			|| ! preg_match( '/^\d{1,2}:\d{2}$/', (string) ( $session['start_time'] ?? '' ) )
			|| ! in_array( $frequency, array( '1st', '2nd', '3rd', '4th', '5th', 'Every', 'Every Other' ), true ) ) {
			return false;
		}
		if ( 'Every Other' === $frequency ) {
			$anchor = (string) ( $session['anchor_date'] ?? '' );
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $anchor ) || ! DateTime::createFromFormat( '!Y-m-d', $anchor ) ) {
				return false;
			}
		}
		return in_array( (string) $tz_name, timezone_identifiers_list(), true );
	}
endif;

if ( ! function_exists( 'owc_expand_session_dates' ) ) :
	/**
	 * The UTC timestamps of one schedule rule's sessions inside [$from, $to], or none when the rule can't be placed
	 * (owc_session_is_placeable()). An "Every Other" rule counts its weeks from its anchor_date.
	 *
	 * @param array  $session One session_list row: frequency, day, start_time, anchor_date.
	 * @param string $tz_name The chronicle's timezone.
	 * @param int    $from    Window start, UTC timestamp.
	 * @param int    $to      Window end, UTC timestamp.
	 * @return int[] Sorted UTC timestamps.
	 */
	function owc_expand_session_dates( array $session, $tz_name, $from, $to ) {
		if ( ! owc_session_is_placeable( $session, $tz_name ) ) {
			return array();
		}
		$days      = array( 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7 );
		$nth       = array( '1st' => 1, '2nd' => 2, '3rd' => 3, '4th' => 4, '5th' => 5 );
		$frequency = (string) $session['frequency'];
		$day       = (string) $session['day'];
		preg_match( '/^(\d{1,2}):(\d{2})$/', (string) $session['start_time'], $time );
		$tz     = new DateTimeZone( (string) $tz_name );
		$anchor = 'Every Other' === $frequency ? DateTime::createFromFormat( '!Y-m-d', (string) $session['anchor_date'], $tz ) : null;

		$from   = (int) $from;
		$to     = (int) $to;
		$cursor = ( new DateTime( '@' . $from ) )->setTimezone( $tz );
		$cursor->setTime( 0, 0, 0 );
		$end    = ( new DateTime( '@' . $to ) )->setTimezone( $tz );
		$cursor->modify( '+' . ( ( $days[ $day ] - (int) $cursor->format( 'N' ) + 7 ) % 7 ) . ' days' );

		$dates = array();
		for ( $weeks = 0; $cursor <= $end && $weeks < 60; $weeks++ ) {
			if ( isset( $nth[ $frequency ] ) ) {
				$include = (int) ceil( (int) $cursor->format( 'j' ) / 7 ) === $nth[ $frequency ];
			} elseif ( null !== $anchor ) {
				$apart   = (int) $anchor->diff( $cursor )->days;
				$include = 0 === intdiv( $apart, 7 ) % 2;
			} else {
				$include = true;
			}

			if ( $include ) {
				$start = clone $cursor;
				$start->setTime( (int) $time[1], (int) $time[2], 0 );
				$ts = $start->getTimestamp();
				if ( $ts >= $from && $ts <= $to ) {
					$dates[] = $ts;
				}
			}
			$cursor->modify( '+7 days' );
		}

		sort( $dates, SORT_NUMERIC );
		return $dates;
	}
endif;

if ( ! function_exists( 'owc_chronicle_location' ) ) :
	/**
	 * A chronicle's first out-of-character location: city, region (state or province) and country code.
	 *
	 * @param array $chronicle A chronicle from owc_get_chronicles().
	 * @return array{city: string, region: string, country: string}
	 */
	function owc_chronicle_location( array $chronicle ) {
		$loc = $chronicle['ooc_locations'] ?? array();
		if ( is_array( $loc ) && isset( $loc[0] ) && is_array( $loc[0] ) ) {
			$loc = $loc[0];
		}
		$loc = is_array( $loc ) ? $loc : array();
		return array(
			'city'    => trim( (string) ( $loc['city'] ?? '' ) ),
			'region'  => trim( (string) ( $loc['region'] ?? '' ) ),
			'country' => strtoupper( trim( (string) ( $loc['country'] ?? '' ) ) ),
		);
	}
endif;

if ( ! function_exists( 'owc_genres_match' ) ) :
	/**
	 * Whether any of a list of genres holds any of the comma-separated names in $filter, case aside, the same way Find a
	 * Game's genre filter matches ("Sabbat" finds "Sabbat" and "Vampire - Sabbat"). An empty filter matches everything.
	 *
	 * @param string[] $genres Genres of a chronicle or a session.
	 * @param string   $filter Comma-separated genre names.
	 * @return bool
	 */
	function owc_genres_match( array $genres, $filter ) {
		$wanted = array_filter( array_map( 'trim', explode( ',', strtolower( (string) $filter ) ) ), 'strlen' );
		if ( ! $wanted ) {
			return true;
		}
		foreach ( $genres as $genre ) {
			$genre = strtolower( (string) $genre );
			foreach ( $wanted as $name ) {
				if ( false !== strpos( $genre, $name ) ) {
					return true;
				}
			}
		}
		return false;
	}
endif;

if ( ! function_exists( 'owc_session_kind' ) ) :
	/**
	 * The kind of a session_list row: 'social' for an OOC social meetup, 'other' for Other, and 'game' for a game,
	 * including rows saved before the type list existed.
	 *
	 * @param array $session One session_list row.
	 * @return string 'game', 'social' or 'other'.
	 */
	function owc_session_kind( array $session ) {
		$type = (string) ( $session['session_type'] ?? '' );
		if ( 'OOC Social Meetup' === $type ) {
			return 'social';
		}
		return 'Other' === $type ? 'other' : 'game';
	}
endif;

if ( ! function_exists( 'owc_get_sessions_in_window' ) ) :
	/**
	 * The dated sessions of every published chronicle inside a window of time, soonest first: each schedule rule's
	 * dates, and the one-off events. A chronicle whose timezone is unset or "UTC" (the field's default) is left out:
	 * its times can't be placed for a visitor.
	 *
	 * @param int   $from Window start, UTC timestamp.
	 * @param int   $to   Window end, UTC timestamp (at most 62 days after the start).
	 * @param array $args {
	 *     @type string   $genre     Comma-separated genre names; a session matches when one of its genres (else its
	 *                               chronicle's) holds one of them.
	 *     @type string   $region    The chronicle's OWBN region, exactly (case aside).
	 *     @type string   $country   Country code of the chronicle's location.
	 *     @type string   $game_type In-Person, Virtual or Hybrid.
	 *     @type string[] $kinds     Session kinds to include: 'game' (default), 'social', 'other'.
	 * }
	 * @return array[] Rows: chronicle, slug, city, region, country, owbn_region, genres, game_type, kind, title,
	 *                 start_utc, start_local, timezone.
	 */
	function owc_get_sessions_in_window( $from, $to, array $args = array() ) {
		$from = (int) $from;
		$to   = min( (int) $to, $from + 62 * DAY_IN_SECONDS );
		if ( $to <= $from ) {
			return array();
		}
		$args = wp_parse_args(
			$args,
			array(
				'genre'     => '',
				'region'    => '',
				'country'   => '',
				'game_type' => '',
				'kinds'     => array( 'game' ),
			)
		);
		$kinds = array_map( 'strval', (array) $args['kinds'] );

		$cache_key = 'owc_sessions_' . md5( wp_json_encode( array( 1, intdiv( $from, HOUR_IN_SECONDS ), intdiv( $to, HOUR_IN_SECONDS ), $args ) ) );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$chronicles = owc_get_chronicles();
		if ( is_wp_error( $chronicles ) || ! is_array( $chronicles ) ) {
			return array();
		}

		$rows = array();
		foreach ( $chronicles as $chronicle ) {
			$chronicle = (array) $chronicle;
			$tz        = (string) ( $chronicle['timezone'] ?? '' );
			if ( 'publish' !== ( $chronicle['status'] ?? 'publish' ) || '' === $tz || 'UTC' === $tz ) {
				continue;
			}
			$location = owc_chronicle_location( $chronicle );
			$region   = (string) ( $chronicle['chronicle_region'] ?? '' );
			$type     = (string) ( $chronicle['game_type'] ?? '' );
			if ( ( '' !== $args['region'] && 0 !== strcasecmp( $region, (string) $args['region'] ) )
				|| ( '' !== $args['country'] && strtoupper( (string) $args['country'] ) !== $location['country'] )
				|| ( '' !== $args['game_type'] && 0 !== strcasecmp( $type, (string) $args['game_type'] ) ) ) {
				continue;
			}
			$chronicle_genres = array_values( array_filter( array_map( 'strval', (array) ( $chronicle['genres'] ?? array() ) ), 'strlen' ) );
			$row              = array(
				'chronicle'   => (string) ( $chronicle['title'] ?? '' ),
				'slug'        => (string) ( $chronicle['slug'] ?? '' ),
				'city'        => $location['city'],
				'region'      => $location['region'],
				'country'     => $location['country'],
				'owbn_region' => $region,
				'game_type'   => $type,
				'timezone'    => $tz,
			);

			foreach ( (array) ( $chronicle['session_list'] ?? array() ) as $session ) {
				$session = (array) $session;
				$kind    = owc_session_kind( $session );
				$genres  = array_values( array_filter( array_map( 'strval', (array) ( $session['genres'] ?? array() ) ), 'strlen' ) );
				$genres  = $genres ? $genres : $chronicle_genres;
				if ( ! in_array( $kind, $kinds, true ) || ! owc_genres_match( $genres, $args['genre'] ) ) {
					continue;
				}
				foreach ( owc_expand_session_dates( $session, $tz, $from, $to ) as $ts ) {
					$rows[] = $row + array(
						'genres'      => $genres,
						'kind'        => $kind,
						'title'       => '',
						'start_utc'   => $ts,
						'start_local' => wp_date( 'Y-m-d H:i', $ts, new DateTimeZone( $tz ) ),
					);
				}
			}

			if ( in_array( 'game', $kinds, true ) ) {
				foreach ( (array) ( $chronicle['session_one_offs'] ?? array() ) as $one_off ) {
					$one_off = (array) $one_off;
					$genres  = array_values( array_filter( array_map( 'strval', (array) ( $one_off['genres'] ?? array() ) ), 'strlen' ) );
					$genres  = $genres ? $genres : $chronicle_genres;
					$when    = DateTime::createFromFormat( 'Y-m-d H:i', (string) ( $one_off['event_date'] ?? '' ) . ' ' . (string) ( $one_off['start_time'] ?? '' ), new DateTimeZone( $tz ) );
					if ( ! $when || ! owc_genres_match( $genres, $args['genre'] ) ) {
						continue;
					}
					$ts = $when->getTimestamp();
					if ( $ts >= $from && $ts <= $to ) {
						$rows[] = $row + array(
							'genres'      => $genres,
							'kind'        => 'game',
							'title'       => trim( (string) ( $one_off['event_title'] ?? '' ) ),
							'start_utc'   => $ts,
							'start_local' => wp_date( 'Y-m-d H:i', $ts, new DateTimeZone( $tz ) ),
						);
					}
				}
			}
		}

		usort(
			$rows,
			static function ( $a, $b ) {
				return array( $a['start_utc'], $a['chronicle'] ) <=> array( $b['start_utc'], $b['chronicle'] );
			}
		);

		set_transient( $cache_key, $rows, HOUR_IN_SECONDS );
		return $rows;
	}
endif;

if ( ! function_exists( 'owc_genre_family' ) ) :
	/**
	 * The genre a genre name belongs to: "Vampire - Sabbat" and the older "Sabbat" are both Vampire, "Mage - Traditions"
	 * is Mage, "Changeling" is Changeling.
	 *
	 * @param string $genre A genre name from a chronicle record.
	 * @return string
	 */
	function owc_genre_family( $genre ) {
		$genre = trim( (string) $genre );
		if ( false !== strpos( $genre, ' - ' ) ) {
			return trim( strstr( $genre, ' - ', true ) );
		}
		$vampire = array( 'camarilla', 'anarch', 'sabbat', 'giovanni', 'independent', 'clan-specific' );
		return in_array( strtolower( $genre ), $vampire, true ) ? 'Vampire' : $genre;
	}
endif;

if ( ! function_exists( 'owc_is_country_code' ) ) :
	/**
	 * Whether a value is an ISO 3166-1 alpha-2 country code.
	 *
	 * @param string $code Two letters.
	 * @return bool
	 */
	function owc_is_country_code( $code ) {
		static $codes = null;
		if ( null === $codes ) {
			$codes = array_flip( str_split( 'ADAEAFAGAIALAMAOAQARASATAUAWAXAZBABBBDBEBFBGBHBIBJBLBMBNBOBQBRBSBTBVBWBYBZCACCCDCFCGCHCICKCLCMCNCOCRCUCVCWCXCYCZDEDJDKDMDODZECEEEGEHERESETFIFJFKFMFOFRGAGBGDGEGFGGGHGIGLGMGNGPGQGRGSGTGUGWGYHKHMHNHRHTHUIDIEILIMINIOIQIRISITJEJMJOJPKEKGKHKIKMKNKPKRKWKYKZLALBLCLILKLRLSLTLULVLYMAMCMDMEMFMGMHMKMLMMMNMOMPMQMRMSMTMUMVMWMXMYMZNANCNENFNGNINLNONPNRNUNZOMPAPEPFPGPHPKPLPMPNPRPSPTPWPYQARERORSRURWSASBSCSDSESGSHSISJSKSLSMSNSOSRSSSTSVSXSYSZTCTDTFTGTHTJTKTLTMTNTOTRTTTVTWTZUAUGUMUSUYUZVAVCVEVGVIVNVUWFWSYEYTZAZMZW', 2 ) );
		}
		return isset( $codes[ strtoupper( (string) $code ) ] );
	}
endif;

if ( ! function_exists( 'owc_get_chronicle_stats' ) ) :
	/**
	 * Counts for the numbers box, from the published chronicles: chronicles, countries, OWBN regions and genres, the
	 * chronicles in each genre and each game type, and how many have a schedule a calendar can place.
	 *
	 * @return array{chronicles: int, countries: int, regions: int, genres: int, per_genre: array<string,int>, per_game_type: array<string,int>, scheduled: int}|array{} Empty when the list can't be read.
	 */
	function owc_get_chronicle_stats() {
		$cached = get_transient( 'owc_chronicle_stats_v1' );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$chronicles = owc_get_chronicles();
		if ( is_wp_error( $chronicles ) || ! is_array( $chronicles ) ) {
			return array();
		}

		$live      = 0;
		$countries = array();
		$regions   = array();
		$per_genre = array();
		$per_type  = array();
		$scheduled = 0;
		foreach ( $chronicles as $chronicle ) {
			$chronicle = (array) $chronicle;
			if ( 'publish' !== ( $chronicle['status'] ?? 'publish' ) ) {
				continue;
			}
			$live++;
			$location = owc_chronicle_location( $chronicle );
			if ( owc_is_country_code( $location['country'] ) ) {
				$countries[ $location['country'] ] = true;
			}
			$region = trim( (string) ( $chronicle['chronicle_region'] ?? '' ) );
			if ( '' !== $region ) {
				$regions[ strtolower( $region ) ] = true;
			}
			$families = array();
			foreach ( (array) ( $chronicle['genres'] ?? array() ) as $genre ) {
				$family = owc_genre_family( $genre );
				if ( '' !== $family && 'other' !== strtolower( $family ) ) {
					$families[ $family ] = true;
				}
			}
			foreach ( array_keys( $families ) as $family ) {
				$per_genre[ $family ] = ( $per_genre[ $family ] ?? 0 ) + 1;
			}
			$type              = (string) ( $chronicle['game_type'] ?? '' );
			$per_type[ $type ] = ( $per_type[ $type ] ?? 0 ) + 1;

			$tz = (string) ( $chronicle['timezone'] ?? '' );
			if ( '' !== $tz && 'UTC' !== $tz ) {
				foreach ( (array) ( $chronicle['session_list'] ?? array() ) as $session ) {
					if ( owc_session_is_placeable( (array) $session, $tz ) ) {
						$scheduled++;
						break;
					}
				}
			}
		}
		arsort( $per_genre );
		arsort( $per_type );

		$stats = array(
			'chronicles'    => $live,
			'countries'     => count( $countries ),
			'regions'       => count( $regions ),
			'genres'        => count( $per_genre ),
			'per_genre'     => $per_genre,
			'per_game_type' => $per_type,
			'scheduled'     => $scheduled,
		);
		set_transient( 'owc_chronicle_stats_v1', $stats, DAY_IN_SECONDS );
		return $stats;
	}
endif;
