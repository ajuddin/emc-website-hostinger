<?php
/**
 * EMC Theme — Page Content Customizer Sections
 * Migrates all emc_acf() page content into the Customizer.
 * @package emc-theme
 */
defined( 'ABSPATH' ) || exit;

function emc_register_page_content_sections( $wp_customize ) {

    /* ── Panel ─────────────────────────────────────────────────────────── */
    $wp_customize->add_panel( 'emc_pages', array(
        'title'       => __( 'Page Content', 'emc-theme' ),
        'priority'    => 40,
        'description' => __( 'Edit text, labels, and content for each page.', 'emc-theme' ),
    ) );

    $p = 10; // priority counter

    /* ================================================================
       ABOUT PAGE
       ================================================================ */
    $wp_customize->add_section( 'emc_pg_about', array(
        'title' => __( 'About Page', 'emc-theme' ), 'panel' => 'emc_pages', 'priority' => $p++,
    ) );
    $about = array(
        'about_hero_title'       => 'Who We Are',
        'about_hero_intro'       => 'Essex Muslim Centre is a registered UK charity dedicated to advancing Islamic faith, education, and community welfare in the heart of Chelmsford, Essex.',
        'about_stat_1_num'       => '2018',
        'about_stat_1_label'     => 'Founded',
        'about_stat_2_num'       => '500+',
        'about_stat_2_label'     => 'Families Served',
        'about_stat_3_num'       => '12',
        'about_stat_3_label'     => 'Programmes',
        'about_stat_4_num'       => '#1209815',
        'about_stat_4_label'     => 'Charity Number',
        'about_mission_subtitle' => 'Our Purpose',
        'about_mission_heading'  => 'Our Mission',
        'about_mission_quote'    => 'Establishing and maintaining places of worship and providing religious education in accordance with the teachings of Islam.',
        'about_value_1_icon'     => 'fas fa-heart',
        'about_value_1_title'    => 'Compassion',
        'about_value_1_desc'     => 'Serving every person with dignity and care.',
        'about_value_2_icon'     => 'fas fa-handshake',
        'about_value_2_title'    => 'Community',
        'about_value_2_desc'     => 'Building bridges across faiths and backgrounds.',
        'about_value_3_icon'     => 'fas fa-book-open',
        'about_value_3_title'    => 'Education',
        'about_value_3_desc'     => 'Empowering through knowledge and faith.',
        'about_value_4_icon'     => 'fas fa-eye',
        'about_value_4_title'    => 'Transparency',
        'about_value_4_desc'     => 'Accountable to our donors and the Charity Commission.',
        'about_trustees_subtitle'=> 'Leadership',
        'about_trustees_heading' => 'Trustees & Team',
        'about_trustees_desc'    => 'Our board of trustees are volunteers committed to the charity\'s mission.',
        'about_trustee_1_name'   => 'Dr Hossain Hadi',
        'about_trustee_1_role'   => 'Trustee and Chairman',
        'about_trustee_1_bio'    => '',
        'about_trustee_2_name'   => 'Dr Nurul Kabir',
        'about_trustee_2_role'   => 'Secretary',
        'about_trustee_2_bio'    => '',
        'about_trustee_3_name'   => 'Shafiqul Islam',
        'about_trustee_3_role'   => 'Vice-Chairman',
        'about_trustee_3_bio'    => '',
        'about_trustee_4_name'   => 'Abu Reaz Alam',
        'about_trustee_4_role'   => 'Treasurer',
        'about_trustee_4_bio'    => '',
        'about_cta_badge'        => 'Join Our Team',
        'about_cta_heading'      => 'Vacancies & Volunteering',
        'about_cta_desc'         => 'Whether you want a paid role or to give your time voluntarily, we always welcome passionate individuals.',
    );
    emc_bulk_text_settings( $wp_customize, $about, 'emc_pg_about' );

    /* ================================================================
       SERVICES PAGE
       ================================================================ */
    $wp_customize->add_section( 'emc_pg_services', array(
        'title' => __( 'Services Page', 'emc-theme' ), 'panel' => 'emc_pages', 'priority' => $p++,
    ) );
    $services = array(
        'svc_hero_badge'          => 'What We Offer',
        'svc_hero_title'          => 'Our Services',
        'svc_hero_desc'           => 'From daily prayers to youth education, health support to spiritual guidance — Essex Muslim Centre provides a broad range of services.',
        'svc_tab_friday'          => 'Friday Prayers',
        'svc_tab_youth'           => 'Youth',
        'svc_tab_reversion'       => 'Reversion',
        'svc_tab_wellbeing'       => 'Wellbeing',
        'svc_friday_subtitle'     => 'Salah',
        'svc_friday_heading'      => 'Friday Prayers (Jumu\'ah)',
        'svc_friday_body_1'       => 'Jumu\'ah is the most important congregational prayer of the week.',
        'svc_friday_body_2'       => 'Our Imams deliver relevant, contemporary Khutbahs.',
        'svc_friday_khutbah_1'    => '12:30 PM',
        'svc_friday_khutbah_2'    => '1:30 PM',
        'svc_friday_gates'        => '12:00 PM',
        'svc_friday_location'     => 'Main Hall',
        'svc_friday_feature_1'    => 'Two congregational Khutbahs each Friday',
        'svc_friday_feature_2'    => 'English and Arabic Khutbahs',
        'svc_friday_feature_3'    => 'Separate sisters\' prayer area',
        'svc_friday_feature_4'    => 'Free parking and wudhu facilities',
        'svc_youth_subtitle'      => 'Next Generation',
        'svc_youth_heading'       => 'Youth Programmes',
        'svc_youth_body'          => 'Our youth provision is at the heart of everything we do.',
        'svc_youth_prog_1_icon'   => 'fas fa-quran',
        'svc_youth_prog_1_title'  => 'Weekend Madrasah',
        'svc_youth_prog_1_desc'   => 'Qur\'an, Arabic, and Islamic Studies for ages 5-16.',
        'svc_youth_prog_2_icon'   => 'fas fa-users',
        'svc_youth_prog_2_title'  => 'Youth Club',
        'svc_youth_prog_2_desc'   => 'Weekly activities, trips, and mentoring for teens.',
        'svc_youth_prog_3_icon'   => 'fas fa-trophy',
        'svc_youth_prog_3_title'  => 'Sports Programme',
        'svc_youth_prog_3_desc'   => 'Football, cricket, and fitness sessions.',
        'svc_reversion_subtitle'  => 'Embrace Islam',
        'svc_reversion_heading'   => 'Reversion to Islam',
        'svc_reversion_body_1'    => 'We provide a warm, judgment-free welcome to all exploring or newly embracing the faith.',
        'svc_reversion_body_2'    => 'Our dedicated reversion team offers one-to-one support.',
        'svc_rev_step_1_title'    => 'Learn',
        'svc_rev_step_1_desc'     => 'Attend our free classes and ask questions in a safe space.',
        'svc_rev_step_2_title'    => 'Embrace',
        'svc_rev_step_2_desc'     => 'Take your Shahada with community support.',
        'svc_rev_step_3_title'    => 'Grow',
        'svc_rev_step_3_desc'     => 'Continue learning with one-to-one mentoring.',
        'svc_wellbeing_subtitle'  => 'Care & Support',
        'svc_wellbeing_heading'   => 'Health & Wellbeing',
        'svc_wellbeing_body'      => 'Our Health & Wellbeing programme brings together NHS professionals and community volunteers.',
        'svc_wb_item_1_icon'      => 'fas fa-brain',
        'svc_wb_item_1_title'     => 'Mental Health Support',
        'svc_wb_item_1_desc'      => 'Confidential counselling and support groups.',
        'svc_wb_item_2_icon'      => 'fas fa-heartbeat',
        'svc_wb_item_2_title'     => 'Health Clinics',
        'svc_wb_item_2_desc'      => 'Free health checks and screenings.',
        'svc_wb_item_3_icon'      => 'fas fa-utensils',
        'svc_wb_item_3_title'     => 'Nutrition Advice',
        'svc_wb_item_3_desc'      => 'Halal dietary guidance and cooking workshops.',
        'svc_cta_heading'         => 'Support Our Services',
        'svc_cta_desc'            => 'Every donation helps us deliver these vital services to our community.',
    );
    emc_bulk_text_settings( $wp_customize, $services, 'emc_pg_services' );

    /* ================================================================
       PRAYER TIMES PAGE
       ================================================================ */
    $wp_customize->add_section( 'emc_pg_prayer', array(
        'title' => __( 'Prayer Times Page', 'emc-theme' ), 'panel' => 'emc_pages', 'priority' => $p++,
    ) );
    $prayer = array(
        'prayer_hero_badge'       => 'Daily Salah',
        'prayer_hero_title'       => 'Prayer Times',
        'prayer_hero_desc'        => 'Essex Muslim Centre, Chelmsford. All times are calculated for our location.',
        'prayer_hijri_date'       => '12 Dhul-Qa\'dah 1447 AH',
        'prayer_widget_label'     => 'Today\'s Prayer Times',
        'prayer_location_label'   => 'Essex Muslim Centre, Cuton Hall Lane, CM2 6PB',
        'prayer_next_label'       => 'Next:',
        'prayer_masjidbox_embed'  => '',
        'prayer_placeholder_embed_note' => 'Live MasjidBox widget embed code will be placed here.',
        'prayer_jumuah_card_heading'    => 'Jumu\'ah (Friday Prayer)',
        'prayer_time_1_name'      => 'Fajr',
        'prayer_time_1_icon'      => 'fas fa-cloud-sun',
        'prayer_time_1_adhan'     => '03:45',
        'prayer_time_1_iqama'     => '04:15',
        'prayer_time_2_name'      => 'Dhuhr',
        'prayer_time_2_icon'      => 'fas fa-sun',
        'prayer_time_2_adhan'     => '13:05',
        'prayer_time_2_iqama'     => '13:30',
        'prayer_time_3_name'      => 'Asr',
        'prayer_time_3_icon'      => 'fas fa-cloud-sun',
        'prayer_time_3_adhan'     => '17:15',
        'prayer_time_3_iqama'     => '17:45',
        'prayer_time_4_name'      => 'Maghrib',
        'prayer_time_4_icon'      => 'fas fa-sunset',
        'prayer_time_4_adhan'     => '20:55',
        'prayer_time_4_iqama'     => '21:00',
        'prayer_time_5_name'      => 'Isha',
        'prayer_time_5_icon'      => 'fas fa-moon',
        'prayer_time_5_adhan'     => '22:30',
        'prayer_time_5_iqama'     => '22:45',
    );
    emc_bulk_text_settings( $wp_customize, $prayer, 'emc_pg_prayer' );

    /* ================================================================
       EVENTS PAGE
       ================================================================ */
    $wp_customize->add_section( 'emc_pg_events', array(
        'title' => __( 'Events Page', 'emc-theme' ), 'panel' => 'emc_pages', 'priority' => $p++,
    ) );
    $events = array(
        'events_hero_badge'       => 'Community Events',
        'events_hero_title'       => 'Upcoming Events',
        'events_hero_desc'        => 'From Friday prayers to youth workshops, fundraising evenings to community fun days.',
        'events_filter_all'       => 'All Events',
        'events_filter_community' => 'Community',
        'events_filter_youth'     => 'Youth',
        'events_filter_fundraising'=> 'Fundraising',
        'events_filter_religious' => 'Religious',
    );
    emc_bulk_text_settings( $wp_customize, $events, 'emc_pg_events' );

    /* ================================================================
       MEDIA PAGE
       ================================================================ */
    $wp_customize->add_section( 'emc_pg_media', array(
        'title' => __( 'Media Page', 'emc-theme' ), 'panel' => 'emc_pages', 'priority' => $p++,
    ) );
    $media = array(
        'media_hero_badge'      => 'Media Hub',
        'media_hero_title'      => 'Media & News',
        'media_hero_desc'       => 'Catch up on the latest Khutbahs, browse photos from recent events, and read our community blog.',
        'media_tab_videos'      => 'Videos & Audio',
        'media_tab_photos'      => 'Photo Gallery',
        'media_tab_news'        => 'News & Blog',
        'media_videos_heading'  => 'Latest Khutbahs & Lectures',
        'media_podcast_heading' => 'Listen on the Go',
        'media_podcast_desc'    => 'All our Friday Khutbahs are available on our weekly podcast.',
        'media_podcast_spotify' => '#',
        'media_podcast_apple'   => '#',
    );
    emc_bulk_text_settings( $wp_customize, $media, 'emc_pg_media' );

    /* ================================================================
       CONTACT PAGE
       ================================================================ */
    $wp_customize->add_section( 'emc_pg_contact', array(
        'title' => __( 'Contact Page', 'emc-theme' ), 'panel' => 'emc_pages', 'priority' => $p++,
    ) );
    $contact = array(
        'contact_hero_badge'          => 'Get in Touch',
        'contact_hero_title'          => 'Contact Us',
        'contact_hero_desc'           => 'We\'re here to help. Whether you have a question about our services, want to volunteer, or need support.',
        'contact_form_heading'        => 'Send a Message',
        'contact_form_desc'           => 'Fill out the form below and a member of our team will get back to you within 48 hours.',
        'contact_label_firstname'     => 'First Name *',
        'contact_placeholder_firstname'=> 'Jane',
        'contact_label_lastname'      => 'Last Name *',
        'contact_placeholder_lastname'=> 'Doe',
        'contact_label_email'         => 'Email Address *',
        'contact_placeholder_email'   => 'jane@example.com',
        'contact_label_subject'       => 'Subject *',
        'contact_select_placeholder'  => 'Select an enquiry type',
        'contact_select_opt1'         => 'General Enquiry',
        'contact_select_opt2'         => 'Services & Programmes',
        'contact_select_opt3'         => 'Donations & Finance',
        'contact_select_opt4'         => 'Reversion to Islam',
        'contact_select_opt5'         => 'Feedback / Complaint',
        'contact_label_message'       => 'Message *',
        'contact_placeholder_message' => 'How can we help you today?',
        'contact_submit_btn'          => 'Send Message',
        'contact_success_title'       => 'Message Sent Successfully',
        'contact_success_desc'        => 'Thank you for reaching out. We will get back to you shortly.',
        'contact_visit_heading'       => 'Visit Us',
        'contact_visit_address'       => "Essex Muslim Centre\nCuton Hall Lane\nCM2 6PB",
        'contact_email_heading'       => 'Email Us',
        'contact_email_hours'         => 'Monitored Monday–Friday, 9am–5pm',
        'contact_hours_heading'       => 'Opening Hours',
        'contact_opening_hours'       => '',
        'contact_hours_centre_label'  => 'Centre:',
        'contact_hours_centre_val'    => 'Open daily for all 5 prayers',
        'contact_hours_office_label'  => 'Office:',
        'contact_hours_office_val'    => 'Mon-Fri, 10am - 4pm',
        'contact_hours_jumuah_label'  => 'Jumu\'ah:',
        'contact_hours_jumuah_val'    => 'Friday, 12:00pm - 2:00pm',
        'contact_map_embed'           => 'https://www.google.com/maps?q=51.745083,0.507917&output=embed',
    );
    emc_bulk_text_settings( $wp_customize, $contact, 'emc_pg_contact' );

    /* ================================================================
       DONATE PAGE
       ================================================================ */
    $wp_customize->add_section( 'emc_pg_donate', array(
        'title' => __( 'Donate Page', 'emc-theme' ), 'panel' => 'emc_pages', 'priority' => $p++,
    ) );
    $donate = array(
        'donate_hero_badge'        => 'Make a Difference',
        'donate_hero_title'        => 'Support Our Centre',
        'donate_hero_desc'         => 'Your generosity funds Friday prayers, youth programmes, reversion support, and vital community welfare.',
        'donate_trust_stripe'      => 'Secured by Stripe',
        'donate_trust_giftaid'     => 'Gift Aid Eligible',
        'donate_tab_oneoff'        => 'One-Off',
        'donate_tab_regular'       => 'Regular',
        'donate_tab_ramadan'       => 'Ramadan',
        'donate_tab_zakat'         => 'Zakat',
        'donate_oneoff_heading'    => 'One-Off Donation',
        'donate_oneoff_desc'       => 'Every amount makes a real difference to our community.',
        'donate_fund_label'        => 'Donation Fund',
        'donate_fund_general'      => 'General Fund',
        'donate_fund_building'     => 'Building Fund',
        'donate_fund_education'    => 'Education',
        'donate_fund_zakat'        => 'Zakat',
        'donate_fund_sadaqah'      => 'Sadaqah',
        'donate_fund_lillah'       => 'Lillah',
        'donate_giftaid_heading'   => 'Claim Gift Aid',
        'donate_giftaid_text'      => 'I am a UK taxpayer. EMC can reclaim 25p of tax on every £1 I give.',
        'donate_oneoff_btn'        => 'Donate Securely',
        'donate_secure_note'       => 'Encrypted & secured by Stripe. Your card details are never stored.',
        'donate_regular_heading'   => 'Regular Donation',
        'donate_regular_desc'      => 'Set up a recurring gift to provide ongoing support.',
        'donate_regular_giftaid_text'=> 'I am a UK taxpayer. EMC can reclaim 25p of tax on every £1 I give at no extra cost.',
        'donate_regular_btn'       => 'Set Up Monthly Giving',
        'donate_portal_url'        => '#',
        'donate_portal_text'       => 'Already a regular donor? Access your Donor Portal.',
        'donate_ramadan_badge'     => 'Ramadan 1447 AH',
        'donate_ramadan_heading'   => 'Ramadan Daily Giving',
        'donate_ramadan_desc'      => 'Schedule daily automatic donations for the blessed month.',
        'donate_ramadan_period_full'=> 'Full Ramadan',
        'donate_ramadan_period_10' => 'Last 10 Nights',
        'donate_ramadan_period_odd'=> 'Odd Nights',
        'donate_ramadan_btn'       => 'Schedule Ramadan Giving',
        'donate_zakat_heading'     => 'Zakat Calculator',
        'donate_zakat_nisab'       => 'Nisab (Silver): £452.06 | Zakat rate: 2.5%',
        'donate_zakat_label_cash'  => 'Cash & Bank Savings (£)',
        'donate_zakat_label_gold'  => 'Gold & Silver Value (£)',
        'donate_zakat_label_biz'   => 'Business / Trade Assets (£)',
        'donate_zakat_label_owed'  => 'Money Owed to You (£)',
        'donate_zakat_label_deduct'=> 'Money You Owe (£) — Deduct',
        'donate_zakat_result_label'=> 'Your Estimated Zakat',
        'donate_zakat_btn'         => 'Donate My Zakat',
        'donate_impact_heading'    => 'Your Impact',
        'donate_impact_1_amount'   => '£5',
        'donate_impact_1_desc'     => 'Provides meals for one family',
        'donate_impact_2_amount'   => '£25',
        'donate_impact_2_desc'     => 'Funds one youth session',
        'donate_impact_3_amount'   => '£50',
        'donate_impact_3_desc'     => 'Supports a reversion welcome pack',
        'donate_impact_4_amount'   => '£100',
        'donate_impact_4_desc'     => 'Covers a week of utilities',
        'donate_campaign_sidebar_heading'=> 'Building Campaign',
        'donate_campaign_name'     => 'Be One of the 313',
        'donate_campaign_percent'  => '63',
        'donate_campaign_raised'   => '£62,500',
        'donate_campaign_goal'     => '£100,000',
        'donate_campaign_btn'      => 'View Campaign',
        'donate_trust_badge_stripe'=> 'Stripe Secured',
        'donate_trust_badge_gdpr'  => 'GDPR Compliant',
        'donate_trust_badge_ga'    => 'Gift Aid Registered',
    );
    emc_bulk_text_settings( $wp_customize, $donate, 'emc_pg_donate' );
}
add_action( 'customize_register', 'emc_register_page_content_sections' );

/**
 * Apply the confirmed trustee roster once on existing installations.
 *
 * The About template reads saved Customizer values before its PHP defaults,
 * so updating defaults alone would leave old placeholder names visible.
 */
function emc_update_confirmed_trustee_roster() {
    $version = '2026-07-25';
    if ( $version === get_option( 'emc_trustee_roster_version' ) ) {
        return;
    }

    $trustees = array(
        1 => array( 'name' => 'Dr Hossain Hadi', 'role' => 'Trustee and Chairman' ),
        2 => array( 'name' => 'Dr Nurul Kabir', 'role' => 'Secretary' ),
        3 => array( 'name' => 'Shafiqul Islam', 'role' => 'Vice-Chairman' ),
        4 => array( 'name' => 'Abu Reaz Alam', 'role' => 'Treasurer' ),
    );

    foreach ( $trustees as $number => $trustee ) {
        set_theme_mod( 'about_trustee_' . $number . '_name', $trustee['name'] );
        set_theme_mod( 'about_trustee_' . $number . '_role', $trustee['role'] );
        remove_theme_mod( 'about_trustee_' . $number . '_bio' );
    }

    for ( $number = 5; $number <= 6; $number++ ) {
        remove_theme_mod( 'about_trustee_' . $number . '_name' );
        remove_theme_mod( 'about_trustee_' . $number . '_role' );
        remove_theme_mod( 'about_trustee_' . $number . '_bio' );
    }

    update_option( 'emc_trustee_roster_version', $version, false );
}
add_action( 'after_setup_theme', 'emc_update_confirmed_trustee_roster', 20 );

/**
 * Bulk-register text settings from an associative array.
 */
function emc_bulk_text_settings( $wp_customize, $fields, $section ) {
    foreach ( $fields as $id => $default ) {
        $label = ucwords( str_replace( '_', ' ', preg_replace( '/^[a-z]+_/', '', $id ) ) );
        $wp_customize->add_setting( $id, array(
            'default'           => $default,
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( $id, array(
            'label'   => $label,
            'section' => $section,
            'type'    => strlen( $default ) > 80 ? 'textarea' : 'text',
        ) );
    }
}
