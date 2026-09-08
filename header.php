<?php
/**
 * EMC Theme — header.php
 * Phase 3: Fully dynamic header with announcement bar, customizer controls,
 * selective refresh support, and accessible menus.
 *
 * @package emc-theme
 */

$show_announcement   = (bool) emc_option( 'emc_announcement_enabled', false );
$announcement_text   = emc_option( 'emc_announcement_text', '' );
$announcement_url    = emc_option( 'emc_announcement_link', '' );
$announcement_label  = emc_option( 'emc_announcement_link_text', __( 'Donate Now', 'emc-theme' ) );
$announcement_dismiss = (bool) emc_option( 'emc_announcement_dismissible', true );

$show_prayer   = (bool) emc_option( 'emc_header_prayer', true );
$show_donate   = (bool) emc_option( 'emc_header_donate_btn', true );
$donate_label  = emc_option( 'emc_header_donate_label', __( 'Donate Now', 'emc-theme' ) );
$sticky_header = (bool) emc_option( 'emc_header_sticky', true );
$cookie_on     = (bool) emc_option( 'emc_cookie_enabled', true );
$logo_height   = (int) emc_option( 'emc_logo_height', 68 );
$cookie_msg    = emc_option( 'emc_cookie_message', __( 'We use cookies to improve your experience. By continuing you agree to our Privacy Policy.', 'emc-theme' ) );
$cookie_accept = emc_option( 'emc_cookie_accept_label', __( 'Accept', 'emc-theme' ) );
$cookie_decline = emc_option( 'emc_cookie_decline_label', __( 'Decline', 'emc-theme' ) );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php wp_head(); ?>
</head>
<body <?php body_class( $sticky_header ? 'sticky-header-enabled' : '' ); ?>>
<?php wp_body_open(); ?>

<?php /* ── Cookie Consent Banner ─────────────────────────────────────────── */ ?>
<?php if ( $cookie_on ) : ?>
<div
    id="emc-cookie-banner"
    class="cookie-banner"
    role="dialog"
    aria-label="<?php esc_attr_e( 'Cookie consent', 'emc-theme' ); ?>"
    aria-describedby="cookie-message"
    style="display:none;"
    data-privacy-url="<?php echo esc_url( get_permalink( get_page_by_path( 'privacy-policy' ) ) ?: home_url( '/privacy-policy/' ) ); ?>"
>
    <div class="cookie-banner-inner container">
        <p id="cookie-message">
            <?php echo wp_kses( $cookie_msg, array( 'a' => array( 'href' => array(), 'target' => array() ) ) ); ?>
            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'privacy-policy' ) ) ?: home_url( '/privacy-policy/' ) ); ?>">
                <?php esc_html_e( 'Privacy Policy', 'emc-theme' ); ?>
            </a>
        </p>
        <div class="cookie-actions">
            <button id="emc-cookie-accept" class="btn btn-primary btn-sm">
                <?php echo esc_html( $cookie_accept ); ?>
            </button>
            <button id="emc-cookie-decline" class="btn btn-outline btn-sm">
                <?php echo esc_html( $cookie_decline ); ?>
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php /* ── Announcement Bar ─────────────────────────────────────────────── */ ?>
<?php if ( $show_announcement && $announcement_text ) : ?>
<div
    class="announcement-bar"
    id="announcement-bar"
    role="banner"
    aria-label="<?php esc_attr_e( 'Site announcement', 'emc-theme' ); ?>"
    data-dismissible="<?php echo $announcement_dismiss ? 'true' : 'false'; ?>"
>
    <div class="container announcement-inner">
        <span class="announcement-text"><?php echo esc_html( $announcement_text ); ?></span>
        <?php if ( $announcement_url ) : ?>
        <a
            href="<?php echo esc_url( $announcement_url ); ?>"
            class="announcement-link btn btn-sm"
            <?php if ( strpos( $announcement_url, home_url() ) === false ) : ?>
                target="_blank" rel="noopener noreferrer"
            <?php endif; ?>
        >
            <?php echo esc_html( $announcement_label ); ?>
        </a>
        <?php endif; ?>
        <?php if ( $announcement_dismiss ) : ?>
        <button
            class="announcement-dismiss"
            id="announcement-dismiss"
            aria-label="<?php esc_attr_e( 'Dismiss announcement', 'emc-theme' ); ?>"
        >
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php /* ── Prayer Times Top Bar ──────────────────────────────────────── */ ?>
<div class="prayer-top-bar" id="prayer-top-bar" role="complementary" aria-label="<?php esc_attr_e( 'Today\'s prayer times', 'emc-theme' ); ?>">
    <div class="container prayer-top-bar-inner">

        <!-- Left: Date & Jumu'ah -->
        <div class="ptb-left">
            <div class="ptb-dates">
                <span class="ptb-gregorian" id="ptb-gregorian"><?php echo esc_html( wp_date( 'jS F Y' ) ); ?></span>
                <span class="ptb-sep" aria-hidden="true">·</span>
                <span class="ptb-hijri" id="ptb-hijri"><?php esc_html_e( 'Hijri date loading', 'emc-theme' ); ?></span>
            </div>
            <div class="ptb-jumuah" id="ptb-jumuah-wrap">
                <span class="ptb-jumuah-label"><?php esc_html_e( "Jum'a", 'emc-theme' ); ?></span>
                <span class="ptb-jumuah-time" id="ptb-jumuah">--:--</span>
                <span class="ptb-jumuah-extra">&amp; <?php echo esc_html( emc_site_setting( 'emc_header_second_jumuah', '14:15' ) ); ?></span>
                <span class="ptb-jumuah-dot" aria-hidden="true">·</span>
                <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'prayer-times' ) ) ?: home_url( '/prayer-times/' ) ); ?>" class="ptb-prayer-times-link">
                    <?php esc_html_e( 'Prayer Times', 'emc-theme' ); ?>
                </a>
            </div>
        </div>

        <!-- Right: 5 Prayer columns -->
        <div class="ptb-prayers" role="table" aria-label="<?php esc_attr_e( 'Prayer times table', 'emc-theme' ); ?>">
            <div class="ptb-row-head" aria-hidden="true">
                <span></span>
                <span><?php esc_html_e( "Jama'at", 'emc-theme' ); ?></span>
                <span><?php esc_html_e( 'Begins', 'emc-theme' ); ?></span>
            </div>
            <?php foreach ( array( 'fajr' => 'Fajr', 'dhuhr' => 'Zuhr', 'asr' => 'Asr', 'maghrib' => 'Maghrib', 'isha' => 'Isha' ) as $key => $label ) : ?>
            <div class="ptb-prayer-col" data-prayer="<?php echo esc_attr( $key ); ?>" role="columnheader">
                <span class="ptb-prayer-name"><?php echo esc_html( $label ); ?></span>
                <span class="ptb-iqamah" id="ptb-iqamah-<?php echo esc_attr( $key ); ?>">--:--</span>
                <span class="ptb-adhan" id="ptb-adhan-<?php echo esc_attr( $key ); ?>">--:--</span>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<?php /* ── Main Header ──────────────────────────────────────────────────── */ ?>
<?php /* Skip native header when Elementor Pro theme builder provides one. */ ?>
<?php if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'header' ) ) : ?>
<header
    class="main-header<?php echo is_front_page() && ! $show_announcement ? '' : ' scrolled'; ?>"
    id="header"
    role="banner"
>
    <div class="container">

        <?php /* Logo */ ?>
        <div class="logo" id="site-logo">
            <?php if ( has_custom_logo() ) : ?>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="custom-logo-link" rel="home">
                    <?php
                    $custom_logo_id  = get_theme_mod( 'custom_logo' );
                    $custom_logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
                    ?>
                    <img src="<?php echo esc_url( $custom_logo_url ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="logo-img" style="height:<?php echo esc_attr( $logo_height ); ?>px;width:auto;">
                    <div class="logo-text">
                        <span class="site-title"><?php bloginfo( 'name' ); ?></span>
                        <?php $desc = get_bloginfo( 'description' ); if ( $desc ) : ?>
                        <span class="site-tagline"><?php echo esc_html( $desc ); ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <img src="<?php echo esc_url( EMC_ASSETS . '/images/logo.jpeg' ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="logo-img" style="height:<?php echo esc_attr( $logo_height ); ?>px;width:auto;">
                    <div class="logo-text">
                        <span class="site-title"><?php bloginfo( 'name' ); ?></span>
                        <?php $desc = get_bloginfo( 'description' ); if ( $desc ) : ?>
                        <span class="site-tagline"><?php echo esc_html( $desc ); ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endif; ?>
        </div>

        <?php /* Desktop Navigation */ ?>
        <nav
            class="desktop-nav"
            id="desktop-nav"
            aria-label="<?php esc_attr_e( 'Primary Navigation', 'emc-theme' ); ?>"
        >
            <?php
            wp_nav_menu( array(
                'theme_location' => 'primary',
                'container'      => false,
                'menu_id'        => 'primary-menu',
                'menu_class'     => 'header-menu',
                'depth'          => 2,
                'fallback_cb'    => 'emc_header_nav_fallback',
            ) );
            ?>
        </nav>

        <?php /* Header Actions */ ?>
        <div class="header-actions">

            <?php if ( $show_prayer ) : ?>
            <div
                class="header-prayer-compact"
                id="header-prayer-compact"
                aria-live="polite"
                aria-label="<?php esc_attr_e( 'Next prayer countdown', 'emc-theme' ); ?>"
            >
                <i class="fas fa-clock" aria-hidden="true"></i>
                <span id="header-next-prayer"><?php esc_html_e( 'Prayer Times', 'emc-theme' ); ?></span>
            </div>
            <?php endif; ?>

            <?php if ( $show_donate ) : ?>
            <div class="magnetic-btn">
                <?php echo emc_donate_button( $donate_label, '', $donate_url ); ?>
            </div>
            <?php endif; ?>

            <button
                class="mobile-menu-toggle"
                id="mobile-toggle"
                aria-label="<?php esc_attr_e( 'Open menu', 'emc-theme' ); ?>"
                aria-expanded="false"
                aria-controls="mobile-nav"
                aria-haspopup="true"
            >
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</header>

<?php /* ── Mobile Navigation Overlay ───────────────────────────────────── */ ?>
<div
    class="mobile-nav-overlay"
    id="mobile-nav"
    role="dialog"
    aria-label="<?php esc_attr_e( 'Mobile Navigation', 'emc-theme' ); ?>"
    aria-modal="true"
    hidden
>
    <div class="mobile-nav-header">
        <?php /* Logo inside mobile menu */ ?>
        <div class="mobile-logo">
            <span><?php bloginfo( 'name' ); ?></span>
        </div>
        <button
            class="mobile-menu-toggle"
            id="close-toggle"
            aria-label="<?php esc_attr_e( 'Close menu', 'emc-theme' ); ?>"
        >
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
    </div>

    <?php
    $mobile_menu_location = has_nav_menu( 'mobile' ) ? 'mobile' : 'primary';
    wp_nav_menu( array(
        'theme_location' => $mobile_menu_location,
        'container'      => false,
        'menu_id'        => 'mobile-menu-list',
        'menu_class'     => 'mobile-menu',
        'depth'          => 2,
        'fallback_cb'    => 'emc_mobile_nav_fallback',
    ) );
    ?>

    <div class="mobile-donate-wrap">
        <?php echo emc_donate_button( $donate_label, 'mobile-donate-btn' ); ?>
    </div>

    <?php if ( $show_prayer ) : ?>
    <div class="mobile-prayer-compact" aria-live="polite">
        <i class="fas fa-mosque" aria-hidden="true"></i>
        <span id="mobile-next-prayer"><?php esc_html_e( 'Prayer Times', 'emc-theme' ); ?></span>
    </div>
    <?php endif; ?>

    <?php /* Social icons in mobile nav */ ?>
    <?php emc_social_icons( 'mobile-social' ); ?>
</div>
<?php endif; /* end Elementor header location check */ ?>
