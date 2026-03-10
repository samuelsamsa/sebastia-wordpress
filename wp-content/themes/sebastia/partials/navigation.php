<?php $mobile = $args['mobile'] ?? false; ?>
<nav<?php echo $mobile ? '' : ' id="page-menu"'; ?> class="page-menu">
  <ul>
    <?php
    $home_active = is_front_page() ? ' class="is-active"' : '';
    ?>
    <li<?php echo $home_active; ?>>
      <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(sebastia_t('map')); ?></a>
    </li>
    <?php
    $sample_id = get_page_by_path('sample-page') ? get_page_by_path('sample-page')->ID : 0;
    $current_lang = function_exists('pll_current_language') ? pll_current_language() : 'no';
    $pages = get_posts([
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'lang'           => $current_lang,
        'post__not_in'   => array_filter([$sample_id]),
    ]);
    foreach ($pages as $p):
        $active = (get_the_ID() === $p->ID) ? ' class="is-active"' : '';
    ?>
    <li<?php echo $active; ?>>
      <a href="<?php echo get_permalink($p->ID); ?>"><?php echo esc_html($p->post_title); ?></a>
    </li>
    <?php endforeach; ?>
  </ul>
</nav>

<nav class="languages">
  <ul>
    <?php
    $current_lang = function_exists('pll_current_language') ? pll_current_language() : 'no';
    foreach (['no', 'en'] as $lang_code):
        $active = ($current_lang === $lang_code) ? ' class="active"' : '';
        $url    = sebastia_lang_url($lang_code);
    ?>
    <li<?php echo $active; ?>>
      <a href="<?php echo esc_url($url); ?>" hreflang="<?php echo $lang_code; ?>"><?php echo $lang_code; ?></a>
    </li>
    <?php endforeach; ?>
  </ul>
</nav>
