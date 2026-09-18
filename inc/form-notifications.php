<?php
/**
 * Central form notification settings and delivery helpers.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/** Default SMTP values for Microsoft 365. The password is never kept in theme files. */
function emc_smtp_defaults() {
	return array(
		'enabled'    => '0',
		'host'       => 'smtp.office365.com',
		'port'       => 587,
		'encryption' => 'tls',
		'username'   => 'info@essexmuslimcentre.org',
		'password'   => '',
		'from_email' => 'info@essexmuslimcentre.org',
		'from_name'  => get_bloginfo( 'name' ),
	);
}

/** Get the saved SMTP configuration with safe defaults. */
function emc_smtp_settings() {
	$saved = get_option( 'emc_smtp_settings', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), emc_smtp_defaults() );
}

/** Sanitize SMTP settings while preserving a previously saved masked password. */
function emc_sanitize_smtp_settings( $input ) {
	$input    = is_array( $input ) ? $input : array();
	$current  = emc_smtp_settings();
	$password = isset( $input['password'] ) ? (string) wp_unslash( $input['password'] ) : '';
	$password = str_replace( array( chr( 13 ), chr( 10 ), chr( 0 ) ), '', $password );
	$host     = strtolower( sanitize_text_field( wp_unslash( $input['host'] ?? '' ) ) );
	$host     = preg_replace( '/[^a-z0-9.-]/', '', $host );
	$port     = absint( $input['port'] ?? 587 );
	$security = sanitize_key( $input['encryption'] ?? 'tls' );

	if ( ! in_array( $security, array( 'tls', 'ssl', 'none' ), true ) ) {
		$security = 'tls';
	}
	if ( $port < 1 || $port > 65535 ) {
		$port = 587;
	}

	$output = array(
		'enabled'    => isset( $input['enabled'] ) ? '1' : '0',
		'host'       => $host ?: 'smtp.office365.com',
		'port'       => $port,
		'encryption' => $security,
		'username'   => sanitize_text_field( wp_unslash( $input['username'] ?? '' ) ),
		'password'   => '' !== $password ? $password : (string) $current['password'],
		'from_email' => sanitize_email( wp_unslash( $input['from_email'] ?? '' ) ),
		'from_name'  => sanitize_text_field( wp_unslash( $input['from_name'] ?? '' ) ),
	);

	if ( '1' === $output['enabled'] && ( ! $output['username'] || ! $output['password'] || ! $output['from_email'] ) ) {
		add_settings_error(
			'emc_smtp_settings',
			'emc_smtp_incomplete',
			__( 'SMTP was enabled, but its username, password, or From email is missing. Complete all three fields before testing delivery.', 'emc-theme' ),
			'error'
		);
	}

	return $output;
}

/** Route all WordPress email through the configured SMTP server. */
function emc_configure_phpmailer( $phpmailer ) {
	$settings = emc_smtp_settings();
	if ( '1' !== (string) $settings['enabled'] ) {
		return;
	}

	$phpmailer->isSMTP();
	$phpmailer->Host        = $settings['host'];
	$phpmailer->Port        = (int) $settings['port'];
	$phpmailer->SMTPAuth    = true;
	$phpmailer->Username    = $settings['username'];
	$phpmailer->Password    = $settings['password'];
	$phpmailer->SMTPSecure  = 'none' === $settings['encryption'] ? '' : $settings['encryption'];
	$phpmailer->SMTPAutoTLS = false;
	$phpmailer->Timeout     = 20;

	if ( $settings['from_email'] ) {
		$phpmailer->Sender = $settings['from_email'];
	}
}
add_action( 'phpmailer_init', 'emc_configure_phpmailer', 100 );

/** Microsoft 365 requires the From address to match the authenticated mailbox. */
function emc_smtp_from_email( $email ) {
	$settings = emc_smtp_settings();
	return '1' === (string) $settings['enabled'] && $settings['from_email'] ? $settings['from_email'] : $email;
}
add_filter( 'wp_mail_from', 'emc_smtp_from_email', 100 );

function emc_smtp_from_name( $name ) {
	$settings = emc_smtp_settings();
	return '1' === (string) $settings['enabled'] && $settings['from_name'] ? $settings['from_name'] : $name;
}
add_filter( 'wp_mail_from_name', 'emc_smtp_from_name', 100 );

/** Public response-producing forms managed by the theme. */
function emc_form_notification_types() {
	return array(
		'contact'            => __( 'Contact form', 'emc-theme' ),
		'volunteer'          => __( 'Job applications', 'emc-theme' ),
		'volunteer_signup'   => __( 'Volunteer applications', 'emc-theme' ),
		'gift_aid'           => __( 'Gift Aid declarations', 'emc-theme' ),
		'event_registration' => __( 'Event registrations and event payments', 'emc-theme' ),
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

/**
 * Use a sender on the website domain so shared hosts do not reject the mail.
 * SMTP plugins can still replace this through WordPress's normal mail filters.
 */
function emc_form_notification_sender() {
	$host        = wp_parse_url( home_url(), PHP_URL_HOST );
	$host        = strtolower( preg_replace( '/^www\./i', '', (string) $host ) );
	$admin_email = sanitize_email( get_option( 'admin_email' ) );

	if ( $admin_email && $host ) {
		$admin_domain = strtolower( substr( strrchr( $admin_email, '@' ), 1 ) );
		if ( $admin_domain === $host || substr( $admin_domain, -strlen( '.' . $host ) ) === '.' . $host ) {
			return $admin_email;
		}
	}

	return $host ? sanitize_email( 'wordpress@' . $host ) : $admin_email;
}

/** Store the most recent transport failure for the notification settings page. */
function emc_record_form_mail_failure( $error ) {
	if ( ! is_wp_error( $error ) ) {
		return;
	}
	update_option( 'emc_form_notification_last_error', array(
		'time'    => current_time( 'mysql' ),
		'message' => sanitize_text_field( $error->get_error_message() ),
	), false );
}
add_action( 'wp_mail_failed', 'emc_record_form_mail_failure' );

/** Send one administrator notification using the central controls. */
function emc_send_form_notification( $type, $subject, $message, $headers = array() ) {
	$setting = emc_form_notification_setting( $type );
	if ( ! $setting['enabled'] ) {
		return false;
	}
	$recipients = emc_form_notification_recipients( $type );
	if ( ! $recipients ) {
		$fallback   = sanitize_email( get_option( 'admin_email' ) );
		$recipients = $fallback ? array( $fallback ) : array();
	}
	if ( ! $recipients ) {
		update_option( 'emc_form_notification_last_error', array(
			'time'    => current_time( 'mysql' ),
			'message' => __( 'No valid notification recipient is configured.', 'emc-theme' ),
		), false );
		return false;
	}

	$headers  = is_array( $headers ) ? $headers : array( $headers );
	$has_from = false;
	foreach ( $headers as $header ) {
		if ( 0 === stripos( trim( $header ), 'From:' ) ) {
			$has_from = true;
			break;
		}
	}
	$sender = emc_form_notification_sender();
	if ( ! $has_from && $sender ) {
		$headers[] = sprintf( 'From: %s <%s>', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $sender );
	}

	$sent = wp_mail( $recipients, $subject, $message, $headers );
	update_option( 'emc_form_notification_last_attempt', array(
		'time'       => current_time( 'mysql' ),
		'type'       => sanitize_key( $type ),
		'recipients' => implode( ', ', $recipients ),
		'sent'       => $sent ? '1' : '0',
	), false );
	if ( $sent ) {
		delete_option( 'emc_form_notification_last_error' );
	}
	return $sent;
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
	register_setting( 'emc_form_notifications', 'emc_smtp_settings', array( 'sanitize_callback' => 'emc_sanitize_smtp_settings' ) );
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
	$last_attempt = get_option( 'emc_form_notification_last_attempt', array() );
	$last_error   = get_option( 'emc_form_notification_last_error', array() );
	$smtp         = emc_smtp_settings();
	$smtp_ready   = '1' === (string) $smtp['enabled'] && $smtp['host'] && $smtp['username'] && $smtp['password'] && $smtp['from_email'];
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Form Notification Emails', 'emc-theme' ); ?></h1>
		<?php settings_errors( 'emc_smtp_settings' ); ?>
		<?php if ( isset( $_GET['test'] ) ) : ?>
			<div class="notice <?php echo 'sent' === sanitize_key( $_GET['test'] ) ? 'notice-success' : 'notice-error'; ?> is-dismissible"><p><?php echo 'sent' === sanitize_key( $_GET['test'] ) ? esc_html__( 'Test notification sent successfully.', 'emc-theme' ) : esc_html__( 'The test notification could not be sent. Check your WordPress SMTP configuration.', 'emc-theme' ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Every accepted form response is stored in WordPress and emailed to the recipients below. Separate multiple addresses with commas.', 'emc-theme' ); ?></p>
		<form action="options.php" method="post">
			<?php settings_fields( 'emc_form_notifications' ); ?>
			<h2><?php esc_html_e( 'Email delivery (SMTP)', 'emc-theme' ); ?></h2>
			<table class='form-table' role='presentation' style='max-width:980px'>
				<tr>
					<th scope='row'><?php esc_html_e( 'Enable SMTP', 'emc-theme' ); ?></th>
					<td><label><input type='checkbox' name='emc_smtp_settings[enabled]' value='1' <?php checked( '1', $smtp['enabled'] ); ?>> <?php esc_html_e( 'Send all website email through this mail server', 'emc-theme' ); ?></label></td>
				</tr>
				<tr>
					<th scope='row'><label for='emc-smtp-host'><?php esc_html_e( 'SMTP host', 'emc-theme' ); ?></label></th>
					<td><input id='emc-smtp-host' type='text' class='regular-text' name='emc_smtp_settings[host]' value='<?php echo esc_attr( $smtp['host'] ); ?>' required></td>
				</tr>
				<tr>
					<th scope='row'><label for='emc-smtp-port'><?php esc_html_e( 'Port and security', 'emc-theme' ); ?></label></th>
					<td><input id='emc-smtp-port' type='number' min='1' max='65535' class='small-text' name='emc_smtp_settings[port]' value='<?php echo esc_attr( $smtp['port'] ); ?>' required>
						<select name='emc_smtp_settings[encryption]' aria-label='<?php esc_attr_e( 'Encryption', 'emc-theme' ); ?>'>
							<option value='tls' <?php selected( 'tls', $smtp['encryption'] ); ?>><?php esc_html_e( 'STARTTLS', 'emc-theme' ); ?></option>
							<option value='ssl' <?php selected( 'ssl', $smtp['encryption'] ); ?>><?php esc_html_e( 'SSL/TLS', 'emc-theme' ); ?></option>
							<option value='none' <?php selected( 'none', $smtp['encryption'] ); ?>><?php esc_html_e( 'None', 'emc-theme' ); ?></option>
						</select>
						<p class='description'><?php esc_html_e( 'Microsoft 365 normally uses port 587 with STARTTLS.', 'emc-theme' ); ?></p></td>
				</tr>
				<tr>
					<th scope='row'><label for='emc-smtp-username'><?php esc_html_e( 'SMTP username', 'emc-theme' ); ?></label></th>
					<td><input id='emc-smtp-username' type='text' class='regular-text' name='emc_smtp_settings[username]' value='<?php echo esc_attr( $smtp['username'] ); ?>' autocomplete='username' required></td>
				</tr>
				<tr>
					<th scope='row'><label for='emc-smtp-password'><?php esc_html_e( 'SMTP password', 'emc-theme' ); ?></label></th>
					<td><input id='emc-smtp-password' type='password' class='regular-text' name='emc_smtp_settings[password]' value='' autocomplete='new-password' placeholder='<?php echo esc_attr( $smtp['password'] ? __( 'Saved - leave blank to keep it', 'emc-theme' ) : __( 'Enter the mailbox password', 'emc-theme' ) ); ?>'>
						<p class='description'><?php esc_html_e( 'The saved password is never displayed. Leaving this blank keeps the current password.', 'emc-theme' ); ?></p></td>
				</tr>
				<tr>
					<th scope='row'><label for='emc-smtp-from-email'><?php esc_html_e( 'From email', 'emc-theme' ); ?></label></th>
					<td><input id='emc-smtp-from-email' type='email' class='regular-text' name='emc_smtp_settings[from_email]' value='<?php echo esc_attr( $smtp['from_email'] ); ?>' required>
						<p class='description'><?php esc_html_e( 'For Microsoft 365 this should match the SMTP username unless the mailbox has Send As permission.', 'emc-theme' ); ?></p></td>
				</tr>
				<tr>
					<th scope='row'><label for='emc-smtp-from-name'><?php esc_html_e( 'From name', 'emc-theme' ); ?></label></th>
					<td><input id='emc-smtp-from-name' type='text' class='regular-text' name='emc_smtp_settings[from_name]' value='<?php echo esc_attr( $smtp['from_name'] ); ?>'></td>
				</tr>
			</table>
			<p><strong><?php esc_html_e( 'SMTP status:', 'emc-theme' ); ?></strong> <?php echo $smtp_ready ? esc_html__( 'Configured and enabled', 'emc-theme' ) : esc_html__( 'Not ready - enable SMTP and save all required credentials', 'emc-theme' ); ?></p>
			<p class='description'><?php esc_html_e( 'Microsoft 365 must have Authenticated SMTP enabled for this mailbox. Password-based SMTP is scheduled to be disabled by default for existing tenants after December 2026, so plan a move to OAuth.', 'emc-theme' ); ?></p>

			<h2><?php esc_html_e( 'Notification recipients', 'emc-theme' ); ?></h2>
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
			<?php submit_button( __( 'Save Email Settings', 'emc-theme' ) ); ?>
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
		<p><strong><?php esc_html_e( 'Delivery note:', 'emc-theme' ); ?></strong> <?php esc_html_e( 'Save SMTP and recipient changes before sending a test. A successful result means the SMTP server accepted the message; also check the recipient inbox and spam folder.', 'emc-theme' ); ?></p>
		<?php if ( ! empty( $last_attempt['time'] ) ) : ?>
			<p><strong><?php esc_html_e( 'Last notification attempt:', 'emc-theme' ); ?></strong> <?php echo esc_html( $last_attempt['time'] ); ?> &mdash; <?php echo ! empty( $last_attempt['sent'] ) ? esc_html__( 'accepted by the mail transport', 'emc-theme' ) : esc_html__( 'failed', 'emc-theme' ); ?><?php if ( ! empty( $last_attempt['recipients'] ) ) : ?> (<?php echo esc_html( $last_attempt['recipients'] ); ?>)<?php endif; ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $last_error['message'] ) ) : ?>
			<div class="notice notice-error inline"><p><strong><?php esc_html_e( 'Mail error:', 'emc-theme' ); ?></strong> <?php echo esc_html( $last_error['message'] ); ?></p></div>
		<?php endif; ?>
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
