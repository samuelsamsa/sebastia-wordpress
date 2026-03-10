<?php
/*
 * Template Name: Timeline
 */

add_action('wp_enqueue_scripts', function () {
    $v = filemtime(get_template_directory() . '/assets/js/timeline.js') ?: '1';
    wp_enqueue_script('sebastia-timeline', get_template_directory_uri() . '/assets/js/timeline.js', [], $v, true);
});

get_header();

$entries_q = sebastia_get_entries();
$parsed    = [];

while ($entries_q->have_posts()) {
    $entries_q->the_post();
    $raw = trim(get_post_meta(get_the_ID(), 'year', true));

    if ($raw === '' || strcasecmp($raw, 'n/a') === 0) continue;

    $events   = [];
    $segments = preg_split('/,|\n/', $raw);

    foreach ($segments as $segment) {
        $segment = trim($segment);

        // "1800 / 1900" — two separate years
        preg_match_all('/(\d{4})\s*\/\s*(\d{4})/', $segment, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $events[] = ['type' => 'dot', 'year' => (int)$m[1], 'approx' => false];
            $events[] = ['type' => 'dot', 'year' => (int)$m[2], 'approx' => false];
        }

        // "1800-1900" — a range
        preg_match_all('/(\d{4})\s*[-–]\s*(\d{4})/', $segment, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $events[] = ['type' => 'range', 'start' => (int)$m[1], 'end' => (int)$m[2]];
        }

        // "ca. 1800" or "1800" — single year with optional approx prefix
        preg_match_all('/\b(Ca|Ca\.|ca|Circa|Trolig|Etter|Approx\.|about)?\s*(\d{4})\b/i', $segment, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $events[] = ['type' => 'dot', 'year' => (int)$m[2], 'approx' => !empty($m[1])];
        }
    }

    if (empty($events)) continue;

    $parsed[] = [
        'title'  => get_the_title(),
        'url'    => get_permalink(),
        'events' => $events,
    ];
}
wp_reset_postdata();

$year_array = [];
foreach ($parsed as $item) {
    foreach ($item['events'] as $event) {
        if ($event['type'] === 'dot') {
            $year_array[] = $event['year'];
        } elseif ($event['type'] === 'range') {
            $year_array[] = $event['start'];
            $year_array[] = $event['end'];
        }
    }
}

$year_array = array_values(array_unique($year_array));
sort($year_array);
$year_count = count($year_array);

$year_to_col = [];
foreach ($year_array as $i => $year) {
    $year_to_col[$year] = $i + 1;
}
?>

<section class="timeline">
  <div class="timeline-grid"
       style="--year-count: <?php echo $year_count; ?>; grid-template-columns: 10rem repeat(<?php echo $year_count; ?>, 3rem);"
       data-fade>

    <div class="timeline-controls" style="grid-column: 1 / 2;">
      <button id="sortYearButton" data-order="asc">Year ↑</button>
      <button id="sortAlphaButton" data-order="asc">A-Z ↑</button>
    </div>

    <?php foreach ($year_array as $year): ?>
    <div class="year" data-year="<?php echo $year; ?>"><?php echo $year; ?></div>
    <?php endforeach; ?>

    <?php foreach ($parsed as $item):
        $first_year = null;
        foreach ($item['events'] as $event) {
            if ($event['type'] === 'dot')   { $first_year = $event['year'];  break; }
            if ($event['type'] === 'range') { $first_year = $event['start']; break; }
        }

        $row_years = [];
        foreach ($item['events'] as $event) {
            if ($event['type'] === 'dot')   $row_years[] = $event['year'];
            if ($event['type'] === 'range') { $row_years[] = $event['start']; $row_years[] = $event['end']; }
        }
        $row_years = array_unique($row_years);
    ?>
    <a href="<?php echo esc_url($item['url']); ?>"
       class="timeline-row"
       data-years="<?php echo implode(',', $row_years); ?>"
       data-fade>
      <div class="entry-title"><span><?php echo esc_html($item['title']); ?></span></div>

      <div class="entry-time" data-start="<?php echo $first_year; ?>">
        <?php foreach ($item['events'] as $event):
            if ($event['type'] === 'dot') {
                $col_start = $year_to_col[$event['year']] ?? 2;
                $col_end   = $col_start + 1;
                $class     = $event['approx'] ? 'dot approx' : 'dot';
            } else {
                $col_start = $year_to_col[$event['start']] ?? 2;
                $col_end   = ($year_to_col[$event['end']] ?? $col_start) + 1;
                $class     = 'range';
            }
        ?>
        <span class="<?php echo $class; ?>"
              style="grid-column: <?php echo $col_start; ?> / <?php echo $col_end; ?>;"></span>
        <?php endforeach; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<?php get_footer(); ?>
