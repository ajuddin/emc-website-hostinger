<?php
/**
 * EMC Theme — inc/customizer.php
 * WordPress Customizer — Phase 3: Full header/footer dynamic controls.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   Colour Palette Presets — Custom Customizer Control
   ========================================================================== */

/**
 * Renders 6 one-click palette swatch buttons inside the Customizer sidebar.
 * Clicking a preset calls the WP Customizer JS API to set all individual
 * colour settings at once, triggering the existing postMessage live preview.
 *
 * No extra theme_mod is stored — palettes just update the existing settings.
 */
if ( class_exists( 'WP_Customize_Control' ) ) :
class EMC_Palette_Control extends WP_Customize_Control {

    public $type = 'emc_palette';

    /**
     * All 6 built-in palettes.
     * Keys match the existing emc_color_* customizer settings.
     */
    private static function palettes() {
        return array(
            array(
                'id'      => 'default',
                'label'   => __( 'EMC Teal', 'emc-theme' ),
                'tag'     => __( 'Default', 'emc-theme' ),
                'primary' => '#2AACA0',
                'dark'    => '#1A7A72',
                'accent'  => '#C4956A',
                'blue'    => '#0C1F2E',
                'text'    => '#2D3E4A',
                'bg'      => '#F5F9F9',
            ),
            array(
                'id'      => 'royal-blue',
                'label'   => __( 'Royal Blue', 'emc-theme' ),
                'tag'     => __( 'Classic', 'emc-theme' ),
                'primary' => '#1E40AF',
                'dark'    => '#1E3A8A',
                'accent'  => '#F59E0B',
                'blue'    => '#0F172A',
                'text'    => '#1E3A5F',
                'bg'      => '#F0F4FF',
            ),
            array(
                'id'      => 'deep-maroon',
                'label'   => __( 'Deep Maroon', 'emc-theme' ),
                'tag'     => __( 'Elegant', 'emc-theme' ),
                'primary' => '#881337',
                'dark'    => '#4C0519',
                'accent'  => '#D4AF37',
                'blue'    => '#1C1917',
                'text'    => '#44403C',
                'bg'      => '#FDF8F8',
            ),
            array(
                'id'      => 'teal-ocean',
                'label'   => __( 'Teal Ocean', 'emc-theme' ),
                'tag'     => __( 'Modern', 'emc-theme' ),
                'primary' => '#0F766E',
                'dark'    => '#134E4A',
                'accent'  => '#F59E0B',
                'blue'    => '#0F172A',
                'text'    => '#334155',
                'bg'      => '#F0FAFA',
            ),
            array(
                'id'      => 'slate-purple',
                'label'   => __( 'Slate Purple', 'emc-theme' ),
                'tag'     => __( 'Premium', 'emc-theme' ),
                'primary' => '#6D28D9',
                'dark'    => '#4C1D95',
                'accent'  => '#F59E0B',
                'blue'    => '#1E1B4B',
                'text'    => '#3B3470',
                'bg'      => '#F5F3FF',
            ),
            array(
                'id'      => 'terracotta',
                'label'   => __( 'Terracotta', 'emc-theme' ),
                'tag'     => __( 'Warm', 'emc-theme' ),
                'primary' => '#B45309',
                'dark'    => '#78350F',
                'accent'  => '#EAB308',
                'blue'    => '#1C1917',
                'text'    => '#44403C',
                'bg'      => '#FFFBF5',
            ),
        );
    }

    /** Render palette swatches + JS into the Customizer controls panel. */
    public function render_content() {
        $palettes = self::palettes();
        ?>
        <style>
        .emc-palette-wrap { margin: 0 -4px 8px; }
        .emc-palette-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .emc-palette-btn {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 8px;
            background: #fff;
            cursor: pointer;
            text-align: left;
            transition: border-color .2s, box-shadow .2s;
        }
        .emc-palette-btn:hover { border-color: #aaa; box-shadow: 0 3px 10px rgba(0,0,0,.1); }
        .emc-palette-btn.emc-active { border-color: #D4AF37; box-shadow: 0 0 0 3px rgba(212,175,55,.25); }
        .emc-swatches { display: flex; gap: 3px; margin-bottom: 5px; }
        .emc-swatches span { height: 20px; flex: 1; border-radius: 4px; display: block; }
        .emc-palette-name { font-size: 11px; font-weight: 700; color: #222; display: block; line-height: 1.3; }
        .emc-palette-tag  { font-size: 10px; color: #888; display: block; }
        </style>

        <span class="customize-control-title"><?php esc_html_e( 'One-Click Colour Presets', 'emc-theme' ); ?></span>
        <span class="description customize-control-description">
            <?php esc_html_e( 'Click any preset to instantly apply a full colour scheme. Fine-tune individual colours below.', 'emc-theme' ); ?>
        </span>

        <div class="emc-palette-wrap">
            <div class="emc-palette-grid">
                <?php foreach ( $palettes as $p ) : ?>
                <button type="button"
                        class="emc-palette-btn"
                        data-primary="<?php echo esc_attr( $p['primary'] ); ?>"
                        data-dark="<?php echo esc_attr( $p['dark'] ); ?>"
                        data-accent="<?php echo esc_attr( $p['accent'] ); ?>"
                        data-blue="<?php echo esc_attr( $p['blue'] ); ?>"
                        data-text="<?php echo esc_attr( $p['text'] ); ?>"
                        data-bg="<?php echo esc_attr( $p['bg'] ); ?>">
                    <div class="emc-swatches">
                        <span style="background:<?php echo esc_attr( $p['primary'] ); ?>;"></span>
                        <span style="background:<?php echo esc_attr( $p['dark'] ); ?>;"></span>
                        <span style="background:<?php echo esc_attr( $p['accent'] ); ?>;"></span>
                        <span style="background:<?php echo esc_attr( $p['blue'] ); ?>;"></span>
                    </div>
                    <span class="emc-palette-name"><?php echo esc_html( $p['label'] ); ?></span>
                    <span class="emc-palette-tag"><?php echo esc_html( $p['tag'] ); ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
        ( function() {
            var btns = document.querySelectorAll( '.emc-palette-btn' );

            // Mark the active preset on load by matching primary color
            var currentPrimary = wp.customize( 'emc_color_primary' ).get().toLowerCase();
            btns.forEach( function( btn ) {
                if ( btn.dataset.primary.toLowerCase() === currentPrimary ) {
                    btn.classList.add( 'emc-active' );
                }
            } );

            btns.forEach( function( btn ) {
                btn.addEventListener( 'click', function() {
                    // Set all 6 Customizer settings — triggers postMessage live preview
                    wp.customize( 'emc_color_primary'      ).set( btn.dataset.primary );
                    wp.customize( 'emc_color_primary_dark' ).set( btn.dataset.dark    );
                    wp.customize( 'emc_color_accent'       ).set( btn.dataset.accent  );
                    wp.customize( 'emc_color_deep_blue'    ).set( btn.dataset.blue    );
                    wp.customize( 'emc_color_text'         ).set( btn.dataset.text    );
                    wp.customize( 'emc_color_light_bg'     ).set( btn.dataset.bg      );

                    // Update active state
                    btns.forEach( function( b ) { b.classList.remove( 'emc-active' ); } );
                    btn.classList.add( 'emc-active' );
                } );
            } );
        } )();
        </script>
        <?php
    }
}
endif; // class_exists WP_Customize_Control

/* ==========================================================================
   Register Settings & Controls
   ========================================================================== */
function emc_customize_register( $wp_customize ) {

    /* ======================================================================
       PANEL: EMC Theme Options
       ====================================================================== */
    $wp_customize->add_panel( 'emc_options', array(
        'title'       => __( 'EMC Theme Options', 'emc-theme' ),
        'priority'    => 30,
        'description' => __( 'Global settings for the Essex Muslim Centre theme.', 'emc-theme' ),
    ) );

    /* ──────────────────────────────────────────────────────────────────────
       SECTION: Charity Identity
       ────────────────────────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_identity', array(
        'title'    => __( 'Charity Identity', 'emc-theme' ),
        'panel'    => 'emc_options',
        'priority' => 10,
    ) );

    emc_add_text_setting( $wp_customize, 'emc_charity_number', '1209815',
        'emc_identity', __( 'Charity Registration Number', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_admin_email', 'admin@essexmuslimcentre.org',
        'emc_identity', __( 'Admin / Contact Email', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_phone', '',
        'emc_identity', __( 'Phone Number (optional)', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_location', 'Essex Muslim Centre, Cuton Hall Lane, CM2 6PB',
        'emc_identity', __( 'Location (short, e.g. "Chelmsford, Essex")', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_address_line1', 'Cuton Hall Lane',
        'emc_identity', __( 'Street Address (Line 1)', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_address_line2', '',
        'emc_identity', __( 'Street Address (Line 2)', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_address_city', '',
        'emc_identity', __( 'City', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_address_postcode', 'CM2 6PB',
        'emc_identity', __( 'Postcode', 'emc-theme' ) );

    /* ──────────────────────────────────────────────────────────────────────
       SECTION: Social Media
       ────────────────────────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_social', array(
        'title'    => __( 'Social Media Links', 'emc-theme' ),
        'panel'    => 'emc_options',
        'priority' => 20,
    ) );

    foreach ( array( 'facebook', 'instagram', 'twitter', 'tiktok', 'youtube' ) as $network ) {
        emc_add_url_setting(
            $wp_customize,
            'emc_social_' . $network,
            '',
            'emc_social',
            ucfirst( $network ) . ' URL'
        );
    }

    /* ──────────────────────────────────────────────────────────────────────
       SECTION: Announcement Bar
       ────────────────────────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_announcement', array(
        'title'       => __( 'Announcement Bar', 'emc-theme' ),
        'panel'       => 'emc_options',
        'priority'    => 25,
        'description' => __( 'A slim banner that appears above the main header. Use it for appeals, events, or important notices.', 'emc-theme' ),
    ) );

    emc_add_checkbox_setting( $wp_customize, 'emc_announcement_enabled', false,
        'emc_announcement', __( 'Enable Announcement Bar', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_announcement_text',
        __( 'Ramadan Appeal 2026 is now open — every donation counts!', 'emc-theme' ),
        'emc_announcement', __( 'Announcement Text', 'emc-theme' ) );

    emc_add_url_setting( $wp_customize, 'emc_announcement_link', '',
        'emc_announcement', __( 'Announcement Link URL (optional)', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_announcement_link_text',
        __( 'Donate Now', 'emc-theme' ),
        'emc_announcement', __( 'Link Button Label', 'emc-theme' ) );

    emc_add_checkbox_setting( $wp_customize, 'emc_announcement_dismissible', true,
        'emc_announcement', __( 'Allow visitors to dismiss the bar', 'emc-theme' ) );

    /* ──────────────────────────────────────────────────────────────────────
       SECTION: Header Options
       ────────────────────────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_header_opts', array(
        'title'    => __( 'Header Options', 'emc-theme' ),
        'panel'    => 'emc_options',
        'priority' => 30,
    ) );

    emc_add_checkbox_setting( $wp_customize, 'emc_header_sticky', true,
        'emc_header_opts', __( 'Sticky header on scroll', 'emc-theme' ) );

    emc_add_checkbox_setting( $wp_customize, 'emc_header_prayer', true,
        'emc_header_opts', __( 'Show next prayer countdown in header', 'emc-theme' ) );

    emc_add_checkbox_setting( $wp_customize, 'emc_header_donate_btn', true,
        'emc_header_opts', __( 'Show "Donate Now" button in header', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_header_donate_label', __( 'Donate Now', 'emc-theme' ),
        'emc_header_opts', __( 'Donate Button Label', 'emc-theme' ) );

    // ── Logo Size ────────────────────────────────────────────────────────
    $wp_customize->add_setting( 'emc_logo_height', array(
        'default'           => 68,
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'emc_logo_height', array(
        'label'       => __( 'Logo Height (px)', 'emc-theme' ),
        'description' => __( 'Adjust the logo size in the header. Upload your logo under Appearance → Customize → Site Identity.', 'emc-theme' ),
        'section'     => 'emc_header_opts',
        'type'        => 'range',
        'input_attrs' => array(
            'min'  => 30,
            'max'  => 120,
            'step' => 2,
        ),
    ) );

    /* ──────────────────────────────────────────────────────────────────────
       SECTION: Footer Options
       ────────────────────────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_footer_opts', array(
        'title'    => __( 'Footer Options', 'emc-theme' ),
        'panel'    => 'emc_options',
        'priority' => 40,
    ) );

    // — Column 1: Brand
    emc_add_textarea_setting(
        $wp_customize,
        'emc_footer_about_text',
        __( 'Advancing Islamic faith, education, and community welfare in Chelmsford, Essex.', 'emc-theme' ),
        'emc_footer_opts',
        __( 'Footer About Text', 'emc-theme' ),
        'postMessage'
    );

    // — Column headings
    emc_add_text_setting( $wp_customize, 'emc_footer_col2_heading', __( 'Quick Links', 'emc-theme' ),
        'emc_footer_opts', __( 'Column 2 Heading', 'emc-theme' ) );

    // — Column 2: Quick Links (one per line: Label|slug)
    emc_add_text_setting( $wp_customize, 'emc_footer_col3_heading', __( 'Community', 'emc-theme' ),
        'emc_footer_opts', __( 'Column 3 Heading', 'emc-theme' ) );

    // — Column 3: Community Links (one per line: Label|slug)
    emc_add_text_setting( $wp_customize, 'emc_footer_col4_heading', __( 'Contact', 'emc-theme' ),
        'emc_footer_opts', __( 'Column 4 Heading', 'emc-theme' ) );

    // — Column 4: Contact details
    emc_add_textarea_setting(
        $wp_customize,
        'emc_footer_address',
        __( "Essex Muslim Centre\nCuton Hall Lane\nCM2 6PB", 'emc-theme' ),
        'emc_footer_opts',
        __( 'Footer Address (each line is a new line)', 'emc-theme' )
    );

    emc_add_checkbox_setting( $wp_customize, 'emc_footer_show_prayer_link', true,
        'emc_footer_opts', __( 'Show Prayer Times link in contact column', 'emc-theme' ) );

    // — Footer newsletter
    emc_add_checkbox_setting( $wp_customize, 'emc_footer_newsletter', true,
        'emc_footer_opts', __( 'Show newsletter sign-up in footer', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_footer_newsletter_heading', __( 'Stay in the Loop', 'emc-theme' ),
        'emc_footer_opts', __( 'Footer Newsletter Heading', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_footer_newsletter_sub',
        __( 'Get the latest news and events delivered to your inbox.', 'emc-theme' ),
        'emc_footer_opts', __( 'Footer Newsletter Subtext', 'emc-theme' ) );

    // — Bottom bar
    emc_add_text_setting( $wp_customize, 'emc_footer_copyright_text', '',
        'emc_footer_opts', __( 'Custom copyright text (leave blank to auto-generate)', 'emc-theme' ) );

    emc_add_checkbox_setting( $wp_customize, 'emc_footer_show_privacy', true,
        'emc_footer_opts', __( 'Show Privacy Policy link in footer bar', 'emc-theme' ) );

    emc_add_checkbox_setting( $wp_customize, 'emc_footer_show_gift_aid', true,
        'emc_footer_opts', __( 'Show Gift Aid link in footer bar', 'emc-theme' ) );

    // — App Download Links
    emc_add_url_setting( $wp_customize, 'emc_ios_app_url', '',
        'emc_footer_opts', __( 'iOS App Store URL (leave blank to hide app strip)', 'emc-theme' ) );

    emc_add_url_setting( $wp_customize, 'emc_android_app_url', '',
        'emc_footer_opts', __( 'Android Google Play URL (leave blank to hide app strip)', 'emc-theme' ) );

    /* ──────────────────────────────────────────────────────────────────────
       SECTION: Cookie Consent
       ────────────────────────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_cookie', array(
        'title'    => __( 'Cookie Consent', 'emc-theme' ),
        'panel'    => 'emc_options',
        'priority' => 90,
    ) );

    emc_add_checkbox_setting( $wp_customize, 'emc_cookie_enabled', true,
        'emc_cookie', __( 'Enable Cookie Consent Banner', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_cookie_message',
        __( 'We use cookies to improve your experience. By continuing you agree to our Privacy Policy.', 'emc-theme' ),
        'emc_cookie', __( 'Cookie Message Text', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_cookie_accept_label', __( 'Accept', 'emc-theme' ),
        'emc_cookie', __( 'Accept Button Label', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_cookie_decline_label', __( 'Decline', 'emc-theme' ),
        'emc_cookie', __( 'Decline Button Label', 'emc-theme' ) );

    /* ======================================================================
       PANEL: Homepage Sections
       ====================================================================== */
    $wp_customize->add_panel( 'emc_homepage', array(
        'title'       => __( 'Homepage Sections', 'emc-theme' ),
        'priority'    => 35,
        'description' => __( 'Control the content and visibility of each homepage section.', 'emc-theme' ),
    ) );

    /* ── Section Visibility ─────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_hp_visibility', array(
        'title'    => __( 'Section Visibility', 'emc-theme' ),
        'panel'    => 'emc_homepage',
        'priority' => 5,
    ) );

    $hp_sections = array(
        'prayer_strip' => __( 'Prayer Times Strip', 'emc-theme' ),
        'about'        => __( 'About Section', 'emc-theme' ),
        'services'     => __( 'Services Section', 'emc-theme' ),
        'events'       => __( 'Events Section', 'emc-theme' ),
        'campaign'     => __( 'Campaign Section', 'emc-theme' ),
        'counters'     => __( 'Stats / Counters', 'emc-theme' ),
        'team'         => __( 'Meet the Team', 'emc-theme' ),
        'testimonials' => __( 'Testimonials', 'emc-theme' ),
        'faq'          => __( 'FAQ Section', 'emc-theme' ),
        'cta'          => __( 'CTA Strip', 'emc-theme' ),
        'media'        => __( 'Media & News', 'emc-theme' ),
        'newsletter'   => __( 'Newsletter Section', 'emc-theme' ),
    );

    $visibility_defaults = array(
        'prayer_strip' => true,
        'about'        => true,
        'services'     => true,
        'events'       => true,
        'campaign'     => true,
        'counters'     => false,
        'team'         => false,
        'testimonials' => false,
        'faq'          => false,
        'cta'          => true,
        'media'        => true,
        'newsletter'   => true,
    );

    foreach ( $hp_sections as $key => $label ) {
        emc_add_checkbox_setting(
            $wp_customize,
            'emc_show_' . $key,
            $visibility_defaults[ $key ],
            'emc_hp_visibility',
            sprintf( __( 'Show %s', 'emc-theme' ), $label )
        );
    }

    /* ── Hero Section ───────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_hp_hero', array(
        'title'    => __( 'Hero Section', 'emc-theme' ),
        'panel'    => 'emc_homepage',
        'priority' => 10,
    ) );

    emc_add_text_setting( $wp_customize, 'emc_hero_title',
        __( 'Advancing Faith & Community', 'emc-theme' ),
        'emc_hp_hero', __( 'Hero Title', 'emc-theme' ), 'postMessage' );

    emc_add_textarea_setting( $wp_customize, 'emc_hero_subtitle',
        __( 'Welcome to the Essex Muslim Centre. A hub for spiritual growth, education, and community welfare in the heart of Chelmsford.', 'emc-theme' ),
        'emc_hp_hero', __( 'Hero Subtitle', 'emc-theme' ), 'postMessage' );

    emc_add_text_setting( $wp_customize, 'emc_hero_cta1_label',
        __( 'Donate Now', 'emc-theme' ),
        'emc_hp_hero', __( 'Button 1 Label', 'emc-theme' ) );

    emc_add_url_setting( $wp_customize, 'emc_hero_cta1_url', '',
        'emc_hp_hero', __( 'Button 1 URL (leave blank for /donate/)', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_hero_cta2_label',
        __( 'Prayer Times', 'emc-theme' ),
        'emc_hp_hero', __( 'Button 2 Label', 'emc-theme' ) );

    emc_add_url_setting( $wp_customize, 'emc_hero_cta2_url', '',
        'emc_hp_hero', __( 'Button 2 URL (leave blank for /prayer-times/)', 'emc-theme' ) );

    // Hero background image
    $wp_customize->add_setting( 'emc_hero_bg_image', array(
        'default'           => '',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'emc_hero_bg_image', array(
        'label'     => __( 'Hero Background Image', 'emc-theme' ),
        'section'   => 'emc_hp_hero',
        'mime_type' => 'image',
    ) ) );

    // ── Quick Donate Card (inside hero) ─────────────────────────────────
    emc_add_text_setting( $wp_customize, 'emc_hero_donate_heading',
        __( 'Support Our Centre', 'emc-theme' ),
        'emc_hp_hero', __( 'Donate Card — Heading', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_hero_donate_amounts',
        '5,10,25,50',
        'emc_hp_hero', __( 'Donate Card — Amounts (comma-separated, e.g. 5,10,25,50)', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_hero_donate_default_amount',
        '25',
        'emc_hp_hero', __( 'Donate Card — Default Selected Amount', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_hero_donate_proceed_label',
        __( 'Proceed Securely', 'emc-theme' ),
        'emc_hp_hero', __( 'Donate Card — Proceed Button Label', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_hero_donate_categories',
        __( 'General Fund,Building Fund,Zakat,Sadaqah', 'emc-theme' ),
        'emc_hp_hero', __( 'Donate Card — Categories (comma-separated)', 'emc-theme' ) );

    /* ── About Section ──────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_hp_about', array(
        'title'    => __( 'About Section', 'emc-theme' ),
        'panel'    => 'emc_homepage',
        'priority' => 20,
    ) );

    emc_add_text_setting( $wp_customize, 'emc_about_heading',
        __( 'Who We Are', 'emc-theme' ),
        'emc_hp_about', __( 'Heading', 'emc-theme' ), 'postMessage' );

    emc_add_text_setting( $wp_customize, 'emc_about_subheading',
        __( 'About Us', 'emc-theme' ),
        'emc_hp_about', __( 'Subheading / Badge', 'emc-theme' ) );

    emc_add_textarea_setting( $wp_customize, 'emc_about_body',
        __( 'Essex Muslim Centre is a registered UK charity dedicated to advancing Islamic faith, education, and community welfare in the heart of Chelmsford, Essex. Founded in 2018, we serve over 500 families across the region.', 'emc-theme' ),
        'emc_hp_about', __( 'Body Text', 'emc-theme' ), 'postMessage' );

    emc_add_text_setting( $wp_customize, 'emc_about_stat1_num', '2018',
        'emc_hp_about', __( 'Stat 1 — Number', 'emc-theme' ) );
    emc_add_text_setting( $wp_customize, 'emc_about_stat1_label', __( 'Founded', 'emc-theme' ),
        'emc_hp_about', __( 'Stat 1 — Label', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_about_stat2_num', '500+',
        'emc_hp_about', __( 'Stat 2 — Number', 'emc-theme' ) );
    emc_add_text_setting( $wp_customize, 'emc_about_stat2_label', __( 'Families Served', 'emc-theme' ),
        'emc_hp_about', __( 'Stat 2 — Label', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_about_stat3_num', '10+',
        'emc_hp_about', __( 'Stat 3 — Number', 'emc-theme' ) );
    emc_add_text_setting( $wp_customize, 'emc_about_stat3_label', __( 'Weekly Services', 'emc-theme' ),
        'emc_hp_about', __( 'Stat 3 — Label', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_about_cta_label', __( 'Learn More About Us', 'emc-theme' ),
        'emc_hp_about', __( 'CTA Button Label', 'emc-theme' ) );

    // ── About card bullet points ─────────────────────────────────────────
    emc_add_text_setting( $wp_customize, 'emc_about_bullet1',
        __( 'Faith-centred community hub', 'emc-theme' ),
        'emc_hp_about', __( 'Bullet Point 1', 'emc-theme' ) );
    emc_add_text_setting( $wp_customize, 'emc_about_bullet2',
        __( 'Islamic education for all ages', 'emc-theme' ),
        'emc_hp_about', __( 'Bullet Point 2', 'emc-theme' ) );
    emc_add_text_setting( $wp_customize, 'emc_about_bullet3',
        __( 'Active welfare & support services', 'emc-theme' ),
        'emc_hp_about', __( 'Bullet Point 3', 'emc-theme' ) );
    emc_add_text_setting( $wp_customize, 'emc_about_image', '',
        'emc_hp_about', __( 'About Image URL (optional — leave blank for icon card)', 'emc-theme' ) );

    /* ── Services Section ───────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_hp_services', array(
        'title'    => __( 'Services Section', 'emc-theme' ),
        'panel'    => 'emc_homepage',
        'priority' => 30,
    ) );

    emc_add_text_setting( $wp_customize, 'emc_services_heading',
        __( 'Serving the Community', 'emc-theme' ),
        'emc_hp_services', __( 'Section Heading', 'emc-theme' ), 'postMessage' );

    emc_add_text_setting( $wp_customize, 'emc_services_subheading',
        __( 'Our Services', 'emc-theme' ),
        'emc_hp_services', __( 'Section Subheading', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_services_cta_label', __( 'View All Services', 'emc-theme' ),
        'emc_hp_services', __( 'CTA Button Label', 'emc-theme' ) );

    emc_add_checkbox_setting( $wp_customize, 'emc_services_cpt_only', false,
        'emc_hp_services', __( 'Only show CPT services (hide static fallback)', 'emc-theme' ) );

    /* ── Events Section ─────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_hp_events', array(
        'title'    => __( 'Events Section', 'emc-theme' ),
        'panel'    => 'emc_homepage',
        'priority' => 40,
    ) );

    emc_add_text_setting( $wp_customize, 'emc_events_heading',
        __( 'Featured Events', 'emc-theme' ),
        'emc_hp_events', __( 'Section Heading', 'emc-theme' ), 'postMessage' );

    emc_add_text_setting( $wp_customize, 'emc_events_subheading',
        __( 'Upcoming', 'emc-theme' ),
        'emc_hp_events', __( 'Section Subheading', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_events_cta_label', __( 'View All Events', 'emc-theme' ),
        'emc_hp_events', __( 'CTA Button Label', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_events_selected', '',
        'emc_hp_events', __( 'Homepage Event IDs or slugs (comma separated)', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_events_count', '2',
        'emc_hp_events', __( 'Number of events to show', 'emc-theme' ) );

    /* ── Campaign Section ───────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_hp_campaign', array(
        'title'    => __( 'Campaign Section', 'emc-theme' ),
        'panel'    => 'emc_homepage',
        'priority' => 50,
    ) );

    emc_add_text_setting( $wp_customize, 'emc_campaign_badge',
        __( 'Building Fund', 'emc-theme' ),
        'emc_hp_campaign', __( 'Campaign Badge Label', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_campaign_heading',
        __( 'Be One of the 313', 'emc-theme' ),
        'emc_hp_campaign', __( 'Campaign Heading', 'emc-theme' ), 'postMessage' );

    emc_add_textarea_setting( $wp_customize, 'emc_campaign_desc',
        __( 'Help us build a lasting place of worship for future generations. Our building campaign needs your generous support. Every pound brings us closer to our goal.', 'emc-theme' ),
        'emc_hp_campaign', __( 'Campaign Description', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_campaign_cta_label',
        __( 'Choose Badr Wall Level', 'emc-theme' ),
        'emc_hp_campaign', __( 'Donate Button Label', 'emc-theme' ) );

    emc_add_url_setting( $wp_customize, 'emc_campaign_cta_url', '',
        'emc_hp_campaign', __( 'Donate Button URL (blank = campaign Badr Wall selector)', 'emc-theme' ) );

    // ── Badr Wall Tier Fill Counts ─────────────────────────────────────────
    emc_add_text_setting( $wp_customize, 'emc_campaign_tier1_filled', '19',
        'emc_hp_campaign', __( 'Badr Wall — Founder tiles taken (max 100)', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_campaign_tier2_filled', '11',
        'emc_hp_campaign', __( 'Badr Wall — Co-Founder tiles taken (max 213)', 'emc-theme' ) );

    /* ── Counters / Stats Section ───────────────────────────────────────── */
    $wp_customize->add_section( 'emc_hp_counters', array(
        'title'    => __( 'Stats / Counters', 'emc-theme' ),
        'panel'    => 'emc_homepage',
        'priority' => 60,
    ) );

    emc_add_text_setting( $wp_customize, 'emc_counters_heading', '',
        'emc_hp_counters', __( 'Section Heading (optional)', 'emc-theme' ) );

    for ( $i = 1; $i <= 4; $i++ ) {
        $defaults = array(
            1 => array( 'num' => '2018',   'label' => __( 'Founded',             'emc-theme' ), 'icon' => 'fas fa-mosque' ),
            2 => array( 'num' => '500+',   'label' => __( 'Families Served',     'emc-theme' ), 'icon' => 'fas fa-users' ),
            3 => array( 'num' => '10+',    'label' => __( 'Weekly Services',     'emc-theme' ), 'icon' => 'fas fa-praying-hands' ),
            4 => array( 'num' => '1,200+', 'label' => __( 'Newsletter Readers',  'emc-theme' ), 'icon' => 'fas fa-envelope' ),
        );
        emc_add_text_setting( $wp_customize, "emc_counter_{$i}_icon",  $defaults[ $i ]['icon'],
            'emc_hp_counters', sprintf( __( 'Counter %d — Icon class', 'emc-theme' ), $i ) );
        emc_add_text_setting( $wp_customize, "emc_counter_{$i}_num",   $defaults[ $i ]['num'],
            'emc_hp_counters', sprintf( __( 'Counter %d — Number', 'emc-theme' ), $i ) );
        emc_add_text_setting( $wp_customize, "emc_counter_{$i}_label", $defaults[ $i ]['label'],
            'emc_hp_counters', sprintf( __( 'Counter %d — Label', 'emc-theme' ), $i ) );
    }

    /* ── Team Section ───────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_hp_team', array(
        'title'    => __( 'Team Section', 'emc-theme' ),
        'panel'    => 'emc_homepage',
        'priority' => 70,
    ) );

    emc_add_text_setting( $wp_customize, 'emc_team_heading',
        __( 'Meet Our Team', 'emc-theme' ),
        'emc_hp_team', __( 'Section Heading', 'emc-theme' ), 'postMessage' );

    emc_add_text_setting( $wp_customize, 'emc_team_subheading',
        __( 'Our People', 'emc-theme' ),
        'emc_hp_team', __( 'Section Subheading', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_team_per_page', '6',
        'emc_hp_team', __( 'Members to show on homepage', 'emc-theme' ) );

    /* ── Testimonials Section ───────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_hp_testimonials', array(
        'title'    => __( 'Testimonials', 'emc-theme' ),
        'panel'    => 'emc_homepage',
        'priority' => 80,
    ) );

    emc_add_text_setting( $wp_customize, 'emc_testimonials_heading',
        __( 'What Our Community Says', 'emc-theme' ),
        'emc_hp_testimonials', __( 'Section Heading', 'emc-theme' ), 'postMessage' );

    emc_add_text_setting( $wp_customize, 'emc_testimonials_subheading',
        __( 'Community Voices', 'emc-theme' ),
        'emc_hp_testimonials', __( 'Section Subheading', 'emc-theme' ) );

    /* ── FAQ Section ────────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_hp_faq', array(
        'title'    => __( 'FAQ Section', 'emc-theme' ),
        'panel'    => 'emc_homepage',
        'priority' => 90,
    ) );

    emc_add_text_setting( $wp_customize, 'emc_faq_heading',
        __( 'Frequently Asked Questions', 'emc-theme' ),
        'emc_hp_faq', __( 'Section Heading', 'emc-theme' ), 'postMessage' );

    emc_add_text_setting( $wp_customize, 'emc_faq_subheading',
        __( 'Got Questions?', 'emc-theme' ),
        'emc_hp_faq', __( 'Section Subheading', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_faq_per_page', '6',
        'emc_hp_faq', __( 'FAQs to show on homepage', 'emc-theme' ) );

    /* ── CTA Strip ──────────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_hp_cta', array(
        'title'    => __( 'CTA Strip', 'emc-theme' ),
        'panel'    => 'emc_homepage',
        'priority' => 100,
    ) );

    emc_add_text_setting( $wp_customize, 'emc_cta_heading',
        __( 'Ready to Make a Difference?', 'emc-theme' ),
        'emc_hp_cta', __( 'CTA Heading', 'emc-theme' ), 'postMessage' );

    emc_add_text_setting( $wp_customize, 'emc_cta_subtitle',
        __( 'Every donation, every volunteer hour, and every shared message helps us serve the community better.', 'emc-theme' ),
        'emc_hp_cta', __( 'CTA Subtitle', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_cta_btn1_label', __( 'Donate Now', 'emc-theme' ),
        'emc_hp_cta', __( 'Button 1 Label', 'emc-theme' ) );
    emc_add_url_setting( $wp_customize, 'emc_cta_btn1_url', '',
        'emc_hp_cta', __( 'Button 1 URL (blank = /donate/)', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_cta_btn2_label', __( 'Get in Touch', 'emc-theme' ),
        'emc_hp_cta', __( 'Button 2 Label (optional)', 'emc-theme' ) );
    emc_add_url_setting( $wp_customize, 'emc_cta_btn2_url', '',
        'emc_hp_cta', __( 'Button 2 URL (blank = /contact/)', 'emc-theme' ) );

    /* ──────────────────────────────────────────────────────────────────────
       SECTION: Colors
       ────────────────────────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_colors', array(
        'title'       => __( 'Colors', 'emc-theme' ),
        'panel'       => 'emc_options',
        'priority'    => 15,
        'description' => __( 'Choose a preset scheme or fine-tune individual colours. All changes appear live.', 'emc-theme' ),
    ) );

    // ── Palette Preset Control (appears first in the section) ────────────
    if ( class_exists( 'EMC_Palette_Control' ) ) {
        $wp_customize->add_control( new EMC_Palette_Control( $wp_customize, 'emc_color_presets', array(
            'label'    => '',
            'section'  => 'emc_colors',
            'priority' => 1,
            'settings' => array(), // decorative control — no own setting
        ) ) );
    }

    // ── Individual colour pickers (fine-tune / override) ─────────────────
    emc_add_color_setting( $wp_customize, 'emc_color_primary',      '#2AACA0', 'emc_colors', __( 'Primary Colour',       'emc-theme' ) );
    emc_add_color_setting( $wp_customize, 'emc_color_primary_dark', '#1A7A72', 'emc_colors', __( 'Primary Dark',         'emc-theme' ) );
    emc_add_color_setting( $wp_customize, 'emc_color_accent',       '#C4956A', 'emc_colors', __( 'Accent Colour',        'emc-theme' ) );
    emc_add_color_setting( $wp_customize, 'emc_color_deep_blue',    '#0C1F2E', 'emc_colors', __( 'Dark Background',      'emc-theme' ) );
    emc_add_color_setting( $wp_customize, 'emc_color_text',         '#2D3E4A', 'emc_colors', __( 'Body Text',            'emc-theme' ) );
    emc_add_color_setting( $wp_customize, 'emc_color_light_bg',     '#F5F9F9', 'emc_colors', __( 'Light Background',     'emc-theme' ) );

    /* ──────────────────────────────────────────────────────────────────────
       SECTION: Typography
       ────────────────────────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_typography', array(
        'title'    => __( 'Typography', 'emc-theme' ),
        'panel'    => 'emc_options',
        'priority' => 16,
    ) );

    $heading_fonts = array(
        'Outfit'           => 'Outfit (Default)',
        'Poppins'          => 'Poppins',
        'Lato'             => 'Lato',
        'Playfair Display' => 'Playfair Display',
        'Merriweather'     => 'Merriweather',
        'Raleway'          => 'Raleway',
        'Montserrat'       => 'Montserrat',
        'Oswald'           => 'Oswald',
        'Roboto'           => 'Roboto',
        'Source Serif Pro' => 'Source Serif Pro',
    );
    $body_fonts = array(
        'Inter'           => 'Inter (Default)',
        'Open Sans'       => 'Open Sans',
        'Nunito'          => 'Nunito',
        'Lato'            => 'Lato',
        'Roboto'          => 'Roboto',
        'Source Sans Pro' => 'Source Sans Pro',
        'Noto Sans'       => 'Noto Sans',
        'PT Sans'         => 'PT Sans',
        'Mulish'          => 'Mulish',
        'Outfit'          => 'Outfit',
    );

    $wp_customize->add_setting( 'emc_font_heading', array(
        'default'           => 'Outfit',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'emc_font_heading', array(
        'label'   => __( 'Heading Font', 'emc-theme' ),
        'section' => 'emc_typography',
        'type'    => 'select',
        'choices' => $heading_fonts,
    ) );

    $wp_customize->add_setting( 'emc_font_body', array(
        'default'           => 'Inter',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'emc_font_body', array(
        'label'   => __( 'Body Font', 'emc-theme' ),
        'section' => 'emc_typography',
        'type'    => 'select',
        'choices' => $body_fonts,
    ) );

    $wp_customize->add_setting( 'emc_font_size_base', array(
        'default'           => '16',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'emc_font_size_base', array(
        'label'   => __( 'Base Font Size (px)', 'emc-theme' ),
        'section' => 'emc_typography',
        'type'    => 'select',
        'choices' => array(
            '14' => '14px',
            '15' => '15px',
            '16' => '16px (Default)',
            '17' => '17px',
            '18' => '18px',
        ),
    ) );

    /* ──────────────────────────────────────────────────────────────────────
       SECTION: Buttons
       ────────────────────────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_buttons', array(
        'title'    => __( 'Buttons', 'emc-theme' ),
        'panel'    => 'emc_options',
        'priority' => 17,
    ) );

    $wp_customize->add_setting( 'emc_btn_radius', array(
        'default'           => '0.5rem',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'emc_btn_radius', array(
        'label'       => __( 'Button Border Radius', 'emc-theme' ),
        'description' => __( 'e.g. 0.5rem  ·  8px  ·  50px (pill)', 'emc-theme' ),
        'section'     => 'emc_buttons',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'emc_btn_padding_x', array(
        'default'           => '1.75rem',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'emc_btn_padding_x', array(
        'label'       => __( 'Button Horizontal Padding', 'emc-theme' ),
        'description' => __( 'e.g. 1.75rem  ·  24px', 'emc-theme' ),
        'section'     => 'emc_buttons',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'emc_btn_padding_y', array(
        'default'           => '0.75rem',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'emc_btn_padding_y', array(
        'label'       => __( 'Button Vertical Padding', 'emc-theme' ),
        'description' => __( 'e.g. 0.75rem  ·  12px', 'emc-theme' ),
        'section'     => 'emc_buttons',
        'type'        => 'text',
    ) );

    /* ──────────────────────────────────────────────────────────────────────
       SECTION: Global Layout
       ────────────────────────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_layout', array(
        'title'    => __( 'Global Layout', 'emc-theme' ),
        'panel'    => 'emc_options',
        'priority' => 18,
    ) );

    $wp_customize->add_setting( 'emc_container_max_width', array(
        'default'           => '1280px',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'emc_container_max_width', array(
        'label'   => __( 'Container Max Width', 'emc-theme' ),
        'section' => 'emc_layout',
        'type'    => 'select',
        'choices' => array(
            '1100px' => '1100px — Narrow',
            '1200px' => '1200px',
            '1280px' => '1280px — Default',
            '1400px' => '1400px — Wide',
            '1600px' => '1600px — Full Wide',
        ),
    ) );

    $wp_customize->add_setting( 'emc_section_padding_y', array(
        'default'           => '5rem',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'emc_section_padding_y', array(
        'label'   => __( 'Section Vertical Padding', 'emc-theme' ),
        'section' => 'emc_layout',
        'type'    => 'select',
        'choices' => array(
            '3rem' => 'Compact (3rem)',
            '4rem' => 'Normal (4rem)',
            '5rem' => 'Spacious (5rem — Default)',
            '6rem' => 'Extra Spacious (6rem)',
            '8rem' => 'Maximum (8rem)',
        ),
    ) );

    $wp_customize->add_setting( 'emc_border_radius', array(
        'default'           => '0.75rem',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'emc_border_radius', array(
        'label'       => __( 'Card / Element Border Radius', 'emc-theme' ),
        'description' => __( 'Applies to cards, widgets, boxes. e.g. 0.75rem  ·  12px  ·  0 (sharp)', 'emc-theme' ),
        'section'     => 'emc_layout',
        'type'        => 'text',
    ) );

    /* ──────────────────────────────────────────────────────────────────────
       SECTION: Backgrounds
       ────────────────────────────────────────────────────────────────────── */
    $wp_customize->add_section( 'emc_backgrounds', array(
        'title'    => __( 'Backgrounds', 'emc-theme' ),
        'panel'    => 'emc_options',
        'priority' => 19,
    ) );

    emc_add_color_setting( $wp_customize, 'emc_bg_body',        '#F8FAFC', 'emc_backgrounds', __( 'Body / Page Background',         'emc-theme' ) );
    emc_add_color_setting( $wp_customize, 'emc_bg_header',      '#FFFFFF', 'emc_backgrounds', __( 'Header Background',               'emc-theme' ) );
    emc_add_color_setting( $wp_customize, 'emc_bg_footer',      '#0F172A', 'emc_backgrounds', __( 'Footer Background',               'emc-theme' ) );
    emc_add_color_setting( $wp_customize, 'emc_bg_section_alt', '#EEF5F0', 'emc_backgrounds', __( 'Alternate Section Background',    'emc-theme' ) );

    /* ======================================================================
       SECTION: Blog Settings  (inside emc_options panel)
       ====================================================================== */
    $wp_customize->add_section( 'emc_blog', array(
        'title'    => __( 'Blog Settings', 'emc-theme' ),
        'panel'    => 'emc_options',
        'priority' => 70,
    ) );

    emc_add_text_setting( $wp_customize, 'emc_blog_heading',
        __( 'News &amp; Updates', 'emc-theme' ),
        'emc_blog', __( 'Blog Page Heading', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_blog_subtitle',
        __( 'Latest News', 'emc-theme' ),
        'emc_blog', __( 'Blog Page Sub-heading', 'emc-theme' ) );

    emc_add_textarea_setting( $wp_customize, 'emc_blog_description',
        __( 'Stay informed with news, announcements, and updates from Essex Muslim Centre.', 'emc-theme' ),
        'emc_blog', __( 'Blog Page Description', 'emc-theme' ) );

    // Number of posts per page — stored as a text field, coerced to int in templates.
    emc_add_text_setting( $wp_customize, 'emc_blog_posts_per_page', '9',
        'emc_blog', __( 'Posts Per Page (leave blank for WP default)', 'emc-theme' ) );

    emc_add_checkbox_setting( $wp_customize, 'emc_blog_show_sidebar', 1,
        'emc_blog', __( 'Show Sidebar on Blog Pages', 'emc-theme' ) );

    emc_add_checkbox_setting( $wp_customize, 'emc_blog_show_author_bio', 1,
        'emc_blog', __( 'Show Author Bio on Single Posts', 'emc-theme' ) );

    emc_add_checkbox_setting( $wp_customize, 'emc_blog_show_related', 1,
        'emc_blog', __( 'Show Related Posts on Single Posts', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_blog_related_heading',
        __( 'You Might Also Like', 'emc-theme' ),
        'emc_blog', __( 'Related Posts Heading', 'emc-theme' ) );

    emc_add_text_setting( $wp_customize, 'emc_blog_related_count', '3',
        'emc_blog', __( 'Number of Related Posts to Show', 'emc-theme' ) );

    /* ======================================================================
       Selective Refresh Partials
       ====================================================================== */
    if ( isset( $wp_customize->selective_refresh ) ) {

        // Site title in header logo fallback
        $wp_customize->selective_refresh->add_partial( 'blogname', array(
            'selector'        => '.site-title',
            'render_callback' => function() {
                bloginfo( 'name' );
            },
        ) );

        // Footer about text
        $wp_customize->selective_refresh->add_partial( 'emc_footer_about_text', array(
            'selector'        => '.footer-about-text',
            'render_callback' => function() {
                echo esc_html( emc_option( 'emc_footer_about_text',
                    __( 'Advancing Islamic faith, education, and community welfare in Chelmsford, Essex.', 'emc-theme' )
                ) );
            },
        ) );

        // Announcement bar text
        $wp_customize->selective_refresh->add_partial( 'emc_announcement_text', array(
            'selector'        => '.announcement-text',
            'render_callback' => function() {
                echo esc_html( emc_option( 'emc_announcement_text', '' ) );
            },
        ) );

        // Footer newsletter heading
        $wp_customize->selective_refresh->add_partial( 'emc_footer_newsletter_heading', array(
            'selector'        => '.footer-nl-heading',
            'render_callback' => function() {
                echo esc_html( emc_option( 'emc_footer_newsletter_heading', __( 'Stay in the Loop', 'emc-theme' ) ) );
            },
        ) );
    }
}
add_action( 'customize_register', 'emc_customize_register' );


/* ==========================================================================
   Enqueue Customizer Live Preview JS
   ========================================================================== */
function emc_customize_preview_js() {
    wp_enqueue_script(
        'emc-customizer-preview',
        EMC_ASSETS . '/js/customizer.js',
        array( 'customize-preview', 'jquery' ),
        EMC_VERSION,
        true
    );
}
add_action( 'customize_preview_init', 'emc_customize_preview_js' );


/* ==========================================================================
   Customizer Helpers
   ========================================================================== */

function emc_add_text_setting( $wp_customize, $id, $default, $section, $label, $transport = 'refresh' ) {
    $wp_customize->add_setting( $id, array(
        'default'           => $default,
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => $transport,
    ) );
    $wp_customize->add_control( $id, array(
        'label'   => $label,
        'section' => $section,
        'type'    => 'text',
    ) );
}

function emc_add_textarea_setting( $wp_customize, $id, $default, $section, $label, $transport = 'refresh' ) {
    $wp_customize->add_setting( $id, array(
        'default'           => $default,
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => $transport,
    ) );
    $wp_customize->add_control( $id, array(
        'label'   => $label,
        'section' => $section,
        'type'    => 'textarea',
    ) );
}

function emc_add_url_setting( $wp_customize, $id, $default, $section, $label ) {
    $wp_customize->add_setting( $id, array(
        'default'           => $default,
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( $id, array(
        'label'   => $label,
        'section' => $section,
        'type'    => 'url',
    ) );
}

function emc_add_checkbox_setting( $wp_customize, $id, $default, $section, $label ) {
    $wp_customize->add_setting( $id, array(
        'default'           => $default,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( $id, array(
        'label'   => $label,
        'section' => $section,
        'type'    => 'checkbox',
    ) );
}

function emc_add_color_setting( $wp_customize, $id, $default, $section, $label ) {
    $wp_customize->add_setting( $id, array(
        'default'           => $default,
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, array(
        'label'   => $label,
        'section' => $section,
    ) ) );
}
