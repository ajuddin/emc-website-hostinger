<?php
/**
 * Single volunteer opportunity.
 *
 * @package emc-theme
 */

get_header();

while ( have_posts() ) :
    the_post();
    $types = get_the_terms( get_the_ID(), 'volunteer_role_type' );
    $type  = $types && ! is_wp_error( $types ) ? $types[0]->name : '';
    ?>
    <section class="page-hero page-hero--vacancy" aria-label="<?php the_title_attribute(); ?>">
        <div class="container"><div class="page-hero-content">
            <div class="page-hero-icon" aria-hidden="true"><i class="fas fa-hands-helping"></i></div>
            <?php if ( $type ) : ?><span class="page-hero-badge"><i class="fas fa-tag" aria-hidden="true"></i> <?php echo esc_html( $type ); ?></span><?php endif; ?>
            <h1><?php the_title(); ?></h1>
            <p class="page-hero-meta"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo esc_html( emc_site_setting( 'emc_default_vacancy_location', __( 'Essex Muslim Centre, Chelmsford, Essex', 'emc-theme' ) ) ); ?></p>
        </div></div>
    </section>

    <section class="section-padding"><div class="container"><div class="vacancy-single-layout">
        <article class="vacancy-single-content prose">
            <?php the_content(); ?>
            <div class="vacancy-apply-box glass-card">
                <h3><?php esc_html_e( 'Interested in this volunteer role?', 'emc-theme' ); ?></h3>
                <p><?php esc_html_e( 'Complete the volunteer application form and our team will contact you about the next steps.', 'emc-theme' ); ?></p>
                <a href="<?php echo esc_url( emc_get_volunteer_url( get_the_title() ) ); ?>" class="btn btn-primary"><i class="fas fa-paper-plane" aria-hidden="true"></i> <?php esc_html_e( 'Apply for This Volunteer Role', 'emc-theme' ); ?></a>
            </div>
            <div class="single-back-link"><a href="<?php echo esc_url( get_post_type_archive_link( 'emc_volunteer_role' ) ); ?>" class="btn btn-outline"><i class="fas fa-arrow-left" aria-hidden="true"></i> <?php esc_html_e( 'All Volunteer Opportunities', 'emc-theme' ); ?></a></div>
        </article>
        <aside class="vacancy-single-sidebar"><div class="vacancy-summary-card glass-card">
            <h3><?php esc_html_e( 'Role Summary', 'emc-theme' ); ?></h3>
            <ul class="vacancy-details-list">
                <li><i class="fas fa-building" aria-hidden="true"></i><div><strong><?php esc_html_e( 'Organisation', 'emc-theme' ); ?></strong><span><?php esc_html_e( 'Essex Muslim Centre', 'emc-theme' ); ?></span></div></li>
                <li><i class="fas fa-map-marker-alt" aria-hidden="true"></i><div><strong><?php esc_html_e( 'Location', 'emc-theme' ); ?></strong><span><?php esc_html_e( 'Chelmsford, Essex', 'emc-theme' ); ?></span></div></li>
                <?php if ( $type ) : ?><li><i class="fas fa-tag" aria-hidden="true"></i><div><strong><?php esc_html_e( 'Role type', 'emc-theme' ); ?></strong><span><?php echo esc_html( $type ); ?></span></div></li><?php endif; ?>
            </ul>
            <a href="<?php echo esc_url( emc_get_volunteer_url( get_the_title() ) ); ?>" class="btn btn-primary btn-block"><i class="fas fa-paper-plane" aria-hidden="true"></i> <?php esc_html_e( 'Apply Now', 'emc-theme' ); ?></a>
        </div></aside>
    </div></div></section>
    <?php
endwhile;

get_footer();
