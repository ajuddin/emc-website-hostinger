<?php
/**
 * Template Part: Testimonials Section
 * Driven by emc_testimonial CPT. Silently hidden if no testimonials are published.
 * @package emc-theme
 */

$heading    = emc_option( 'emc_testimonials_heading',    __( 'What Our Community Says', 'emc-theme' ) );
$subheading = emc_option( 'emc_testimonials_subheading', __( 'Community Voices', 'emc-theme' ) );

$query = new WP_Query( array(
    'post_type'      => 'emc_testimonial',
    'posts_per_page' => max( 1, absint( emc_site_setting( 'emc_testimonial_count', 6 ) ) ),
    'post_status'    => 'publish',
    'orderby'        => 'rand',
) );

if ( ! $query->have_posts() ) {
    return;
}
?>
<section class="testimonials-section section-padding" id="testimonials" aria-labelledby="testimonials-heading">
    <div class="container">
        <div class="section-header">
            <span class="subtitle"><?php echo esc_html( $subheading ); ?></span>
            <h2 id="testimonials-heading"><?php echo esc_html( $heading ); ?></h2>
        </div>

        <div class="testimonials-grid">
            <?php
            $delay = 0;
            while ( $query->have_posts() ) : $query->the_post();
                $author = get_post_meta( get_the_ID(), '_emc_testimonial_author', true ) ?: get_the_title();
                $role   = get_post_meta( get_the_ID(), '_emc_testimonial_role',   true );
                $rating = (int) get_post_meta( get_the_ID(), '_emc_testimonial_rating', true ) ?: 5;
                $thumb  = get_the_post_thumbnail_url( get_the_ID(), 'emc-square' );
            ?>
            <div class="testimonial-card glass-card scroll-reveal" style="transition-delay:<?php echo esc_attr( $delay ); ?>s">
                <div class="testimonial-stars" aria-label="<?php echo esc_attr( sprintf( __( '%d out of 5 stars', 'emc-theme' ), $rating ) ); ?>">
                    <?php for ( $s = 1; $s <= 5; $s++ ) : ?>
                    <i class="<?php echo $s <= $rating ? 'fas' : 'far'; ?> fa-star" aria-hidden="true"></i>
                    <?php endfor; ?>
                </div>
                <blockquote class="testimonial-body">
                    <p><?php the_content(); ?></p>
                </blockquote>
                <div class="testimonial-author">
                    <?php if ( $thumb ) : ?>
                    <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $author ); ?>" class="testimonial-avatar" loading="lazy">
                    <?php else : ?>
                    <div class="testimonial-avatar testimonial-avatar--placeholder" aria-hidden="true">
                        <i class="fas fa-user"></i>
                    </div>
                    <?php endif; ?>
                    <div class="testimonial-author-info">
                        <strong><?php echo esc_html( $author ); ?></strong>
                        <?php if ( $role ) : ?>
                        <span><?php echo esc_html( $role ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
            $delay = round( $delay + 0.1, 1 );
            endwhile;
            wp_reset_postdata();
            ?>
        </div>
    </div>
</section>
