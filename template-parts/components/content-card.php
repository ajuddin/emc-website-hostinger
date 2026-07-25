<?php
/**
 * Template Part: Post Card
 * Used in archive.php and search.php grids.
 * @package emc-theme
 */
?>
<?php $card_categories = get_the_category(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'blog-card scroll-reveal' ); ?>>
    <?php if ( has_post_thumbnail() ) : ?>
    <a href="<?php the_permalink(); ?>" class="blog-card-img" aria-label="<?php echo esc_attr( sprintf( __( 'Read %s', 'emc-theme' ), get_the_title() ) ); ?>">
        <?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
    </a>
    <?php endif; ?>

    <div class="blog-card-body">
        <?php if ( $card_categories ) : ?>
        <a class="blog-card-cat" href="<?php echo esc_url( get_category_link( $card_categories[0]->term_id ) ); ?>">
            <?php echo esc_html( $card_categories[0]->name ); ?>
        </a>
        <?php endif; ?>

        <h2>
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>

        <div class="blog-card-meta">
            <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                <i class="far fa-calendar" aria-hidden="true"></i>
                <?php echo esc_html( get_the_date() ); ?>
            </time>
            <span>
                <i class="fas fa-clock" aria-hidden="true"></i>
                <?php echo esc_html( emc_reading_time() ); ?>
            </span>
        </div>

        <div class="blog-card-excerpt"><?php the_excerpt(); ?></div>

        <a href="<?php the_permalink(); ?>" class="btn btn-outline">
            <?php esc_html_e( 'Read Article', 'emc-theme' ); ?>
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
</article>
