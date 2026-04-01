<?php

/**
 * OAT Reports Page
 *
 * Admin-side reporting dashboard. Restricted to:
 *   chronicle/<slug>/hst|cm|staff
 *   coordinator/<slug>/coordinator|sub-coordinator
 *   exec/<slug>/coordinator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check if current user has report access via ASC roles.
 *
 * @return bool
 */
function owc_oat_reports_has_access() {
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }
    if ( ! function_exists( 'owc_oat_get_user_asc_roles' ) ) {
        return false;
    }
    $roles = owc_oat_get_user_asc_roles( get_current_user_id() );
    foreach ( $roles as $role ) {
        if ( preg_match( '#^chronicle/[^/]+/(hst|cm|staff)$#', $role ) ) {
            return true;
        }
        if ( preg_match( '#^coordinator/[^/]+/(coordinator|sub-coordinator)$#', $role ) ) {
            return true;
        }
        if ( preg_match( '#^exec/[^/]+/coordinator$#', $role ) ) {
            return true;
        }
    }
    return false;
}

/**
 * Get the user's scoped filters based on their ASC roles.
 *
 * Returns arrays of chronicle slugs and coordinator genres
 * the user has access to.  WP Admin / exec/archivist sees all.
 *
 * @return array { 'chronicles' => string[], 'genres' => string[], 'is_global' => bool }
 */
function owc_oat_reports_user_scope() {
    if ( current_user_can( 'manage_options' ) ) {
        return array( 'chronicles' => array(), 'genres' => array(), 'is_global' => true );
    }
    $roles      = function_exists( 'owc_oat_get_user_asc_roles' ) ? owc_oat_get_user_asc_roles( get_current_user_id() ) : array();
    $chronicles = array();
    $genres     = array();
    $is_global  = false;

    foreach ( $roles as $role ) {
        if ( preg_match( '#^exec/archivist/coordinator$#', $role ) ) {
            $is_global = true;
            break;
        }
        if ( preg_match( '#^chronicle/([^/]+)/(hst|cm|staff)$#', $role, $m ) ) {
            $chronicles[] = $m[1];
        }
        if ( preg_match( '#^coordinator/([^/]+)/(coordinator|sub-coordinator)$#', $role, $m ) ) {
            $genres[] = $m[1];
        }
        if ( preg_match( '#^exec/([^/]+)/coordinator$#', $role, $m ) ) {
            $genres[] = $m[1];
        }
    }

    return array(
        'chronicles' => array_unique( $chronicles ),
        'genres'     => array_unique( $genres ),
        'is_global'  => $is_global,
    );
}

/**
 * Render the OAT Reports page.
 */
function owc_oat_page_reports() {
    if ( ! owc_oat_reports_has_access() ) {
        echo '<div class="wrap"><h1>OAT Reports</h1><p>You do not have permission to view reports.</p></div>';
        return;
    }

    $scope   = owc_oat_reports_user_scope();
    $report  = isset( $_GET['report'] ) ? sanitize_text_field( $_GET['report'] ) : 'entries_by_domain';
    $filters = array(
        'chronicle' => isset( $_GET['chronicle'] ) ? sanitize_text_field( $_GET['chronicle'] ) : '',
        'genre'     => isset( $_GET['genre'] ) ? sanitize_text_field( $_GET['genre'] ) : '',
        'status'    => isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '',
        'domain'    => isset( $_GET['domain'] ) ? sanitize_text_field( $_GET['domain'] ) : '',
        'date_from' => isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '',
        'date_to'   => isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '',
    );

    $reports = array(
        'entries_by_domain'       => 'Entries by Domain',
        'entries_by_status'       => 'Entries by Status',
        'entries_by_coordinator'  => 'Entries by Coordinator',
        'custom_content_by_type'  => 'Custom Content by Type',
        'disciplinary_actions'    => 'Disciplinary Actions',
        'da_by_level'             => 'DA by Level',
        'recent_activity'         => 'Recent Activity',
    );

    ?>
    <div class="wrap">
        <h1>OAT Reports</h1>

        <nav class="nav-tab-wrapper">
            <?php foreach ( $reports as $key => $label ) :
                $url    = admin_url( 'admin.php?page=owc-oat-reports&report=' . $key );
                $active = ( $key === $report ) ? ' nav-tab-active' : '';
            ?>
                <a href="<?php echo esc_url( $url ); ?>" class="nav-tab<?php echo $active; ?>">
                    <?php echo esc_html( $label ); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div style="margin-top:15px;">
            <?php owc_oat_render_report( $report, $filters, $scope ); ?>
        </div>
    </div>
    <?php
}

/**
 * Render a specific report.
 */
function owc_oat_render_report( $report, $filters, $scope ) {
    global $wpdb;
    $prefix = $wpdb->prefix;

    // Build scope WHERE clause for characters.
    $char_scope = '';
    if ( ! $scope['is_global'] ) {
        $conditions = array();
        if ( ! empty( $scope['chronicles'] ) ) {
            $slugs = implode( "','", array_map( 'esc_sql', $scope['chronicles'] ) );
            $conditions[] = "c.chronicle_slug IN ('{$slugs}')";
        }
        if ( ! empty( $scope['genres'] ) ) {
            $slugs = implode( "','", array_map( 'esc_sql', $scope['genres'] ) );
            $conditions[] = "LOWER(c.creature_type) IN ('{$slugs}')";
        }
        $char_scope = ! empty( $conditions ) ? ' AND (' . implode( ' OR ', $conditions ) . ')' : ' AND 1=0';
    }

    // Build scope WHERE for entries.
    $entry_scope = '';
    if ( ! $scope['is_global'] ) {
        $conditions = array();
        if ( ! empty( $scope['chronicles'] ) ) {
            $slugs = implode( "','", array_map( 'esc_sql', $scope['chronicles'] ) );
            $conditions[] = "e.chronicle_slug IN ('{$slugs}')";
        }
        if ( ! empty( $scope['genres'] ) ) {
            $slugs = implode( "','", array_map( 'esc_sql', $scope['genres'] ) );
            $conditions[] = "e.coordinator_genre IN ('{$slugs}')";
        }
        $entry_scope = ! empty( $conditions ) ? ' AND (' . implode( ' OR ', $conditions ) . ')' : ' AND 1=0';
    }

    // Optional filters.
    $f_chronicle = '';
    if ( $filters['chronicle'] ) {
        $f_chronicle = $wpdb->prepare( ' AND c.chronicle_slug = %s', $filters['chronicle'] );
    }
    $f_entry_chronicle = '';
    if ( $filters['chronicle'] ) {
        $f_entry_chronicle = $wpdb->prepare( ' AND e.chronicle_slug = %s', $filters['chronicle'] );
    }

    echo '<div style="padding:0;">';

    switch ( $report ) {

        case 'ru_active':
            echo '<h2>R&U Active</h2>';
            echo '<p style="color:#666;font-size:13px;">Active approved character_lifecycle entries grouped by R&U classification, split by PC/NPC.</p>';
            // R&U data lives in item_description meta on approved character_lifecycle entries.
            // Group by the item_description (which is the R&U classification text) and pc_npc.
            $page    = isset( $_GET['rpg'] ) ? max( 0, (int) $_GET['rpg'] ) : 0;
            $per     = 50;
            $offset  = $page * $per;
            $f_genre = '';
            if ( $filters['genre'] ) {
                $f_genre = $wpdb->prepare( ' AND e.coordinator_genre = %s', $filters['genre'] );
            }
            $total_rows = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM (
                    SELECT m.meta_value, c.pc_npc
                    FROM {$prefix}oat_entries e
                    JOIN {$prefix}oat_entry_meta m ON e.id = m.entry_id AND m.meta_key = 'item_description' AND m.meta_value != ''
                    LEFT JOIN {$prefix}oat_characters c ON e.character_id = c.id
                    WHERE e.domain = 'character_lifecycle' AND e.status = 'approved' {$entry_scope} {$f_genre}
                    GROUP BY m.meta_value, IFNULL(c.pc_npc, 'pc')
                ) sub"
            );
            $rows = $wpdb->get_results(
                "SELECT m.meta_value as classification, IFNULL(c.pc_npc, 'pc') as pc_npc, COUNT(*) as cnt
                 FROM {$prefix}oat_entries e
                 JOIN {$prefix}oat_entry_meta m ON e.id = m.entry_id AND m.meta_key = 'item_description' AND m.meta_value != ''
                 LEFT JOIN {$prefix}oat_characters c ON e.character_id = c.id
                 WHERE e.domain = 'character_lifecycle' AND e.status = 'approved' {$entry_scope} {$f_genre}
                 GROUP BY m.meta_value, IFNULL(c.pc_npc, 'pc')
                 ORDER BY m.meta_value ASC, pc_npc ASC
                 LIMIT {$per} OFFSET {$offset}"
            );
            if ( empty( $rows ) ) {
                echo '<p>No R&U data found.</p>';
            } else {
                echo '<input type="text" class="oat-rpt-search" data-table="oat-rpt-ru" placeholder="Filter rows..." style="margin-bottom:8px;width:250px;padding:4px 8px;">';
                echo '<table id="oat-rpt-ru" class="widefat striped oat-rpt-table"><thead><tr>';
                echo '<th data-col="0" style="cursor:pointer;">R&U Classification <span style="color:#999;font-size:10px;">&#x25B4;&#x25BE;</span></th>';
                echo '<th data-col="1" style="text-align:right;cursor:pointer;">Total <span style="color:#999;font-size:10px;">&#x25B4;&#x25BE;</span></th>';
                echo '<th data-col="2" style="cursor:pointer;">PC or NPC <span style="color:#999;font-size:10px;">&#x25B4;&#x25BE;</span></th>';
                echo '</tr></thead><tbody>';
                foreach ( $rows as $r ) {
                    echo '<tr>';
                    echo '<td>' . esc_html( $r->classification ) . '</td>';
                    echo '<td style="text-align:right;">' . number_format( $r->cnt ) . '</td>';
                    echo '<td>' . esc_html( strtoupper( $r->pc_npc ) ) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                $base = admin_url( 'admin.php?page=owc-oat-reports&report=ru_active' );
                if ( $filters['genre'] ) {
                    $base .= '&genre=' . urlencode( $filters['genre'] );
                }
                owc_oat_render_pagination( $total_rows, $per, $page, $base );
            }
            break;

        case 'ru_by_classification':
            echo '<h2>R&U by Classification (Summary)</h2>';
            echo '<p style="color:#666;font-size:13px;">Top-level R&U classification categories with total counts.</p>';
            // Extract the part before the first colon as the category.
            $rows = $wpdb->get_results(
                "SELECT
                    CASE
                        WHEN m.meta_value LIKE 'Custom %' THEN 'Custom Content'
                        WHEN m.meta_value LIKE '%:%' THEN TRIM(SUBSTRING_INDEX(m.meta_value, ':', 1))
                        ELSE m.meta_value
                    END as category,
                    COUNT(*) as cnt
                 FROM {$prefix}oat_entries e
                 JOIN {$prefix}oat_entry_meta m ON e.id = m.entry_id AND m.meta_key = 'item_description' AND m.meta_value != ''
                 WHERE e.domain = 'character_lifecycle' AND e.status = 'approved' {$entry_scope}
                 GROUP BY category
                 ORDER BY cnt DESC"
            );
            owc_oat_report_table( array( 'Classification', 'Count' ), $rows, function( $r ) {
                return array( $r->category, number_format( $r->cnt ) );
            } );
            $total = array_sum( wp_list_pluck( $rows, 'cnt' ) );
            echo '<p style="color:#666;margin-top:8px;"><strong>Total: ' . number_format( $total ) . '</strong></p>';
            break;

        case 'characters_by_status':
            echo '<h2>Characters by Status</h2>';
            $rows = $wpdb->get_results(
                "SELECT c.status, COUNT(*) as cnt
                 FROM {$prefix}oat_characters c
                 WHERE 1=1 {$char_scope} {$f_chronicle}
                 GROUP BY c.status ORDER BY cnt DESC"
            );
            owc_oat_report_table( array( 'Status', 'Count' ), $rows, function( $r ) {
                return array( ucfirst( $r->status ?: 'unknown' ), number_format( $r->cnt ) );
            } );
            $total = array_sum( wp_list_pluck( $rows, 'cnt' ) );
            echo '<p style="color:#666;margin-top:8px;"><strong>Total: ' . number_format( $total ) . '</strong></p>';
            break;

        case 'characters_by_chronicle':
            echo '<h2>Characters by Chronicle</h2>';
            $rows = $wpdb->get_results(
                "SELECT c.chronicle_slug, c.status, COUNT(*) as cnt
                 FROM {$prefix}oat_characters c
                 WHERE c.chronicle_slug != '' {$char_scope}
                 GROUP BY c.chronicle_slug, c.status
                 ORDER BY c.chronicle_slug, c.status"
            );
            // Pivot: chronicle → status → count.
            $pivoted = array();
            $statuses = array();
            foreach ( $rows as $r ) {
                $pivoted[ $r->chronicle_slug ][ $r->status ] = (int) $r->cnt;
                $statuses[ $r->status ] = true;
            }
            $status_keys = array_keys( $statuses );
            sort( $status_keys );
            $headers = array_merge( array( 'Chronicle' ), array_map( 'ucfirst', $status_keys ), array( 'Total' ) );
            echo '<table class="widefat striped"><thead><tr>';
            foreach ( $headers as $i => $h ) {
                $align = ( $i > 0 ) ? ' style="text-align:right;"' : '';
                echo '<th' . $align . '>' . esc_html( $h ) . '</th>';
            }
            echo '</tr></thead><tbody>';
            foreach ( $pivoted as $slug => $counts ) {
                $title = function_exists( 'owc_entity_get_title' ) ? owc_entity_get_title( 'chronicle', $slug ) : $slug;
                echo '<tr><td>' . esc_html( $title ?: $slug ) . '</td>';
                $row_total = 0;
                foreach ( $status_keys as $s ) {
                    $v = isset( $counts[ $s ] ) ? $counts[ $s ] : 0;
                    $row_total += $v;
                    echo '<td style="text-align:right;">' . number_format( $v ) . '</td>';
                }
                echo '<td style="text-align:right;font-weight:bold;">' . number_format( $row_total ) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            break;

        case 'characters_by_creature':
            echo '<h2>Characters by Creature Type</h2>';
            $rows = $wpdb->get_results(
                "SELECT IFNULL(NULLIF(c.creature_type,''), 'Unknown') as creature, COUNT(*) as cnt
                 FROM {$prefix}oat_characters c
                 WHERE 1=1 {$char_scope} {$f_chronicle}
                 GROUP BY creature ORDER BY cnt DESC"
            );
            owc_oat_report_table( array( 'Creature Type', 'Count' ), $rows, function( $r ) {
                return array( $r->creature, number_format( $r->cnt ) );
            } );
            break;

        case 'entries_by_domain':
            echo '<h2>Entries by Domain</h2>';
            $rows = $wpdb->get_results(
                "SELECT e.domain, e.status, COUNT(*) as cnt
                 FROM {$prefix}oat_entries e
                 WHERE 1=1 {$entry_scope} {$f_entry_chronicle}
                 GROUP BY e.domain, e.status ORDER BY e.domain, e.status"
            );
            $pivoted = array();
            $statuses = array();
            foreach ( $rows as $r ) {
                $pivoted[ $r->domain ][ $r->status ] = (int) $r->cnt;
                $statuses[ $r->status ] = true;
            }
            $status_keys = array_keys( $statuses );
            sort( $status_keys );
            $headers = array_merge( array( 'Domain' ), array_map( 'ucfirst', $status_keys ), array( 'Total' ) );
            echo '<table class="widefat striped"><thead><tr>';
            foreach ( $headers as $i => $h ) {
                $align = ( $i > 0 ) ? ' style="text-align:right;"' : '';
                echo '<th' . $align . '>' . esc_html( $h ) . '</th>';
            }
            echo '</tr></thead><tbody>';
            foreach ( $pivoted as $dom => $counts ) {
                $label = class_exists( 'OAT_Domain_Registry' ) ? OAT_Domain_Registry::get_label( $dom ) : $dom;
                echo '<tr><td>' . esc_html( $label ?: ucfirst( str_replace( '_', ' ', $dom ) ) ) . '</td>';
                $row_total = 0;
                foreach ( $status_keys as $s ) {
                    $v = isset( $counts[ $s ] ) ? $counts[ $s ] : 0;
                    $row_total += $v;
                    echo '<td style="text-align:right;">' . number_format( $v ) . '</td>';
                }
                echo '<td style="text-align:right;font-weight:bold;">' . number_format( $row_total ) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            break;

        case 'entries_by_status':
            echo '<h2>Entries by Status</h2>';
            $rows = $wpdb->get_results(
                "SELECT e.status, COUNT(*) as cnt
                 FROM {$prefix}oat_entries e
                 WHERE 1=1 {$entry_scope} {$f_entry_chronicle}
                 GROUP BY e.status ORDER BY cnt DESC"
            );
            owc_oat_report_table( array( 'Status', 'Count' ), $rows, function( $r ) {
                return array( ucfirst( str_replace( '_', ' ', $r->status ) ), number_format( $r->cnt ) );
            } );
            $total = array_sum( wp_list_pluck( $rows, 'cnt' ) );
            echo '<p style="color:#666;margin-top:8px;"><strong>Total: ' . number_format( $total ) . '</strong></p>';
            break;

        case 'entries_by_coordinator':
            echo '<h2>Entries by Coordinator</h2>';
            $rows = $wpdb->get_results(
                "SELECT IFNULL(NULLIF(e.coordinator_genre,''), 'Unassigned') as coord, COUNT(*) as cnt
                 FROM {$prefix}oat_entries e
                 WHERE 1=1 {$entry_scope} {$f_entry_chronicle}
                 GROUP BY coord ORDER BY cnt DESC"
            );
            owc_oat_report_table( array( 'Coordinator', 'Count' ), $rows, function( $r ) {
                $title = ( $r->coord !== 'Unassigned' && function_exists( 'owc_entity_get_title' ) )
                    ? owc_entity_get_title( 'coordinator', $r->coord )
                    : $r->coord;
                return array( $title ?: ucfirst( $r->coord ), number_format( $r->cnt ) );
            } );
            break;

        case 'custom_content_by_type':
            echo '<h2>Custom Content by Type</h2>';
            $rows = $wpdb->get_results(
                "SELECT m.meta_value as content_type, COUNT(*) as cnt
                 FROM {$prefix}oat_entries e
                 JOIN {$prefix}oat_entry_meta m ON e.id = m.entry_id AND m.meta_key = 'content_type'
                 WHERE e.domain = 'custom_content' {$entry_scope}
                 GROUP BY m.meta_value ORDER BY cnt DESC"
            );
            owc_oat_report_table( array( 'Content Type', 'Count' ), $rows, function( $r ) {
                return array( $r->content_type ?: '(none)', number_format( $r->cnt ) );
            } );
            $total = array_sum( wp_list_pluck( $rows, 'cnt' ) );
            echo '<p style="color:#666;margin-top:8px;"><strong>Total: ' . number_format( $total ) . '</strong></p>';
            break;

        case 'disciplinary_actions':
            echo '<h2>Disciplinary Actions</h2>';
            $page    = isset( $_GET['rpg'] ) ? max( 0, (int) $_GET['rpg'] ) : 0;
            $per     = 50;
            $offset  = $page * $per;
            $f_status = '';
            if ( $filters['status'] ) {
                $f_status = $wpdb->prepare( " AND m_status.meta_value = %s", $filters['status'] );
            }
            $total_rows = (int) $wpdb->get_var(
                "SELECT COUNT(*)
                 FROM {$prefix}oat_entries e
                 LEFT JOIN {$prefix}oat_entry_meta m_status ON e.id = m_status.entry_id AND m_status.meta_key = 'da_status'
                 WHERE e.domain = 'disciplinary_actions' {$entry_scope} {$f_entry_chronicle} {$f_status}"
            );
            $rows = $wpdb->get_results(
                "SELECT e.id, e.chronicle_slug, e.created_at,
                    m_name.meta_value as player_name,
                    m_level.meta_value as da_level,
                    m_details.meta_value as da_details,
                    m_date.meta_value as da_date,
                    m_type.meta_value as da_type,
                    IFNULL(m_status.meta_value, 'active') as da_status
                 FROM {$prefix}oat_entries e
                 LEFT JOIN {$prefix}oat_entry_meta m_name ON e.id = m_name.entry_id AND m_name.meta_key = 'player_name'
                 LEFT JOIN {$prefix}oat_entry_meta m_level ON e.id = m_level.entry_id AND m_level.meta_key = 'da_level'
                 LEFT JOIN {$prefix}oat_entry_meta m_details ON e.id = m_details.entry_id AND m_details.meta_key = 'da_details'
                 LEFT JOIN {$prefix}oat_entry_meta m_date ON e.id = m_date.entry_id AND m_date.meta_key = 'da_date'
                 LEFT JOIN {$prefix}oat_entry_meta m_type ON e.id = m_type.entry_id AND m_type.meta_key = 'da_type'
                 LEFT JOIN {$prefix}oat_entry_meta m_status ON e.id = m_status.entry_id AND m_status.meta_key = 'da_status'
                 WHERE e.domain = 'disciplinary_actions' {$entry_scope} {$f_entry_chronicle} {$f_status}
                 ORDER BY m_name.meta_value ASC, m_date.meta_value DESC
                 LIMIT {$per} OFFSET {$offset}"
            );
            // Level label map.
            $level_labels = array(
                'chronicle_ban' => 'Chronicle Ban', 'chronicle_strike' => 'Chronicle Strike',
                'chronicle_probation' => 'Chronicle Probation', 'temporary_suspension' => 'Temporary Suspension',
                'warning' => 'Warning', 'loss_of_xp' => 'Loss of XP',
                'gnc_of_character' => 'GNC of Character', 'removal_from_staff' => 'Removal from Staff',
                'owbn_strike' => 'OWBN Strike', 'owbn_censure' => 'OWBN Censure',
                'owbn_condemnation' => 'OWBN Condemnation', 'owbn_warning' => 'OWBN Warning',
                'owbn_temporary_ban' => 'OWBN Temporary Ban', 'owbn_indefinite_ban' => 'OWBN Indefinite Ban',
                'owbn_permanent_ban' => 'OWBN Permanent Ban', 'other' => 'Other',
            );
            if ( empty( $rows ) ) {
                echo '<p>No disciplinary action records found.</p>';
            } else {
                echo '<input type="text" class="oat-rpt-search" data-table="oat-rpt-da" placeholder="Filter rows..." style="margin-bottom:8px;width:250px;padding:4px 8px;">';
                echo '<table id="oat-rpt-da" class="widefat striped oat-rpt-table"><thead><tr>';
                echo '<th data-col="0" style="cursor:pointer;">Recipient <span style="color:#999;font-size:10px;">&#x25B4;&#x25BE;</span></th>';
                echo '<th data-col="1" style="cursor:pointer;">Chronicle <span style="color:#999;font-size:10px;">&#x25B4;&#x25BE;</span></th>';
                echo '<th data-col="2" style="cursor:pointer;">Date <span style="color:#999;font-size:10px;">&#x25B4;&#x25BE;</span></th>';
                echo '<th data-col="3" style="cursor:pointer;">Level <span style="color:#999;font-size:10px;">&#x25B4;&#x25BE;</span></th>';
                echo '<th data-col="4" style="cursor:pointer;">Status <span style="color:#999;font-size:10px;">&#x25B4;&#x25BE;</span></th>';
                echo '<th>Details</th>';
                echo '</tr></thead><tbody>';
                foreach ( $rows as $r ) {
                    $chron_title = $r->chronicle_slug
                        ? ( function_exists( 'owc_entity_get_title' ) ? owc_entity_get_title( 'chronicle', $r->chronicle_slug ) : $r->chronicle_slug )
                        : 'OWBN-Wide';
                    $level_lbl = isset( $level_labels[ $r->da_level ] ) ? $level_labels[ $r->da_level ] : ( $r->da_level ?: '—' );
                    $status_color = $r->da_status === 'active' ? '#d63638' : ( $r->da_status === 'lifted' ? '#00a32a' : '#666' );
                    $detail_url = admin_url( 'admin.php?page=owc-oat-entry&entry_id=' . $r->id );
                    echo '<tr>';
                    echo '<td><a href="' . esc_url( $detail_url ) . '">' . esc_html( $r->player_name ?: '—' ) . '</a></td>';
                    echo '<td>' . esc_html( $chron_title ?: '—' ) . '</td>';
                    echo '<td>' . esc_html( $r->da_date ?: '—' ) . '</td>';
                    echo '<td>' . esc_html( $level_lbl ) . '</td>';
                    echo '<td><span style="color:' . $status_color . ';font-weight:600;">' . esc_html( ucfirst( $r->da_status ) ) . '</span></td>';
                    echo '<td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' . esc_html( wp_trim_words( wp_strip_all_tags( $r->da_details ), 20 ) ) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                $base = admin_url( 'admin.php?page=owc-oat-reports&report=disciplinary_actions' );
                if ( $filters['status'] ) {
                    $base .= '&status=' . urlencode( $filters['status'] );
                }
                owc_oat_render_pagination( $total_rows, $per, $page, $base );
            }
            break;

        case 'da_by_level':
            echo '<h2>Disciplinary Actions by Level</h2>';
            $rows = $wpdb->get_results(
                "SELECT IFNULL(m.meta_value, 'unknown') as da_level, COUNT(*) as cnt
                 FROM {$prefix}oat_entries e
                 LEFT JOIN {$prefix}oat_entry_meta m ON e.id = m.entry_id AND m.meta_key = 'da_level'
                 WHERE e.domain = 'disciplinary_actions' {$entry_scope}
                 GROUP BY da_level ORDER BY cnt DESC"
            );
            $level_labels = array(
                'chronicle_ban' => 'Chronicle Ban', 'chronicle_strike' => 'Chronicle Strike',
                'chronicle_probation' => 'Chronicle Probation', 'temporary_suspension' => 'Temporary Suspension',
                'warning' => 'Warning', 'loss_of_xp' => 'Loss of XP',
                'gnc_of_character' => 'GNC of Character', 'removal_from_staff' => 'Removal from Staff',
                'owbn_strike' => 'OWBN Strike', 'owbn_censure' => 'OWBN Censure',
                'owbn_condemnation' => 'OWBN Condemnation', 'owbn_warning' => 'OWBN Warning',
                'owbn_temporary_ban' => 'OWBN Temporary Ban', 'owbn_indefinite_ban' => 'OWBN Indefinite Ban',
                'owbn_permanent_ban' => 'OWBN Permanent Ban', 'other' => 'Other',
            );
            owc_oat_report_table( array( 'Level', 'Count' ), $rows, function( $r ) use ( $level_labels ) {
                $label = isset( $level_labels[ $r->da_level ] ) ? $level_labels[ $r->da_level ] : $r->da_level;
                return array( $label, number_format( $r->cnt ) );
            } );
            $total = array_sum( wp_list_pluck( $rows, 'cnt' ) );
            echo '<p style="color:#666;margin-top:8px;"><strong>Total: ' . number_format( $total ) . '</strong></p>';
            break;

        case 'recent_activity':
            echo '<h2>Recent Activity (Last 30 Days)</h2>';
            $cutoff = time() - ( 30 * DAY_IN_SECONDS );
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT DATE(FROM_UNIXTIME(t.created_at)) as day, t.action_type, COUNT(*) as cnt
                 FROM {$prefix}oat_timeline t
                 JOIN {$prefix}oat_entries e ON t.entry_id = e.id
                 WHERE t.created_at >= %d {$entry_scope}
                 GROUP BY day, t.action_type
                 ORDER BY day DESC, cnt DESC",
                $cutoff
            ) );
            if ( empty( $rows ) ) {
                echo '<p>No activity in the last 30 days.</p>';
            } else {
                $by_day = array();
                $actions = array();
                foreach ( $rows as $r ) {
                    $by_day[ $r->day ][ $r->action_type ] = (int) $r->cnt;
                    $actions[ $r->action_type ] = true;
                }
                $action_keys = array_keys( $actions );
                sort( $action_keys );
                $headers = array_merge( array( 'Date' ), array_map( function( $a ) {
                    return ucfirst( str_replace( '_', ' ', $a ) );
                }, $action_keys ), array( 'Total' ) );
                echo '<table class="widefat striped"><thead><tr>';
                foreach ( $headers as $h ) {
                    echo '<th>' . esc_html( $h ) . '</th>';
                }
                echo '</tr></thead><tbody>';
                foreach ( $by_day as $day => $counts ) {
                    echo '<tr><td>' . esc_html( $day ) . '</td>';
                    $row_total = 0;
                    foreach ( $action_keys as $a ) {
                        $v = isset( $counts[ $a ] ) ? $counts[ $a ] : 0;
                        $row_total += $v;
                        echo '<td style="text-align:right;">' . number_format( $v ) . '</td>';
                    }
                    echo '<td style="text-align:right;font-weight:bold;">' . number_format( $row_total ) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
            break;

        default:
            echo '<p>Unknown report.</p>';
    }

    echo '</div>';
}

/**
 * Render a simple two+ column report table with client-side sort/filter.
 */
function owc_oat_report_table( $headers, $rows, $row_fn ) {
    if ( empty( $rows ) ) {
        echo '<p>No data.</p>';
        return;
    }
    static $table_id = 0;
    $table_id++;
    $tid = 'oat-rpt-' . $table_id;

    echo '<input type="text" class="oat-rpt-search" data-table="' . $tid . '" placeholder="Filter rows..." style="margin-bottom:8px;width:250px;padding:4px 8px;">';
    echo '<table id="' . $tid . '" class="widefat striped oat-rpt-table"><thead><tr>';
    foreach ( $headers as $i => $h ) {
        $align = ( $i > 0 ) ? ' style="text-align:right;cursor:pointer;"' : ' style="cursor:pointer;"';
        echo '<th data-col="' . $i . '"' . $align . '>' . esc_html( $h ) . ' <span style="color:#999;font-size:10px;">&#x25B4;&#x25BE;</span></th>';
    }
    echo '</tr></thead><tbody>';
    foreach ( $rows as $r ) {
        $cells = $row_fn( $r );
        echo '<tr>';
        foreach ( $cells as $i => $cell ) {
            $align = ( $i > 0 ) ? ' style="text-align:right;"' : '';
            echo '<td' . $align . '>' . esc_html( $cell ) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
}

/**
 * Render windowed pagination: « ‹ 1 2 3 ... 76 77 › »
 */
function owc_oat_render_pagination( $total_rows, $per_page, $current_page, $base_url ) {
    $total_pages = (int) ceil( $total_rows / $per_page );
    if ( $total_pages <= 1 ) {
        return;
    }
    echo '<div style="margin-top:12px;display:flex;align-items:center;gap:4px;flex-wrap:wrap;">';

    // Prev.
    if ( $current_page > 0 ) {
        echo '<a href="' . esc_url( $base_url . '&rpg=' . ( $current_page - 1 ) ) . '" style="padding:4px 8px;text-decoration:none;">&lsaquo;</a>';
    }

    // Window: show pages around current, plus first and last.
    $window = 3;
    for ( $i = 0; $i < $total_pages; $i++ ) {
        if ( $i === 0 || $i === $total_pages - 1 || abs( $i - $current_page ) <= $window ) {
            if ( $i === $current_page ) {
                echo '<strong style="padding:4px 8px;background:#2271b1;color:#fff;border-radius:3px;">' . ( $i + 1 ) . '</strong>';
            } else {
                echo '<a href="' . esc_url( $base_url . '&rpg=' . $i ) . '" style="padding:4px 8px;text-decoration:none;">' . ( $i + 1 ) . '</a>';
            }
        } elseif ( $i === 1 || $i === $total_pages - 2 ) {
            echo '<span style="padding:4px;">…</span>';
        }
    }

    // Next.
    if ( $current_page < $total_pages - 1 ) {
        echo '<a href="' . esc_url( $base_url . '&rpg=' . ( $current_page + 1 ) ) . '" style="padding:4px 8px;text-decoration:none;">&rsaquo;</a>';
    }

    echo '<span style="color:#666;margin-left:8px;">(' . number_format( $total_rows ) . ' rows)</span>';
    echo '</div>';
}

/**
 * Client-side sort and filter JS for report tables.
 * Enqueued once at end of page.
 */
function owc_oat_reports_inline_js() {
    ?>
    <script>
    (function() {
        // Filter
        document.querySelectorAll('.oat-rpt-search').forEach(function(input) {
            input.addEventListener('input', function() {
                var term = this.value.toLowerCase();
                var table = document.getElementById(this.getAttribute('data-table'));
                if (!table) return;
                table.querySelectorAll('tbody tr').forEach(function(tr) {
                    tr.style.display = tr.textContent.toLowerCase().indexOf(term) >= 0 ? '' : 'none';
                });
            });
        });
        // Sort
        document.querySelectorAll('.oat-rpt-table th[data-col]').forEach(function(th) {
            th.addEventListener('click', function() {
                var col = parseInt(this.getAttribute('data-col'));
                var table = this.closest('table');
                var tbody = table.querySelector('tbody');
                var rows = Array.from(tbody.querySelectorAll('tr'));
                var asc = this.getAttribute('data-sort-dir') !== 'asc';
                this.setAttribute('data-sort-dir', asc ? 'asc' : 'desc');
                rows.sort(function(a, b) {
                    var va = (a.children[col] || {}).textContent || '';
                    var vb = (b.children[col] || {}).textContent || '';
                    var na = parseFloat(va.replace(/,/g, '')), nb = parseFloat(vb.replace(/,/g, ''));
                    if (!isNaN(na) && !isNaN(nb)) return asc ? na - nb : nb - na;
                    return asc ? va.localeCompare(vb) : vb.localeCompare(va);
                });
                rows.forEach(function(r) { tbody.appendChild(r); });
            });
        });
    })();
    </script>
    <?php
}
add_action( 'admin_footer', 'owc_oat_reports_inline_js' );
