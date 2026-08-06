<?php
/**
 * Template Name: Badr Wall Tiles
 * Template Post Type: page
 *
 * Dynamic public directory of approved Badr Wall names and dedications.
 *
 * @package emc-theme
 */

$badr_css_path = EMC_DIR . '/assets/css/badr-wall.css';
$badr_js_path  = EMC_DIR . '/assets/js/badr-wall.js';

wp_enqueue_style(
    'emc-badr-wall',
    EMC_ASSETS . '/css/badr-wall.css',
    array( 'emc-style' ),
    file_exists( $badr_css_path ) ? filemtime( $badr_css_path ) : EMC_VERSION
);
wp_enqueue_script(
    'emc-badr-wall',
    EMC_ASSETS . '/js/badr-wall.js',
    array(),
    file_exists( $badr_js_path ) ? filemtime( $badr_js_path ) : EMC_VERSION,
    true
);

get_header();

$campaign_url = emc_get_campaign_url();
$levels       = emc_get_badr_levels();
$total_places = array_sum( array_column( $levels, 'total' ) );
$taken_count  = array_sum( array_column( $levels, 'filled' ) );
$remaining_count = array_sum( array_column( $levels, 'remaining' ) );
$taken_percent = $total_places ? min( 100, round( ( $taken_count / $total_places ) * 100, 2 ) ) : 0;

$tile_groups = array(
    'founder' => array(
        'label'       => __( 'Founder Tiles', 'emc-theme' ),
        'badge'       => __( 'Founder', 'emc-theme' ),
        'description' => __( 'Recognition for those pledging £10,000 or more.', 'emc-theme' ),
        'class'       => 'tier-founder',
        'arabic'      => 'وقف لله',
        'inscription' => __( 'Waqf for Allah', 'emc-theme' ),
    ),
    'co-founder' => array(
        'label'       => __( 'Co-Founder Tiles', 'emc-theme' ),
        'badge'       => __( 'Co-Founder', 'emc-theme' ),
        'description' => __( 'Recognition for those pledging £5,000 or more.', 'emc-theme' ),
        'class'       => 'tier-cofunder',
        'arabic'      => 'صدقة جارية',
        'inscription' => __( 'Sadaqah Jariyah', 'emc-theme' ),
    ),
    'sponsor' => array(
        'label'       => __( 'Sponsored Tiles', 'emc-theme' ),
        'badge'       => __( 'Sponsor', 'emc-theme' ),
        'description' => __( 'Names and dedications from those sponsoring a tile.', 'emc-theme' ),
        'class'       => 'tier-sponsor',
        'arabic'      => 'صدقة جارية',
        'inscription' => __( 'Sadaqah Jariyah', 'emc-theme' ),
    ),
);

$tiles_by_group = array_fill_keys( array_keys( $tile_groups ), array() );
$tile_query     = new WP_Query( array(
    'post_type'      => 'emc_badr_tile',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
    'no_found_rows'  => true,
) );

while ( $tile_query->have_posts() ) {
    $tile_query->the_post();
    $tile_id    = get_the_ID();
    $tier       = get_post_meta( $tile_id, '_emc_badr_tier', true );
    $tier       = isset( $tile_groups[ $tier ] ) ? $tier : 'founder';
    $anonymous  = '1' === get_post_meta( $tile_id, '_emc_badr_anonymous', true );
    $name       = $anonymous ? __( 'Anonymous', 'emc-theme' ) : get_the_title();
    $dedication = get_post_meta( $tile_id, '_emc_badr_dedication', true );

    $tiles_by_group[ $tier ][] = array(
        'id'          => $tile_id,
        'name'        => $name,
        'dedication'  => $dedication,
        'anonymous'   => $anonymous,
        'number'      => absint( get_post_meta( $tile_id, '_emc_badr_tile_number', true ) ),
        'search_text' => strtolower( wp_strip_all_tags( $name . ' ' . $dedication ) ),
    );
}
wp_reset_postdata();

$published_count = array_sum( array_map( 'count', $tiles_by_group ) );
?>

<main class="badr-wall-page" id="main-content">
    <section class="badr-wall-directory" aria-labelledby="badr-wall-title">
        <div class="container badr-wall-container">
            <header class="badr-wall-control-card">
                <div class="badr-wall-control-top">
                    <a class="badr-back-link" href="<?php echo esc_url( $campaign_url ); ?>">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        <?php esc_html_e( 'Back to Badr Wall', 'emc-theme' ); ?>
                    </a>
                    <span class="badr-showing-count" id="badr-showing-count">
                        <?php
                        printf(
                            esc_html__( 'Showing %1$s of %2$s', 'emc-theme' ),
                            esc_html( number_format_i18n( $published_count ) ),
                            esc_html( number_format_i18n( $published_count ) )
                        );
                        ?>
                    </span>
                </div>

                <h1 id="badr-wall-title"><?php esc_html_e( 'Badr Wall – Named Tiles & Dedications', 'emc-theme' ); ?></h1>
                <p class="badr-wall-lead">
                    <?php esc_html_e( 'A beautiful record of those who took a place among the 313. You may appear here with your name, family name, a dedication, or as Anonymous.', 'emc-theme' ); ?>
                </p>

                <div class="badr-wall-verse" aria-label="<?php esc_attr_e( 'A legacy built for Allah', 'emc-theme' ); ?>">
                    <span lang="ar" dir="rtl">بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيم</span>
                    <small><?php esc_html_e( 'A legacy built for Allah — a reminder that remains.', 'emc-theme' ); ?></small>
                </div>

                <div class="badr-wall-summary" aria-label="<?php esc_attr_e( 'Badr Wall progress', 'emc-theme' ); ?>">
                    <span class="is-remaining"><strong><?php echo esc_html( number_format_i18n( $remaining_count ) ); ?></strong> <?php esc_html_e( 'remaining', 'emc-theme' ); ?></span>
                    <span><strong><?php echo esc_html( number_format_i18n( $taken_count ) ); ?></strong> <?php esc_html_e( 'taken', 'emc-theme' ); ?></span>
                    <span><strong><?php echo esc_html( number_format_i18n( $total_places ) ); ?></strong> <?php esc_html_e( 'total', 'emc-theme' ); ?></span>
                    <span><strong><?php echo esc_html( number_format_i18n( $published_count ) ); ?></strong> <?php esc_html_e( 'tiles listed', 'emc-theme' ); ?></span>
                </div>

                <div class="badr-wall-progress" aria-label="<?php echo esc_attr( sprintf( __( '%1$s of %2$s places taken', 'emc-theme' ), $taken_count, $total_places ) ); ?>">
                    <span style="width:<?php echo esc_attr( $taken_percent ); ?>%"></span>
                </div>

                <?php if ( $published_count ) : ?>
                <div class="badr-wall-search-row">
                    <label class="badr-wall-search">
                        <span class="screen-reader-text"><?php esc_html_e( 'Search names and dedications', 'emc-theme' ); ?></span>
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input type="search" id="badr-tile-search" placeholder="<?php esc_attr_e( 'Search a name or dedication…', 'emc-theme' ); ?>" autocomplete="off">
                    </label>
                    <button type="button" class="badr-sort-button" id="badr-sort-button" data-order="asc">
                        <?php esc_html_e( 'Sort A–Z', 'emc-theme' ); ?>
                    </button>
                    <button type="button" class="badr-clear-button" id="badr-clear-button"><?php esc_html_e( 'Clear', 'emc-theme' ); ?></button>
                </div>

                <div class="badr-wall-filters" role="group" aria-label="<?php esc_attr_e( 'Filter named tiles', 'emc-theme' ); ?>">
                    <button type="button" class="badr-filter-button active" data-filter="all" aria-pressed="true"><?php esc_html_e( 'All', 'emc-theme' ); ?></button>
                    <button type="button" class="badr-filter-button is-founder" data-filter="founder" aria-pressed="false"><?php esc_html_e( 'Founder', 'emc-theme' ); ?></button>
                    <button type="button" class="badr-filter-button is-cofounder" data-filter="co-founder" aria-pressed="false"><?php esc_html_e( 'Co-Founder', 'emc-theme' ); ?></button>
                    <button type="button" class="badr-filter-button is-sponsor" data-filter="sponsor" aria-pressed="false"><?php esc_html_e( 'Sponsor', 'emc-theme' ); ?></button>
                    <button type="button" class="badr-filter-button is-anonymous" data-filter="anonymous" aria-pressed="false"><?php esc_html_e( 'Anonymous', 'emc-theme' ); ?></button>
                </div>
                <?php endif; ?>

                <?php if ( current_user_can( 'edit_posts' ) ) : ?>
                <div class="badr-admin-tip">
                    <strong><?php esc_html_e( 'Admin:', 'emc-theme' ); ?></strong>
                    <?php esc_html_e( 'Publish approved tile records and choose their recognition level to update this directory automatically.', 'emc-theme' ); ?>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=emc_badr_tile' ) ); ?>"><?php esc_html_e( 'Manage tiles', 'emc-theme' ); ?></a>
                </div>
                <?php endif; ?>
            </header>

            <?php if ( $published_count ) : ?>
            <div class="badr-groups" id="badr-groups">
                <?php foreach ( $tile_groups as $group_id => $group ) :
                    $group_tiles = $tiles_by_group[ $group_id ];
                    if ( ! $group_tiles ) {
                        continue;
                    }
                ?>
                <section class="badr-tile-group <?php echo esc_attr( $group['class'] ); ?>" data-group="<?php echo esc_attr( $group_id ); ?>" aria-labelledby="badr-group-<?php echo esc_attr( $group_id ); ?>">
                    <header class="badr-group-header">
                        <div>
                            <h2 id="badr-group-<?php echo esc_attr( $group_id ); ?>"><?php echo esc_html( $group['label'] ); ?></h2>
                            <p><?php echo esc_html( $group['description'] ); ?></p>
                        </div>
                        <span class="badr-group-count" data-group-count><?php echo esc_html( sprintf( _n( '%s shown', '%s shown', count( $group_tiles ), 'emc-theme' ), number_format_i18n( count( $group_tiles ) ) ) ); ?></span>
                    </header>

                    <div class="badr-tile-grid">
                        <?php foreach ( $group_tiles as $tile ) : ?>
                        <article
                            class="badr-name-tile <?php echo esc_attr( $group['class'] ); ?>"
                            data-tier="<?php echo esc_attr( $group_id ); ?>"
                            data-anonymous="<?php echo $tile['anonymous'] ? '1' : '0'; ?>"
                            data-name="<?php echo esc_attr( strtolower( $tile['name'] ) ); ?>"
                            data-search="<?php echo esc_attr( $tile['search_text'] ); ?>"
                        >
                            <div class="badr-name-tile-top">
                                <span class="badr-name-tier"><?php echo esc_html( $group['badge'] ); ?></span>
                                <span class="badr-name-number">
                                    <?php echo $tile['number'] ? esc_html( sprintf( __( 'Tile #%d', 'emc-theme' ), $tile['number'] ) ) : esc_html__( 'Tile #—', 'emc-theme' ); ?>
                                </span>
                            </div>
                            <h3><?php echo esc_html( $tile['name'] ); ?></h3>
                            <p class="<?php echo $tile['dedication'] ? '' : 'badr-name-default'; ?>">
                                <?php echo esc_html( $tile['dedication'] ?: __( 'For the sake of Allah', 'emc-theme' ) ); ?>
                            </p>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>
            </div>

            <div class="badr-no-results" id="badr-no-results" hidden>
                <i class="fas fa-search" aria-hidden="true"></i>
                <strong><?php esc_html_e( 'No tiles match your search', 'emc-theme' ); ?></strong>
                <span><?php esc_html_e( 'Try another name, dedication, or category.', 'emc-theme' ); ?></span>
            </div>
            <?php else : ?>
            <div class="badr-wall-empty">
                <i class="fas fa-star-and-crescent" aria-hidden="true"></i>
                <h2><?php esc_html_e( 'The first named tiles are being prepared', 'emc-theme' ); ?></h2>
                <p><?php esc_html_e( 'Approved names and dedications will appear here automatically after they are published.', 'emc-theme' ); ?></p>
                <a href="<?php echo esc_url( $campaign_url . '#badr-membership' ); ?>" class="btn btn-primary"><?php esc_html_e( 'Choose Your Tile', 'emc-theme' ); ?></a>
            </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>
