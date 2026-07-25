<?php
/**
 * EMC Theme — single-emc_vacancy.php
 * Single vacancy / job posting template.
 * @package emc-theme
 */

get_header();

while ( have_posts() ) :
    the_post();

    $types    = get_the_terms( get_the_ID(), 'vacancy_type' );
    $type_name = ( $types && ! is_wp_error( $types ) ) ? $types[0]->name : '';
    $posted   = get_the_date();
?>

<section class="page-hero page-hero--vacancy" aria-label="<?php the_title_attribute(); ?>">
    <div class="container">
        <div class="page-hero-content">
            <div class="page-hero-icon" aria-hidden="true">
                <i class="fas fa-briefcase"></i>
            </div>
            <?php if ( $type_name ) : ?>
            <span class="page-hero-badge">
                <i class="fas fa-tag" aria-hidden="true"></i>
                <?php echo esc_html( $type_name ); ?>
            </span>
            <?php endif; ?>
            <h1><?php the_title(); ?></h1>
            <p class="page-hero-meta">
                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                <?php esc_html_e( 'Essex Muslim Centre, Chelmsford', 'emc-theme' ); ?>
                &nbsp;&bull;&nbsp;
                <i class="fas fa-calendar" aria-hidden="true"></i>
                <?php /* translators: posted date */ printf( esc_html__( 'Posted %s', 'emc-theme' ), esc_html( $posted ) ); ?>
            </p>
        </div>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="vacancy-single-layout">

            <article class="vacancy-single-content prose">
                <?php the_content(); ?>

                <div class="vacancy-apply-box glass-card" role="complementary" aria-label="<?php esc_attr_e( 'How to apply', 'emc-theme' ); ?>">
                    <h3><?php esc_html_e( 'How to Apply', 'emc-theme' ); ?></h3>
                    <p><?php esc_html_e( 'Complete the application form and our team will contact you about this role.', 'emc-theme' ); ?></p>
                    <p>
                        <a href="<?php echo esc_url( emc_get_volunteer_url() ); ?>" class="btn btn-primary">
                            <i class="fas fa-file-alt" aria-hidden="true"></i>
                            <?php esc_html_e( 'Open Application Form', 'emc-theme' ); ?>
                        </a>
                    </p>
                </div>

                <div class="single-back-link">
                    <a href="<?php echo esc_url( get_post_type_archive_link( 'emc_vacancy' ) ); ?>" class="btn btn-outline">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        <?php esc_html_e( 'All Vacancies', 'emc-theme' ); ?>
                    </a>
                </div>
            </article>

            <aside class="vacancy-single-sidebar">
                <div class="vacancy-summary-card glass-card">
                    <h3><?php esc_html_e( 'Role Summary', 'emc-theme' ); ?></h3>
                    <ul class="vacancy-details-list">
                        <li>
                            <i class="fas fa-building" aria-hidden="true"></i>
                            <div>
                                <strong><?php esc_html_e( 'Organisation', 'emc-theme' ); ?></strong>
                                <span><?php esc_html_e( 'Essex Muslim Centre', 'emc-theme' ); ?></span>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                            <div>
                                <strong><?php esc_html_e( 'Location', 'emc-theme' ); ?></strong>
                                <span><?php esc_html_e( 'Chelmsford, Essex', 'emc-theme' ); ?></span>
                            </div>
                        </li>
                        <?php if ( $type_name ) : ?>
                        <li>
                            <i class="fas fa-tag" aria-hidden="true"></i>
                            <div>
                                <strong><?php esc_html_e( 'Type', 'emc-theme' ); ?></strong>
                                <span><?php echo esc_html( $type_name ); ?></span>
                            </div>
                        </li>
                        <?php endif; ?>
                        <li>
                            <i class="fas fa-calendar" aria-hidden="true"></i>
                            <div>
                                <strong><?php esc_html_e( 'Posted', 'emc-theme' ); ?></strong>
                                <span><?php echo esc_html( $posted ); ?></span>
                            </div>
                        </li>
                    </ul>
                    <a href="<?php echo esc_url( emc_get_volunteer_url() ); ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-paper-plane" aria-hidden="true"></i>
                        <?php esc_html_e( 'Apply for This Role', 'emc-theme' ); ?>
                    </a>
                </div>
            </aside>

        </div>
    </div>
</section>

<?php endwhile; ?>

<?php get_footer(); ?>
