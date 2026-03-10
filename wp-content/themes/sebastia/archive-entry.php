<?php
// Entries list — rendered inside the home page index panel via JS,
// but also accessible directly at /entries/ as a fallback.
get_header();
$entries = sebastia_get_entries();
?>

<div id="search-overlay" hidden>
  <div class="index-container">

    <div class="search-input-wrapper">
      <svg class="icon-search-small" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32">
        <path d="M11 4a7 7 0 1 1 0 14 7 7 0 0 1 0-14zm5 12l5 5" stroke-width="0.8" fill="none" stroke-linecap="square"/>
      </svg>
      <input type="text" id="search-input" placeholder="">
    </div>

    <div class="index-header-wrapper">
      <div class="index-header index-grid">
        <div class="header-filter" data-type="title">
          <?php echo esc_html(sebastia_t('title')); ?>
          <span class="caret">[ ]</span>
          <div class="filter-dropdown" hidden></div>
        </div>
        <div class="header-filter" data-type="county">
          <?php echo esc_html(sebastia_t('county')); ?>
          <span class="caret">[ ]</span>
          <div class="filter-dropdown" hidden></div>
        </div>
        <div class="header-filter" data-type="municipality">
          <?php echo esc_html(sebastia_t('municipality')); ?>
          <span class="caret">[ ]</span>
          <div class="filter-dropdown" hidden></div>
        </div>
        <div class="header-filter" data-type="type">
          <?php echo esc_html(sebastia_t('type')); ?>
          <span class="caret">[ ]</span>
          <div class="filter-dropdown" hidden></div>
        </div>
        <div class="header-filter" data-type="stone">
          <?php echo esc_html(sebastia_t('stone')); ?>
          <span class="caret">[ ]</span>
          <div class="filter-dropdown" hidden></div>
        </div>
      </div>
    </div>

    <div class="index-entries-wrapper">
      <?php while ($entries->have_posts()): $entries->the_post();
          $slug         = get_post_field('post_name', get_the_ID());
          $county       = get_post_meta(get_the_ID(), 'county', true);
          $municipality = get_post_meta(get_the_ID(), 'municipality', true);
          $type         = get_post_meta(get_the_ID(), 'type', true);
          $stone        = get_post_meta(get_the_ID(), 'stone', true);
      ?>
      <button
        class="entry-index-item index-grid"
        data-slug="<?php echo esc_attr($slug); ?>"
        data-url="<?php echo esc_url(get_permalink()); ?>"
      >
        <div><?php the_title(); ?></div>
        <div><?php echo esc_html($county); ?></div>
        <div><?php echo esc_html($municipality); ?></div>
        <div><?php echo esc_html($type); ?></div>
        <div><?php echo esc_html($stone); ?></div>
      </button>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>

  </div>
</div>

<?php get_footer(); ?>
