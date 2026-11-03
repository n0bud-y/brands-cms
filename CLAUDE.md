# CLAUDE.md: project memory for every new chat

Read this first; it is the whole story so far. **Keep it current:** whenever a task changes what is built,
deployed, pending or agreed, update the matching section below (and add a line to "Log") before ending the turn.
Check details against the code before relying on them. Never put passwords, API keys, cache secrets or
APP_KEY in this file (the GitHub repo may be public).

## The user and how to work with them

- Runs an agency with several book-publishing/design brands, each with a live static PHP/Bootstrap website.
  Not deeply technical about servers: answer in plain language, give exact files to upload and exact
  terminal commands to paste. Writes informally and fast; ask (AskUserQuestion) when something is unclear.
- Top priority: **as lightweight and optimized as possible**; changes must be low-risk for live sites.
- **Commits/pushes: never add a Co-Authored-By / Claude attribution line.** Push to `main` on
  `github.com/n0bud-y/brands-cms` when the user wants something live; for UI work they want to **see it
  locally first** (start the preview, give URLs) and only then push.
- Any test data created in the local DB must be deleted afterwards (temporary brand slug `zz-kit-test`).
- Panel UI style: neo-brutalist (pop colours, hard shadows, emoji); keep it friendly for non-technical
  editors (plain words like "Web address", not "slug").

## What this is

```
panel/      Laravel 12 + Inertia v3 + React on Preact (main bundle ~55 kB gzip). One central admin panel.
brand-kit/  Drop-in PHP 7.4-compatible files copied onto each brand website (see brand-kit/README.md).
sites/      LOCAL ONLY (git-ignored): copies of brand websites being connected.
  bookcraftservices/   Book Craft Services site, fully connected to the kit (ready to upload)
  _tools/              build-bookcraft-import.php + bookcraft-extras.php -> bookcraft-import.json,
                       bookcraft-original/ (the site's files before our changes, for diffs)
README.md   setup, roles, how it works, brand API (keep updated)
```

The panel manages per brand: **posts** (blocks editor, SEO, custom post schema, authors, categories,
scheduling, 301 on slug change), **authors**, **settings** (contact, logo, favicon, socials, **theme**
colours/fonts, admin-only **site code** for head/body/footer), **Pages & SEO** (the website's own pages:
title/description/share image/noindex; "Find pages" asks the site via `cms/pages.php`), **services** and
**locations** (states → cities; pages `/california`, `/california-ghostwriting`, `/california/los-angeles`,
`/california/los-angeles-ghostwriting`; service text is a template with `{location} {state} {city} {service}
{brand}`; any single page can override slug/title/content/feature/FAQs/SEO or be hidden; renamed slugs 301),
**Page defaults** (banner, section titles, FAQs, SEO, `/locations` heading/intro).
Roles: Admin (everything) and Editor (assigned brands; no brand settings page, no site code).

The brand kit gives each website: `/blogs`, `/blogs/{slug}`, `/locations` (hub), location pages
(`location.php` catch-all that only fires when no real file/page exists), `/sitemap.xml` index →
`sitemap-pages.xml` (scans the site's page files), `sitemap-blogs.xml`, `sitemap-locations.xml`.
Content comes from the panel API (`/api/v1/{brand}/…`, `X-Api-Key`), cached as files in `cms/cache/`
(10 min, stale copy served if the panel is down); the panel POSTs `cms/clear-cache.php` after every save.

Connecting a site = upload kit + `.htaccess` rules + a config file whose variables read
`setting_raw('phone', 'fallback')` and call `cms_page_meta($title, $desc)`, plus `cms_head()` in `<head>`,
`cms_body_code()` after `<body>`, `cms_footer_code()` before `</body>`. Book Craft is the worked example.

Removed on request: the brand-wide schema box (settings key `schema` is dropped on save). Site pages keep their
own schema files.

## Live deployment

- Panel: **https://panel.inkpixelstudios.com** (moved from panel.bookcraftservices.com, which was deleted).
  cPanel host **with terminal**. Repo cloned at `~/brands-cms`; subdomain document root
  `brands-cms/panel/public`; Composer at `~/composer.phar`; `.env` at `~/brands-cms/panel/.env`
  (MySQL `inkpqpat_brands_cms`, production, same APP_KEY as local so encrypted cache secrets still work).
- Deploy = push to GitHub, then on the server: `bash ~/brands-cms/panel/deploy.sh`
  (git pull --ff-only, composer install --no-dev, migrate --force, optimize). If pull can't fast-forward
  (history rewritten): `cd ~/brands-cms && git fetch && git reset --hard origin/main`.
  The server pulls via a read-only GitHub deploy key (`~/.ssh/github_brands`, ssh.github.com:443).
- DNS for inkpixelstudios.com is on **Cloudflare**; the `panel` A record is proxied (orange) with SSL mode
  **Full** and Bot Fight Mode off (so brand sites can call the API). The origin only has the host's generic cert.
- Brand content import on the server: upload JSON to `~/brands-cms/panel/storage/app/`, then
  `php artisan cms:import {brand-slug} storage/app/{file}.json` (idempotent; see `app/Support/BrandImport.php`).
- Applewood Publisher website is connected and working. Other brands: not connected yet.

## Local development (Windows)

- PHP 8.2: `/c/xampp/php/php.exe` (not on PATH). MySQL: `/c/xampp/mysql/bin/mysql.exe -u root`, DB
  `multibrand_cms` holds the user's real local data (6 brands, 1 post "blog one"); never leave test data.
- In `panel/`: tests `php artisan test`; frontend checks `npx tsc --noEmit`, `npx oxlint resources/js`,
  `npm run build` (built assets ARE committed: the server has no Node).
- After a PC restart MySQL is off: start it in the background with
  `cd /c/xampp/mysql && ./bin/mysqld.exe --defaults-file=./bin/my.ini --standalone`.
- Port 8000 is usually taken by an OLD copy of the panel in `Desktop/multi brand cms` (also its Vite on 5173).
  Run this project's panel on **8010**. In the background `artisan serve` can stall, so use
  `php -S 127.0.0.1:8010 -t public vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`
  from `panel/`. If `panel/public/hot` exists it points at that other Vite; delete it (git-ignored).
- Preview a brand site: copy it to a scratch folder, put a `cms/config.php` pointing at
  `http://127.0.0.1:8010` with the `zz-kit-test` key, run `php -S localhost:8080 cms/dev-router.php`
  from the site root (dev-router mimics the .htaccess rules). Seed `zz-kit-test`, then
  `php artisan cms:import zz-kit-test sites/_tools/bookcraft-import.json`. Screenshots:
  `"/c/Program Files (x86)/Microsoft/Edge/Application/msedge.exe" --headless=new --screenshot=… --window-size=1366,3000 URL`
  (narrow widths below ~500px are unreliable; use an iframe page for mobile).
- `php artisan tinker <file>` hangs; use `tinker --execute="require '…';" < /dev/null`. No Python on this PC.
- Windows 11 21H2 lacks newer emoji; stick to older emoji in the UI.

## Book Craft Services (bookcraftservices.com)

Connected locally in `sites/bookcraftservices` (only these files changed vs `_tools/bookcraft-original`):
`include/config.php` (new: variables from the panel with fallbacks + page SEO), `include/header.php`
(config include, `cms_head`, conversion flag `window.cmsConversion`, dynamic logo/tagline, Locations + Blogs
menu items), `include/footer.php` + `include/js.php` (dynamic socials/WhatsApp/year; chat widget and
right-click blocking moved to panel Site code), `include/css.php` (static favicon removed), `include/form.php`
(new banner form), `sections/form.php` (hidden `referer` = page URL), `assets/css/style.css` (+6 lines),
`.htaccess` (kit rules, 19 old blog URLs 301 → `/blogs/…`, location catch-all after rule 6), kit files + `cms/`.
`cms/config.php` has `PASTE_API_KEY_HERE` / `PASTE_CACHE_SECRET_HERE` placeholders (rotate in the panel).
`email.php` already prints `referer` as "Page URL" and `services` as "Service" in the lead email.
Import (`sites/_tools/bookcraft-import.json`): 19 posts by Olivia Bennett, settings/theme (#140600, #d4af37,
#3a3530, #f2ebde; Lexend Deca / Montserrat, self-hosted), site code (GA4/Ads/Clarity, verification, chat),
5 services (ghostwriting, book-writing, book-publishing, book-marketing, audiobook-production) with feature
sections, 5 states × 5 cities (CA, FL, NY, TX, GA) with write-ups, location defaults + /locations intro, 41 pages
(thank-you noindex). Rebuild: `php sites/_tools/build-bookcraft-import.php sites/bookcraftservices out.json`
then `php sites/_tools/bookcraft-extras.php out.json`.
Map pictures: `sites/_tools/make-bookcraft-maps.php <us-states.json> sites/bookcraftservices/assets/images/maps` draws 30
SVG locator maps (state outline from public-domain US Census GeoJSON, e.g. PublicaMundi `us-states.json`, + city
pins, Book Craft colours); the import sets each location's `image` to `/assets/images/maps/{state}[-{city}].svg`
(root-relative, shipped inside the site zip). Other brands upload their own map pictures in the panel.

## Current status (update this)

- Pushed and deployed earlier: location pages, theme, site code, Pages & SEO, sitemaps, /locations, cms:import.
- **Committed locally, NOT pushed yet** (`a0e9eb1` and the next commit): /locations redesign (intro section, state bands with
  write-up + map picture, city picture boxes), modern service page (sticky side card, text + image "feature"
  section above FAQs), one-open-at-a-time FAQs, list bullets fix, editors pre-filled with real default text,
  form lead tracking (`referer` + `services`), pointer cursor, logout confirmation, brand schema removed,
  migration `2026_11_03_000001` (locations.image/summary, services/location_pages.feature).
  Then: new service-box design (number, icon per service via `cms_service_icon`, dark hover), Book Craft map
  pictures, posts month/day filters, posts **trash** (soft deletes, restore, delete forever; migration
  `2026_11_04_000001`), one styled Yes/No popup for every delete/duplicate/logout (`lib/confirm.ts` +
  `components/ConfirmDialog.tsx`; only the unsaved-changes guard keeps the browser confirm).
  Then: posts 10/25/50 per page with numbered pages; **move a post to another brand** (`POST posts/{post}/move`,
  free slug in the target, writer → target default, no redirects left); **duplicate** services and states/cities
  (copies switched off, per-page overrides copied without custom slugs; a state copy has no cities; `AppSupportSlugs`).
  The user was reviewing it in the local preview (panel :8010 brand `zz-kit-test`, site :8080).
- Waiting for the user's OK, then: push; delete local test brand `zz-kit-test`; rebuild
  `Desktop/bookcraft-upload.zip` (site files above) and `Desktop/bookcraft-import.json`; walk them through
  go-live (deploy.sh → upload JSON + cms:import book-craft-services → rotate key/secret in panel →
  upload/extract zip, paste key/secret into cms/config.php → Test connection → check pages).

## Open to-dos / ideas

- User should make the GitHub repo private and replace the demo accounts on live.
- Book Craft go-live (above); afterwards optionally delete the 19 old blog .php files, `blogs.php`,
  `include/recent-blogs.php`, old `sitemap.xml` on its server (keep `include/blogs-data.php`: schema.php reads it).
- Connect the remaining brands (Dawbooks, Neale, All American Publications, Websites Design LLC, Applewood's
  new kit features) the Book Craft way.
- City/state map pictures are uploaded by the user in the panel (a pin placeholder shows until then).

## Log (newest last, one line each)

- 2026-10-29/30: panel + brand kit built; panel live; Applewood connected (blogs, authors, settings).
- 2026-10-30: moved panel to panel.inkpixelstudios.com with git deploys (deploy.sh, deploy key, Cloudflare).
- 2026-10-30: location pages (states/cities/services, templates, overrides, redirects) shipped.
- 2026-10-30: theme, site code, Pages & SEO, sitemap index, /locations, cms:import; Book Craft connected locally.
- 2026-10-30: locations/service redesign, feature section, editable defaults, lead tracking, logout confirm (local, unpushed).
- 2026-10-30: added this CLAUDE.md as the project memory.
- 2026-11-03: service boxes redesign, Book Craft locator maps, posts filters + trash, styled confirm popups (local, unpushed).
- 2026-11-03: posts pagination per page, move post to another brand, duplicate services/states/cities (local, unpushed).
