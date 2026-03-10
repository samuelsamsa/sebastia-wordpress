<?php
/**
 * Sebastia — admin utility scripts (keep this file).
 *
 * All handlers require ?param=1 in the WP admin URL and manage_options capability.
 *
 * Available handlers:
 *   ?pll_status=1          Check Polylang language assignment status
 *   ?pll_fix_pages=1       Reassign all Pages to Norwegian (one-time fix)
 *   ?pll_assign_languages=1  Bulk-assign NO/EN languages to entry posts (one-time setup)
 *   ?migrate_images=1      Import entry images from _image_files into WP Media Library
 *                          → populates _gallery meta (JSON array of attachment IDs)
 *                          → safe to re-run; already-migrated entries are skipped
 */

add_action('admin_init', function () {

    // ── Status check ──────────────────────────────────────────────────────────
    if (!empty($_GET['pll_status'])) {
        if (!current_user_can('manage_options')) wp_die('Unauthorised');

        $languages = function_exists('pll_languages_list') ? pll_languages_list() : [];
        $default   = function_exists('pll_default_language') ? pll_default_language() : 'unknown';

        echo '<pre style="font-family:monospace;padding:2em">';
        echo '<strong>Polylang status</strong>' . "\n\n";
        echo 'Registered languages: ' . implode(', ', $languages) . "\n";
        echo 'Default language: ' . $default . "\n\n";

        // Count entry posts by _entry_lang meta
        foreach (['no', 'en'] as $lang) {
            $q = new WP_Query([
                'post_type'      => 'entry',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => [['key' => '_entry_lang', 'value' => $lang]],
            ]);
            echo "Entry posts with _entry_lang=$lang: " . $q->found_posts . "\n";

            // How many have Polylang language assigned?
            $assigned = 0;
            foreach ($q->posts as $id) {
                if (function_exists('pll_get_post_language') && pll_get_post_language($id)) {
                    $assigned++;
                }
            }
            echo "  → with Polylang language assigned: $assigned\n";
        }

        echo "\n<a href='?pll_assign_languages=1'>Run language assignment →</a>";
        echo '</pre>';
        exit;
    }

    // ── Fix pages language ────────────────────────────────────────────────────
    if (!empty($_GET['pll_fix_pages'])) {
        if (!current_user_can('manage_options')) wp_die('Unauthorised');
        if (!function_exists('pll_set_post_language')) {
            wp_die('Polylang functions not available. Make sure Polylang is active.');
        }

        echo '<pre style="font-family:monospace;padding:2em">';
        echo "Reassigning all pages to Norwegian...\n\n";

        $pages = get_posts([
            'post_type'      => 'page',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'lang'           => '',  // bypass Polylang filter
        ]);

        foreach ($pages as $page) {
            $current = function_exists('pll_get_post_language') ? pll_get_post_language($page->ID) : 'none';
            pll_set_post_language($page->ID, 'no');
            echo "  {$page->post_title} (ID {$page->ID}): $current → no\n";
        }

        echo "\nDone. All pages assigned to Norwegian.\n";
        echo '</pre>';
        exit;
    }

    // ── Migrate images to WP Media Library ────────────────────────────────────
    if (!empty($_GET['migrate_images'])) {
        if (!current_user_can('manage_options')) wp_die('Unauthorised');

        $upload_dir   = wp_upload_dir();
        $uploads_base = $upload_dir['basedir']; // absolute path to wp-content/uploads

        echo '<pre style="font-family:monospace;padding:2em">';
        echo "Migrating images from _image_files to WP Media Library...\n\n";

        $entries = get_posts([
            'post_type'      => 'entry',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [['key' => '_image_files', 'compare' => 'EXISTS']],
        ]);

        $total_created = 0;
        $total_reused  = 0;

        foreach ($entries as $entry) {
            $folder      = get_post_meta($entry->ID, 'kirby_folder', true);
            $image_files = get_post_meta($entry->ID, '_image_files', true);

            if (!$folder || !$image_files) continue;

            // Skip entries already migrated
            $existing_gallery = get_post_meta($entry->ID, '_gallery', true);
            if ($existing_gallery && $existing_gallery !== '[]') {
                echo "SKIP (already migrated): {$entry->post_title}\n";
                continue;
            }

            $filenames = array_filter(explode('|', $image_files));
            $att_ids   = [];

            foreach ($filenames as $filename) {
                $relative  = 'entries/' . $folder . '/' . $filename;
                $full_path = $uploads_base . '/' . $relative;

                if (!file_exists($full_path)) {
                    echo "  MISSING: $relative\n";
                    continue;
                }

                // Reuse existing attachment if already imported
                $existing = get_posts([
                    'post_type'      => 'attachment',
                    'post_status'    => 'inherit',
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                    'meta_query'     => [['key' => '_wp_attached_file', 'value' => $relative]],
                ]);

                if (!empty($existing)) {
                    $att_ids[] = $existing[0];
                    $total_reused++;
                    echo "  REUSE #{$existing[0]}: $filename\n";
                    continue;
                }

                $mime   = wp_check_filetype($filename);
                $att_id = wp_insert_attachment([
                    'post_title'     => pathinfo($filename, PATHINFO_FILENAME),
                    'post_status'    => 'inherit',
                    'post_mime_type' => $mime['type'] ?: 'image/jpeg',
                    'post_parent'    => $entry->ID,
                ], $full_path, $entry->ID);

                if (is_wp_error($att_id)) {
                    echo "  ERROR: $filename — " . $att_id->get_error_message() . "\n";
                    continue;
                }

                $att_ids[] = $att_id;
                $total_created++;
                echo "  CREATE #{$att_id}: $filename\n";
            }

            if (!empty($att_ids)) {
                update_post_meta($entry->ID, '_gallery', wp_json_encode($att_ids));
                echo "Migrated: {$entry->post_title} (" . count($att_ids) . " images)\n\n";
            }
        }

        echo "\nDone. Created $total_created new attachments, reused $total_reused.\n";
        echo 'Run again at any time — already-migrated entries are skipped.';
        echo '</pre>';
        exit;
    }

    // ── Bulk language assignment ───────────────────────────────────────────────
    if (!empty($_GET['pll_assign_languages'])) {
        if (!current_user_can('manage_options')) wp_die('Unauthorised');
        if (!function_exists('pll_set_post_language') || !function_exists('pll_save_post_translations')) {
            wp_die('Polylang functions not available. Make sure Polylang is active.');
        }

        $languages = function_exists('pll_languages_list') ? pll_languages_list() : [];
        if (!in_array('no', $languages) || !in_array('en', $languages)) {
            wp_die(
                '<p>Both <strong>no</strong> and <strong>en</strong> languages must be added in Polylang first.</p>' .
                '<p>Go to <a href="' . admin_url('options-general.php?page=mlang') . '">Settings → Languages</a> and add Norwegian (slug: no) and English (slug: en), then come back here.</p>'
            );
        }

        echo '<pre style="font-family:monospace;padding:2em">';
        echo "Assigning Polylang languages to entry posts...\n\n";

        $no_posts = get_posts([
            'post_type'      => 'entry',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [['key' => '_entry_lang', 'value' => 'no']],
        ]);

        $en_posts = get_posts([
            'post_type'      => 'entry',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [['key' => '_entry_lang', 'value' => 'en']],
        ]);

        // Build a slug→id map for EN posts (slug is like "kirkenes-en")
        $en_by_base = [];
        foreach ($en_posts as $p) {
            $base = preg_replace('/-en$/', '', $p->post_name);
            $en_by_base[$base] = $p->ID;
        }

        $paired = 0;
        $unpaired = 0;

        foreach ($no_posts as $p) {
            pll_set_post_language($p->ID, 'no');

            $en_id = $en_by_base[$p->post_name] ?? null;
            if ($en_id) {
                pll_set_post_language($en_id, 'en');
                pll_save_post_translations(['no' => $p->ID, 'en' => $en_id]);
                $paired++;
            } else {
                $unpaired++;
                echo "  No EN pair for: {$p->post_name}\n";
            }
        }

        // Assign language to WordPress pages
        $pages = get_pages();
        foreach ($pages as $page) {
            // Pages get Norwegian by default; user can set EN translations manually
            if (function_exists('pll_get_post_language') && !pll_get_post_language($page->ID)) {
                pll_set_post_language($page->ID, 'no');
            }
        }

        echo "Done.\n";
        echo "  NO posts assigned: " . count($no_posts) . "\n";
        echo "  NO↔EN pairs linked: $paired\n";
        if ($unpaired) echo "  NO posts without EN pair: $unpaired\n";
        echo "\nPages assigned to 'no' (set EN translations manually if needed).\n";
        echo "\n<strong>All done. You can delete mu-plugins/pll-language-setup.php now.</strong>\n";
        echo '</pre>';
        exit;
    }
});
