<?php
/**
 * EMC Theme — archive-emc_service.php
 * Services archive with category filter tabs.
 * @package emc-theme
 */

get_header();

$heading    = emc_option( 'emc_services_heading',    __( 'Our Services', 'emc-theme' ) );
$subheading = emc_option( 'emc_services_subheading', __( 'What We Offer', 'emc-theme' ) );
?>

<section class="page-hero page-hero--services" aria-label="<?php esc_attr_e( 'Services archive', 'emc-theme' ); ?>">
    <div class="container">
        <div class="page-hero-content">
            <span class="page-hero-badge">
                <i class="fas fa-heart" aria-hidden="true"></i>
                <?php echo esc_html( $subheading ); ?>
            </span>
            <h1><?php echo esc_html( $heading ); ?></h1>
            <p><?php esc_html_e( 'Discover the range of services and programmes offered to our community.', 'emc-theme' ); ?></p>
        </div>
    </div>
</section>

<?php
$service_cats = get_terms( array( 'taxonomy' => 'service_category', 'hide_empty' => true ) );
if ( $service_cats && ! is_wp_error( $service_cats ) ) :
?>
<nav class="archive-filter-bar" aria-label="<?php esc_attr_e( 'Filter by service category', 'emc-theme' ); ?>">
    <div class="container">
        <ul class="filter-tabs" role="list">
            <li>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'emc_service' ) ); ?>"
                   class="filter-tab<?php echo ! is_tax() ? ' active' : ''; ?>"
                   <?php echo ! is_tax() ? 'aria-current="page"' : ''; ?>>
                    <?php esc_html_e( 'All Services', 'emc-theme' ); ?>
                </a>
            </li>
            <?php foreach ( $service_cats as $cat ) : ?>
            <li>
                <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
                   class="filter-tab<?php echo ( is_tax( 'service_category', $cat ) ) ? ' active' : ''; ?>"
                   <?php echo ( is_tax( 'service_category', $cat ) ) ? 'aria-current="page"' : ''; ?>>
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
        <div class="services-grid">
            <?php while ( have_posts() ) :
                the_post();
                $icon     = get_post_meta( get_the_ID(), '_emc_service_icon', true ) ?: 'fas fa-star';
                $featured = get_post_meta( get_the_ID(), '_emc_service_featured', true );
            ?>
            <article class="service-card glass-card<?php echo $featured === '1' ? ' service-card--featured' : ''; ?>"
                     id="post-<?php the_ID(); ?>">
                <div class="service-card-icon" aria-hidden="true">
                    <i class="<?php echo esc_attr( $icon ); ?>"></i>
                </div>
                <div class="service-card-body">
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <?php the_excerpt(); ?>
                    <a href="<?php the_permalink(); ?>" class="service-learn-more">
                        <?php esc_html_e( 'Learn More', 'emc-theme' ); ?>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </article>
            <?php endwhile; ?>
        </div>

        <?php the_posts_pagination( array(
            'prev_text' => '<i class="fas fa-arrow-left" aria-hidden="true"></i> ' . __( 'Previous', 'emc-theme' ),
            'next_text' => __( 'Next', 'emc-theme' ) . ' <i class="fas fa-arrow-right" aria-hidden="true"></i>',
        ) ); ?>

        <?php elseif ( false ) : ?>
        <?php
        // ── Static fallback when no service CPT posts exist ────────────────
        $fallback_services = array(
            array(
                'icon'    => 'fas fa-praying-hands',
                'title'   => 'Friday Prayers (Jumu\'ah)',
                'excerpt' => 'Two Jumu\'ah Khutbahs every Friday to accommodate our growing congregation. All are welcome.',
                'cat'     => 'Religious',
            ),
            array(
                'icon'    => 'fas fa-users',
                'title'   => 'Youth Programmes',
                'excerpt' => 'Quran classes, sports, academic mentorship, and arts for young Muslims aged 6–25.',
                'cat'     => 'Youth',
            ),
            array(
                'icon'    => 'fas fa-book-open',
                'title'   => 'Reversion to Islam',
                'excerpt' => 'A warm, judgment-free welcome and full support for those exploring or embracing the faith.',
                'cat'     => 'Community',
            ),
            array(
                'icon'    => 'fas fa-heartbeat',
                'title'   => 'Health & Wellbeing',
                'excerpt' => 'Free health checks, mental health support, and nutritional guidance from qualified professionals.',
                'cat'     => 'Wellbeing',
            ),
            array(
                'icon'    => 'fas fa-hands-helping',
                'title'   => 'Community Support',
                'excerpt' => 'Practical help, counselling, and resources for community members in need.',
                'cat'     => 'Community',
            ),
            array(
                'icon'    => 'fas fa-book-quran',
                'title'   => 'Quran & Islamic Education',
                'excerpt' => 'Weekly Tajweed classes, tafseer circles, and Islamic studies for all ages and levels.',
                'cat'     => 'Education',
            ),
        );
        ?>
        <div class="services-grid">
            <?php foreach ( $fallback_services as $svc ) : ?>
            <article class="service-card glass-card">
                <div class="service-card-icon" aria-hidden="true">
                    <i class="<?php echo esc_attr( $svc['icon'] ); ?>"></i>
                </div>
                <div class="service-card-body">
                    <span class="event-cat-label"><?php echo esc_html( $svc['cat'] ); ?></span>
                    <h3><?php echo esc_html( $svc['title'] ); ?></h3>
                    <p><?php echo esc_html( $svc['excerpt'] ); ?></p>
                    <?php
                    $services_page = get_page_by_path( 'services' );
                    $svc_url = $services_page ? get_permalink( $services_page ) : home_url( '/services/' );
                    ?>
                    <a href="<?php echo esc_url( $svc_url ); ?>" class="service-learn-more">
                        <?php esc_html_e( 'Learn More', 'emc-theme' ); ?>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="cta-strip section-padding">
    <div class="container">
        <div class="cta-strip-inner scroll-reveal">
            <div class="cta-strip-text">
                <h2><?php esc_html_e( 'Want to Know More?', 'emc-theme' ); ?></h2>
                <p><?php esc_html_e( 'Contact us to find out how our services can benefit you and your family.', 'emc-theme' ); ?></p>
            </div>
            <div class="cta-strip-actions">
                <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="btn btn-primary">
                    <i class="fas fa-envelope" aria-hidden="true"></i>
                    <?php esc_html_e( 'Get in Touch', 'emc-theme' ); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
