<?php
/**
 * OAT Client - Registry Template
 *
 * Variables available:
 *   $sections         array  Sections: [ label, key, characters[] ].
 *   $total_count      int    Total character count across all sections.
 *   $chronicle_filter string Active chronicle filter slug.
 *   $genre_filter     string Active genre filter slug.
 */

defined( 'ABSPATH' ) || exit;
?>
<style>
    .oat-registry-section { margin-bottom: 20px; }
    .oat-registry-section-header {
        cursor: pointer;
        user-select: none;
        padding: 10px 14px;
        background: #f0f0f1;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .oat-registry-section-header:hover { background: #e5e5e6; }
    .oat-registry-section-header h2 { margin: 0; font-size: 14px; }
    .oat-registry-section-header .oat-toggle { font-size: 18px; line-height: 1; color: #646970; }
    .oat-registry-section-body { display: none; margin-top: 0; }
    .oat-registry-section-body.oat-expanded { display: block; }
    .oat-registry-section-body .wp-list-table { border-top: none; border-radius: 0 0 4px 4px; }
    .oat-registry-count { color: #646970; font-weight: normal; margin-left: 6px; }
    @media screen and (max-width: 782px) {
        .oat-registry-section-header { padding: 12px; }
        .oat-registry-section-header h2 { font-size: 13px; }
    }
</style>

<div class="wrap">
    <h1>Registry</h1>

    <form method="get">
        <input type="hidden" name="page" value="owc-oat-registry">
        <div class="alignleft actions">
            <input type="text" name="chronicle" value="<?php echo esc_attr( $chronicle_filter ); ?>" placeholder="Chronicle slug" style="width:160px;">
            <input type="text" name="genre" value="<?php echo esc_attr( $genre_filter ); ?>" placeholder="Coordinator genre" style="width:160px;">
            <?php submit_button( 'Filter', '', 'filter_action', false ); ?>
            <?php if ( $chronicle_filter || $genre_filter ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=owc-oat-registry' ) ); ?>" class="button">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <p class="description"><?php echo esc_html( $total_count ); ?> character<?php echo $total_count !== 1 ? 's' : ''; ?> across <?php echo count( $sections ); ?> section<?php echo count( $sections ) !== 1 ? 's' : ''; ?>.</p>

    <?php if ( empty( $sections ) ) : ?>
        <p>No characters in your registry scope.</p>
    <?php endif; ?>

    <?php foreach ( $sections as $i => $section ) :
        $key        = esc_attr( $section['key'] );
        $chars      = $section['characters'];
        $char_count = count( $chars );
        // First section ("My Characters") defaults expanded. Others collapse if >20 characters.
        $expanded   = ( $i === 0 ) || ( $char_count <= 20 );
    ?>
        <div class="oat-registry-section" data-section="<?php echo $key; ?>">
            <div class="oat-registry-section-header" onclick="oatToggleSection(this)">
                <h2>
                    <?php echo esc_html( $section['label'] ); ?>
                    <span class="oat-registry-count">(<?php echo $char_count; ?>)</span>
                </h2>
                <span class="oat-toggle"><?php echo $expanded ? '&#9662;' : '&#9656;'; ?></span>
            </div>

            <div class="oat-registry-section-body <?php echo $expanded ? 'oat-expanded' : ''; ?>">
                <?php if ( empty( $chars ) ) : ?>
                    <p style="padding:8px 14px;">No characters.</p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width:30%;">Character</th>
                                <th>Chronicle</th>
                                <th>Type</th>
                                <th>PC/NPC</th>
                                <th>Status</th>
                                <th>Entries</th>
                                <th style="width:80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $chars as $char ) :
                                $char_id      = isset( $char['id'] ) ? (int) $char['id'] : 0;
                                $char_name    = isset( $char['character_name'] ) ? $char['character_name'] : '(unnamed)';
                                $chronicle    = isset( $char['chronicle_slug'] ) ? $char['chronicle_slug'] : '';
                                $creature     = isset( $char['creature_type'] ) ? $char['creature_type'] : '';
                                $pc_npc       = isset( $char['pc_npc'] ) ? strtoupper( $char['pc_npc'] ) : '';
                                $status       = isset( $char['status'] ) ? $char['status'] : '';
                                $entry_counts = isset( $char['entry_counts'] ) ? $char['entry_counts'] : array();
                                if ( is_object( $entry_counts ) ) {
                                    $entry_counts = (array) $entry_counts;
                                }
                                $total_entries = array_sum( $entry_counts );
                                $detail_url    = admin_url( 'admin.php?page=owc-oat-registry-character&character_id=' . $char_id );
                            ?>
                                <tr>
                                    <td><a href="<?php echo esc_url( $detail_url ); ?>"><strong><?php echo esc_html( $char_name ); ?></strong></a></td>
                                    <td><?php echo esc_html( $chronicle ); ?></td>
                                    <td><?php echo esc_html( $creature ); ?></td>
                                    <td><?php echo esc_html( $pc_npc ); ?></td>
                                    <td><?php echo esc_html( ucfirst( $status ) ); ?></td>
                                    <td>
                                        <?php if ( $total_entries > 0 ) : ?>
                                            <span title="<?php echo esc_attr( implode( ', ', array_map( function( $d, $c ) { return "$d: $c"; }, array_keys( $entry_counts ), $entry_counts ) ) ); ?>">
                                                <?php echo esc_html( $total_entries ); ?>
                                            </span>
                                        <?php else : ?>
                                            0
                                        <?php endif; ?>
                                    </td>
                                    <td><a href="<?php echo esc_url( $detail_url ); ?>" class="button button-small">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
function oatToggleSection(header) {
    var body = header.nextElementSibling;
    var toggle = header.querySelector('.oat-toggle');
    if (body.classList.contains('oat-expanded')) {
        body.classList.remove('oat-expanded');
        toggle.innerHTML = '&#9656;';
    } else {
        body.classList.add('oat-expanded');
        toggle.innerHTML = '&#9662;';
    }
}
</script>
