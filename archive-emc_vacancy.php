<?php
/**
 * EMC Theme — archive-emc_vacancy.php
 * Vacancies archive with type filter.
 * @package emc-theme
 */

get_header();
?>

<section class="page-hero page-hero--vacancies" aria-label="<?php esc_attr_e( 'Vacancies archive', 'emc-theme' ); ?>">
    <div class="container">
        <div class="page-hero-content">
            <span class="page-hero-badge">
                <i class="fas fa-briefcase" aria-hidden="true"></i>
                <?php esc_html_e( 'Join Our Team', 'emc-theme' ); ?>
            </span>
            <h1><?php esc_html_e( 'Vacancies', 'emc-theme' ); ?></h1>
            <p><?php esc_html_e( 'Explore current opportunities to work or volunteer with Essex Muslim Centre.', 'emc-theme' ); ?></p>
        </div>
    </div>
</section>

<?php
$vacancy_types = get_terms( array( 'taxonomy' => 'vacancy_type', 'hide_empty' => true ) );
if ( $vacancy_types && ! is_wp_error( $vacancy_types ) ) :
?>
<nav class="archive-filter-bar" aria-label="<?php esc_attr_e( 'Filter by vacancy type', 'emc-theme' ); ?>">
    <div class="container">
        <ul class="filter-tabs" role="list">
            <li>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'emc_vacancy' ) ); ?>"
                   class="filter-tab<?php echo ! is_tax() ? ' active' : ''; ?>"
                   <?php echo ! is_tax() ? 'aria-current="page"' : ''; ?>>
                    <?php esc_html_e( 'All Roles', 'emc-theme' ); ?>
                </a>
            </li>
            <?php foreach ( $vacancy_types as $type ) : ?>
            <li>
                <a href="<?php echo esc_url( get_term_link( $type ) ); ?>"
                   class="filter-tab<?php echo ( is_tax( 'vacancy_type', $type ) ) ? ' active' : ''; ?>"
                   <?php echo ( is_tax( 'vacancy_type', $type ) ) ? 'aria-current="page"' : ''; ?>>
                    <?php echo esc_html( $type->name ); ?>
                    <span class="filter-tab-count"><?php echo absint( $type->count ); ?></span>
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

        <div class="vacancy-listing">
            <?php while ( have_posts() ) :
                the_post();
                $types    = get_the_terms( get_the_ID(), 'vacancy_type' );
                $type_name = ( $types && ! is_wp_error( $types ) ) ? $types[0]->name : '';
            ?>
            <article class="vacancy-item glass-card" id="post-<?php the_ID(); ?>">
                <div class="vacancy-item-body">
                    <div class="vacancy-item-meta">
                        <?php if ( $type_name ) : ?>
                        <span class="vacancy-type-badge">
                            <i class="fas fa-tag" aria-hidden="true"></i>
                            <?php echo esc_html( $type_name ); ?>
                        </span>
                        <?php endif; ?>
                        <time class="vacancy-posted" datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
                            <i class="fas fa-calendar" aria-hidden="true"></i>
                            <?php /* translators: posted date */
                                  printf( esc_html__( 'Posted %s', 'emc-theme' ), esc_html( get_the_date() ) ); ?>
                        </time>
                    </div>
                    <h3 class="vacancy-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>
                    <div class="vacancy-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                    <p class="vacancy-location">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <?php echo esc_html( emc_site_setting( 'emc_default_vacancy_location', __( 'Essex Muslim Centre, Chelmsford, Essex', 'emc-theme' ) ) ); ?>
                    </p>
                </div>
                <div class="vacancy-item-actions">
                    <a href="<?php the_permalink(); ?>" class="btn btn-primary">
                        <?php esc_html_e( 'View Details', 'emc-theme' ); ?>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                    <a href="<?php echo esc_url( emc_get_volunteer_url() ); ?>"
                       class="btn btn-outline">
                        <i class="fas fa-file-alt" aria-hidden="true"></i>
                        <?php esc_html_e( 'Apply Now', 'emc-theme' ); ?>
                    </a>
                </div>
            </article>
            <?php endwhile; ?>
        </div>

        <?php the_posts_pagination( array(
            'prev_text' => '<i class="fas fa-arrow-left" aria-hidden="true"></i> ' . __( 'Previous', 'emc-theme' ),
            'next_text' => __( 'Next', 'emc-theme' ) . ' <i class="fas fa-arrow-right" aria-hidden="true"></i>',
        ) ); ?>

        <?php else : ?>
        <div class="archive-no-results">
            <i class="fas fa-briefcase" aria-hidden="true"></i>
            <h2><?php esc_html_e( 'No vacancies at this time', 'emc-theme' ); ?></h2>
            <p><?php esc_html_e( 'We don\'t have any open roles right now, but please check back regularly or send us a speculative enquiry.', 'emc-theme' ); ?></p>
            <a href="mailto:<?php echo esc_attr( emc_option( 'emc_email', 'info@essexmuslimcentre.org.uk' ) ); ?>" class="btn btn-primary">
                <i class="fas fa-envelope" aria-hidden="true"></i>
                <?php esc_html_e( 'Send Speculative Enquiry', 'emc-theme' ); ?>
            </a>
        </div>
        <?php endif; ?>

    </div>
</section>

<section class="section-padding" style="background:var(--light-bg)">
    <div class="container">
        <div class="vacancy-volunteer-cta glass-card">
            <div class="cta-icon" aria-hidden="true">
                <i class="fas fa-hands-helping"></i>
            </div>
            <div class="cta-text">
                <h2><?php esc_html_e( 'Interested in Volunteering?', 'emc-theme' ); ?></h2>
                <p><?php esc_html_e( 'Even if you don\'t see a suitable paid role, we always welcome dedicated volunteers. Get in touch to find out how you can contribute.', 'emc-theme' ); ?></p>
            </div>
            <a href="<?php echo esc_url( emc_get_volunteer_url() ); ?>" class="btn btn-primary">
                <?php esc_html_e( 'Volunteer With Us', 'emc-theme' ); ?>
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
