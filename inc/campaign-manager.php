<?php
/**
 * Fundraising campaign administration and reporting.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/** Return the Stripe/donation fund label assigned to a campaign. */
function emc_campaign_fund_name( $campaign_id ) {
	$fund = trim( (string) get_post_meta( $campaign_id, '_emc_campaign_fund', true ) );
	return $fund ?: get_the_title( $campaign_id );
}

/** Total confirmed online payments assigned to this campaign's fund. */
function emc_campaign_online_raised( $campaign_id ) {
	$fund     = emc_campaign_fund_name( $campaign_id );
	$total    = 0.0;
	$payments = get_option( 'emc_donations_log', array() );

	foreach ( is_array( $payments ) ? $payments : array() as $payment ) {
		if ( ! is_array( $payment ) || 0 !== strcasecmp( trim( (string) ( $payment['fund'] ?? '' ) ), $fund ) ) {
			continue;
		}
		$total += (float) preg_replace( '/[^0-9.\-]/', '', (string) ( $payment['amount'] ?? 0 ) );
	}
	return max( 0, $total );
}

/** Combined confirmed online and manually recorded offline amount. */
function emc_campaign_amount_raised( $campaign_id ) {
	$offline = (float) get_post_meta( $campaign_id, '_emc_campaign_offline_raised', true );
	return emc_campaign_online_raised( $campaign_id ) + max( 0, $offline );
}

function emc_campaign_goal( $campaign_id ) {
	return max( 0, (float) get_post_meta( $campaign_id, '_emc_campaign_goal', true ) );
}

function emc_campaign_progress( $campaign_id ) {
	$goal = emc_campaign_goal( $campaign_id );
	return $goal > 0 ? min( 100, round( ( emc_campaign_amount_raised( $campaign_id ) / $goal ) * 100 ) ) : 0;
}

/** New campaigns accept donations unless explicitly disabled. */
function emc_campaign_donations_enabled( $campaign_id ) {
	$value = get_post_meta( $campaign_id, '_emc_campaign_donations_enabled', true );
	return '' === $value || '1' === $value;
}

function emc_campaign_register_meta_boxes() {
	add_meta_box( 'emc_campaign_fundraising', __( 'Fundraising Settings', 'emc-theme' ), 'emc_campaign_fundraising_meta_box', 'emc_campaign', 'normal', 'high' );
	add_meta_box( 'emc_campaign_live_summary', __( 'Live Campaign Summary', 'emc-theme' ), 'emc_campaign_summary_meta_box', 'emc_campaign', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'emc_campaign_register_meta_boxes' );

/** Keep the campaign editor focused on its fundraising controls and story. */
add_filter( 'use_block_editor_for_post_type', function( $use_block_editor, $post_type ) {
	return 'emc_campaign' === $post_type ? false : $use_block_editor;
}, 10, 2 );

function emc_campaign_fundraising_meta_box( $post ) {
	wp_nonce_field( 'emc_campaign_fundraising_save', 'emc_campaign_fundraising_nonce' );
	$status       = get_post_meta( $post->ID, '_emc_campaign_status', true ) ?: 'active';
	$goal         = get_post_meta( $post->ID, '_emc_campaign_goal', true );
	$offline      = get_post_meta( $post->ID, '_emc_campaign_offline_raised', true );
	$fund         = get_post_meta( $post->ID, '_emc_campaign_fund', true );
	$start        = get_post_meta( $post->ID, '_emc_campaign_start_date', true );
	$end          = get_post_meta( $post->ID, '_emc_campaign_end_date', true );
	$amounts      = get_post_meta( $post->ID, '_emc_campaign_amounts', true ) ?: '10,25,50,100';
	$cta          = get_post_meta( $post->ID, '_emc_campaign_cta_label', true ) ?: __( 'Donate to This Campaign', 'emc-theme' );
	$enabled_meta = get_post_meta( $post->ID, '_emc_campaign_donations_enabled', true );
	$enabled      = '' === $enabled_meta || '1' === $enabled_meta;
	?>
	<div class="emc-campaign-admin-intro">
		<strong><?php esc_html_e( 'How campaign totals work', 'emc-theme' ); ?></strong>
		<p><?php esc_html_e( 'Confirmed Stripe payments are counted automatically when their Donation Fund exactly matches the fund name below. Add cash, bank transfer, cheque or other offline income separately.', 'emc-theme' ); ?></p>
	</div>
	<table class="form-table emc-campaign-settings-table" role="presentation">
		<tr><th><label for="emc_campaign_status"><?php esc_html_e( 'Campaign status', 'emc-theme' ); ?></label></th><td><select id="emc_campaign_status" name="emc_campaign_status"><option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'emc-theme' ); ?></option><option value="paused" <?php selected( $status, 'paused' ); ?>><?php esc_html_e( 'Paused', 'emc-theme' ); ?></option><option value="completed" <?php selected( $status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'emc-theme' ); ?></option></select></td></tr>
		<tr><th><label for="emc_campaign_goal"><?php esc_html_e( 'Fundraising goal (£)', 'emc-theme' ); ?></label></th><td><input type="number" min="0" step="0.01" id="emc_campaign_goal" name="emc_campaign_goal" value="<?php echo esc_attr( $goal ); ?>" class="regular-text" placeholder="100000"><p class="description"><?php esc_html_e( 'The public progress bar and percentage are calculated from this amount.', 'emc-theme' ); ?></p></td></tr>
		<tr><th><label for="emc_campaign_fund"><?php esc_html_e( 'Stripe donation fund name', 'emc-theme' ); ?></label></th><td><input type="text" id="emc_campaign_fund" name="emc_campaign_fund" value="<?php echo esc_attr( $fund ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_the_title( $post ) ?: __( 'Campaign name', 'emc-theme' ) ); ?>"><p class="description"><?php esc_html_e( 'This name is sent through the existing Stripe donation form and used to match payments. Leave blank to use the campaign title.', 'emc-theme' ); ?></p></td></tr>
		<tr><th><label for="emc_campaign_offline_raised"><?php esc_html_e( 'Offline amount raised (£)', 'emc-theme' ); ?></label></th><td><input type="number" min="0" step="0.01" id="emc_campaign_offline_raised" name="emc_campaign_offline_raised" value="<?php echo esc_attr( $offline ); ?>" class="regular-text" placeholder="0"><p class="description"><?php esc_html_e( 'Enter the combined total received by cash, bank transfer, cheque or another offline method.', 'emc-theme' ); ?></p></td></tr>
		<tr><th><label for="emc_campaign_start_date"><?php esc_html_e( 'Start and end dates', 'emc-theme' ); ?></label></th><td><input type="date" id="emc_campaign_start_date" name="emc_campaign_start_date" value="<?php echo esc_attr( $start ); ?>"> <span aria-hidden="true">—</span> <input type="date" id="emc_campaign_end_date" name="emc_campaign_end_date" value="<?php echo esc_attr( $end ); ?>"><p class="description"><?php esc_html_e( 'The end date is optional for ongoing appeals.', 'emc-theme' ); ?></p></td></tr>
		<tr><th><label for="emc_campaign_amounts"><?php esc_html_e( 'Suggested amounts (£)', 'emc-theme' ); ?></label></th><td><input type="text" id="emc_campaign_amounts" name="emc_campaign_amounts" value="<?php echo esc_attr( $amounts ); ?>" class="regular-text"><p class="description"><?php esc_html_e( 'Comma-separated values, for example: 10,25,50,100.', 'emc-theme' ); ?></p></td></tr>
		<tr><th><label for="emc_campaign_cta_label"><?php esc_html_e( 'Donate button wording', 'emc-theme' ); ?></label></th><td><input type="text" id="emc_campaign_cta_label" name="emc_campaign_cta_label" value="<?php echo esc_attr( $cta ); ?>" class="regular-text"></td></tr>
		<tr><th><?php esc_html_e( 'Online donations', 'emc-theme' ); ?></th><td><label><input type="checkbox" name="emc_campaign_donations_enabled" value="1" <?php checked( $enabled ); ?>> <?php esc_html_e( 'Enable the Stripe donation button for this campaign', 'emc-theme' ); ?></label></td></tr>
		<tr><th><?php esc_html_e( 'Featured campaign', 'emc-theme' ); ?></th><td><label><input type="checkbox" name="emc_campaign_featured" value="1" <?php checked( get_post_meta( $post->ID, '_emc_campaign_featured', true ), '1' ); ?>> <?php esc_html_e( 'Highlight this campaign in campaign listings', 'emc-theme' ); ?></label></td></tr>
	</table>
	<?php
}

function emc_campaign_summary_meta_box( $post ) {
	$goal = emc_campaign_goal( $post->ID );
	$online = emc_campaign_online_raised( $post->ID );
	$offline = max( 0, (float) get_post_meta( $post->ID, '_emc_campaign_offline_raised', true ) );
	$total = $online + $offline;
	$progress = emc_campaign_progress( $post->ID );
	?>
	<div class="emc-campaign-summary"><div class="emc-campaign-summary-total"><span><?php esc_html_e( 'Total raised', 'emc-theme' ); ?></span><strong><?php echo esc_html( '£' . number_format_i18n( $total, 2 ) ); ?></strong></div><div class="emc-campaign-admin-track"><span style="width:<?php echo esc_attr( $progress ); ?>%"></span></div><p><strong><?php echo esc_html( $progress . '%' ); ?></strong> <?php esc_html_e( 'of goal', 'emc-theme' ); ?> <?php echo esc_html( '£' . number_format_i18n( $goal, 2 ) ); ?></p><ul><li><span><?php esc_html_e( 'Confirmed Stripe', 'emc-theme' ); ?></span><strong><?php echo esc_html( '£' . number_format_i18n( $online, 2 ) ); ?></strong></li><li><span><?php esc_html_e( 'Offline recorded', 'emc-theme' ); ?></span><strong><?php echo esc_html( '£' . number_format_i18n( $offline, 2 ) ); ?></strong></li></ul><p class="description"><?php printf( esc_html__( 'Matched fund: %s', 'emc-theme' ), esc_html( emc_campaign_fund_name( $post->ID ) ?: '—' ) ); ?></p></div>
	<?php
}

function emc_campaign_save_settings( $post_id ) {
	if ( ! isset( $_POST['emc_campaign_fundraising_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['emc_campaign_fundraising_nonce'] ) ), 'emc_campaign_fundraising_save' ) || ! current_user_can( 'edit_post', $post_id ) ) return;
	$status = sanitize_key( wp_unslash( $_POST['emc_campaign_status'] ?? 'active' ) );
	update_post_meta( $post_id, '_emc_campaign_status', in_array( $status, array( 'active', 'paused', 'completed' ), true ) ? $status : 'active' );
	$goal = max( 0, (float) wp_unslash( $_POST['emc_campaign_goal'] ?? 0 ) );
	update_post_meta( $post_id, '_emc_campaign_goal', $goal );
	update_post_meta( $post_id, '_emc_target', $goal );
	update_post_meta( $post_id, '_emc_campaign_offline_raised', max( 0, (float) wp_unslash( $_POST['emc_campaign_offline_raised'] ?? 0 ) ) );
	update_post_meta( $post_id, '_emc_campaign_fund', sanitize_text_field( wp_unslash( $_POST['emc_campaign_fund'] ?? '' ) ) );
	update_post_meta( $post_id, '_emc_campaign_start_date', sanitize_text_field( wp_unslash( $_POST['emc_campaign_start_date'] ?? '' ) ) );
	update_post_meta( $post_id, '_emc_campaign_end_date', sanitize_text_field( wp_unslash( $_POST['emc_campaign_end_date'] ?? '' ) ) );
	$amounts = array_filter( array_map( 'floatval', preg_split( '/\s*,\s*/', sanitize_text_field( wp_unslash( $_POST['emc_campaign_amounts'] ?? '' ) ) ) ) );
	update_post_meta( $post_id, '_emc_campaign_amounts', implode( ',', array_map( static function( $amount ) { return max( 1, $amount ); }, $amounts ) ) );
	update_post_meta( $post_id, '_emc_campaign_cta_label', sanitize_text_field( wp_unslash( $_POST['emc_campaign_cta_label'] ?? '' ) ) );
	update_post_meta( $post_id, '_emc_campaign_donations_enabled', isset( $_POST['emc_campaign_donations_enabled'] ) ? '1' : '0' );
	update_post_meta( $post_id, '_emc_campaign_featured', isset( $_POST['emc_campaign_featured'] ) ? '1' : '0' );
	update_post_meta( $post_id, '_emc_raised', emc_campaign_amount_raised( $post_id ) );
}
add_action( 'save_post_emc_campaign', 'emc_campaign_save_settings' );

add_filter( 'enter_title_here', function( $title, $post ) { return $post && 'emc_campaign' === $post->post_type ? __( 'Enter the fundraising campaign name', 'emc-theme' ) : $title; }, 10, 2 );

add_filter( 'manage_emc_campaign_posts_columns', function() {
	return array( 'cb' => '<input type="checkbox">', 'title' => __( 'Campaign', 'emc-theme' ), 'campaign_progress' => __( 'Fundraising progress', 'emc-theme' ), 'campaign_fund' => __( 'Stripe fund name', 'emc-theme' ), 'campaign_dates' => __( 'Dates', 'emc-theme' ), 'campaign_status' => __( 'Status', 'emc-theme' ), 'date' => __( 'Published', 'emc-theme' ) );
} );

add_action( 'manage_emc_campaign_posts_custom_column', function( $column, $post_id ) {
	if ( 'campaign_progress' === $column ) {
		$raised = emc_campaign_amount_raised( $post_id ); $goal = emc_campaign_goal( $post_id ); $progress = emc_campaign_progress( $post_id );
		printf( '<div class="emc-campaign-column-progress"><strong>£%1$s</strong> / £%2$s <span>%3$d%%</span><div><i style="width:%3$d%%"></i></div></div>', esc_html( number_format_i18n( $raised, 2 ) ), esc_html( number_format_i18n( $goal, 2 ) ), absint( $progress ) );
	} elseif ( 'campaign_fund' === $column ) {
		echo '<code>' . esc_html( emc_campaign_fund_name( $post_id ) ) . '</code>';
	} elseif ( 'campaign_dates' === $column ) {
		$start = get_post_meta( $post_id, '_emc_campaign_start_date', true ); $end = get_post_meta( $post_id, '_emc_campaign_end_date', true );
		echo $start ? esc_html( wp_date( get_option( 'date_format' ), strtotime( $start ) ) ) : '—';
		if ( $end ) echo '<br><small>' . esc_html__( 'Ends:', 'emc-theme' ) . ' ' . esc_html( wp_date( get_option( 'date_format' ), strtotime( $end ) ) ) . '</small>';
	} elseif ( 'campaign_status' === $column ) {
		$status = get_post_meta( $post_id, '_emc_campaign_status', true ) ?: 'active';
		printf( '<span class="emc-campaign-status is-%1$s">%2$s</span>', esc_attr( $status ), esc_html( ucfirst( $status ) ) );
	}
}, 10, 2 );

function emc_campaign_admin_screen_help() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-emc_campaign' !== $screen->id ) return;
	?><div class="notice notice-info emc-campaign-list-guide"><p><strong><?php esc_html_e( 'Fundraising Campaign Manager:', 'emc-theme' ); ?></strong> <?php esc_html_e( 'Select Add New Campaign to set the goal, Stripe fund name, offline income, dates, suggested amounts and donation availability. The Raised column combines confirmed Stripe payments with the offline amount you record.', 'emc-theme' ); ?></p></div><?php
}
add_action( 'admin_notices', 'emc_campaign_admin_screen_help' );

function emc_campaign_admin_assets() {
	$screen = get_current_screen();
	if ( ! $screen || 'emc_campaign' !== $screen->post_type ) return;
	$path = EMC_DIR . '/assets/css/admin-campaign.css';
	wp_enqueue_style( 'emc-admin-campaign', EMC_ASSETS . '/css/admin-campaign.css', array(), file_exists( $path ) ? filemtime( $path ) : EMC_VERSION );
}
add_action( 'admin_enqueue_scripts', 'emc_campaign_admin_assets' );
