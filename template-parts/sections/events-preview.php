<?php
/**
 * Template Part: Featured Events Preview
 * @package emc-theme
 */

$events_url = get_permalink( get_page_by_path( 'events' ) ) ?: home_url( '/events/' );
$events_count = max( 1, absint( emc_option( 'emc_events_count', 2 ) ) );
$selected_raw = trim( emc_option( 'emc_events_selected', '' ) );
$selected_ids = array();

if ( $selected_raw ) {
    $tokens = array_filter( array_map( 'trim', explode( ',', $selected_raw ) ) );
    foreach ( $tokens as $token ) {
        if ( is_numeric( $token ) ) {
            $selected_ids[] = absint( $token );
            continue;
        }

        $event = get_page_by_path( sanitize_title( $token ), OBJECT, 'emc_event' );
        if ( $event ) {
            $selected_ids[] = $event->ID;
        }
    }
    $selected_ids = array_values( array_unique( array_filter( $selected_ids ) ) );
}

$query_args = array(
    'post_type'      => 'emc_event',
    'posts_per_page' => $events_count,
    'post_status'    => 'publish',
);

if ( $selected_ids ) {
    $query_args['post__in'] = $selected_ids;
    $query_args['orderby']  = 'post__in';
} else {
    $query_args['meta_key']   = '_emc_event_date';
    $query_args['orderby']    = 'meta_value';
    $query_args['order']      = 'ASC';
    $query_args['meta_query'] = array(
        array(
            'key'     => '_emc_event_date',
            'value'   => date( 'Y-m-d' ),
            'compare' => '>=',
            'type'    => 'DATE',
        ),
    );
}

$events_query = new WP_Query( $query_args );
?>
<section class="events section-padding" id="events" style="background:var(--white);" aria-labelledby="events-heading">
    <div class="container">
        <div class="section-header left">
            <span class="subtitle"><?php echo esc_html( emc_option( 'emc_events_subheading', __( 'Upcoming', 'emc-theme' ) ) ); ?></span>
            <h2 id="events-heading"><?php echo esc_html( emc_option( 'emc_events_heading', __( 'Featured Events', 'emc-theme' ) ) ); ?></h2>
        </div>

        <div class="events-grid">
            <?php if ( $events_query->have_posts() ) : ?>
                <?php
                $delay = 0;
                while ( $events_query->have_posts() ) : $events_query->the_post();
                    $event_date  = get_post_meta( get_the_ID(), '_emc_event_date', true );
                    $event_time  = get_post_meta( get_the_ID(), '_emc_event_time', true );
                    $event_venue = get_post_meta( get_the_ID(), '_emc_event_venue', true );
                    $thumb       = get_the_post_thumbnail_url( get_the_ID(), 'emc-card' );
                    /* Recurring events resolve to their next occurrence. */
                    $display_ts  = emc_get_event_display_timestamp( get_the_ID() );
                    $day         = $display_ts ? strtoupper( function_exists( 'wp_date' ) ? wp_date( 'D', $display_ts ) : date_i18n( 'D', $display_ts ) ) : '';
                    $formatted_date = emc_get_event_display_day( get_the_ID() );
                    $formatted_time = $event_time ?: __( 'To be confirmed', 'emc-theme' );
                    $formatted_venue = $event_venue ?: __( 'Essex Muslim Centre', 'emc-theme' );
                    $event_url        = get_permalink();
                    $registration_open = function_exists( 'emc_event_registration_is_open' )
                        && emc_event_registration_is_open( get_the_ID() );
                ?>
                <div class="event-card scroll-reveal" style="transition-delay:<?php echo esc_attr( $delay . 's' ); ?>">
                    <div class="event-img"<?php if ( $thumb ) : ?> style="background-image:url('<?php echo esc_url( $thumb ); ?>')"<?php endif; ?>>
                        <?php if ( $day ) : ?>
                        <div class="event-date">
                            <span style="display:block;font-size:var(--step-2);font-weight:700;color:var(--primary-green);line-height:1;"><?php echo esc_html( $day ); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="featured-event-body">
                        <h3><?php the_title(); ?></h3>

                        <dl class="featured-event-facts">
                            <div>
                                <dt><i class="far fa-calendar-alt" aria-hidden="true"></i> <?php esc_html_e( 'Date', 'emc-theme' ); ?></dt>
                                <dd><?php echo esc_html( $formatted_date ); ?></dd>
                            </div>
                            <div>
                                <dt><i class="far fa-clock" aria-hidden="true"></i> <?php esc_html_e( 'Time', 'emc-theme' ); ?></dt>
                                <dd><?php echo esc_html( $formatted_time ); ?></dd>
                            </div>
                            <div class="featured-event-location">
                                <dt><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php esc_html_e( 'Location', 'emc-theme' ); ?></dt>
                                <dd><?php echo esc_html( $formatted_venue ); ?></dd>
                            </div>
                        </dl>

                        <div class="featured-event-actions">
                            <a href="<?php echo esc_url( $event_url ); ?>" class="btn btn-outline">
                            <?php esc_html_e( 'Learn More', 'emc-theme' ); ?>
                            </a>
                            <?php if ( $registration_open ) : ?>
                            <a href="<?php echo esc_url( $event_url . '#event-registration' ); ?>" class="btn btn-primary">
                                <i class="fas fa-ticket-alt" aria-hidden="true"></i>
                                <?php esc_html_e( 'Register', 'emc-theme' ); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
                $delay += 0.1;
                endwhile;
                wp_reset_postdata();
                ?>
            <?php elseif ( false ) : ?>
                <!-- Static fallback cards until events are published -->
                <div class="event-card scroll-reveal">
                    <div class="event-img" style="background-image:url('<?php echo esc_url( EMC_ASSETS . '/gallery/Community Support Services/New-Muslim-600x338.jpeg' ); ?>');">
                        <div class="event-date">
                            <span style="display:block;font-size:var(--step-2);font-weight:700;color:var(--primary-green);line-height:1;">15</span>
                            <span style="display:block;font-size:var(--step--2);font-weight:600;color:var(--text-muted);">MAY</span>
                        </div>
                    </div>
                    <div style="padding:2rem;">
                        <h3 style="margin-bottom:1rem;"><?php esc_html_e( 'Community Support Services', 'emc-theme' ); ?></h3>
                        <div style="display:flex;gap:1rem;margin-bottom:1rem;font-size:var(--step--2);color:var(--text-muted);">
                            <span><i class="far fa-clock" style="color:var(--accent-gold);" aria-hidden="true"></i> 10:00 AM – 4:00 PM</span>
                            <span><i class="fas fa-map-marker-alt" style="color:var(--accent-gold);" aria-hidden="true"></i> EMC Centre</span>
                        </div>
                        <p style="color:var(--text-muted);font-size:var(--step--1);margin-bottom:1.5rem;">
                            <?php esc_html_e( 'Supporting new Muslims and community members with guidance, resources, and a welcoming environment.', 'emc-theme' ); ?>
                        </p>
                        <a href="<?php echo esc_url( $events_url ); ?>" class="btn btn-outline" style="padding:0.5rem 1rem;font-size:var(--step--2);">
                            <?php esc_html_e( 'View Events', 'emc-theme' ); ?>
                        </a>
                    </div>
                </div>
                <div class="event-card scroll-reveal" style="transition-delay:0.1s;">
                    <div class="event-img" style="background-image:url('<?php echo esc_url( EMC_ASSETS . '/gallery/Career Advise/WhatsApp-Image-2025-08-21-at-21.05.39-1-1-300x169.jpeg' ); ?>');">
                        <div class="event-date">
                            <span style="display:block;font-size:var(--step-2);font-weight:700;color:var(--primary-green);line-height:1;">22</span>
                            <span style="display:block;font-size:var(--step--2);font-weight:600;color:var(--text-muted);">MAY</span>
                        </div>
                    </div>
                    <div style="padding:2rem;">
                        <h3 style="margin-bottom:1rem;"><?php esc_html_e( 'Career Advise Seminar', 'emc-theme' ); ?></h3>
                        <div style="display:flex;gap:1rem;margin-bottom:1rem;font-size:var(--step--2);color:var(--text-muted);">
                            <span><i class="far fa-clock" style="color:var(--accent-gold);" aria-hidden="true"></i> 6:00 PM – 8:00 PM</span>
                            <span><i class="fas fa-map-marker-alt" style="color:var(--accent-gold);" aria-hidden="true"></i> Main Hall</span>
                        </div>
                        <p style="color:var(--text-muted);font-size:var(--step--1);margin-bottom:1.5rem;">
                            <?php esc_html_e( 'Career guidance and mentorship for youth aged 14–18. Guest speakers and workshops.', 'emc-theme' ); ?>
                        </p>
                        <a href="<?php echo esc_url( $events_url ); ?>" class="btn btn-outline" style="padding:0.5rem 1rem;font-size:var(--step--2);">
                            <?php esc_html_e( 'View Events', 'emc-theme' ); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center" style="margin-top:4rem;">
            <a href="<?php echo esc_url( $events_url ); ?>" class="btn btn-primary">
                <?php echo esc_html( emc_option( 'emc_events_cta_label', __( 'View All Events', 'emc-theme' ) ) ); ?>
            </a>
        </div>
    </div>
</section>
