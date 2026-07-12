<?php
/**
 * EMC Theme — footer.php
 * Fully dynamic footer driven by the WordPress Customizer.
 *
 * @package emc-theme
 */

$email           = emc_option( 'emc_admin_email',           'admin@essexmuslimcentre.org' );
$phone           = emc_option( 'emc_phone',                 '' );
$charity         = emc_option( 'emc_charity_number',        '1209815' );
$about_text      = emc_option( 'emc_footer_about_text',     __( 'Advancing Islamic faith, education, and community welfare in Chelmsford, Essex.', 'emc-theme' ) );
$col2_heading    = emc_option( 'emc_footer_col2_heading',   __( 'Quick Links', 'emc-theme' ) );
$col3_heading    = emc_option( 'emc_footer_col3_heading',   __( 'Community', 'emc-theme' ) );
$col4_heading    = emc_option( 'emc_footer_col4_heading',   __( 'Contact', 'emc-theme' ) );
$show_nl         = (bool) emc_option( 'emc_footer_newsletter',    true );
$nl_heading      = emc_option( 'emc_footer_newsletter_heading',   __( 'Stay in the Loop', 'emc-theme' ) );
$nl_sub          = emc_option( 'emc_footer_newsletter_sub',       __( 'Get the latest news and events delivered to your inbox.', 'emc-theme' ) );
$custom_copy     = emc_option( 'emc_footer_copyright_text', '' );
$show_privacy    = (bool) emc_option( 'emc_footer_show_privacy', true );
$show_gift_aid   = (bool) emc_option( 'emc_footer_show_gift_aid', true );
$footer_address  = emc_option( 'emc_footer_address', "Essex Muslim Centre\nCuton Hall Lane\nCM2 6PB" );
$show_prayer_lnk = (bool) emc_option( 'emc_footer_show_prayer_link', true );

// Column 2 & 3 links from Customizer (format: "Label|slug" per line)
$col2_links_raw = emc_option( 'emc_footer_col2_links', "About Us|about\nOur Services|services\nPrayer Times|prayer-times\nDonate|donate\nEvents|events\nMedia|media\nVacancies|vacancies\nContact|contact\nPrivacy Policy|privacy-policy" );
$col3_links_raw = emc_option( 'emc_footer_col3_links', "Upcoming Events|events\nMedia Gallery|media\nVolunteering|vacancies\nContact Us|contact" );

/**
 * Parse "Label|slug" text into array of [label, url].
 */
function emc_parse_footer_links( $raw ) {
    $links = array();
    $lines = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );
    foreach ( $lines as $line ) {
        $parts = array_map( 'trim', explode( '|', $line, 2 ) );
        if ( count( $parts ) < 2 ) continue;
        $label = $parts[0];
        $slug  = $parts[1];

        // If it looks like a full URL, use directly
        if ( preg_match( '#^https?://#', $slug ) ) {
            $url = $slug;
        } else {
            $page = get_page_by_path( $slug );
            $url  = $page ? get_permalink( $page ) : home_url( '/' . ltrim( $slug, '/' ) . '/' );
        }
        $links[] = array( 'label' => $label, 'url' => $url );
    }
    return $links;
}

$col2_links = emc_parse_footer_links( $col2_links_raw );
$col3_links = emc_parse_footer_links( $col3_links_raw );
?>

<?php /* Skip native footer when Elementor Pro theme builder provides one. */ ?>
<?php if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'footer' ) ) : ?>

<?php
// ── App Download Strip ────────────────────────────────────────────────────
$ios_url     = emc_option( 'emc_ios_app_url',     '#' );
$android_url = emc_option( 'emc_android_app_url', '#' );
$show_app    = ( $ios_url !== '#' || $android_url !== '#' );

// Platform detection via User-Agent
$ua          = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_ios      = (bool) preg_match( '/iPhone|iPad|iPod/i', $ua );
$is_android  = (bool) preg_match( '/Android/i', $ua );
$show_ios    = ! $is_android; // show on iOS + desktop
$show_android = ! $is_ios;   // show on Android + desktop

if ( $show_app ) :
?>
<div class="app-download-strip" aria-label="<?php esc_attr_e( 'Download our app', 'emc-theme' ); ?>">
    <div class="container app-download-inner">
        <div class="app-download-text">
            <h3><?php esc_html_e( 'Get the EMC App', 'emc-theme' ); ?></h3>
            <p><?php esc_html_e( 'Prayer times, events, news & more — right on your phone.', 'emc-theme' ); ?></p>
        </div>
        <div class="app-download-badges">
            <?php if ( $show_ios && $ios_url ) : ?>
            <a href="<?php echo esc_url( $ios_url ); ?>"
               class="app-badge"
               target="_blank"
               rel="noopener noreferrer"
               aria-label="<?php esc_attr_e( 'Download on the App Store', 'emc-theme' ); ?>">
                <span class="app-badge-icon" aria-hidden="true"><i class="fab fa-apple"></i></span>
                <span class="app-badge-text">
                    <span class="app-badge-sub"><?php esc_html_e( 'Download on the', 'emc-theme' ); ?></span>
                    <span class="app-badge-name"><?php esc_html_e( 'App Store', 'emc-theme' ); ?></span>
                </span>
            </a>
            <?php endif; ?>
            <?php if ( $show_android && $android_url ) : ?>
            <a href="<?php echo esc_url( $android_url ); ?>"
               class="app-badge"
               target="_blank"
               rel="noopener noreferrer"
               aria-label="<?php esc_attr_e( 'Get it on Google Play', 'emc-theme' ); ?>">
                <span class="app-badge-icon" aria-hidden="true"><i class="fab fa-google-play"></i></span>
                <span class="app-badge-text">
                    <span class="app-badge-sub"><?php esc_html_e( 'Get it on', 'emc-theme' ); ?></span>
                    <span class="app-badge-name"><?php esc_html_e( 'Google Play', 'emc-theme' ); ?></span>
                </span>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php /* ── Main Footer ──────────────────────────────────────────────────── */ ?>
<footer class="site-footer" aria-label="<?php esc_attr_e( 'Site footer', 'emc-theme' ); ?>">

    <div class="container">
        <div class="footer-grid">

            <?php /* Column 1 — Brand & About */ ?>
            <div class="footer-col footer-brand">
                <div class="footer-logo">
                    <?php if ( has_custom_logo() ) : ?>
                        <?php the_custom_logo(); ?>
                    <?php else : ?>
                        <span class="footer-logo-icon" aria-hidden="true">
                            <i class="fas fa-mosque"></i>
                        </span>
                    <?php endif; ?>
                    <span class="footer-site-name"><?php bloginfo( 'name' ); ?></span>
                </div>
                <p class="footer-about-text">
                    <?php echo esc_html( $about_text ); ?>
                </p>
            </div>

            <?php /* Column 2 — Quick Links (Customizer-driven) */ ?>
            <div class="footer-col">
                <h4 class="footer-col-heading"><?php echo esc_html( $col2_heading ); ?></h4>
                <?php if ( $col2_links ) : ?>
                <ul class="footer-menu">
                    <?php foreach ( $col2_links as $link ) : ?>
                    <li><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <?php /* Column 3 — Community (Customizer-driven) */ ?>
            <div class="footer-col">
                <h4 class="footer-col-heading"><?php echo esc_html( $col3_heading ); ?></h4>
                <?php if ( $col3_links ) : ?>
                <ul class="footer-menu">
                    <?php foreach ( $col3_links as $link ) : ?>
                    <li><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <?php /* Column 4 — Contact Info (Customizer-driven) */ ?>
            <div class="footer-col">
                <h4 class="footer-col-heading"><?php echo esc_html( $col4_heading ); ?></h4>
                <ul class="footer-contact-list">
                    <?php if ( $footer_address ) : ?>
                    <li>
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <address><?php echo nl2br( esc_html( $footer_address ) ); ?></address>
                    </li>
                    <?php endif; ?>

                    <?php if ( $phone ) : ?>
                    <li>
                        <i class="fas fa-phone" aria-hidden="true"></i>
                        <a href="tel:<?php echo esc_attr( preg_replace( '/[^+\d]/', '', $phone ) ); ?>">
                            <?php echo esc_html( $phone ); ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ( $email ) : ?>
                    <li>
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <a href="mailto:<?php echo esc_attr( $email ); ?>">
                            <?php echo esc_html( $email ); ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ( $show_prayer_lnk ) : ?>
                    <li>
                        <i class="fas fa-clock" aria-hidden="true"></i>
                        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'prayer-times' ) ) ?: home_url( '/prayer-times/' ) ); ?>">
                            <?php esc_html_e( 'Prayer Times', 'emc-theme' ); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <?php /* Bottom Bar */ ?>
        <div class="footer-bottom">
            <p class="footer-copyright">
                <?php if ( $custom_copy ) : ?>
                    <?php echo wp_kses_post( $custom_copy ); ?>
                <?php else : ?>
                    &copy; <?php echo esc_html( emc_copyright_year() ); ?>
                    <?php bloginfo( 'name' ); ?>.
                    <?php
                    printf(
                        /* translators: %s: charity number */
                        esc_html__( 'Registered Charity No. %s', 'emc-theme' ),
                        esc_html( $charity )
                    );
                    ?>
                <?php endif; ?>
                <?php if ( $show_privacy ) : ?>
                    | <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'privacy-policy' ) ) ?: home_url( '/privacy-policy/' ) ); ?>">
                        <?php esc_html_e( 'Privacy Policy', 'emc-theme' ); ?>
                      </a>
                <?php endif; ?>
                <?php if ( $show_gift_aid ) : ?>
                    | <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'gift-aid' ) ) ?: home_url( '/gift-aid/' ) ); ?>">
                        <?php esc_html_e( 'Gift Aid', 'emc-theme' ); ?>
                      </a>
                <?php endif; ?>
            </p>

            <div class="footer-bottom-social" aria-label="<?php esc_attr_e( 'Social media links', 'emc-theme' ); ?>">
                <?php
                $socials = emc_get_social_links();
                foreach ( $socials as $data ) :
                    $url = $data['url'] && $data['url'] !== '#' ? $data['url'] : false;
                    if ( $url ) :
                        printf(
                            '<a href="%s" aria-label="%s" target="_blank" rel="noopener noreferrer"><i class="%s" aria-hidden="true"></i></a>',
                            esc_url( $url ),
                            esc_attr( $data['label'] ),
                            esc_attr( $data['icon'] )
                        );
                    else :
                        printf(
                            '<a href="#" aria-label="%s"><i class="%s" aria-hidden="true"></i></a>',
                            esc_attr( $data['label'] ),
                            esc_attr( $data['icon'] )
                        );
                    endif;
                endforeach;
                ?>
            </div>
        </div>
    </div>
</footer>

<?php endif; /* end Elementor footer location check */ ?>

<?php wp_footer(); ?>
</body>
</html>
