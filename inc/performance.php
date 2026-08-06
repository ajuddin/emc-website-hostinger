<?php
/**
 * EMC Theme — inc/performance.php
 * Phase 12: Performance Optimization.
 *
 * Covers:
 *  1.  Native image lazy-loading (loading="lazy" + fetchpriority="high" for LCP)
 *  2.  Resource hints (preconnect, dns-prefetch, preload)
 *  3.  Google Fonts: display=swap + critical font preload
 *  4.  Font Awesome font-display swap via inline style override
 *  5.  Disable bloat: emojis, oEmbed, XML-RPC, RSD link, shortlink
 *  6.  Remove Gutenberg block library CSS on non-Gutenberg pages
 *  7.  Defer non-critical 3rd-party scripts
 *  8.  Remove query strings from static assets (for proxy caches)
 *  9.  File-mtime-based cache busting for local assets
 * 10.  Optimised WP_Query helpers (no_found_rows, etc.)
 * 11.  Native WordPress image loading without whole-page output buffering
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. Lazy-Load Images
   ========================================================================== */

/**
 * Add loading="lazy" and width/height to all post content images.
 * WordPress 5.5+ adds this natively for wp_get_attachment_image() images,
 * but this catches any manually written <img> tags in post content.
 */
add_filter( 'the_content', 'emc_lazy_load_content_images', 99 );
add_filter( 'post_thumbnail_html', 'emc_lazy_load_content_images', 99 );
add_filter( 'get_avatar', 'emc_lazy_load_content_images', 99 );

function emc_lazy_load_content_images( $content ) {
    if ( is_admin() || ! $content ) return $content;

    // Add loading="lazy" to <img> tags that don't already have a loading attribute
    $content = preg_replace_callback(
        '/<img([^>]+)>/i',
        function ( $matches ) {
            $tag = $matches[0];
            // Skip if already has loading attribute
            if ( stripos( $tag, 'loading=' ) !== false ) return $tag;
            // Skip if it's a data URI or no src
            if ( stripos( $tag, 'src=' ) === false ) return $tag;
            return str_replace( '<img', '<img loading="lazy" decoding="async"', $tag );
        },
        $content
    );

    return $content;
}

/**
 * Add fetchpriority="high" to the hero/LCP image (first featured image).
 * Applied only on the front page and singular views.
 */
add_filter( 'wp_get_attachment_image_attributes', 'emc_lcp_image_priority', 10, 3 );

function emc_lcp_image_priority( $attr, $attachment, $size ) {
    // Only target the hero / post thumbnail on front page or singular
    if ( ( is_front_page() || is_singular() ) && isset( $attr['class'] ) ) {
        if (
            strpos( $attr['class'], 'wp-post-image' ) !== false ||
            strpos( $attr['class'], 'attachment-emc-hero' ) !== false
        ) {
            $attr['fetchpriority'] = 'high';
            $attr['loading']       = 'eager'; // Don't lazy-load LCP image
        }
    }
    return $attr;
}


/* ==========================================================================
   2. Resource Hints — Preconnect & DNS Prefetch
   ========================================================================== */

add_action( 'wp_head', 'emc_resource_hints', 1 );

function emc_resource_hints() {
    $origins = array(
        // Google Fonts
        array( 'href' => 'https://fonts.googleapis.com',  'crossorigin' => false ),
        array( 'href' => 'https://fonts.gstatic.com',     'crossorigin' => true  ),
        // Font Awesome CDN
        array( 'href' => 'https://cdnjs.cloudflare.com',  'crossorigin' => false ),
    );

    foreach ( $origins as $origin ) {
        $co = $origin['crossorigin'] ? ' crossorigin' : '';
        echo '<link rel="preconnect" href="' . esc_url( $origin['href'] ) . '"' . $co . '>' . "\n";
        echo '<link rel="dns-prefetch" href="' . esc_url( $origin['href'] ) . '">' . "\n";
    }
}


/* ==========================================================================
   3. Google Fonts: display=swap is already added in helper-functions.php
      but we also add a <link rel="preload"> for the critical heading font.
   ========================================================================== */

add_action( 'wp_head', 'emc_preload_critical_font', 2 );

function emc_preload_critical_font() {
    // Preload the first Outfit woff2 (heading font — used above-the-fold)
    // This is a best-effort static URL for Outfit 700 Latin subset.
    // In production this would be self-hosted; this covers the CDN flow.
    $font_url = 'https://fonts.gstatic.com/s/outfit/v11/QGYvz_MVcBeNP4NjuGObqx1XmO1I4TC1C4G-EiAou6Y.woff2';
    echo '<link rel="preload" as="font" type="font/woff2" href="' . esc_url( $font_url ) . '" crossorigin>' . "\n";
}


/* ==========================================================================
   4. Font Awesome font-display: swap
      Font Awesome CDN doesn't set font-display:swap by default.
      We inject a tiny inline style that overrides the @font-face rules.
   ========================================================================== */

add_action( 'wp_head', 'emc_fa_font_display_swap', 3 );

function emc_fa_font_display_swap() {
    echo '<style id="emc-fa-swap">@font-face{font-display:swap!important}</style>' . "\n";
}


/* ==========================================================================
   5. Disable WordPress Bloat
   ========================================================================== */

/** Remove emoji scripts & styles (saves ~10 KB per page). */
remove_action( 'wp_head',             'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles',     'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles',  'print_emoji_styles' );
remove_filter( 'the_content_feed',    'wp_staticize_emoji' );
remove_filter( 'comment_text_rss',    'wp_staticize_emoji' );
remove_filter( 'wp_mail',             'wp_staticize_emoji_for_email' );
add_filter( 'emoji_svg_url', '__return_false' );

/** Remove oEmbed discovery links from <head>. */
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'wp_oembed_add_host_js' );

/** Remove Really Simple Discovery link. */
remove_action( 'wp_head', 'rsd_link' );

/** Remove Windows Live Writer manifest link. */
remove_action( 'wp_head', 'wlwmanifest_link' );

/** Remove shortlink from <head>. */
remove_action( 'wp_head', 'wp_shortlink_wp_head' );

/** Disable XML-RPC (not needed; reduces attack surface). */
add_filter( 'xmlrpc_enabled', '__return_false' );

/** Remove X-Pingback header. */
add_filter( 'wp_headers', function ( $headers ) {
    unset( $headers['X-Pingback'] );
    return $headers;
} );


/* ==========================================================================
   6. Remove Gutenberg Block Library CSS on Pages not Using Blocks
   ========================================================================== */

add_action( 'wp_enqueue_scripts', 'emc_remove_block_css', 100 );

function emc_remove_block_css() {
    // Remove if Elementor is rendering the page or page content has no blocks
    global $post;
    if (
        ! $post ||
        ! has_blocks( $post->post_content ) ||
        ( defined( 'ELEMENTOR_VERSION' ) && \Elementor\Plugin::$instance->db->is_built_with_elementor( $post->ID ) )
    ) {
        wp_dequeue_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library-theme' );
        wp_dequeue_style( 'global-styles' );           // WP 5.9+ full-site editing styles
        wp_dequeue_style( 'classic-theme-styles' );    // WP 6.1+
    }
}


/* ==========================================================================
   7. Defer Non-Critical Scripts
   ========================================================================== */

add_filter( 'script_loader_tag', 'emc_defer_scripts', 10, 2 );

function emc_defer_scripts( $tag, $handle ) {
    // Scripts to defer (non-critical, loaded after DOM)
    $defer_scripts = array(
        'emc-modal',
        'emc-slider',
    );

    // Scripts to async (fully independent, order doesn't matter)
    $async_scripts = array();

    if ( is_admin() ) return $tag;

    if ( in_array( $handle, $defer_scripts, true ) ) {
        return str_replace( ' src=', ' defer src=', $tag );
    }

    if ( in_array( $handle, $async_scripts, true ) ) {
        return str_replace( ' src=', ' async src=', $tag );
    }

    return $tag;
}


/* ==========================================================================
   8. Remove Query Strings from Static Assets
      (Improves caching hit rates on CDNs and proxy servers)
   ========================================================================== */

add_filter( 'style_loader_src',  'emc_remove_query_strings', 15 );
add_filter( 'script_loader_src', 'emc_remove_query_strings', 15 );

function emc_remove_query_strings( $src ) {
    // Don't strip from external CDN sources (fonts, FA, etc.)
    if ( strpos( $src, get_site_url() ) === false ) {
        return $src;
    }
    // Strip query string ver= parameter from local files
    $parts = explode( '?', $src );
    return $parts[0];
}


/* ==========================================================================
   9. File-mtime Cache Busting for Local Assets
      Replaces static EMC_VERSION with the file's last-modified timestamp
      so browsers auto-invalidate the cache when a file changes.
   ========================================================================== */

add_filter( 'style_loader_src',  'emc_mtime_version', 20, 2 );
add_filter( 'script_loader_src', 'emc_mtime_version', 20, 2 );

function emc_mtime_version( $src, $handle ) {
    // Only apply to EMC theme assets
    if ( strpos( $src, EMC_URI ) === false ) return $src;

    // Convert URI → filesystem path
    $path = str_replace( EMC_URI, EMC_DIR, $src );
    // Strip existing query string
    $path = explode( '?', $path )[0];

    if ( file_exists( $path ) ) {
        return add_query_arg( 'v', filemtime( $path ), $src );
    }

    return $src;
}


/* ==========================================================================
   10. Optimised WP_Query Defaults
       Adds performance flags to the main query and custom queries.
   ========================================================================== */

/**
 * For archive and home pages: turn off SQL_CALC_FOUND_ROWS when we don't
 * need pagination (e.g., homepage recent-events widgets).
 */
add_action( 'pre_get_posts', 'emc_optimise_main_query' );

function emc_optimise_main_query( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) return;

    // On CPT archives (events, services) limit fields to what we need
    if ( $query->is_post_type_archive( array( 'emc_event', 'emc_service', 'emc_vacancy' ) ) ) {
        $query->set( 'no_found_rows', false ); // keep for pagination
        $query->set( 'update_post_term_cache', false );
    }
}

/**
 * Helper: create a performance-optimised WP_Query for listing CPT posts.
 * Used by template-parts to avoid redundant meta/term cache warming.
 *
 * @param  string $post_type  CPT slug.
 * @param  int    $count      Number of posts.
 * @param  array  $extra_args Additional WP_Query args.
 * @return WP_Query
 */
function emc_get_posts_query( $post_type, $count = 6, $extra_args = array() ) {
    $defaults = array(
        'post_type'              => $post_type,
        'posts_per_page'         => $count,
        'post_status'            => 'publish',
        'no_found_rows'          => true,   // Skip COUNT(*) — we don't paginate here
        'update_post_meta_cache' => false,  // Don't warm meta cache unless we need it
        'update_post_term_cache' => false,  // Don't warm term cache unless we need it
        'orderby'                => 'date',
        'order'                  => 'DESC',
    );
    return new WP_Query( array_merge( $defaults, $extra_args ) );
}


/* ==========================================================================
   11. Image Loading Compatibility
       Whole-page rewriting is disabled to remain compatible with page caches.
   ========================================================================== */

/*
 * Whole-page buffering is intentionally disabled. WordPress already adds
 * native image loading attributes, and an additional response buffer can
 * conflict with LiteSpeed/Hostinger caching and cache a partial document.
 */

/**
 * Never page-cache logged-in front-end requests.
 *
 * Administrators see personalized markup such as the admin toolbar. Preventing
 * LiteSpeed from storing that private response also keeps a transient PHP error
 * or partial response from becoming the user's cached page.
 */
function emc_disable_logged_in_page_cache() {
    if ( is_admin() || ! is_user_logged_in() ) {
        return;
    }

    nocache_headers();

    if ( ! headers_sent() ) {
        header( 'X-LiteSpeed-Cache-Control: no-cache' );
    }
}
add_action( 'send_headers', 'emc_disable_logged_in_page_cache', 99 );

/* ==========================================================================
   12. Critical CSS Inline Hint
       Add a <link rel="preload"> for the main stylesheet so it loads ASAP.
   ========================================================================== */

add_action( 'wp_head', 'emc_preload_main_stylesheet', 1 );

function emc_preload_main_stylesheet() {
    $css_path = EMC_DIR . '/assets/css/style.css';
    $version  = file_exists( $css_path ) ? filemtime( $css_path ) : EMC_VERSION;
    $css_url  = EMC_ASSETS . '/css/style.css?v=' . $version;
    echo '<link rel="preload" href="' . esc_url( $css_url ) . '" as="style">' . "\n";
}


/* ==========================================================================
   13. Limit Post Revisions & Auto-Save Interval
   ========================================================================== */

if ( ! defined( 'WP_POST_REVISIONS' ) ) {
    define( 'WP_POST_REVISIONS', 5 );
}

/** Increase auto-save interval to reduce DB writes during editing. */
add_filter( 'autosave_interval', function () { return 120; } ); // 2 minutes


/* ==========================================================================
   14. Heartbeat API Throttle (reduces AJAX polling overhead)
   ========================================================================== */

add_filter( 'heartbeat_settings', 'emc_throttle_heartbeat' );

function emc_throttle_heartbeat( $settings ) {
    // Front-end: disable (not needed)
    if ( ! is_admin() ) {
        $settings['suspend'] = true;
    } else {
        // Admin: poll every 60s instead of 15s
        $settings['interval'] = 60;
    }
    return $settings;
}
