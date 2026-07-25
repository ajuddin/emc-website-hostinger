<?php
/**
 * EMC Theme — search.php
 * Search results page with result count, refined search form, and sidebar.
 * @package emc-theme
 */

get_header();

$show_sidebar = (bool) emc_option( 'emc_blog_show_sidebar', 1 ) && is_active_sidebar( 'sidebar-blog' );
$search_query = get_search_query();
$result_count = $GLOBALS['wp_query']->found_posts;
?>

<section class="page-hero page-hero--search" aria-label="<?php esc_attr_e( 'Search results', 'emc-theme' ); ?>">
    <div class="container">
        <div class="page-hero-content">
            <span class="page-hero-badge">
                <i class="fas fa-search" aria-hidden="true"></i>
                <?php esc_html_e( 'Search', 'emc-theme' ); ?>
            </span>
            <?php if ( $search_query ) : ?>
            <h1>
                <?php printf(
                    /* translators: %s: search term */
                    esc_html__( 'Results for: %s', 'emc-theme' ),
                    '<span class="search-query-highlight">' . esc_html( $search_query ) . '</span>'
                ); ?>
            </h1>
            <?php if ( have_posts() ) : ?>
            <p class="search-result-count">
                <?php printf(
                    /* translators: %d: number of results */
                    esc_html( _n( '%d result found', '%d results found', $result_count, 'emc-theme' ) ),
                    $result_count
                ); ?>
            </p>
            <?php endif; ?>
            <?php else : ?>
            <h1><?php esc_html_e( 'Search', 'emc-theme' ); ?></h1>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="search-form-bar">
    <div class="container">
        <?php get_search_form(); ?>
    </div>
</section>

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
        <div class="search-no-results">
            <div class="archive-no-results">
                <i class="fas fa-search" aria-hidden="true"></i>
                <h2><?php esc_html_e( 'Nothing Found', 'emc-theme' ); ?></h2>
                <?php if ( $search_query ) : ?>
                <p>
                    <?php printf(
                        /* translators: %s: search term */
                        esc_html__( 'No results for &ldquo;%s&rdquo;. Try different keywords or browse our categories below.', 'emc-theme' ),
                        esc_html( $search_query )
                    ); ?>
                </p>
                <?php else : ?>
                <p><?php esc_html_e( 'Enter a search term above to find content.', 'emc-theme' ); ?></p>
                <?php endif; ?>
            </div>

            <?php
            $cats = get_categories( array( 'hide_empty' => true, 'number' => 8 ) );
            if ( $cats ) :
            ?>
            <div class="search-suggestions">
                <h3><?php esc_html_e( 'Browse by Category', 'emc-theme' ); ?></h3>
                <div class="search-suggestion-cats">
                    <?php foreach ( $cats as $cat ) : ?>
                    <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" class="filter-tab">
                        <?php echo esc_html( $cat->name ); ?>
                        <span class="filter-tab-count"><?php echo absint( $cat->count ); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</section>

<?php get_footer();
