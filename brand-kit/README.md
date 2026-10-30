# Brand kit

Drop-in PHP files that connect an existing brand website to the central panel.
Adds `/blogs`, `/blogs/{slug}` and `/sitemap-blogs.xml`, and lets any page read the
brand's settings (phone, email, socials…). Nothing else on the site changes.

```
blog.php            /blogs           listing (paginated)
blog-post.php       /blogs/{slug}    one template for every post; picks the post's layout
blog-sitemap.php    /sitemap-blogs.xml
location.php        /california, /california-ghostwriting, /california/los-angeles, /california/los-angeles-ghostwriting
location-sitemap.php /sitemap-locations.xml
locations.php       /locations (every state with its cities; link it in the menu)
sitemap.php         /sitemap.xml (index) and /sitemap-pages.xml
htaccess-rules.txt  lines to merge into the site's .htaccess
cms/
  brand.php         API client, cache, helpers (setting(), cms_post(), ...)
  config.example.php → copy to config.php
  clear-cache.php   called by the panel when content changes
  pages.php         called by the panel's "Find pages": the site's page files and their titles
  .htaccess         blocks web access to everything in cms/ except the two above + assets
  partials/         header.php, footer.php, error.php  ← put the site's own header/footer here
  layouts/          classic.php, hero.php, sidebar.php, minimal.php
  locations/        place.php (state/city page), service.php (service page in a place)
  blocks/           one template per content block type
  assets/blog.css
  cache/            created automatically; must be writable by PHP
```

Requirements: PHP 7.4+, Apache with mod_rewrite, and the curl extension or `allow_url_fopen`.

## Install on a brand site

1. In the dashboard, create the brand (or open **Brand → Connect the website**) and copy the config.
2. Copy `blog.php`, `blog-post.php`, `blog-sitemap.php`, `location.php`, `location-sitemap.php`, `locations.php`,
   `sitemap.php` and the whole `cms/` folder to the website root.
3. Copy `cms/config.example.php` to `cms/config.php` and paste the values from step 1.
4. Merge `htaccess-rules.txt` into the site's `.htaccess`.
5. Header and footer: the blog pages include the site's own `includes/header.php` and `includes/footer.php`
   automatically (other paths: `site_header` / `site_footer` in `cms/config.php`). Nothing to change in them;
   see "Your header and footer" below. Match colours and fonts with the variables at the top of `cms/assets/blog.css`.
6. Dashboard → Brand → **Test connection** should say the site was reached.
7. Connect the header, footer and variables (below), then in the panel: **Pages & SEO → Find pages**.
8. `robots.txt`: `Sitemap: https://brand.com/sitemap.xml` (and submit it in Google Search Console).

Test on a staging copy first, then deploy. Existing pages and URLs keep working because only
`/blogs…` and `/sitemap-blogs.xml` are routed to the new files.

## Connect the website's own pages (header, footer, variables)

Only three of the site's files change, never the pages themselves:

1. **A config file** (e.g. `include/config.php`), included at the very top of the header. It gives the site's
   variables their value from the panel, with today's value as the fallback, and lets the panel's
   **Pages & SEO** replace the page's own `$title` / `$desc`:
   ```php
   <?php
   require_once __DIR__ . '/../cms/brand.php';
   $phone = setting_raw('phone', '(555) 010 0100');
   $email = setting_raw('email', 'hello@brand.com');
   $logo  = setting_raw('logo', 'assets/images/logo.webp');
   cms_page_meta($title, $desc);
   ```
2. **The header:** `<?php include __DIR__ . '/config.php'; ?>` first, `<?php cms_head(); ?>` inside `<head>`
   (favicon, noindex, share image, theme, Site code) and `<?php cms_body_code(); ?>` right after `<body>`.
3. **The footer:** `<?php cms_footer_code(); ?>` right before `</body>`.

A new page file needs nothing: it keeps its own `$title`/`$desc`, shows up in the panel after **Find pages**,
and joins `/sitemap-pages.xml`. Files that aren't pages (mail senders, old copies) go in `'page_exclude'` in
`cms/config.php`. Book Craft Services (`sites/bookcraftservices` locally) is the worked example.

## Sitemaps

`/sitemap.xml` (`sitemap.php`) is an index of `/sitemap-pages.xml` (every page file: it uses the site header or has
its own `<html>`, isn't `noindex` and isn't hidden in the panel), `/sitemap-blogs.xml` and `/sitemap-locations.xml`.
Point `robots.txt` at `/sitemap.xml` only. The rewrite rule wins over an old static `sitemap.xml` file.

## Location pages

States, cities and services are managed in the panel (**Locations**, **Services**). `location.php` serves them all:

| URL | Page |
|---|---|
| `/california` | state: banner + lead form, service boxes, extra content, its cities, FAQs, contact |
| `/california-ghostwriting` | the Ghostwriting page for California |
| `/california/los-angeles` | city: same layout, with the state's other cities |
| `/california/los-angeles-ghostwriting` | the Ghostwriting page for Los Angeles |

- **Routing:** the `.htaccess` rule only sends a URL to `location.php` when no real file, folder, `name.php` or
  `name.html` exists, so the site's own pages always win. Unknown URLs get the site's `404.php` (config `site_404`),
  else the kit's 404 page. The URL map is one cached call, so random URLs never reach the panel.
- **Form:** the banner shows the website's own lead form file, `includes/form.php` (config `location_form`), so
  leads go where they always went. Without that file it shows call/email buttons from Settings.
- **Renamed slugs** 301 to the new URL. The sitemap is `/sitemap-locations.xml`.

### Locations menu

Paste this into the site's header (Bootstrap 5 dropdown) where the menu item should go:

```php
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/cms/brand.php'; ?>
<li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Locations</a>
  <ul class="dropdown-menu">
    <?php foreach (cms_location_menu() as $state): ?>
      <li><a class="dropdown-item" href="<?= cms_e($state['url']) ?>"><?= cms_e($state['name']) ?></a></li>
    <?php endforeach ?>
  </ul>
</li>
```

Each state also has `$state['cities']` (`name`, `url`) for a bigger menu. New states show up in the menu by
themselves.

## Use settings in existing pages

```php
<?php require __DIR__ . '/cms/brand.php'; ?>

<a href="tel:<?= cms_tel(setting_raw('phone')) ?>"><?= setting('phone') ?></a>
<a href="mailto:<?= setting('email') ?>"><?= setting('email') ?></a>
<a href="<?= cms_e(cms_whatsapp_url(setting_raw('whatsapp'))) ?>">WhatsApp</a>
<img src="<?= setting('logo') ?>" alt="">
<?= setting('footer_text') ?>
<?= setting('my_custom_key', 'fallback value') ?>
```

`setting()` is HTML-escaped and safe to echo anywhere. `setting_raw()` is the raw value.
Pages must be `.php` (or `.html` with the `AddHandler` line from `htaccess-rules.txt`).

## Your header and footer

`cms/partials/header.php` runs the site's `includes/header.php` (and `footer.php` runs `includes/footer.php`),
exactly like the site's other pages do. On the way it:

- replaces the header's own `<title>`, meta description, canonical and `og:`/`twitter:` tags with the blog
  page's, and adds the page's schema, so each post has its own SEO without touching the header file;
- turns relative links (`css/style.css`, `index.php`) into root ones (`/css/style.css`), because pages live under
  `/blogs/…`. Set `'fix_relative_urls' => false` in `cms/config.php` if the site already uses `/…` links;
- gives the header the usual variables (`$title`, `$description`), and lets the footer see any variable the header set.

The blog stylesheet loads first in `<head>`, so the site's own CSS can override anything. To match a brand,
override the variables in the site's CSS:

```css
:root { --cms-accent: #c9a45c; --cms-ink: #12284c; --cms-muted: #5b6070; --cms-heading-font: 'Oswald', sans-serif; }
```

If the site has no `includes/header.php`, a simple built-in header and footer are used instead.

## Design

The blog styles in `cms/assets/blog.css` are standalone (the site doesn't need Bootstrap), every class starts with
`cms-`, and the file loads before the site's CSS so the site can override anything. Its URL carries a `?v=` that
changes with the file, so visitors never keep an old copy after an update.

- **Listing** (`blog.php`): heading band, card grid, numbered pagination.
- **Post pages** (`cms/layouts/`): breadcrumbs, category, title, intro and a byline with the writer, date and
  reading time (`cms/partials/post-header.php`); then the content, share links, writer box and "back" link
  (`post-end.php`), and "Keep reading" with up to 3 other posts, same category first (`related.php`).
- **Layouts:** classic (wide cover under the title), hero (title over a full-width cover), sidebar ("On this page"
  and a contact card), minimal (centred, no cover).
- **Blocks** (`cms/blocks/`): each content block has its own template.

## Blog cards and the writer box

- Listing cards (`cms/partials/card.php`) show the cover, the post's **category** badge, date, writer, title, two
  lines of the excerpt and "Read more".
- Under each post, `cms/partials/author.php` prints the writer box (photo, name, title, bio). Writers are managed
  once per brand in the panel (**Authors**); the default writer shows on every post that doesn't pick one, and each
  post can hide the box.

## SEO output

Every blog page's `<head>` gets: title, meta description, canonical, Open Graph / Twitter tags, and
JSON-LD from three sources, all printed by `cms_schema_tags()` in `cms/partials/header.php`:

1. a generated `BlogPosting` schema (post pages),
2. the post's **custom schema** from the dashboard (SEO card in the post editor),
3. the brand-wide schema from **Settings → Brand schema** (every blog page).

The panel validates schema and stores it as data; the kit re-encodes it with `JSON_HEX_TAG`, so pasted
code can't break out of the `<script>` tag. Keep that line if you replace the header partial.

## How caching works

- Every panel response is cached in `cms/cache/` for `cache_ttl` seconds (default 10 minutes).
- When an admin saves a post or settings, the panel calls `cms/clear-cache.php`, so changes show at once.
- If the panel is down, the last cached copy keeps being served. The site never breaks because of it.
- Requests for posts that don't exist are cached for 60 seconds, so bots can't hammer the panel.

## Adding a layout or block design

- **Layout:** add `cms/layouts/{name}.php` (it gets `$post`), then tick or type the name under
  Dashboard → Brand → Blog layouts. Posts using a layout a site doesn't have fall back to `classic`.
- **Block design:** edit `cms/blocks/{type}.php` (it gets `$block`). Rich-text fields (`html`, `left`,
  `right`) are already sanitized by the panel and echoed as-is; escape everything else with `cms_e()`.

## Test locally

```bash
php -S localhost:8080 cms/dev-router.php    # from this folder; mimics the .htaccess rules
```
