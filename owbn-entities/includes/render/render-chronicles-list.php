<?php

/**
 * OWBN-Client Chronicles List Render
 */

defined('ABSPATH') || exit;

/**
 * Render chronicles list.
 *
 * @param array $chronicles List of chronicle data
 * @return string HTML output
 */
function owc_render_chronicles_list(array $chronicles): string
{
    if (isset($chronicles['error'])) {
        return '<p class="owc-error">' . esc_html($chronicles['error']) . '</p>';
    }

    // Filter to published chronicles only (shortcode path).
    $chronicles = array_filter( $chronicles, function( $c ) {
        return ( $c['status'] ?? 'publish' ) === 'publish';
    } );
    $chronicles = array_values( $chronicles );

    if (empty($chronicles)) {
        return '<p class="owc-no-results">' . esc_html__('No chronicles found.', 'owbn-entities') . '</p>';
    }

    // Sort by title ascending by default
    usort($chronicles, function ($a, $b) {
        return strcasecmp($a['title'] ?? '', $b['title'] ?? '');
    });

    $detail_page_id = get_option(owc_option_name('chronicles_detail_page'), 0);
    $base_url = $detail_page_id ? get_permalink($detail_page_id) : '';

    ob_start();
?>
    <?php echo owc_render_chronicles_filter_bar($chronicles); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped where it is built. ?>

    <div class="owc-chronicles-list">
        <div class="owc-list-header">
            <div class="owc-col-title sort-asc"><?php esc_html_e('Chronicle', 'owbn-entities'); ?></div>
            <div class="owc-col-genres"><?php esc_html_e('Genres', 'owbn-entities'); ?></div>
            <div class="owc-col-region"><?php esc_html_e('Region', 'owbn-entities'); ?></div>
            <div class="owc-col-state"><?php esc_html_e('State/Province', 'owbn-entities'); ?></div>
            <div class="owc-col-city"><?php esc_html_e('City', 'owbn-entities'); ?></div>
            <div class="owc-col-type"><?php esc_html_e('Type', 'owbn-entities'); ?></div>
            <div class="owc-col-status"><?php esc_html_e('Status', 'owbn-entities'); ?></div>
        </div>

        <?php foreach ($chronicles as $chronicle) : ?>
            <?php echo owc_render_chronicle_row($chronicle, $base_url); ?>
        <?php endforeach; ?>
    </div>

    <p class="owc-no-results-filtered" style="display:none;"><?php esc_html_e('No chronicles match your filters.', 'owbn-entities'); ?></p>
<?php
    return ob_get_clean();
}

/**
 * A chronicle's genres as filter tokens: "f:<family>" for each genre family and "g:<family> - <genre>" for each named
 * genre within one, lowercase, so "Camarilla" and "Vampire - Camarilla" give the same two tokens.
 *
 * @param array $genres The chronicle's genres.
 * @return array<string,string> token => the genre's own label ("Camarilla"), or the family's for a family token.
 */
function owc_list_genre_tokens(array $genres): array
{
    $tokens = [];
    foreach ($genres as $genre) {
        $genre = trim((string) $genre);
        if ('' === $genre) {
            continue;
        }
        $family = function_exists('owc_genre_family') ? owc_genre_family($genre) : $genre;
        $tokens['f:' . strtolower($family)] = $family;
        $sub = false !== strpos($genre, ' - ') ? trim(substr($genre, strpos($genre, ' - ') + 3)) : (0 !== strcasecmp($family, $genre) ? $genre : '');
        if ('' !== $sub) {
            $key = 'g:' . strtolower($family) . ' - ' . trim(preg_replace('/[\s-]+/', ' ', strtolower($sub)));
            if (!isset($tokens[$key])) {
                $tokens[$key] = str_replace('-', ' ', $sub);
            }
        }
    }
    return $tokens;
}

/**
 * The filter bar's dropdowns for a list of chronicles: genres grouped by family, OWBN regions, countries and game
 * types, each holding only values the list has.
 *
 * @param array $chronicles Published chronicles.
 * @return string HTML.
 */
function owc_render_chronicles_filters(array $chronicles): string
{
    $families = [];
    $regions  = [];
    $countries = [];
    $types    = [];
    $languages = [];
    foreach ($chronicles as $chronicle) {
        foreach (owc_list_genre_tokens((array) ($chronicle['genres'] ?? [])) as $token => $label) {
            $family = strtolower(owc_list_token_family($token));
            if (0 === strpos($token, 'f:')) {
                $families[$family]['label'] = $families[$family]['label'] ?? $label;
            } else {
                $families[$family]['subs'][$token] = $families[$family]['subs'][$token] ?? $label;
            }
        }
        $region = trim((string) ($chronicle['chronicle_region'] ?? ''));
        if ('' !== $region) {
            $regions[strtolower($region)] = $region;
        }
        $country = owc_list_country($chronicle);
        if ('' !== $country) {
            $countries[strtolower($country)] = $country;
        }
        $type = trim((string) ($chronicle['game_type'] ?? ''));
        if ('' !== $type) {
            $types[strtolower($type)] = $type;
        }
        $language = owc_chronicle_language($chronicle);
        if ('' !== $language) {
            $languages[$language] = owc_language_label($language);
        }
    }
    uksort($families, static function ($a, $b) {
        return [('other' === $a), $a] <=> [('other' === $b), $b];
    });
    ksort($regions);
    ksort($countries);
    ksort($types);
    uksort($languages, static function ($a, $b) {
        return [('other' === $a), $a] <=> [('other' === $b), $b];
    });

    $html  = '<select id="owc-filter-genre" class="owc-filter-select" data-attr="genres" aria-label="' . esc_attr__('Genre', 'owbn-entities') . '">';
    $html .= '<option value="">' . esc_html__('All genres', 'owbn-entities') . '</option>';
    foreach ($families as $key => $family) {
        $label = (string) ($family['label'] ?? $key);
        if (empty($family['subs'])) {
            $html .= '<option value="' . esc_attr('f:' . $key) . '">' . esc_html($label) . '</option>';
            continue;
        }
        asort($family['subs'], SORT_NATURAL | SORT_FLAG_CASE);
        $html .= '<optgroup label="' . esc_attr($label) . '">';
        /* translators: %s: genre family, e.g. Vampire */
        $html .= '<option value="' . esc_attr('f:' . $key) . '">' . esc_html(sprintf(__('%s (all)', 'owbn-entities'), $label)) . '</option>';
        foreach ($family['subs'] as $token => $sub) {
            $html .= '<option value="' . esc_attr($token) . '">' . esc_html($sub) . '</option>';
        }
        $html .= '</optgroup>';
    }
    $html .= '</select>';

    $simple = [
        ['region', __('Region', 'owbn-entities'), __('All regions', 'owbn-entities'), $regions],
        ['country', __('Country', 'owbn-entities'), __('All countries', 'owbn-entities'), $countries],
        ['type', __('Game type', 'owbn-entities'), __('All game types', 'owbn-entities'), $types],
    ];
    if ($languages) {
        $simple[] = ['language', __('Language', 'owbn-entities'), __('All languages', 'owbn-entities'), $languages];
    }
    foreach ($simple as $select) {
        $html .= '<select id="owc-filter-' . esc_attr($select[0]) . '" class="owc-filter-select" data-attr="' . esc_attr($select[0]) . '" aria-label="' . esc_attr($select[1]) . '">';
        $html .= '<option value="">' . esc_html($select[2]) . '</option>';
        foreach ($select[3] as $value => $label) {
            $html .= '<option value="' . esc_attr($value) . '"' . ('country' === $select[0] ? ' data-country="' . esc_attr($label) . '"' : '') . '>' . esc_html($label) . '</option>';
        }
        $html .= '</select>';
    }
    return $html;
}

/**
 * The family part of a genre token: "Vampire" for "f:vampire" or "g:vampire - camarilla".
 *
 * @param string $token A token from owc_list_genre_tokens().
 * @return string
 */
function owc_list_token_family(string $token): string
{
    $body = substr($token, 2);
    return 0 === strpos($token, 'g:') ? (string) strstr($body, ' - ', true) : $body;
}

/**
 * A chronicle's country code from its first out-of-character location, '' when it has none or the value isn't one.
 *
 * @param array $chronicle Chronicle data.
 * @return string
 */
function owc_list_country(array $chronicle): string
{
    $loc = $chronicle['ooc_locations'] ?? [];
    if (is_array($loc) && isset($loc[0]) && is_array($loc[0])) {
        $loc = $loc[0];
    }
    $country = strtoupper(trim((string) (is_array($loc) ? ($loc['country'] ?? '') : '')));
    $valid   = function_exists('owc_is_country_code') ? owc_is_country_code($country) : (bool) preg_match('/^[A-Z]{2}$/', $country);
    return $valid ? $country : '';
}

/**
 * A chronicle's first out-of-character location (stored as one location or a list of them).
 *
 * @param array $chronicle Chronicle data.
 * @return array
 */
function owc_chronicle_ooc(array $chronicle): array
{
    $ooc = $chronicle['ooc_locations'] ?? [];
    if (is_array($ooc) && isset($ooc[0]) && is_array($ooc[0])) {
        $ooc = $ooc[0];
    }
    return is_array($ooc) ? $ooc : [];
}

/**
 * The data a Find a Game row carries for its dropdown filters: genre tokens, region, country, game type, language.
 *
 * @param array $chronicle Chronicle data.
 * @return string HTML attributes, escaped.
 */
function owc_chronicle_row_data_attrs(array $chronicle): string
{
    $genres = $chronicle['genres'] ?? [];
    return 'data-genres="' . esc_attr('|' . implode('|', array_keys(owc_list_genre_tokens(is_array($genres) ? $genres : [$genres]))) . '|') . '"'
        . ' data-region="' . esc_attr(strtolower(trim((string) ($chronicle['chronicle_region'] ?? '')))) . '"'
        . ' data-country="' . esc_attr(strtolower(owc_list_country($chronicle))) . '"'
        . ' data-type="' . esc_attr(strtolower(trim((string) ($chronicle['game_type'] ?? '')))) . '"'
        . ' data-language="' . esc_attr(owc_chronicle_language($chronicle)) . '"';
}

/**
 * A Find a Game row's Type cell: the game type, and the players under it.
 *
 * @param array $chronicle Chronicle data.
 * @return string HTML, escaped; '' when there is nothing to show.
 */
function owc_chronicle_type_cell_html(array $chronicle): string
{
    $html    = esc_html(trim((string) ($chronicle['game_type'] ?? '')));
    $players = trim((string) ($chronicle['active_player_count'] ?? ''));
    if ('' !== $players) {
        /* translators: %s: rough number of active players, e.g. 21-30 */
        $html .= ' <span class="owc-players">' . esc_html(sprintf(__('%s players', 'owbn-entities'), $players)) . '</span>';
    }
    return trim($html);
}

/**
 * Find a Game's filter bar: the dropdowns, one search box over the given columns, and Clear.
 *
 * @param array  $chronicles     The chronicles listed.
 * @param string $search_columns Comma-separated positions of the name, state and city columns in each row.
 * @return string HTML.
 */
function owc_render_chronicles_filter_bar(array $chronicles, string $search_columns = '0,3,4'): string
{
    return '<div class="owc-chronicles-filters">'
        . owc_render_chronicles_filters($chronicles)
        . '<input type="search" id="owc-filter-search" class="owc-filter-input" placeholder="' . esc_attr__('Search name, state or city', 'owbn-entities') . '" aria-label="' . esc_attr__('Search name, state or city', 'owbn-entities') . '" data-column="' . esc_attr($search_columns) . '">'
        . '<button type="button" id="owc-clear-filters" class="owc-clear-filters">' . esc_html__('Clear', 'owbn-entities') . '</button>'
        . '</div>';
}

/**
 * Render single chronicle row.
 *
 * @param array  $chronicle Chronicle data
 * @param string $base_url  Base URL for detail page
 * @return string HTML output
 */
function owc_render_chronicle_row(array $chronicle, string $base_url): string
{
    $slug = $chronicle['slug'] ?? $chronicle['chronicle_slug'] ?? '';
    $title = $chronicle['title'] ?? __('Untitled', 'owbn-entities');
    $url = $base_url ? add_query_arg('slug', $slug, $base_url) : '#';

    // Location fields
    $ooc   = owc_chronicle_ooc($chronicle);
    $state = $ooc['region'] ?? '';
    $city  = $ooc['city'] ?? '';

    // Region
    $region = $chronicle['chronicle_region'] ?? '';

    // Genres
    $genres = $chronicle['genres'] ?? [];
    $genres_display = is_array($genres) ? implode(', ', $genres) : $genres;

    // Type, with the players when known
    $type_cell = owc_chronicle_type_cell_html($chronicle);

    // Status flags
    $status = owc_format_status($chronicle);

    ob_start();
?>
    <div class="owc-list-row" <?php echo owc_chronicle_row_data_attrs($chronicle); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped where it is built. ?>>
        <div class="owc-col-title">
            <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
        </div>
        <div class="owc-col-genres<?php echo $genres_display ? '' : ' owc-cell-empty'; ?>" data-label="<?php esc_attr_e('Genres', 'owbn-entities'); ?>">
            <?php echo esc_html($genres_display); ?>
        </div>
        <div class="owc-col-region<?php echo $region ? '' : ' owc-cell-empty'; ?>" data-label="<?php esc_attr_e('Region', 'owbn-entities'); ?>">
            <?php echo esc_html($region); ?>
        </div>
        <div class="owc-col-state<?php echo $state ? '' : ' owc-cell-empty'; ?>" data-label="<?php esc_attr_e('State/Province', 'owbn-entities'); ?>">
            <?php echo esc_html($state); ?>
        </div>
        <div class="owc-col-city<?php echo $city ? '' : ' owc-cell-empty'; ?>" data-label="<?php esc_attr_e('City', 'owbn-entities'); ?>">
            <?php echo esc_html($city); ?>
        </div>
        <div class="owc-col-type<?php echo '' !== $type_cell ? '' : ' owc-cell-empty'; ?>" data-label="<?php esc_attr_e('Type', 'owbn-entities'); ?>">
            <?php echo $type_cell; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped where it is built. ?>
        </div>
        <div class="owc-col-status<?php echo $status ? '' : ' owc-cell-empty'; ?>" data-label="<?php esc_attr_e('Status', 'owbn-entities'); ?>">
            <?php echo esc_html($status); ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Format status flags.
 *
 * @param array $chronicle Chronicle data
 * @return string Status text
 */
function owc_format_status(array $chronicle): string
{
    $flags = [];

    if (!empty($chronicle['chronicle_probationary']) && $chronicle['chronicle_probationary'] !== '0') {
        $flags[] = __('Probationary', 'owbn-entities');
    }

    if (!empty($chronicle['chronicle_satellite']) && $chronicle['chronicle_satellite'] !== '0') {
        $flags[] = __('Satellite', 'owbn-entities');
    }

    if (empty($flags)) {
        $flags[] = __('Full Member', 'owbn-entities');
    }

    return implode(', ', $flags);
}
