<?php
/**
 * Volunteer opportunities listing.
 *
 * @package emc-theme
 */

get_header();
?>
<section class="page-hero page-hero--vacancies" aria-label="<?php esc_attr_e( 'Volunteer opportunities', 'emc-theme' ); ?>">
    <div class="container"><div class="page-hero-content">
        <span class="page-hero-badge"><i class="fas fa-hands-helping" aria-hidden="true"></i> <?php esc_html_e( 'Make a Difference', 'emc-theme' ); ?></span>
        <h1><?php esc_html_e( 'Volunteer Opportunities', 'emc-theme' ); ?></h1>
        <p><?php esc_html_e( 'Explore the current ways you can give your time and skills to support Essex Muslim Centre and the wider community.', 'emc-theme' ); ?></p>
    </div></div>
</section>

<?php
$role_types = get_terms( array( 'taxonomy' => 'volunteer_role_type', 'hide_empty' => true ) );
if ( $role_types && ! is_wp_error( $role_types ) ) :
?>
<nav class="archive-filter-bar" aria-label="<?php esc_attr_e( 'Filter by volunteer role type', 'emc-theme' ); ?>">
    <div class="container"><ul class="filter-tabs" role="list">
        <li><a href="<?php echo esc_url( get_post_type_archive_link( 'emc_volunteer_role' ) ); ?>" class="filter-tab<?php echo ! is_tax() ? ' active' : ''; ?>" <?php echo ! is_tax() ? 'aria-current="page"' : ''; ?>><?php esc_html_e( 'All Roles', 'emc-theme' ); ?></a></li>
        <?php foreach ( $role_types as $role_type ) : ?>
            <li><a href="<?php echo esc_url( get_term_link( $role_type ) ); ?>" class="filter-tab<?php echo is_tax( 'volunteer_role_type', $role_type ) ? ' active' : ''; ?>" <?php echo is_tax( 'volunteer_role_type', $role_type ) ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $role_type->name ); ?> <span class="filter-tab-count"><?php echo absint( $role_type->count ); ?></span></a></li>
        <?php endforeach; ?>
    </ul></div>
</nav>
<?php endif; ?>

<section class="section-padding"><div class="container">
    <?php if ( have_posts() ) : ?>
        <div class="vacancy-listing">
        <?php while ( have_posts() ) : the_post();
            $types     = get_the_terms( get_the_ID(), 'volunteer_role_type' );
            $type_name = $types && ! is_wp_error( $types ) ? $types[0]->name : '';
            ?>
            <article class="vacancy-item glass-card" id="post-<?php the_ID(); ?>">
                <div class="vacancy-item-body">
                    <div class="vacancy-item-meta">
                        <?php if ( $type_name ) : ?><span class="vacancy-type-badge"><i class="fas fa-tag" aria-hidden="true"></i> <?php echo esc_html( $type_name ); ?></span><?php endif; ?>
                        <time class="vacancy-posted" datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>"><i class="fas fa-calendar" aria-hidden="true"></i> <?php printf( esc_html__( 'Posted %s', 'emc-theme' ), esc_html( get_the_date() ) ); ?></time>
                    </div>
                    <h2 class="vacancy-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <div class="vacancy-excerpt"><?php the_excerpt(); ?></div>
                    <p class="vacancy-location"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo esc_html( emc_site_setting( 'emc_default_vacancy_location', __( 'Essex Muslim Centre, Chelmsford, Essex', 'emc-theme' ) ) ); ?></p>
                </div>
                <div class="vacancy-item-actions">
                    <a href="<?php the_permalink(); ?>" class="btn btn-primary"><?php esc_html_e( 'View Details', 'emc-theme' ); ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    <a href="<?php echo esc_url( emc_get_volunteer_url( get_the_title() ) ); ?>" class="btn btn-outline"><i class="fas fa-paper-plane" aria-hidden="true"></i> <?php esc_html_e( 'Apply Now', 'emc-theme' ); ?></a>
                </div>
            </article>
        <?php endwhile; ?>
        </div>
        <?php the_posts_pagination( array( 'prev_text' => '<i class="fas fa-arrow-left" aria-hidden="true"></i> ' . __( 'Previous', 'emc-theme' ), 'next_text' => __( 'Next', 'emc-theme' ) . ' <i class="fas fa-arrow-right" aria-hidden="true"></i>' ) ); ?>
    <?php else : ?>
        <div class="archive-no-results"><i class="fas fa-hands-helping" aria-hidden="true"></i><h2><?php esc_html_e( 'No volunteer opportunities at this time', 'emc-theme' ); ?></h2><p><?php esc_html_e( 'Please check back soon or submit a general volunteer application.', 'emc-theme' ); ?></p><a href="<?php echo esc_url( emc_get_volunteer_url( 'General volunteering' ) ); ?>" class="btn btn-primary"><?php esc_html_e( 'Submit a General Application', 'emc-theme' ); ?></a></div>
    <?php endif; ?>
</div></section>

<section class="section-padding" style="background:var(--light-bg)"><div class="container"><div class="vacancy-volunteer-cta glass-card">
    <div class="cta-icon" aria-hidden="true"><i class="fas fa-hands-helping"></i></div>
    <div class="cta-text"><h2><?php esc_html_e( 'Ready to help?', 'emc-theme' ); ?></h2><p><?php esc_html_e( 'You can also send a general volunteer application if you are open to different opportunities.', 'emc-theme' ); ?></p></div>
    <a href="<?php echo esc_url( emc_get_volunteer_url( 'General volunteering' ) ); ?>" class="btn btn-primary"><?php esc_html_e( 'Volunteer With Us', 'emc-theme' ); ?></a>
</div></div></section>
<?php get_footer(); ?>
