<?php
/**
 * EMC Theme — archive-emc_event.php
 * Events archive: upcoming + past, filterable by event_category.
 * @package emc-theme
 */

get_header();

wp_enqueue_style( 'emc-page-events', EMC_ASSETS . '/css/events.css', array( 'emc-style' ), EMC_VERSION );

$heading    = emc_option( 'emc_events_heading',    __( 'Events & Programmes', 'emc-theme' ) );
$subheading = emc_option( 'emc_events_subheading', __( 'What\'s On', 'emc-theme' ) );
$today      = current_time( 'Y-m-d' );
$active_cat = isset( $_GET['event_category'] ) ? sanitize_text_field( wp_unslash( $_GET['event_category'] ) ) : '';
?>

<section class="page-hero page-hero--events" aria-label="<?php esc_attr_e( 'Events archive', 'emc-theme' ); ?>">
    <div class="container">
        <div class="page-hero-content">
            <span class="page-hero-badge">
                <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                <?php echo esc_html( $subheading ); ?>
            </span>
            <h1><?php echo esc_html( $heading ); ?></h1>
            <p><?php esc_html_e( 'Join us for events, classes, and community gatherings throughout the year.', 'emc-theme' ); ?></p>
        </div>
    </div>
</section>

<?php
$event_cats = get_terms( array( 'taxonomy' => 'event_category', 'hide_empty' => true ) );
if ( $event_cats && ! is_wp_error( $event_cats ) ) :
?>
<nav class="archive-filter-bar" aria-label="<?php esc_attr_e( 'Filter events by category', 'emc-theme' ); ?>">
    <div class="container">
        <ul class="filter-tabs" role="list">
            <li>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'emc_event' ) ); ?>"
                   class="filter-tab<?php echo ! $active_cat ? ' active' : ''; ?>"
                   <?php echo ! $active_cat ? 'aria-current="page"' : ''; ?>>
                    <?php esc_html_e( 'All Events', 'emc-theme' ); ?>
                </a>
            </li>
            <?php foreach ( $event_cats as $cat ) : ?>
            <li>
                <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
                   class="filter-tab<?php echo ( $active_cat === $cat->slug ) ? ' active' : ''; ?>"
                   <?php echo ( $active_cat === $cat->slug ) ? 'aria-current="page"' : ''; ?>>
                    <?php echo esc_html( $cat->name ); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
<?php endif; ?>

<section class="section-padding">
    <div class="container">

        <?php if ( have_posts() ) : ?>

        <?php
        /* Split posts into upcoming vs past */
        $upcoming_posts = array();
        $past_posts     = array();
        while ( have_posts() ) :
            the_post();
            $ev_date = get_post_meta( get_the_ID(), '_emc_event_date', true );
            if ( ! $ev_date || $ev_date >= $today ) {
                $upcoming_posts[] = get_the_ID();
            } else {
                $past_posts[] = get_the_ID();
            }
        endwhile;
        ?>

        <?php if ( $upcoming_posts ) : ?>
        <h2 class="archive-section-heading">
            <i class="fas fa-calendar-day" aria-hidden="true"></i>
            <?php esc_html_e( 'Upcoming Events', 'emc-theme' ); ?>
        </h2>
        <div class="event-cards-grid">
            <?php foreach ( $upcoming_posts as $pid ) :
                $ev_date  = get_post_meta( $pid, '_emc_event_date',  true );
                $ev_time  = get_post_meta( $pid, '_emc_event_time',  true );
                $ev_venue = get_post_meta( $pid, '_emc_event_venue', true );
                $ev_cats  = get_the_terms( $pid, 'event_category' );
                $ev_weekday = $ev_date ? date_i18n( 'l', strtotime( $ev_date ) ) : __( 'Date TBC', 'emc-theme' );
            ?>
            <article class="event-card glass-card" id="post-<?php echo esc_attr( $pid ); ?>">
                <?php if ( has_post_thumbnail( $pid ) ) : ?>
                <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" class="event-card-img" tabindex="-1" aria-hidden="true">
                    <?php echo get_the_post_thumbnail( $pid, 'emc-thumbnail' ); ?>
                </a>
                <?php endif; ?>
                <div class="event-card-body">
                    <div class="event-weekday-strip">
                        <strong><?php echo esc_html( $ev_weekday ); ?></strong>
                        <?php if ( $ev_time ) : ?><span><i class="far fa-clock" aria-hidden="true"></i> <?php echo esc_html( $ev_time ); ?></span><?php endif; ?>
                    </div>
                    <?php if ( $ev_cats && ! is_wp_error( $ev_cats ) ) : ?>
                    <span class="event-cat-label"><?php echo esc_html( $ev_cats[0]->name ); ?></span>
                    <?php endif; ?>
                    <?php if ( $ev_date ) : ?>
                    <time class="event-date-chip upcoming" datetime="<?php echo esc_attr( $ev_date ); ?>">
                        <i class="fas fa-calendar-check" aria-hidden="true"></i>
                        <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $ev_date ) ) ); ?>
                        <?php if ( $ev_time ) : ?>
                        &bull; <?php echo esc_html( $ev_time ); ?>
                        <?php endif; ?>
                    </time>
                    <?php endif; ?>
                    <h3><a href="<?php echo esc_url( get_permalink( $pid ) ); ?>"><?php echo esc_html( get_the_title( $pid ) ); ?></a></h3>
                    <?php $excerpt = get_the_excerpt( $pid );
                          if ( $excerpt ) echo '<p>' . esc_html( $excerpt ) . '</p>'; ?>
                    <?php if ( $ev_venue ) : ?>
                    <p class="event-venue">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <?php echo esc_html( $ev_venue ); ?>
                    </p>
                    <?php endif; ?>
                    <div class="event-card-actions">
                        <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" class="btn btn-outline btn-sm">
                            <?php esc_html_e( 'Learn More', 'emc-theme' ); ?>
                        </a>
                        <?php if ( emc_event_registration_is_open( $pid ) ) : ?>
                        <a href="<?php echo esc_url( get_permalink( $pid ) . '#event-registration' ); ?>" class="btn btn-primary btn-sm">
                            <?php esc_html_e( 'Register', 'emc-theme' ); ?>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( $past_posts ) : ?>
        <h2 class="archive-section-heading archive-section-heading--past" style="margin-top:3rem">
            <i class="fas fa-history" aria-hidden="true"></i>
            <?php esc_html_e( 'Past Events', 'emc-theme' ); ?>
        </h2>
        <div class="event-cards-grid event-cards-grid--past">
            <?php foreach ( $past_posts as $pid ) :
                $ev_date  = get_post_meta( $pid, '_emc_event_date',  true );
                $ev_venue = get_post_meta( $pid, '_emc_event_venue', true );
            ?>
            <article class="event-card event-card--past glass-card" id="post-<?php echo esc_attr( $pid ); ?>">
                <?php if ( has_post_thumbnail( $pid ) ) : ?>
                <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" class="event-card-img" tabindex="-1" aria-hidden="true">
                    <?php echo get_the_post_thumbnail( $pid, 'emc-thumbnail' ); ?>
                </a>
                <?php endif; ?>
                <div class="event-card-body">
                    <?php if ( $ev_date ) : ?>
                    <time class="event-date-chip past" datetime="<?php echo esc_attr( $ev_date ); ?>">
                        <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $ev_date ) ) ); ?>
                    </time>
                    <?php endif; ?>
                    <h3><a href="<?php echo esc_url( get_permalink( $pid ) ); ?>"><?php echo esc_html( get_the_title( $pid ) ); ?></a></h3>
                    <?php if ( $ev_venue ) : ?>
                    <p class="event-venue">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <?php echo esc_html( $ev_venue ); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php the_posts_pagination( array(
            'prev_text' => '<i class="fas fa-arrow-left" aria-hidden="true"></i> ' . __( 'Previous', 'emc-theme' ),
            'next_text' => __( 'Next', 'emc-theme' ) . ' <i class="fas fa-arrow-right" aria-hidden="true"></i>',
        ) ); ?>

        <?php else : ?>

        <?php
        // ── Static fallback when no events exist yet ──────────────────────
        $demo_events = function_exists( 'emc_demo_get_events' ) ? emc_demo_get_events() : array();
        if ( false && ! empty( $demo_events ) ) :
        ?>
        <h2 class="archive-section-heading">
            <i class="fas fa-calendar-day" aria-hidden="true"></i>
            <?php esc_html_e( 'Upcoming Events', 'emc-theme' ); ?>
        </h2>
        <div class="event-cards-grid">
            <?php foreach ( $demo_events as $evt ) :
                $ev_day   = date( 'd', strtotime( $evt['date'] ) );
                $ev_month = strtoupper( date( 'M', strtotime( $evt['date'] ) ) );
                $img_url  = EMC_ASSETS . '/gallery/' . $evt['image'];
            ?>
            <article class="event-card glass-card scroll-reveal">
                <a href="#" class="event-card-img" tabindex="-1" aria-hidden="true">
                    <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $evt['title'] ); ?>" loading="lazy">
                </a>
                <div class="event-card-body">
                    <span class="event-cat-label"><?php echo esc_html( ucfirst( $evt['category'] ) ); ?></span>
                    <time class="event-date-chip upcoming" datetime="<?php echo esc_attr( $evt['date'] ); ?>">
                        <i class="fas fa-calendar-check" aria-hidden="true"></i>
                        <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $evt['date'] ) ) ); ?>
                        &bull; <?php echo esc_html( $evt['time'] ); ?>
                    </time>
                    <h3><?php echo esc_html( $evt['title'] ); ?></h3>
                    <p><?php echo esc_html( $evt['excerpt'] ); ?></p>
                    <?php if ( ! empty( $evt['location'] ) ) : ?>
                    <p class="event-venue">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <?php echo esc_html( $evt['location'] ); ?>
                    </p>
                    <?php endif; ?>
                    <span class="btn btn-primary btn-sm">
                        <?php esc_html_e( 'Learn More', 'emc-theme' ); ?>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </span>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <?php else : ?>
        <div class="archive-no-results">
            <i class="fas fa-calendar-times" aria-hidden="true"></i>
            <h2><?php esc_html_e( 'No events found', 'emc-theme' ); ?></h2>
            <p><?php esc_html_e( 'Check back soon — new events are added regularly.', 'emc-theme' ); ?></p>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-primary">
                <?php esc_html_e( 'Back to Home', 'emc-theme' ); ?>
            </a>
        </div>
        <?php endif; ?>

        <?php endif; ?>

    </div>
</section>

<?php get_footer(); ?>
