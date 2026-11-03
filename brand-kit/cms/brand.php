<?php
/**
 * Brand CMS client: loads this website's settings and blog posts from the central panel.
 *
 *   require __DIR__ . '/cms/brand.php';
 *   <a href="tel:<?= cms_tel(setting_raw('phone')) ?>"><?= setting('phone') ?></a>
 *
 * API responses are cached as JSON files in cms/cache/. When the panel is unreachable the
 * last cached copy keeps being served, so the website never breaks because of the panel.
 * The panel clears this cache via cms/clear-cache.php whenever content changes.
 *
 * Compatible with PHP 7.4+. Needs either the curl extension or allow_url_fopen.
 */

if (defined('BRAND_CMS')) {
    return;
}
define('BRAND_CMS', __DIR__);

const CMS_BLOCK_TYPES = ['heading', 'paragraph', 'image', 'image_text', 'quote', 'cta', 'two_column', 'gallery', 'faq'];

function cms_config($key = null)
{
    static $config = null;

    if ($config === null) {
        $file = __DIR__ . '/config.php';
        if (!is_file($file)) {
            trigger_error('Brand CMS: copy cms/config.example.php to cms/config.php and fill it in.', E_USER_WARNING);
        }
        $config = (is_file($file) ? (array) require $file : []) + [
            'panel_url' => '',
            'site' => '',
            'api_key' => '',
            'cache_secret' => '',
            'site_url' => '',
            'cache_ttl' => 600,
            'timeout' => 3,
            'posts_per_page' => 9,
            'kit_url' => '/cms',
            'cache_dir' => __DIR__ . '/cache',
            'header' => __DIR__ . '/partials/header.php',
            'footer' => __DIR__ . '/partials/footer.php',
            // The website's own header/footer, relative to the website root. The blog uses them when they exist.
            'site_root' => dirname(__DIR__),
            'site_header' => 'includes/header.php',
            'site_footer' => 'includes/footer.php',
            // Location pages: the lead form shown in their banner, and the page for unknown URLs.
            'location_form' => 'includes/form.php',
            'site_404' => '404.php',
            // The site's own pages (Pages in the panel, sitemap): addresses without ".php", and files that aren't pages.
            'clean_urls' => true,
            'page_exclude' => [],
            'fix_relative_urls' => true,
            'error_page' => __DIR__ . '/partials/error.php',
        ];
    }

    return $key === null ? $config : ($config[$key] ?? null);
}

/** Escape for HTML text and attributes. */
function cms_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* -------------------------------------------------------------------------
 * API + cache
 * ---------------------------------------------------------------------- */

/**
 * GET a panel API path through the file cache.
 * Returns ['status' => 200|301|404, 'body' => array|null], or null when the panel
 * is unreachable and nothing is cached yet.
 */
function cms_api($path)
{
    static $memo = [];
    if (array_key_exists($path, $memo)) {
        return $memo[$path];
    }

    $file = cms_cache_file($path);
    $cached = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
    if (is_array($cached) && time() < ($cached['expires'] ?? 0)) {
        return $memo[$path] = $cached;
    }

    $fresh = cms_http($path);
    if ($fresh === null) {
        // Panel down or erroring: keep serving the stale copy and retry in a minute.
        if (is_array($cached)) {
            $cached['expires'] = time() + 60;
            cms_cache_write($file, $cached);
        }

        return $memo[$path] = is_array($cached) ? $cached : null;
    }

    // Misses are cached briefly so bots probing random URLs don't each reach the panel.
    $fresh['expires'] = time() + ($fresh['status'] === 200 ? (int) cms_config('cache_ttl') : 60);
    cms_cache_write($file, $fresh);

    return $memo[$path] = $fresh;
}

function cms_http($path)
{
    $url = rtrim((string) cms_config('panel_url'), '/') . '/api/v1/' . rawurlencode((string) cms_config('site')) . $path;
    $headers = ['Accept: application/json', 'X-Api-Key: ' . cms_config('api_key')];
    $timeout = max(1, (int) cms_config('timeout'));

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout + 2,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    } else {
        $context = stream_context_create(['http' => [
            'header' => implode("\r\n", $headers),
            'timeout' => $timeout + 2,
            'ignore_errors' => true,
            'follow_location' => 0,
        ]]);
        $body = @file_get_contents($url, false, $context);
        $responseHeaders = function_exists('http_get_last_response_headers')
            ? (array) http_get_last_response_headers()
            : ($http_response_header ?? []);
        $status = isset($responseHeaders[0]) && preg_match('/\s(\d{3})/', $responseHeaders[0], $m) ? (int) $m[1] : 0;
    }

    if (!is_string($body) || !in_array($status, [200, 301, 404], true)) {
        return null;
    }

    $data = json_decode($body, true);
    if ($status === 200 && !is_array($data)) {
        return null;
    }

    return ['status' => $status, 'body' => is_array($data) ? $data : null];
}

function cms_cache_file($path)
{
    return rtrim((string) cms_config('cache_dir'), '/\\') . '/' . sha1(cms_config('site') . '|' . $path) . '.json';
}

function cms_cache_write($file, array $data)
{
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    // Write then rename, so a reader never sees a half-written file.
    $tmp = $file . '.' . uniqid('', true) . '.tmp';
    if (@file_put_contents($tmp, json_encode($data), LOCK_EX) !== false) {
        @rename($tmp, $file);
    }
}

/** Deletes every cached response. Returns how many files were removed. */
function cms_clear_cache()
{
    $count = 0;
    foreach (glob(rtrim((string) cms_config('cache_dir'), '/\\') . '/*.json') ?: [] as $file) {
        $count += @unlink($file) ? 1 : 0;
    }

    return $count;
}

/* -------------------------------------------------------------------------
 * Settings
 * ---------------------------------------------------------------------- */

function cms_settings()
{
    $res = cms_api('/settings');

    return ($res['status'] ?? 0) === 200 ? (array) ($res['body']['settings'] ?? []) : [];
}

function cms_site_name()
{
    $res = cms_api('/settings');

    return (string) ($res['body']['name'] ?? '');
}

if (!function_exists('setting_raw')) {
    /** A setting's raw value (not escaped). Use for building URLs or with your own escaping. */
    function setting_raw($key, $default = '')
    {
        $settings = cms_settings();

        return isset($settings[$key]) && $settings[$key] !== '' ? (string) $settings[$key] : (string) $default;
    }
}

if (!function_exists('setting')) {
    /** A setting's value, HTML-escaped and safe to echo anywhere in a page. */
    function setting($key, $default = '')
    {
        return cms_e(setting_raw($key, $default));
    }
}

/* -------------------------------------------------------------------------
 * The website's own pages: SEO from the panel, theme, site code
 *
 *   in the site's header, after the page set $title/$desc:   cms_page_meta($title, $desc);
 *   inside <head>:                                         <?php cms_head(); ?>
 *   right after <body>:                                    <?php cms_body_code(); ?>
 *   right before </body>:                                  <?php cms_footer_code(); ?>
 * ---------------------------------------------------------------------- */

/** This request's page: "/", "/about" ("/about.php" and "/index.php" are the same pages). */
function cms_current_path()
{
    $path = '/' . trim(rawurldecode((string) strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?')), '/');
    $path = (string) preg_replace('#\.php$#', '', $path);

    return $path === '/index' ? '/' : $path;
}

/** What the panel's Pages screen sets for a page: title, description, image, noindex (only the ones it changes). */
function cms_page_seo($path = null)
{
    $res = cms_api('/settings');
    $pages = ($res['status'] ?? 0) === 200 ? (array) ($res['body']['pages'] ?? []) : [];
    $seo = $pages[$path === null ? cms_current_path() : $path] ?? [];

    return is_array($seo) ? $seo : [];
}

/** Lets the panel replace a page's own title and description; the page's text stays when the panel has none. */
function cms_page_meta(&$title, &$description)
{
    $seo = cms_page_seo();
    if (!empty($seo['title'])) {
        $title = (string) $seo['title'];
    }
    if (!empty($seo['description'])) {
        $description = (string) $seo['description'];
    }
}

/** For the website's own <head>: favicon, noindex and share image from Pages, theme, and the panel's head code. */
function cms_head()
{
    $seo = cms_page_seo();
    $tags = [];
    if (setting_raw('favicon') !== '') {
        $tags[] = '<link rel="icon" href="' . setting('favicon') . '">';
    }
    if (!empty($seo['noindex'])) {
        $tags[] = '<meta name="robots" content="noindex">';
    }
    if (!empty($seo['image'])) {
        $tags[] = '<meta property="og:image" content="' . cms_e($seo['image']) . '">';
        $tags[] = '<meta name="twitter:card" content="summary_large_image">';
    }
    echo implode("\n", $tags), "\n", cms_theme_tags(), cms_code('code_head');
}

function cms_body_code()
{
    echo cms_code('code_body');
}

function cms_footer_code()
{
    echo cms_code('code_footer');
}

/** Code from the panel's Settings → Site code, printed at most once per page. */
function cms_code($key)
{
    static $done = [];
    if (isset($done[$key])) {
        return '';
    }
    $done[$key] = true;
    $code = setting_raw($key);

    return $code === '' ? '' : "\n" . $code . "\n";
}

/** The brand's colours and fonts from Settings → Theme, as CSS variables for the kit's pages. Once per page. */
function cms_theme_tags()
{
    static $done = false;
    if ($done) {
        return '';
    }
    $done = true;

    $vars = [];
    foreach (['theme_primary' => '--cms-ink', 'theme_accent' => '--cms-accent', 'theme_text' => '--cms-text', 'theme_background' => '--cms-soft'] as $key => $var) {
        if (preg_match('/^#[0-9a-f]{6}$/i', $color = setting_raw($key))) {
            $vars[] = $var . ':' . $color;
        }
    }
    $font = function ($key) {
        $name = setting_raw($key);

        return preg_match('/^[a-z0-9 \-]{1,60}$/i', $name) ? $name : '';
    };
    $heading = $font('font_heading');
    $body = $font('font_body');
    if ($heading !== '') {
        $vars[] = "--cms-heading-font:'{$heading}',sans-serif";
    }

    $html = '';
    $families = array_unique(array_filter([$heading, $body]));
    if ($families && setting_raw('font_google') === '1') {
        $query = implode('&', array_map(function ($f) {
            return 'family=' . str_replace(' ', '+', $f) . ':wght@400;600;700';
        }, $families));
        $html .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
            . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?' . cms_e($query) . '&amp;display=swap">' . "\n";
    }
    $css = ($vars ? ':root{' . implode(';', $vars) . '}' : '') . ($body !== '' ? ".cms-blog{font-family:'{$body}',sans-serif}" : '');

    return $html . ($css !== '' ? "<style>{$css}</style>\n" : '');
}

/**
 * The website's own pages, read from its .php files: [['path', 'title', 'description', 'modified'], ...].
 * A file is a page when it uses the site header (or has its own <html>) and isn't marked noindex;
 * the kit's files and config 'page_exclude' are left out. Used by Find pages and the sitemap.
 */
function cms_site_pages()
{
    $skip = array_merge(
        ['404', 'blog', 'blog-post', 'blog-sitemap', 'location', 'location-sitemap', 'locations', 'sitemap'],
        (array) cms_config('page_exclude')
    );
    $pages = [];
    foreach (glob(rtrim((string) cms_config('site_root'), '/\\') . '/*.php') ?: [] as $file) {
        $name = basename($file, '.php');
        if (in_array($name, $skip, true) || !preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
            continue;
        }
        $src = (string) @file_get_contents($file, false, null, 0, 300000);
        if (!preg_match('/header\.php|<html/i', $src) || preg_match('/<meta[^>]+noindex/i', $src)) {
            continue;
        }
        $pages[] = [
            'path' => $name === 'index' ? '/' : '/' . $name . (cms_config('clean_urls') ? '' : '.php'),
            'title' => cms_php_string($src, ['title', 'page_title', 'pageTitle']),
            'description' => cms_php_string($src, ['desc', 'description', 'meta_description', 'disc']),
            'modified' => (int) filemtime($file),
        ];
    }

    return $pages;
}

/** The text a page file assigns to one of these variables, e.g. $title = "About us"; */
function cms_php_string($src, array $names)
{
    foreach ($names as $name) {
        if (preg_match('/\$' . $name . '\s*=\s*(["\'])((?:\\\\.|(?!\1).)*)\1\s*;/s', $src, $m)) {
            return trim((string) preg_replace('/\s+/', ' ', stripslashes($m[2])));
        }
    }

    return '';
}

/** Endpoints the panel calls (POST + X-Cache-Secret). Stops with 403 for anyone else. */
function cms_require_panel()
{
    header('Content-Type: application/json');
    header('Cache-Control: no-store');

    $secret = (string) cms_config('cache_secret');
    $given = (string) ($_SERVER['HTTP_X_CACHE_SECRET'] ?? '');

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || strlen($secret) < 16 || !hash_equals($secret, $given)) {
        http_response_code(403);
        echo json_encode(['ok' => false]);
        exit;
    }
}

/** "+1 555 0100" -> "+15550100", for tel: links. */
function cms_tel($number)
{
    return (string) preg_replace('/[^0-9+]/', '', (string) $number);
}

function cms_whatsapp_url($number, $text = '')
{
    return 'https://wa.me/' . preg_replace('/\D/', '', (string) $number) . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

/* -------------------------------------------------------------------------
 * Posts
 * ---------------------------------------------------------------------- */

function cms_valid_slug($slug)
{
    return is_string($slug) && strlen($slug) <= 190 && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
}

/** One page of published posts: ['data' => [...], 'meta' => [...]], or null if the panel is unavailable. */
function cms_posts($page = 1)
{
    $res = cms_api('/posts?' . http_build_query([
        'page' => max(1, (int) $page),
        'per_page' => (int) cms_config('posts_per_page'),
    ]));

    return ($res['status'] ?? 0) === 200 ? $res['body'] : null;
}

/**
 * Looks up a published post. Returns one of:
 *   ['status' => 200, 'post' => [...]]
 *   ['status' => 301, 'redirect' => 'new-slug']   (the post was renamed)
 *   ['status' => 404]
 *   ['status' => 503]                              (panel unreachable, nothing cached)
 */
function cms_post($slug)
{
    if (!cms_valid_slug($slug)) {
        return ['status' => 404];
    }

    $res = cms_api('/posts/' . $slug);
    if ($res === null) {
        return ['status' => 503];
    }
    if ($res['status'] === 200) {
        return ['status' => 200, 'post' => $res['body']];
    }
    if ($res['status'] === 301 && cms_valid_slug($res['body']['redirect'] ?? null)) {
        return ['status' => 301, 'redirect' => $res['body']['redirect']];
    }

    return ['status' => 404];
}

function cms_sitemap_posts()
{
    $res = cms_api('/sitemap');

    return ($res['status'] ?? 0) === 200 ? (array) ($res['body']['data'] ?? []) : [];
}

/* -------------------------------------------------------------------------
 * Locations (/california, /california-ghostwriting, /california/los-angeles…)
 * ---------------------------------------------------------------------- */

/**
 * Every location URL of this brand: ['paths' => [url path => page key], 'redirects' => [old => new], 'menu' => [...]],
 * or null if the panel is unavailable. One cached call, so unknown URLs never reach the panel.
 */
function cms_locations()
{
    $res = cms_api('/locations');

    return ($res['status'] ?? 0) === 200 ? $res['body'] : null;
}

/**
 * Looks up a location page by its URL path ("california/los-angeles"). Returns one of:
 *   ['status' => 200, 'page' => [...]]
 *   ['status' => 301, 'redirect' => 'new/path']   (its slug changed)
 *   ['status' => 404]
 *   ['status' => 503]                             (panel unreachable, nothing cached)
 */
function cms_location($path)
{
    if (!is_string($path) || strlen($path) > 255 || !preg_match('#^[a-z0-9]+(?:-[a-z0-9]+)*(?:/[a-z0-9]+(?:-[a-z0-9]+)*)?$#', $path)) {
        return ['status' => 404];
    }

    $map = cms_locations();
    if ($map === null) {
        return ['status' => 503];
    }

    $key = $map['paths'][$path] ?? null;
    if (is_string($key)) {
        $res = cms_api('/locations/' . rawurlencode($key));
        if ($res === null) {
            return ['status' => 503];
        }

        return $res['status'] === 200 ? ['status' => 200, 'page' => $res['body']] : ['status' => 404];
    }

    $redirect = $map['redirects'][$path] ?? null;

    return is_string($redirect) ? ['status' => 301, 'redirect' => $redirect] : ['status' => 404];
}

function cms_location_url($path)
{
    return cms_url('/' . $path);
}

/**
 * States with their cities, for a "Locations" menu or /locations:
 * [['name', 'url', 'image', 'summary', 'cities' => [['name', 'url', 'image'], ...]], ...].
 * Root-relative URLs, so it works in any header.
 */
function cms_location_menu()
{
    $menu = [];
    foreach ((array) (cms_locations()['menu'] ?? []) as $state) {
        $cities = [];
        foreach ((array) ($state['cities'] ?? []) as $city) {
            $cities[] = ['name' => (string) $city['name'], 'url' => '/' . $city['path'], 'image' => (string) ($city['image'] ?? '')];
        }
        $menu[] = [
            'name' => (string) $state['name'],
            'url' => '/' . $state['path'],
            'image' => (string) ($state['image'] ?? ''),
            'summary' => (string) ($state['summary'] ?? ''),
            'cities' => $cities,
        ];
    }

    return $menu;
}

/** The website's own lead form (config 'location_form'), or call/email buttons when it has none. */
function cms_location_form(array $page = [])
{
    if (!$form = cms_site_file('location_form')) {
        cms_partial('location-quick-contact');

        return;
    }

    // Tell the lead email which page the form was sent from: "referer" (the page URL, shown as "Page URL")
    // and "services" (e.g. "Ghostwriting – Los Angeles, California") unless the form asks for a service itself.
    $html = cms_render_site_file($form);
    $hidden = [];
    foreach (['referer' => $page['url'] ?? '', 'services' => $page['label'] ?? ''] as $name => $value) {
        if ($value !== '' && !preg_match('/\sname=["\']?' . $name . '\b/i', $html)) {
            $hidden[] = '<input type="hidden" name="' . $name . '" value="' . cms_e($value) . '">';
        }
    }
    echo $hidden ? str_ireplace('</form>', implode('', $hidden) . '</form>', $html) : $html;
}

/** True the first time it's called with $key on this page (for scripts a template needs only once). */
function cms_once($key)
{
    static $seen = [];
    if (isset($seen[$key])) {
        return false;
    }

    return $seen[$key] = true;
}

/** Unknown URL: the website's own 404 page (config 'site_404') when it has one, else the kit's. */
function cms_site_not_found()
{
    $file = cms_site_file('site_404');
    if ($file === '') {
        cms_not_found();
    }
    http_response_code(404);
    echo cms_render_site_file($file);
    exit;
}

/* -------------------------------------------------------------------------
 * URLs + formatting
 * ---------------------------------------------------------------------- */

function cms_url($path = '')
{
    return rtrim((string) cms_config('site_url'), '/') . $path;
}

function cms_post_url($slug)
{
    return cms_url('/blogs/' . $slug);
}

/** URL of a kit asset. The ?v= changes whenever the file does, so browsers never keep an old copy. */
function cms_asset($file)
{
    $path = __DIR__ . '/assets/' . $file;

    return rtrim((string) cms_config('kit_url'), '/') . '/assets/' . $file . (is_file($path) ? '?v=' . filemtime($path) : '');
}

function cms_date($iso, $format = 'F j, Y')
{
    $time = $iso ? strtotime((string) $iso) : false;

    return $time ? date($format, $time) : '';
}

/* -------------------------------------------------------------------------
 * Rendering
 * ---------------------------------------------------------------------- */

/**
 * Prints the page header. $meta keys: title, description, image, url (canonical),
 * type (website|article), robots, prev, next, jsonld (generated schema), schema (the post's custom schema).
 */
function cms_header(array $meta = [])
{
    $meta += [
        'title' => cms_site_name(),
        'description' => '',
        'image' => '',
        'url' => cms_url(strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?')),
        'type' => 'website',
        'robots' => '',
        'prev' => null,
        'next' => null,
        'jsonld' => null,
        'schema' => null,
    ];
    // The panel's Pages screen can set this URL's title, description and noindex too (e.g. /blogs, /locations).
    $seo = cms_page_seo();
    $meta['title'] = !empty($seo['title']) ? (string) $seo['title'] : $meta['title'];
    $meta['description'] = !empty($seo['description']) ? (string) $seo['description'] : $meta['description'];
    $meta['image'] = !empty($seo['image']) ? (string) $seo['image'] : $meta['image'];
    $meta['robots'] = !empty($seo['noindex']) ? 'noindex' : $meta['robots'];
    include cms_config('header');
}

function cms_footer()
{
    include cms_config('footer');
}

function cms_error_page($status, $title, $message)
{
    http_response_code($status);
    if ($status === 503) {
        header('Retry-After: 300');
    }
    include cms_config('error_page');
    exit;
}

function cms_not_found()
{
    cms_error_page(404, 'Page not found', 'The page you are looking for does not exist or has moved.');
}

function cms_unavailable()
{
    cms_error_page(503, 'Temporarily unavailable', 'This page could not be loaded right now. Please try again in a few minutes.');
}

/* -------------------------------------------------------------------------
 * The website's own header and footer
 * ---------------------------------------------------------------------- */

/** Absolute path of a website file named in the config (e.g. includes/header.php), or '' when there is none. */
function cms_site_file($key)
{
    $path = (string) cms_config($key);
    if ($path === '') {
        return '';
    }
    $file = rtrim((string) cms_config('site_root'), '/\\') . '/' . ltrim($path, '/\\');

    return is_file($file) ? $file : (is_file($path) ? $path : '');
}

/**
 * Runs one of the website's own PHP files and returns its HTML. It gets the usual page variables
 * ($title, $description…), and the footer sees any variables the header set, like on a normal page.
 */
function cms_render_site_file($__file, array $__meta = [])
{
    static $__vars = [];
    extract($__vars, EXTR_SKIP);
    if (($__meta['title'] ?? '') !== '') {
        $title = $pageTitle = $page_title = (string) $__meta['title'];
        $description = $meta_description = $desc = (string) ($__meta['description'] ?? '');
    }

    ob_start();
    include $__file;
    $__html = (string) ob_get_clean();
    $__vars = array_diff_key(get_defined_vars(), ['__file' => 1, '__meta' => 1, '__vars' => 1, '__html' => 1]);

    return cms_config('fix_relative_urls') ? cms_root_relative_urls($__html) : $__html;
}

/** "css/style.css" → "/css/style.css", so the site's relative links still work on /blogs/{slug} pages. */
function cms_root_relative_urls($html)
{
    return (string) preg_replace(
        '/(\s(?:src|href|data-src|action|poster)=)(["\'])(?![\/#?]|[a-z][a-z0-9+.\-]*:|\{|<|\$)([^"\']+)\2/i',
        '$1$2/$3$2',
        $html
    );
}

/** Puts this page's SEO tags into the website's own <head>, replacing the title/description/social tags it has. */
function cms_seo_head($html, array $meta)
{
    $html = (string) preg_replace([
        '#<title\b[^>]*>.*?</title>\s*#is',
        '#<meta\s[^>]*\b(?:name|property)=["\']?(?:description|robots|og:[\w:]+|twitter:[\w:]+)["\'\s/>][^>]*>\s*#i',
        '#<link\s[^>]*\brel=["\']?(?:canonical|prev|next)["\'\s/>][^>]*>\s*#i',
    ], '', $html);

    $tags = cms_head_tags($meta, false);
    $end = stripos($html, '</head>');
    $html = $end === false ? $tags . $html : substr_replace($html, $tags, $end, 0);

    // The blog stylesheet goes first in <head>, so the website's own CSS can override it.
    $css = '<link rel="stylesheet" href="' . cms_e(cms_asset('blog.css')) . '">' . cms_theme_tags();

    foreach (['#<meta\s+charset[^>]*>#i', '#<head\b[^>]*>#i'] as $after) {
        if (preg_match($after, $html, $m, PREG_OFFSET_CAPTURE)) {
            return substr_replace($html, "\n" . $css, $m[0][1] + strlen($m[0][0]), 0);
        }
    }

    return $css . $html;
}

/** Title, description, canonical, Open Graph/Twitter, JSON-LD and the blog stylesheet for <head>. */
function cms_head_tags(array $meta, $withCss = true)
{
    $tags = ['<title>' . cms_e($meta['title'] ?: cms_site_name()) . '</title>'];
    $add = function ($html) use (&$tags) {
        $tags[] = $html;
    };
    if ($meta['description'] !== '') {
        $add('<meta name="description" content="' . cms_e($meta['description']) . '">');
    }
    if ($meta['robots'] !== '') {
        $add('<meta name="robots" content="' . cms_e($meta['robots']) . '">');
    }
    $add('<link rel="canonical" href="' . cms_e($meta['url']) . '">');
    foreach (['prev', 'next'] as $rel) {
        if ($meta[$rel]) {
            $add('<link rel="' . $rel . '" href="' . cms_e($meta[$rel]) . '">');
        }
    }
    $add('<meta property="og:type" content="' . cms_e($meta['type']) . '">');
    $add('<meta property="og:site_name" content="' . cms_e(cms_site_name()) . '">');
    $add('<meta property="og:title" content="' . cms_e($meta['title']) . '">');
    $add('<meta property="og:url" content="' . cms_e($meta['url']) . '">');
    if ($meta['description'] !== '') {
        $add('<meta property="og:description" content="' . cms_e($meta['description']) . '">');
    }
    if ($meta['image'] !== '') {
        $add('<meta property="og:image" content="' . cms_e($meta['image']) . '">');
    }
    $add('<meta name="twitter:card" content="' . ($meta['image'] !== '' ? 'summary_large_image' : 'summary') . '">');
    $add(trim(cms_schema_tags($meta)));
    if ($withCss) {
        $add('<link rel="stylesheet" href="' . cms_e(cms_asset('blog.css')) . '">');
        $add(trim(cms_theme_tags() . cms_code('code_head')));
    }

    return implode("\n", array_filter($tags)) . "\n";
}

/** The writer box under a post (cms/partials/author.php), when the post has one. */
function cms_author_box(array $post)
{
    $author = $post['author_box'] ?? null;
    if (is_array($author) && ($author['name'] ?? '') !== '') {
        include __DIR__ . '/partials/author.php';
    }
}

/** Includes cms/partials/{name}.php with the given variables. */
function cms_partial($name, array $vars = [])
{
    extract($vars, EXTR_SKIP);
    include __DIR__ . '/partials/' . $name . '.php';
}

/** Everything after a post's content: share links, writer box, back link. */
function cms_post_end(array $post)
{
    cms_partial('post-end', ['post' => $post]);
}

/** Minutes to read the post's blocks, at ~220 words a minute. */
function cms_reading_time($blocks)
{
    $text = '';
    $blocks = (array) $blocks;
    array_walk_recursive($blocks, function ($value, $key) use (&$text) {
        if (is_string($value) && in_array($key, ['text', 'html', 'left', 'right', 'q', 'a', 'caption', 'title'], true)) {
            $text .= ' ' . strip_tags($value);
        }
    });

    return max(1, (int) round(str_word_count($text) / 220));
}

/** Up to $limit other published posts, same category first. Uses the cached first listing page. */
function cms_related_posts(array $post, $limit = 3)
{
    $result = cms_posts(1);
    $same = [];
    $other = [];
    foreach (($result['data'] ?? []) as $item) {
        if ($item['slug'] === $post['slug']) {
            continue;
        }
        if (!empty($post['category']) && ($item['category'] ?? '') === $post['category']) {
            $same[] = $item;
        } else {
            $other[] = $item;
        }
    }

    return array_slice(array_merge($same, $other), 0, $limit);
}

/** Small line icons used by the templates (stroke = currentColor). */
function cms_icon($name)
{
    static $paths = [
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'back' => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'x' => '<path d="M4 4l16 16M20 4L4 20"/>',
        'linkedin' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
        'whatsapp' => '<path d="M21 11.5a8.4 8.4 0 0 1-12.4 7.4L3 21l1.9-5.5A8.5 8.5 0 1 1 21 11.5z"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
        'pin' => '<path d="M12 22s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'feather' => '<path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/><path d="M16 8 2 22M17.5 15H9"/>',
        'pen' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/>',
        'megaphone' => '<path d="m3 11 18-5v12L3 14z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
        'headphones' => '<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>',
        'star' => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8-6.2-3.2-6.2 3.2L7 14.2 2 9.3l6.9-1z"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
    ];

    return '<svg class="cms-i" viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

/** Every JSON-LD block for a page: the generated one and the post's custom schema. */
function cms_schema_tags(array $meta)
{
    $html = '';
    foreach ([$meta['jsonld'] ?? null, $meta['schema'] ?? null] as $data) {
        if (is_array($data) && $data) {
            $html .= cms_json_ld($data) . "\n";
        }
    }

    return $html;
}

/** Re-encodes JSON-LD into a script tag. JSON_HEX_TAG stops "</script>" in the data from breaking out. */
function cms_json_ld(array $data)
{
    return '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP)
        . '</script>';
}

/** Stable id="" anchors for heading blocks (block index => anchor), shared by headings and tables of contents. */
function cms_heading_anchors($blocks)
{
    $anchors = [];
    $seen = [];
    foreach ((array) $blocks as $i => $block) {
        if (($block['type'] ?? '') !== 'heading') {
            continue;
        }
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) ($block['text'] ?? ''))), '-');
        $base = $base !== '' ? $base : 'section';
        $seen[$base] = ($seen[$base] ?? 0) + 1;
        $anchors[$i] = $seen[$base] > 1 ? $base . '-' . $seen[$base] : $base;
    }

    return $anchors;
}

/** Renders each block with cms/blocks/{type}.php. Unknown types are skipped. */
function cms_render_blocks($blocks)
{
    $anchors = cms_heading_anchors($blocks);
    foreach ((array) $blocks as $i => $block) {
        $type = $block['type'] ?? '';
        if (in_array($type, CMS_BLOCK_TYPES, true) && is_file($file = __DIR__ . '/blocks/' . $type . '.php')) {
            $block['anchor'] = $anchors[$i] ?? '';
            cms_include_block($file, $block);
        }
    }
}

/** Includes a block template with only $block in scope. */
function cms_include_block($__file, array $block)
{
    include $__file;
}

/** An icon that fits a service's name, for its box when it has no icon image of its own. */
function cms_service_icon($name)
{
    $name = strtolower((string) $name);
    foreach (['ghost' => 'feather', 'audio' => 'headphones', 'narrat' => 'headphones', 'market' => 'megaphone', 'promot' => 'megaphone',
        'publish' => 'book', 'print' => 'book', 'edit' => 'check', 'proof' => 'check', 'writ' => 'pen'] as $word => $icon) {
        if (strpos($name, $word) !== false) {
            return cms_icon($icon);
        }
    }

    return cms_icon('star');
}
