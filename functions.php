<?php
/**
 * EMC Theme — functions.php
 *
 * Central hub for theme setup, asset enqueueing, menu registration,
 * widget areas, and custom post type includes.
 *
 * @package emc-theme
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   Constants
   ========================================================================== */
define( 'EMC_VERSION',   '1.0.0' );
define( 'EMC_DIR',       get_template_directory() );
define( 'EMC_URI',       get_template_directory_uri() );
define( 'EMC_ASSETS',    EMC_URI . '/assets' );

/* ==========================================================================
   1. Theme Setup
   ========================================================================== */
if ( ! function_exists( 'emc_theme_setup' ) ) :
    function emc_theme_setup() {

        // Make theme available for translation
        load_theme_textdomain( 'emc-theme', EMC_DIR . '/languages' );

        // Add default posts and comments RSS feed links to head
        add_theme_support( 'automatic-feed-links' );

        // Let WordPress manage the document title
        add_theme_support( 'title-tag' );

        // Enable support for Post Thumbnails
        add_theme_support( 'post-thumbnails' );
        set_post_thumbnail_size( 1200, 630, true );

        // Custom image sizes
        add_image_size( 'emc-hero',      1920, 900,  true );
        add_image_size( 'emc-card',       600, 400,  true );
        add_image_size( 'emc-thumbnail',  400, 300,  true );
        add_image_size( 'emc-square',     400, 400,  true );

        // Register navigation menus
        register_nav_menus( array(
            'primary'          => __( 'Primary Navigation', 'emc-theme' ),
            'footer'           => __( 'Footer Quick Links', 'emc-theme' ),
            'footer-community' => __( 'Footer Community Links', 'emc-theme' ),
            'mobile'           => __( 'Mobile Navigation', 'emc-theme' ),
        ) );

        // Switch default core markup to output valid HTML5
        add_theme_support( 'html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ) );

        // Custom logo
        add_theme_support( 'custom-logo', array(
            'height'      => 60,
            'width'       => 200,
            'flex-height' => true,
            'flex-width'  => true,
            'header-text' => array( 'site-title', 'site-description' ),
        ) );

        // Custom background
        add_theme_support( 'custom-background', array(
            'default-color' => 'F8FAFC',
        ) );

        // Add support for core custom header
        add_theme_support( 'customize-selective-refresh-widgets' );

        // Align wide / full-width blocks (Gutenberg)
        add_theme_support( 'align-wide' );

        // Responsive embeds
        add_theme_support( 'responsive-embeds' );
    }
endif;
add_action( 'after_setup_theme', 'emc_theme_setup' );


/* ==========================================================================
   2. Content Width
   ========================================================================== */
if ( ! isset( $content_width ) ) {
    $content_width = 1280;
}


/* ==========================================================================
   2b. Auto-assign page templates by slug
   ========================================================================== */
/**
 * Automatically assign specific page templates based on the page slug,
 * so the admin doesn't need to manually set them in the editor.
 */
add_filter( 'template_include', function ( $template ) {
    if ( is_page() ) {
        $slug = get_post_field( 'post_name', get_queried_object_id() );

        $slug_templates = array(
            'campaign'        => 'template-campaign.php',
            'badr-wall-tiles' => 'page-badr-wall-tiles.php',
        );

        if ( isset( $slug_templates[ $slug ] ) ) {
            $custom = locate_template( $slug_templates[ $slug ] );
            if ( $custom ) {
                return $custom;
            }
        }
    }
    return $template;
} );


/* ==========================================================================
   3. Enqueue Styles & Scripts
   ========================================================================== */
if ( ! function_exists( 'emc_enqueue_assets' ) ) :
    function emc_enqueue_assets() {

        // ── Google Fonts (dynamically built from Customizer font selections) ─
        wp_enqueue_style(
            'emc-google-fonts',
            emc_get_google_fonts_url(),
            array(),
            null
        );

        // ── Font Awesome (self-hosted — reliable on local + restricted networks) ─
        $fa_css_path = EMC_DIR . '/assets/fonts/fontawesome/css/all.min.css';
        wp_enqueue_style(
            'font-awesome',
            EMC_URI . '/assets/fonts/fontawesome/css/all.min.css',
            array(),
            file_exists( $fa_css_path ) ? filemtime( $fa_css_path ) : '6.4.0'
        );


        // ── Core Theme CSS ────────────────────────────────────────────────
        $style_path = EMC_DIR . '/assets/css/style.css';
        wp_enqueue_style(
            'emc-style',
            EMC_ASSETS . '/css/style.css',
            array( 'emc-google-fonts', 'font-awesome' ),
            file_exists( $style_path ) ? filemtime( $style_path ) : EMC_VERSION
        );

        // ── Page-Specific CSS ─────────────────────────────────────────────
        // Loaded conditionally by page template (handled in template files)
        // Payment page assets are supplied by the separate EMC Payments plugin.

        // ── Blog CSS (archive + single posts) ────────────────────────────
        if ( is_home() || is_single() || is_category() || is_tag() || is_author() || is_search() || is_archive() ) {
            $blog_css_path = EMC_DIR . '/assets/css/blog.css';
            if ( file_exists( $blog_css_path ) ) {
                wp_enqueue_style(
                    'emc-blog',
                    EMC_ASSETS . '/css/blog.css',
                    array( 'emc-style' ),
                    filemtime( $blog_css_path )
                );
            }

            $blog_js_path = EMC_DIR . '/assets/js/blog.js';
            if ( file_exists( $blog_js_path ) ) {
                wp_enqueue_script(
                    'emc-blog',
                    EMC_ASSETS . '/js/blog.js',
                    array(),
                    filemtime( $blog_js_path ),
                    true
                );
            }
        }

        // ── Core JS ───────────────────────────────────────────────────────
        wp_enqueue_script(
            'emc-script',
            EMC_ASSETS . '/js/script.js',
            array(),
            EMC_VERSION,
            true // load in footer
        );

        // ── Modal System ──────────────────────────────────────────────────
        wp_enqueue_script(
            'emc-modal',
            EMC_ASSETS . '/js/modal.js',
            array( 'emc-script' ),
            EMC_VERSION,
            true
        );

        // ── Slider / Carousel ─────────────────────────────────────────────
        wp_enqueue_script(
            'emc-slider',
            EMC_ASSETS . '/js/slider.js',
            array( 'emc-script' ),
            EMC_VERSION,
            true
        );

        // ── Prayer Times Top Bar (sitewide) ──────────────────────────────
        $topbar_js_path = EMC_DIR . '/assets/js/prayer-topbar.js';
        wp_enqueue_script(
            'emc-prayer-topbar',
            EMC_ASSETS . '/js/prayer-topbar.js',
            array(),
            file_exists( $topbar_js_path ) ? filemtime( $topbar_js_path ) : EMC_VERSION,
            true
        );
        // Pass the JSON data URL so the top bar JS can fetch it
        wp_localize_script( 'emc-prayer-topbar', 'emcPrayer', array(
            'dataUrl'  => emc_prayer_data_url(),
            'timezone' => wp_timezone_string() ?: 'UTC',
        ) );

        // ── Localize script data ──────────────────────────────────────────

        wp_localize_script( 'emc-script', 'emcData', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'emc_nonce' ),
            'themeUri' => EMC_URI,
            'prayerDataUrl' => emc_prayer_data_url(),
        ) );

        // ── Comments ──────────────────────────────────────────────────────
        if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
            wp_enqueue_script( 'comment-reply' );
        }
    }
endif;
add_action( 'wp_enqueue_scripts', 'emc_enqueue_assets' );


/* ==========================================================================
   3b. Page-Specific Asset Enqueueing
   ========================================================================== */
if ( ! function_exists( 'emc_enqueue_page_assets' ) ) :
    function emc_enqueue_page_assets() {
        if ( ! is_page() ) {
            return;
        }

        if ( is_page_template( 'template-events.php' ) ) {
            $slug = 'events';
        } elseif ( is_page_template( 'page-volunteer.php' ) ) {
            // The legacy template filename is retained for existing sites, while
            // the public page is now the Job Application page.
            $slug = 'job-application';
        } elseif ( is_page_template( 'page-volunteer-registration.php' ) ) {
            $slug = 'volunteer';
        } else {
            $slug = get_post_field( 'post_name', get_queried_object_id() );
        }

        // Donate and Ramadan templates enqueue their own Stripe dependencies/config.
        if ( in_array( $slug, array( 'donate', 'ramadan' ), true ) ) {
            return;
        }

        $map = array(
            'events'        => array( 'css' => 'events.css',       'js' => 'events.js' ),
            'contact'       => array( 'css' => 'contact.css',      'js' => 'contact.js' ),
            'prayer-times'  => array( 'css' => 'prayer-times.css', 'js' => 'prayer-times.js' ),
            'media'         => array( 'css' => 'media.css',        'js' => 'media.js' ),
            'services'      => array( 'css' => 'services.css',     'js' => 'services.js' ),
            'about'         => array( 'css' => 'about.css',        'js' => 'about.js' ),
            'campaign'      => array( 'css' => 'campaign.css',     'js' => 'campaign.js' ),
            'gift-aid'      => array( 'css' => 'gift-aid.css',     'js' => 'gift-aid.js' ),
            'volunteer'       => array( 'css' => 'volunteer.css', 'js' => 'volunteer.js' ),
            'job-application' => array( 'css' => 'volunteer.css', 'js' => 'volunteer.js' ),
        );

        if ( ! isset( $map[ $slug ] ) ) {
            return;
        }

        $assets   = $map[ $slug ];
        $css_path = EMC_DIR . '/assets/css/' . $assets['css'];
        $js_path  = EMC_DIR . '/assets/js/'  . $assets['js'];

        if ( file_exists( $css_path ) ) {
            wp_enqueue_style(
                'emc-page-' . $slug,
                EMC_ASSETS . '/css/' . $assets['css'],
                array( 'emc-style' ),
                filemtime( $css_path )
            );

            if ( 'events' === $slug ) {
                $registration_css = EMC_DIR . '/assets/css/event-registration.css';
                wp_enqueue_style(
                    'emc-event-registration',
                    EMC_ASSETS . '/css/event-registration.css',
                    array( 'emc-page-events' ),
                    file_exists( $registration_css ) ? filemtime( $registration_css ) : EMC_VERSION
                );
            }
        }

        if ( file_exists( $js_path ) ) {
            $handle = 'emc-page-' . $slug;
            wp_enqueue_script(
                $handle,
                EMC_ASSETS . '/js/' . $assets['js'],
                array( 'emc-script' ),
                filemtime( $js_path ),
                true
            );

            if ( 'events' === $slug ) {
                wp_localize_script( $handle, 'emcEventsConfig', array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'emc_event_registration' ),
                ) );
            }
        }
    }
endif;
add_action( 'wp_enqueue_scripts', 'emc_enqueue_page_assets' );

/* Enqueue CPT-specific assets for single/archive/taxonomy views */
if ( ! function_exists( 'emc_enqueue_cpt_assets' ) ) :
    function emc_enqueue_cpt_assets() {
        $cpt_map = array(
            'emc_event'   => array( 'css' => 'events.css',   'js' => 'events.js' ),
            'emc_service' => array( 'css' => 'services.css', 'js' => 'services.js' ),
        );

        // Map taxonomies to their parent CPT
        $tax_map = array(
            'event_category' => 'emc_event',
        );

        $post_type = '';
        if ( is_singular() ) {
            $post_type = get_post_type();
        } elseif ( is_post_type_archive() ) {
            $post_type = get_query_var( 'post_type' );
        } elseif ( is_tax() ) {
            $queried = get_queried_object();
            if ( $queried && isset( $tax_map[ $queried->taxonomy ] ) ) {
                $post_type = $tax_map[ $queried->taxonomy ];
            }
        }

        if ( ! $post_type || ! isset( $cpt_map[ $post_type ] ) ) {
            return;
        }

        $assets   = $cpt_map[ $post_type ];
        $css_path = EMC_DIR . '/assets/css/' . $assets['css'];
        $js_path  = EMC_DIR . '/assets/js/' . $assets['js'];

        if ( file_exists( $css_path ) ) {
            wp_enqueue_style(
                'emc-cpt-' . $post_type,
                EMC_ASSETS . '/css/' . $assets['css'],
                array( 'emc-style' ),
                filemtime( $css_path )
            );

            if ( 'emc_event' === $post_type ) {
                $registration_css = EMC_DIR . '/assets/css/event-registration.css';
                wp_enqueue_style(
                    'emc-event-registration',
                    EMC_ASSETS . '/css/event-registration.css',
                    array( 'emc-cpt-emc_event' ),
                    file_exists( $registration_css ) ? filemtime( $registration_css ) : EMC_VERSION
                );
            }
        }

        if ( file_exists( $js_path ) ) {
            $handle = 'emc-cpt-' . $post_type;
            $script_dependencies = array( 'emc-script' );
            if ( 'emc_event' === $post_type && is_singular( 'emc_event' ) ) {
                $event_payment = emc_event_payment_config( get_queried_object_id() );
                if ( $event_payment['enabled'] && emc_event_stripe_is_available() ) {
                    wp_enqueue_script( 'stripe-js', 'https://js.stripe.com/v3/', array(), null, true );
                    $script_dependencies[] = 'stripe-js';
                }
            }
            wp_enqueue_script(
                $handle,
                EMC_ASSETS . '/js/' . $assets['js'],
                $script_dependencies,
                filemtime( $js_path ),
                true
            );

            if ( 'emc_event' === $post_type ) {
                wp_localize_script( $handle, 'emcEventsConfig', array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'emc_event_registration' ),
                ) );
            }
        }
    }
endif;
add_action( 'wp_enqueue_scripts', 'emc_enqueue_cpt_assets' );


/* ==========================================================================
   4. Widget Areas (Sidebars)
   ========================================================================== */
if ( ! function_exists( 'emc_register_sidebars' ) ) :
    function emc_register_sidebars() {
        $defaults = array(
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h4 class="widget-title">',
            'after_title'   => '</h4>',
        );

        register_sidebar( array_merge( $defaults, array(
            'name'        => __( 'Blog Sidebar', 'emc-theme' ),
            'id'          => 'sidebar-blog',
            'description' => __( 'Widgets displayed alongside blog posts and archives.', 'emc-theme' ),
        ) ) );

        register_sidebar( array_merge( $defaults, array(
            'name'        => __( 'Footer Column 1', 'emc-theme' ),
            'id'          => 'footer-1',
            'description' => __( 'First footer column — branding & about text.', 'emc-theme' ),
        ) ) );

        register_sidebar( array_merge( $defaults, array(
            'name'        => __( 'Footer Column 2', 'emc-theme' ),
            'id'          => 'footer-2',
            'description' => __( 'Second footer column — quick links.', 'emc-theme' ),
        ) ) );

        register_sidebar( array_merge( $defaults, array(
            'name'        => __( 'Footer Column 3', 'emc-theme' ),
            'id'          => 'footer-3',
            'description' => __( 'Third footer column — community links.', 'emc-theme' ),
        ) ) );

        register_sidebar( array_merge( $defaults, array(
            'name'        => __( 'Footer Column 4', 'emc-theme' ),
            'id'          => 'footer-4',
            'description' => __( 'Fourth footer column — contact info.', 'emc-theme' ),
        ) ) );
    }
endif;
add_action( 'widgets_init', 'emc_register_sidebars' );

/**
 * Apply the administrator-configured post count to all standard blog lists.
 */
function emc_apply_blog_posts_per_page( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( $query->is_home() || $query->is_category() || $query->is_tag() || $query->is_author() || $query->is_date() ) {
        $posts_per_page = absint( emc_option( 'emc_blog_posts_per_page', 9 ) );
        if ( $posts_per_page ) {
            $query->set( 'posts_per_page', $posts_per_page );
        }
    }
}
add_action( 'pre_get_posts', 'emc_apply_blog_posts_per_page' );


/* ==========================================================================
   5. Include Modular Files
   ========================================================================== */
$emc_includes = array(
    '/inc/custom-post-types.php',   // CPTs: Services, Events, Vacancies, etc.
    '/inc/meta-boxes.php',          // Native meta boxes for CPT custom fields
    '/inc/campaign-manager.php',    // Campaign goals, Stripe totals, controls, and admin reporting
    '/inc/admin-columns.php',       // Custom admin list columns for all CPTs
    '/inc/customizer.php',          // Theme Customizer options
    '/inc/customizer-pages.php',    // Page Content Customizer sections (migrated from ACF)
    '/inc/admin-organizer.php',     // Customer-friendly EMC admin hub and grouped navigation
    '/inc/site-content-editor.php', // Central editor for every theme string and operational value
    '/inc/form-notifications.php',  // Central recipients and delivery controls for every public form
    '/inc/helper-functions.php',    // Utility functions
    '/inc/prayer-timetable-import.php', // Admin Masjidbox XLSX timetable importer
    '/inc/donation-csv-export.php', // Admin CSV exports for EMC Payments records
    '/inc/form-response-exports.php', // CSV exports for every stored website form response
    '/inc/badr-wall.php',           // Badr Wall tile records, gallery, and payment intake
    '/inc/gallery-admin.php',       // Bulk gallery uploads, category assignment, and previews
    '/inc/annual-reports.php',      // Dynamic About page annual reports manager
    '/inc/media-videos.php',        // Admin-managed, inline-playable media videos
    '/inc/acf-helpers.php',         // ACF wrapper (emc_acf, emc_acf_image) with fallback
    '/inc/acf-fields.php',          // ACF field group definitions for page templates
    '/inc/shortcodes.php',          // Shortcodes (prayer times, campaign bar, etc.)
    '/inc/ajax-handlers.php',       // Theme-only AJAX handlers
    '/inc/contact-submissions.php', // Contact form records and administrator inbox
    '/inc/newsletter.php',          // Mailchimp newsletter sync, submissions, and settings
    '/inc/gift-aid.php',            // Gift Aid declaration storage and admin records
    '/inc/volunteers.php',          // Job applications, CVs, dynamic fields, and admin records
    '/inc/volunteer-signups.php',   // Separate volunteer applications and admin records
    '/inc/event-registrations.php', // Event forms, email notifications, submissions, and settings
    '/inc/elementor-compat.php',    // Elementor compatibility (locations, style fixes)
    '/inc/elementor-widgets.php',   // Custom Elementor widgets (donate, prayer, counter)
    // Phase 11 — Demo Import System
    '/inc/demo-data.php',           // Demo content definitions (pages, menus, theme_mods)
    '/inc/demo-import.php',         // Import engine + AJAX handler
    '/inc/setup-wizard.php',        // Admin Appearance > EMC Setup wizard UI
    // Phase 12 — Performance Optimization
    '/inc/performance.php',         // Lazy-load, resource hints, bloat removal, query optimisation
);


foreach ( $emc_includes as $file ) {
    $filepath = EMC_DIR . $file;
    if ( file_exists( $filepath ) ) {
        require_once $filepath;
    }
}


/* ==========================================================================
   5b. Auto-seed Service Posts
   Creates the 8 core emc_service posts automatically on first theme load.
   Re-runs on any environment (local / Hostinger) where posts are missing.
   ========================================================================== */
function emc_auto_seed_services() {

    // Skip if already seeded on this install
    if ( get_option( 'emc_services_seeded' ) ) {
        return;
    }

    $services = array(
        array(
            'title'   => 'Islamic Education',
            'slug'    => 'islamic-education',
            'icon'    => 'fas fa-book-open',
            'excerpt' => 'Weekend Madrasah, Quran lessons, and Islamic studies for children and adults of all levels.',
            'order'   => 1,
            'content' => '<h2>About Our Islamic Education Programme</h2>
<p>At Essex Muslim Centre, we believe that every Muslim should have the opportunity to connect with the Quran and Islamic teachings. Our Islamic Education programme serves children and adults across Chelmsford and Essex.</p>
<h3>What We Offer</h3>
<ul>
<li><strong>Weekend Madrasah (Ages 5–16):</strong> Saturday and Sunday classes covering Quran recitation with Tajweed, Islamic Studies, Seerah, and core Islamic knowledge.</li>
<li><strong>Adult Islamic Studies:</strong> Beginner-friendly classes covering worship, Quran, character, and everyday Islamic practice.</li>
<li><strong>Quran Memorisation (Hifz):</strong> Structured programme for dedicated students with individual mentor support.</li>
<li><strong>Quran Recitation &amp; Tajweed:</strong> Perfecting pronunciation and application of Tajweed rules for all ages.</li>
</ul>
<h3>Timings</h3>
<p>Weekend classes run Saturday and Sunday mornings. Adult classes are available on weekday evenings. Contact us for the current timetable.</p>
<h3>How to Enrol</h3>
<p>Registration is open at the beginning of each term. Contact our office or visit the centre to pick up a registration form. Subsidised rates are available for families in financial need.</p>',
        ),
        array(
            'title'   => 'Nikah (Marriage)',
            'slug'    => 'nikah-marriage',
            'icon'    => 'fas fa-ring',
            'excerpt' => 'Islamic marriage ceremonies conducted by our Imam with pre-marriage guidance and support.',
            'order'   => 2,
            'content' => '<h2>Nikah Ceremonies at EMC</h2>
<p>Marriage is half of one\'s deen. At Essex Muslim Centre, our Imams conduct Islamic marriage (Nikah) ceremonies in a blessed and dignified environment.</p>
<h3>Our Services Include</h3>
<ul>
<li><strong>Nikah Ceremony:</strong> A formal Islamic marriage contract conducted in the presence of witnesses and according to Sunnah.</li>
<li><strong>Pre-Marriage Guidance:</strong> One-to-one confidential sessions with our Imam covering rights, responsibilities, and expectations in Islamic marriage.</li>
<li><strong>Documentation Support:</strong> Guidance on obtaining the relevant civil marriage documents alongside the Islamic ceremony.</li>
<li><strong>Venue:</strong> Our main hall can accommodate families and guests for the ceremony.</li>
</ul>
<h3>How to Book</h3>
<p>To book a Nikah ceremony, please contact our office at least 4 weeks in advance. Both parties and their walis (guardians) should be present for the initial consultation.</p>',
        ),
        array(
            'title'   => 'Janaza Services',
            'slug'    => 'janaza-services',
            'icon'    => 'fas fa-praying-hands',
            'excerpt' => 'Compassionate funeral prayer, ghusl, and burial coordination services available 24/7.',
            'order'   => 3,
            'content' => '<h2>Janaza Services — Available 24/7</h2>
<p>Losing a loved one is one of life\'s most difficult moments. Essex Muslim Centre is here to support your family through the entire Janaza (funeral) process with compassion, care, and full adherence to Islamic tradition.</p>
<h3>Services We Provide</h3>
<ul>
<li><strong>Ghusl (Islamic Washing):</strong> Carried out by trained volunteers in our on-site ghusl facility, with separate facilities for male and female deceased.</li>
<li><strong>Kafan (Shrouding):</strong> Preparation and wrapping of the deceased according to Sunnah.</li>
<li><strong>Janaza Prayer (Salat ul-Janaza):</strong> Congregational funeral prayer led by our Imam.</li>
<li><strong>Burial Coordination:</strong> Liaison with local cemeteries including Chelmsford City Council cemeteries for Muslim burial plots.</li>
<li><strong>Repatriation Guidance:</strong> Support for families wishing to repatriate the deceased to their home country.</li>
</ul>
<h3>Contact Us Urgently</h3>
<p>For immediate Janaza support, contact our 24/7 emergency line. Our team will guide you through every step with sensitivity and Islamic care.</p>',
        ),
        array(
            'title'   => 'Meet an Imam',
            'slug'    => 'meet-an-imam',
            'icon'    => 'fas fa-user-tie',
            'excerpt' => 'Schedule a one-to-one appointment with our Imam for spiritual guidance, counselling, or Islamic advice.',
            'order'   => 4,
            'content' => '<h2>Meet an Imam — Private Appointments</h2>
<p>Our resident Imam is available for private, confidential one-to-one appointments. Whether you are seeking Islamic guidance, spiritual support, or advice on a personal matter, we are here to help.</p>
<h3>Areas of Guidance</h3>
<ul>
<li><strong>Spiritual Counselling:</strong> Personal struggles, mental wellbeing from an Islamic perspective, and strengthening one\'s relationship with Allah.</li>
<li><strong>Family &amp; Relationship Advice:</strong> Islamic guidance on marriage, divorce, parenting, and family conflict resolution.</li>
<li><strong>Islamic Rulings (Fatawa):</strong> Questions on halal/haram, worship, business transactions, and day-to-day Islamic practice.</li>
<li><strong>Reversion Support:</strong> Dedicated guidance for those considering or who have recently embraced Islam.</li>
<li><strong>Grief &amp; Bereavement:</strong> Spiritual support for those dealing with loss.</li>
</ul>
<h3>How to Book</h3>
<p>Appointments are available on weekday afternoons and by arrangement. Please contact the office to schedule a session. All conversations are strictly confidential.</p>',
        ),
        array(
            'title'   => 'Welfare Services',
            'slug'    => 'welfare-services',
            'icon'    => 'fas fa-hand-holding-heart',
            'excerpt' => 'Food bank partnerships, financial counselling, and support for families in need.',
            'order'   => 5,
            'content' => '<h2>Community Welfare &amp; Support</h2>
<p>Islam places great emphasis on caring for those in need. Essex Muslim Centre works to support vulnerable members of our community through a range of welfare services, carried out with dignity and respect.</p>
<h3>What We Offer</h3>
<ul>
<li><strong>Emergency Food Parcels:</strong> In partnership with local food banks, we arrange emergency food support for families in crisis.</li>
<li><strong>Zakat &amp; Sadaqah Distribution:</strong> We collect and distribute Zakat and Sadaqah funds to eligible recipients in the local Muslim community.</li>
<li><strong>Financial Guidance:</strong> Signposting to halal financial advice, benefits entitlement support, and debt counselling services.</li>
<li><strong>Mental Health Referrals:</strong> Culturally sensitive referrals to NHS and third-sector mental health support services.</li>
<li><strong>Community Befriending:</strong> Connecting isolated elderly or vulnerable individuals with community volunteers.</li>
</ul>
<h3>Confidential Support</h3>
<p>All welfare enquiries are handled confidentially by our trained volunteers. To speak to a welfare officer, please contact the centre directly.</p>',
        ),
        array(
            'title'   => 'General Events',
            'slug'    => 'general-events',
            'icon'    => 'fas fa-calendar-alt',
            'excerpt' => 'Community gatherings, Islamic talks, sports days, and interfaith activities throughout the year.',
            'order'   => 6,
            'content' => '<h2>EMC Events Programme</h2>
<p>Throughout the year, Essex Muslim Centre organises a rich and varied programme of events for all members of the community — young and old, Muslim and non-Muslim alike.</p>
<h3>Types of Events</h3>
<ul>
<li><strong>Islamic Talks &amp; Lectures:</strong> Monthly talks by visiting scholars and local Imams on topics relevant to contemporary Muslim life.</li>
<li><strong>Eid Celebrations:</strong> Annual Eid ul-Fitr and Eid ul-Adha community gatherings with food, activities, and celebrations.</li>
<li><strong>Ramadan Programme:</strong> Tarawih prayers, Iftar gatherings, Laylatul Qadr events, and charity fundraisers throughout the holy month.</li>
<li><strong>Youth Sports Days:</strong> Football, cricket, and multi-sport events for young people in the community.</li>
<li><strong>Interfaith Dialogue:</strong> Open days, mosque tours, and interfaith panel discussions welcoming neighbours of all faiths.</li>
<li><strong>Fundraising Dinners:</strong> Annual gala dinners and community fundraisers supporting the building campaign.</li>
</ul>
<h3>Stay Updated</h3>
<p>Follow our Events page and social media channels to stay up to date with upcoming events. All are welcome unless otherwise stated. Many events are free to attend.</p>',
        ),
    );

    $all_done = true;

    foreach ( $services as $svc ) {
        $existing = get_page_by_path( $svc['slug'], OBJECT, 'emc_service' );
        if ( $existing ) {
            continue; // already exists, skip
        }

        $post_id = wp_insert_post( array(
            'post_type'    => 'emc_service',
            'post_title'   => $svc['title'],
            'post_name'    => $svc['slug'],
            'post_content' => $svc['content'],
            'post_excerpt' => $svc['excerpt'],
            'post_status'  => 'publish',
            'menu_order'   => $svc['order'],
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_emc_service_icon',     $svc['icon'] );
            update_post_meta( $post_id, '_emc_service_order',    $svc['order'] );
            update_post_meta( $post_id, '_emc_service_featured', '1' );
        } else {
            $all_done = false;
        }
    }

    // Flush rewrite rules so /service/slug/ URLs work immediately
    flush_rewrite_rules( false );

    // Mark as done so this block never runs again on this install
    if ( $all_done ) {
        update_option( 'emc_services_seeded', '1' );
    }
}
add_action( 'init', 'emc_auto_seed_services', 20 );

/**
 * Keep core service posts aligned with the current EMC service list.
 */
function emc_update_core_services_v2() {
    $seed_version = 2;

    if ( (int) get_option( 'emc_services_seed_version', 0 ) >= $seed_version ) {
        return;
    }

    $changed = false;

    $education = array(
        'title'   => 'Islamic Education',
        'slug'    => 'islamic-education',
        'icon'    => 'fas fa-book-open',
        'excerpt' => 'Weekend Madrasah, Quran lessons, and Islamic studies for children and adults of all levels.',
        'order'   => 1,
        'content' => '<h2>About Our Islamic Education Programme</h2>
<p>At Essex Muslim Centre, we believe that every Muslim should have the opportunity to connect with the Quran and Islamic teachings. Our Islamic Education programme serves children and adults across Chelmsford and Essex.</p>
<h3>What We Offer</h3>
<ul>
<li><strong>Weekend Madrasah:</strong> Classes covering Quran recitation with Tajweed, Islamic Studies, Seerah, and core Islamic knowledge.</li>
<li><strong>Adult Islamic Studies:</strong> Beginner-friendly classes covering worship, Quran, character, and everyday Islamic practice.</li>
<li><strong>Quran Memorisation:</strong> Structured support for dedicated students.</li>
<li><strong>Quran Recitation &amp; Tajweed:</strong> Perfecting pronunciation and application of Tajweed rules for all ages.</li>
</ul>
<h3>How to Enrol</h3>
<p>Registration is open at the beginning of each term. Contact our office or visit the centre to pick up a registration form.</p>',
    );

    $existing_education = get_page_by_path( 'islamic-education', OBJECT, 'emc_service' );
    if ( ! $existing_education ) {
        $existing_education = get_page_by_path( 'arabic-education', OBJECT, 'emc_service' );
    }

    if ( $existing_education ) {
        wp_update_post( array(
            'ID'           => $existing_education->ID,
            'post_title'   => $education['title'],
            'post_name'    => $education['slug'],
            'post_content' => $education['content'],
            'post_excerpt' => $education['excerpt'],
            'menu_order'   => $education['order'],
        ) );
        update_post_meta( $existing_education->ID, '_emc_service_icon',     $education['icon'] );
        update_post_meta( $existing_education->ID, '_emc_service_order',    $education['order'] );
        update_post_meta( $existing_education->ID, '_emc_service_featured', '1' );
        $changed = true;
    } else {
        $post_id = wp_insert_post( array(
            'post_type'    => 'emc_service',
            'post_title'   => $education['title'],
            'post_name'    => $education['slug'],
            'post_content' => $education['content'],
            'post_excerpt' => $education['excerpt'],
            'post_status'  => 'publish',
            'menu_order'   => $education['order'],
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_emc_service_icon',     $education['icon'] );
            update_post_meta( $post_id, '_emc_service_order',    $education['order'] );
            update_post_meta( $post_id, '_emc_service_featured', '1' );
            $changed = true;
        }
    }

    $new_services = array(
        array(
            'title'   => 'School Visit',
            'slug'    => 'school-visit',
            'icon'    => 'fas fa-school',
            'excerpt' => 'Welcoming local schools for mosque visits, faith learning, guided tours, and Q&A sessions.',
            'order'   => 7,
            'content' => '<h2>School Visits to EMC</h2>
<p>Essex Muslim Centre welcomes local schools and educational groups for guided visits that help pupils learn about Islam, mosque life, and the Muslim community in Essex.</p>
<h3>Visits Can Include</h3>
<ul>
<li><strong>Mosque Tours:</strong> A guided walk through the centre and prayer spaces.</li>
<li><strong>Faith Learning:</strong> Age-appropriate introductions to Islamic beliefs, worship, and community life.</li>
<li><strong>Q&amp;A Sessions:</strong> Opportunities for pupils and teachers to ask respectful questions.</li>
<li><strong>Curriculum Support:</strong> Visits tailored around RE, citizenship, diversity, and interfaith learning.</li>
</ul>
<h3>Arrange a Visit</h3>
<p>Please contact the centre with your preferred dates, year group, group size, and any curriculum topics you would like covered.</p>',
        ),
        array(
            'title'   => 'Bereavement Support',
            'slug'    => 'bereavement-support',
            'icon'    => 'fas fa-hands-helping',
            'excerpt' => 'Compassionate spiritual and practical support for individuals and families after the loss of a loved one.',
            'order'   => 8,
            'content' => '<h2>Bereavement Support</h2>
<p>Bereavement can be overwhelming. Essex Muslim Centre offers compassionate support for individuals and families dealing with loss, with care rooted in Islamic guidance and community support.</p>
<h3>How We Can Help</h3>
<ul>
<li><strong>Spiritual Support:</strong> Guidance, duas, and reminders for families navigating grief.</li>
<li><strong>Family Support:</strong> A confidential space to talk through difficult moments after a loss.</li>
<li><strong>Janaza Signposting:</strong> Help connecting families with the appropriate funeral and burial support.</li>
<li><strong>Ongoing Care:</strong> Follow-up support and signposting to suitable local services where needed.</li>
</ul>
<h3>Speak to Us</h3>
<p>Please contact the centre if you or your family would benefit from bereavement support. All enquiries are handled with sensitivity and confidentiality.</p>',
        ),
    );

    foreach ( $new_services as $svc ) {
        if ( get_page_by_path( $svc['slug'], OBJECT, 'emc_service' ) ) {
            continue;
        }

        $post_id = wp_insert_post( array(
            'post_type'    => 'emc_service',
            'post_title'   => $svc['title'],
            'post_name'    => $svc['slug'],
            'post_content' => $svc['content'],
            'post_excerpt' => $svc['excerpt'],
            'post_status'  => 'publish',
            'menu_order'   => $svc['order'],
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_emc_service_icon',     $svc['icon'] );
            update_post_meta( $post_id, '_emc_service_order',    $svc['order'] );
            update_post_meta( $post_id, '_emc_service_featured', '1' );
            $changed = true;
        }
    }

    if ( $changed ) {
        flush_rewrite_rules( false );
    }

    update_option( 'emc_services_seed_version', (string) $seed_version );
}
add_action( 'init', 'emc_update_core_services_v2', 21 );


/* ==========================================================================
   5c. Auto-seed Event Posts
   Creates 6 core emc_event posts automatically on first theme load.
   ========================================================================== */
function emc_auto_seed_events() {

    if ( get_option( 'emc_events_seeded' ) ) {
        return;
    }

    // Check both the option flag AND actual post count
    // (handles cases where option was set but posts were wiped by a re-import)
    if ( get_option( 'emc_events_seeded' ) ) {
        $count = wp_count_posts( 'emc_event' );
        if ( isset( $count->publish ) && (int) $count->publish >= 6 ) {
            return; // all good
        }
        // Posts missing despite option being set — reset and re-seed
        delete_option( 'emc_events_seeded' );
    }

    $events = array(
        array(
            'title'    => 'Friday Prayer (Jumu\'ah)',
            'slug'     => 'friday-prayer-jumuah',
            'excerpt'  => 'Weekly congregational Friday prayers with Khutbah. Two sessions available — all are welcome.',
            'category' => 'religious',
            'date'     => '2026-06-20',
            'end_date' => '',
            'time'     => '12:30 PM – 2:00 PM',
            'venue'    => 'Main Hall, EMC Centre',
            'capacity' => '',
            'reg_link' => '',
            'content'  => '<h2>Friday Prayer at Essex Muslim Centre</h2>
<p>Join us every Friday for Jumu\'ah (Friday congregational prayer). Two sessions are held to accommodate our growing community. The Khutbah (sermon) is delivered in English to ensure everyone can benefit.</p>
<h3>Session Times</h3>
<ul>
<li><strong>First Khutbah:</strong> 12:30 PM — Prayer begins approximately 1:00 PM</li>
<li><strong>Second Khutbah:</strong> 1:30 PM — Prayer begins approximately 2:00 PM</li>
<li><strong>Gates open:</strong> From 12:00 PM</li>
</ul>
<h3>Facilities</h3>
<ul>
<li>Separate sisters\' prayer area with full audio</li>
<li>Wudu (ablution) facilities for brothers and sisters</li>
<li>Accessible entrance for wheelchair users</li>
<li>Free parking nearby</li>
</ul>
<h3>Location</h3>
<p>Essex Muslim Centre, Cuton Hall Lane, CM2 6PB. Please use the exact map pin for the most accurate entrance and drop-off point.</p>',
        ),
        array(
            'title'    => 'Arabic Education Open Day',
            'slug'     => 'arabic-education-open-day',
            'excerpt'  => 'Come and see our Arabic and Quran classes in action. Meet the teachers and register your child for the new term.',
            'category' => 'community',
            'date'     => '2026-07-15',
            'end_date' => '',
            'time'     => '10:00 AM – 1:00 PM',
            'venue'    => 'EMC Centre, Chelmsford',
            'capacity' => '',
            'reg_link' => '',
            'content'  => '<h2>Arabic Education Open Day</h2>
<p>Our annual Open Day gives families the opportunity to see our Arabic and Islamic education classes first-hand, speak to teachers, and register for the new academic term starting in September.</p>
<h3>What to Expect</h3>
<ul>
<li>Tours of classrooms and learning facilities</li>
<li>Sample lessons for children of all ages</li>
<li>Meet our qualified teachers and ask questions</li>
<li>Register for Weekend Madrasah, Quran, and Arabic classes</li>
<li>Refreshments provided</li>
</ul>
<h3>Who Is It For?</h3>
<p>Open to all families — whether you are enrolling for the first time or returning for another year. Children aged 5 and above are welcome. Adult Arabic classes will also be showcased.</p>
<h3>How to Attend</h3>
<p>No booking required — simply arrive at the EMC Centre between 10:00 AM and 1:00 PM. Free entry for all.</p>',
        ),
        array(
            'title'    => 'Youth Sports Day 2026',
            'slug'     => 'youth-sports-day-2026',
            'excerpt'  => 'A fun-filled outdoor sports day for young people aged 8–18. Football, cricket, and more activities for the whole family.',
            'category' => 'youth',
            'date'     => '2026-07-26',
            'end_date' => '',
            'time'     => '10:00 AM – 3:00 PM',
            'venue'    => 'Chelmsford Sports Ground',
            'capacity' => '',
            'reg_link' => '',
            'content'  => '<h2>EMC Youth Sports Day 2026</h2>
<p>An action-packed day of outdoor sports and activities for young people in our community. This is a fantastic opportunity to get active, make new friends, and enjoy a fun day out in a safe and welcoming environment.</p>
<h3>Activities</h3>
<ul>
<li><strong>Football Tournament:</strong> 5-a-side teams, ages 10–18</li>
<li><strong>Cricket:</strong> Friendly matches and coaching sessions</li>
<li><strong>Athletics:</strong> Sprint races and field events for younger children</li>
<li><strong>Family Fun Zone:</strong> Tug-of-war, sack races, and more</li>
<li><strong>Food &amp; Refreshments:</strong> Halal BBQ and snacks available</li>
</ul>
<h3>Who Can Come?</h3>
<p>Young people aged 8–18, accompanied by family members. Parents and guardians are welcome to watch and join in the family activities. Free entry for all attendees.</p>',
        ),
        array(
            'title'    => 'Annual Fundraising Dinner 2026',
            'slug'     => 'annual-fundraising-dinner-2026',
            'excerpt'  => 'An elegant three-course fundraising dinner in support of our building campaign. Guest speakers and live auction.',
            'category' => 'fundraising',
            'date'     => '2026-08-15',
            'end_date' => '',
            'time'     => '7:00 PM – 10:30 PM',
            'venue'    => 'Park Inn by Radisson, Chelmsford',
            'capacity' => '200',
            'reg_link' => '',
            'content'  => '<h2>EMC Annual Fundraising Dinner 2026</h2>
<p>Join us for our flagship annual fundraising evening in support of the "Be One of the 313" building campaign. This prestigious event brings together community members, supporters, and dignitaries for a wonderful evening of dining and giving.</p>
<h3>Evening Programme</h3>
<ul>
<li><strong>7:00 PM:</strong> Registration &amp; Welcome Reception</li>
<li><strong>7:30 PM:</strong> Three-Course Halal Dinner</li>
<li><strong>8:30 PM:</strong> Guest Speakers &amp; Building Campaign Update</li>
<li><strong>9:15 PM:</strong> Live Fundraising Auction</li>
<li><strong>10:00 PM:</strong> Du\'a &amp; Close</li>
</ul>
<h3>Tickets</h3>
<p>Tickets are £35 per person, tables of 10 available at £300. All proceeds go directly to the EMC building fund. Contact our office to reserve your place or table.</p>
<h3>Dress Code</h3>
<p>Smart/semi-formal. Brothers in suits, sisters in modest formal attire.</p>',
        ),
        array(
            'title'    => 'Community Support Services Day',
            'slug'     => 'community-support-services-day',
            'excerpt'  => 'Free welfare support, signposting, and Zakat distribution for community members in need. Confidential appointments available.',
            'category' => 'community',
            'date'     => '2026-07-12',
            'end_date' => '',
            'time'     => '10:00 AM – 4:00 PM',
            'venue'    => 'EMC Centre, Chelmsford',
            'capacity' => '',
            'reg_link' => '',
            'content'  => '<h2>Community Support Services Day</h2>
<p>Our quarterly Community Support Day brings together welfare officers, volunteers, and partner organisations to provide free support, guidance, and referrals to community members in need.</p>
<h3>Services Available on the Day</h3>
<ul>
<li><strong>Welfare Assessment:</strong> Speak confidentially with our welfare officer about financial hardship, housing, or family support</li>
<li><strong>Zakat &amp; Sadaqah Distribution:</strong> Applications for emergency Zakat support</li>
<li><strong>Food Bank:</strong> Emergency food parcels available for qualifying families</li>
<li><strong>Benefits Advice:</strong> Help with Universal Credit, PIP applications, and entitlement checks</li>
<li><strong>Mental Health Signposting:</strong> Referrals to appropriate support services</li>
<li><strong>New Muslim Support:</strong> Resources and guidance for those who have recently embraced Islam</li>
</ul>
<h3>Confidentiality</h3>
<p>All consultations are strictly confidential. You do not need to make an appointment — simply arrive at any point during the day and speak to a member of our team.</p>',
        ),
        array(
            'title'    => 'Eid ul-Adha Celebration 2026',
            'slug'     => 'eid-ul-adha-celebration-2026',
            'excerpt'  => 'Celebrate Eid ul-Adha with prayers, a community feast, children\'s activities, and a joyful atmosphere for the whole family.',
            'category' => 'religious',
            'date'     => '2026-06-28',
            'end_date' => '',
            'time'     => '8:00 AM – 2:00 PM',
            'venue'    => 'EMC Centre & Car Park, Chelmsford',
            'capacity' => '',
            'reg_link' => '',
            'content'  => '<h2>Eid ul-Adha Celebration at EMC</h2>
<p>Come together as a community to celebrate the blessed occasion of Eid ul-Adha. After Eid prayers, join us for a day of community celebrations, food, and activities for all ages.</p>
<h3>Programme</h3>
<ul>
<li><strong>8:00 AM:</strong> First Eid Prayer</li>
<li><strong>9:15 AM:</strong> Second Eid Prayer</li>
<li><strong>10:00 AM:</strong> Community Feast &amp; Refreshments</li>
<li><strong>10:30 AM – 2:00 PM:</strong> Children\'s Activities, Games &amp; Stalls</li>
<li><strong>Throughout the Day:</strong> Face painting, bouncy castle, and family fun</li>
</ul>
<h3>All Are Welcome</h3>
<p>Eid is a time for community and celebration. Non-Muslim friends, neighbours, and colleagues are warmly invited to join the festivities after prayers. No booking required.</p>
<h3>Udhiyah (Qurbani)</h3>
<p>Qurbani orders can be placed through the office prior to Eid. Meat distribution will take place after prayers.</p>',
        ),
    );

    $all_done = true;

    // Ensure event_category taxonomy terms exist
    $tax_map = array(
        'religious'   => 'Religious',
        'community'   => 'Community',
        'youth'       => 'Youth',
        'fundraising' => 'Fundraising',
    );
    foreach ( $tax_map as $slug => $name ) {
        if ( ! term_exists( $slug, 'event_category' ) ) {
            wp_insert_term( $name, 'event_category', array( 'slug' => $slug ) );
        }
    }

    foreach ( $events as $evt ) {
        $existing = get_page_by_path( $evt['slug'], OBJECT, 'emc_event' );
        if ( $existing ) {
            continue;
        }

        $post_id = wp_insert_post( array(
            'post_type'    => 'emc_event',
            'post_title'   => $evt['title'],
            'post_name'    => $evt['slug'],
            'post_content' => $evt['content'],
            'post_excerpt' => $evt['excerpt'],
            'post_status'  => 'publish',
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_emc_event_date',     $evt['date'] );
            update_post_meta( $post_id, '_emc_event_end_date', $evt['end_date'] );
            update_post_meta( $post_id, '_emc_event_time',     $evt['time'] );
            update_post_meta( $post_id, '_emc_event_venue',    $evt['venue'] );
            update_post_meta( $post_id, '_emc_event_location', $evt['venue'] ); // alias used in events list
            update_post_meta( $post_id, '_emc_event_category', $evt['category'] );
            update_post_meta( $post_id, '_emc_event_capacity', $evt['capacity'] );
            update_post_meta( $post_id, '_emc_event_reg_link', $evt['reg_link'] );
            update_post_meta( $post_id, '_emc_event_featured', '1' );

            // Assign taxonomy term
            $term = get_term_by( 'slug', $evt['category'], 'event_category' );
            if ( $term ) {
                wp_set_post_terms( $post_id, array( $term->term_id ), 'event_category' );
            }
        } else {
            $all_done = false;
        }
    }

    flush_rewrite_rules( false );

    if ( $all_done ) {
        update_option( 'emc_events_seeded', '1' );
    }
}
// Do not auto-create demo events on every install/load; admins must be able to delete them.

/* ==========================================================================
   6. Body Classes
   ========================================================================== */
function emc_body_classes( $classes ) {
    if ( is_singular() ) {
        $classes[] = 'singular';
    }
    if ( ! is_front_page() ) {
        $classes[] = 'inner-page';
    }
    return $classes;
}
add_filter( 'body_class', 'emc_body_classes' );


/* ==========================================================================
   7. Custom Excerpt Length
   ========================================================================== */
function emc_excerpt_length( $length ) {
    return 25;
}
add_filter( 'excerpt_length', 'emc_excerpt_length', 999 );

function emc_excerpt_more( $more ) {
    return '&hellip;';
}
add_filter( 'excerpt_more', 'emc_excerpt_more' );


/* ==========================================================================
   8. Security: Remove WordPress Version from Head
   ========================================================================== */
remove_action( 'wp_head', 'wp_generator' );


/* ==========================================================================
   9. Admin Bar Push-Down Fix
   ========================================================================== */
function emc_admin_bar_offset() {
    if ( is_admin_bar_showing() ) {
        echo '<style>
            .prayer-top-bar { top: 32px !important; }
            .main-header    { top: calc(32px + 83px) !important; }
            @media screen and (min-width:1101px) {
                .main-header { top: calc(32px + 63px) !important; }
            }
            @media screen and (max-width:782px) {
                .prayer-top-bar { display: none; }
                .main-header    { top: 46px !important; }
            }
        </style>';
    }
}
add_action( 'wp_head', 'emc_admin_bar_offset' );
