<?php
/*
 * Template Name: About
 */

add_action('wp_enqueue_scripts', function () {
    $v = filemtime(get_template_directory() . '/assets/js/about.js') ?: '1';
    wp_enqueue_script('sebastia-about', get_template_directory_uri() . '/assets/js/about.js', [], $v, true);
});

get_header();
?>

<div class="entry-background">
  <article class="about-grid">
    <?php the_content(); ?>
  </article>
</div>

<?php get_footer(); ?>
