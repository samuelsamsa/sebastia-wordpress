<?php
/*
 * Template Name: Timeline
 */

add_action('wp_enqueue_scripts', function () {
    $v = filemtime(get_template_directory() . '/assets/js/timeline.js') ?: '1';
    wp_enqueue_script('sebastia-timeline', get_template_directory_uri() . '/assets/js/timeline.js', [], $v, true);
});

get_header();

// Map a raw year to its display bucket:
// pre-1800 → nearest century (1572→1500, 1665→1600)
// 1800+    → nearest decade  (1848→1840, 1989→1980)
function sebastia_year_to_bucket(int $year): int {
    if ($year < 1800) {
        return (int)(floor($year / 100) * 100);
    }
    return (int)(floor($year / 10) * 10);
}

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

// Collect unique buckets from all events
$bucket_set = [];
foreach ($parsed as $item) {
    foreach ($item['events'] as $event) {
        if ($event['type'] === 'dot') {
            $bucket_set[] = sebastia_year_to_bucket($event['year']);
        } elseif ($event['type'] === 'range') {
            $bucket_set[] = sebastia_year_to_bucket($event['start']);
            $bucket_set[] = sebastia_year_to_bucket($event['end']);
        }
    }
}

$buckets = array_values(array_unique($bucket_set));
sort($buckets);
$bucket_count = count($buckets);

$bucket_to_col = [];
foreach ($buckets as $i => $bucket) {
    $bucket_to_col[$bucket] = $i + 1;
}
?>

<section class="timeline">
  <div class="timeline-grid"
       style="--year-count: <?php echo $bucket_count; ?>; grid-template-columns: 10rem repeat(<?php echo $bucket_count; ?>, 1fr);"
       data-fade>

    <div class="timeline-controls" style="grid-column: 1 / 2;">
      <button id="sortYearButton" data-order="asc">Year ↑</button>
      <button id="sortAlphaButton" data-order="asc">A-Z ↑</button>
    </div>

    <?php foreach ($buckets as $bucket): ?>
    <div class="year" data-year="<?php echo $bucket; ?>"><?php echo $bucket; ?></div>
    <?php endforeach; ?>

    <?php foreach ($parsed as $item):
        $first_bucket = null;
        foreach ($item['events'] as $event) {
            if ($event['type'] === 'dot')   { $first_bucket = sebastia_year_to_bucket($event['year']);  break; }
            if ($event['type'] === 'range') { $first_bucket = sebastia_year_to_bucket($event['start']); break; }
        }

        $row_buckets = [];
        foreach ($item['events'] as $event) {
            if ($event['type'] === 'dot') {
                $row_buckets[] = sebastia_year_to_bucket($event['year']);
            } elseif ($event['type'] === 'range') {
                $row_buckets[] = sebastia_year_to_bucket($event['start']);
                $row_buckets[] = sebastia_year_to_bucket($event['end']);
            }
        }
        $row_buckets = array_unique($row_buckets);
    ?>
    <a href="<?php echo esc_url($item['url']); ?>"
       class="timeline-row"
       data-years="<?php echo implode(',', $row_buckets); ?>"
       data-fade>
      <div class="entry-title"><span><?php echo esc_html($item['title']); ?></span></div>

      <div class="entry-time" data-start="<?php echo $first_bucket; ?>">
        <?php foreach ($item['events'] as $event):
            if ($event['type'] === 'dot') {
                $col_start = $bucket_to_col[sebastia_year_to_bucket($event['year'])] ?? 2;
                $col_end   = $col_start + 1;
                $class     = $event['approx'] ? 'dot approx' : 'dot';
            } else {
                $col_start = $bucket_to_col[sebastia_year_to_bucket($event['start'])] ?? 2;
                $col_end   = ($bucket_to_col[sebastia_year_to_bucket($event['end'])] ?? $col_start) + 1;
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
