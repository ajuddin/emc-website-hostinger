<?php
/**
 * Template Part: Services Preview — Phase 4 CPT-driven version.
 * Queries emc_service CPT (featured only). Falls back to static cards
 * unless the admin has set "CPT only" in the Customizer.
 * @package emc-theme
 */

$heading      = emc_option( 'emc_services_heading',    __( 'Serving the Community', 'emc-theme' ) );
$subheading   = emc_option( 'emc_services_subheading', __( 'Our Services', 'emc-theme' ) );
$cta_label    = emc_option( 'emc_services_cta_label',  __( 'View All Services', 'emc-theme' ) );
$services_url = get_permalink( get_page_by_path( 'services' ) ) ?: home_url( '/services/' );
$contact_url  = get_permalink( get_page_by_path( 'contact' ) ) ?: home_url( '/contact/' );
$services_count = max( 1, absint( emc_site_setting( 'emc_home_services_count', 8 ) ) );

// Query featured services from CPT
$cpt_query = new WP_Query( array(
    'post_type'      => 'emc_service',
    'posts_per_page' => $services_count,
    'post_status'    => 'publish',
    'meta_query'     => array(
        array(
            'key'   => '_emc_service_featured',
            'value' => '1',
        ),
    ),
    'meta_key'  => '_emc_service_order',
    'orderby'   => 'meta_value_num',
    'order'     => 'ASC',
) );

// If no featured found, fall back to latest 8
if ( ! $cpt_query->have_posts() ) {
    $cpt_query = new WP_Query( array(
        'post_type'      => 'emc_service',
        'posts_per_page' => $services_count,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order date',
        'order'          => 'ASC',
    ) );
}

$use_cpt = $cpt_query->have_posts();

// Static fallback cards — 5 core EMC services with Read More + Book links
$static_services = array(
    array(
        'icon'  => 'fas fa-book-open',
        'slug'  => 'islamic-education',
        'title' => __( 'Islamic Education', 'emc-theme' ),
        'desc'  => __( 'Weekend Madrasah, Quran lessons, and Islamic studies for children and adults of all levels.', 'emc-theme' ),
        'delay' => '0s',
    ),
    array(
        'icon'  => 'fas fa-ring',
        'slug'  => 'nikah-marriage',
        'title' => __( 'Nikah (Marriage)', 'emc-theme' ),
        'desc'  => __( 'Islamic marriage ceremonies conducted by our Imam with pre-marriage guidance and support.', 'emc-theme' ),
        'delay' => '.1s',
    ),
    array(
        'icon'  => 'fas fa-praying-hands',
        'slug'  => 'janaza-services',
        'title' => __( 'Janaza Services', 'emc-theme' ),
        'desc'  => __( 'Compassionate funeral prayer, ghusl, and burial coordination services available 24/7.', 'emc-theme' ),
        'delay' => '.2s',
    ),
    array(
        'icon'  => 'fas fa-user-tie',
        'slug'  => 'meet-an-imam',
        'title' => __( 'Meet an Imam', 'emc-theme' ),
        'desc'  => __( 'Schedule a one-to-one appointment with our Imam for spiritual guidance, counselling, or Islamic advice.', 'emc-theme' ),
        'delay' => '.3s',
    ),
    array(
        'icon'  => 'fas fa-hand-holding-heart',
        'slug'  => 'welfare-services',
        'title' => __( 'Welfare Services', 'emc-theme' ),
        'desc'  => __( 'Food bank partnerships, financial counselling, and support for families in need.', 'emc-theme' ),
        'delay' => '.4s',
    ),
    array(
        'icon'  => 'fas fa-calendar-alt',
        'slug'  => 'general-events',
        'title' => __( 'General Events', 'emc-theme' ),
        'desc'  => __( 'Community gatherings, Islamic talks, sports days, and interfaith activities throughout the year.', 'emc-theme' ),
        'delay' => '.5s',
    ),
    array(
        'icon'  => 'fas fa-school',
        'slug'  => 'school-visit',
        'title' => __( 'School Visit', 'emc-theme' ),
        'desc'  => __( 'Welcoming local schools for mosque visits, faith learning, guided tours, and Q&A sessions.', 'emc-theme' ),
        'delay' => '.6s',
    ),
    array(
        'icon'  => 'fas fa-hands-helping',
        'slug'  => 'bereavement-support',
        'title' => __( 'Bereavement Support', 'emc-theme' ),
        'desc'  => __( 'Compassionate spiritual and practical support for individuals and families after the loss of a loved one.', 'emc-theme' ),
        'delay' => '.7s',
    ),
);
?>
<section class="services services-preview section-padding" id="services" aria-labelledby="services-heading">
    <div class="container">
        <div class="section-header">
            <span class="subtitle"><?php echo esc_html( $subheading ); ?></span>
            <h2 id="services-heading"><?php echo esc_html( $heading ); ?></h2>
        </div>

        <div class="services-grid">
            <?php if ( $use_cpt ) : ?>
                <?php
                $delay = 0;
                while ( $cpt_query->have_posts() ) : $cpt_query->the_post();
                    $icon     = get_post_meta( get_the_ID(), '_emc_service_icon', true ) ?: 'fas fa-star-and-crescent';
                    $book_url = add_query_arg( 'service', sanitize_title( get_the_title() ), $contact_url );
                ?>
                <div class="service-card scroll-reveal" style="transition-delay:<?php echo esc_attr( $delay . 's' ); ?>">
                    <div class="icon-wrapper" aria-hidden="true">
                        <i class="<?php echo esc_attr( $icon ); ?>"></i>
                    </div>
                    <h3><?php the_title(); ?></h3>
                    <p><?php echo wp_trim_words( get_the_excerpt(), 20 ); ?></p>
                    <div class="service-card-actions">
                        <a href="<?php the_permalink(); ?>" class="btn btn-primary btn-sm">
                            <?php esc_html_e( 'Learn More', 'emc-theme' ); ?>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
                <?php
                $delay = round( $delay + 0.1, 1 );
                endwhile;
                wp_reset_postdata();
                ?>
            <?php elseif ( false ) : ?>
                <?php foreach ( $static_services as $s ) : ?>
                <div class="service-card scroll-reveal" style="transition-delay:<?php echo esc_attr( $s['delay'] ); ?>">
                    <div class="icon-wrapper" aria-hidden="true">
                        <i class="<?php echo esc_attr( $s['icon'] ); ?>"></i>
                    </div>
                    <h3><?php echo esc_html( $s['title'] ); ?></h3>
                    <p><?php echo esc_html( $s['desc'] ); ?></p>
                    <div class="service-card-actions">
                        <a href="<?php echo esc_url( home_url( '/service/' . $s['slug'] . '/' ) ); ?>" class="btn btn-primary btn-sm">
                            <?php esc_html_e( 'Learn More', 'emc-theme' ); ?>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="no-content-notice"><?php esc_html_e( 'No services published yet. Add services in the WordPress admin under Services.', 'emc-theme' ); ?></p>
            <?php endif; ?>
        </div>

        <div class="text-center" style="margin-top:3rem;">
            <a href="<?php echo esc_url( $services_url ); ?>" class="btn btn-primary">
                <?php echo esc_html( $cta_label ); ?>
            </a>
        </div>
    </div>
</section>
