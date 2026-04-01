<?php

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Archivist Workspace Widget — modular role-based sections.
 *
 * Place multiple instances on a page, each showing a different section:
 * sites, self, chronicles, coordinators, exec, or resources.
 */
class OWC_OAT_Workspace_Widget extends Widget_Base {

	public function get_name() {
		return 'owc_oat_workspace';
	}

	public function get_title() {
		return __( 'Archivist Workspace', 'owbn-client' );
	}

	public function get_icon() {
		return 'eicon-apps';
	}

	public function get_categories() {
		return array( 'owbn-core' );
	}

	public function get_keywords() {
		return array( 'archivist', 'workspace', 'dashboard', 'roles', 'landing', 'chronicles', 'coordinator' );
	}

	public function get_style_depends() {
		return array(); // Styles are inline in render()
	}

	protected function register_controls() {
		$this->start_controls_section( 'content_section', array(
			'label' => __( 'Section', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'section', array(
			'label'   => __( 'Show Section', 'owbn-client' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'self',
			'options' => array(
				'sites'       => __( 'OWBN Sites (SSO buttons)', 'owbn-client' ),
				'self'        => __( 'My Stuff', 'owbn-client' ),
				'chronicles'  => __( 'My Chronicles', 'owbn-client' ),
				'coordinators' => __( 'My Coordinator Roles', 'owbn-client' ),
				'exec'        => __( 'Executive Roles', 'owbn-client' ),
			),
		) );

		$this->add_control( 'section_title', array(
			'label'       => __( 'Section Title', 'owbn-client' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'placeholder' => __( 'Auto (based on section)', 'owbn-client' ),
			'description' => __( 'Leave blank to use the default title for the section.', 'owbn-client' ),
		) );

		$this->add_control( 'show_inbox_count', array(
			'label'     => __( 'Show Inbox Count', 'owbn-client' ),
			'type'      => Controls_Manager::SWITCHER,
			'default'   => 'yes',
			'condition' => array( 'section' => 'self' ),
		) );

		$this->add_control( 'council_base_url', array(
			'label'   => __( 'Council Base URL', 'owbn-client' ),
			'type'    => Controls_Manager::TEXT,
			'default' => 'https://council.owbn.net',
		) );

		$this->add_control( 'chronicles_base_url', array(
			'label'   => __( 'Chronicles Base URL', 'owbn-client' ),
			'type'    => Controls_Manager::TEXT,
			'default' => 'https://chronicles.owbn.net',
		) );

		$this->add_control( 'archivist_base_url', array(
			'label'   => __( 'Archivist Base URL', 'owbn-client' ),
			'type'    => Controls_Manager::TEXT,
			'default' => 'https://archivist.owbn.net',
		) );

		$this->add_control( 'players_base_url', array(
			'label'   => __( 'Players Base URL', 'owbn-client' ),
			'type'    => Controls_Manager::TEXT,
			'default' => 'https://players.owbn.net',
		) );

		$this->end_controls_section();

		// Custom cards.
		$this->start_controls_section( 'custom_cards_section', array(
			'label' => __( 'Custom Cards', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$repeater = new \Elementor\Repeater();

		$repeater->add_control( 'card_title', array(
			'label'       => __( 'Card Name', 'owbn-client' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'Resources',
			'description' => __( 'Links with the same card name are grouped together.', 'owbn-client' ),
		) );

		$repeater->add_control( 'link_label', array(
			'label' => __( 'Link Label', 'owbn-client' ),
			'type'  => Controls_Manager::TEXT,
		) );

		$repeater->add_control( 'link_url', array(
			'label'         => __( 'Link URL', 'owbn-client' ),
			'type'          => Controls_Manager::URL,
			'default'       => array( 'url' => '', 'is_external' => true, 'nofollow' => false ),
			'show_external' => true,
		) );

		$repeater->add_control( 'link_sso', array(
			'label'   => __( 'Route through SSO', 'owbn-client' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		) );

		$repeater->add_control( 'card_visibility', array(
			'label'   => __( 'Show to', 'owbn-client' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'everyone',
			'options' => array(
				'everyone'    => __( 'Everyone (logged in)', 'owbn-client' ),
				'chronicle'   => __( 'Chronicle staff only', 'owbn-client' ),
				'coordinator' => __( 'Coordinators only', 'owbn-client' ),
				'exec'        => __( 'Exec only', 'owbn-client' ),
			),
		) );

		$this->add_control( 'custom_links', array(
			'label'       => __( 'Custom Cards', 'owbn-client' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'title_field' => '{{{ card_title }}} — {{{ link_label }}}',
		) );

		$this->end_controls_section();

		// Style.
		$this->start_controls_section( 'style_section', array(
			'label' => __( 'Style', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'section_heading_color', array(
			'label'     => __( 'Heading Color', 'owbn-client' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .owc-ws-section h3' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	/**
	 * Get parsed role data for current user. Cached per request.
	 */
	private function get_role_data() {
		static $cached = null;
		if ( $cached !== null ) return $cached;

		$user  = wp_get_current_user();
		$roles = array();
		if ( function_exists( 'owc_asc_get_user_roles' ) ) {
			$asc = owc_asc_get_user_roles( 'oat', $user->user_email );
			if ( ! is_wp_error( $asc ) && isset( $asc['roles'] ) && is_array( $asc['roles'] ) ) {
				$roles = $asc['roles'];
			}
		}

		$chronicle_roles = array();
		$coord_roles     = array();
		$exec_roles      = array();
		foreach ( $roles as $role ) {
			if ( preg_match( '#^chronicle/([^/]+)/(hst|cm|staff)$#i', $role, $m ) ) {
				$chronicle_roles[ strtolower( $m[1] ) ][] = strtolower( $m[2] );
			} elseif ( preg_match( '#^coordinator/([^/]+)/(coordinator|sub-coordinator)$#i', $role, $m ) ) {
				$genre = strtolower( $m[1] );
				$level = strtolower( $m[2] );
				if ( ! isset( $coord_roles[ $genre ] ) || $level === 'coordinator' ) {
					$coord_roles[ $genre ] = $level;
				}
			} elseif ( preg_match( '#^exec/([^/]+)/(coordinator|staff|sub-coordinator)$#i', $role, $m ) ) {
				$office = strtolower( $m[1] );
				$level  = strtolower( $m[2] );
				if ( ! isset( $exec_roles[ $office ] ) || $level === 'coordinator' ) {
					$exec_roles[ $office ] = $level;
				}
			}
		}

		// Resolve titles and post IDs.
		$chron_titles = $chron_ids = array();
		if ( function_exists( 'owc_get_chronicles' ) && ! empty( $chronicle_roles ) ) {
			$all = owc_get_chronicles();
			if ( ! is_wp_error( $all ) ) {
				foreach ( $all as $c ) {
					$c = (array) $c;
					if ( isset( $chronicle_roles[ $c['slug'] ] ) ) {
						$chron_titles[ $c['slug'] ] = $c['title'] ?? ucfirst( $c['slug'] );
						$chron_ids[ $c['slug'] ]    = $c['id'] ?? 0;
					}
				}
			}
		}

		$coord_titles = $coord_ids = array();
		if ( function_exists( 'owc_get_coordinators' ) && ! empty( $coord_roles ) ) {
			$all = owc_get_coordinators();
			if ( ! is_wp_error( $all ) ) {
				foreach ( $all as $co ) {
					$co = (array) $co;
					if ( isset( $coord_roles[ $co['slug'] ] ) ) {
						$coord_titles[ $co['slug'] ] = $co['title'] ?? ucfirst( $co['slug'] );
						$coord_ids[ $co['slug'] ]    = $co['id'] ?? 0;
					}
				}
			}
		}

		// Resolve exec titles and post IDs from coordinators data (exec offices are coordinator CPTs).
		$exec_titles = $exec_ids = array();
		if ( function_exists( 'owc_get_coordinators' ) && ! empty( $exec_roles ) ) {
			$all = owc_get_coordinators();
			if ( ! is_wp_error( $all ) ) {
				foreach ( $all as $co ) {
					$co = (array) $co;
					if ( isset( $exec_roles[ $co['slug'] ] ) ) {
						$exec_titles[ $co['slug'] ] = $co['title'] ?? ucfirst( $co['slug'] );
						$exec_ids[ $co['slug'] ]    = $co['id'] ?? 0;
					}
				}
			}
		}

		$cached = compact( 'chronicle_roles', 'coord_roles', 'exec_roles', 'chron_titles', 'chron_ids', 'coord_titles', 'coord_ids', 'exec_titles', 'exec_ids' );
		return $cached;
	}

	protected function render() {
		if ( ! is_user_logged_in() ) {
			echo '<p>' . esc_html__( 'Please log in.', 'owbn-client' ) . '</p>';
			return;
		}

		$settings       = $this->get_settings_for_display();
		$section        = $settings['section'] ?? 'self';
		$custom_title   = $settings['section_title'] ?? '';
		$council_url    = rtrim( $settings['council_base_url'] ?? 'https://council.owbn.net', '/' );
		$chronicles_url = rtrim( $settings['chronicles_base_url'] ?? 'https://chronicles.owbn.net', '/' );
		$archivist_url  = rtrim( $settings['archivist_base_url'] ?? 'https://archivist.owbn.net', '/' );
		$players_url    = rtrim( $settings['players_base_url'] ?? 'https://players.owbn.net', '/' );
		$show_inbox     = ( $settings['show_inbox_count'] ?? 'yes' ) === 'yes';

		$sso_link = function( $base_url, $path = '/' ) {
			return $base_url . '/?auth=sso&redirect_uri=' . rawurlencode( '/' . ltrim( $path, '/' ) );
		};

		$data = $this->get_role_data();
		extract( $data ); // chronicle_roles, coord_roles, exec_roles, chron_titles, chron_ids, coord_titles, coord_ids

		// Custom cards for this section.
		$custom_links = $settings['custom_links'] ?? array();
		$custom_cards = array();
		foreach ( $custom_links as $item ) {
			$card_name  = $item['card_title'] ?? '';
			$visibility = $item['card_visibility'] ?? 'everyone';
			if ( ! $card_name ) continue;
			if ( 'chronicle' === $visibility && empty( $chronicle_roles ) ) continue;
			if ( 'coordinator' === $visibility && empty( $coord_roles ) ) continue;
			if ( 'exec' === $visibility && empty( $exec_roles ) ) continue;
			$custom_cards[ $card_name ][] = $item;
		}

		$render_custom = function() use ( $custom_cards, $sso_link ) {
			foreach ( $custom_cards as $card_name => $items ) {
				echo '<div class="owc-ws-card"><h4>' . esc_html( $card_name ) . '</h4><ul class="owc-ws-links">';
				foreach ( $items as $item ) {
					$label = $item['link_label'] ?? '';
					$url_data = $item['link_url'] ?? array();
					$raw_url = $url_data['url'] ?? '';
					if ( ! $label || ! $raw_url ) continue;
					$href = $raw_url;
					if ( ( $item['link_sso'] ?? 'yes' ) === 'yes' ) {
						$p = wp_parse_url( $raw_url );
						$href = $sso_link( ( $p['scheme'] ?? 'https' ) . '://' . ( $p['host'] ?? '' ), ( $p['path'] ?? '/' ) . ( ! empty( $p['query'] ) ? '?' . $p['query'] : '' ) );
					}
					$a = ! empty( $url_data['is_external'] ) ? ' target="_blank"' : '';
					$a .= ! empty( $url_data['nofollow'] ) ? ' rel="nofollow"' : '';
					echo '<li><a href="' . esc_url( $href ) . '"' . $a . '>' . esc_html( $label ) . '</a></li>';
				}
				echo '</ul></div>';
			}
		};

		// Default titles per section.
		$default_titles = array(
			'sites'        => 'OWBN Sites',
			'self'         => 'My Stuff',
			'chronicles'   => 'My Chronicles',
			'coordinators' => 'My Coordinator Roles',
			'exec'         => 'Executive Roles',
		);
		$heading = $custom_title ?: ( $default_titles[ $section ] ?? '' );

		?>
		<style>
			.owc-ws-section { margin-bottom: 24px; }
			.owc-ws-section h3 { font-size: 1.2em; margin: 0 0 12px; padding-bottom: 6px; border-bottom: 2px solid rgba(128,128,128,0.3); }
			.owc-ws-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
			.owc-ws-card { border: 1px solid rgba(128,128,128,0.3); border-radius: 6px; padding: 16px; background: rgba(128,128,128,0.08); transition: box-shadow 0.15s; }
			.owc-ws-card:hover { box-shadow: 0 2px 8px rgba(128,128,128,0.2); }
			.owc-ws-card h4 { margin: 0 0 8px; font-size: 1em; }
			.owc-ws-card .owc-ws-links { list-style: none; margin: 0; padding: 0; }
			.owc-ws-card .owc-ws-links li { margin: 4px 0; }
			.owc-ws-card .owc-ws-links a { text-decoration: none; color: var(--e-global-color-accent, #EA5B3A); }
			.owc-ws-card .owc-ws-links a:hover { text-decoration: underline; }
			.owc-ws-connect-btn { display: inline-block; padding: 8px 16px; background: var(--e-global-color-accent, #EA5B3A); color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.9em; }
			.owc-ws-connect-btn:hover { opacity: 0.85; color: #fff; }
			.owc-ws-badge { display: inline-block; background: #d63638; color: #fff; border-radius: 10px; padding: 1px 8px; font-size: 0.85em; margin-left: 6px; }
			.owc-ws-role-tag { display: inline-block; background: rgba(128,128,128,0.2); border-radius: 3px; padding: 1px 6px; font-size: 0.8em; opacity: 0.7; margin-left: 4px; }
		</style>

		<?php if ( 'sites' === $section ) : ?>
			<div class="owc-ws-section">
				<?php if ( $heading ) : ?><h3><?php echo esc_html( $heading ); ?></h3><?php endif; ?>
				<div style="display:flex;gap:10px;flex-wrap:wrap;">
					<a href="<?php echo esc_url( $players_url . '/?auth=sso' ); ?>" target="_blank" class="owc-ws-connect-btn">Players</a>
					<a href="<?php echo esc_url( $chronicles_url . '/?auth=sso' ); ?>" target="_blank" class="owc-ws-connect-btn">Chronicles</a>
					<a href="<?php echo esc_url( $council_url . '/?auth=sso' ); ?>" target="_blank" class="owc-ws-connect-btn">Council</a>
					<a href="<?php echo esc_url( $archivist_url . '/?auth=sso' ); ?>" target="_blank" class="owc-ws-connect-btn">Archivist</a>
				</div>
				<?php if ( ! empty( $custom_cards ) ) : ?>
					<div class="owc-ws-grid" style="margin-top:12px;"><?php $render_custom(); ?></div>
				<?php endif; ?>
			</div>

		<?php elseif ( 'self' === $section ) :
			$inbox_count = 0;
			if ( $show_inbox && function_exists( 'owc_oat_get_dashboard_counts' ) ) {
				$counts = owc_oat_get_dashboard_counts( get_current_user_id() );
				if ( ! is_wp_error( $counts ) ) {
					$inbox_count = ( $counts['assignments'] ?? 0 ) + ( $counts['watched'] ?? 0 );
				}
			}
		?>
			<div class="owc-ws-section">
				<?php if ( $heading ) : ?><h3><?php echo esc_html( $heading ); ?></h3><?php endif; ?>
				<div class="owc-ws-grid">
					<div class="owc-ws-card">
						<h4>Archivist Dashboard<?php if ( $inbox_count > 0 ) : ?><span class="owc-ws-badge"><?php echo (int) $inbox_count; ?> pending</span><?php endif; ?></h4>
						<ul class="owc-ws-links">
							<li><a href="<?php echo esc_url( $sso_link( $archivist_url, 'oat-dashboard/' ) ); ?>" target="_blank">My Characters, Inbox &amp; Submissions</a></li>
						</ul>
					</div>
					<?php $render_custom(); ?>
				</div>
			</div>

		<?php elseif ( 'chronicles' === $section && ! empty( $chronicle_roles ) ) : ?>
			<div class="owc-ws-section">
				<?php if ( $heading ) : ?><h3><?php echo esc_html( $heading ); ?></h3><?php endif; ?>
				<div class="owc-ws-grid">
					<?php foreach ( $chronicle_roles as $slug => $types ) :
						$title = $chron_titles[ $slug ] ?? strtoupper( $slug );
						$is_cm    = in_array( 'cm', $types, true );
						$is_hst   = in_array( 'hst', $types, true );
						$is_staff = in_array( 'staff', $types, true );
						$can_edit = $is_hst || $is_cm || $is_staff;
						$chron_post_id = $chron_ids[ $slug ] ?? 0;
					?>
					<div class="owc-ws-card">
						<h4><?php echo esc_html( $title ); ?>
							<?php foreach ( array_map( 'strtoupper', $types ) as $rl ) : ?>
								<span class="owc-ws-role-tag"><?php echo esc_html( $rl ); ?></span>
							<?php endforeach; ?>
						</h4>
						<ul class="owc-ws-links">
							<li><a href="<?php echo esc_url( $sso_link( $chronicles_url, 'chronicle-detail/?slug=' . $slug ) ); ?>" target="_blank">View Chronicle</a></li>
							<?php if ( $can_edit && $chron_post_id ) : ?>
								<li><a href="<?php echo esc_url( $sso_link( $chronicles_url, 'wp-admin/post.php?post=' . $chron_post_id . '&action=edit' ) ); ?>" target="_blank">Edit Chronicle</a></li>
							<?php endif; ?>
							<?php if ( $can_edit ) : ?>
								<li><a href="<?php echo esc_url( $sso_link( $archivist_url, 'oat-dashboard/' ) ); ?>" target="_blank">Archivist Dashboard</a></li>
								<li><a href="<?php echo esc_url( $sso_link( $council_url, 'voting-dashboard/' ) ); ?>" target="_blank">Council Votes</a></li>
							<?php endif; ?>
						</ul>
					</div>
					<?php endforeach; ?>
					<?php $render_custom(); ?>
				</div>
			</div>

		<?php elseif ( 'coordinators' === $section && ! empty( $coord_roles ) ) : ?>
			<div class="owc-ws-section">
				<?php if ( $heading ) : ?><h3><?php echo esc_html( $heading ); ?></h3><?php endif; ?>
				<div class="owc-ws-grid">
					<?php foreach ( $coord_roles as $genre => $level ) :
						$title = $coord_titles[ $genre ] ?? ucfirst( $genre );
						$level_label = ( $level === 'coordinator' ) ? 'Coordinator' : 'Sub-Coordinator';
						$coord_post_id = $coord_ids[ $genre ] ?? 0;
					?>
					<div class="owc-ws-card">
						<h4><?php echo esc_html( $title ); ?>
							<span class="owc-ws-role-tag"><?php echo esc_html( $level_label ); ?></span>
						</h4>
						<ul class="owc-ws-links">
							<li><a href="<?php echo esc_url( $sso_link( $council_url, 'coordinator-detail/?slug=' . $genre ) ); ?>" target="_blank">View Coordinator Page</a></li>
							<?php if ( $coord_post_id ) : ?>
								<li><a href="<?php echo esc_url( $sso_link( $council_url, 'wp-admin/post.php?post=' . $coord_post_id . '&action=edit' ) ); ?>" target="_blank">Edit Coordinator Page</a></li>
							<?php endif; ?>
							<li><a href="<?php echo esc_url( $sso_link( $archivist_url, 'oat-dashboard/' ) ); ?>" target="_blank">Archivist Dashboard</a></li>
							<?php if ( $level === 'coordinator' ) : ?>
								<li><a href="<?php echo esc_url( $sso_link( $council_url, 'voting-dashboard/' ) ); ?>" target="_blank">Council Votes</a></li>
							<?php endif; ?>
						</ul>
					</div>
					<?php endforeach; ?>
					<?php $render_custom(); ?>
				</div>
			</div>

		<?php elseif ( 'exec' === $section && ! empty( $exec_roles ) ) : ?>
			<div class="owc-ws-section">
				<?php if ( $heading ) : ?><h3><?php echo esc_html( $heading ); ?></h3><?php endif; ?>
				<div class="owc-ws-grid">
					<?php foreach ( $exec_roles as $office => $level ) :
						$exec_title   = $exec_titles[ $office ] ?? ucfirst( str_replace( '-', ' ', $office ) );
						$exec_post_id = $exec_ids[ $office ] ?? 0;
						$level_label  = ( $level === 'coordinator' ) ? 'Coordinator' : ucfirst( $level );
					?>
					<div class="owc-ws-card">
						<h4><?php echo esc_html( $exec_title ); ?>
							<span class="owc-ws-role-tag"><?php echo esc_html( $level_label ); ?></span>
						</h4>
						<ul class="owc-ws-links">
							<li><a href="<?php echo esc_url( $sso_link( $council_url, 'coordinator-detail/?slug=' . $office ) ); ?>" target="_blank">View Page</a></li>
							<?php if ( $exec_post_id ) : ?>
								<li><a href="<?php echo esc_url( $sso_link( $council_url, 'wp-admin/post.php?post=' . $exec_post_id . '&action=edit' ) ); ?>" target="_blank">Edit Page</a></li>
							<?php endif; ?>
							<li><a href="<?php echo esc_url( $sso_link( $archivist_url, 'wp-admin/' ) ); ?>" target="_blank">Archivist Admin</a></li>
							<li><a href="<?php echo esc_url( $sso_link( $council_url, 'voting-dashboard/' ) ); ?>" target="_blank">Council Votes</a></li>
						</ul>
					</div>
					<?php endforeach; ?>
					<?php $render_custom(); ?>
				</div>
			</div>
		<?php endif; ?>
		<?php
	}
}
