<?php
/**
 * OAT Fame Registry — server-side render.
 *
 * Queries approved fame_registry entries from OAT tables and renders
 * a sortable, filterable div-based table. Called by the Elementor widget.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the fame registry HTML.
 *
 * @param array $args Widget settings.
 */
function owc_oat_render_fame_registry( $args = array() ) {
	$show_chronicle = $args['show_chronicle'] ?? true;
	$show_identity  = $args['show_identity']  ?? true;
	$show_notes     = $args['show_notes']     ?? true;
	$sort_col       = $args['sort_col']       ?? 'level';
	$sort_dir       = $args['sort_dir']       ?? 'desc';

	$rows = owc_oat_query_fame_entries();

	$version = defined( 'OWC_ARCHIVIST_VERSION' ) ? OWC_ARCHIVIST_VERSION : '1.0.0';
	$js_url  = defined( 'OWC_ARCHIVIST_URL' ) ? OWC_ARCHIVIST_URL . 'includes/oat/assets/js/' : '';
	wp_enqueue_script( 'oat-fame-registry', $js_url . 'oat-fame-registry.js', array(), $version, true );

	?>
	<div class="oat-fame-registry" data-sort-col="<?php echo esc_attr( $sort_col ); ?>" data-sort-dir="<?php echo esc_attr( $sort_dir ); ?>">

		<div class="oat-fame-filter">
			<input type="text" class="oat-fame-search" placeholder="<?php esc_attr_e( 'Filter by name, chronicle, influence\u2026', 'owbn-archivist' ); ?>" />
		</div>

		<?php if ( empty( $rows ) ) : ?>
			<p class="oat-fame-empty"><?php esc_html_e( 'No fame records found.', 'owbn-archivist' ); ?></p>
		<?php else : ?>
			<div class="oat-fame-table" role="table">
				<div class="oat-fame-thead" role="rowgroup">
					<div class="oat-fame-row oat-fame-header" role="row">
						<div class="oat-fame-cell oat-fame-sortable" role="columnheader" data-col="character">
							<?php esc_html_e( 'Character', 'owbn-archivist' ); ?><span class="oat-sort-icon"></span>
						</div>
						<?php if ( $show_chronicle ) : ?>
							<div class="oat-fame-cell oat-fame-sortable" role="columnheader" data-col="chronicle">
								<?php esc_html_e( 'Home Chronicle', 'owbn-archivist' ); ?><span class="oat-sort-icon"></span>
							</div>
						<?php endif; ?>
						<?php if ( $show_identity ) : ?>
							<div class="oat-fame-cell oat-fame-sortable" role="columnheader" data-col="identity">
								<?php esc_html_e( 'Identity', 'owbn-archivist' ); ?><span class="oat-sort-icon"></span>
							</div>
						<?php endif; ?>
						<div class="oat-fame-cell oat-fame-sortable" role="columnheader" data-col="level">
							<?php esc_html_e( 'Level', 'owbn-archivist' ); ?><span class="oat-sort-icon"></span>
						</div>
						<div class="oat-fame-cell oat-fame-sortable" role="columnheader" data-col="influence">
							<?php esc_html_e( 'Influence', 'owbn-archivist' ); ?><span class="oat-sort-icon"></span>
						</div>
						<?php if ( $show_notes ) : ?>
							<div class="oat-fame-cell" role="columnheader" data-col="notes">
								<?php esc_html_e( 'Notes', 'owbn-archivist' ); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<div class="oat-fame-tbody" role="rowgroup">
					<?php foreach ( $rows as $row ) : ?>
						<div class="oat-fame-row" role="row"
							data-character="<?php echo esc_attr( strtolower( $row['character'] ) ); ?>"
							data-chronicle="<?php echo esc_attr( strtolower( $row['chronicle'] ) ); ?>"
							data-identity="<?php echo esc_attr( strtolower( $row['identity'] ) ); ?>"
							data-level="<?php echo esc_attr( $row['level'] ); ?>"
							data-influence="<?php echo esc_attr( strtolower( $row['influence'] ) ); ?>"
							data-notes="<?php echo esc_attr( strtolower( wp_strip_all_tags( $row['notes'] ) ) ); ?>"
						>
							<div class="oat-fame-cell" role="cell" data-label="<?php esc_attr_e( 'Character', 'owbn-archivist' ); ?>"><?php echo esc_html( $row['character'] ); ?></div>
							<?php if ( $show_chronicle ) : ?>
								<div class="oat-fame-cell" role="cell" data-label="<?php esc_attr_e( 'Chronicle', 'owbn-archivist' ); ?>"><?php echo esc_html( $row['chronicle'] ); ?></div>
							<?php endif; ?>
							<?php if ( $show_identity ) : ?>
								<div class="oat-fame-cell" role="cell" data-label="<?php esc_attr_e( 'Identity', 'owbn-archivist' ); ?>"><?php echo esc_html( $row['identity'] ); ?></div>
							<?php endif; ?>
							<div class="oat-fame-cell oat-fame-level" role="cell" data-label="<?php esc_attr_e( 'Level', 'owbn-archivist' ); ?>"><?php echo esc_html( $row['level'] ); ?></div>
							<div class="oat-fame-cell" role="cell" data-label="<?php esc_attr_e( 'Influence', 'owbn-archivist' ); ?>"><?php echo esc_html( $row['influence'] ); ?></div>
							<?php if ( $show_notes ) : ?>
								<div class="oat-fame-cell oat-fame-notes" role="cell" data-label="<?php esc_attr_e( 'Notes', 'owbn-archivist' ); ?>"><?php echo wp_kses_post( $row['notes'] ); ?></div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="oat-fame-count">
				<span class="oat-fame-visible"><?php echo count( $rows ); ?></span>
				<?php /* translators: %d: total records */ ?>
				<?php printf( esc_html__( 'of %d records', 'owbn-archivist' ), count( $rows ) ); ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Query approved fame entries from OAT tables.
 *
 * @return array Rows with keys: character, chronicle, identity, level, influence, notes.
 */
function owc_oat_query_fame_entries() {
	global $wpdb;

	$entries_table = $wpdb->prefix . 'oat_entries';
	$meta_table    = $wpdb->prefix . 'oat_entry_meta';

	// Verify tables exist (OAT must be active).
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$entries_table}'" ) !== $entries_table ) {
		return array();
	}

	$entry_ids = $wpdb->get_col(
		"SELECT id FROM {$entries_table}
		 WHERE domain = 'character_lifecycle'
		   AND form_slug = 'cl_fame_registry'
		   AND status IN ('approved', 'auto_approved')
		 ORDER BY created_at DESC"
	);

	if ( empty( $entry_ids ) ) {
		return array();
	}

	$placeholders = implode( ',', array_fill( 0, count( $entry_ids ), '%d' ) );

	$meta_rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT entry_id, meta_key, meta_value
		 FROM {$meta_table}
		 WHERE entry_id IN ({$placeholders})",
		...$entry_ids
	) );

	$meta_map = array();
	foreach ( $meta_rows as $m ) {
		$meta_map[ (int) $m->entry_id ][ $m->meta_key ] = $m->meta_value;
	}

	$rows = array();
	foreach ( $entry_ids as $eid ) {
		$eid  = (int) $eid;
		$meta = isset( $meta_map[ $eid ] ) ? $meta_map[ $eid ] : array();

		$influence = owc_oat_parse_fame_influence( $meta['fame_influence_areas'] ?? '' );

		$chronicle_slug  = $meta['chronicle_slug'] ?? '';
		$chronicle_label = owc_oat_fame_chronicle_label( $chronicle_slug );

		$description = $meta['fame_description'] ?? '';
		$scope       = $meta['fame_scope'] ?? '';
		$notes       = $description;
		if ( $scope ) {
			$notes .= ( $notes ? '<br>' : '' ) . '<em>Scope: ' . $scope . '</em>';
		}

		$rows[] = array(
			'character' => $meta['character_name'] ?? '(unknown)',
			'chronicle' => $chronicle_label ?: $chronicle_slug,
			'identity'  => $meta['fame_public_id'] ?? '',
			'level'     => isset( $meta['fame_level'] ) ? (int) $meta['fame_level'] : 0,
			'influence' => $influence,
			'notes'     => $notes,
		);
	}

	return $rows;
}

/**
 * Parse influence areas from stored JSON or comma-separated value.
 */
function owc_oat_parse_fame_influence( $raw ) {
	if ( empty( $raw ) ) {
		return '';
	}
	$decoded = json_decode( $raw, true );
	if ( is_array( $decoded ) ) {
		return implode( ', ', array_map( function ( $v ) {
			return ucwords( str_replace( '_', ' ', $v ) );
		}, $decoded ) );
	}
	return $raw;
}

/**
 * Resolve a chronicle slug to a human-readable label.
 */
function owc_oat_fame_chronicle_label( $slug ) {
	if ( empty( $slug ) ) {
		return '';
	}
	$posts = get_posts( array(
		'post_type'   => 'owbn_chronicle',
		'name'        => $slug,
		'numberposts' => 1,
		'fields'      => 'ids',
	) );
	if ( ! empty( $posts ) ) {
		return get_the_title( $posts[0] );
	}
	return ucwords( str_replace( '-', ' ', $slug ) );
}
