# Multi-Brand CMS

One central panel that manages blogs and settings for every brand website.
Brand sites keep their existing HTML/Bootstrap/PHP; they only gain `/blogs`, `/blogs/{slug}`,
a blog sitemap, and a `setting('phone')`-style helper for contact details, logo, socials, etc.

```
panel/       The app: Laravel 12 + Inertia.js + React (one project, one deploy)
  app/                  controllers, models, brand API
  resources/js/pages/   dashboard screens (React)
  resources/js/components/
brand-kit/   Drop-in PHP files for each brand website (see brand-kit/README.md)
```

## Run locally

Requirements: PHP 8.2+ (XAMPP works), MySQL/MariaDB, Node 20+, Composer.

```bash
cd panel
composer install
npm install
cp .env.example .env && php artisan key:generate   # then set DB_* in .env
php artisan migrate --seed       # demo login + demo brand; prints the demo brand's API key
php artisan storage:link

php artisan serve                # terminal 1 → open http://localhost:8000
npm run dev                      # terminal 2 → hot reload for the React screens
```

Seeded login: `admin@example.com` / `password`. Create real users in the dashboard (**Team**) or with
`php artisan cms:user you@agency.com --role=admin` (also resets a forgotten password).

## Roles

| | Admin 👑 | Editor ✍️ |
|---|---|---|
| Brands they see | All | Only the ones an admin assigned |
| Write, edit, publish, delete posts | ✓ | ✓ |
| SEO fields + custom schema (JSON-LD) | ✓ | ✓ |
| Brand Settings (contact, logo, socials, brand schema) | ✓ | ✓ |
| Brand page: website connection, API keys, active/paused, delete | ✓ | — |
| Create brands, manage the team | ✓ | — |
| Own profile: name, email, password | ✓ | ✓ |

Permissions are enforced on the server (route gates), not just hidden in the UI.

## How it works

- **Dashboard:** Laravel controllers render React pages through Inertia. No separate API or frontend
  deploy; normal Laravel sessions and CSRF. Each page is its own JS chunk.
- **Size:** the React code runs on [Preact](https://preactjs.com) (aliased in `vite.config.ts`), so the whole
  app shell is ~55 kB gzipped and each screen adds 1–7 kB. Write components as normal React; the one React 19
  API Preact lacks (`use`) is shimmed in `resources/js/lib/react-compat.js`.
- **Snackbars:** controllers call `$this->toast('Saved', 'success'|'info'|'error', action: [...])`; failed
  validation, expired sessions, too many logins and lost connections show one automatically.
- **Brand site → panel:** `brand-kit/cms/brand.php` calls `GET /api/v1/{brand}/…` server-side with the
  brand's `X-Api-Key`, caches responses as files for 10 minutes, and keeps serving the cache if the panel
  is down. Pages render in PHP, so search engines see full HTML.
- **Panel → brand site:** after every save the panel POSTs to the brand's `cms/clear-cache.php`
  (with its cache secret) once the response is sent, so changes go live within seconds.
- **Posts:** title, slug (unique per brand), layout, cover, excerpt, content blocks, SEO fields,
  custom schema, draft/published, optional future publish date. Renaming a slug 301-redirects the old URL.
  **Duplicate** copies a post (blocks, SEO, schema) as a new draft with a free slug (`…-copy`, `…-copy-2`).
  Each post has an optional **category** (badge on the blog card).
- **Authors:** writer profiles per brand (name, title, photo, bio), written once. The default one shows under
  every post that doesn't pick its own; a post can pick another or hide the box. Editors can manage them.
- **Schema (JSON-LD):** a per-post box and a brand-wide box (Settings). Accepts raw JSON or pasted
  `<script type="application/ld+json">` tags; it is validated, stored as data, and re-encoded by the
  brand site, so pasted code can never run as JavaScript. A BlogPosting schema is always generated too.
- **Security:** API keys stored hashed (shown once), cache secrets encrypted, login rate-limited per
  email + IP, rich text and URLs sanitized, uploads images only (no SVG), kit internals blocked by `.htaccess`,
  anti-framing/sniffing headers on every response, panel hidden from search engines (`robots.txt`).

### Brand API

All require `X-Api-Key`. Only published posts with a publish date in the past are returned.

| Endpoint | Returns |
|---|---|
| `GET /api/v1/{brand}/settings` | `{ name, url, settings: { key: value } }` |
| `GET /api/v1/{brand}/posts?page=&per_page=` | paginated post summaries (max 50 per page) |
| `GET /api/v1/{brand}/posts/{slug}` | full post with blocks, SEO and schema; `301 {redirect}` for renamed slugs; `404` |
| `GET /api/v1/{brand}/sitemap` | every published slug + last modified |

## Tests

```bash
cd panel && php artisan test
```

## Deploy

1. `cd panel && npm ci && npm run build` (writes `public/build`), `composer install --no-dev --optimize-autoloader`.
   `node_modules/` is only needed for building; don't upload it.
2. Upload `panel/` (document root must be `panel/public`). Copy `.env.example` to `.env`, run `php artisan key:generate`,
   and set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://panel.youragency.com`,
   `SESSION_SECURE_COOKIE=true`, `LOG_LEVEL=warning`, and the DB credentials. Use HTTPS.
3. `php artisan migrate --force && php artisan storage:link && php artisan optimize`
   (re-run `php artisan optimize` after every deploy or `.env` change).
4. Create the first admin: `php artisan cms:user you@agency.com --role=admin`. Don't run the demo seeder in production.
5. Make `storage/` and `bootstrap/cache/` writable by PHP. On Apache, `public/.htaccess` already sets one-year
   caching for built assets and uploads and gzips text; on Nginx, do the same for `/build/` and `/storage/uploads/`.
   Turn on PHP OPcache.
6. Roll brands out one at a time with `brand-kit/README.md`: staging copy first, then live.

**No terminal on the host?** Log in as an admin and open `https://panel…/maintenance`. It has links for
`storage-link` (makes uploaded images public), `migrate` (applies database changes), `optimize` and `clear-cache`.

Updating later: upload the new code, then steps 1 and 3 (or the `migrate` and `optimize` maintenance links). Browsers pick up the new JS automatically (Inertia
notices the new build and reloads open tabs on their next click).
