# Sebastia — WordPress site


## What's in this repo

```
wp-content/themes/sebastia/   Custom theme (all custom code lives here)
wp-content/mu-plugins/        Admin utility scripts (see below)
db/sebastia.sql               Database dump — import this to get all content
```

WordPress core files, third-party plugins, and uploaded images are **not** tracked. Install them separately as described below.

---

## Local setup

### 1 — Install Local by Flywheel

Download from [localwp.com](https://localwp.com) and create a new blank WordPress site. Note down the site name; you'll use it in step 4.

### 2 — Clone the repo into the site root

```bash
cd ~/Local\ Sites/<your-site-name>/app/public
git init
git remote add origin https://github.com/samuelsamsa/sebastia-wordpress.git
git pull origin main
```

### 3 — Install WordPress core

Local installs WordPress automatically. If the `wp-*.php` root files are missing, re-create the site in Local or copy them from a fresh WordPress download.

### 4 — Configure wp-config.php

Copy the sample file and fill in the database credentials Local gave you:

```bash
cp wp-config-sample.php wp-config.php
```

Edit `wp-config.php` and set `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST` to match your Local site's database settings (visible in Local under "Database").

### 5 — Import the database

In Local, open the site's **Database** tab → click **Open Adminer** → select your database → choose **Import** → upload `db/sebastia.sql`.

Alternatively from the terminal (replace values from Local's Database tab):

```bash
mysql -u root -p -h 127.0.0.1 -P <port> <db-name> < db/sebastia.sql
```

### 6 — Install required plugins

In the WordPress admin go to **Plugins → Add New** and install:

| Plugin | Notes |
|--------|-------|
| **Polylang** | Required — handles Norwegian / English bilingual setup |
| **Advanced Custom Fields** | Required — in use on some pages |
| **WordPress Importer** | Optional — only needed for XML import/export |

### 7 — Activate the theme

Go to **Appearance → Themes** and activate **Sebastia**.

---

## Theme overview

See [`wp-content/themes/sebastia/README.md`](wp-content/themes/sebastia/README.md) for full documentation of the theme structure, entry meta fields, bilingual setup, and asset organisation.

---

## Admin utilities

`wp-content/mu-plugins/pll-language-setup.php` contains admin scripts accessible via URL parameters in the WP admin. See its file header for the full list of available handlers.
