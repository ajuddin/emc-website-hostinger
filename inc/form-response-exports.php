<?php
/**
 * Detailed CSV exports for every public form response.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

function emc_form_response_export_types() {
	return array(
		'all'                  => array( __( 'All Form Responses', 'emc-theme' ), 'manage_options' ),
		'event-registrations'  => array( __( 'Event Registrations', 'emc-theme' ), 'edit_posts' ),
		'contact-messages'     => array( __( 'Contact Messages', 'emc-theme' ), 'manage_options' ),
		'volunteer'            => array( __( 'Job Applications', 'emc-theme' ), 'manage_options' ),
		'volunteer-signups'    => array( __( 'Volunteer Applications', 'emc-theme' ), 'manage_options' ),
		'gift-aid'             => array( __( 'Gift Aid Declarations', 'emc-theme' ), 'manage_options' ),
		'memberships'          => array( __( 'Memberships', 'emc-theme' ), 'manage_options' ),
		'newsletter'           => array( __( 'Newsletter Subscribers', 'emc-theme' ), 'manage_options' ),
	);
}

function emc_form_response_export_url( $type ) {
	return wp_nonce_url(
		admin_url( 'admin-post.php?action=emc_export_form_responses&type=' . rawurlencode( $type ) ),
		'emc_export_form_responses_' . $type
	);
}

/** Retrieve every stored private response of one post type. */
function emc_form_response_ids( $post_type ) {
	return get_posts( array(
		'post_type'      => $post_type,
		'post_status'    => 'private',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
}

function emc_event_registration_export_data() {
	$records = get_option( 'emc_event_registrations', array() );
	$records = is_array( $records ) ? array_reverse( $records ) : array();
	$dynamic = array();
	foreach ( $records as $record ) {
		foreach ( is_array( $record['fields'] ?? null ) ? $record['fields'] : array() as $key => $field ) {
			$key = sanitize_key( $key );
			if ( $key ) $dynamic[ $key ] = sanitize_text_field( $field['label'] ?? $key );
		}
	}
	$headers = array_merge( array( 'Registration ID', 'Submitted', 'Event ID', 'Event', 'Name', 'Email', 'Phone', 'Attendees', 'Notes', 'Payment Status', 'Subtotal (GBP)', 'Discount (GBP)', 'Amount Paid (GBP)', 'Stripe Payment Intent', 'Notification Status' ), array_values( $dynamic ) );
	$rows = array();
	foreach ( $records as $record ) {
		$event_id = absint( $record['event_id'] ?? 0 );
		$row = array( $record['id'] ?? '', $record['date'] ?? '', $event_id, $event_id ? get_the_title( $event_id ) : 'Deleted event', $record['name'] ?? '', $record['email'] ?? '', $record['phone'] ?? '', $record['attendees'] ?? 1, $record['message'] ?? '', $record['payment_status'] ?? 'free', $record['subtotal'] ?? $record['amount'] ?? '0.00', $record['discount'] ?? '0.00', $record['amount'] ?? '0.00', $record['payment_intent'] ?? '', $record['notification_sent'] ?? '' );
		foreach ( $dynamic as $key => $label ) $row[] = $record['fields'][ $key ]['value'] ?? '';
		$rows[] = $row;
	}
	return array( $headers, $rows );
}

function emc_contact_export_data() {
	$headers = array( 'Record ID', 'Submitted', 'First Name', 'Last Name', 'Email', 'Subject Key', 'Subject', 'Message', 'Record Status', 'Email Notification Status' );
	$rows = array();
	foreach ( emc_form_response_ids( 'emc_contact_entry' ) as $id ) {
		$rows[] = array( $id, get_post_meta( $id, '_emc_contact_submitted_at', true ) ?: get_the_date( 'Y-m-d H:i:s', $id ), get_post_meta( $id, '_emc_contact_first_name', true ), get_post_meta( $id, '_emc_contact_last_name', true ), get_post_meta( $id, '_emc_contact_email', true ), get_post_meta( $id, '_emc_contact_subject', true ), get_post_meta( $id, '_emc_contact_subject_label', true ), get_post_meta( $id, '_emc_contact_message', true ), get_post_meta( $id, '_emc_contact_status', true ), get_post_meta( $id, '_emc_contact_email_status', true ) );
	}
	return array( $headers, $rows );
}

function emc_volunteer_export_data() {
	$fields = array( 'first_name' => 'First Name', 'last_name' => 'Last Name', 'email' => 'Email', 'phone' => 'Phone', 'postcode' => 'Postcode', 'over_18' => 'Over 18', 'position' => 'Position Applied For', 'availability_details' => 'Notice Period / Availability', 'skills' => 'Skills and Experience', 'motivation' => 'Supporting Statement', 'checks_consent' => 'Checks Consent', 'privacy_consent' => 'Privacy Consent', 'status' => 'Record Status', 'email_status' => 'Email Notification Status' );
	$ids = emc_form_response_ids( 'emc_volunteer' );
	$dynamic = array();
	foreach ( $ids as $id ) {
		foreach ( (array) get_post_meta( $id, '_emc_volunteer_extra_fields', true ) as $key => $answer ) {
			$key = sanitize_key( $key );
			if ( $key ) $dynamic[ $key ] = sanitize_text_field( $answer['label'] ?? $key );
		}
	}
	$headers = array_merge( array( 'Record ID', 'Submitted' ), array_values( $fields ), array( 'CV URL' ), array_values( $dynamic ) );
	$rows = array();
	foreach ( $ids as $id ) {
		$row = array( $id, get_post_meta( $id, '_emc_volunteer_submitted_at', true ) ?: get_the_date( 'Y-m-d H:i:s', $id ) );
		foreach ( $fields as $field => $label ) {
			$value = get_post_meta( $id, '_emc_volunteer_' . $field, true );
			$row[] = in_array( $field, array( 'checks_consent', 'privacy_consent' ), true ) ? ( '1' === $value ? 'Yes' : 'No' ) : $value;
		}
		$row[] = wp_get_attachment_url( absint( get_post_meta( $id, '_emc_volunteer_cv_attachment_id', true ) ) ) ?: '';
		$answers = (array) get_post_meta( $id, '_emc_volunteer_extra_fields', true );
		foreach ( $dynamic as $key => $label ) $row[] = $answers[ $key ]['value'] ?? '';
		$rows[] = $row;
	}
	return array( $headers, $rows );
}

function emc_volunteer_signup_export_data() {
	$fields = array( 'first_name' => 'First Name', 'last_name' => 'Last Name', 'email' => 'Email', 'phone' => 'Phone', 'postcode' => 'Postcode', 'over_18' => 'Over 18', 'role' => 'Volunteer Role', 'interests' => 'Interests', 'interest_other' => 'Other Interest', 'availability' => 'Availability', 'availability_details' => 'Availability Details', 'skills' => 'Skills and Experience', 'motivation' => 'Reason for Volunteering', 'checks_consent' => 'Checks Consent', 'privacy_consent' => 'Privacy Consent', 'status' => 'Record Status', 'email_status' => 'Email Notification Status' );
	$headers = array_merge( array( 'Record ID', 'Submitted' ), array_values( $fields ) );
	$rows = array();
	foreach ( emc_form_response_ids( 'emc_volunteer_signup' ) as $id ) {
		$row = array( $id, get_post_meta( $id, '_emc_volunteer_signup_submitted_at', true ) ?: get_the_date( 'Y-m-d H:i:s', $id ) );
		foreach ( $fields as $field => $label ) {
			$value = get_post_meta( $id, '_emc_volunteer_signup_' . $field, true );
			$row[] = in_array( $field, array( 'checks_consent', 'privacy_consent' ), true ) ? ( '1' === $value ? 'Yes' : 'No' ) : $value;
		}
		$rows[] = $row;
	}
	return array( $headers, $rows );
}

function emc_gift_aid_export_data() {
	$fields = array( 'prefix' => 'Title', 'first_name' => 'First Name', 'last_name' => 'Last Name', 'address_line_1' => 'Address Line 1', 'town_city' => 'Town/City', 'postcode' => 'Postcode', 'country' => 'Country', 'phone' => 'Phone', 'email' => 'Email', 'declaration_scope' => 'Declaration Scope', 'declaration_text' => 'Declaration Text', 'non_uk_donor' => 'Non-UK Donor', 'accuracy_confirmed' => 'Accuracy Confirmed', 'taxpayer_confirmed' => 'UK Taxpayer Confirmed' );
	$headers = array_merge( array( 'Record ID', 'Submitted' ), array_values( $fields ) );
	$rows = array();
	foreach ( emc_form_response_ids( 'emc_gift_aid' ) as $id ) {
		$row = array( $id, get_post_meta( $id, '_emc_gift_aid_submitted_at', true ) ?: get_the_date( 'Y-m-d H:i:s', $id ) );
		foreach ( $fields as $field => $label ) {
			$value = get_post_meta( $id, '_emc_gift_aid_' . $field, true );
			$row[] = in_array( $field, array( 'non_uk_donor', 'accuracy_confirmed', 'taxpayer_confirmed' ), true ) ? ( '1' === $value ? 'Yes' : 'No' ) : $value;
		}
		$rows[] = $row;
	}
	return array( $headers, $rows );
}

function emc_membership_export_data() {
	$fields = array( 'first_name' => 'First Name', 'last_name' => 'Last Name', 'email' => 'Email', 'phone' => 'Phone', 'address_1' => 'Address Line 1', 'address_2' => 'Address Line 2', 'city' => 'Town/City', 'postcode' => 'Postcode', 'tier_name' => 'Membership Category', 'start_date' => 'Start Date', 'expiry_date' => 'Expiry Date', 'payment_status' => 'Payment Status', 'amount' => 'Amount (GBP)', 'payment_intent' => 'Stripe Reference', 'gift_aid' => 'Gift Aid', 'status' => 'Record Status', 'notes' => 'Notes' );
	$headers = array_merge( array( 'Record ID', 'Submitted' ), array_values( $fields ) );
	$rows = array();
	foreach ( emc_form_response_ids( 'emc_membership' ) as $id ) {
		$row = array( $id, get_post_meta( $id, '_emc_membership_submitted_at', true ) ?: get_the_date( 'Y-m-d H:i:s', $id ) );
		foreach ( $fields as $field => $label ) {
			$value = get_post_meta( $id, '_emc_membership_' . $field, true );
			$row[] = 'gift_aid' === $field ? ( '1' === $value ? 'Yes' : 'No' ) : $value;
		}
		$rows[] = $row;
	}
	return array( $headers, $rows );
}

function emc_newsletter_export_data() {
	$headers = array( 'Record ID', 'Submitted', 'Email', 'Consent', 'Mailchimp Status', 'Last Sync', 'Mailchimp Error' );
	$rows = array();
	foreach ( emc_form_response_ids( 'emc_newsletter' ) as $id ) {
		$rows[] = array( $id, get_post_meta( $id, '_emc_newsletter_submitted_at', true ) ?: get_the_date( 'Y-m-d H:i:s', $id ), get_post_meta( $id, '_emc_newsletter_email', true ), '1' === get_post_meta( $id, '_emc_newsletter_consent', true ) ? 'Yes' : 'No', get_post_meta( $id, '_emc_newsletter_status', true ), get_post_meta( $id, '_emc_newsletter_synced_at', true ), get_post_meta( $id, '_emc_newsletter_error', true ) );
	}
	return array( $headers, $rows );
}

function emc_donation_payments_export_data() {
	$headers = array( 'Submitted', 'Amount (GBP)', 'Fund', 'Name', 'Email', 'Address Line 1', 'Postcode', 'Gift Aid', 'Stripe Reference', 'Message' );
	$rows = array();
	foreach ( array_reverse( (array) get_option( 'emc_donations_log', array() ) ) as $record ) {
		if ( ! is_array( $record ) ) continue;
		$rows[] = array( $record['date'] ?? '', $record['amount'] ?? '0.00', $record['fund'] ?? '', $record['name'] ?? 'Anonymous', $record['email'] ?? '', $record['address'] ?? '', $record['postcode'] ?? '', ! empty( $record['gift_aid'] ) ? 'Yes' : 'No', $record['pi_id'] ?? '', $record['message'] ?? '' );
	}
	return array( $headers, $rows );
}

function emc_giving_schedules_export_data() {
	$headers = array( 'Submitted', 'Amount (GBP)', 'Frequency', 'Start Date', 'Occurrences', 'Fund', 'Name', 'Email', 'Address Line 1', 'Postcode', 'Status', 'Stripe Subscription', 'Message' );
	$rows = array();
	foreach ( array_reverse( (array) get_option( 'emc_subscriptions_log', array() ) ) as $record ) {
		if ( ! is_array( $record ) ) continue;
		$rows[] = array( $record['date'] ?? '', $record['amount'] ?? '0.00', $record['frequency'] ?? '', $record['start_date'] ?? '', $record['occurrences'] ?? 'Ongoing', $record['fund'] ?? '', $record['name'] ?? 'Anonymous', $record['email'] ?? '', $record['address'] ?? '', $record['postcode'] ?? '', $record['status'] ?? '', $record['subscription_id'] ?? '', $record['message'] ?? '' );
	}
	return array( $headers, $rows );
}

function emc_form_response_export_data( $type ) {
	$callbacks = array( 'event-registrations' => 'emc_event_registration_export_data', 'contact-messages' => 'emc_contact_export_data', 'volunteer' => 'emc_volunteer_export_data', 'volunteer-signups' => 'emc_volunteer_signup_export_data', 'gift-aid' => 'emc_gift_aid_export_data', 'memberships' => 'emc_membership_export_data', 'newsletter' => 'emc_newsletter_export_data' );
	if ( 'all' !== $type ) return isset( $callbacks[ $type ] ) ? call_user_func( $callbacks[ $type ] ) : array( array(), array() );

	$headers = array( 'Response Type', 'Record ID', 'Submitted', 'Name', 'Email', 'Phone', 'Related Form or Event', 'Status', 'Amount (GBP)', 'Complete Response Details' );
	$rows = array();
	$labels = emc_form_response_export_types();
	$all_sources = $callbacks + array( 'donation-payments' => 'emc_donation_payments_export_data', 'giving-schedules' => 'emc_giving_schedules_export_data' );
	$source_labels = array( 'donation-payments' => __( 'Donation Payments', 'emc-theme' ), 'giving-schedules' => __( 'Giving Schedules', 'emc-theme' ) );
	foreach ( $all_sources as $response_type => $callback ) {
		list( $source_headers, $source_rows ) = call_user_func( $callback );
		foreach ( $source_rows as $source_row ) {
			$record = array_combine( $source_headers, array_slice( array_pad( $source_row, count( $source_headers ), '' ), 0, count( $source_headers ) ) );
			$name = trim( (string) ( $record['Name'] ?? ( ( $record['First Name'] ?? '' ) . ' ' . ( $record['Last Name'] ?? '' ) ) ) );
			$details = array();
			foreach ( $record as $key => $value ) if ( '' !== trim( (string) $value ) ) $details[] = $key . ': ' . $value;
			$response_label = $source_labels[ $response_type ] ?? $labels[ $response_type ][0];
			$rows[] = array( $response_label, $record['Record ID'] ?? $record['Registration ID'] ?? $record['Stripe Reference'] ?? $record['Stripe Subscription'] ?? '', $record['Submitted'] ?? '', $name, $record['Email'] ?? '', $record['Phone'] ?? '', $record['Event'] ?? $record['Subject'] ?? $record['Fund'] ?? $response_label, $record['Payment Status'] ?? $record['Record Status'] ?? $record['Mailchimp Status'] ?? $record['Status'] ?? '', $record['Amount (GBP)'] ?? '', implode( ' | ', $details ) );
		}
	}
	return array( $headers, $rows );
}

function emc_export_form_responses() {
	$type = sanitize_key( wp_unslash( $_GET['type'] ?? '' ) );
	$types = emc_form_response_export_types();
	if ( ! isset( $types[ $type ] ) || ! current_user_can( $types[ $type ][1] ) ) wp_die( esc_html__( 'You do not have permission to export these responses.', 'emc-theme' ), esc_html__( 'Export denied', 'emc-theme' ), array( 'response' => 403 ) );
	check_admin_referer( 'emc_export_form_responses_' . $type );
	list( $headers, $rows ) = emc_form_response_export_data( $type );
	while ( ob_get_level() ) ob_end_clean();
	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="emc-' . sanitize_file_name( $type ) . '-' . wp_date( 'Y-m-d' ) . '.csv"' );
	header( 'X-Content-Type-Options: nosniff' );
	$output = fopen( 'php://output', 'w' );
	if ( false === $output ) wp_die( esc_html__( 'The CSV file could not be created.', 'emc-theme' ) );
	fwrite( $output, "\xEF\xBB\xBF" );
	fputcsv( $output, $headers );
	foreach ( $rows as $row ) fputcsv( $output, array_map( 'emc_theme_donation_csv_safe_value', $row ) );
	fclose( $output );
	exit;
}
add_action( 'admin_post_emc_export_form_responses', 'emc_export_form_responses' );

/** Download button on each individual response screen. */
function emc_form_response_export_screen_action() {
	$page = sanitize_key( wp_unslash( $_GET['page'] ?? '' ) );
	$map = array( 'emc-event-registrations' => 'event-registrations', 'emc-contact-messages' => 'contact-messages', 'emc-job-applications' => 'volunteer', 'emc-volunteer-applications' => 'volunteer-signups', 'emc-gift-aid-declarations' => 'gift-aid', 'emc-newsletter' => 'newsletter' );
	if ( ! isset( $map[ $page ] ) ) return;
	$type = $map[ $page ]; $definition = emc_form_response_export_types()[ $type ];
	if ( ! current_user_can( $definition[1] ) ) return;
	?><div class="notice notice-info inline emc-response-export-notice"><p><strong><?php esc_html_e( 'Export all saved records:', 'emc-theme' ); ?></strong> <a class="button button-secondary" href="<?php echo esc_url( emc_form_response_export_url( $type ) ); ?>"><?php printf( esc_html__( 'Download %s CSV', 'emc-theme' ), esc_html( $definition[0] ) ); ?></a></p></div><?php
}
add_action( 'admin_notices', 'emc_form_response_export_screen_action' );
