<?php
/**
 * Central form notification settings and delivery helpers.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/** Public response-producing forms managed by the theme. */
function emc_form_notification_types() {
	return array(
		'contact'            => __( 'Contact form', 'emc-theme' ),
		'volunteer'          => __( 'Job applications', 'emc-theme' ),
		'volunteer_signup'   => __( 'Volunteer applications', 'emc-theme' ),
		'gift_aid'           => __( 'Gift Aid declarations', 'emc-theme' ),
		'event_registration' => __( 'Event registrations and event payments', 'emc-theme' ),
		'membership'         => __( 'Membership applications and fees', 'emc-theme' ),
		'newsletter'         => __( 'Newsletter signups', 'emc-theme' ),
		'donation'           => __( 'Donation and Badr Wall payments', 'emc-theme' ),
		'subscription'       => __( 'Regular and Ramadan giving schedules', 'emc-theme' ),
	);
}

/** Legacy recipient used before the central screen was introduced. */
function emc_form_legacy_recipient( $type ) {
	$defaults = array(
		'contact'            => emc_option( 'emc_admin_email', get_option( 'admin_email' ) ),
		'volunteer'          => get_option( 'emc_volunteer_notification_email', get_option( 'admin_email' ) ),
		'volunteer_signup'   => get_option( 'admin_email' ),
		'gift_aid'           => get_option( 'emc_gift_aid_notification_email', get_option( 'admin_email' ) ),
		'event_registration' => get_option( 'emc_event_registration_email', get_option( 'admin_email' ) ),
		'membership'         => get_option( 'admin_email' ),
		'newsletter'         => get_option( 'admin_email' ),
		'donation'           => get_option( 'admin_email' ),
		'subscription'       => get_option( 'admin_email' ),
	);
	return sanitize_email( $defaults[ $type ] ?? get_option( 'admin_email' ) );
}

/** Get settings for one form. Notifications default to enabled. */
function emc_form_notification_setting( $type ) {
	$all     = get_option( 'emc_form_notification_settings', array() );
	$current = is_array( $all[ $type ] ?? null ) ? $all[ $type ] : array();
	return array(
		'enabled'    => ! isset( $current['enabled'] ) || '0' !== (string) $current['enabled'],
		'recipients' => sanitize_text_field( $current['recipients'] ?? emc_form_legacy_recipient( $type ) ),
	);
}

/** Convert a comma-separated recipient setting into validated addresses. */
function emc_form_notification_recipients( $type ) {
	$setting = emc_form_notification_setting( $type );
	$emails  = array_filter( array_map( 'sanitize_email', preg_split( '/[,;\s]+/', $setting['recipients'] ) ) );
	return array_values( array_unique( $emails ) );
}

/** Send one administrator notification using the central controls. */
function emc_send_form_notification( $type, $subject, $message, $headers = array() ) {
	$setting = emc_form_notification_setting( $type );
	if ( ! $setting['enabled'] ) {
		return false;
	}
	$recipients = emc_form_notification_recipients( $type );
	if ( ! $recipients ) {
		$recipients = array( sanitize_email( get_option( 'admin_email' ) ) );
	}
	return wp_mail( $recipients, $subject, $message, $headers );
}

/** Build a safe HTML summary which includes every supplied form answer. */
function emc_form_notification_html( $heading, $responses ) {
	$html = '<h2>' . esc_html( $heading ) . '</h2><table cellpadding="7" cellspacing="0" border="1" style="border-collapse:collapse;width:100%;max-width:760px">';
	foreach ( $responses as $label => $value ) {
		if ( is_array( $value ) ) {
			$value = implode( ', ', $value );
		}
		$value = '' === trim( (string) $value ) ? __( 'Not supplied', 'emc-theme' ) : (string) $value;
		$html .= '<tr><th align="left" valign="top" style="width:190px">' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( $value ) ) . '</td></tr>';
	}
	return $html . '</table>';
}

/** Sanitize the complete central settings collection. */
function emc_sanitize_form_notification_settings( $input ) {
	$output = array();
	foreach ( emc_form_notification_types() as $type => $label ) {
		$row    = is_array( $input[ $type ] ?? null ) ? $input[ $type ] : array();
		$emails = array_filter( array_map( 'sanitize_email', preg_split( '/[,;\s]+/', wp_unslash( $row['recipients'] ?? '' ) ) ) );
		$output[ $type ] = array(
			'enabled'    => isset( $row['enabled'] ) ? '1' : '0',
			'recipients' => implode( ', ', array_unique( $emails ) ),
		);
	}
	return $output;
}

function emc_register_form_notification_settings() {
	register_setting( 'emc_form_notifications', 'emc_form_notification_settings', array( 'sanitize_callback' => 'emc_sanitize_form_notification_settings' ) );
}
add_action( 'admin_init', 'emc_register_form_notification_settings' );

function emc_form_notifications_admin_menu() {
	add_submenu_page(
		null,
		__( 'Form Notifications', 'emc-theme' ),
		__( 'Form Notifications', 'emc-theme' ),
		'manage_options',
		'emc-form-notifications',
		'emc_form_notifications_admin_page'
	);
}
add_action( 'admin_menu', 'emc_form_notifications_admin_menu', 20 );

/**
 * Email newly appended payment-plugin records without changing payment logic.
 */
function emc_notify_new_payment_log_records( $option, $old_value, $new_value ) {
	$types = array(
		'emc_donations_log'   => 'donation',
		'emc_subscriptions_log' => 'subscription',
	);
	if ( ! isset( $types[ $option ] ) || ! is_array( $new_value ) ) {
		return;
	}
	$old_value = is_array( $old_value ) ? $old_value : array();
	$fingerprint = static function( $record ) use ( $option ) {
		if ( ! is_array( $record ) ) {
			return '';
		}
		$reference = $record['pi_id'] ?? $record['subscription_id'] ?? '';
		return $reference ? $option . '|' . $reference : $option . '|' . md5( wp_json_encode( $record ) );
	};
	$old_fingerprints = array_filter( array_map( $fingerprint, $old_value ) );
	$new_records      = array_filter( $new_value, static function( $record ) use ( $fingerprint, $old_fingerprints ) {
		$key = $fingerprint( $record );
		return $key && ! in_array( $key, $old_fingerprints, true );
	} );
	foreach ( $new_records as $record ) {
		if ( ! is_array( $record ) ) {
			continue;
		}
		$reference = sanitize_text_field( $record['pi_id'] ?? $record['subscription_id'] ?? '' );
		$dedupe    = 'emc_form_mail_' . md5( $option . '|' . $reference . '|' . wp_json_encode( $record ) );
		if ( get_transient( $dedupe ) ) {
			continue;
		}
		set_transient( $dedupe, 1, 30 * DAY_IN_SECONDS );
		$labels = array(
			'date'            => __( 'Date', 'emc-theme' ),
			'amount'          => __( 'Amount (GBP)', 'emc-theme' ),
			'frequency'       => __( 'Frequency', 'emc-theme' ),
			'start_date'      => __( 'Start date', 'emc-theme' ),
			'occurrences'     => __( 'Number of runs', 'emc-theme' ),
			'fund'            => __( 'Fund', 'emc-theme' ),
			'name'            => __( 'Name', 'emc-theme' ),
			'email'           => __( 'Email', 'emc-theme' ),
			'address'         => __( 'Address line 1', 'emc-theme' ),
			'postcode'        => __( 'Postcode', 'emc-theme' ),
			'gift_aid'        => __( 'Gift Aid', 'emc-theme' ),
			'status'          => __( 'Status', 'emc-theme' ),
			'pi_id'           => __( 'Stripe payment reference', 'emc-theme' ),
			'subscription_id' => __( 'Stripe subscription reference', 'emc-theme' ),
			'message'         => __( 'Message', 'emc-theme' ),
		);
		$responses = array();
		foreach ( $record as $key => $value ) {
			if ( is_scalar( $value ) ) {
				$responses[ $labels[ $key ] ?? ucwords( str_replace( '_', ' ', $key ) ) ] = $value;
			}
		}
		$type    = $types[ $option ];
		$heading = 'donation' === $type ? __( 'New Donation Payment', 'emc-theme' ) : __( 'New Giving Schedule', 'emc-theme' );
		$subject = sprintf( '%1$s — %2$s — £%3$s', $heading, sanitize_text_field( $record['name'] ?? __( 'Anonymous', 'emc-theme' ) ), sanitize_text_field( $record['amount'] ?? '0.00' ) );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$email   = sanitize_email( $record['email'] ?? '' );
		if ( $email ) {
			$headers[] = 'Reply-To: ' . $email;
		}
		emc_send_form_notification( $type, $subject, emc_form_notification_html( $heading, $responses ), $headers );
	}
}
add_action( 'updated_option', 'emc_notify_new_payment_log_records', 20, 3 );

function emc_notify_first_payment_log_records( $option, $value ) {
	emc_notify_new_payment_log_records( $option, array(), $value );
}
add_action( 'added_option', 'emc_notify_first_payment_log_records', 20, 2 );

function emc_form_notifications_admin_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Form Notification Emails', 'emc-theme' ); ?></h1>
		<?php if ( isset( $_GET['test'] ) ) : ?>
			<div class="notice <?php echo 'sent' === sanitize_key( $_GET['test'] ) ? 'notice-success' : 'notice-error'; ?> is-dismissible"><p><?php echo 'sent' === sanitize_key( $_GET['test'] ) ? esc_html__( 'Test notification sent successfully.', 'emc-theme' ) : esc_html__( 'The test notification could not be sent. Check your WordPress SMTP configuration.', 'emc-theme' ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Every accepted form response is stored in WordPress and emailed to the recipients below. Separate multiple addresses with commas.', 'emc-theme' ); ?></p>
		<form action="options.php" method="post">
			<?php settings_fields( 'emc_form_notifications' ); ?>
			<table class="widefat striped" style="max-width:980px">
				<thead><tr><th><?php esc_html_e( 'Form', 'emc-theme' ); ?></th><th><?php esc_html_e( 'Send email', 'emc-theme' ); ?></th><th><?php esc_html_e( 'Notification recipients', 'emc-theme' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( emc_form_notification_types() as $type => $label ) : $setting = emc_form_notification_setting( $type ); ?>
				<tr>
					<th scope="row"><?php echo esc_html( $label ); ?></th>
					<td><label><input type="checkbox" name="emc_form_notification_settings[<?php echo esc_attr( $type ); ?>][enabled]" value="1" <?php checked( $setting['enabled'] ); ?>> <?php esc_html_e( 'Enabled', 'emc-theme' ); ?></label></td>
					<td><input type="text" class="large-text" name="emc_form_notification_settings[<?php echo esc_attr( $type ); ?>][recipients]" value="<?php echo esc_attr( $setting['recipients'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php submit_button( __( 'Save Form Notification Settings', 'emc-theme' ) ); ?>
		</form>
		<h2><?php esc_html_e( 'Test email delivery', 'emc-theme' ); ?></h2>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="emc_test_form_notification">
			<?php wp_nonce_field( 'emc_test_form_notification' ); ?>
			<select name="notification_type">
				<?php foreach ( emc_form_notification_types() as $type => $label ) : ?><option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
			</select>
			<?php submit_button( __( 'Send Test Notification', 'emc-theme' ), 'secondary', 'submit', false ); ?>
		</form>
		<p><strong><?php esc_html_e( 'Delivery note:', 'emc-theme' ); ?></strong> <?php esc_html_e( 'WordPress must be able to send email. For reliable delivery, configure an SMTP plugin and send a test email after deployment.', 'emc-theme' ); ?></p>
	</div>
	<?php
}

function emc_handle_test_form_notification() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to send this test.', 'emc-theme' ) );
	}
	check_admin_referer( 'emc_test_form_notification' );
	$type  = sanitize_key( wp_unslash( $_POST['notification_type'] ?? '' ) );
	$types = emc_form_notification_types();
	if ( ! isset( $types[ $type ] ) ) {
		wp_die( esc_html__( 'Invalid notification type.', 'emc-theme' ) );
	}
	$sent = emc_send_form_notification(
		$type,
		sprintf( __( 'Test form notification: %s', 'emc-theme' ), $types[ $type ] ),
		emc_form_notification_html( __( 'Test Form Notification', 'emc-theme' ), array(
			__( 'Form', 'emc-theme' ) => $types[ $type ],
			__( 'Sent at', 'emc-theme' ) => current_time( 'mysql' ),
			__( 'Message', 'emc-theme' ) => __( 'Your form notification email settings are working.', 'emc-theme' ),
		) ),
		array( 'Content-Type: text/html; charset=UTF-8' )
	);
	wp_safe_redirect( add_query_arg( array( 'page' => 'emc-form-notifications', 'test' => $sent ? 'sent' : 'failed' ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_emc_test_form_notification', 'emc_handle_test_form_notification' );
