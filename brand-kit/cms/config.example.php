<?php
// Copy this file to cms/config.php and fill it in.
// The dashboard's Brand page shows these values ready to paste (Brand -> "Connect the website").

return [
    'panel_url'    => 'https://panel.youragency.com', // the central panel, no trailing slash
    'site'         => 'brand-a',                      // this brand's slug in the panel
    'api_key'      => '',                             // shown once when the brand is created
    'cache_secret' => '',                             // lets the panel clear this site's cache
    'site_url'     => 'https://brand-a.com',          // this website, used for canonical URLs and the sitemap

    // Optional:
    // 'cache_ttl'      => 600,   // seconds a cached response stays fresh
    // 'posts_per_page' => 9,
    // 'kit_url'        => '/cms', // public URL of this cms/ folder
    // The website's own header and footer (relative to the website root). The blog pages include them
    // and add each post's SEO tags to the header's <head>. Leave as is if the site uses includes/.
    // 'site_header'    => 'includes/header.php',
    // 'site_footer'    => 'includes/footer.php',
    // 'location_form'  => 'includes/form.php',   // the lead form in the location pages' banner
    // 'site_404'       => '404.php',              // shown for unknown addresses
    // 'clean_urls'     => true,                   // pages are /about, not /about.php
    // 'page_exclude'   => ['email', 'old-page'],  // .php files that are not pages (Pages & SEO, sitemap)
];
