<?php
/**
 * Council boxes: what the Council is voting on, and recent bylaw changes, drawn from owbn-core's votes and bylaws
 * functions and cached so a busy page doesn't call council on every view. A box whose source can't be read shows
 * nothing rather than a misleading "nothing open".
 */

defined( 'ABSPATH' ) || exit;

/**
 * The public site behind one of owbn-core's data sources, e.g. "https://council.owbn.net" for 'votes'; '' when unset.
 *
 * @param string $type owbn-core data type: 'votes' or 'bylaws'.
 * @return string
 */
function owc_council_site_root( $type ) {
	$base = function_exists( 'owc_get_remote_base' ) ? owc_get_remote_base( $type ) : '';
	if ( '' === $base ) {
		return '';
	}
	$parts = wp_parse_url( $base );
	return ( ! empty( $parts['scheme'] ) && ! empty( $parts['host'] ) ) ? $parts['scheme'] . '://' . $parts['host'] : '';
}

/**
 * Vote types in words, by wp-voting-plugin voting type.
 *
 * @return array<string,string>
 */
function owc_council_vote_type_labels() {
	return array(
		'singleton'      => __( 'Single choice', 'owbn-entities' ),
		'rcv'            => __( 'Ranked choice', 'owbn-entities' ),
		'sequential_rcv' => __( 'Ranked choice, several seats', 'owbn-entities' ),
		'stv'            => __( 'Single transferable vote', 'owbn-entities' ),
		'condorcet'      => __( 'Condorcet', 'owbn-entities' ),
		'disciplinary'   => __( 'Disciplinary', 'owbn-entities' ),
		'consent'        => __( 'Consent agenda', 'owbn-entities' ),
	);
}

/**
 * A vote type in words.
 *
 * @param string $type wp-voting-plugin voting type.
 * @return string
 */
function owc_council_vote_type_label( $type ) {
	$labels = owc_council_vote_type_labels();
	$type   = (string) $type;
	return $labels[ $type ] ?? ucfirst( str_replace( '_', ' ', $type ) );
}

/**
 * A day as markup: the day and month in words, carrying the date so the page script can write it in the page's
 * language. The day is shown as given, without moving it between timezones.
 *
 * @param string $date Date or date-time ("2026-11-21 23:59:00"); only the Y-m-d part is used.
 * @return string '' when it isn't a date.
 */
function owc_council_day_html( $date ) {
	$ymd = substr( (string) $date, 0, 10 );
	$day = DateTime::createFromFormat( '!Y-m-d', $ymd, new DateTimeZone( 'UTC' ) );
	if ( ! $day || $day->format( 'Y-m-d' ) !== $ymd ) {
		return '';
	}
	return '<span class="owc-council__day" data-owc-day="' . esc_attr( $ymd ) . '">' . esc_html( wp_date( 'j F', $day->getTimestamp(), new DateTimeZone( 'UTC' ) ) ) . '</span>';
}

/**
 * What the Council is voting on: the open votes anyone may see, soonest closing first, each linking to its page on the
 * votes site. Votes the votes site marks private or restricted are never listed.
 *
 * @param array $args {
 *     @type int    $limit     Most votes (default 5).
 *     @type string $vote_page Address of the votes site's vote page; ?wpvp_vote= is added. Default: this site's own
 *                             vote page where the votes are local, else the votes site's /en/voting-dashboard/cast-vote/.
 *     @type string $all_url   Address for "All votes". Default: the local voting dashboard, else the votes site's
 *                             /en/voting-dashboard/.
 *     @type string $empty     Text when no public vote is open.
 * }
 * @return string HTML, or '' when the votes site can't be read.
 */
function owc_render_council_votes( array $args = array() ) {
	if ( ! function_exists( 'owc_wpvp_get_open_votes_or_error' ) ) {
		return '';
	}
	$root  = owc_council_site_root( 'votes' );
	$pages = function_exists( 'owc_wpvp_is_local' ) && owc_wpvp_is_local() ? (array) get_option( 'wpvp_page_ids', array() ) : array();
	$args  = wp_parse_args(
		$args,
		array(
			'limit'     => 5,
			'vote_page' => ! empty( $pages['cast-vote'] ) ? (string) get_permalink( (int) $pages['cast-vote'] ) : ( $root ? $root . '/en/voting-dashboard/cast-vote/' : '' ),
			'all_url'   => ! empty( $pages['voting-dashboard'] ) ? (string) get_permalink( (int) $pages['voting-dashboard'] ) : ( $root ? $root . '/en/voting-dashboard/' : '' ),
			'empty'     => '',
		)
	);
	$limit = max( 1, min( 50, (int) $args['limit'] ) );
	$key   = 'owc_council_votes_public_' . $limit;
	$votes = get_transient( $key );
	if ( false === $votes ) {
		$votes = owc_wpvp_get_open_votes_or_error( $limit, 'public' );
		if ( is_wp_error( $votes ) ) {
			$votes = array( 'error' => $votes->get_error_code() );
		} else {
			$public = array();
			foreach ( $votes as $vote ) {
				$vote = (array) $vote;
				if ( 'public' === ( $vote['visibility'] ?? '' ) ) {
					$public[] = $vote;
				}
			}
			$votes = $public;
		}
		set_transient( $key, $votes, isset( $votes['error'] ) ? 5 * MINUTE_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
	}
	if ( isset( $votes['error'] ) ) {
		return '';
	}
	owc_enqueue_games_assets();

	$html = '<div class="owc-council owc-council--votes">';
	if ( ! $votes ) {
		$empty = '' !== (string) $args['empty'] ? (string) $args['empty'] : __( 'No public votes are open right now.', 'owbn-entities' );
		$html .= '<p class="owc-council__empty">' . esc_html( $empty ) . '</p>';
	} else {
		$html .= '<ul class="owc-council__list">';
		foreach ( $votes as $vote ) {
			$vote  = (array) $vote;
			$title = esc_html( (string) ( $vote['proposal_name'] ?? '' ) );
			$url   = '' !== (string) $args['vote_page'] ? add_query_arg( 'wpvp_vote', (int) ( $vote['id'] ?? 0 ), (string) $args['vote_page'] ) : '';
			$html .= '<li class="owc-council__item">';
			$html .= '<span class="owc-council__title">' . ( $url ? '<a href="' . esc_url( $url ) . '">' . $title . '</a>' : $title ) . '</span>';
			$html .= ' <span class="owc-council__meta">' . esc_html( owc_council_vote_type_label( $vote['voting_type'] ?? '' ) );
			$closes = owc_council_day_html( $vote['closing_date'] ?? '' );
			if ( '' !== $closes ) {
				/* translators: %s: day and month the vote closes */
				$html .= ' · ' . sprintf( esc_html__( 'closes %s', 'owbn-entities' ), $closes );
			}
			$html .= '</span></li>';
		}
		$html .= '</ul>';
	}
	if ( '' !== (string) $args['all_url'] ) {
		$html .= '<p class="owc-council__all"><a href="' . esc_url( (string) $args['all_url'] ) . '">' . esc_html__( 'All votes', 'owbn-entities' ) . '</a></p>';
	}
	return $html . '</div>';
}

/**
 * A bylaw clause's name as readers see it on the bylaws site: its group and section ("Character 4.e"), else its title.
 *
 * @param array $clause Clause from owbn-core's bylaws functions.
 * @return string Plain text.
 */
function owc_council_clause_name( array $clause ) {
	$section = trim( (string) ( $clause['section_id'] ?? '' ) );
	if ( '' === $section ) {
		return wp_strip_all_tags( (string) ( $clause['title'] ?? '' ) );
	}
	$group = trim( (string) ( $clause['bylaw_group_label'] ?? '' ) );
	if ( '' === $group ) {
		$group = ucwords( str_replace( array( '-', '_' ), ' ', trim( (string) ( $clause['bylaw_group'] ?? '' ) ) ) );
	}
	return wp_strip_all_tags( trim( $group . ' ' . $section ) );
}

/**
 * Recent bylaw changes: the clauses added or amended lately, newest first, each linking to the clause.
 *
 * @param array $args {
 *     @type int    $days    Days back (default 30).
 *     @type int    $limit   Most clauses (default 5).
 *     @type string $all_url Address for "All bylaws", or '' for no link.
 *     @type string $empty   Text when nothing changed; %d is the number of days.
 * }
 * @return string HTML, or '' when the bylaws site can't be read.
 */
function owc_render_bylaw_changes( array $args = array() ) {
	if ( ! function_exists( 'owc_bylaws_get_recent_or_error' ) ) {
		return '';
	}
	$args    = wp_parse_args( $args, array( 'days' => 30, 'limit' => 5, 'all_url' => '', 'empty' => '' ) );
	$days    = max( 1, min( 365, (int) $args['days'] ) );
	$limit   = max( 1, min( 50, (int) $args['limit'] ) );
	$key     = 'owc_bylaw_changes_' . $days . '_' . $limit;
	$clauses = get_transient( $key );
	if ( false === $clauses ) {
		$clauses = owc_bylaws_get_recent_or_error( $days, $limit );
		$clauses = is_wp_error( $clauses ) ? array( 'error' => $clauses->get_error_code() ) : array_values( $clauses );
		set_transient( $key, $clauses, isset( $clauses['error'] ) ? 5 * MINUTE_IN_SECONDS : HOUR_IN_SECONDS );
	}
	if ( isset( $clauses['error'] ) ) {
		return '';
	}
	owc_enqueue_games_assets();

	$html = '<div class="owc-council owc-council--bylaws">';
	if ( ! $clauses ) {
		/* translators: %d: number of days */
		$empty = '' !== (string) $args['empty'] ? (string) $args['empty'] : _n( 'No bylaw changes in the last %d day.', 'No bylaw changes in the last %d days.', $days, 'owbn-entities' );
		$html .= '<p class="owc-council__empty">' . esc_html( sprintf( $empty, $days ) ) . '</p>';
	} else {
		$html .= '<ul class="owc-council__list">';
		foreach ( $clauses as $clause ) {
			$clause  = (array) $clause;
			$title   = esc_html( owc_council_clause_name( $clause ) );
			$excerpt = trim( wp_strip_all_tags( (string) ( $clause['excerpt'] ?? '' ) ) );
			$url     = (string) ( $clause['permalink'] ?? '' );
			$day    = ! empty( $clause['modified_ts'] ) ? owc_council_day_html( wp_date( 'Y-m-d', (int) $clause['modified_ts'] ) ) : '';
			$added = 'added' === ( $clause['change'] ?? '' );
			if ( '' === $day ) {
				$meta = $added ? esc_html__( 'Added', 'owbn-entities' ) : esc_html__( 'Amended', 'owbn-entities' );
			} elseif ( $added ) {
				/* translators: %s: day and month the clause was added */
				$meta = sprintf( esc_html__( 'Added %s', 'owbn-entities' ), $day );
			} else {
				/* translators: %s: day and month the clause was amended */
				$meta = sprintf( esc_html__( 'Amended %s', 'owbn-entities' ), $day );
			}
			$html .= '<li class="owc-council__item">';
			$html .= '<span class="owc-council__title">' . ( $url ? '<a href="' . esc_url( $url ) . '">' . $title . '</a>' : $title ) . '</span>';
			if ( '' !== $excerpt ) {
				$html .= ' <span class="owc-council__excerpt">' . esc_html( $excerpt ) . '</span>';
			}
			$html .= ' <span class="owc-council__meta">' . $meta . '</span>';
			$html .= '</li>';
		}
		$html .= '</ul>';
	}
	if ( '' !== (string) $args['all_url'] ) {
		$html .= '<p class="owc-council__all"><a href="' . esc_url( (string) $args['all_url'] ) . '">' . esc_html__( 'All bylaws', 'owbn-entities' ) . '</a></p>';
	}
	return $html . '</div>';
}
