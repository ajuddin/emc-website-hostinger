<?php
/**
 * Template Name: Events
 * Template Post Type: page
 *
 * EMC Theme — Events page template.
 * Hero content editable via ACF. Event cards are CPT-driven.
 *
 * @package emc-theme
 */

get_header();

wp_enqueue_style( 'emc-page-events', EMC_ASSETS . '/css/events.css', array( 'emc-style' ), EMC_VERSION );
wp_enqueue_style( 'emc-event-registration', EMC_ASSETS . '/css/event-registration.css', array( 'emc-page-events' ), EMC_VERSION );

$events_js_path = EMC_DIR . '/assets/js/events.js';
if ( file_exists( $events_js_path ) ) {
    wp_enqueue_script( 'emc-page-events', EMC_ASSETS . '/js/events.js', array( 'emc-script' ), filemtime( $events_js_path ), true );
}
?>

<!-- Page Hero -->
<section class="events-hero page-hero" style="background: linear-gradient(135deg, #0F172A 0%, #0E3020 100%);">
    <div class="container">
        <div class="page-hero-content">
            <span class="badge"><i class="fas fa-calendar-alt"></i> <?php echo esc_html( emc_acf( 'events_hero_badge', __( 'Community Events', 'emc-theme' ) ) ); ?></span>
            <h1><?php echo esc_html( emc_acf( 'events_hero_title', __( 'Upcoming Events', 'emc-theme' ) ) ); ?></h1>
            <p><?php echo esc_html( emc_acf( 'events_hero_desc', __( 'From Friday prayers to youth workshops, fundraising evenings to community fun days — there\'s always something happening at EMC.', 'emc-theme' ) ) ); ?></p>
        </div>
    </div>
</section>

<!-- Filter & View Toggle Bar -->
<div class="events-toolbar">
    <div class="container events-toolbar-inner">
        <div class="filter-chips" id="filter-chips">
            <button class="filter-chip active" data-filter="all"><?php echo esc_html( emc_acf( 'events_filter_all', 'All Events' ) ); ?></button>
            <button class="filter-chip" data-filter="community"><?php echo esc_html( emc_acf( 'events_filter_community', 'Community' ) ); ?></button>
            <button class="filter-chip" data-filter="youth"><?php echo esc_html( emc_acf( 'events_filter_youth', 'Youth' ) ); ?></button>
            <button class="filter-chip" data-filter="fundraising"><?php echo esc_html( emc_acf( 'events_filter_fundraising', 'Fundraising' ) ); ?></button>
            <button class="filter-chip" data-filter="religious"><?php echo esc_html( emc_acf( 'events_filter_religious', 'Religious' ) ); ?></button>
        </div>
        <div class="view-toggle">
            <button class="view-btn active" id="grid-view-btn" title="<?php esc_attr_e( 'Grid View', 'emc-theme' ); ?>"><i class="fas fa-th-large"></i></button>
            <button class="view-btn" id="list-view-btn" title="<?php esc_attr_e( 'List View', 'emc-theme' ); ?>"><i class="fas fa-list"></i></button>
        </div>
    </div>
</div>

<!-- Events Grid -->
<section class="events-section section-padding">
    <div class="container">
        <div class="events-hub-grid" id="events-container">

            <?php
            $events_query = new WP_Query( array(
                'post_type'      => 'emc_event',
    'posts_per_page' => max( 1, absint( emc_site_setting( 'emc_events_page_count', 12 ) ) ),
                'orderby'        => 'meta_value',
                'meta_key'       => '_emc_event_date',
                'order'          => 'ASC',
                'meta_query'     => array(
                    'relation' => 'OR',
                    array( 'key' => '_emc_event_date', 'compare' => 'EXISTS' ),
                    array( 'key' => '_emc_event_date', 'compare' => 'NOT EXISTS' ),
                ),
            ) );

            if ( $events_query->have_posts() ) :
                $delay = 0;
                while ( $events_query->have_posts() ) :
                    $events_query->the_post();
                    $event_date     = get_post_meta( get_the_ID(), '_emc_event_date', true );
                    $event_time     = get_post_meta( get_the_ID(), '_emc_event_time', true );
                    $event_location = get_post_meta( get_the_ID(), '_emc_event_venue', true );
                    $event_terms    = get_the_terms( get_the_ID(), 'event_category' );
                    $event_category = ( $event_terms && ! is_wp_error( $event_terms ) )
                        ? $event_terms[0]->slug
                        : ( get_post_meta( get_the_ID(), '_emc_event_category', true ) ?: 'community' );
                    $event_spots    = get_post_meta( get_the_ID(), '_emc_event_capacity', true );
                    $flyer_url      = has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'full' ) : EMC_ASSETS . '/gallery/Community Support Services/New-Muslim-600x338.jpeg';
                    $day   = $event_date ? strtoupper( date_i18n( 'D', strtotime( $event_date ) ) ) : '—';
                    $weekday = emc_get_event_display_day( get_the_ID() );
                    $registration_open = emc_event_registration_is_open( get_the_ID() );
                    $details_url       = get_permalink();
                    $registration_url  = $details_url . '#event-registration';
            ?>
            <article class="event-hub-card scroll-reveal" data-category="<?php echo esc_attr( $event_category ); ?>"<?php echo $delay ? ' style="transition-delay:' . esc_attr( $delay ) . 's"' : ''; ?>>
                <div class="event-card-link">
                    <div class="event-hub-img" data-flyer-url="<?php echo esc_url( $flyer_url ); ?>" title="<?php esc_attr_e( 'View full flyer', 'emc-theme' ); ?>" <?php if ( has_post_thumbnail() ) : ?>style="background-image:url('<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'emc-card' ) ); ?>');"<?php else : ?>style="background-image:url('<?php echo esc_url( EMC_ASSETS . '/gallery/Community Support Services/New-Muslim-600x338.jpeg' ); ?>');background-size:cover;background-position:center;"<?php endif; ?>>
                        <div class="event-hub-date"><span class="day"><?php echo esc_html( $day ); ?></span></div>
                        <span class="event-category-tag <?php echo esc_attr( $event_category ); ?>"><?php echo esc_html( ucfirst( $event_category ) ); ?></span>
                        <div class="event-schedule-banner">
                            <?php if ( $weekday ) : ?><strong><?php echo esc_html( $weekday ); ?></strong><?php endif; ?>
                            <?php if ( $event_time ) : ?><span><i class="far fa-clock" aria-hidden="true"></i> <?php echo esc_html( $event_time ); ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="event-hub-body">
                        <h3><a href="<?php echo esc_url( $details_url ); ?>"><?php the_title(); ?></a></h3>
                        <div class="event-meta-row">
                            <?php if ( $event_time ) : ?><span><i class="far fa-clock"></i> <?php echo esc_html( $event_time ); ?></span><?php endif; ?>
                            <?php if ( $event_location ) : ?><span><i class="fas fa-map-marker-alt"></i> <?php echo esc_html( $event_location ); ?></span><?php endif; ?>
                        </div>
                        <div class="event-hub-footer">
                            <span class="event-spots"><i class="fas fa-users"></i> <?php echo $event_spots ? esc_html( $event_spots ) : esc_html__( 'Free entry', 'emc-theme' ); ?></span>
                            <div class="event-hub-actions">
                                <a href="<?php echo esc_url( $details_url ); ?>" class="event-hub-action event-hub-learn">
                                    <?php esc_html_e( 'Learn More', 'emc-theme' ); ?>
                                </a>
                                <?php if ( $registration_open ) : ?>
                                <a href="<?php echo esc_url( $registration_url ); ?>" class="event-hub-action event-hub-register">
                                    <i class="fas fa-ticket-alt" aria-hidden="true"></i>
                                    <?php esc_html_e( 'Register', 'emc-theme' ); ?>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
            <?php
                    $delay = round( fmod( $delay + 0.1, 0.4 ), 1 );
                endwhile;
                wp_reset_postdata();
            elseif ( false ) :
                $demo_events = array(
                    array( 'title' => 'Community Support Services', 'day' => '15', 'month' => 'MAY', 'time' => '10:00 AM – 4:00 PM', 'loc' => 'EMC Centre',       'cat' => 'community',   'desc' => 'Supporting new Muslims and community members with guidance and resources.', 'spots' => 'Free entry',       'img' => '/gallery/Community Support Services/New-Muslim-600x338.jpeg' ),
                    array( 'title' => 'Career Advise Seminar',      'day' => '22', 'month' => 'MAY', 'time' => '6:00 PM – 8:00 PM',  'loc' => 'Main Hall',        'cat' => 'youth',       'desc' => 'Career guidance and mentorship for youth aged 14–18.',                      'spots' => '12 spots left',    'img' => '/gallery/Career Advise/WhatsApp-Image-2025-08-21-at-21.05.39-1-1-300x169.jpeg' ),
                    array( 'title' => 'Fundraising Dinner',          'day' => '01', 'month' => 'JUN', 'time' => '7:00 PM – 10:00 PM', 'loc' => 'Chelmsford Hotel', 'cat' => 'fundraising', 'desc' => 'An elegant fundraising dinner to support our building campaign.',          'spots' => 'Tickets: £30',     'img' => '/gallery/Fundraising Event/20250316_170418-300x225.jpg' ),
                    array( 'title' => 'Friday Prayer (Jumu\'ah)',    'day' => '06', 'month' => 'JUN', 'time' => '12:30 PM – 2:00 PM', 'loc' => 'Main Hall',        'cat' => 'religious',   'desc' => 'Weekly congregational Friday prayers with Khutbah.',                       'spots' => 'Open to all',      'img' => '/gallery/Friday Prayer/FPS-600x600.jpeg' ),
                    array( 'title' => 'Outdoor Activity 2024',       'day' => '14', 'month' => 'JUN', 'time' => '10:00 AM – 3:00 PM', 'loc' => 'Sports Ground',    'cat' => 'youth',       'desc' => 'Fun outdoor activities for the whole family.',                              'spots' => 'Free entry',       'img' => '/gallery/Outdoor Activity 2024/out_1-1-300x300.jpeg' ),
                    array( 'title' => 'Eid Celebration',             'day' => '20', 'month' => 'JUN', 'time' => '9:00 AM – 1:00 PM',  'loc' => 'EMC Centre',       'cat' => 'religious',   'desc' => 'Celebrate Eid with prayers, food, and community togetherness.',            'spots' => 'Walk-ins welcome', 'img' => '/gallery/Eid Celebration/eid_3-600x600.jpeg' ),
                );
                $delay = 0;
                foreach ( $demo_events as $evt ) :
            ?>
            <article class="event-hub-card scroll-reveal" data-category="<?php echo esc_attr( $evt['cat'] ); ?>"<?php echo $delay ? ' style="transition-delay:' . esc_attr( $delay ) . 's"' : ''; ?>>
                <a href="#" class="event-card-link">
                    <div class="event-hub-img" data-flyer-url="<?php echo esc_url( EMC_ASSETS . $evt['img'] ); ?>" title="<?php esc_attr_e( 'View full flyer', 'emc-theme' ); ?>" style="background-image:url('<?php echo esc_url( EMC_ASSETS . $evt['img'] ); ?>');background-size:cover;background-position:center;">
                        <div class="event-hub-date"><span class="day"><?php echo esc_html( $evt['day'] ); ?></span><span class="month"><?php echo esc_html( $evt['month'] ); ?></span></div>
                        <span class="event-category-tag <?php echo esc_attr( $evt['cat'] ); ?>"><?php echo esc_html( ucfirst( $evt['cat'] ) ); ?></span>
                    </div>
                    <div class="event-hub-body">
                        <h3><?php echo esc_html( $evt['title'] ); ?></h3>
                        <div class="event-meta-row">
                            <span><i class="far fa-clock"></i> <?php echo esc_html( $evt['time'] ); ?></span>
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo esc_html( $evt['loc'] ); ?></span>
                        </div>
                        <p><?php echo esc_html( $evt['desc'] ); ?></p>
                        <div class="event-hub-footer">
                            <span class="event-spots"><i class="fas fa-users"></i> <?php echo esc_html( $evt['spots'] ); ?></span>
                            <span class="event-cta"><?php esc_html_e( 'Register', 'emc-theme' ); ?> <i class="fas fa-arrow-right"></i></span>
                        </div>
                    </div>
                </a>
            </article>
            <?php
                $delay = round( fmod( $delay + 0.1, 0.4 ), 1 );
                endforeach;
            endif;
            ?>

        </div>

        <div class="no-results" id="no-results" style="display:none;">
            <i class="fas fa-calendar-times"></i>
            <p><?php esc_html_e( 'No events found for this category.', 'emc-theme' ); ?></p>
        </div>
    </div>
</section>

<?php get_footer(); ?>
