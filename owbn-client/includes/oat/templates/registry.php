<?php
/**
 * OAT Client - Registry Template (Backend WP Admin)
 *
 * Renders tabbed registry with lazy-loaded sections and characters via AJAX.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
    <h1>Registry</h1>

    <nav class="nav-tab-wrapper" id="oat-registry-tabs">
        <a href="#" class="nav-tab oat-reg-tab" data-scope="mine">My Characters</a>
        <a href="#" class="nav-tab oat-reg-tab" data-scope="chronicles">Chronicles</a>
        <a href="#" class="nav-tab oat-reg-tab" data-scope="coordinators">Coordinators</a>
        <a href="#" class="nav-tab oat-reg-tab" data-scope="decommissioned">Decommissioned</a>
    </nav>

    <div style="margin:12px 0;display:flex;gap:10px;align-items:center;">
        <input type="text" id="oat-registry-search" placeholder="Search characters..." class="regular-text">
        <button type="button" id="oat-registry-clear" class="button">Clear</button>
    </div>

    <div id="oat-registry-content">
        <p>Loading...</p>
    </div>
</div>

<script>
(function($) {
    var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    var nonce   = <?php echo wp_json_encode( wp_create_nonce( 'owc_oat_nonce' ) ); ?>;
    var $content = $('#oat-registry-content');
    var loadedSections = {};

    function doPost(action, data, cb) {
        data.action = action;
        data.nonce  = nonce;
        $.post(ajaxUrl, data, function(resp) {
            cb(resp.success ? resp.data : null);
        }).fail(function() { cb(null); });
    }

    function loadTab(scope) {
        $content.html('<p>Loading...</p>');
        loadedSections = {};
        $('.oat-reg-tab').removeClass('nav-tab-active');
        $('.oat-reg-tab[data-scope="' + scope + '"]').addClass('nav-tab-active');

        doPost('owc_oat_registry_sections', { scope: scope }, function(sections) {
            if (!sections || !sections.length) {
                $content.html('<p>No sections found.</p>');
                return;
            }
            var html = '';
            for (var i = 0; i < sections.length; i++) {
                var s = sections[i];
                html += '<div class="oat-reg-section" data-key="' + s.key + '" data-label="' + s.label.toLowerCase() + '">'
                    + '<h3 class="oat-reg-section-header" style="cursor:pointer;padding:8px 12px;margin:4px 0;background:#f7f7f7;border:1px solid #ddd;border-radius:4px;">'
                    + '<span class="oat-reg-toggle" style="display:inline-block;width:16px;">&#9656;</span>'
                    + s.label + ' <span style="color:#666;font-weight:normal;">(' + s.count + ')</span></h3>'
                    + '<div class="oat-reg-section-body" style="display:none;">'
                    + '<table class="wp-list-table widefat fixed striped"><thead><tr>'
                    + '<th style="width:25%;">Character</th><th>Chronicle</th><th>Type</th><th>PC/NPC</th><th>Status</th><th style="width:60px;">Entries</th>'
                    + '</tr></thead><tbody><tr><td colspan="6">Loading...</td></tr></tbody></table>'
                    + '</div></div>';
            }
            $content.html(html);

            // Bind section expand/collapse.
            $content.find('.oat-reg-section-header').on('click', function() {
                var $section = $(this).closest('.oat-reg-section');
                var $body = $section.find('.oat-reg-section-body');
                var $toggle = $(this).find('.oat-reg-toggle');
                var key = $section.data('key');

                if ($body.is(':visible')) {
                    $body.slideUp(150);
                    $toggle.html('&#9656;');
                } else {
                    $body.slideDown(150);
                    $toggle.html('&#9662;');
                    if (!loadedSections[key]) {
                        loadedSections[key] = true;
                        loadSectionCharacters($section, key);
                    }
                }
            });
        });
    }

    function loadSectionCharacters($section, key) {
        doPost('owc_oat_registry_section', { section_key: key }, function(data) {
            var $tbody = $section.find('tbody');
            if (!data || !data.characters || !data.characters.length) {
                $tbody.html('<tr><td colspan="6" style="padding:8px;">No characters.</td></tr>');
                return;
            }
            var html = '';
            for (var i = 0; i < data.characters.length; i++) {
                var c = data.characters[i];
                var name = c.character_name || '(unnamed)';
                var slug = (c.chronicle_slug || '').toUpperCase();
                var url = <?php echo wp_json_encode( admin_url( 'admin.php?page=owc-oat-registry-character&character_id=' ) ); ?> + (c.id || 0);
                var entries = c.entry_counts || 0;
                if (typeof entries === 'object') {
                    var sum = 0; for (var k in entries) sum += parseInt(entries[k])||0;
                    entries = sum;
                }
                html += '<tr class="oat-reg-row" data-name="' + name.toLowerCase() + '" data-chron="' + slug.toLowerCase() + '">'
                    + '<td><a href="' + url + '"><strong>' + name + '</strong></a></td>'
                    + '<td>' + slug + '</td>'
                    + '<td>' + (c.creature_type||'') + '</td>'
                    + '<td>' + (c.pc_npc||'').toUpperCase() + '</td>'
                    + '<td>' + (c.status ? c.status.charAt(0).toUpperCase() + c.status.slice(1) : '') + '</td>'
                    + '<td>' + entries + '</td>'
                    + '</tr>';
            }
            $tbody.html(html);
        });
    }

    // Tab clicks.
    $('.oat-reg-tab').on('click', function(e) {
        e.preventDefault();
        loadTab($(this).data('scope'));
    });

    // Search — filters within loaded rows.
    $('#oat-registry-search').on('input', function() {
        var term = this.value.toLowerCase();
        $content.find('.oat-reg-section').each(function() {
            var $rows = $(this).find('.oat-reg-row');
            if (!$rows.length) return;
            var visible = 0;
            $rows.each(function() {
                var match = !term || ($(this).data('name')||'').indexOf(term) !== -1 || ($(this).data('chron')||'').indexOf(term) !== -1;
                $(this).toggle(match);
                if (match) visible++;
            });
            $(this).toggle(!term || visible > 0);
        });
    });

    // Clear.
    $('#oat-registry-clear').on('click', function() {
        $('#oat-registry-search').val('');
        $content.find('.oat-reg-section').show();
        $content.find('.oat-reg-row').show();
        $content.find('.oat-reg-section-body').slideUp(150);
        $content.find('.oat-reg-toggle').html('&#9656;');
    });

    // Load first tab.
    loadTab('mine');
})(jQuery);
</script>
