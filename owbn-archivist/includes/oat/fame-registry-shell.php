<?php
defined( 'ABSPATH' ) || exit;

function owc_oat_render_fame_registry( $args = [] ) {
	$sort_col = $args['sort_col'] ?? 'level';
	$sort_dir = $args['sort_dir'] ?? 'desc';

	$rows = owc_oat_query_fame_entries();

	$version = defined( 'OWC_ARCHIVIST_VERSION' ) ? OWC_ARCHIVIST_VERSION : '1.0.0';
	$js_url  = defined( 'OWC_ARCHIVIST_URL' ) ? OWC_ARCHIVIST_URL . 'includes/oat/assets/js/' : '';
	wp_enqueue_script( 'oat-fame-registry', $js_url . 'oat-fame-registry.js', [], $version, true );

	?>
	<div class="oat-fame-registry" data-sort-col="<?php echo esc_attr( $sort_col ); ?>" data-sort-dir="<?php echo esc_attr( $sort_dir ); ?>">

		<div class="oat-fame-filter">
			<input type="text" class="oat-fame-search" placeholder="<?php esc_attr_e( 'Filter by name, chronicle, influence...', 'owbn-archivist' ); ?>" />
		</div>

		<?php if ( empty( $rows ) ) : ?>
			<p class="oat-fame-empty"><?php esc_html_e( 'No fame records found.', 'owbn-archivist' ); ?></p>
		<?php else : ?>
			<div class="oat-fame-table" role="table">
				<div class="oat-fame-thead" role="rowgroup">
					<div class="oat-fame-row oat-fame-header" role="row">
						<div class="oat-fame-cell oat-fame-sortable" role="columnheader" data-col="character" style="flex:2;">
							<?php esc_html_e( 'Character', 'owbn-archivist' ); ?><span class="oat-sort-icon"></span>
						</div>
						<div class="oat-fame-cell oat-fame-sortable" role="columnheader" data-col="chronicle" style="flex:1;">
							<?php esc_html_e( 'Home Chronicle', 'owbn-archivist' ); ?><span class="oat-sort-icon"></span>
						</div>
						<div class="oat-fame-cell oat-fame-sortable" role="columnheader" data-col="level" style="flex:0.5;text-align:center;">
							<?php esc_html_e( 'Level', 'owbn-archivist' ); ?><span class="oat-sort-icon"></span>
						</div>
					</div>
				</div>
				<div class="oat-fame-tbody" role="rowgroup">
					<?php foreach ( $rows as $row ) :
						$chr_url = '';
						if ( function_exists( 'owc_get_chronicle_url' ) && $row['chronicle_slug'] ) {
							$chr_url = owc_get_chronicle_url( $row['chronicle_slug'] );
						}
					?>
						<div class="oat-fame-entry"
							data-character="<?php echo esc_attr( strtolower( $row['character'] ) ); ?>"
							data-chronicle="<?php echo esc_attr( strtolower( $row['chronicle'] ) ); ?>"
							data-identity="<?php echo esc_attr( strtolower( $row['identity'] ) ); ?>"
							data-level="<?php echo esc_attr( $row['level'] ); ?>"
							data-influence="<?php echo esc_attr( strtolower( $row['influence'] ) ); ?>"
							data-notes="<?php echo esc_attr( strtolower( wp_strip_all_tags( $row['notes'] ) ) ); ?>"
						>
							<div class="oat-fame-row oat-fame-summary" role="row" onclick="this.parentElement.classList.toggle('oat-fame-open');" style="cursor:pointer;">
								<div class="oat-fame-cell" role="cell" style="flex:2;">
									<span class="oat-fame-arrow">&#9654;</span>
									<?php echo esc_html( $row['character'] ); ?>
								</div>
								<div class="oat-fame-cell" role="cell" style="flex:1;">
									<?php if ( $chr_url ) : ?>
										<a href="<?php echo esc_url( $chr_url ); ?>" title="<?php echo esc_attr( $row['chronicle'] ); ?>" onclick="event.stopPropagation();"><?php echo esc_html( $row['chronicle_slug'] ?: $row['chronicle'] ); ?></a>
									<?php else : ?>
										<span title="<?php echo esc_attr( $row['chronicle'] ); ?>"><?php echo esc_html( $row['chronicle_slug'] ?: $row['chronicle'] ); ?></span>
									<?php endif; ?>
								</div>
								<div class="oat-fame-cell oat-fame-level" role="cell" style="flex:0.5;text-align:center;">
									<?php echo esc_html( $row['level'] ); ?>
								</div>
							</div>
							<div class="oat-fame-detail" style="display:none;padding:8px 12px 12px 28px;font-size:0.9em;border-bottom:1px solid #eee;">
								<?php if ( $row['identity'] ) : ?>
									<p style="margin:4px 0;"><strong><?php esc_html_e( 'Identity:', 'owbn-archivist' ); ?></strong> <?php echo esc_html( $row['identity'] ); ?></p>
								<?php endif; ?>
								<?php if ( $row['influence'] ) : ?>
									<p style="margin:4px 0;"><strong><?php esc_html_e( 'Influences:', 'owbn-archivist' ); ?></strong> <?php echo esc_html( $row['influence'] ); ?></p>
								<?php endif; ?>
								<?php if ( $row['notes'] ) : ?>
									<p style="margin:4px 0;"><strong><?php esc_html_e( 'Notes:', 'owbn-archivist' ); ?></strong> <?php echo wp_kses_post( $row['notes'] ); ?></p>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="oat-fame-count">
				<span class="oat-fame-visible"><?php echo count( $rows ); ?></span>
				<?php printf( esc_html__( 'of %d records', 'owbn-archivist' ), count( $rows ) ); ?>
			</div>
		<?php endif; ?>
	</div>

	<style>
	.oat-fame-entry.oat-fame-open .oat-fame-detail { display: block !important; }
	.oat-fame-entry.oat-fame-open .oat-fame-arrow { display: inline-block; transform: rotate(90deg); }
	.oat-fame-arrow { display: inline-block; font-size: 0.7em; margin-right: 6px; transition: transform 0.15s; }
	</style>
	<?php
}

function owc_oat_query_fame_entries() {
	global $wpdb;

	$entries_table = $wpdb->prefix . 'oat_entries';
	$meta_table    = $wpdb->prefix . 'oat_entry_meta';

	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$entries_table}'" ) !== $entries_table ) {
		return [];
	}

	$entry_ids = $wpdb->get_col(
		"SELECT id FROM {$entries_table}
		 WHERE domain = 'character_lifecycle'
		   AND form_slug = 'cl_fame_registry'
		   AND status IN ('approved', 'auto_approved')
		 ORDER BY created_at DESC"
	);

	if ( empty( $entry_ids ) ) {
		return [];
	}

	$placeholders = implode( ',', array_fill( 0, count( $entry_ids ), '%d' ) );

	$meta_rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT entry_id, meta_key, meta_value FROM {$meta_table} WHERE entry_id IN ({$placeholders})",
		...$entry_ids
	) );

	$meta_map = [];
	foreach ( $meta_rows as $m ) {
		$meta_map[ (int) $m->entry_id ][ $m->meta_key ] = $m->meta_value;
	}

	$rows = [];
	foreach ( $entry_ids as $eid ) {
		$eid  = (int) $eid;
		$meta = $meta_map[ $eid ] ?? [];

		$influence = owc_oat_parse_fame_influence( $meta['fame_influence_areas'] ?? '' );

		$chronicle_slug  = $meta['chronicle_slug'] ?? '';
		$chronicle_label = owc_oat_fame_chronicle_label( $chronicle_slug );

		$description = $meta['fame_description'] ?? '';
		$scope       = $meta['fame_scope'] ?? '';
		$notes       = $description;
		if ( $scope ) {
			$notes .= ( $notes ? '<br>' : '' ) . '<em>Scope: ' . $scope . '</em>';
		}

		$rows[] = [
			'character'      => $meta['character_name'] ?? '(unknown)',
			'chronicle'      => $chronicle_label ?: $chronicle_slug,
			'chronicle_slug' => strtoupper( $chronicle_slug ),
			'identity'       => $meta['fame_public_id'] ?? '',
			'level'          => isset( $meta['fame_level'] ) ? (int) $meta['fame_level'] : 0,
			'influence'      => $influence,
			'notes'          => $notes,
		];
	}

	return $rows;
}

function owc_oat_parse_fame_influence( $raw ) {
	if ( empty( $raw ) ) return '';
	$decoded = json_decode( $raw, true );
	if ( is_array( $decoded ) ) {
		return implode( ', ', array_map( function ( $v ) {
			return ucwords( str_replace( '_', ' ', $v ) );
		}, $decoded ) );
	}
	return $raw;
}

function owc_oat_fame_chronicle_label( $slug ) {
	if ( empty( $slug ) ) return '';
	$posts = get_posts( [
		'post_type'   => 'owbn_chronicle',
		'name'        => $slug,
		'numberposts' => 1,
		'fields'      => 'ids',
	] );
	if ( ! empty( $posts ) ) {
		return get_the_title( $posts[0] );
	}
	return ucwords( str_replace( '-', ' ', $slug ) );
}
