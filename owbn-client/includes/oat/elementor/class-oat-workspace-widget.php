<?php

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * OAT Workspace Widget — personalized landing page.
 *
 * Reads user's ASC roles and renders a top-down dashboard:
 *   1. Self: My Characters, Inbox, Submit, Recent Activity
 *   2. Chronicle roles: per-chronicle links (manage, vote if CM)
 *   3. Coordinator roles: per-genre coordinator links
 */
class OWC_OAT_Workspace_Widget extends Widget_Base {

	public function get_name() {
		return 'owc_oat_workspace';
	}

	public function get_title() {
		return __( 'OAT Workspace', 'owbn-client' );
	}

	public function get_icon() {
		return 'eicon-apps';
	}

	public function get_categories() {
		return array( 'owbn-oat' );
	}

	public function get_keywords() {
		return array( 'oat', 'workspace', 'dashboard', 'roles', 'landing' );
	}

	public function get_style_depends() {
		return array( 'owc-oat-client', 'owc-oat-frontend' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'content_section', array(
			'label' => __( 'Settings', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'show_characters', array(
			'label'   => __( 'Show My Characters', 'owbn-client' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		) );

		$this->add_control( 'show_inbox_count', array(
			'label'   => __( 'Show Inbox Count', 'owbn-client' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
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

		$this->start_controls_section( 'style_section', array(
			'label' => __( 'Style', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'section_heading_color', array(
			'label'     => __( 'Section Heading Color', 'owbn-client' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .owc-ws-section h3' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'link_color', array(
			'label'     => __( 'Link Color', 'owbn-client' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .owc-ws-link a' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		if ( ! is_user_logged_in() ) {
			echo '<p>' . esc_html__( 'Please log in to view your workspace.', 'owbn-client' ) . '</p>';
			return;
		}

		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<div style="padding:20px;border:1px dashed #ccc;text-align:center;color:#646970;">'
				. esc_html__( 'OAT Workspace — preview not available in editor.', 'owbn-client' )
				. '</div>';
			return;
		}

		$settings      = $this->get_settings_for_display();
		$council_url   = rtrim( $settings['council_base_url'] ?? 'https://council.owbn.net', '/' );
		$chronicles_url = rtrim( $settings['chronicles_base_url'] ?? 'https://chronicles.owbn.net', '/' );
		$archivist_url = rtrim( $settings['archivist_base_url'] ?? 'https://archivist.owbn.net', '/' );
		$players_url   = rtrim( $settings['players_base_url'] ?? 'https://players.owbn.net', '/' );
		$show_chars    = ( $settings['show_characters'] ?? 'yes' ) === 'yes';
		$show_inbox    = ( $settings['show_inbox_count'] ?? 'yes' ) === 'yes';

		$user = wp_get_current_user();

		// Helper: build SSO-aware URL. Passes through ?auth=sso with redirect_uri to destination.
		$sso_link = function( $base_url, $path = '/' ) {
			$path = ltrim( $path, '/' );
			return $base_url . '/?auth=sso&redirect_uri=' . rawurlencode( '/' . $path );
		};

		// Get ASC roles.
		$roles = array();
		if ( function_exists( 'owc_asc_get_user_roles' ) ) {
			$asc = owc_asc_get_user_roles( 'oat', $user->user_email );
			if ( ! is_wp_error( $asc ) && isset( $asc['roles'] ) && is_array( $asc['roles'] ) ) {
				$roles = $asc['roles'];
			}
		}

		// Parse roles into structured data.
		$chronicle_roles = array(); // slug => array of role types (hst, cm, staff, ast)
		$coord_roles     = array(); // genre => role (coordinator, sub-coordinator)
		$exec_roles      = array(); // office => role
		foreach ( $roles as $role ) {
			if ( preg_match( '#^chronicle/([^/]+)/(hst|cm|staff|ast)$#i', $role, $m ) ) {
				$slug = strtolower( $m[1] );
				$type = strtolower( $m[2] );
				if ( ! isset( $chronicle_roles[ $slug ] ) ) {
					$chronicle_roles[ $slug ] = array();
				}
				$chronicle_roles[ $slug ][] = $type;
			} elseif ( preg_match( '#^coordinator/([^/]+)/(coordinator|sub-coordinator)$#i', $role, $m ) ) {
				$genre = strtolower( $m[1] );
				$level = strtolower( $m[2] );
				if ( ! isset( $coord_roles[ $genre ] ) || $level === 'coordinator' ) {
					$coord_roles[ $genre ] = $level;
				}
			} elseif ( preg_match( '#^exec/([^/]+)/coordinator$#i', $role, $m ) ) {
				$exec_roles[ strtolower( $m[1] ) ] = 'coordinator';
			}
		}

		// Resolve titles.
		$chron_titles = array();
		if ( function_exists( 'owc_get_chronicles' ) && ! empty( $chronicle_roles ) ) {
			$all_chrons = owc_get_chronicles();
			if ( ! is_wp_error( $all_chrons ) ) {
				foreach ( $all_chrons as $c ) {
					$c = (array) $c;
					if ( isset( $chronicle_roles[ $c['slug'] ] ) ) {
						$chron_titles[ $c['slug'] ] = $c['title'] ?? ucfirst( $c['slug'] );
					}
				}
			}
		}

		$coord_titles = array();
		if ( function_exists( 'owc_get_coordinators' ) && ! empty( $coord_roles ) ) {
			$all_coords = owc_get_coordinators();
			if ( ! is_wp_error( $all_coords ) ) {
				foreach ( $all_coords as $co ) {
					$co = (array) $co;
					if ( isset( $coord_roles[ $co['slug'] ] ) ) {
						$coord_titles[ $co['slug'] ] = $co['title'] ?? ucfirst( $co['slug'] );
					}
				}
			}
		}

		// Get inbox counts if requested.
		$inbox_count = 0;
		if ( $show_inbox && function_exists( 'owc_oat_get_dashboard_counts' ) ) {
			$counts = owc_oat_get_dashboard_counts( $user->ID );
			if ( ! is_wp_error( $counts ) ) {
				$inbox_count = ( $counts['assignments'] ?? 0 ) + ( $counts['watched'] ?? 0 );
			}
		}

		// Player ID.
		$pid_key   = defined( 'PID_META_KEY' ) ? PID_META_KEY : 'player_id';
		$player_id = get_user_meta( $user->ID, $pid_key, true );

		?>
		<div class="owc-workspace">
			<style>
				.owc-workspace { font-family: inherit; }
				.owc-ws-section { margin-bottom: 24px; }
				.owc-ws-section h3 { font-size: 1.2em; margin: 0 0 12px; padding-bottom: 6px; border-bottom: 2px solid #ddd; }
				.owc-ws-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
				.owc-ws-card { border: 1px solid #ddd; border-radius: 6px; padding: 16px; background: #fafafa; transition: box-shadow 0.15s; }
				.owc-ws-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
				.owc-ws-card h4 { margin: 0 0 8px; font-size: 1em; }
				.owc-ws-card .owc-ws-links { list-style: none; margin: 0; padding: 0; }
				.owc-ws-card .owc-ws-links li { margin: 4px 0; }
				.owc-ws-card .owc-ws-links a { text-decoration: none; color: #0073aa; }
				.owc-ws-card .owc-ws-links a:hover { text-decoration: underline; }
				.owc-ws-connect-btn { display: inline-block; padding: 8px 16px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.9em; }
				.owc-ws-connect-btn:hover { background: #005a87; color: #fff; }
				.owc-ws-badge { display: inline-block; background: #d63638; color: #fff; border-radius: 10px; padding: 1px 8px; font-size: 0.85em; margin-left: 6px; }
				.owc-ws-role-tag { display: inline-block; background: #e0e0e0; border-radius: 3px; padding: 1px 6px; font-size: 0.8em; color: #555; margin-left: 4px; }
				.owc-ws-welcome { margin-bottom: 20px; }
				.owc-ws-welcome p { margin: 4px 0; color: #555; }
			</style>

			<!-- ── Welcome ──────────────────────────────────────── -->
			<div class="owc-ws-welcome">
				<h2>Welcome, <?php echo esc_html( $user->display_name ); ?></h2>
				<?php if ( $player_id ) : ?>
					<p>Player ID: <strong><?php echo esc_html( $player_id ); ?></strong></p>
				<?php endif; ?>
			</div>

			<!-- ── Connect ──────────────────────────────────────── -->
			<div class="owc-ws-section">
				<h3>OWBN Sites</h3>
				<div style="display:flex;gap:10px;flex-wrap:wrap;">
					<a href="<?php echo esc_url( $players_url . '/?auth=sso' ); ?>" target="_blank" class="owc-ws-connect-btn">Players</a>
					<a href="<?php echo esc_url( $chronicles_url . '/?auth=sso' ); ?>" target="_blank" class="owc-ws-connect-btn">Chronicles</a>
					<a href="<?php echo esc_url( $council_url . '/?auth=sso' ); ?>" target="_blank" class="owc-ws-connect-btn">Council</a>
					<a href="<?php echo esc_url( $archivist_url . '/?auth=sso' ); ?>" target="_blank" class="owc-ws-connect-btn">Archivist</a>
				</div>
			</div>

			<!-- ── 1. Self ──────────────────────────────────────── -->
			<div class="owc-ws-section">
				<h3>My Stuff</h3>
				<div class="owc-ws-grid">
					<div class="owc-ws-card">
						<h4>OAT Dashboard<?php if ( $inbox_count > 0 ) : ?><span class="owc-ws-badge"><?php echo (int) $inbox_count; ?> pending</span><?php endif; ?></h4>
						<ul class="owc-ws-links">
							<li><a href="<?php echo esc_url( $sso_link( $archivist_url, 'oat-dashboard/' ) ); ?>">My Characters, Inbox &amp; Submissions</a></li>
						</ul>
					</div>
				</div>
			</div>

			<?php if ( ! empty( $chronicle_roles ) ) : ?>
			<!-- ── 2. Chronicle Roles ───────────────────────────── -->
			<div class="owc-ws-section">
				<h3>My Chronicles</h3>
				<div class="owc-ws-grid">
					<?php foreach ( $chronicle_roles as $slug => $types ) :
						$title = $chron_titles[ $slug ] ?? strtoupper( $slug );
						$is_cm  = in_array( 'cm', $types, true );
						$is_hst = in_array( 'hst', $types, true );
						$role_labels = array_map( 'strtoupper', $types );
					?>
					<div class="owc-ws-card">
						<h4><?php echo esc_html( $title ); ?>
							<?php foreach ( $role_labels as $rl ) : ?>
								<span class="owc-ws-role-tag"><?php echo esc_html( $rl ); ?></span>
							<?php endforeach; ?>
						</h4>
						<ul class="owc-ws-links">
							<li><a href="<?php echo esc_url( $sso_link( $chronicles_url, 'chronicle-detail/?slug=' . $slug ) ); ?>">View Chronicle</a></li>
							<?php if ( $is_hst ) : ?>
								<li><a href="<?php echo esc_url( $sso_link( $chronicles_url, 'wp-admin/edit.php?post_type=owbn_chronicle&s=' . $slug ) ); ?>">Edit Chronicle</a></li>
							<?php endif; ?>
							<?php if ( $is_hst || $is_cm ) : ?>
								<li><a href="<?php echo esc_url( $sso_link( $archivist_url, 'oat-dashboard/' ) ); ?>">OAT Dashboard</a></li>
							<?php endif; ?>
							<?php if ( $is_cm ) : ?>
								<li><a href="<?php echo esc_url( $sso_link( $council_url, 'voting-dashboard/' ) ); ?>">Council Votes</a></li>
							<?php endif; ?>
						</ul>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $coord_roles ) ) : ?>
			<!-- ── 3. Coordinator Roles ─────────────────────────── -->
			<div class="owc-ws-section">
				<h3>My Coordinator Roles</h3>
				<div class="owc-ws-grid">
					<?php foreach ( $coord_roles as $genre => $level ) :
						$title = $coord_titles[ $genre ] ?? ucfirst( $genre );
						$level_label = ( $level === 'coordinator' ) ? 'Coordinator' : 'Sub-Coordinator';
					?>
					<div class="owc-ws-card">
						<h4><?php echo esc_html( $title ); ?>
							<span class="owc-ws-role-tag"><?php echo esc_html( $level_label ); ?></span>
						</h4>
						<ul class="owc-ws-links">
							<li><a href="<?php echo esc_url( $sso_link( $council_url, 'coordinator-detail/?slug=' . $genre ) ); ?>">View Coordinator Page</a></li>
							<li><a href="<?php echo esc_url( $sso_link( $council_url, 'wp-admin/edit.php?post_type=owbn_coordinator&s=' . $genre ) ); ?>">Edit Coordinator Page</a></li>
							<li><a href="<?php echo esc_url( $sso_link( $archivist_url, 'oat-dashboard/' ) ); ?>">OAT Dashboard</a></li>
							<?php if ( $level === 'coordinator' ) : ?>
								<li><a href="<?php echo esc_url( $sso_link( $council_url, 'voting-dashboard/' ) ); ?>">Council Votes</a></li>
							<?php endif; ?>
						</ul>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $exec_roles ) ) : ?>
			<!-- ── 4. Exec Roles ────────────────────────────────── -->
			<div class="owc-ws-section">
				<h3>Executive Roles</h3>
				<div class="owc-ws-grid">
					<?php foreach ( $exec_roles as $office => $level ) :
						$label = ucfirst( str_replace( '-', ' ', $office ) );
					?>
					<div class="owc-ws-card">
						<h4><?php echo esc_html( $label ); ?>
							<span class="owc-ws-role-tag">Exec</span>
						</h4>
						<ul class="owc-ws-links">
							<li><a href="<?php echo esc_url( $sso_link( $archivist_url, 'wp-admin/' ) ); ?>">Archivist Admin</a></li>
							<li><a href="<?php echo esc_url( $sso_link( $council_url, 'voting-dashboard/' ) ); ?>">Council Votes</a></li>
						</ul>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

		</div>
		<?php
	}
}
