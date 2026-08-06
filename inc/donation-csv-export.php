<?php
/**
 * CSV exports for the donation records maintained by EMC Payments.
 *
 * The payments plugin owns the admin table and stored records. Keeping the
 * export integration here allows the theme to enhance that screen without
 * loading any of the removed Stripe/payment processing code.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/** Load the minimal history interface only on Settings > EMC Donations. */
function emc_theme_donation_history_assets( $hook ) {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'emc-donations' !== $page ) {
		return;
	}

	$css_path = EMC_DIR . '/assets/css/admin-donations.css';
	$js_path  = EMC_DIR . '/assets/js/admin-donations.js';
	wp_enqueue_style( 'emc-admin-donations', EMC_ASSETS . '/css/admin-donations.css', array(), file_exists( $css_path ) ? filemtime( $css_path ) : EMC_VERSION );
	wp_enqueue_script( 'emc-admin-donations', EMC_ASSETS . '/js/admin-donations.js', array(), file_exists( $js_path ) ? filemtime( $js_path ) : EMC_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'emc_theme_donation_history_assets' );

/**
 * Add CSV download actions to Settings > EMC Donations.
 */
function emc_theme_donation_csv_export_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'emc-donations' !== $page ) {
		return;
	}

	$subscriptions_url = wp_nonce_url(
		admin_url( 'admin-post.php?action=emc_theme_export_donation_records_csv&type=subscriptions' ),
		'emc_theme_export_donation_records_csv_subscriptions'
	);
	$payments_url = wp_nonce_url(
		admin_url( 'admin-post.php?action=emc_theme_export_donation_records_csv&type=payments' ),
		'emc_theme_export_donation_records_csv_payments'
	);
	?>
	<div class="notice notice-info inline emc-donation-export-actions">
		<p>
			<strong><?php esc_html_e( 'Download records:', 'emc-theme' ); ?></strong>
			<a class="button button-secondary" href="<?php echo esc_url( $subscriptions_url ); ?>">
				<?php esc_html_e( 'Scheduled Subscriptions CSV', 'emc-theme' ); ?>
			</a>
			<a class="button button-secondary" href="<?php echo esc_url( $payments_url ); ?>">
				<?php esc_html_e( 'Payments Received CSV', 'emc-theme' ); ?>
			</a>
		</p>
	</div>
	<style>
		.emc-donation-export-actions .button { margin-left: 8px; }
	</style>
	<?php
}
add_action( 'admin_notices', 'emc_theme_donation_csv_export_notice' );

/**
 * Prevent a CSV value from being interpreted as a spreadsheet formula.
 *
 * @param mixed $value Cell value.
 * @return string
 */
function emc_theme_donation_csv_safe_value( $value ) {
	$value = (string) $value;

	if ( preg_match( '/^[=+\-@\t\r]/', $value ) ) {
		$value = "'" . $value;
	}

	return $value;
}

/**
 * Send the selected donation record collection as a CSV download.
 */
function emc_theme_export_donation_records_csv() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die(
			esc_html__( 'You do not have permission to export donation records.', 'emc-theme' ),
			esc_html__( 'Donation export denied', 'emc-theme' ),
			array( 'response' => 403 )
		);
	}

	$type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
	if ( ! in_array( $type, array( 'subscriptions', 'payments' ), true ) ) {
		wp_die(
			esc_html__( 'Invalid donation export type.', 'emc-theme' ),
			esc_html__( 'Invalid donation export', 'emc-theme' ),
			array( 'response' => 400 )
		);
	}

	check_admin_referer( 'emc_theme_export_donation_records_csv_' . $type );

	if ( 'subscriptions' === $type ) {
		$option_name = 'emc_subscriptions_log';
		$file_label  = 'scheduled-subscriptions';
		$headers     = array( 'Setup Date', 'Amount (GBP)', 'Frequency', 'Start Date', 'Runs', 'Fund', 'Name', 'Email', 'Address Line 1', 'Postcode', 'Status', 'Stripe Subscription', 'Message' );
		$map_row     = static function ( $record ) {
			return array(
				$record['date'] ?? '',
				$record['amount'] ?? '0.00',
				ucfirst( $record['frequency'] ?? '' ),
				$record['start_date'] ?? '',
				! empty( $record['occurrences'] ) ? $record['occurrences'] : 'Ongoing',
				$record['fund'] ?? '',
				$record['name'] ?? 'Anonymous',
				$record['email'] ?? '',
				$record['address'] ?? '',
				$record['postcode'] ?? '',
				ucfirst( $record['status'] ?? '' ),
				$record['subscription_id'] ?? '',
				$record['message'] ?? '',
			);
		};
	} else {
		$option_name = 'emc_donations_log';
		$file_label  = 'payments-received';
		$headers     = array( 'Date', 'Amount (GBP)', 'Fund', 'Name', 'Email', 'Address Line 1', 'Postcode', 'Gift Aid', 'Stripe Ref', 'Message' );
		$map_row     = static function ( $record ) {
			return array(
				$record['date'] ?? '',
				$record['amount'] ?? '0.00',
				$record['fund'] ?? '',
				$record['name'] ?? 'Anonymous',
				$record['email'] ?? '',
				$record['address'] ?? '',
				$record['postcode'] ?? '',
				! empty( $record['gift_aid'] ) ? 'Yes' : 'No',
				$record['pi_id'] ?? '',
				$record['message'] ?? '',
			);
		};
	}

	$records = get_option( $option_name, array() );
	$records = is_array( $records ) ? array_reverse( $records ) : array();
	$filename = sprintf( 'emc-%s-%s.csv', $file_label, wp_date( 'Y-m-d' ) );

	while ( ob_get_level() ) {
		ob_end_clean();
	}

	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'X-Content-Type-Options: nosniff' );

	$output = fopen( 'php://output', 'w' );
	if ( false === $output ) {
		wp_die(
			esc_html__( 'The CSV export could not be created.', 'emc-theme' ),
			esc_html__( 'Donation export failed', 'emc-theme' ),
			array( 'response' => 500 )
		);
	}

	// UTF-8 BOM helps Microsoft Excel display names and other non-ASCII text.
	fwrite( $output, "\xEF\xBB\xBF" );
	fputcsv( $output, $headers );

	foreach ( $records as $record ) {
		if ( ! is_array( $record ) ) {
			continue;
		}

		$row = array_map( 'emc_theme_donation_csv_safe_value', $map_row( $record ) );
		fputcsv( $output, $row );
	}

	fclose( $output );
	exit;
}
add_action( 'admin_post_emc_theme_export_donation_records_csv', 'emc_theme_export_donation_records_csv' );
