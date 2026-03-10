<!DOCTYPE html>
<html lang="<?php echo function_exists('pll_current_language') ? pll_current_language() : 'no'; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?php echo esc_html(get_bloginfo('name')); ?><?php if (!is_front_page()): ?> | <?php the_title(); ?><?php endif; ?></title>
  <?php wp_head(); ?>
  <link rel="icon" type="image/png" href="<?php echo get_template_directory_uri(); ?>/assets/icons/favicon.png">
</head>
<body <?php body_class('page-scroll'); ?>>

<svg width="0" height="0" style="position:absolute">
  <filter id="duotone-blue">
    <feColorMatrix type="matrix" values="
      0.2126 0.7152 0.0722 0 0
      0.2126 0.7152 0.0722 0 0
      0.2126 0.7152 0.0722 0 0
      0      0      0      1 0"/>
    <feComponentTransfer>
      <feFuncR type="table" tableValues="0.25 0.99"/>
      <feFuncG type="table" tableValues="0.41 0.99"/>
      <feFuncB type="table" tableValues="0.98 0.98"/>
    </feComponentTransfer>
  </filter>
</svg>

<header class="header <?php echo is_front_page() ? 'home' : 'not-home'; ?> is-visible" data-fade>
  <h1><a href="<?php echo home_url('/'); ?>"><?php echo esc_html(get_bloginfo('name')); ?></a></h1>
  <?php get_template_part('partials/navigation'); ?>
  <?php if (is_front_page()): ?>
  <div class="search-trigger" id="toggle-search" aria-label="Search">
    <svg id="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32">
      <path id="icon-path" d="M11 4a7 7 0 1 1 0 14 7 7 0 0 1 0-14zm5 12l5 5" stroke="black" stroke-width="0.8" fill="none" stroke-linecap="square"/>
    </svg>
  </div>
  <?php endif; ?>
</header>

<div class="mobile-nav-panel" id="mobile-nav-panel">
  <?php get_template_part('partials/navigation', null, ['mobile' => true]); ?>
</div>

<button class="hamburger" id="hamburger" aria-expanded="false" aria-label="Menu">
  <span></span><span></span><span></span>
</button>

<main class="main">
