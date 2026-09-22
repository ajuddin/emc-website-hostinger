<?php
/**
 * EMC Theme — inc/helper-functions.php
 * Utility functions used across templates and the Customizer.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render a safe fallback when the separate EMC Payments plugin is inactive.
 */
function emc_render_payment_plugin_required() {
    ?>
    <main>
        <section class="page-hero">
            <div class="container">
                <div class="page-hero-content">
                    <span class="badge"><i class="fas fa-plug" aria-hidden="true"></i> <?php esc_html_e( 'Payment Add-on', 'emc-theme' ); ?></span>
                    <h1><?php esc_html_e( 'EMC Payments Plugin Required', 'emc-theme' ); ?></h1>
                    <p><?php esc_html_e( 'The donation and online payment features are unavailable until the licensed EMC Payments plugin is installed and activated.', 'emc-theme' ); ?></p>
                    <?php if ( current_user_can( 'activate_plugins' ) ) : ?>
                        <a class="btn btn-primary" href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>">
                            <?php esc_html_e( 'Manage Plugins', 'emc-theme' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    <?php
}

/* ==========================================================================
   Theme Option Shorthand
   ========================================================================== */

/**
 * Get a theme option with fallback default.
 */
function emc_option( $option, $default = '' ) {
    return get_theme_mod( $option, $default );
}

/**
 * Return the weekday shown for an event.
 *
 * A dated event uses the weekday from its start date. Recurring events use
 * the weekday selected in Event Details instead.
 *
 * @param int $post_id Event post ID.
 * @return string Localized weekday, or an empty string when none is configured.
 */
function emc_get_event_display_day( $post_id ) {
    /*
     * The recurring day wins over the stored date. A weekly event's date field
     * records when it was first entered, which drifts out of step with the day
     * it actually runs — that is why Friday Prayer was rendering as Saturday.
     */
    $timestamp = emc_get_event_display_timestamp( $post_id );

    return $timestamp ? date_i18n( 'l', $timestamp ) : '';
}

/**
 * Map the stored recurring-day slug to its PHP weekday index.
 *
 * @return array<string,int> Lowercase day slug => 0 (Sunday) to 6 (Saturday).
 */
function emc_event_weekday_map() {
    return array(
        'sunday'    => 0,
        'monday'    => 1,
        'tuesday'   => 2,
        'wednesday' => 3,
        'thursday'  => 4,
        'friday'    => 5,
        'saturday'  => 6,
    );
}

/**
 * Return the timestamp an event should be displayed against.
 *
 * Recurring events resolve to their next upcoming occurrence, so a weekly
 * event never shows a date in the past and never needs re-entering. Dated
 * events return their stored date unchanged.
 *
 * Computed in the WordPress site timezone rather than the server's, matching
 * how the prayer-time code treats "today".
 *
 * @param int $post_id Event post ID.
 * @return int|false Unix timestamp, or false when the event has neither.
 */
function emc_get_event_display_timestamp( $post_id ) {
    $day_slug = strtolower( (string) get_post_meta( $post_id, '_emc_event_day', true ) );
    $weekdays = emc_event_weekday_map();

    if ( isset( $weekdays[ $day_slug ] ) ) {
        $tz    = wp_timezone();
        $today = new DateTimeImmutable( 'today', $tz );
        $ahead = ( $weekdays[ $day_slug ] - (int) $today->format( 'w' ) + 7 ) % 7;

        return $today->modify( sprintf( '+%d days', $ahead ) )->getTimestamp();
    }

    $event_date = get_post_meta( $post_id, '_emc_event_date', true );

    return $event_date ? strtotime( $event_date ) : false;
}

/**
 * Whether an event repeats weekly rather than happening on one fixed date.
 *
 * @param int $post_id Event post ID.
 * @return bool
 */
function emc_event_is_recurring( $post_id ) {
    $day_slug = strtolower( (string) get_post_meta( $post_id, '_emc_event_day', true ) );

    return isset( emc_event_weekday_map()[ $day_slug ] );
}

/**
 * Seed the recurring day for the weekly events that predate the meta field.
 *
 * These schedules were previously hardcoded in a slug lookup, which meant
 * editors could not correct them. Writing them to post meta once moves the
 * schedule into Event Details, where it can be changed from the admin UI.
 * Only empty values are written, so an editor's own choice is never replaced.
 */
function emc_migrate_recurring_event_days() {
    $version = '2026-09-recurring-days';
    if ( $version === get_option( 'emc_recurring_event_days_version' ) ) {
        return;
    }
    update_option( 'emc_recurring_event_days_version', $version, false );

    $schedules = array(
        'friday-prayer-jumuah'           => 'friday',
        'quranic-arabic-language-course' => 'wednesday',
        'dawn-of-reflection'             => 'sunday',
        'tajweed-workshop'               => 'friday',
    );

    foreach ( $schedules as $slug => $day ) {
        $event = get_page_by_path( $slug, OBJECT, 'emc_event' );
        if ( ! $event ) {
            continue;
        }

        if ( '' === (string) get_post_meta( $event->ID, '_emc_event_day', true ) ) {
            update_post_meta( $event->ID, '_emc_event_day', $day );
        }
    }
}
add_action( 'after_setup_theme', 'emc_migrate_recurring_event_days', 25 );

/**
 * Move the saved contact address from the old admin@ mailbox to info@.
 *
 * Runs once, and only when the stored value is still the superseded address,
 * so a site that has deliberately set something else keeps it.
 */
function emc_migrate_contact_email() {
    $version = '2026-09-info-mailbox';
    if ( $version === get_option( 'emc_contact_email_version' ) ) {
        return;
    }
    update_option( 'emc_contact_email_version', $version, false );

    if ( 'admin@essexmuslimcentre.org' === get_theme_mod( 'emc_admin_email' ) ) {
        set_theme_mod( 'emc_admin_email', 'info@essexmuslimcentre.org' );
    }
}
add_action( 'after_setup_theme', 'emc_migrate_contact_email', 26 );

/**
 * Return the shared Badr Wall levels and their current availability.
 *
 * The totals are fixed by the campaign structure, while the number taken is
 * managed in the Customizer. Keeping this calculation here ensures every
 * template displays the same figures.
 *
 * @return array[]
 */
function emc_get_badr_levels() {
    $tier1_total = max( 1, absint( emc_site_setting( 'emc_badr_tier1_total', 100 ) ) );
    $tier2_total = max( 1, absint( emc_site_setting( 'emc_badr_tier2_total', 213 ) ) );
    $levels = array(
        array(
            'id'     => 'founder',
            'label'  => emc_site_setting( 'emc_badr_tier1_label', __( 'Founder of the Centre', 'emc-theme' ) ),
            'amount' => (float) emc_site_setting( 'emc_badr_tier1_amount', 10000 ),
            'icon'   => 'fas fa-trophy',
            'class'  => 'tier-founder',
            'total'  => $tier1_total,
            'filled' => min( max( 0, (int) emc_option( 'emc_campaign_tier1_filled', 19 ) ), $tier1_total ),
            'desc'   => emc_site_setting( 'emc_badr_tier1_desc', __( '£10,000+ — founding places', 'emc-theme' ) ),
        ),
        array(
            'id'     => 'co-founder',
            'label'  => emc_site_setting( 'emc_badr_tier2_label', __( 'Co-Founder of the Centre', 'emc-theme' ) ),
            'amount' => (float) emc_site_setting( 'emc_badr_tier2_amount', 5000 ),
            'icon'   => 'fas fa-star',
            'class'  => 'tier-cofunder',
            'total'  => $tier2_total,
            'filled' => min( max( 0, (int) emc_option( 'emc_campaign_tier2_filled', 11 ) ), $tier2_total ),
            'desc'   => emc_site_setting( 'emc_badr_tier2_desc', __( '£5,000+ — co-founder places', 'emc-theme' ) ),
        ),
    );

    foreach ( $levels as &$level ) {
        $level['remaining'] = max( 0, $level['total'] - $level['filled'] );
    }
    unset( $level );

    return $levels;
}

/**
 * Return the public campaign page URL, including installations where the
 * campaign page is nested below the Donate page.
 *
 * @return string
 */
function emc_get_campaign_url() {
    $campaign_page = get_page_by_path( 'donate/campaign', OBJECT, 'page' );

    if ( ! $campaign_page ) {
        $campaign_pages = get_posts( array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'name'           => 'campaign',
            'posts_per_page' => 1,
            'no_found_rows'  => true,
        ) );
        $campaign_page = $campaign_pages ? reset( $campaign_pages ) : null;
    }

    return $campaign_page ? get_permalink( $campaign_page ) : home_url( '/donate/campaign/' );
}


/* ==========================================================================
   Address & Contact Helpers
   ========================================================================== */

/**
 * Build a formatted address string from Customizer fields.
 * Returns an HTML string ready for output inside <address>.
 *
 * @return string HTML or empty string.
 */
function emc_get_address() {
    $name     = 'Essex Muslim Centre';
    $line1    = emc_option( 'emc_address_line1', 'Cuton Hall Lane' );
    $line2    = emc_option( 'emc_address_line2', '' );
    $city     = emc_option( 'emc_address_city',  '' );
    $postcode = emc_option( 'emc_address_postcode', 'CM2 6PB' );
    $location = emc_option( 'emc_location', 'Essex Muslim Centre, Cuton Hall Lane, CM2 6PB' );

    // If specific fields are populated, build multi-line address.
    if ( $line1 || $postcode ) {
        $parts = array_filter( array( $name, $line1, $line2, $city, $postcode ) );
        return implode( '<br>', array_map( 'esc_html', $parts ) );
    }

    // Fall back to the short location string.
    return $location ? esc_html( $location ) : '';
}

/**
 * Return a tel: href-ready phone string (digits + + only).
 *
 * @return string
 */
function emc_get_phone_href() {
    $phone = emc_option( 'emc_phone', '' );
    return $phone ? preg_replace( '/[^+\d]/', '', $phone ) : '';
}


/* ==========================================================================
   Social Icons
   ========================================================================== */

/**
 * Get social media links array from Customizer.
 *
 * @return array
 */
function emc_get_social_links() {
    $links = array(
        'facebook'  => array(
            'url'   => emc_option( 'emc_social_facebook',  '' ),
            'icon'  => 'fab fa-facebook',
            'label' => __( 'Facebook', 'emc-theme' ),
        ),
        'instagram' => array(
            'url'   => emc_option( 'emc_social_instagram', '' ),
            'icon'  => 'fab fa-instagram',
            'label' => __( 'Instagram', 'emc-theme' ),
        ),
        'twitter'   => array(
            'url'   => emc_option( 'emc_social_twitter',   '' ),
            'icon'  => 'fab fa-x-twitter',
            'label' => __( 'X / Twitter', 'emc-theme' ),
        ),
        'tiktok'    => array(
            'url'   => emc_option( 'emc_social_tiktok',    '' ),
            'icon'  => 'fab fa-tiktok',
            'label' => __( 'TikTok', 'emc-theme' ),
        ),
        'youtube'   => array(
            'url'   => emc_option( 'emc_social_youtube',   '' ),
            'icon'  => 'fab fa-youtube',
            'label' => __( 'YouTube', 'emc-theme' ),
        ),
        'whatsapp'  => array(
            'url'   => emc_option( 'emc_social_whatsapp', '' ),
            'icon'  => 'fab fa-whatsapp',
            'label' => __( 'WhatsApp', 'emc-theme' ),
        ),
    );

    $icons = emc_social_icon_choices();
    foreach ( range( 1, 4 ) as $slot ) {
        $url = emc_option( 'emc_social_custom_' . $slot . '_url', '' );
        if ( ! $url ) {
            continue;
        }
        $icon_key = sanitize_key( emc_option( 'emc_social_custom_' . $slot . '_icon', 'link' ) );
        $label = trim( (string) emc_option( 'emc_social_custom_' . $slot . '_label', '' ) );
        $links[ 'custom_' . $slot ] = array(
            'url'   => $url,
            'icon'  => $icons[ $icon_key ]['class'] ?? $icons['link']['class'],
            'label' => $label ?: __( 'Social link', 'emc-theme' ),
        );
    }

    return $links;
}

/** Icon options available to administrator-defined social links. */
function emc_social_icon_choices() {
    return array(
        'link'      => array( 'label' => __( 'Website / Link', 'emc-theme' ), 'class' => 'fas fa-link' ),
        'whatsapp'  => array( 'label' => __( 'WhatsApp', 'emc-theme' ), 'class' => 'fab fa-whatsapp' ),
        'telegram'  => array( 'label' => __( 'Telegram', 'emc-theme' ), 'class' => 'fab fa-telegram' ),
        'linkedin'  => array( 'label' => __( 'LinkedIn', 'emc-theme' ), 'class' => 'fab fa-linkedin-in' ),
        'threads'   => array( 'label' => __( 'Threads', 'emc-theme' ), 'class' => 'fab fa-threads' ),
        'snapchat'  => array( 'label' => __( 'Snapchat', 'emc-theme' ), 'class' => 'fab fa-snapchat' ),
        'discord'   => array( 'label' => __( 'Discord', 'emc-theme' ), 'class' => 'fab fa-discord' ),
        'pinterest' => array( 'label' => __( 'Pinterest', 'emc-theme' ), 'class' => 'fab fa-pinterest-p' ),
        'email'     => array( 'label' => __( 'Email', 'emc-theme' ), 'class' => 'fas fa-envelope' ),
        'phone'     => array( 'label' => __( 'Phone', 'emc-theme' ), 'class' => 'fas fa-phone' ),
    );
}

function emc_sanitize_social_icon( $value ) {
    $value = sanitize_key( $value );
    return isset( emc_social_icon_choices()[ $value ] ) ? $value : 'link';
}

/**
 * Output social icons HTML.
 * Renders all networks; hides any without a URL via CSS class.
 *
 * @param string $class  Extra CSS class for the wrapper div.
 */
function emc_social_icons( $class = '' ) {
    $links = emc_get_social_links();
    $has_any = false;

    ob_start();
    echo '<div class="social-icons' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '">';
    foreach ( $links as $network => $data ) {
        if ( $data['url'] ) {
            $has_any = true;
            printf(
                '<a href="%s" class="social-icon" aria-label="%s" target="_blank" rel="noopener noreferrer"><i class="%s" aria-hidden="true"></i></a>',
                esc_url( $data['url'] ),
                esc_attr( $data['label'] ),
                esc_attr( $data['icon'] )
            );
        }
    }
    // Placeholder links when none configured — visible only in Customizer preview
    if ( ! $has_any && is_customize_preview() ) {
        foreach ( $links as $network => $data ) {
            printf(
                '<a href="#" class="social-icon social-icon--placeholder" aria-label="%s"><i class="%s" aria-hidden="true"></i></a>',
                esc_attr( $data['label'] ),
                esc_attr( $data['icon'] )
            );
        }
    }
    echo '</div>';
    echo ob_get_clean();
}


/* ==========================================================================
   Navigation Fallbacks
   ========================================================================== */

/**
 * Return the curated header navigation used by desktop and mobile headers.
 *
 * @return array[]
 */
function emc_get_header_nav_items() {
    $campaign_url   = emc_get_campaign_url();
    $membership_url = get_permalink( get_page_by_path( 'membership' ) ) ?: $campaign_url . '#badr-membership';
    $service_items  = array(
        array( 'slug' => 'islamic-education',   'label' => __( 'Islamic Education', 'emc-theme' ) ),
        array( 'slug' => 'nikah-marriage',      'label' => __( 'Nikah Marriage', 'emc-theme' ) ),
        array( 'slug' => 'janaza-services',     'label' => __( 'Janaza Services', 'emc-theme' ) ),
        array( 'slug' => 'meet-an-imam',        'label' => __( 'Meet an Imam', 'emc-theme' ) ),
        array( 'slug' => 'welfare-services',    'label' => __( 'Welfare Services', 'emc-theme' ) ),
        array( 'slug' => 'general-events',      'label' => __( 'General Events', 'emc-theme' ) ),
        array( 'slug' => 'school-visit',        'label' => __( 'School Visit', 'emc-theme' ) ),
        array( 'slug' => 'bereavement-support', 'label' => __( 'Bereavement Support', 'emc-theme' ) ),
    );
    $service_children = array();

    foreach ( $service_items as $service_item ) {
        $service_post = get_page_by_path( $service_item['slug'], OBJECT, 'emc_service' );
        $service_children[] = array(
            'slug'  => $service_item['slug'],
            'label' => $service_item['label'],
            'url'   => $service_post ? get_permalink( $service_post ) : home_url( '/service/' . $service_item['slug'] . '/' ),
        );
    }

    return array(
        array( 'slug' => 'about',      'label' => __( 'About Us', 'emc-theme' ),    'url' => get_permalink( get_page_by_path( 'about' ) ) ?: home_url( '/about/' ) ),
        array( 'slug' => 'services',   'label' => __( 'Services', 'emc-theme' ),    'url' => get_permalink( get_page_by_path( 'services' ) ) ?: home_url( '/services/' ), 'children' => $service_children ),
        array( 'slug' => 'events',     'label' => __( 'Events', 'emc-theme' ),      'url' => get_permalink( get_page_by_path( 'events' ) ) ?: home_url( '/events/' ) ),
        array( 'slug' => 'membership', 'label' => __( 'Membership', 'emc-theme' ),  'url' => $membership_url ),
        array( 'slug' => 'contact',    'label' => __( 'Contact', 'emc-theme' ),     'url' => get_permalink( get_page_by_path( 'contact' ) ) ?: home_url( '/contact/' ) ),
    );
}

/**
 * Render a header nav item, including an optional one-level submenu.
 *
 * @param array $item Navigation item.
 */
function emc_render_header_nav_item( $item ) {
    $children = ! empty( $item['children'] ) && is_array( $item['children'] ) ? $item['children'] : array();
    $classes  = array();

    if ( is_page( $item['slug'] ) ) {
        $classes[] = 'current-menu-item';
    }

    if ( $children ) {
        $classes[] = 'menu-item-has-children';
    }

    $class_attr = $classes ? ' class="' . esc_attr( implode( ' ', $classes ) ) . '"' : '';

    echo '<li' . $class_attr . '>';
    echo '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';

    if ( $children ) {
        echo '<ul class="sub-menu">';
        foreach ( $children as $child ) {
            $child_current = is_singular( 'emc_service' ) && get_post_field( 'post_name', get_queried_object_id() ) === $child['slug'] ? ' class="current-menu-item"' : '';
            echo '<li' . $child_current . '><a href="' . esc_url( $child['url'] ) . '">' . esc_html( $child['label'] ) . '</a></li>';
        }
        echo '</ul>';
    }

    echo '</li>';
}

/**
 * Render the curated desktop header nav.
 */
function emc_header_nav_fallback() {
    echo '<ul>';
    foreach ( emc_get_header_nav_items() as $item ) {
        emc_render_header_nav_item( $item );
    }
    echo '</ul>';
}

/**
 * Render the curated mobile header nav.
 */
function emc_mobile_nav_fallback() {
    echo '<ul class="mobile-menu">';
    $delay = 0.1;
    foreach ( emc_get_header_nav_items() as $item ) {
        $children = ! empty( $item['children'] ) && is_array( $item['children'] ) ? $item['children'] : array();
        $classes  = $children ? ' class="menu-item-has-children sub-open"' : '';

        echo '<li' . $classes . ' style="transition-delay:' . esc_attr( number_format( $delay, 2 ) ) . 's">';
        echo '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
        if ( $children ) {
            echo '<ul class="sub-menu">';
            foreach ( $children as $child ) {
                echo '<li><a href="' . esc_url( $child['url'] ) . '">' . esc_html( $child['label'] ) . '</a></li>';
            }
            echo '</ul>';
        }
        echo '</li>';

        $delay = round( $delay + 0.05, 2 );
    }
    echo '</ul>';
}

/**
 * Fallback for the footer Quick Links column when no WP menu is assigned.
 */
function emc_footer_quick_links_fallback() {
    $links = array(
        'about'        => __( 'About Us',     'emc-theme' ),
        'services'     => __( 'Our Services', 'emc-theme' ),
        'prayer-times' => __( 'Prayer Times', 'emc-theme' ),
        'donate'       => __( 'Donate',       'emc-theme' ),
    );
    echo '<ul class="footer-menu">';
    foreach ( $links as $slug => $label ) {
        $page = get_page_by_path( $slug );
        $url  = $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
        echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
    }
    echo '</ul>';
}

/**
 * Fallback for the footer Community column when no WP menu is assigned.
 */
function emc_footer_community_links() {
    $links = array(
        'events'    => __( 'Upcoming Events', 'emc-theme' ),
        'media'     => __( 'Media Gallery',   'emc-theme' ),
        'volunteer' => __( 'Volunteering',    'emc-theme' ),
        'contact'   => __( 'Contact Us',      'emc-theme' ),
    );
    echo '<ul class="footer-menu">';
    foreach ( $links as $slug => $label ) {
        $page = get_page_by_path( $slug );
        $url  = $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
        echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
    }
    echo '</ul>';
}


/* ==========================================================================
   Misc Helpers
   ========================================================================== */

/**
 * Output a Donate button linked to the donate page.
 *
 * @param string $label  Button label.
 * @param string $class  Extra CSS classes.
 * @param string $url    Optional custom destination URL.
 * @return string  HTML anchor.
 */
function emc_donate_button( $label = '', $class = '', $url = '' ) {
    $label    = $label ?: __( 'Donate Now', 'emc-theme' );
    $page     = get_page_by_path( 'donations' );
    $page_url = $url ?: ( $page ? get_permalink( $page ) : home_url( '/donations/' ) );
    return sprintf(
        '<a href="%s" class="btn btn-primary%s">%s</a>',
        esc_url( $page_url ),
        $class ? ' ' . esc_attr( $class ) : '',
        esc_html( $label )
    );
}

/**
 * Get copyright year range (e.g. "2025–2026").
 *
 * @return string
 */
function emc_copyright_year() {
    $start = '2025';
    $now   = date( 'Y' );
    return $now > $start ? $start . '–' . $now : $now;
}

/**
 * Output charity number badge.
 *
 * @return string
 */
function emc_charity_badge() {
    $number = emc_option( 'emc_charity_number', '1209815' );
    return '<span class="charity-badge"><i class="fas fa-certificate" aria-hidden="true"></i> '
        . sprintf( esc_html__( 'Registered Charity No. %s', 'emc-theme' ), esc_html( $number ) )
        . '</span>';
}

/**
 * Render a compact prayer times placeholder for the header.
 * Populated by script.js via the MasjidBox API.
 *
 * @return string
 */
function emc_prayer_compact_widget() {
    ob_start();
    ?>
    <div class="header-prayer-compact" id="header-prayer-compact" aria-live="polite">
        <i class="fas fa-circle" aria-hidden="true"></i>
        <span><?php esc_html_e( 'Loading…', 'emc-theme' ); ?></span>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Return default page hero data.
 *
 * @return array
 */
function emc_page_hero_defaults() {
    return array(
        'subtitle' => get_the_title(),
        'bg_class' => 'page-hero--default',
    );
}


/* ==========================================================================
   Blog Helpers
   ========================================================================== */

/**
 * Estimate reading time for a post.
 *
 * @param int|null $post_id  Defaults to current post.
 * @return string  e.g. "4 min read"
 */
function emc_reading_time( $post_id = null ) {
    $post_id  = $post_id ?: get_the_ID();
    $content  = get_post_field( 'post_content', $post_id );
    $content  = wp_strip_all_tags( $content );
    $words    = str_word_count( $content );
    $minutes  = max( 1, (int) ceil( $words / 200 ) );
    return sprintf(
        /* translators: %d: number of minutes */
        _n( '%d min read', '%d min read', $minutes, 'emc-theme' ),
        $minutes
    );
}

/**
 * Get related posts for a given post (by shared category).
 *
 * @param int $post_id
 * @param int $count
 * @return WP_Post[]
 */
function emc_get_related_posts( $post_id = null, $count = 3 ) {
    $post_id = $post_id ?: get_the_ID();
    $cats    = wp_get_post_categories( $post_id );
    if ( ! $cats ) {
        return array();
    }
    $query = new WP_Query( array(
        'post_type'           => 'post',
        'posts_per_page'      => $count,
        'post__not_in'        => array( $post_id ),
        'category__in'        => $cats,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'ignore_sticky_posts' => true,
    ) );
    return $query->posts;
}


/* ==========================================================================
   Phase 8: Theme Options — Dynamic Google Fonts & CSS Output
   ========================================================================== */

/**
 * Allowed Google Font names. Only these are ever loaded or output into CSS.
 */
function emc_allowed_fonts() {
    return array(
        // Heading candidates
        'Outfit', 'Poppins', 'Lato', 'Playfair Display', 'Merriweather',
        'Raleway', 'Montserrat', 'Oswald', 'Roboto', 'Source Serif Pro',
        // Body candidates
        'Inter', 'Open Sans', 'Nunito', 'Source Sans Pro', 'Noto Sans',
        'PT Sans', 'Mulish',
    );
}

/**
 * Build a Google Fonts URL from the current heading + body font selections.
 *
 * @return string URL
 */
function emc_get_google_fonts_url() {
    $allowed  = emc_allowed_fonts();
    $heading  = get_theme_mod( 'emc_font_heading', 'Outfit' );
    $body     = get_theme_mod( 'emc_font_body',    'Inter' );

    // Fall back to defaults if an unexpected value somehow gets stored.
    $heading = in_array( $heading, $allowed, true ) ? $heading : 'Outfit';
    $body    = in_array( $body,    $allowed, true ) ? $body    : 'Inter';

    $families = array_unique( array( $heading, $body ) );
    $query    = array();
    foreach ( $families as $font ) {
        $query[] = 'family=' . rawurlencode( $font ) . ':wght@300;400;500;600;700';
    }

    return 'https://fonts.googleapis.com/css2?' . implode( '&', $query ) . '&display=swap';
}

/**
 * Sanitize a CSS dimension / shorthand value.
 * Allows digits, letters, %, space, dot, dash — rejects everything else.
 *
 * @param string $value
 * @param string $default  Returned when value fails validation.
 * @return string
 */
function emc_sanitize_css_value( $value, $default = '' ) {
    $value = trim( $value );
    if ( preg_match( '/^[0-9a-zA-Z%\s\.\-]+$/', $value ) ) {
        return $value;
    }
    return $default;
}

/**
 * Output inline <style> that overrides CSS custom properties and key rules
 * based on Customizer settings. Hooked to wp_head at priority 99 so it loads
 * after the theme stylesheet.
 */
/**
 * Convert a #RRGGBB hex colour to an 'R, G, B' string for use in rgba().
 *
 * @param string $hex  e.g. '#0F172A'
 * @return string      e.g. '15, 23, 42'
 */
function emc_hex_to_rgb( $hex ) {
    $hex = ltrim( $hex, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    return sprintf(
        '%d, %d, %d',
        hexdec( substr( $hex, 0, 2 ) ),
        hexdec( substr( $hex, 2, 2 ) ),
        hexdec( substr( $hex, 4, 2 ) )
    );
}

function emc_output_customizer_css() {

    // ── Colors ────────────────────────────────────────────────────────────
    $primary      = sanitize_hex_color( emc_option( 'emc_color_primary',      '#2AACA0' ) ) ?: '#2AACA0';
    $primary_dark = sanitize_hex_color( emc_option( 'emc_color_primary_dark', '#1A7A72' ) ) ?: '#1A7A72';
    $accent       = sanitize_hex_color( emc_option( 'emc_color_accent',       '#C4956A' ) ) ?: '#C4956A';
    $deep_blue    = sanitize_hex_color( emc_option( 'emc_color_deep_blue',    '#0C1F2E' ) ) ?: '#0C1F2E';
    $text         = sanitize_hex_color( emc_option( 'emc_color_text',         '#2D3E4A' ) ) ?: '#2D3E4A';
    $light_bg     = sanitize_hex_color( emc_option( 'emc_color_light_bg',     '#F5F9F9' ) ) ?: '#F5F9F9';

    // ── Backgrounds ───────────────────────────────────────────────────────
    $bg_body        = sanitize_hex_color( emc_option( 'emc_bg_body',        '#F8FAFC' ) ) ?: '#F8FAFC';
    $bg_header      = sanitize_hex_color( emc_option( 'emc_bg_header',      '#FFFFFF' ) ) ?: '#FFFFFF';
    $bg_footer      = sanitize_hex_color( emc_option( 'emc_bg_footer',      '#0F172A' ) ) ?: '#0F172A';
    $bg_section_alt = sanitize_hex_color( emc_option( 'emc_bg_section_alt', '#EEF5F0' ) ) ?: '#EEF5F0';

    // ── Typography ────────────────────────────────────────────────────────
    $allowed      = emc_allowed_fonts();
    $font_heading = get_theme_mod( 'emc_font_heading', 'Outfit' );
    $font_body    = get_theme_mod( 'emc_font_body',    'Inter' );
    $font_heading = in_array( $font_heading, $allowed, true ) ? $font_heading : 'Outfit';
    $font_body    = in_array( $font_body,    $allowed, true ) ? $font_body    : 'Inter';
    $font_size    = max( 12, min( 24, (int) emc_option( 'emc_font_size_base', '16' ) ) );

    // ── Buttons ───────────────────────────────────────────────────────────
    $btn_radius    = emc_sanitize_css_value( emc_option( 'emc_btn_radius',    '0.5rem' ),  '0.5rem' );
    $btn_padding_x = emc_sanitize_css_value( emc_option( 'emc_btn_padding_x', '1.75rem' ), '1.75rem' );
    $btn_padding_y = emc_sanitize_css_value( emc_option( 'emc_btn_padding_y', '0.75rem' ), '0.75rem' );

    // ── Layout ────────────────────────────────────────────────────────────
    $allowed_widths = array( '1100px', '1200px', '1280px', '1400px', '1600px' );
    $container_w    = emc_option( 'emc_container_max_width', '1280px' );
    $container_w    = in_array( $container_w, $allowed_widths, true ) ? $container_w : '1280px';

    $allowed_pads  = array( '3rem', '4rem', '5rem', '6rem', '8rem' );
    $section_pad   = emc_option( 'emc_section_padding_y', '5rem' );
    $section_pad   = in_array( $section_pad, $allowed_pads, true ) ? $section_pad : '5rem';

    $border_radius = emc_sanitize_css_value( emc_option( 'emc_border_radius', '0.75rem' ), '0.75rem' );

    // ── Output ────────────────────────────────────────────────────────────
    ?>
<style id="emc-theme-custom-css">
:root {
    --primary-green:    <?php echo esc_html( $primary ); ?>;
    --primary-light:    <?php echo esc_html( $primary ); ?>;
    --primary-dark:     <?php echo esc_html( $primary_dark ); ?>;
    --accent-gold:      <?php echo esc_html( $accent ); ?>;
    --deep-blue:        <?php echo esc_html( $deep_blue ); ?>;
    --deep-blue-light:  <?php echo esc_html( $deep_blue ); ?>;
    --text-main:        <?php echo esc_html( $text ); ?>;
    --light-bg:         <?php echo esc_html( $light_bg ); ?>;
    --font-heading:     '<?php echo esc_html( $font_heading ); ?>', sans-serif;
    --font-body:        '<?php echo esc_html( $font_body ); ?>', sans-serif;
    --border-radius:    <?php echo esc_html( $border_radius ); ?>;
    --bg-section-alt:   <?php echo esc_html( $bg_section_alt ); ?>;
}
html { font-size: <?php echo esc_html( $font_size ); ?>px; }
body { background-color: <?php echo esc_html( $bg_body ); ?>; color: <?php echo esc_html( $text ); ?>; }
.container { max-width: <?php echo esc_html( $container_w ); ?>; }
.section-padding { padding: <?php echo esc_html( $section_pad ); ?> 0; }
<?php
    // ── Header background derived from palette, not a flat white ──────────
    $header_rgb      = emc_hex_to_rgb( $deep_blue );
    $header_alt_rgb  = emc_hex_to_rgb( $primary_dark );
    $header_text_rgb = emc_hex_to_rgb( $light_bg );
?>
.main-header:not(.scrolled) {
    background: linear-gradient(135deg,
        rgba(<?php echo esc_html( $header_rgb ); ?>, 0.92) 0%,
        rgba(<?php echo esc_html( $header_alt_rgb ); ?>, 0.82) 100%) !important;
}
.main-header.scrolled {
    background: rgba(<?php echo esc_html( $header_text_rgb ); ?>, 0.97) !important;
}
.site-footer { background-color: <?php echo esc_html( $deep_blue ); ?>; }
.btn { border-radius: <?php echo esc_html( $btn_radius ); ?>; padding: <?php echo esc_html( $btn_padding_y ); ?> <?php echo esc_html( $btn_padding_x ); ?>; }
<?php $logo_h = max( 30, min( 120, (int) emc_option( 'emc_logo_height', 88 ) ) ); ?>
.logo-img, .logo .custom-logo { height: <?php echo esc_html( $logo_h ); ?>px; width: auto !important; }
</style>
    <?php
}
add_action( 'wp_head', 'emc_output_customizer_css', 99 );
