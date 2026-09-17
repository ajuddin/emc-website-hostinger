<?php
/**
 * EMC Theme — single-emc_service.php
 * Premium single service page with full ACF editable content.
 *
 * ACF Fields (post type: emc_service):
 *   svc_single_badge           — hero badge text
 *   svc_single_hero_image      — hero background image (array)
 *   svc_single_intro           — intro paragraph
 *   svc_single_highlights_heading — section heading for highlights
 *   svc_highlight_{n}_icon     — FontAwesome class (n = 1–6)
 *   svc_highlight_{n}_title    — highlight card title
 *   svc_highlight_{n}_desc     — highlight card description
 *   svc_single_cta_heading     — sidebar CTA heading
 *   svc_single_cta_desc        — sidebar CTA description
 *   svc_single_cta_btn_label   — sidebar button text
 *   svc_single_cta_btn_url     — sidebar button URL
 *
 * @package emc-theme
 */

get_header();

while ( have_posts() ) :
    the_post();

    $post_id   = get_the_ID();
    $icon      = get_post_meta( $post_id, '_emc_service_icon', true ) ?: 'fas fa-star-and-crescent';
    $cats      = get_the_terms( $post_id, 'service_category' );
    $cat_name  = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';

    // ── ACF fields (graceful fallback if ACF inactive) ─────────────────────
    $has_acf   = function_exists( 'get_field' );

    $badge        = $has_acf ? get_field( 'svc_single_badge' )      : '';
    $hero_img     = $has_acf ? get_field( 'svc_single_hero_image' ) : null;
    $intro        = $has_acf ? get_field( 'svc_single_intro' )      : '';
    $hl_heading   = $has_acf ? get_field( 'svc_single_highlights_heading' ) : __( 'Key Highlights', 'emc-theme' );
    $cta_heading  = $has_acf ? get_field( 'svc_single_cta_heading' )   : __( 'Get Involved', 'emc-theme' );
    $cta_desc     = $has_acf ? get_field( 'svc_single_cta_desc' )      : __( 'Interested in this service? Contact us to learn more or register your interest.', 'emc-theme' );
    $cta_btn_lbl  = $has_acf ? get_field( 'svc_single_cta_btn_label' ) : __( 'Contact Us', 'emc-theme' );
    $cta_btn_url  = $has_acf ? get_field( 'svc_single_cta_btn_url' )   : home_url( '/contact/' );

    // Fallback badge to category name
    if ( ! $badge ) {
        $badge = $cat_name ?: __( 'Our Services', 'emc-theme' );
    }
    if ( ! $cta_btn_url ) {
        $cta_btn_url = home_url( '/contact/' );
    }

    // Collect highlights (up to 6)
    $highlights = array();
    if ( $has_acf ) {
        for ( $n = 1; $n <= 6; $n++ ) {
            $hl_title = get_field( "svc_highlight_{$n}_title" );
            if ( $hl_title ) {
                $highlights[] = array(
                    'icon'  => get_field( "svc_highlight_{$n}_icon" ) ?: 'fas fa-check-circle',
                    'title' => $hl_title,
                    'desc'  => get_field( "svc_highlight_{$n}_desc" ) ?: '',
                );
            }
        }
    }

    // Hero background image
    $hero_bg_style = '';
    if ( $hero_img && ! empty( $hero_img['url'] ) ) {
        $hero_bg_style = 'style="background-image:url(' . esc_url( $hero_img['url'] ) . ')"';
    } elseif ( has_post_thumbnail() ) {
        $thumb_url     = get_the_post_thumbnail_url( $post_id, 'emc-hero' );
        $hero_bg_style = 'style="background-image:url(' . esc_url( $thumb_url ) . ')"';
    }
?>

<?php /* ═══════════════════════════════════════
   HERO
   ═══════════════════════════════════════ */ ?>
<section class="svc-single-hero<?php echo $hero_bg_style ? ' has-bg' : ''; ?>" <?php echo $hero_bg_style; ?> aria-labelledby="svc-single-heading">
    <div class="svc-single-hero-overlay"></div>
    <div class="container svc-single-hero-inner">

        <div class="svc-single-hero-icon" aria-hidden="true">
            <i class="<?php echo esc_attr( $icon ); ?>"></i>
        </div>

        <?php if ( $badge ) : ?>
        <span class="svc-single-badge"><?php echo esc_html( $badge ); ?></span>
        <?php endif; ?>

        <h1 id="svc-single-heading"><?php the_title(); ?></h1>

        <?php if ( has_excerpt() ) : ?>
        <p class="svc-single-tagline"><?php echo esc_html( get_the_excerpt() ); ?></p>
        <?php endif; ?>

        <nav class="svc-single-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'emc-theme' ); ?>">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'emc-theme' ); ?></a>
            <span aria-hidden="true">›</span>
            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'services' ) ) ?: home_url( '/services/' ) ); ?>"><?php esc_html_e( 'Services', 'emc-theme' ); ?></a>
            <span aria-hidden="true">›</span>
            <span aria-current="page"><?php the_title(); ?></span>
        </nav>

    </div>
</section>

<?php /* ═══════════════════════════════════════
   BODY — 2 columns
   ═══════════════════════════════════════ */ ?>
<section class="svc-single-body section-padding">
    <div class="container">
        <div class="svc-single-layout">

            <?php /* ── LEFT: main content ── */ ?>
            <article class="svc-single-content">

                <?php if ( $intro ) : ?>
                <p class="svc-single-intro"><?php echo nl2br( esc_html( $intro ) ); ?></p>
                <?php endif; ?>

                <?php /* WP Editor content (seeded HTML) */ ?>
                <div class="svc-prose">
                    <?php the_content(); ?>
                </div>

                <?php /* ── Key Highlights grid (ACF-driven) ── */ ?>
                <?php if ( ! empty( $highlights ) ) : ?>
                <div class="svc-highlights-section">
                    <h2 class="svc-highlights-heading">
                        <i class="fas fa-star" aria-hidden="true"></i>
                        <?php echo esc_html( $hl_heading ?: __( 'Key Highlights', 'emc-theme' ) ); ?>
                    </h2>
                    <div class="svc-highlights-grid">
                        <?php foreach ( $highlights as $i => $hl ) : ?>
                        <div class="svc-highlight-card scroll-reveal" style="transition-delay:<?php echo esc_attr( ( $i * 0.08 ) . 's' ); ?>">
                            <div class="svc-highlight-icon" aria-hidden="true">
                                <i class="<?php echo esc_attr( $hl['icon'] ); ?>"></i>
                            </div>
                            <div class="svc-highlight-body">
                                <strong><?php echo esc_html( $hl['title'] ); ?></strong>
                                <?php if ( $hl['desc'] ) : ?>
                                <p><?php echo esc_html( $hl['desc'] ); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php /* Back link */ ?>
                <div class="svc-back-link">
                    <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'services' ) ) ?: home_url( '/services/' ) ); ?>" class="btn btn-outline">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        <?php esc_html_e( 'All Services', 'emc-theme' ); ?>
                    </a>
                </div>

            </article>

            <?php /* ── RIGHT: sidebar ── */ ?>
            <aside class="svc-single-sidebar">

                <?php /* CTA card */ ?>
                <div class="svc-cta-card glass-card scroll-reveal">
                    <div class="svc-cta-icon" aria-hidden="true">
                        <i class="<?php echo esc_attr( $icon ); ?>"></i>
                    </div>
                    <h3><?php echo esc_html( $cta_heading ); ?></h3>
                    <p><?php echo esc_html( $cta_desc ); ?></p>
                    <a href="<?php echo esc_url( $cta_btn_url ); ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <?php echo esc_html( $cta_btn_lbl ); ?>
                    </a>
                </div>

                <?php /* Related services */ ?>
                <?php
                $related = new WP_Query( array(
                    'post_type'      => 'emc_service',
                    'posts_per_page' => 5,
                    'post__not_in'   => array( $post_id ),
                    'orderby'        => 'menu_order',
                    'order'          => 'ASC',
                ) );
                if ( $related->have_posts() ) :
                ?>
                <div class="svc-related-card glass-card scroll-reveal" style="transition-delay:.1s">
                    <h3>
                        <i class="fas fa-th-large" aria-hidden="true"></i>
                        <?php esc_html_e( 'Other Services', 'emc-theme' ); ?>
                    </h3>
                    <ul class="svc-related-list">
                        <?php while ( $related->have_posts() ) : $related->the_post();
                            $r_icon = get_post_meta( get_the_ID(), '_emc_service_icon', true ) ?: 'fas fa-star';
                        ?>
                        <li>
                            <a href="<?php the_permalink(); ?>">
                                <span class="svc-related-icon"><i class="<?php echo esc_attr( $r_icon ); ?>" aria-hidden="true"></i></span>
                                <span><?php the_title(); ?></span>
                                <i class="fas fa-chevron-right svc-related-arrow" aria-hidden="true"></i>
                            </a>
                        </li>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php /* Quick donate nudge */ ?>
                <div class="svc-donate-nudge scroll-reveal" style="transition-delay:.2s">
                    <i class="fas fa-hand-holding-heart" aria-hidden="true"></i>
                    <div>
                        <strong><?php esc_html_e( 'Support This Service', 'emc-theme' ); ?></strong>
                        <p><?php esc_html_e( 'Your donation keeps our community programmes running.', 'emc-theme' ); ?></p>
                    </div>
                    <a href="<?php echo esc_url( emc_get_donate_url() ); ?>" class="btn btn-outline btn-sm">
                        <?php esc_html_e( 'Donate', 'emc-theme' ); ?>
                    </a>
                </div>

            </aside>

        </div>
    </div>
</section>

<?php endwhile; ?>
<?php get_footer(); ?>
