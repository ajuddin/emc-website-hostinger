<?php
/**
 * Template Part: Single Post Content
 * Full single post display: hero, featured image, content, share, author bio, nav, comments.
 * @package emc-theme
 */

$cats         = get_the_category();
$reading_time = emc_reading_time();
$show_sidebar = (bool) emc_option( 'emc_blog_show_sidebar', 1 ) && is_active_sidebar( 'sidebar-blog' );
$show_author  = (bool) emc_option( 'emc_blog_show_author_bio', 1 );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-post' ); ?>>

    <!-- Post Hero -->
    <section class="page-hero page-hero--blog" aria-label="<?php the_title_attribute(); ?>">
        <div class="container">
            <div class="page-hero-content">
                <?php if ( $cats ) : ?>
                <span class="page-hero-badge">
                    <a href="<?php echo esc_url( get_category_link( $cats[0]->term_id ) ); ?>"
                       style="color:inherit;text-decoration:none">
                        <?php echo esc_html( $cats[0]->name ); ?>
                    </a>
                </span>
                <?php endif; ?>

                <h1><?php the_title(); ?></h1>

                <div class="post-hero-meta">
                    <span class="post-meta-author">
                        <?php echo get_avatar( get_the_author_meta( 'ID' ), 28, '', '', array( 'class' => 'post-meta-avatar' ) ); ?>
                        <?php the_author(); ?>
                    </span>
                    <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                        <i class="far fa-calendar" aria-hidden="true"></i>
                        <?php echo get_the_date(); ?>
                    </time>
                    <span class="post-reading-time">
                        <i class="fas fa-clock" aria-hidden="true"></i>
                        <?php echo esc_html( $reading_time ); ?>
                    </span>
                    <?php if ( get_the_modified_date() !== get_the_date() ) : ?>
                    <span class="post-updated">
                        <i class="far fa-edit" aria-hidden="true"></i>
                        <?php printf(
                            /* translators: %s: modified date */
                            esc_html__( 'Updated %s', 'emc-theme' ),
                            esc_html( get_the_modified_date() )
                        ); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Post Content -->
    <section class="section-padding">
        <div class="container">
            <div class="single-layout<?php echo $show_sidebar ? ' single-layout--with-sidebar' : ''; ?>">

                <div class="single-content">

                    <?php if ( has_post_thumbnail() ) : ?>
                    <figure class="single-featured-media">
                        <?php the_post_thumbnail( 'large', array(
                            'class'         => 'single-featured-image',
                            'loading'       => 'eager',
                            'fetchpriority' => 'high',
                        ) ); ?>
                        <?php if ( get_the_post_thumbnail_caption() ) : ?>
                        <figcaption><?php echo wp_kses_post( get_the_post_thumbnail_caption() ); ?></figcaption>
                        <?php endif; ?>
                    </figure>
                    <?php endif; ?>

                    <div class="entry-content prose">
                        <?php
                        the_content(
                            sprintf(
                                wp_kses(
                                    /* translators: %s: post title */
                                    __( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'emc-theme' ),
                                    array( 'span' => array( 'class' => array() ) )
                                ),
                                wp_kses_post( get_the_title() )
                            )
                        );

                        wp_link_pages( array(
                            'before' => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'emc-theme' ) . '</span>',
                            'after'  => '</div>',
                        ) );
                        ?>
                    </div>

                    <!-- Tags & Share -->
                    <div class="post-footer-row">
                        <?php the_tags( '<div class="entry-tags"><i class="fas fa-tags" aria-hidden="true"></i> ', ', ', '</div>' ); ?>
                        <?php get_template_part( 'template-parts/blog/post-share' ); ?>
                    </div>

                    <!-- Author Bio -->
                    <?php if ( $show_author ) :
                        $author_id  = get_the_author_meta( 'ID' );
                        $author_bio = get_the_author_meta( 'description' );
                        if ( $author_bio ) :
                    ?>
                    <div class="author-bio glass-card" aria-label="<?php esc_attr_e( 'About the author', 'emc-theme' ); ?>">
                        <div class="author-bio-avatar">
                            <?php echo get_avatar( $author_id, 80, '', '', array( 'class' => 'author-avatar' ) ); ?>
                        </div>
                        <div class="author-bio-body">
                            <h3 class="author-bio-name">
                                <?php esc_html_e( 'About', 'emc-theme' ); ?>
                                <?php the_author(); ?>
                            </h3>
                            <p class="author-bio-text"><?php echo wp_kses_post( $author_bio ); ?></p>
                            <?php
                            $author_posts_url = get_author_posts_url( $author_id );
                            ?>
                            <a href="<?php echo esc_url( $author_posts_url ); ?>" class="author-bio-link">
                                <?php printf(
                                    /* translators: %s: author name */
                                    esc_html__( 'More posts by %s', 'emc-theme' ),
                                    esc_html( get_the_author() )
                                ); ?>
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                    <?php
                        endif;
                    endif; ?>

                    <!-- Post Navigation -->
                    <nav class="post-navigation" aria-label="<?php esc_attr_e( 'Post navigation', 'emc-theme' ); ?>">
                        <?php
                        the_post_navigation( array(
                            'prev_text' => '<span class="nav-subtitle"><i class="fas fa-arrow-left" aria-hidden="true"></i> ' . esc_html__( 'Previous', 'emc-theme' ) . '</span><span class="nav-title">%title</span>',
                            'next_text' => '<span class="nav-subtitle">' . esc_html__( 'Next', 'emc-theme' ) . ' <i class="fas fa-arrow-right" aria-hidden="true"></i></span><span class="nav-title">%title</span>',
                        ) );
                        ?>
                    </nav>

                    <!-- Comments -->
                    <?php
                    if ( comments_open() || get_comments_number() ) {
                        comments_template();
                    }
                    ?>

                </div><!-- /.single-content -->

                <!-- Sidebar -->
                <?php if ( $show_sidebar ) :
                    get_sidebar();
                endif; ?>

            </div><!-- /.single-layout -->
        </div>
    </section>

</article>
