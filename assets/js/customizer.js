/**
 * EMC Theme — customizer.js
 * postMessage live-preview bindings for the WordPress Customizer.
 * Fields using 'postMessage' transport update the iframe instantly.
 */
( function( $, api ) {
    'use strict';

    /* ── CSS injection helper ──────────────────────────────────────────── */
    function emcInjectCSS( id, css ) {
        var $el = $( '#' + id );
        if ( $el.length ) {
            $el.text( css );
        } else {
            $( 'head' ).append( '<style id="' + id + '">' + css + '</style>' );
        }
    }

    /* ── Colors ────────────────────────────────────────────────────────── */

    /* Helper: update header gradient whenever primary or deep-blue changes */
    function emcUpdateHeaderBg() {
        var deepBlue   = api( 'emc_color_deep_blue'    ).get() || '#0F172A';
        var primaryDark = api( 'emc_color_primary_dark' ).get() || '#06402A';
        var lightBg    = api( 'emc_color_light_bg'     ).get() || '#F8FAFC';
        emcInjectCSS( 'emc-live-header',
            '.main-header:not(.scrolled){background:linear-gradient(135deg,' +
            deepBlue + 'EB 0%,' + primaryDark + 'D1 100%)!important;}' +
            '.main-header.scrolled{background:' + lightBg + 'F7!important;}'
        );
    }

    api( 'emc_color_primary', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-primary',
                ':root { --primary-green: ' + to + '; --primary-light: ' + to + '; }' );
        } );
    } );

    api( 'emc_color_primary_dark', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-primary-dark', ':root { --primary-dark: ' + to + '; }' );
            emcUpdateHeaderBg();
        } );
    } );

    api( 'emc_color_accent', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-accent', ':root { --accent-gold: ' + to + '; }' );
        } );
    } );

    api( 'emc_color_deep_blue', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-deep-blue',
                ':root { --deep-blue: ' + to + '; --deep-blue-light: ' + to + '; }' );
            emcUpdateHeaderBg();
        } );
    } );

    api( 'emc_color_text', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-text',
                ':root { --text-main: ' + to + '; } body { color: ' + to + '; }' );
        } );
    } );

    api( 'emc_color_light_bg', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-light-bg', ':root { --light-bg: ' + to + '; }' );
            emcUpdateHeaderBg();
        } );
    } );

    /* ── Backgrounds ───────────────────────────────────────────────────── */

    api( 'emc_bg_body', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-bg-body', 'body { background-color: ' + to + '; }' );
        } );
    } );

    api( 'emc_bg_header', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-bg-header', '.main-header { background-color: ' + to + '; }' );
        } );
    } );

    api( 'emc_bg_footer', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-bg-footer', '.site-footer { background-color: ' + to + '; }' );
        } );
    } );

    api( 'emc_bg_section_alt', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-bg-alt', ':root { --bg-section-alt: ' + to + '; }' );
        } );
    } );

    /* ── Typography ────────────────────────────────────────────────────── */

    api( 'emc_font_heading', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-font-heading',
                'h1,h2,h3,h4,h5,h6 { font-family: "' + to + '", sans-serif; }' );
        } );
    } );

    api( 'emc_font_body', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-font-body',
                'body { font-family: "' + to + '", sans-serif; }' );
        } );
    } );

    api( 'emc_font_size_base', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-font-size', 'html { font-size: ' + to + 'px; }' );
        } );
    } );

    /* ── Buttons ───────────────────────────────────────────────────────── */

    api( 'emc_btn_radius', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-btn-radius', '.btn { border-radius: ' + to + '; }' );
        } );
    } );

    api( 'emc_btn_padding_x', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-btn-px', ':root { --btn-padding-x: ' + to + '; }' );
        } );
    } );

    api( 'emc_btn_padding_y', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-btn-py', ':root { --btn-padding-y: ' + to + '; }' );
        } );
    } );

    /* ── Global Layout ─────────────────────────────────────────────────── */

    api( 'emc_container_max_width', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-container', '.container { max-width: ' + to + '; }' );
        } );
    } );

    api( 'emc_section_padding_y', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-section-pad',
                '.section-padding { padding: ' + to + ' 0; }' );
        } );
    } );

    api( 'emc_border_radius', function( value ) {
        value.bind( function( to ) {
            emcInjectCSS( 'emc-live-radius', ':root { --border-radius: ' + to + '; }' );
        } );
    } );

    /* ── Site Identity ─────────────────────────────────────────────────── */

    api( 'blogname', function( value ) {
        value.bind( function( to ) {
            $( '.site-title, .footer-site-name, .mobile-logo span' ).text( to );
        } );
    } );

    /* ── Hero Section ──────────────────────────────────────────────────── */

    api( 'emc_hero_title', function( value ) {
        value.bind( function( to ) { $( '.hero-title' ).text( to ); } );
    } );

    api( 'emc_hero_subtitle', function( value ) {
        value.bind( function( to ) { $( '.hero-subtitle' ).text( to ); } );
    } );

    api( 'emc_hero_donate_heading', function( value ) {
        value.bind( function( to ) { $( '.quick-donate-card h3' ).text( to ); } );
    } );

    api( 'emc_hero_donate_proceed_label', function( value ) {
        value.bind( function( to ) { $( '#quick-donate-proceed' ).text( to ); } );
    } );

    /* ── About Section ─────────────────────────────────────────────────── */

    api( 'emc_about_heading', function( value ) {
        value.bind( function( to ) { $( '#about-intro-heading' ).text( to ); } );
    } );

    api( 'emc_about_subheading', function( value ) {
        value.bind( function( to ) { $( '.about-intro-text .subtitle' ).text( to ); } );
    } );

    api( 'emc_about_body', function( value ) {
        value.bind( function( to ) { $( '.about-intro-text > p' ).first().text( to ); } );
    } );

    api( 'emc_about_stat1_num', function( value ) {
        value.bind( function( to ) { $( '.about-intro-stat:nth-child(1) .stat-num' ).text( to ); } );
    } );
    api( 'emc_about_stat1_label', function( value ) {
        value.bind( function( to ) { $( '.about-intro-stat:nth-child(1) .stat-label' ).text( to ); } );
    } );
    api( 'emc_about_stat2_num', function( value ) {
        value.bind( function( to ) { $( '.about-intro-stat:nth-child(2) .stat-num' ).text( to ); } );
    } );
    api( 'emc_about_stat2_label', function( value ) {
        value.bind( function( to ) { $( '.about-intro-stat:nth-child(2) .stat-label' ).text( to ); } );
    } );
    api( 'emc_about_stat3_num', function( value ) {
        value.bind( function( to ) { $( '.about-intro-stat:nth-child(3) .stat-num' ).text( to ); } );
    } );
    api( 'emc_about_stat3_label', function( value ) {
        value.bind( function( to ) { $( '.about-intro-stat:nth-child(3) .stat-label' ).text( to ); } );
    } );

    api( 'emc_about_cta_label', function( value ) {
        value.bind( function( to ) { $( '.about-intro-text .btn-primary' ).text( to ); } );
    } );

    api( 'emc_about_bullet1', function( value ) {
        value.bind( function( to ) { $( '.about-values-list li:nth-child(1)' ).html( '<i class="fas fa-check-circle"></i> ' + to ); } );
    } );
    api( 'emc_about_bullet2', function( value ) {
        value.bind( function( to ) { $( '.about-values-list li:nth-child(2)' ).html( '<i class="fas fa-check-circle"></i> ' + to ); } );
    } );
    api( 'emc_about_bullet3', function( value ) {
        value.bind( function( to ) { $( '.about-values-list li:nth-child(3)' ).html( '<i class="fas fa-check-circle"></i> ' + to ); } );
    } );

    /* ── Services Section ──────────────────────────────────────────────── */

    api( 'emc_services_heading', function( value ) {
        value.bind( function( to ) { $( '#services-heading' ).text( to ); } );
    } );

    api( 'emc_services_subheading', function( value ) {
        value.bind( function( to ) { $( '.services-preview .subtitle' ).text( to ); } );
    } );

    api( 'emc_services_cta_label', function( value ) {
        value.bind( function( to ) { $( '.services-preview .section-cta .btn' ).text( to ); } );
    } );

    /* ── Events Section ────────────────────────────────────────────────── */

    api( 'emc_events_heading', function( value ) {
        value.bind( function( to ) { $( '#events-heading' ).text( to ); } );
    } );

    api( 'emc_events_subheading', function( value ) {
        value.bind( function( to ) { $( '.events-preview .subtitle' ).text( to ); } );
    } );

    api( 'emc_events_cta_label', function( value ) {
        value.bind( function( to ) { $( '.events-preview .section-cta .btn' ).text( to ); } );
    } );

    /* ── Campaign Section ──────────────────────────────────────────────── */

    api( 'emc_campaign_heading', function( value ) {
        value.bind( function( to ) { $( '#campaign-heading' ).text( to ); } );
    } );

    api( 'emc_campaign_badge', function( value ) {
        value.bind( function( to ) { $( '.campaign-badge' ).text( to ); } );
    } );

    api( 'emc_campaign_desc', function( value ) {
        value.bind( function( to ) { $( '.campaign-description' ).text( to ); } );
    } );

    api( 'emc_campaign_cta_label', function( value ) {
        value.bind( function( to ) { $( '.campaign-cta .btn-primary' ).text( to ); } );
    } );

    /* ── CTA Strip ─────────────────────────────────────────────────────── */

    api( 'emc_cta_heading', function( value ) {
        value.bind( function( to ) { $( '#cta-strip-heading' ).text( to ); } );
    } );

    api( 'emc_cta_subtitle', function( value ) {
        value.bind( function( to ) { $( '.cta-strip-text p' ).text( to ); } );
    } );

    api( 'emc_cta_btn1_label', function( value ) {
        value.bind( function( to ) { $( '.cta-strip-actions .btn-primary' ).text( to ); } );
    } );

    api( 'emc_cta_btn2_label', function( value ) {
        value.bind( function( to ) { $( '.cta-strip-actions .btn-outline' ).text( to ); } );
    } );

    /* ── Team / Testimonials / FAQ headings ────────────────────────────── */

    api( 'emc_team_heading', function( value ) {
        value.bind( function( to ) { $( '#team-heading' ).text( to ); } );
    } );

    api( 'emc_team_subheading', function( value ) {
        value.bind( function( to ) { $( '.team-section .subtitle' ).text( to ); } );
    } );

    api( 'emc_testimonials_heading', function( value ) {
        value.bind( function( to ) { $( '#testimonials-heading' ).text( to ); } );
    } );

    api( 'emc_testimonials_subheading', function( value ) {
        value.bind( function( to ) { $( '.testimonials-section .subtitle' ).text( to ); } );
    } );

    api( 'emc_faq_heading', function( value ) {
        value.bind( function( to ) { $( '#faq-heading' ).text( to ); } );
    } );

    api( 'emc_faq_subheading', function( value ) {
        value.bind( function( to ) { $( '.faq-section .subtitle' ).text( to ); } );
    } );

    /* ── Footer About Text ─────────────────────────────────────────────── */

    api( 'emc_footer_about_text', function( value ) {
        value.bind( function( to ) { $( '.footer-about-text' ).text( to ); } );
    } );

    /* ── Announcement Bar ──────────────────────────────────────────────── */

    api( 'emc_announcement_text', function( value ) {
        value.bind( function( to ) { $( '.announcement-text' ).text( to ); } );
    } );

    api( 'emc_announcement_enabled', function( value ) {
        value.bind( function( to ) { $( '#announcement-bar' ).toggle( !! to ); } );
    } );

    /* ── Footer Column Headings ────────────────────────────────────────── */

    api( 'emc_footer_newsletter_heading', function( value ) {
        value.bind( function( to ) { $( '.footer-nl-heading' ).text( to ); } );
    } );

    api( 'emc_footer_col2_heading', function( value ) {
        value.bind( function( to ) { $( '.footer-col:nth-child(2) .footer-col-heading' ).text( to ); } );
    } );

    api( 'emc_footer_col3_heading', function( value ) {
        value.bind( function( to ) { $( '.footer-col:nth-child(3) .footer-col-heading' ).text( to ); } );
    } );

    api( 'emc_footer_col4_heading', function( value ) {
        value.bind( function( to ) { $( '.footer-col:nth-child(4) .footer-col-heading' ).text( to ); } );
    } );

    /* ── Header Donate Button ───────────────────────────────────────────── */

    api( 'emc_header_donate_label', function( value ) {
        value.bind( function( to ) {
            $( '#header .btn-primary, .mobile-donate-wrap .btn-primary' ).text( to );
        } );
    } );

    /* ── Logo Height ────────────────────────────────────────────────────── */

    api( 'emc_logo_height', function( value ) {
        value.bind( function( to ) {
            $( '.logo-img, .logo .custom-logo' ).css( { height: to + 'px', width: 'auto' } );
        } );
    } );

} )( jQuery, wp.customize );
