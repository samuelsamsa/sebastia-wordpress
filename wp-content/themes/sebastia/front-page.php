<?php
// ── Stone panel AJAX handler ──────────────────────────────────────────────────
// home.js fetches ?stone={base-slug} and expects back the entry-card HTML fragment.
if (!empty($_GET['stone'])) {
    $stone_slug = sanitize_title($_GET['stone']); // base slug, e.g. "kirkenes"
    $lang       = function_exists('pll_current_language') ? pll_current_language() : 'no';
    $post_slug  = $lang === 'en' ? $stone_slug . '-en' : $stone_slug;

    $post = get_posts([
        'name'           => $post_slug,
        'post_type'      => 'entry',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => [[
            'key'   => '_entry_lang',
            'value' => $lang,
        ]],
    ]);

    // Fall back to Norwegian if English entry not found
    if (empty($post) && $lang === 'en') {
        $post = get_posts([
            'name'           => $stone_slug,
            'post_type'      => 'entry',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => [[
                'key'   => '_entry_lang',
                'value' => 'no',
            ]],
        ]);
    }

    if (empty($post)) {
        http_response_code(404);
        echo 'Stone not found';
        exit;
    }

    $p     = $post[0];
    $id    = $p->ID;
    $order = $p->menu_order;

    $fields = [
        'donor'        => get_post_meta($id, 'donor', true),
        'year'         => get_post_meta($id, 'year', true),
        'type'         => get_post_meta($id, 'type', true),
        'stone'        => get_post_meta($id, 'stone', true),
        'municipality' => get_post_meta($id, 'municipality', true),
        'origin'       => get_post_meta($id, 'origin', true),
        'county'       => get_post_meta($id, 'county', true),
        'sizequantity' => get_post_meta($id, 'sizequantity', true),
    ];

    $notes     = get_the_content(null, false, $id);
    $permalink = get_permalink($id);
    $title     = get_the_title($id);

    echo '<article class="entry-card entry-card-home">';
    echo '<div class="space">' . $order . 'a</div>';
    echo '<header>';
    echo '<h2>' . esc_html($title) . '</h2>';
    echo '<p><a href="' . esc_url($permalink) . '">' . esc_html(sebastia_t('explore')) . '</a></p>';
    echo '</header>';
    echo '<div class="space">' . $order . 'b</div>';
    echo '<ul class="meta">';
    foreach ($fields as $key => $value) {
        echo '<li><span class="entry-label">' . esc_html(sebastia_t($key)) . '</span><span>' . esc_html($value) . '</span></li>';
    }
    echo '</ul>';
    echo '<div class="space">' . $order . 'c</div>';
    if ($notes) {
        echo '<section class="notes">' . wp_kses_post($notes) . '</section>';
    }
    echo '</article>';
    exit;
}

// ── Normal page render ────────────────────────────────────────────────────────
get_header();
?>

<div class="mosaic-wrapper">
  <div class="mosaic-rotated">
    <img
      src="<?php echo get_template_directory_uri(); ?>/assets/images/mapping_14_2_drone.jpg"
      alt=""
      loading="eager"
      fetchpriority="high"
      decoding="async"
    >
    <?php include get_template_directory() . '/partials/new_backg.svg.php'; ?>
    <?php include get_template_directory() . '/partials/new_mosaic.svg.php'; ?>
    <?php include get_template_directory() . '/partials/c.svg.php'; ?>
  </div>

  <div class="mosaic-street-markers" aria-hidden="true">
    <span class="street-marker marker-left marker-desktop">Høyblokka</span>
    <span class="street-marker marker-right marker-desktop">Akersgata</span>
    <span class="street-marker marker-bottom marker-desktop">A-Blokka</span>
    <span class="street-marker marker-left marker-mobile">A-Blokka</span>
    <span class="street-marker marker-right marker-mobile">Lindenalle</span>
  </div>
</div>

<?php $entries = sebastia_get_entries(); ?>

<div id="search-overlay">
  <div class="index-container">
    <div class="search-input-wrapper">
      <svg class="icon-search-small" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32">
        <path d="M11 4a7 7 0 1 1 0 14 7 7 0 0 1 0-14zm5 12l5 5" stroke-width="0.8" fill="none" stroke-linecap="square"/>
      </svg>
      <input type="text" id="search-input" placeholder="">
    </div>
    <div class="index-header-wrapper">
      <div class="index-header index-grid">
        <div class="header-filter" data-type="title"><?php echo esc_html(sebastia_t('title')); ?><span class="caret">[ ]</span><div class="filter-dropdown" hidden></div></div>
        <div class="header-filter" data-type="county"><?php echo esc_html(sebastia_t('county')); ?><span class="caret">[ ]</span><div class="filter-dropdown" hidden></div></div>
        <div class="header-filter" data-type="municipality"><?php echo esc_html(sebastia_t('municipality')); ?><span class="caret">[ ]</span><div class="filter-dropdown" hidden></div></div>
        <div class="header-filter" data-type="type"><?php echo esc_html(sebastia_t('type')); ?><span class="caret">[ ]</span><div class="filter-dropdown" hidden></div></div>
        <div class="header-filter" data-type="stone"><?php echo esc_html(sebastia_t('stone')); ?><span class="caret">[ ]</span><div class="filter-dropdown" hidden></div></div>
      </div>
    </div>
    <div class="index-entries-wrapper">
      <?php while ($entries->have_posts()): $entries->the_post();
        // Use the base slug (strip -en suffix) so it matches the SVG data-name attribute
        $post_name = get_post_field('post_name', get_the_ID());
        $base_slug = preg_replace('/-en$/', '', $post_name);
      ?>
      <button
        class="entry-index-item index-grid"
        data-slug="<?php echo esc_attr($base_slug); ?>"
        data-url="<?php echo esc_url(get_permalink()); ?>"
      >
        <div><?php the_title(); ?></div>
        <div><?php echo esc_html(get_post_meta(get_the_ID(), 'county', true)); ?></div>
        <div><?php echo esc_html(get_post_meta(get_the_ID(), 'municipality', true)); ?></div>
        <div><?php echo esc_html(get_post_meta(get_the_ID(), 'type', true)); ?></div>
        <div><?php echo esc_html(get_post_meta(get_the_ID(), 'stone', true)); ?></div>
      </button>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
  </div>
</div>

<div id="stone-panel-wrapper">
  <button class="entry-close" aria-label="Close entry">
    <svg viewBox="0 0 24 24" width="40" height="40" aria-hidden="true">
      <path d="M4 4l16 16M20 4L4 20" stroke="black" stroke-width="0.8" stroke-linecap="round"/>
    </svg>
  </button>
  <div id="stone-panel"></div>
</div>

<?php get_footer(); ?>
