<?php
/**
 * EMC Theme — archive.php
 * Category, tag, date, and author archives with sidebar.
 * @package emc-theme
 */

get_header();

$show_sidebar = (bool) emc_option( 'emc_blog_show_sidebar', 1 ) && is_active_sidebar( 'sidebar-blog' );
$archive_title = get_the_archive_title();
$archive_desc  = get_the_archive_description();
?>

<section class="page-hero page-hero--archive" aria-label="<?php esc_attr_e( 'Archive', 'emc-theme' ); ?>">
    <div class="container">
        <div class="page-hero-content">
            <?php if ( is_category() ) : ?>
            <span class="page-hero-badge">
                <i class="fas fa-folder-open" aria-hidden="true"></i>
                <?php esc_html_e( 'Category', 'emc-theme' ); ?>
            </span>
            <?php elseif ( is_tag() ) : ?>
            <span class="page-hero-badge">
                <i class="fas fa-tag" aria-hidden="true"></i>
                <?php esc_html_e( 'Tag', 'emc-theme' ); ?>
            </span>
            <?php elseif ( is_author() ) : ?>
            <span class="page-hero-badge">
                <i class="far fa-user" aria-hidden="true"></i>
                <?php esc_html_e( 'Author', 'emc-theme' ); ?>
            </span>
            <?php elseif ( is_date() ) : ?>
            <span class="page-hero-badge">
                <i class="far fa-calendar" aria-hidden="true"></i>
                <?php esc_html_e( 'Archive', 'emc-theme' ); ?>
            </span>
            <?php endif; ?>

            <h1><?php echo wp_kses_post( $archive_title ); ?></h1>

            <?php if ( $archive_desc ) : ?>
            <p><?php echo wp_kses_post( $archive_desc ); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
/* Category siblings filter bar — only on category archives */
if ( is_category() ) :
    $current_cat = get_queried_object();
    $sibling_cats = get_categories( array(
        'parent'     => $current_cat->parent,
        'hide_empty' => true,
        'exclude'    => $current_cat->term_id,
        'number'     => 8,
    ) );
    if ( $sibling_cats ) :
?>
<nav class="archive-filter-bar" aria-label="<?php esc_attr_e( 'Browse other categories', 'emc-theme' ); ?>">
    <div class="container">
        <ul class="filter-tabs" role="list">
            <li>
                <a href="<?php echo esc_url( get_category_link( $current_cat->term_id ) ); ?>"
                   class="filter-tab active" aria-current="page">
                    <?php echo esc_html( $current_cat->name ); ?>
                </a>
            </li>
            <?php foreach ( $sibling_cats as $sib ) : ?>
            <li>
                <a href="<?php echo esc_url( get_category_link( $sib->term_id ) ); ?>"
                   class="filter-tab">
                    <?php echo esc_html( $sib->name ); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
<?php
    endif;
endif;
?>

<section class="section-padding">
    <div class="container">

        <?php if ( have_posts() ) : ?>

        <div class="blog-layout<?php echo $show_sidebar ? ' blog-layout--sidebar' : ''; ?>">

            <div class="blog-main">
                <div class="blog-grid">
                    <?php while ( have_posts() ) : the_post(); ?>
                        <?php get_template_part( 'template-parts/components/content', 'card' ); ?>
                    <?php endwhile; ?>
                </div>

                <?php
                the_posts_pagination( array(
                    'mid_size'  => 2,
                    'prev_text' => '<i class="fas fa-arrow-left" aria-hidden="true"></i> ' . __( 'Previous', 'emc-theme' ),
                    'next_text' => __( 'Next', 'emc-theme' ) . ' <i class="fas fa-arrow-right" aria-hidden="true"></i>',
                ) );
                ?>
            </div><!-- /.blog-main -->

            <?php if ( $show_sidebar ) : ?>
            <?php get_sidebar(); ?>
            <?php endif; ?>

        </div><!-- /.blog-layout -->

        <?php else : ?>
        <?php get_template_part( 'template-parts/components/content', 'none' ); ?>
        <?php endif; ?>

    </div>
</section>

<?php get_footer();
