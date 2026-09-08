<?php
/**
 * Customer-friendly administration hub.
 *
 * Keeps WordPress core menus intact while moving theme-owned content and
 * operational screens into a documented EMC Website workspace.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/** Theme content types are accessed from Content Manager instead of cluttering the sidebar. */
function emc_organize_post_type_menu( $args, $post_type ) {
	$managed = array(
		'emc_event', 'emc_service', 'emc_team', 'emc_testimonial', 'emc_faq',
		'emc_campaign', 'emc_vacancy', 'emc_volunteer_role', 'emc_portfolio', 'emc_pricing',
		'emc_case_study', 'emc_gallery', 'emc_video', 'emc_badr_tile',
	);
	if ( in_array( $post_type, $managed, true ) ) {
		$args['show_in_menu'] = false;
	}
	return $args;
}
add_filter( 'register_post_type_args', 'emc_organize_post_type_menu', 20, 2 );

/** Cards used throughout the hub. */
function emc_admin_workspace_cards() {
	return array(
		'content' => array(
			array( 'Events', 'Dates, venues, flyers, registration fields and optional ticket payments.', 'dashicons-calendar-alt', admin_url( 'edit.php?post_type=emc_event' ), 'edit_posts' ),
			array( 'Services', 'Mosque services, descriptions, images and display order.', 'dashicons-heart', admin_url( 'edit.php?post_type=emc_service' ), 'edit_posts' ),
			array( 'Team Members', 'Trustees, staff and team profiles.', 'dashicons-groups', admin_url( 'edit.php?post_type=emc_team' ), 'edit_posts' ),
			array( 'Testimonials', 'Community feedback displayed across the website.', 'dashicons-format-quote', admin_url( 'edit.php?post_type=emc_testimonial' ), 'edit_posts' ),
			array( 'FAQs', 'Frequently asked questions and their display order.', 'dashicons-editor-help', admin_url( 'edit.php?post_type=emc_faq' ), 'edit_posts' ),
			array( 'Fundraising Campaigns', 'Manage goals, Stripe fund matching, progress, dates and campaign pages.', 'dashicons-chart-line', admin_url( 'edit.php?post_type=emc_campaign' ), 'edit_posts' ),
			array( 'Job Roles', 'Create paid positions shown in the job application form.', 'dashicons-businessperson', admin_url( 'edit.php?post_type=emc_vacancy' ), 'edit_posts' ),
			array( 'Volunteer Roles', 'Create volunteering opportunities shown in the volunteer form.', 'dashicons-universal-access-alt', admin_url( 'edit.php?post_type=emc_volunteer_role' ), 'edit_posts' ),
			array( 'Projects', 'Community projects and portfolio information.', 'dashicons-building', admin_url( 'edit.php?post_type=emc_portfolio' ), 'edit_posts' ),
			array( 'Programmes', 'Courses, programmes, pricing and schedules.', 'dashicons-tickets-alt', admin_url( 'edit.php?post_type=emc_pricing' ), 'edit_posts' ),
			array( 'Impact Stories', 'Case studies and evidence of community impact.', 'dashicons-star-filled', admin_url( 'edit.php?post_type=emc_case_study' ), 'edit_posts' ),
			array( 'Gallery', 'Photo gallery records and categories.', 'dashicons-format-gallery', admin_url( 'edit.php?post_type=emc_gallery' ), 'upload_files' ),
			array( 'Media Videos', 'YouTube, Vimeo and uploaded videos shown on the Media page.', 'dashicons-video-alt3', admin_url( 'edit.php?post_type=emc_video' ), 'upload_files' ),
			array( 'Badr Wall Tiles', 'Named founder tiles, dedications and moderation status.', 'dashicons-awards', admin_url( 'edit.php?post_type=emc_badr_tile' ), 'edit_posts' ),
		),
		'responses' => array(
			array( 'Donations & Payments', 'Payment history, recurring schedules, detailed records and CSV exports.', 'dashicons-money-alt', admin_url( 'options-general.php?page=emc-donations' ), 'manage_options' ),
			array( 'Event Registrations', 'Attendee answers, ticket totals, payment status and exports.', 'dashicons-clipboard', admin_url( 'admin.php?page=emc-event-registrations' ), 'edit_posts' ),
			array( 'Contact Messages', 'Messages submitted through the public contact form.', 'dashicons-email', admin_url( 'admin.php?page=emc-contact-messages' ), 'manage_options' ),
			array( 'Job Applications', 'Complete job applications, CVs and applicant details.', 'dashicons-businessperson', admin_url( 'admin.php?page=emc-job-applications' ), 'manage_options' ),
			array( 'Volunteer Applications', 'Volunteer interests, availability and applicant details.', 'dashicons-universal-access', admin_url( 'admin.php?page=emc-volunteer-applications' ), 'manage_options' ),
			array( 'Gift Aid Declarations', 'Stored declarations, addresses and confirmation records.', 'dashicons-heart', admin_url( 'admin.php?page=emc-gift-aid-declarations' ), 'manage_options' ),
			array( 'Memberships', 'Member details, categories, membership periods and fees paid.', 'dashicons-id', admin_url( 'admin.php?page=emc-memberships' ), 'manage_options' ),
			array( 'Newsletter Subscribers', 'Newsletter consent records and Mailchimp sync status.', 'dashicons-email-alt2', admin_url( 'admin.php?page=emc-newsletter' ), 'manage_options' ),
		),
		'settings' => array(
			array( 'Ramadan Giving Schedule', 'Set the opening date and time for the fixed 30-day recurring-giving window.', 'dashicons-calendar-alt', admin_url( 'admin.php?page=emc-site-content#emc_ramadan_start_datetime' ), 'manage_options' ),
			array( 'Membership Categories', 'Set the membership categories, fees and wording shown on the Membership page.', 'dashicons-id', admin_url( 'customize.php?autofocus[section]=emc_pg_membership' ), 'edit_theme_options' ),
			array( 'Website Content', 'Edit operational values and searchable website wording.', 'dashicons-edit-page', admin_url( 'admin.php?page=emc-site-content' ), 'manage_options' ),
			array( 'Customizer', 'Logo, colours, fonts, header, footer and homepage section settings.', 'dashicons-admin-customizer', admin_url( 'customize.php' ), 'edit_theme_options' ),
			array( 'Menus', 'Control the header, footer and community navigation links.', 'dashicons-menu', admin_url( 'nav-menus.php' ), 'edit_theme_options' ),
			array( 'Form Notifications', 'Choose recipients and test delivery for every website form.', 'dashicons-email-alt', admin_url( 'admin.php?page=emc-form-notifications' ), 'manage_options' ),
			array( 'Prayer Timetable', 'Upload the annual Masjidbox XLSX timetable and review import status.', 'dashicons-clock', admin_url( 'admin.php?page=emc-prayer-timetable' ), 'manage_options' ),
			array( 'Event Registration Settings', 'Confirmation emails, retention and registration defaults.', 'dashicons-forms', admin_url( 'admin.php?page=emc-event-registration-settings' ), 'manage_options' ),
			array( 'Mailchimp', 'Audience connection, double opt-in and connection testing.', 'dashicons-megaphone', admin_url( 'admin.php?page=emc-newsletter-settings' ), 'manage_options' ),
			array( 'Annual Reports', 'Upload and organise annual report documents.', 'dashicons-media-document', admin_url( 'admin.php?page=emc-annual-reports' ), 'edit_pages' ),
			array( 'Bulk Gallery Upload', 'Upload and categorise multiple gallery images together.', 'dashicons-images-alt2', admin_url( 'admin.php?page=emc-gallery-bulk-upload' ), 'upload_files' ),
		),
	);
}

function emc_admin_organizer_menu() {
	add_menu_page(
		__( 'EMC Website', 'emc-theme' ),
		__( 'EMC Website', 'emc-theme' ),
		'edit_posts',
		'emc-website',
		'emc_admin_dashboard_page',
		'dashicons-admin-home',
		3
	);
	add_submenu_page( 'emc-website', __( 'EMC Dashboard', 'emc-theme' ), __( 'Dashboard', 'emc-theme' ), 'edit_posts', 'emc-website', 'emc_admin_dashboard_page' );
	add_submenu_page( 'emc-website', __( 'Content Manager', 'emc-theme' ), __( 'Content Manager', 'emc-theme' ), 'edit_posts', 'emc-content-manager', 'emc_admin_content_page' );
	add_submenu_page( 'emc-website', __( 'Form Responses', 'emc-theme' ), __( 'Form Responses', 'emc-theme' ), 'edit_posts', 'emc-form-responses', 'emc_admin_responses_page' );
	add_submenu_page( 'emc-website', __( 'Website Settings', 'emc-theme' ), __( 'Website Settings', 'emc-theme' ), 'manage_options', 'emc-website-settings', 'emc_admin_settings_page' );
	add_submenu_page( 'emc-website', __( 'Help & Guide', 'emc-theme' ), __( 'Help & Guide', 'emc-theme' ), 'edit_posts', 'emc-admin-guide', 'emc_admin_guide_page' );
}
add_action( 'admin_menu', 'emc_admin_organizer_menu', 1 );

/** Remove the old payment shortcut from Settings; the screen remains available from Form Responses. */
function emc_admin_remove_legacy_shortcuts() {
	remove_submenu_page( 'options-general.php', 'emc-donations' );
}
add_action( 'admin_menu', 'emc_admin_remove_legacy_shortcuts', 999 );

function emc_admin_render_cards( $cards ) {
	echo '<div class="emc-admin-card-grid">';
	foreach ( $cards as $card ) {
		if ( ! current_user_can( $card[4] ) ) {
			continue;
		}
		printf(
			'<a class="emc-admin-card" href="%1$s"><span class="dashicons %2$s" aria-hidden="true"></span><span><strong>%3$s</strong><small>%4$s</small></span><span class="dashicons dashicons-arrow-right-alt2 emc-card-arrow" aria-hidden="true"></span></a>',
			esc_url( $card[3] ), esc_attr( $card[2] ), esc_html( $card[0] ), esc_html( $card[1] )
		);
	}
	echo '</div>';
}

function emc_admin_page_start( $title, $description ) {
	echo '<div class="wrap emc-admin-workspace"><div class="emc-workspace-heading"><div><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $description ) . '</p></div><a class="button" href="' . esc_url( admin_url( 'admin.php?page=emc-admin-guide' ) ) . '">' . esc_html__( 'Open help guide', 'emc-theme' ) . '</a></div>';
}

function emc_admin_dashboard_page() {
	$cards = emc_admin_workspace_cards();
	emc_admin_page_start( __( 'EMC Website Dashboard', 'emc-theme' ), __( 'A clear starting point for managing website content, form responses and operational settings.', 'emc-theme' ) );
	echo '<div class="emc-admin-intro"><strong>' . esc_html__( 'Recommended workflow', 'emc-theme' ) . '</strong><span>1. Update content</span><span>2. Review form responses</span><span>3. Check payments</span><span>4. Test email notifications</span></div>';
	echo '<h2>' . esc_html__( 'Common tasks', 'emc-theme' ) . '</h2>';
	emc_admin_render_cards( array( $cards['content'][0], $cards['responses'][0], $cards['responses'][2], $cards['settings'][0], $cards['settings'][3], $cards['settings'][4] ) );
	echo '</div>';
}

function emc_admin_content_page() {
	$cards = emc_admin_workspace_cards();
	emc_admin_page_start( __( 'Content Manager', 'emc-theme' ), __( 'Create and update the information visitors see. Each content type has its own list and editor.', 'emc-theme' ) );
	echo '<div class="emc-admin-note"><strong>' . esc_html__( 'Pages and news:', 'emc-theme' ) . '</strong> ' . wp_kses_post( sprintf( __( 'Use the standard WordPress <a href="%1$s">Pages</a> and <a href="%2$s">Posts</a> screens for page content and news articles.', 'emc-theme' ), esc_url( admin_url( 'edit.php?post_type=page' ) ), esc_url( admin_url( 'edit.php' ) ) ) ) . '</div>';
	emc_admin_render_cards( $cards['content'] );
	echo '<div class="emc-admin-taxonomies"><h2>' . esc_html__( 'Categories and grouping', 'emc-theme' ) . '</h2><p>' . esc_html__( 'Use these links to organise records into the groups shown on the public website.', 'emc-theme' ) . '</p><div class="emc-admin-link-list">';
	$taxonomies = array(
		array( 'Event categories', 'event_category', 'emc_event' ),
		array( 'Service categories', 'service_category', 'emc_service' ),
		array( 'Team departments', 'team_department', 'emc_team' ),
		array( 'Testimonial categories', 'testimonial_category', 'emc_testimonial' ),
		array( 'Project categories', 'portfolio_category', 'emc_portfolio' ),
		array( 'Job role types', 'vacancy_type', 'emc_vacancy' ),
		array( 'Volunteer role types', 'volunteer_role_type', 'emc_volunteer_role' ),
		array( 'Gallery categories', 'gallery_category', 'emc_gallery' ),
	);
	foreach ( $taxonomies as $taxonomy ) {
		printf( '<a href="%1$s">%2$s <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></a>', esc_url( admin_url( 'edit-tags.php?taxonomy=' . $taxonomy[1] . '&post_type=' . $taxonomy[2] ) ), esc_html( $taxonomy[0] ) );
	}
	echo '</div></div>';
	echo '</div>';
}

function emc_admin_responses_page() {
	$cards = emc_admin_workspace_cards();
	emc_admin_page_start( __( 'Form Responses', 'emc-theme' ), __( 'Review information submitted by visitors. Notifications are also sent by email according to Form Notification settings.', 'emc-theme' ) );
	emc_admin_render_cards( $cards['responses'] );
	if ( function_exists( 'emc_form_response_export_url' ) ) {
		echo '<section class="emc-export-panel"><div><h2>' . esc_html__( 'Download CSV exports', 'emc-theme' ) . '</h2><p>' . esc_html__( 'Each download contains every saved record and all available form answers, not only the fields visible in the admin tables.', 'emc-theme' ) . '</p></div><div class="emc-export-actions">';
		$exports = array(
			array( 'all', __( 'All Form Responses', 'emc-theme' ), 'manage_options' ),
			array( 'event-registrations', __( 'Event Registrations', 'emc-theme' ), 'edit_posts' ),
			array( 'contact-messages', __( 'Contact Messages', 'emc-theme' ), 'manage_options' ),
			array( 'volunteer', __( 'Job Applications', 'emc-theme' ), 'manage_options' ),
			array( 'volunteer-signups', __( 'Volunteer Applications', 'emc-theme' ), 'manage_options' ),
			array( 'gift-aid', __( 'Gift Aid Declarations', 'emc-theme' ), 'manage_options' ),
			array( 'newsletter', __( 'Newsletter Subscribers', 'emc-theme' ), 'manage_options' ),
		);
		foreach ( $exports as $export ) {
			if ( current_user_can( $export[2] ) ) printf( '<a class="button button-secondary" href="%1$s"><span class="dashicons dashicons-download" aria-hidden="true"></span>%2$s</a>', esc_url( emc_form_response_export_url( $export[0] ) ), esc_html( $export[1] ) );
		}
		if ( current_user_can( 'manage_options' ) ) {
			$payment_url = wp_nonce_url( admin_url( 'admin-post.php?action=emc_theme_export_donation_records_csv&type=payments' ), 'emc_theme_export_donation_records_csv_payments' );
			$schedule_url = wp_nonce_url( admin_url( 'admin-post.php?action=emc_theme_export_donation_records_csv&type=subscriptions' ), 'emc_theme_export_donation_records_csv_subscriptions' );
			printf( '<a class="button button-secondary" href="%1$s"><span class="dashicons dashicons-download" aria-hidden="true"></span>%2$s</a>', esc_url( $payment_url ), esc_html__( 'Donation Payments', 'emc-theme' ) );
			printf( '<a class="button button-secondary" href="%1$s"><span class="dashicons dashicons-download" aria-hidden="true"></span>%2$s</a>', esc_url( $schedule_url ), esc_html__( 'Giving Schedules', 'emc-theme' ) );
		}
		echo '</div></section>';
	}
	echo '</div>';
}

function emc_admin_settings_page() {
	$cards = emc_admin_workspace_cards();
	emc_admin_page_start( __( 'Website Settings', 'emc-theme' ), __( 'Operational tools and appearance controls. Use these screens carefully because changes affect the public website.', 'emc-theme' ) );
	emc_admin_render_cards( $cards['settings'] );
	echo '</div>';
}

function emc_admin_guide_page() {
	emc_admin_page_start( __( 'EMC Website Help & Guide', 'emc-theme' ), __( 'A short guide to the website administration areas and the safest way to make updates.', 'emc-theme' ) );
	?>
	<div class="emc-guide-grid">
		<section><h2>1. Updating visible content</h2><p>Use Content Manager for events, services, team profiles and other structured content. Use Pages for general page articles and Website Content for fixed labels or operational values.</p></section>
		<section><h2>2. Reviewing customer responses</h2><p>Open Form Responses to review registrations, messages, declarations and applications. Records remain stored in WordPress even when an email cannot be delivered.</p></section>
		<section><h2>3. Payments and exports</h2><p>Donations & Payments contains confirmed Stripe payments and giving schedules. Use search, filters, record details and CSV downloads for reporting.</p></section>
		<section><h2>4. Prayer timetable updates</h2><p>Upload the annual Masjidbox XLSX file under Website Settings → Prayer Timetable. Confirm the reported date range before leaving the screen.</p></section>
		<section><h2>5. Email delivery</h2><p>Form Notifications controls administrator recipients. Send a test notification after changing an address. Reliable production delivery requires SMTP.</p></section>
		<section><h2>6. Before publishing</h2><p>Preview important changes, check links and images, avoid deleting historical payment/form records, and keep Stripe or Mailchimp credentials private.</p></section>
	</div>
	<?php echo '</div>';
}

function emc_admin_organizer_assets( $hook ) {
	if ( false === strpos( $hook, 'emc-' ) && 'toplevel_page_emc-website' !== $hook ) {
		return;
	}
	$path = EMC_DIR . '/assets/css/admin-organizer.css';
	wp_enqueue_style( 'emc-admin-organizer', EMC_ASSETS . '/css/admin-organizer.css', array(), file_exists( $path ) ? filemtime( $path ) : EMC_VERSION );
}
add_action( 'admin_enqueue_scripts', 'emc_admin_organizer_assets' );
