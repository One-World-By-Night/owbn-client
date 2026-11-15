<?php
/**
 * The public site's shortcodes. Registered on init, after the child theme's own copies, so these are the ones used.
 * Their strings keep the theme's text domain, where their Portuguese translations live.
 */

defined( 'ABSPATH' ) || exit;

/**
 * [www_news count="3"]: the latest published posts as cards. [www_member_bar]: signed-in members' report link.
 */
function owbn_www_register_shortcodes() {
	add_shortcode(
		'www_news',
		function ( $atts ) {
			$a = shortcode_atts( array( 'count' => 3 ), $atts, 'www_news' );
			$q = new WP_Query(
				array(
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'posts_per_page'      => (int) $a['count'],
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				)
			);
			ob_start();
			echo '<section class="wp-block-group owbn-news"><h2 class="wp-block-heading">' . esc_html__( 'News & Events', 'wp-eternal-www' ) . '</h2>';
			if ( $q->have_posts() ) {
				echo '<div class="owbn-news__grid">';
				while ( $q->have_posts() ) {
					$q->the_post();
					echo '<article class="owbn-news__item"><a class="owbn-news__link" href="' . esc_url( get_permalink() ) . '">';
					echo '<span class="owbn-news__date">' . esc_html( get_the_date() ) . '</span>';
					echo '<h3 class="owbn-news__title">' . esc_html( get_the_title() ) . '</h3>';
					echo '<p class="owbn-news__excerpt">' . esc_html( wp_trim_words( get_the_excerpt(), 22 ) ) . '</p>';
					echo '</a></article>';
				}
				echo '</div>';
				wp_reset_postdata();
			} else {
				echo '<p class="owbn-news__empty">' . esc_html__( 'No news yet. Check back soon.', 'wp-eternal-www' ) . '</p>';
			}
			echo '<p class="owbn-news__more"><a href="/news/">' . esc_html__( 'All news & events', 'wp-eternal-www' ) . ' &rarr;</a></p>';
			echo '</section>';
			return ob_get_clean();
		}
	);

	add_shortcode(
		'www_member_bar',
		function () {
			if ( ! is_user_logged_in() ) {
				return '';
			}
			return '<div class="www-member-bar"><p>'
				. '<a class="www-member-bar__cta" href="https://archivist.owbn.net/oat-submit/?auth=sso">' . esc_html__( 'Submit a chronicle report', 'wp-eternal-www' ) . ' &rarr;</a>'
				. ' <span class="www-member-bar__sub">' . esc_html__( '(Storytellers &amp; chronicle staff)', 'wp-eternal-www' ) . '</span></p></div>';
		}
	);
}
add_action( 'init', 'owbn_www_register_shortcodes', 20 );
