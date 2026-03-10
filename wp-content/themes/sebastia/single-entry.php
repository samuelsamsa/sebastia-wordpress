<?php get_header(); ?>

<?php while (have_posts()): the_post();
    $id           = get_the_ID();
    $slug         = get_post_field('post_name', $id);
    $order        = (int) get_post_field('menu_order', $id);
    $title        = get_the_title();
    $donor        = sebastia_meta('donor');
    $year         = sebastia_meta('year');
    $type         = sebastia_meta('type');
    $stone        = sebastia_meta('stone');
    $municipality = sebastia_meta('municipality');
    $origin       = sebastia_meta('origin');
    $county       = sebastia_meta('county');
    $sizequantity = sebastia_meta('sizequantity');
    $protection   = sebastia_meta('protection');

    // Base slug (strip -en suffix) for SVG data-name matching
    $base_slug = preg_replace('/-en$/', '', $slug);

    // Build entry titles + URLs map for JS (navigation between entries)
    $entries_query = sebastia_get_entries();
    $entry_titles  = [];
    $entry_urls    = [];
    foreach ($entries_query->posts as $e) {
        $entry_base = preg_replace('/-en$/', '', get_post_field('post_name', $e->ID));
        $entry_titles[$entry_base] = $e->post_title;
        $entry_urls[$entry_base]   = get_permalink($e->ID);
    }
    wp_reset_postdata();

    $entry_config = json_encode([
        'slug'   => $base_slug,
        'titles' => $entry_titles,
        'urls'   => $entry_urls,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Images: WP Media Library attachment IDs stored in _gallery
    $gallery_ids = json_decode(get_post_meta($id, '_gallery', true) ?: '[]', true);
    if (!is_array($gallery_ids)) $gallery_ids = [];
?>

<div class="entry-background">
  <article class="entry-card entry">

    <div class="section1 entry-main-panel" data-fade>
      <div class="space"><?php echo $order; ?>a</div>

      <header>
        <h2><?php echo esc_html($title); ?></h2>
      </header>

      <div class="space"><?php echo $order; ?>b</div>

      <ul class="meta">
        <?php
        $meta_fields = [
            'donor'        => $donor,
            'year'         => $year,
            'type'         => $type,
            'stone'        => $stone,
            'municipality' => $municipality,
            'origin'       => $origin,
            'county'       => $county,
            'sizequantity' => $sizequantity,
        ];
        foreach ($meta_fields as $key => $value):
            if (!$value) continue;
        ?>
        <li>
          <span class="entry-label"><?php echo esc_html(sebastia_t($key)); ?></span>
          <span><?php echo esc_html($value); ?></span>
        </li>
        <?php endforeach; ?>
      </ul>

      <div class="space"><?php echo $order; ?>c</div>

      <div class="entry-mosaic lightbox-trigger">
        <?php include get_template_directory() . '/partials/mosaic_entry.svg.php'; ?>
      </div>

      <div class="space"><?php echo $order; ?>d</div>

      <?php if (get_the_content()): ?>
      <section class="notes">
        <?php the_content(); ?>
      </section>
      <?php endif; ?>
      <div class="space"><?php echo $order; ?>e</div>

      <div class="entry-mosaic">
        <?php include get_template_directory() . '/partials/norway.svg.php'; ?>
      </div>

      
    </div>

    <div class="section2 entry-media-panel" data-fade>
      <?php if (!empty($gallery_ids)): ?>
      <section class="gallery entry-gallery">
        <?php foreach ($gallery_ids as $att_id):
            $img_src = wp_get_attachment_image_src((int) $att_id, 'large');
            if (!$img_src) continue;
        ?>
        <figure class="gallery-entry-item">
          <img
            src="<?php echo esc_url($img_src[0]); ?>"
            alt="<?php echo esc_attr($title); ?>"
            loading="lazy"
            decoding="async"
            fetchpriority="low"
          >
        </figure>
        <?php endforeach; ?>
      </section>
      <?php endif; ?>

      <div class="space-right"><?php echo $order; ?>f</div>
    </div>

  </article>
</div>

<div class="lightbox" hidden>
  <button class="lightbox-close" aria-label="Close">
    <svg viewBox="0 0 24 24" width="40" height="40">
      <path d="M4 4l16 16M20 4L4 20" stroke="black" stroke-width="0.8" stroke-linecap="round"/>
    </svg>
  </button>
  <div class="lightbox-media"></div>
  <div class="lightbox-caption"></div>
</div>

<script id="entry-page-config" type="application/json"><?php echo $entry_config; ?></script>

<?php endwhile; ?>
<?php get_footer(); ?>
