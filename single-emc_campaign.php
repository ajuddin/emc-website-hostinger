<?php
/**
 * Public fundraising campaign page.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

$campaign_id = get_queried_object_id();
$goal        = emc_campaign_goal( $campaign_id );
$raised      = emc_campaign_amount_raised( $campaign_id );
$progress    = emc_campaign_progress( $campaign_id );
$status      = get_post_meta( $campaign_id, '_emc_campaign_status', true ) ?: 'active';
$start       = get_post_meta( $campaign_id, '_emc_campaign_start_date', true );
$end         = get_post_meta( $campaign_id, '_emc_campaign_end_date', true );
$cta         = get_post_meta( $campaign_id, '_emc_campaign_cta_label', true ) ?: __( 'Donate to This Campaign', 'emc-theme' );
$amounts     = array_filter( array_map( 'floatval', explode( ',', (string) get_post_meta( $campaign_id, '_emc_campaign_amounts', true ) ) ) );
$amounts     = $amounts ?: array( 10, 25, 50, 100 );
$payments_available = function_exists( 'emc_payments_is_available' ) && emc_payments_is_available();
$payments_ready = $payments_available && function_exists( 'emc_payment_license_is_active' ) && emc_payment_license_is_active();
if ( $payments_available ) emc_payments_enqueue_assets( 'campaign' );
$css_path    = EMC_DIR . '/assets/css/campaign-manager.css';
wp_enqueue_style( 'emc-campaign-manager', EMC_ASSETS . '/css/campaign-manager.css', array( 'emc-style' ), file_exists( $css_path ) ? filemtime( $css_path ) : EMC_VERSION );
$js_path = EMC_DIR . '/assets/js/campaign-manager.js';
wp_enqueue_script( 'emc-campaign-manager', EMC_ASSETS . '/js/campaign-manager.js', array( 'emc-script' ), file_exists( $js_path ) ? filemtime( $js_path ) : EMC_VERSION, true );
wp_localize_script( 'emc-campaign-manager', 'emcCampaignDonation', array(
	'fund' => emc_campaign_fund_name( $campaign_id ),
	'minimum' => 0.50,
	'messages' => array(
		'amount' => __( 'Please select or enter a donation amount.', 'emc-theme' ),
		'name' => __( 'Please enter your full name.', 'emc-theme' ),
		'email' => __( 'Please enter a valid email address.', 'emc-theme' ),
		'giftAid' => __( 'Address line 1 and a valid UK postcode are required when claiming Gift Aid.', 'emc-theme' ),
		'unavailable' => __( 'Card payments are not available at the moment. Please try again later.', 'emc-theme' ),
	),
) );

get_header();
?>
<main class="fundraising-campaign">
	<section class="fundraising-campaign-hero">
		<div class="container fundraising-campaign-hero-grid">
			<div class="fundraising-campaign-copy">
				<span class="fundraising-campaign-badge"><?php echo esc_html( ucfirst( $status ) ); ?> <?php esc_html_e( 'fundraising campaign', 'emc-theme' ); ?></span>
				<h1><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?><p class="fundraising-campaign-summary"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
				<div class="fundraising-campaign-progress" aria-label="<?php echo esc_attr( sprintf( __( '%d percent funded', 'emc-theme' ), $progress ) ); ?>"><div><span style="width:<?php echo esc_attr( $progress ); ?>%"></span></div><strong><?php echo esc_html( $progress . '%' ); ?> <?php esc_html_e( 'funded', 'emc-theme' ); ?></strong></div>
				<div class="fundraising-campaign-totals"><span><strong><?php echo esc_html( '£' . number_format_i18n( $raised, 2 ) ); ?></strong><?php esc_html_e( 'Raised', 'emc-theme' ); ?></span><span><strong><?php echo esc_html( '£' . number_format_i18n( $goal, 2 ) ); ?></strong><?php esc_html_e( 'Goal', 'emc-theme' ); ?></span></div>
				<?php if ( $start || $end ) : ?><p class="fundraising-campaign-dates"><?php if ( $start ) printf( esc_html__( 'Started %s', 'emc-theme' ), esc_html( wp_date( get_option( 'date_format' ), strtotime( $start ) ) ) ); ?><?php if ( $start && $end ) echo ' · '; ?><?php if ( $end ) printf( esc_html__( 'Ends %s', 'emc-theme' ), esc_html( wp_date( get_option( 'date_format' ), strtotime( $end ) ) ) ); ?></p><?php endif; ?>
				<?php if ( 'active' === $status && emc_campaign_donations_enabled( $campaign_id ) ) : ?><a class="btn btn-primary fundraising-campaign-cta" href="#campaign-donation"><i class="fas fa-heart" aria-hidden="true"></i> <?php echo esc_html( $cta ); ?></a><?php else : ?><span class="fundraising-campaign-closed"><?php esc_html_e( 'Online donations are currently closed for this campaign.', 'emc-theme' ); ?></span><?php endif; ?>
				<?php if ( $amounts && 'active' === $status ) : ?><p class="fundraising-campaign-suggestions"><?php esc_html_e( 'Suggested gifts:', 'emc-theme' ); ?> <?php echo esc_html( implode( ' · ', array_map( static function( $amount ) { return '£' . number_format_i18n( $amount ); }, $amounts ) ) ); ?></p><?php endif; ?>
			</div>
			<?php if ( has_post_thumbnail() ) : ?><figure class="fundraising-campaign-image"><?php the_post_thumbnail( 'large' ); ?></figure><?php endif; ?>
		</div>
	</section>
	<?php if ( 'active' === $status && emc_campaign_donations_enabled( $campaign_id ) ) : ?>
	<section class="fundraising-campaign-donation section-padding" id="campaign-donation">
		<div class="container"><div class="campaign-donation-card">
			<div class="campaign-donation-heading"><span><i class="fas fa-lock" aria-hidden="true"></i> <?php esc_html_e( 'Secure Stripe payment', 'emc-theme' ); ?></span><h2><?php echo esc_html( $cta ); ?></h2><p><?php printf( esc_html__( 'Your donation will be assigned directly to “%s”.', 'emc-theme' ), esc_html( emc_campaign_fund_name( $campaign_id ) ) ); ?></p></div>
			<?php if ( $payments_ready ) : ?>
			<form class="campaign-inline-donation-form" id="campaign-inline-donation-form" novalidate>
				<div class="campaign-donation-field campaign-donation-amount-field"><label><?php esc_html_e( 'Choose an amount', 'emc-theme' ); ?></label><div class="campaign-donation-amounts"><?php foreach ( $amounts as $index => $amount ) : ?><button type="button" class="campaign-amount-button<?php echo 0 === $index ? ' is-active' : ''; ?>" data-amount="<?php echo esc_attr( $amount ); ?>"><?php echo esc_html( '£' . number_format_i18n( $amount ) ); ?></button><?php endforeach; ?><button type="button" class="campaign-amount-button is-custom" data-amount=""><?php esc_html_e( 'Other', 'emc-theme' ); ?></button></div><div class="campaign-custom-amount" hidden><span>£</span><input type="number" min="0.50" step="0.01" id="campaign-donation-custom-amount" placeholder="0.00" aria-label="<?php esc_attr_e( 'Custom donation amount', 'emc-theme' ); ?>"></div></div>
				<div class="campaign-donation-fields"><div class="campaign-donation-field"><label for="campaign-donor-name"><?php esc_html_e( 'Full name *', 'emc-theme' ); ?></label><input type="text" id="campaign-donor-name" autocomplete="name" required></div><div class="campaign-donation-field"><label for="campaign-donor-email"><?php esc_html_e( 'Email address *', 'emc-theme' ); ?></label><input type="email" id="campaign-donor-email" autocomplete="email" required></div><div class="campaign-donation-field"><label for="campaign-donor-address"><?php esc_html_e( 'Address line 1', 'emc-theme' ); ?></label><input type="text" id="campaign-donor-address" autocomplete="address-line1"></div><div class="campaign-donation-field"><label for="campaign-donor-postcode"><?php esc_html_e( 'Postcode', 'emc-theme' ); ?></label><input type="text" id="campaign-donor-postcode" autocomplete="postal-code" maxlength="8"></div><div class="campaign-donation-field is-wide"><label for="campaign-donor-message"><?php esc_html_e( 'Message or dedication (optional)', 'emc-theme' ); ?></label><textarea id="campaign-donor-message" rows="3"></textarea></div></div>
				<label class="campaign-gift-aid"><input type="checkbox" id="campaign-donor-gift-aid"><span><strong><?php esc_html_e( 'Add Gift Aid', 'emc-theme' ); ?></strong><?php esc_html_e( 'I am a UK taxpayer and would like EMC to reclaim Gift Aid on this donation.', 'emc-theme' ); ?></span></label>
				<p class="campaign-donation-error" role="alert" hidden></p>
				<button type="submit" class="btn btn-primary campaign-donation-submit"><i class="fas fa-lock" aria-hidden="true"></i> <?php esc_html_e( 'Continue to Secure Card Payment', 'emc-theme' ); ?></button><p class="campaign-donation-secure"><i class="fab fa-stripe" aria-hidden="true"></i> <?php esc_html_e( 'Encrypted and processed securely by Stripe. Card details are never stored by this website.', 'emc-theme' ); ?></p>
			</form>
			<?php elseif ( $payments_available ) : ?><div class="campaign-payment-unavailable"><?php esc_html_e( 'Online campaign payments are not currently licensed. Please contact the website administrator.', 'emc-theme' ); ?></div><?php else : ?><?php emc_render_payment_plugin_required(); ?><?php endif; ?>
		</div></div>
	</section>
	<?php endif; ?>
	<section class="fundraising-campaign-story section-padding"><div class="container"><article class="fundraising-campaign-content"><?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?></article></div></section>
</main>
<?php get_footer(); ?>
