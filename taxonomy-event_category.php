<?php
/**
 * EMC Theme — taxonomy-event_category.php
 * Displays events filtered by a specific event_category term.
 * Reuses the same card layout as archive-emc_event.php.
 * @package emc-theme
 */

get_header();

// Enqueue events CSS on taxonomy pages
$css_path = EMC_DIR . '/assets/css/events.css';
if ( file_exists( $css_path ) ) {
    wp_enqueue_style( 'emc-tax-events', EMC_ASSETS . '/css/events.css', array( 'emc-style' ), filemtime( $css_path ) );
}

$term       = get_queried_object();
$term_name  = $term ? $term->name : '';
$today      = current_time( 'Y-m-d' );
?>

<section class="page-hero page-hero--events" aria-label="<?php echo esc_attr( $term_name ); ?>">
    <div class="container">
        <div class="page-hero-content">
            <span class="page-hero-badge">
                <i class="fas fa-tag" aria-hidden="true"></i>
                <?php esc_html_e( 'Event Category', 'emc-theme' ); ?>
            </span>
            <h1><?php echo esc_html( $term_name ); ?></h1>
            <?php if ( $term && $term->description ) : ?>
            <p><?php echo esc_html( $term->description ); ?></p>
            <?php else : ?>
            <p><?php printf( esc_html__( 'Browse all events in the %s category.', 'emc-theme' ), esc_html( $term_name ) ); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
// Category filter tabs
$event_cats = get_terms( array( 'taxonomy' => 'event_category', 'hide_empty' => true ) );
if ( $event_cats && ! is_wp_error( $event_cats ) ) :
?>
<nav class="archive-filter-bar" aria-label="<?php esc_attr_e( 'Filter events by category', 'emc-theme' ); ?>">
    <div class="container">
        <ul class="filter-tabs" role="list">
            <li>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'emc_event' ) ); ?>"
                   class="filter-tab">
                    <?php esc_html_e( 'All Events', 'emc-theme' ); ?>
                </a>
            </li>
            <?php foreach ( $event_cats as $cat ) : ?>
            <li>
                <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
                   class="filter-tab<?php echo ( $term && $term->term_id === $cat->term_id ) ? ' active' : ''; ?>"
                   <?php echo ( $term && $term->term_id === $cat->term_id ) ? 'aria-current="page"' : ''; ?>>
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
                $ev_weekday = emc_get_event_display_day( $pid );
            ?>
            <article class="event-card glass-card" id="post-<?php echo esc_attr( $pid ); ?>">
                <?php if ( has_post_thumbnail( $pid ) ) : ?>
                <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" class="event-card-img" tabindex="-1" aria-hidden="true">
                    <?php echo get_the_post_thumbnail( $pid, 'emc-thumbnail' ); ?>
                </a>
                <?php endif; ?>
                <div class="event-card-body">
                    <?php if ( $ev_cats && ! is_wp_error( $ev_cats ) ) : ?>
                    <span class="event-cat-label"><?php echo esc_html( $ev_cats[0]->name ); ?></span>
                    <?php endif; ?>
                    <?php if ( $ev_weekday || $ev_time ) : ?>
                    <div class="event-date-chip upcoming"<?php if ( $ev_date ) : ?> data-date="<?php echo esc_attr( $ev_date ); ?>"<?php endif; ?>>
                        <i class="fas fa-calendar-check" aria-hidden="true"></i>
                        <?php echo esc_html( $ev_weekday ); ?>
                        <?php if ( $ev_time ) : ?>
                        <?php if ( $ev_weekday ) : ?>&bull; <?php endif; ?><?php echo esc_html( $ev_time ); ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <h3><a href="<?php echo esc_url( get_permalink( $pid ) ); ?>"><?php echo esc_html( get_the_title( $pid ) ); ?></a></h3>
                    <?php if ( $ev_venue ) : ?>
                    <p class="event-venue">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <?php echo esc_html( $ev_venue ); ?>
                    </p>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" class="btn btn-primary btn-sm">
                        <?php esc_html_e( 'Learn More', 'emc-theme' ); ?>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
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
                $ev_time  = get_post_meta( $pid, '_emc_event_time',  true );
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
                        <?php echo esc_html( date_i18n( 'l', strtotime( $ev_date ) ) ); ?>
                        <?php if ( $ev_time ) : ?>
                        &bull; <?php echo esc_html( $ev_time ); ?>
                        <?php endif; ?>
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

        <div class="archive-no-results">
            <i class="fas fa-calendar-times" aria-hidden="true"></i>
            <h2><?php printf( esc_html__( 'No %s events found', 'emc-theme' ), esc_html( $term_name ) ); ?></h2>
            <p><?php esc_html_e( 'Check back soon — new events are added regularly.', 'emc-theme' ); ?></p>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'emc_event' ) ); ?>" class="btn btn-primary">
                <?php esc_html_e( 'View All Events', 'emc-theme' ); ?>
            </a>
        </div>

        <?php endif; ?>

    </div>
</section>

<?php get_footer(); ?>
