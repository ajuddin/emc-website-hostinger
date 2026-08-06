<?php
/** Fundraising campaign archive. @package emc-theme */
defined( 'ABSPATH' ) || exit;
$css_path = EMC_DIR . '/assets/css/campaign-manager.css';
wp_enqueue_style( 'emc-campaign-manager', EMC_ASSETS . '/css/campaign-manager.css', array( 'emc-style' ), file_exists( $css_path ) ? filemtime( $css_path ) : EMC_VERSION );
get_header();
?>
<main class="fundraising-campaign-archive">
	<section class="fundraising-archive-hero"><div class="container"><span><?php esc_html_e( 'Support our work', 'emc-theme' ); ?></span><h1><?php esc_html_e( 'Fundraising Campaigns', 'emc-theme' ); ?></h1><p><?php esc_html_e( 'See where support is needed, follow each appeal’s progress and donate securely through Stripe.', 'emc-theme' ); ?></p></div></section>
	<section class="section-padding"><div class="container">
		<?php if ( have_posts() ) : ?><div class="fundraising-campaign-grid"><?php while ( have_posts() ) : the_post();
			$id = get_the_ID(); $goal = emc_campaign_goal( $id ); $raised = emc_campaign_amount_raised( $id ); $progress = emc_campaign_progress( $id ); $status = get_post_meta( $id, '_emc_campaign_status', true ) ?: 'active';
			?><article class="fundraising-campaign-card<?php echo '1' === get_post_meta( $id, '_emc_campaign_featured', true ) ? ' is-featured' : ''; ?>">
				<a class="fundraising-campaign-card-image" href="<?php the_permalink(); ?>"><?php if ( has_post_thumbnail() ) the_post_thumbnail( 'medium_large' ); else echo '<i class="fas fa-bullseye" aria-hidden="true"></i>'; ?></a>
				<div class="fundraising-campaign-card-body"><span class="fundraising-campaign-card-status"><?php echo esc_html( ucfirst( $status ) ); ?></span><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><p><?php echo esc_html( get_the_excerpt() ); ?></p><div class="fundraising-campaign-card-track"><span style="width:<?php echo esc_attr( $progress ); ?>%"></span></div><div class="fundraising-campaign-card-totals"><strong><?php echo esc_html( '£' . number_format_i18n( $raised, 2 ) ); ?> <small><?php esc_html_e( 'raised', 'emc-theme' ); ?></small></strong><span><?php echo esc_html( $progress . '%' ); ?> <?php esc_html_e( 'of', 'emc-theme' ); ?> <?php echo esc_html( '£' . number_format_i18n( $goal, 2 ) ); ?></span></div><a class="btn btn-outline" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View Campaign', 'emc-theme' ); ?></a></div>
			</article><?php endwhile; ?></div><?php the_posts_pagination(); ?><?php else : ?><div class="fundraising-empty"><h2><?php esc_html_e( 'No campaigns are currently available.', 'emc-theme' ); ?></h2></div><?php endif; ?>
	</div></section>
</main>
<?php get_footer(); ?>
