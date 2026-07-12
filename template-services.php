<?php
/**
 * Template Name: Services
 * Template Post Type: page
 *
 * EMC Theme - Services page template.
 *
 * @package emc-theme
 */

get_header();

wp_enqueue_style( 'emc-page-services', EMC_ASSETS . '/css/services.css', array( 'emc-style' ), EMC_VERSION );

$contact_url = get_permalink( get_page_by_path( 'contact' ) ) ?: home_url( '/contact/' );
$donate_url  = get_permalink( get_page_by_path( 'donate' ) ) ?: home_url( '/donate/' );

$service_fallbacks = array(
    array(
        'icon'    => 'fas fa-book-open',
        'slug'    => 'islamic-education',
        'title'   => __( 'Islamic Education', 'emc-theme' ),
        'excerpt' => __( 'Weekend Madrasah, Quran lessons, and Islamic studies for children and adults of all levels.', 'emc-theme' ),
    ),
    array(
        'icon'    => 'fas fa-ring',
        'slug'    => 'nikah-marriage',
        'title'   => __( 'Nikah Marriage', 'emc-theme' ),
        'excerpt' => __( 'Islamic marriage ceremonies conducted with care, dignity, and pre-marriage guidance.', 'emc-theme' ),
    ),
    array(
        'icon'    => 'fas fa-praying-hands',
        'slug'    => 'janaza-services',
        'title'   => __( 'Janaza Services', 'emc-theme' ),
        'excerpt' => __( 'Compassionate support for funeral prayer, family guidance, and burial coordination.', 'emc-theme' ),
    ),
    array(
        'icon'    => 'fas fa-user-tie',
        'slug'    => 'meet-an-imam',
        'title'   => __( 'Meet an Imam', 'emc-theme' ),
        'excerpt' => __( 'Private appointments for spiritual guidance, counselling, and Islamic advice.', 'emc-theme' ),
    ),
    array(
        'icon'    => 'fas fa-hand-holding-heart',
        'slug'    => 'welfare-services',
        'title'   => __( 'Welfare Services', 'emc-theme' ),
        'excerpt' => __( 'Practical help, signposting, and support for individuals and families in need.', 'emc-theme' ),
    ),
    array(
        'icon'    => 'fas fa-calendar-alt',
        'slug'    => 'general-events',
        'title'   => __( 'General Events', 'emc-theme' ),
        'excerpt' => __( 'Community gatherings, Islamic talks, open days, and family activities throughout the year.', 'emc-theme' ),
    ),
    array(
        'icon'    => 'fas fa-school',
        'slug'    => 'school-visit',
        'title'   => __( 'School Visit', 'emc-theme' ),
        'excerpt' => __( 'Welcoming local schools for mosque visits, guided tours, faith learning, and Q&A sessions.', 'emc-theme' ),
    ),
    array(
        'icon'    => 'fas fa-hands-helping',
        'slug'    => 'bereavement-support',
        'title'   => __( 'Bereavement Support', 'emc-theme' ),
        'excerpt' => __( 'Spiritual and practical support for individuals and families after the loss of a loved one.', 'emc-theme' ),
    ),
);

$services_query = new WP_Query( array(
    'post_type'      => 'emc_service',
    'posts_per_page' => 8,
    'post_status'    => 'publish',
    'meta_key'       => '_emc_service_order',
    'orderby'        => array(
        'meta_value_num' => 'ASC',
        'menu_order'     => 'ASC',
        'title'          => 'ASC',
    ),
) );
?>

<section class="services-hero">
    <div class="container">
        <div class="page-hero-content">
            <span class="badge">
                <i class="fas fa-hands-helping" aria-hidden="true"></i>
                <?php echo esc_html( emc_acf( 'svc_hero_badge', __( 'What We Offer', 'emc-theme' ) ) ); ?>
            </span>
            <h1><?php echo esc_html( emc_acf( 'svc_hero_title', __( 'Our Services', 'emc-theme' ) ) ); ?></h1>
            <p><?php echo esc_html( emc_acf( 'svc_hero_desc', __( 'Explore the services and programmes Essex Muslim Centre provides for worship, learning, family life, welfare, and the wider community.', 'emc-theme' ) ) ); ?></p>
        </div>
    </div>
</section>

<section class="services-card-section section-padding" aria-labelledby="services-list-heading">
    <div class="container">
        <div class="section-header">
            <span class="subtitle"><?php esc_html_e( 'Community Services', 'emc-theme' ); ?></span>
            <h2 id="services-list-heading"><?php esc_html_e( 'How We Can Help', 'emc-theme' ); ?></h2>
        </div>

        <div class="services-grid services-page-grid">
            <?php if ( $services_query->have_posts() ) : ?>
                <?php
                $delay = 0;
                while ( $services_query->have_posts() ) :
                    $services_query->the_post();
                    $icon = get_post_meta( get_the_ID(), '_emc_service_icon', true ) ?: 'fas fa-star-and-crescent';
                ?>
                <article class="service-card service-page-card scroll-reveal" style="transition-delay:<?php echo esc_attr( $delay . 's' ); ?>">
                    <div class="icon-wrapper" aria-hidden="true">
                        <i class="<?php echo esc_attr( $icon ); ?>"></i>
                    </div>
                    <h3><?php the_title(); ?></h3>
                    <p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
                    <div class="service-card-actions">
                        <a href="<?php the_permalink(); ?>" class="btn btn-primary btn-sm">
                            <?php esc_html_e( 'Learn More', 'emc-theme' ); ?>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( 'service', sanitize_title( get_the_title() ), $contact_url ) ); ?>" class="btn btn-outline btn-sm">
                            <?php esc_html_e( 'Enquire', 'emc-theme' ); ?>
                        </a>
                    </div>
                </article>
                <?php
                    $delay = round( $delay + 0.08, 2 );
                endwhile;
                wp_reset_postdata();
                ?>
            <?php else : ?>
                <?php foreach ( $service_fallbacks as $index => $service ) : ?>
                <article class="service-card service-page-card scroll-reveal" style="transition-delay:<?php echo esc_attr( round( $index * 0.08, 2 ) . 's' ); ?>">
                    <div class="icon-wrapper" aria-hidden="true">
                        <i class="<?php echo esc_attr( $service['icon'] ); ?>"></i>
                    </div>
                    <h3><?php echo esc_html( $service['title'] ); ?></h3>
                    <p><?php echo esc_html( $service['excerpt'] ); ?></p>
                    <div class="service-card-actions">
                        <a href="<?php echo esc_url( home_url( '/service/' . $service['slug'] . '/' ) ); ?>" class="btn btn-primary btn-sm">
                            <?php esc_html_e( 'Learn More', 'emc-theme' ); ?>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( 'service', $service['slug'], $contact_url ) ); ?>" class="btn btn-outline btn-sm">
                            <?php esc_html_e( 'Enquire', 'emc-theme' ); ?>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="services-cta">
    <div class="container">
        <div class="services-cta-inner glass-card">
            <div class="svc-cta-text">
                <h2><?php echo esc_html( emc_acf( 'svc_cta_heading', __( 'Support Our Services', 'emc-theme' ) ) ); ?></h2>
                <p><?php echo esc_html( emc_acf( 'svc_cta_desc', __( 'Your support helps Essex Muslim Centre continue serving families, students, schools, and community members in need.', 'emc-theme' ) ) ); ?></p>
            </div>
            <div class="svc-cta-actions">
                <a href="<?php echo esc_url( $donate_url ); ?>" class="btn btn-primary">
                    <i class="fas fa-hand-holding-heart" aria-hidden="true"></i>
                    <?php esc_html_e( 'Donate Now', 'emc-theme' ); ?>
                </a>
                <a href="<?php echo esc_url( $contact_url ); ?>" class="btn btn-outline">
                    <i class="fas fa-envelope" aria-hidden="true"></i>
                    <?php esc_html_e( 'Contact Us', 'emc-theme' ); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
