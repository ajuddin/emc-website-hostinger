<?php
/**
 * Template Part: Related Posts
 * Shows up to N posts from the same category as the current post.
 * @package emc-theme
 */

if ( ! (bool) emc_option( 'emc_blog_show_related', 1 ) ) {
    return;
}

$count   = max( 1, (int) emc_option( 'emc_blog_related_count', 3 ) );
$related = emc_get_related_posts( get_the_ID(), $count );

if ( ! $related ) {
    return;
}
?>
<section class="related-posts-section section-padding" aria-label="<?php esc_attr_e( 'Related posts', 'emc-theme' ); ?>">
    <div class="container">
        <h2 class="section-heading related-posts-heading">
            <?php echo esc_html( emc_option( 'emc_blog_related_heading', __( 'You Might Also Like', 'emc-theme' ) ) ); ?>
        </h2>
        <div class="related-posts-grid">
            <?php foreach ( $related as $rp ) :
                setup_postdata( $rp );
                $rp_cats = get_the_terms( $rp->ID, 'category' );
                $rp_cat  = ( $rp_cats && ! is_wp_error( $rp_cats ) ) ? $rp_cats[0]->name : '';
            ?>
            <article class="related-post-card glass-card" id="post-<?php echo esc_attr( $rp->ID ); ?>">
                <?php if ( has_post_thumbnail( $rp->ID ) ) : ?>
                <a href="<?php echo esc_url( get_permalink( $rp->ID ) ); ?>" class="related-post-img" tabindex="-1" aria-hidden="true">
                    <?php echo get_the_post_thumbnail( $rp->ID, 'emc-thumbnail', array( 'loading' => 'lazy' ) ); ?>
                </a>
                <?php endif; ?>
                <div class="related-post-body">
                    <?php if ( $rp_cat ) : ?>
                    <span class="related-post-cat"><?php echo esc_html( $rp_cat ); ?></span>
                    <?php endif; ?>
                    <h3 class="related-post-title">
                        <a href="<?php echo esc_url( get_permalink( $rp->ID ) ); ?>">
                            <?php echo esc_html( get_the_title( $rp->ID ) ); ?>
                        </a>
                    </h3>
                    <div class="related-post-meta">
                        <time datetime="<?php echo esc_attr( get_the_date( 'c', $rp->ID ) ); ?>">
                            <i class="far fa-calendar" aria-hidden="true"></i>
                            <?php echo esc_html( get_the_date( '', $rp->ID ) ); ?>
                        </time>
                        <span>
                            <i class="fas fa-clock" aria-hidden="true"></i>
                            <?php echo esc_html( emc_reading_time( $rp->ID ) ); ?>
                        </span>
                    </div>
                    <p><?php echo esc_html( wp_trim_words( get_the_excerpt( $rp->ID ), 15 ) ); ?></p>
                </div>
            </article>
            <?php endforeach; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
