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
	wp_localize_script( 'emc-admin-donations', 'emcDonationsData', array(
		'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
		'nonce'       => wp_create_nonce( 'emc_delete_donation_record_nonce' ),
		'confirmText' => __( 'Are you sure you want to delete this payment record from the history? This cannot be undone.', 'emc-theme' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'emc_theme_donation_history_assets' );

/**
 * Determine if a payment or subscription record is a test entry.
 *
 * @param array $record
 * @return bool
 */
function emc_is_test_donation_record( $record ) {
	if ( ! is_array( $record ) ) {
		return false;
	}

	// 1. Explicit Stripe livemode / test flags
	if ( isset( $record['livemode'] ) && false === $record['livemode'] ) {
		return true;
	}
	if ( ! empty( $record['test_mode'] ) || ! empty( $record['is_test'] ) ) {
		return true;
	}
	if ( isset( $record['mode'] ) && 'test' === strtolower( (string) $record['mode'] ) ) {
		return true;
	}

	// 2. Known developer / testing emails
	$email = strtolower( trim( (string) ( $record['email'] ?? '' ) ) );
	$test_emails = array(
		'animm914@gmail.com',
		'ajuddin927@gmail.com',
		'a@gmail.com',
		'test@test.com',
		'test@gmail.com',
	);
	if ( in_array( $email, $test_emails, true ) ) {
		return true;
	}
	if ( preg_match( '/@(example\.com|test\.com|test\.org)$/i', $email ) || false !== strpos( $email, '+test' ) ) {
		return true;
	}

	// 3. Known test addresses / postcodes
	$address = strtolower( (string) ( $record['address'] ?? '' ) );
	$test_addresses = array(
		'test text',
		'avdsv',
		'efs',
		'fgdfgasg',
		'morkun tongi',
		'bangladesh',
		'14 st nazaire road',
	);
	foreach ( $test_addresses as $needle ) {
		if ( '' !== $needle && false !== strpos( $address, $needle ) ) {
			return true;
		}
	}

	// 4. Test names
	$name = strtolower( trim( (string) ( $record['name'] ?? '' ) ) );
	$test_names = array(
		'test',
		'test user',
		'maraj anim',
		'anim',
		'jahir',
		'acc a',
		'ss',
	);
	if ( in_array( $name, $test_names, true ) ) {
		return true;
	}

	// 5. Test message strings
	$message = strtolower( trim( (string) ( $record['message'] ?? '' ) ) );
	if ( in_array( $message, array( 'test', 'testing', 'test donation' ), true ) ) {
		return true;
	}

	return false;
}

/**
 * Add CSV download actions and test cleanup notice to Settings > EMC Donations.
 */
function emc_theme_donation_csv_export_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'emc-donations' !== $page ) {
		return;
	}

	if ( ! empty( $_GET['emc_purged'] ) ) {
		$subs_del = isset( $_GET['subs_del'] ) ? absint( $_GET['subs_del'] ) : 0;
		$pays_del = isset( $_GET['pays_del'] ) ? absint( $_GET['pays_del'] ) : 0;
		?>
		<div class="notice notice-success is-dismissible" style="border-left-color:#159d92;">
			<p>
				<strong><?php esc_html_e( 'Test history cleanup complete:', 'emc-theme' ); ?></strong>
				<?php
				printf(
					/* translators: 1: subscriptions count, 2: payments count */
					esc_html__( 'Deleted %1$d test subscription(s) and %2$d test payment(s). Live donor records have been preserved.', 'emc-theme' ),
					$subs_del,
					$pays_del
				);
				?>
			</p>
		</div>
		<?php
	}

	$subscriptions_url = wp_nonce_url(
		admin_url( 'admin-post.php?action=emc_theme_export_donation_records_csv&type=subscriptions' ),
		'emc_theme_export_donation_records_csv_subscriptions'
	);
	$payments_url = wp_nonce_url(
		admin_url( 'admin-post.php?action=emc_theme_export_donation_records_csv&type=payments' ),
		'emc_theme_export_donation_records_csv_payments'
	);
	$purge_url = wp_nonce_url(
		admin_url( 'admin-post.php?action=emc_purge_test_donations' ),
		'emc_purge_test_donations_nonce'
	);

	// Count test records
	$subs      = get_option( 'emc_subscriptions_log', array() );
	$test_subs = 0;
	if ( is_array( $subs ) ) {
		foreach ( $subs as $sub ) {
			if ( emc_is_test_donation_record( $sub ) ) {
				$test_subs++;
			}
		}
	}

	$payments      = get_option( 'emc_donations_log', array() );
	$test_payments = 0;
	if ( is_array( $payments ) ) {
		foreach ( $payments as $pay ) {
			if ( emc_is_test_donation_record( $pay ) ) {
				$test_payments++;
			}
		}
	}
	$total_test = $test_subs + $test_payments;
	?>
	<div class="notice notice-info inline emc-donation-export-actions">
		<p style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;">
			<strong><?php esc_html_e( 'Records:', 'emc-theme' ); ?></strong>
			<a class="button button-secondary" href="<?php echo esc_url( $subscriptions_url ); ?>">
				<?php esc_html_e( 'Scheduled Subscriptions CSV', 'emc-theme' ); ?>
			</a>
			<a class="button button-secondary" href="<?php echo esc_url( $payments_url ); ?>">
				<?php esc_html_e( 'Payments Received CSV', 'emc-theme' ); ?>
			</a>

			<?php if ( $total_test > 0 ) : ?>
				<span style="color:#cfdadd;margin:0 4px;">|</span>
				<a class="button button-secondary" style="color:#b32d2e;border-color:#f0c5c5;font-weight:600;" href="<?php echo esc_url( $purge_url ); ?>" onclick="return confirm('<?php echo esc_js( sprintf( __( 'Delete all %d test payment and subscription records? All live customer payments will be preserved.', 'emc-theme' ), $total_test ) ); ?>');">
					<span class="dashicons dashicons-trash" style="font-size:16px;line-height:26px;vertical-align:top;color:#b32d2e;"></span>
					<?php
					printf(
						/* translators: %d: number of test records */
						esc_html__( 'Delete All Test Histories (%d detected)', 'emc-theme' ),
						$total_test
					);
					?>
				</a>
			<?php endif; ?>
		</p>
	</div>
	<style>
		.emc-donation-export-actions .button { margin-left: 0; }
	</style>
	<?php
}
add_action( 'admin_notices', 'emc_theme_donation_csv_export_notice' );

/**
 * Handle purging of all test donation and subscription records.
 */
function emc_theme_purge_test_donations() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'emc-theme' ), 403 );
	}

	check_admin_referer( 'emc_purge_test_donations_nonce' );

	$subs     = get_option( 'emc_subscriptions_log', array() );
	$subs_del = 0;
	if ( is_array( $subs ) ) {
		$clean_subs = array();
		foreach ( $subs as $sub ) {
			if ( emc_is_test_donation_record( $sub ) ) {
				$subs_del++;
			} else {
				$clean_subs[] = $sub;
			}
		}
		update_option( 'emc_subscriptions_log', $clean_subs );
	}

	$payments = get_option( 'emc_donations_log', array() );
	$pays_del = 0;
	if ( is_array( $payments ) ) {
		$clean_pays = array();
		foreach ( $payments as $payment ) {
			if ( emc_is_test_donation_record( $payment ) ) {
				$pays_del++;
			} else {
				$clean_pays[] = $payment;
			}
		}
		update_option( 'emc_donations_log', $clean_pays );
	}

	wp_safe_redirect( add_query_arg( array(
		'page'       => 'emc-donations',
		'emc_purged' => 1,
		'subs_del'   => $subs_del,
		'pays_del'   => $pays_del,
	), admin_url( 'options-general.php' ) ) );
	exit;
}
add_action( 'admin_post_emc_purge_test_donations', 'emc_theme_purge_test_donations' );

/**
 * AJAX delete single donation record from the table.
 */
function emc_theme_delete_single_donation_record() {
	check_ajax_referer( 'emc_delete_donation_record_nonce', 'security' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'emc-theme' ) ), 403 );
	}

	$table_title = sanitize_text_field( wp_unslash( $_POST['table_title'] ?? '' ) );
	$is_sub      = ( false !== stripos( $table_title, 'subscription' ) || false !== stripos( $table_title, 'schedule' ) );
	$option_name = $is_sub ? 'emc_subscriptions_log' : 'emc_donations_log';

	$ref   = sanitize_text_field( wp_unslash( $_POST['ref'] ?? '' ) );
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$date  = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );

	$records = get_option( $option_name, array() );
	if ( ! is_array( $records ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'No records found.', 'emc-theme' ) ), 404 );
	}

	$found       = false;
	$new_records = array();

	if ( is_array( $records ) ) {
		foreach ( array_reverse( $records ) as $record ) {
			if ( ! $found && is_array( $record ) ) {
				$sub_id    = $record['subscription_id'] ?? '';
				$pi_id     = $record['pi_id'] ?? '';
				$rec_email = $record['email'] ?? '';
				$rec_date  = $record['date'] ?? '';

				$match_ref = ( '' !== $ref && ( $sub_id === $ref || $pi_id === $ref || ( '' !== $sub_id && false !== strpos( $ref, $sub_id ) ) || ( '' !== $pi_id && false !== strpos( $ref, $pi_id ) ) ) );
				$match_fallback = ( '' !== $date && '' !== $email && $rec_date === $date && strtolower( (string) $rec_email ) === strtolower( (string) $email ) );

				if ( $match_ref || $match_fallback ) {
					$found = true;
					continue; // Skip this record to delete it
				}
			}
			$new_records[] = $record;
		}

		if ( $found ) {
			update_option( $option_name, array_reverse( $new_records ) );
		}
	}

	// If not found in primary option, check the alternate log option defensively
	if ( ! $found ) {
		$alt_option  = ( 'emc_subscriptions_log' === $option_name ) ? 'emc_donations_log' : 'emc_subscriptions_log';
		$alt_records = get_option( $alt_option, array() );
		if ( is_array( $alt_records ) ) {
			$new_alt_records = array();
			foreach ( array_reverse( $alt_records ) as $record ) {
				if ( ! $found && is_array( $record ) ) {
					$sub_id    = $record['subscription_id'] ?? '';
					$pi_id     = $record['pi_id'] ?? '';
					$rec_email = $record['email'] ?? '';
					$rec_date  = $record['date'] ?? '';

					$match_ref = ( '' !== $ref && ( $sub_id === $ref || $pi_id === $ref || ( '' !== $sub_id && false !== strpos( $ref, $sub_id ) ) || ( '' !== $pi_id && false !== strpos( $ref, $pi_id ) ) ) );
					$match_fallback = ( '' !== $date && '' !== $email && $rec_date === $date && strtolower( (string) $rec_email ) === strtolower( (string) $email ) );

					if ( $match_ref || $match_fallback ) {
						$found = true;
						continue;
					}
				}
				$new_alt_records[] = $record;
			}
			if ( $found ) {
				update_option( $alt_option, array_reverse( $new_alt_records ) );
			}
		}
	}

	if ( $found ) {
		wp_send_json_success( array( 'message' => esc_html__( 'Record deleted successfully.', 'emc-theme' ) ) );
	} else {
		wp_send_json_error( array( 'message' => esc_html__( 'Record not found.', 'emc-theme' ) ), 404 );
	}
}
add_action( 'wp_ajax_emc_delete_single_donation_record', 'emc_theme_delete_single_donation_record' );

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
