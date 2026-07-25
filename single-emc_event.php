<?php
/**
 * EMC Theme — single-emc_event.php
 * Premium single event page with ACF-editable content.
 *
 * Meta fields (native meta boxes):
 *   _emc_event_date      — start date (Y-m-d)
 *   _emc_event_end_date  — end date (Y-m-d)
 *   _emc_event_time      — time string e.g. "7:00 PM – 10:30 PM"
 *   _emc_event_venue     — venue/location
 *   _emc_event_capacity  — capacity number
 *   _emc_event_reg_link  — registration URL
 *   _emc_event_category  — category slug (community/religious/youth/fundraising)
 *
 * ACF fields (emc_event post type):
 *   evt_single_intro              — intro paragraph
 *   evt_single_highlights_heading — highlights section heading
 *   evt_highlight_{n}_icon/title/desc — up to 4 highlight cards
 *   evt_single_cta_heading/desc/btn_label/btn_url — sidebar CTA
 *
 * @package emc-theme
 */

get_header();

wp_enqueue_style( 'emc-page-events',    EMC_ASSETS . '/css/events.css',    array( 'emc-style' ), EMC_VERSION );
wp_enqueue_style( 'emc-page-services',  EMC_ASSETS . '/css/services.css',  array( 'emc-style', 'emc-page-events' ), EMC_VERSION );

while ( have_posts() ) :
    the_post();

    $post_id  = get_the_ID();
    $has_acf  = function_exists( 'get_field' );

    // ── Native meta fields ──────────────────────────────────────────────────
    $date     = get_post_meta( $post_id, '_emc_event_date',     true );
    $end_date = get_post_meta( $post_id, '_emc_event_end_date', true );
    $time     = get_post_meta( $post_id, '_emc_event_time',     true );
    $venue    = get_post_meta( $post_id, '_emc_event_venue',    true );
    $capacity = get_post_meta( $post_id, '_emc_event_capacity', true );
    $reg_link = get_post_meta( $post_id, '_emc_event_reg_link', true );
    $ev_cat   = get_post_meta( $post_id, '_emc_event_category', true ) ?: '';
    $cats     = get_the_terms( $post_id, 'event_category' );
    $cat_name = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : ucfirst( $ev_cat );

    $day          = $date ? date( 'd', strtotime( $date ) ) : '';
    $month        = $date ? strtoupper( date( 'M', strtotime( $date ) ) ) : '';
    $year         = $date ? date( 'Y', strtotime( $date ) ) : '';
    $weekday      = $date ? date_i18n( 'l', strtotime( $date ) ) : '';
    $fmt_date     = $date ? date_i18n( 'l, j F Y', strtotime( $date ) ) : '';
    $fmt_end      = $end_date ? date_i18n( 'l, j F Y', strtotime( $end_date ) ) : '';

    // ── ACF fields ──────────────────────────────────────────────────────────
    $intro        = $has_acf ? get_field( 'evt_single_intro' )              : '';
    $hl_heading   = $has_acf ? get_field( 'evt_single_highlights_heading' ) : __( 'Event Highlights', 'emc-theme' );
    $cta_heading  = $has_acf ? get_field( 'evt_single_cta_heading' )        : __( 'Join This Event', 'emc-theme' );
    $cta_desc     = $has_acf ? get_field( 'evt_single_cta_desc' )           : __( 'All are welcome. Contact us for more information.', 'emc-theme' );
    $cta_btn_lbl  = $has_acf ? get_field( 'evt_single_cta_btn_label' )      : __( 'Get in Touch', 'emc-theme' );
    $cta_btn_url  = $has_acf ? get_field( 'evt_single_cta_btn_url' )        : home_url( '/contact/' );

    if ( ! $cta_btn_url ) $cta_btn_url = home_url( '/contact/' );

    // Highlights (ACF)
    $highlights = array();
    if ( $has_acf ) {
        for ( $n = 1; $n <= 4; $n++ ) {
            $hl_title = get_field( "evt_highlight_{$n}_title" );
            if ( $hl_title ) {
                $highlights[] = array(
                    'icon'  => get_field( "evt_highlight_{$n}_icon" ) ?: 'fas fa-check-circle',
                    'title' => $hl_title,
                    'desc'  => get_field( "evt_highlight_{$n}_desc" ) ?: '',
                );
            }
        }
    }

    // Category colour classes
    $cat_colours = array(
        'religious'   => 'cat-religious',
        'community'   => 'cat-community',
        'youth'       => 'cat-youth',
        'fundraising' => 'cat-fundraising',
    );
    $cat_class = $cat_colours[ $ev_cat ] ?? 'cat-community';
?>

<?php /* ════ HERO ════ */ ?>
<section class="evt-single-hero <?php echo esc_attr( $cat_class ); ?>"
         <?php if ( has_post_thumbnail() ) : ?>
         style="background-image:url(<?php echo esc_url( get_the_post_thumbnail_url( $post_id, 'emc-hero' ) ); ?>)"
         <?php endif; ?>
         aria-labelledby="evt-single-heading">
    <div class="evt-single-hero-overlay"></div>
    <div class="container evt-single-hero-inner">

        <?php if ( $day ) : ?>
        <div class="evt-single-date-badge" aria-label="<?php echo esc_attr( $fmt_date ); ?>">
            <span class="evt-badge-weekday"><?php echo esc_html( $weekday ); ?></span>
            <span class="evt-badge-day"><?php echo esc_html( $day ); ?></span>
            <span class="evt-badge-month"><?php echo esc_html( $month ); ?></span>
            <span class="evt-badge-year"><?php echo esc_html( $year ); ?></span>
        </div>
        <?php endif; ?>

        <?php if ( $cat_name ) : ?>
        <span class="evt-single-cat-tag <?php echo esc_attr( $cat_class ); ?>"><?php echo esc_html( $cat_name ); ?></span>
        <?php endif; ?>

        <h1 id="evt-single-heading"><?php the_title(); ?></h1>

        <?php if ( has_excerpt() ) : ?>
        <p class="evt-single-tagline"><?php echo esc_html( get_the_excerpt() ); ?></p>
        <?php endif; ?>

        <div class="evt-single-hero-meta">
            <?php if ( $time ) : ?>
            <span><i class="far fa-clock" aria-hidden="true"></i> <?php echo esc_html( $time ); ?></span>
            <?php endif; ?>
            <?php if ( $venue ) : ?>
            <span><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo esc_html( $venue ); ?></span>
            <?php endif; ?>
            <?php if ( $capacity ) : ?>
            <span><i class="fas fa-users" aria-hidden="true"></i> <?php echo esc_html( $capacity ); ?> <?php esc_html_e( 'places', 'emc-theme' ); ?></span>
            <?php endif; ?>
        </div>

        <nav class="evt-single-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'emc-theme' ); ?>">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'emc-theme' ); ?></a>
            <span aria-hidden="true">›</span>
            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'events' ) ) ?: home_url( '/events/' ) ); ?>"><?php esc_html_e( 'Events', 'emc-theme' ); ?></a>
            <span aria-hidden="true">›</span>
            <span aria-current="page"><?php the_title(); ?></span>
        </nav>

    </div>
</section>

<?php /* ════ BODY ════ */ ?>
<section class="evt-single-body section-padding">
    <div class="container">
        <div class="evt-single-layout">

            <?php /* ── Main content ── */ ?>
            <article class="evt-single-content">

                <?php if ( $intro ) : ?>
                <p class="evt-single-intro"><?php echo nl2br( esc_html( $intro ) ); ?></p>
                <?php endif; ?>

                <div class="svc-prose">
                    <?php the_content(); ?>
                </div>

                <?php if ( ! empty( $highlights ) ) : ?>
                <div class="svc-highlights-section">
                    <h2 class="svc-highlights-heading">
                        <i class="fas fa-star" aria-hidden="true"></i>
                        <?php echo esc_html( $hl_heading ?: __( 'Event Highlights', 'emc-theme' ) ); ?>
                    </h2>
                    <div class="svc-highlights-grid">
                        <?php foreach ( $highlights as $i => $hl ) : ?>
                        <div class="svc-highlight-card scroll-reveal" style="transition-delay:<?php echo esc_attr( $i * 0.08 . 's' ); ?>">
                            <div class="svc-highlight-icon" aria-hidden="true">
                                <i class="<?php echo esc_attr( $hl['icon'] ); ?>"></i>
                            </div>
                            <div class="svc-highlight-body">
                                <strong><?php echo esc_html( $hl['title'] ); ?></strong>
                                <?php if ( $hl['desc'] ) : ?>
                                <p><?php echo esc_html( $hl['desc'] ); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php emc_render_event_registration_form( $post_id ); ?>

                <div class="svc-back-link">
                    <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'events' ) ) ?: home_url( '/events/' ) ); ?>" class="btn btn-outline">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        <?php esc_html_e( 'All Events', 'emc-theme' ); ?>
                    </a>
                </div>

            </article>

            <?php /* ── Sidebar ── */ ?>
            <aside class="evt-single-sidebar">

                <?php /* Event details card */ ?>
                <div class="evt-details-card glass-card scroll-reveal">
                    <h3><i class="fas fa-calendar-alt" aria-hidden="true"></i> <?php esc_html_e( 'Event Details', 'emc-theme' ); ?></h3>
                    <ul class="evt-details-list">
                        <?php if ( $fmt_date ) : ?>
                        <li>
                            <i class="fas fa-calendar-day" aria-hidden="true"></i>
                            <div>
                                <strong><?php esc_html_e( 'Date', 'emc-theme' ); ?></strong>
                                <span><?php echo esc_html( $fmt_date );
                                      if ( $fmt_end && $fmt_end !== $fmt_date ) echo ' &ndash; ' . esc_html( $fmt_end ); ?></span>
                            </div>
                        </li>
                        <?php endif; ?>
                        <?php if ( $time ) : ?>
                        <li>
                            <i class="fas fa-clock" aria-hidden="true"></i>
                            <div>
                                <strong><?php esc_html_e( 'Time', 'emc-theme' ); ?></strong>
                                <span><?php echo esc_html( $time ); ?></span>
                            </div>
                        </li>
                        <?php endif; ?>
                        <?php if ( $venue ) : ?>
                        <li>
                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                            <div>
                                <strong><?php esc_html_e( 'Venue', 'emc-theme' ); ?></strong>
                                <span><?php echo esc_html( $venue ); ?></span>
                            </div>
                        </li>
                        <?php endif; ?>
                        <?php if ( $capacity ) : ?>
                        <li>
                            <i class="fas fa-users" aria-hidden="true"></i>
                            <div>
                                <strong><?php esc_html_e( 'Capacity', 'emc-theme' ); ?></strong>
                                <span><?php echo esc_html( $capacity ); ?> <?php esc_html_e( 'places', 'emc-theme' ); ?></span>
                            </div>
                        </li>
                        <?php endif; ?>
                        <?php if ( $cat_name ) : ?>
                        <li>
                            <i class="fas fa-tag" aria-hidden="true"></i>
                            <div>
                                <strong><?php esc_html_e( 'Category', 'emc-theme' ); ?></strong>
                                <span><?php echo esc_html( $cat_name ); ?></span>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <?php if ( emc_event_registration_is_open( $post_id ) ) : ?>
                    <a href="#event-registration" class="btn btn-primary btn-block">
                        <i class="fas fa-ticket-alt" aria-hidden="true"></i>
                        <?php esc_html_e( 'Register for This Event', 'emc-theme' ); ?>
                    </a>
                    <?php endif; ?>

                    <?php if ( $reg_link ) : ?>
                    <a href="<?php echo esc_url( $reg_link ); ?>" class="evt-external-registration" target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e( 'Use external registration page', 'emc-theme' ); ?>
                        <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                    </a>
                    <?php endif; ?>
                </div>

                <?php /* ACF CTA card */ ?>
                <div class="svc-cta-card glass-card scroll-reveal" style="transition-delay:.1s">
                    <div class="svc-cta-icon" aria-hidden="true">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <h3><?php echo esc_html( $cta_heading ); ?></h3>
                    <p><?php echo esc_html( $cta_desc ); ?></p>
                    <a href="<?php echo esc_url( $cta_btn_url ); ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <?php echo esc_html( $cta_btn_lbl ); ?>
                    </a>
                </div>

                <?php /* Donate nudge */ ?>
                <div class="svc-donate-nudge scroll-reveal" style="transition-delay:.2s">
                    <i class="fas fa-hand-holding-heart" aria-hidden="true"></i>
                    <div>
                        <strong><?php esc_html_e( 'Support Our Events', 'emc-theme' ); ?></strong>
                        <p><?php esc_html_e( 'Your donations make events like this possible for our community.', 'emc-theme' ); ?></p>
                    </div>
                    <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'donate' ) ) ?: home_url( '/donate/' ) ); ?>" class="btn btn-outline btn-sm">
                        <?php esc_html_e( 'Donate', 'emc-theme' ); ?>
                    </a>
                </div>

            </aside>

        </div>
    </div>
</section>

<?php /* ════ RELATED EVENTS ════ */ ?>
<?php
$related = new WP_Query( array(
    'post_type'      => 'emc_event',
    'posts_per_page' => 3,
    'post__not_in'   => array( $post_id ),
    'orderby'        => 'meta_value',
    'meta_key'       => '_emc_event_date',
    'order'          => 'ASC',
    'post_status'    => 'publish',
) );
if ( $related->have_posts() ) : ?>
<section class="evt-related-section section-padding">
    <div class="container">
        <h2 class="evt-related-heading">
            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
            <?php esc_html_e( 'More Events', 'emc-theme' ); ?>
        </h2>
        <div class="evt-related-grid">
            <?php while ( $related->have_posts() ) : $related->the_post();
                $r_date  = get_post_meta( get_the_ID(), '_emc_event_date',  true );
                $r_time  = get_post_meta( get_the_ID(), '_emc_event_time',  true );
                $r_venue = get_post_meta( get_the_ID(), '_emc_event_venue', true );
                $r_cat   = get_post_meta( get_the_ID(), '_emc_event_category', true );
                $r_day   = $r_date ? date( 'd', strtotime( $r_date ) ) : '—';
                $r_month = $r_date ? strtoupper( date( 'M', strtotime( $r_date ) ) ) : '';
                $r_weekday = $r_date ? date_i18n( 'l', strtotime( $r_date ) ) : __( 'Date TBC', 'emc-theme' );
                $r_registration_url = get_permalink() . ( emc_event_registration_is_open( get_the_ID() ) ? '#event-registration' : '' );
            ?>
            <article class="evt-related-card scroll-reveal">
                <a href="<?php echo esc_url( $r_registration_url ); ?>" class="evt-related-link">
                    <div class="evt-related-img"
                         <?php if ( has_post_thumbnail() ) : ?>
                         style="background-image:url(<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'emc-card' ) ); ?>)"
                         <?php else : ?>
                         style="background:linear-gradient(135deg,var(--deep-blue),var(--primary-dark))"
                         <?php endif; ?>>
                        <div class="evt-related-date">
                            <span class="day"><?php echo esc_html( $r_day ); ?></span>
                            <span class="month"><?php echo esc_html( $r_month ); ?></span>
                        </div>
                        <?php if ( $r_cat ) : ?>
                        <span class="event-category-tag <?php echo esc_attr( $r_cat ); ?>"><?php echo esc_html( ucfirst( $r_cat ) ); ?></span>
                        <?php endif; ?>
                        <div class="event-schedule-banner">
                            <strong><?php echo esc_html( $r_weekday ); ?></strong>
                            <?php if ( $r_time ) : ?><span><i class="far fa-clock" aria-hidden="true"></i> <?php echo esc_html( $r_time ); ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="evt-related-body">
                        <h3><?php the_title(); ?></h3>
                        <?php if ( $r_time ) : ?><span><i class="far fa-clock"></i> <?php echo esc_html( $r_time ); ?></span><?php endif; ?>
                        <?php if ( $r_venue ) : ?><span><i class="fas fa-map-marker-alt"></i> <?php echo esc_html( $r_venue ); ?></span><?php endif; ?>
                    </div>
                </a>
            </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php endwhile; ?>
<?php get_footer(); ?>
