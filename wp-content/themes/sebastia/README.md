# Sebastia — WordPress theme


## Content type: Entry

Entries are registered as the `entry` custom post type. Each entry represents a stone donation location along the Norwegian coast.

**Custom meta fields** (edited via the "Entry Fields" metabox in the admin):

| Meta key      | Description                          |
|---------------|--------------------------------------|
| `donor`       | Name of the donor                    |
| `year`        | Year of donation                     |
| `type`        | Type of stone                        |
| `stone`       | Stone name                           |
| `municipality`| Municipality                         |
| `origin`      | Geographical origin of the stone     |
| `county`      | County                               |
| `sizequantity`| Size and/or quantity                 |
| `protection`  | Cultural heritage protection status  |
| `_gallery`    | JSON array of WP attachment IDs — the entry's image gallery (e.g. `[123, 456, 789]`) |
| `_entry_lang` | `no` or `en` — used internally to separate language versions in queries (see below) |
| `kirby_folder`| Legacy: the source folder name from the original Kirby CMS. No longer editable; kept as a migration reference. |

---

## Bilingual setup (Polylang)

The site uses the **Polylang** plugin for Norwegian (`no`) and English (`en`).

**Pages** use Polylang natively — create the NO page first, then add an EN translation via the flag icon in the Pages list.

**Entries** use a separate convention: each entry exists as two posts, one with `_entry_lang = no` and one with `_entry_lang = en`. They are also linked as Polylang translations. The helper function `sebastia_get_entries()` in `functions.php` queries by `_entry_lang` to get the right language set.

---

## Images

Entry images are stored in the **WP Media Library** and referenced by attachment ID.

The `_gallery` meta on each entry holds a JSON-encoded array of attachment IDs in display order. The metabox on each entry uses the native WP media picker (`wp.media`) to add, remove, and reorder images.

Image files live in `wp-content/uploads/entries/{kirby_folder}/` — they were migrated from the original Kirby CMS structure using the `?migrate_images=1` admin script.

---

## SVG partials

Two static SVGs are included on each entry page via PHP:

- `partials/mosaic_entry.svg.php` — mosaic of all entry shapes; the current entry's shape is highlighted via the CSS `.active` class, set by JavaScript using the `data-name` attribute matched to the entry slug.
- `partials/norway.svg.php` — map of Norway with a dot per entry location; same `.active` highlighting logic.

The slug used for matching is the base slug with the `-en` suffix stripped (e.g. `kirkenes-en` → `kirkenes`).

---

## Admin utilities

`mu-plugins/pll-language-setup.php` contains several admin scripts. See that file's header for the full list of available `?param=1` URL handlers.

---

## Asset structure

```
assets/
  css/
    index.css        Main stylesheet (imports all others)
    base.css         Typography, variables, layout primitives
    entry.css        Single entry page
    gallery.css      Gallery page
    home.css         Home / front page
    about.css        About page
    timeline.css     Timeline page
  js/
    site.js          Shared JS (navigation, fade-in utilities)
    entry.js         Entry page: lightbox, map labels, stone highlight
    gallery.js       Gallery page: filtering, masonry
    home.js          Home page
    about.js         About page: intersection observer, touch captions
    timeline.js      Timeline page
partials/
  navigation.php     Site navigation (uses Polylang for language-aware links)
  mosaic_entry.svg.php  Static SVG — all entry shapes
  norway.svg.php        Static SVG — Norway map with entry locations
```
