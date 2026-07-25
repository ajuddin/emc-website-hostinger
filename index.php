<?php
/**
 * EMC Theme — index.php
 * Blog homepage / fallback template. Shows featured post + grid + sidebar.
 * @package emc-theme
 */

get_header();

$heading     = emc_option( 'emc_blog_heading',     __( 'News &amp; Updates', 'emc-theme' ) );
$subtitle    = emc_option( 'emc_blog_subtitle',    __( 'Latest News', 'emc-theme' ) );
$description = emc_option( 'emc_blog_description', __( 'Stay informed with news, announcements, and updates from Essex Muslim Centre.', 'emc-theme' ) );
$show_sidebar = (bool) emc_option( 'emc_blog_show_sidebar', 1 ) && is_active_sidebar( 'sidebar-blog' );
?>

<section class="page-hero page-hero--blog-index" aria-label="<?php esc_attr_e( 'Blog', 'emc-theme' ); ?>">
    <div class="container">
        <div class="page-hero-content">
            <span class="page-hero-badge">
                <i class="fas fa-newspaper" aria-hidden="true"></i>
                <?php echo esc_html( $subtitle ); ?>
            </span>
            <h1><?php echo wp_kses_post( $heading ); ?></h1>
            <?php if ( $description ) : ?>
            <p><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <?php if ( have_posts() ) : ?>

        <div class="blog-layout<?php echo $show_sidebar ? ' blog-layout--sidebar' : ''; ?>">

            <div class="blog-main">

                <?php
                $post_count = 0;
                while ( have_posts() ) :
                    the_post();
                    $post_count++;

                    if ( 1 === $post_count && is_paged() === false ) :
                        /* First post on first page gets a featured hero card */
                        $fp_cats = get_the_category();
                        $fp_cat  = $fp_cats ? $fp_cats[0]->name : '';
                ?>
                <article class="blog-featured-post glass-card" id="post-<?php the_ID(); ?>">
                    <?php if ( has_post_thumbnail() ) : ?>
                    <a href="<?php the_permalink(); ?>" class="blog-featured-img" tabindex="-1" aria-hidden="true">
                        <?php the_post_thumbnail( 'emc-hero', array( 'loading' => 'eager' ) ); ?>
                    </a>
                    <?php endif; ?>
                    <div class="blog-featured-body">
                        <?php if ( $fp_cat ) : ?>
                        <span class="blog-featured-cat"><?php echo esc_html( $fp_cat ); ?></span>
                        <?php endif; ?>
                        <h2 class="blog-featured-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>
                        <div class="blog-featured-meta">
                            <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                <i class="far fa-calendar" aria-hidden="true"></i>
                                <?php echo esc_html( get_the_date() ); ?>
                            </time>
                            <span>
                                <i class="far fa-user" aria-hidden="true"></i>
                                <?php the_author(); ?>
                            </span>
                            <span>
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                <?php echo esc_html( emc_reading_time() ); ?>
                            </span>
                        </div>
                        <div class="blog-featured-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                        <a href="<?php the_permalink(); ?>" class="btn btn-primary">
                            <?php esc_html_e( 'Read Article', 'emc-theme' ); ?>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </article>

                <div class="blog-grid">
                <?php
                    else :
                        get_template_part( 'template-parts/components/content', 'card' );
                    endif;
                endwhile;
                ?>
                </div><!-- /.blog-grid -->

            </div><!-- /.blog-main -->

            <?php if ( $show_sidebar ) : ?>
            <?php get_sidebar(); ?>
            <?php endif; ?>

        </div><!-- /.blog-layout -->

        <?php
        the_posts_pagination( array(
            'mid_size'  => 2,
            'prev_text' => '<i class="fas fa-arrow-left" aria-hidden="true"></i> ' . __( 'Previous', 'emc-theme' ),
            'next_text' => __( 'Next', 'emc-theme' ) . ' <i class="fas fa-arrow-right" aria-hidden="true"></i>',
        ) );
        ?>

        <?php else : ?>
        <?php get_template_part( 'template-parts/components/content', 'none' ); ?>
        <?php endif; ?>

    </div>
</section>

<?php get_footer();
