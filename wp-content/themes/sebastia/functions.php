<?php
// ─── Disable admin bar on frontend ────────────────────────────────────────────
add_filter('show_admin_bar', '__return_false');

// ─── Custom post type ─────────────────────────────────────────────────────────
add_action('init', function () {
    register_post_type('entry', [
        'labels'       => ['name' => 'Entries', 'singular_name' => 'Entry'],
        'public'       => true,
        'show_in_rest' => true,
        'supports'     => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'menu_icon'    => 'dashicons-location-alt',
        'rewrite'      => ['slug' => 'entry'],
        'has_archive'  => 'entries',
    ]);
});

// ─── Body class: remove 'home' — the CSS .home selector targets <header class="home">,
//     not <body>. Kirby puts 'home' on the header element, not the body.
add_filter('body_class', function ($classes) {
    return array_diff($classes, ['home']);
});

// ─── Enqueue assets ───────────────────────────────────────────────────────────
add_action('wp_enqueue_scripts', function () {
    $v = filemtime(get_template_directory() . '/assets/css/base.css') ?: '1';

    wp_enqueue_style(
        'sebastia-base',
        get_template_directory_uri() . '/assets/css/index.css',
        [],
        $v
    );

    wp_enqueue_style(
        'sebastia-overrides',
        get_template_directory_uri() . '/assets/css/wp-overrides.css',
        ['sebastia-base'],
        $v
    );

    wp_enqueue_script(
        'sebastia-site',
        get_template_directory_uri() . '/assets/js/site.js',
        [],
        $v,
        true
    );

    // Page-specific scripts
    if (is_front_page()) {
        wp_enqueue_script('sebastia-home', get_template_directory_uri() . '/assets/js/home.js', [], $v, true);
    }
    if (is_singular('entry')) {
        wp_enqueue_script('sebastia-entry', get_template_directory_uri() . '/assets/js/entry.js', [], $v, true);
    }
    // gallery.js, timeline.js, about.js are enqueued directly in their template files
});

// ─── Translation strings ──────────────────────────────────────────────────────
function sebastia_t(string $key): string {
    $lang = function_exists('pll_current_language') ? pll_current_language() : 'no';

    $strings = [
        'no' => [
            'title'        => 'Tittel',
            'donor'        => 'Donor',
            'year'         => 'År',
            'type'         => 'Type',
            'stone'        => 'Stein',
            'municipality' => 'Kommune',
            'origin'       => 'Opprinnelse',
            'county'       => 'Fylke',
            'notes'        => 'Notater',
            'sizequantity' => 'Størrelse/Antall',
            'explore'      => 'Les mer',
            'map'          => 'Kart',
            'protection'   => 'Kulturminneverdi',
        ],
        'en' => [
            'title'        => 'Title',
            'donor'        => 'Donor',
            'year'         => 'Year',
            'type'         => 'Type',
            'stone'        => 'Stone',
            'municipality' => 'Municipality',
            'origin'       => 'Origin',
            'county'       => 'County',
            'notes'        => 'Notes',
            'sizequantity' => 'Size/Quantity',
            'explore'      => 'Read more',
            'map'          => 'Map',
            'protection'   => 'Cultural heritage protection',
        ],
    ];

    return $strings[$lang][$key] ?? $strings['no'][$key] ?? $key;
}

// ─── Entry meta helper ────────────────────────────────────────────────────────
function sebastia_meta(string $key): string {
    return esc_html(get_post_meta(get_the_ID(), $key, true) ?: '');
}

// ─── Get all entries ordered by menu_order for the index ─────────────────────
function sebastia_get_entries(): WP_Query {
    $lang     = function_exists('pll_current_language') ? pll_current_language() : 'no';
    $lang_val = $lang === 'en' ? 'en' : 'no';

    return new WP_Query([
        'post_type'      => 'entry',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'meta_query'     => [[
            'key'   => '_entry_lang',
            'value' => $lang_val,
        ]],
    ]);
}

// ─── Entry fields metabox ─────────────────────────────────────────────────────
add_action('add_meta_boxes', function () {
    add_meta_box(
        'sebastia_entry_fields',
        'Entry Fields',
        'sebastia_entry_fields_render',
        'entry',
        'normal',
        'high'
    );
});

function sebastia_entry_fields_render(WP_Post $post): void {
    wp_nonce_field('sebastia_entry_fields', 'sebastia_entry_nonce');

    $fields = [
        'donor'        => 'Donor',
        'year'         => 'Year',
        'type'         => 'Type',
        'stone'        => 'Stone',
        'municipality' => 'Municipality',
        'origin'       => 'Origin',
        'county'       => 'County',
        'sizequantity' => 'Size / Quantity',
        'protection'   => 'Protection',
    ];

    echo '<table style="width:100%;border-collapse:collapse">';
    foreach ($fields as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<tr><th style="text-align:left;padding:6px 10px 6px 0;width:140px;vertical-align:top;font-weight:600">'
            . esc_html($label) . '</th><td style="padding:4px 0">'
            . '<input type="text" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" style="width:100%">'
            . '</td></tr>';
    }
    echo '</table>';

    // Legacy: kirby_folder was used before the WP Media Library migration.
    // Shown read-only for reference; no longer affects anything.
    $kirby_folder = get_post_meta($post->ID, 'kirby_folder', true);
    if ($kirby_folder) {
        echo '<p style="font-size:11px;color:#999;margin:8px 0 0">'
            . 'Legacy image folder: <code>' . esc_html($kirby_folder) . '</code></p>';
    }

    // ── Images (WP Media Library gallery) ────────────────────────────────────
    wp_enqueue_media();

    $gallery_ids = json_decode(get_post_meta($post->ID, '_gallery', true) ?: '[]', true);
    if (!is_array($gallery_ids)) $gallery_ids = [];

    echo '<hr style="margin:16px 0"><h4 style="margin:0 0 12px">Images</h4>';
    echo '<div id="sebastia-gallery-thumbs" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px">';
    foreach ($gallery_ids as $att_id) {
        $src = wp_get_attachment_image_src((int) $att_id, 'thumbnail');
        if (!$src) continue;
        echo '<div class="sebastia-thumb" data-id="' . esc_attr($att_id) . '" style="position:relative">'
            . '<img src="' . esc_url($src[0]) . '" style="width:100px;height:75px;object-fit:cover;display:block;border:1px solid #ddd">'
            . '<button type="button" class="sebastia-remove-thumb" '
            . 'style="display:block;width:100%;padding:2px;background:#c00;color:#fff;border:none;cursor:pointer;font-size:11px;margin-top:2px">'
            . 'Remove</button></div>';
    }
    echo '</div>';
    echo '<input type="hidden" id="sebastia-gallery-ids" name="sebastia_gallery_ids" '
        . 'value="' . esc_attr(implode(',', $gallery_ids)) . '">';
    echo '<button type="button" id="sebastia-add-images" class="button">Add / Edit Images</button>';
    ?>
    <script>
    jQuery(function($){
        var frame;
        $('#sebastia-add-images').on('click', function(e){
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({ title: 'Select Images', button: { text: 'Add to Gallery' }, multiple: true });
            frame.on('select', function(){
                frame.state().get('selection').each(function(a){
                    var d = a.toJSON();
                    var url = (d.sizes && d.sizes.thumbnail) ? d.sizes.thumbnail.url : d.url;
                    addThumb(d.id, url);
                });
                updateIds();
            });
            frame.open();
        });
        $(document).on('click', '.sebastia-remove-thumb', function(){
            $(this).closest('.sebastia-thumb').remove();
            updateIds();
        });
        function addThumb(id, url){
            if ($('#sebastia-gallery-thumbs [data-id="' + id + '"]').length) return;
            $('#sebastia-gallery-thumbs').append(
                '<div class="sebastia-thumb" data-id="' + id + '" style="position:relative">'
                + '<img src="' + url + '" style="width:100px;height:75px;object-fit:cover;display:block;border:1px solid #ddd">'
                + '<button type="button" class="sebastia-remove-thumb" style="display:block;width:100%;padding:2px;background:#c00;color:#fff;border:none;cursor:pointer;font-size:11px;margin-top:2px">Remove</button>'
                + '</div>'
            );
        }
        function updateIds(){
            var ids = [];
            $('#sebastia-gallery-thumbs .sebastia-thumb').each(function(){ ids.push($(this).data('id')); });
            $('#sebastia-gallery-ids').val(ids.join(','));
        }
    });
    </script>
    <?php
}

add_action('save_post_entry', function (int $post_id): void {
    if (!isset($_POST['sebastia_entry_nonce']) || !wp_verify_nonce($_POST['sebastia_entry_nonce'], 'sebastia_entry_fields')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $fields = ['donor', 'year', 'type', 'stone', 'municipality', 'origin', 'county', 'sizequantity', 'protection'];
    foreach ($fields as $key) {
        if (isset($_POST[$key])) {
            update_post_meta($post_id, $key, sanitize_text_field($_POST[$key]));
        }
    }

    // Save gallery (comma-separated attachment IDs → JSON array)
    if (isset($_POST['sebastia_gallery_ids'])) {
        $ids = array_values(array_filter(array_map('intval', explode(',', $_POST['sebastia_gallery_ids']))));
        update_post_meta($post_id, '_gallery', wp_json_encode($ids));
    }
});

// ─── Language switcher URL ────────────────────────────────────────────────────
function sebastia_lang_url(string $lang): string {
    if (function_exists('pll_home_url') && is_singular()) {
        $translations = pll_get_post_translations(get_the_ID());
        if (!empty($translations[$lang])) {
            return get_permalink($translations[$lang]);
        }
    }
    return function_exists('pll_home_url') ? pll_home_url($lang) : home_url('/');
}
