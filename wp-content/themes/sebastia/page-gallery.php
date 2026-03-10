<?php
/*
 * Template Name: Gallery
 */

add_action('wp_enqueue_scripts', function () {
    $v = filemtime(get_template_directory() . '/assets/js/gallery.js') ?: '1';
    wp_enqueue_script('sebastia-gallery', get_template_directory_uri() . '/assets/js/gallery.js', [], $v, true);
});

get_header();

$all_entries = [];
$entries = sebastia_get_entries();
while ($entries->have_posts()) {
    $entries->the_post();
    $gids = json_decode(get_post_meta(get_the_ID(), '_gallery', true) ?: '[]', true);
    $all_entries[] = [
        'title'       => get_the_title(),
        'url'         => get_permalink(),
        'gallery_ids' => is_array($gids) ? $gids : [],
        'muni'        => get_post_meta(get_the_ID(), 'municipality', true),
    ];
}
wp_reset_postdata();

shuffle($all_entries);
?>

<div class="gallery-wrapper">

  <div class="gallery-entries-wrapper is-loading">
    <?php foreach ($all_entries as $entry):
        $first_id  = $entry['gallery_ids'][0] ?? null;
        $img_src   = $first_id ? wp_get_attachment_image_src((int) $first_id, 'large') : null;
        $image_url = $img_src ? $img_src[0] : null;
    ?>
    <div class="gallery-entry-item is-link">
      <a href="<?php echo esc_url($entry['url']); ?>" class="gallery-entry-link">
        <div class="gallery-entry-image-wrapper">
          <?php if ($image_url): ?>
          <img
            src="<?php echo esc_url($image_url); ?>"
            alt="<?php echo esc_attr($entry['title']); ?>"
            width="400"
            height="400"
            loading="lazy"
            decoding="async"
          >
          <?php else: ?>
          <div class="no-image-placeholder">—</div>
          <?php endif; ?>
          <div class="gallery-entry-title">
            <p><?php echo esc_html($entry['title']); ?></p>
            <p><?php echo esc_html($entry['muni']); ?></p>
          </div>
        </div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php get_footer(); ?>
