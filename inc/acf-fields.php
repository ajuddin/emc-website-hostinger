<?php
/**
 * EMC Theme — inc/acf-fields.php
 * Registers all ACF field groups programmatically so they ship with the theme.
 * Uses fixed numbered groups (ACF Free compatible — no Repeater required).
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register all ACF field groups on init.
 */
function emc_register_acf_fields() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    /* ======================================================================
       ABOUT PAGE
       ====================================================================== */
    acf_add_local_field_group( array(
        'key'      => 'group_emc_about',
        'title'    => 'About Page Content',
        'location' => array( array( array(
            'param'    => 'page_template',
            'operator' => '==',
            'value'    => 'template-about.php',
        ) ) ),
        'menu_order' => 0,
        'position'   => 'normal',
        'style'      => 'default',
        'fields'     => array_merge(
            emc_acf_section( 'About Hero', 'about_hero', array(
                emc_acf_text( 'about_hero_title', 'Hero Title', 'Who We Are' ),
                emc_acf_textarea( 'about_hero_intro', 'Hero Introduction', 'Essex Muslim Centre is a registered UK charity dedicated to advancing Islamic faith, education, and community welfare in the heart of Chelmsford, Essex.' ),
                emc_acf_image_field( 'about_hero_image', 'Hero Image' ),
            ) ),
            emc_acf_section( 'Hero Stats', 'about_stats', array(
                emc_acf_text( 'about_stat_1_num', 'Stat 1 Number', '2018' ),
                emc_acf_text( 'about_stat_1_label', 'Stat 1 Label', 'Founded' ),
                emc_acf_text( 'about_stat_2_num', 'Stat 2 Number', '500+' ),
                emc_acf_text( 'about_stat_2_label', 'Stat 2 Label', 'Families Served' ),
                emc_acf_text( 'about_stat_3_num', 'Stat 3 Number', '12' ),
                emc_acf_text( 'about_stat_3_label', 'Stat 3 Label', 'Programmes' ),
                emc_acf_text( 'about_stat_4_num', 'Stat 4 Number', '#1209815' ),
                emc_acf_text( 'about_stat_4_label', 'Stat 4 Label', 'Charity Number' ),
            ) ),
            emc_acf_section( 'Mission Section', 'about_mission', array(
                emc_acf_text( 'about_mission_subtitle', 'Section Subtitle', 'Our Purpose' ),
                emc_acf_text( 'about_mission_heading', 'Section Heading', 'Our Mission' ),
                emc_acf_textarea( 'about_mission_quote', 'Mission Quote', 'Establishing and maintaining places of worship and providing religious education in accordance with the teachings of Islam.' ),
                emc_acf_wysiwyg( 'about_mission_body', 'Mission Body Text' ),
                emc_acf_image_field( 'about_mission_image', 'Mission Section Image' ),
            ) ),
            emc_acf_section( 'Values', 'about_values', emc_acf_numbered_items( 'about_value', 4, array(
                'icon'  => array( 'Icon Class', 'fas fa-heart' ),
                'title' => array( 'Title', 'Value' ),
                'desc'  => array( 'Description', '' ),
            ) ) ),
            emc_acf_section( 'Trustees', 'about_trustees', array_merge(
                array(
                    emc_acf_text( 'about_trustees_subtitle', 'Section Subtitle', 'Leadership' ),
                    emc_acf_text( 'about_trustees_heading', 'Section Heading', 'Trustees & Team' ),
                    emc_acf_textarea( 'about_trustees_desc', 'Section Description', '' ),
                ),
                emc_acf_numbered_items( 'about_trustee', 4, array(
                    'name' => array( 'Name', '' ),
                    'role' => array( 'Role / Title', '' ),
                    'bio'  => array( 'Short Bio', '' ),
                    'image' => array( 'Photo', '', 'image' ),
                ) )
            ) ),
            emc_acf_section( 'Vacancies CTA', 'about_cta', array(
                emc_acf_text( 'about_cta_badge', 'Badge Text', 'Join Our Team' ),
                emc_acf_text( 'about_cta_heading', 'Heading', 'Vacancies & Volunteering' ),
                emc_acf_textarea( 'about_cta_desc', 'Description', '' ),
            ) )
        ),
    ) );

    /* ======================================================================
       SERVICES PAGE
       ====================================================================== */
    acf_add_local_field_group( array(
        'key'      => 'group_emc_services',
        'title'    => 'Services Page Content',
        'location' => array( array( array(
            'param'    => 'page_template',
            'operator' => '==',
            'value'    => 'template-services.php',
        ) ) ),
        'menu_order' => 0,
        'fields'     => array_merge(
            emc_acf_section( 'Services Hero', 'svc_hero', array(
                emc_acf_text( 'svc_hero_badge', 'Badge Text', 'What We Offer' ),
                emc_acf_text( 'svc_hero_title', 'Hero Title', 'Our Services' ),
                emc_acf_textarea( 'svc_hero_desc', 'Hero Description', '' ),
            ) ),
            emc_acf_section( 'Tab Navigation', 'svc_tabs', array(
                emc_acf_text( 'svc_tab_friday',    'Tab 1: Friday Prayers', 'Friday Prayers' ),
                emc_acf_text( 'svc_tab_youth',     'Tab 2: Youth', 'Youth' ),
                emc_acf_text( 'svc_tab_reversion', 'Tab 3: Reversion', 'Reversion' ),
                emc_acf_text( 'svc_tab_wellbeing', 'Tab 4: Wellbeing', 'Wellbeing' ),
            ) ),
            emc_acf_section( 'Friday Prayers', 'svc_friday', array(
                emc_acf_text( 'svc_friday_subtitle', 'Subtitle', 'Salah' ),
                emc_acf_text( 'svc_friday_heading', 'Heading', 'Friday Prayers (Jumu\'ah)' ),
                emc_acf_textarea( 'svc_friday_body_1', 'Paragraph 1', '' ),
                emc_acf_textarea( 'svc_friday_body_2', 'Paragraph 2', '' ),
                emc_acf_text( 'svc_friday_khutbah_1', 'First Khutbah Time', '12:30 PM' ),
                emc_acf_text( 'svc_friday_khutbah_2', 'Second Khutbah Time', '1:30 PM' ),
                emc_acf_text( 'svc_friday_gates', 'Gates Open Time', '12:00 PM' ),
                emc_acf_text( 'svc_friday_location', 'Location', 'Main Hall' ),
                emc_acf_text( 'svc_friday_feature_1', 'Feature 1', 'Two Jumu\'ah sessions every Friday' ),
                emc_acf_text( 'svc_friday_feature_2', 'Feature 2', 'Separate sisters\' prayer area' ),
                emc_acf_text( 'svc_friday_feature_3', 'Feature 3', 'Wudu (ablution) facilities available' ),
                emc_acf_text( 'svc_friday_feature_4', 'Feature 4', 'Accessible for wheelchair users' ),
                emc_acf_text( 'svc_friday_feature_5', 'Feature 5', 'Free parking nearby' ),
                emc_acf_text( 'svc_friday_feature_6', 'Feature 6', 'Daily five prayers (contact for timings)' ),
                emc_acf_image_field( 'svc_friday_image', 'Section Image' ),
            ) ),
            emc_acf_section( 'Youth Programmes', 'svc_youth', array(
                emc_acf_text( 'svc_youth_subtitle', 'Subtitle', 'Next Generation' ),
                emc_acf_text( 'svc_youth_heading', 'Heading', 'Youth Programmes' ),
                emc_acf_textarea( 'svc_youth_body', 'Body Text', '' ),
            ) ),
            emc_acf_section( 'Youth Programme Cards', 'svc_youth_cards', emc_acf_numbered_items( 'svc_youth_prog', 4, array(
                'icon'  => array( 'Icon Class', 'fas fa-book' ),
                'title' => array( 'Title', '' ),
                'desc'  => array( 'Description', '' ),
            ) ) ),
            emc_acf_section( 'Reversion', 'svc_reversion', array(
                emc_acf_text( 'svc_reversion_subtitle', 'Subtitle', 'Embrace Islam' ),
                emc_acf_text( 'svc_reversion_heading', 'Heading', 'Reversion to Islam' ),
                emc_acf_textarea( 'svc_reversion_body_1', 'Paragraph 1', '' ),
                emc_acf_textarea( 'svc_reversion_body_2', 'Paragraph 2', '' ),
            ) ),
            emc_acf_section( 'Reversion Steps', 'svc_rev_steps', emc_acf_numbered_items( 'svc_rev_step', 4, array(
                'title' => array( 'Step Title', '' ),
                'desc'  => array( 'Step Description', '' ),
            ) ) ),
            emc_acf_section( 'Health & Wellbeing', 'svc_wellbeing', array(
                emc_acf_text( 'svc_wellbeing_subtitle', 'Subtitle', 'Care & Support' ),
                emc_acf_text( 'svc_wellbeing_heading', 'Heading', 'Health & Wellbeing' ),
                emc_acf_textarea( 'svc_wellbeing_body', 'Body Text', '' ),
            ) ),
            emc_acf_section( 'Wellbeing Items', 'svc_wb_items', emc_acf_numbered_items( 'svc_wb_item', 4, array(
                'icon'  => array( 'Icon Class', 'fas fa-stethoscope' ),
                'title' => array( 'Title', '' ),
                'desc'  => array( 'Description', '' ),
            ) ) ),
            emc_acf_section( 'Services CTA', 'svc_cta', array(
                emc_acf_text( 'svc_cta_heading', 'CTA Heading', 'Support Our Services' ),
                emc_acf_textarea( 'svc_cta_desc', 'CTA Description', '' ),
            ) )
        ),
    ) );

    /* ======================================================================
       EVENTS PAGE
       ====================================================================== */
    acf_add_local_field_group( array(
        'key'      => 'group_emc_events',
        'title'    => 'Events Page Content',
        'location' => array( array( array(
            'param'    => 'page_template',
            'operator' => '==',
            'value'    => 'template-events.php',
        ) ) ),
        'menu_order' => 0,
        'fields'     => array_merge(
            emc_acf_section( 'Events Hero', 'events_hero', array(
                emc_acf_text( 'events_hero_badge', 'Badge Text', 'Community Events' ),
                emc_acf_text( 'events_hero_title', 'Hero Title', 'Upcoming Events' ),
                emc_acf_textarea( 'events_hero_desc', 'Hero Description', '' ),
            ) ),
            emc_acf_section( 'Filter Labels', 'events_filters', array(
                emc_acf_text( 'events_filter_all',         'Filter: All Events',  'All Events' ),
                emc_acf_text( 'events_filter_community',   'Filter: Community',   'Community' ),
                emc_acf_text( 'events_filter_youth',       'Filter: Youth',       'Youth' ),
                emc_acf_text( 'events_filter_fundraising', 'Filter: Fundraising', 'Fundraising' ),
                emc_acf_text( 'events_filter_religious',   'Filter: Religious',   'Religious' ),
            ) )
        ),
    ) );

    /* ======================================================================
       MEDIA PAGE
       ====================================================================== */
    acf_add_local_field_group( array(
        'key'      => 'group_emc_media',
        'title'    => 'Media Page Content',
        'location' => array( array( array(
            'param'    => 'page_template',
            'operator' => '==',
            'value'    => 'template-media.php',
        ) ) ),
        'menu_order' => 0,
        'fields'     => array_merge(
            emc_acf_section( 'Media Hero', 'media_hero', array(
                emc_acf_text( 'media_hero_badge', 'Badge Text', 'Media Hub' ),
                emc_acf_text( 'media_hero_title', 'Hero Title', 'Media & News' ),
                emc_acf_textarea( 'media_hero_desc', 'Hero Description', '' ),
            ) ),
            emc_acf_section( 'Tab Labels', 'media_tabs', array(
                emc_acf_text( 'media_tab_videos', 'Tab: Videos & Audio', 'Videos & Audio' ),
                emc_acf_text( 'media_tab_photos', 'Tab: Photo Gallery', 'Photo Gallery' ),
                emc_acf_text( 'media_tab_news',   'Tab: News & Blog',   'News & Blog' ),
                emc_acf_text( 'media_videos_heading', 'Videos Section Heading', 'Latest Khutbahs & Lectures' ),
            ) ),
            emc_acf_section( 'Podcast', 'media_podcast', array(
                emc_acf_text( 'media_podcast_heading', 'Heading', 'Listen on the Go' ),
                emc_acf_textarea( 'media_podcast_desc', 'Description', '' ),
                emc_acf_text( 'media_podcast_spotify', 'Spotify URL', '' ),
                emc_acf_text( 'media_podcast_apple', 'Apple Podcasts URL', '' ),
            ) )
        ),
    ) );

    /* ======================================================================
       CONTACT PAGE
       ====================================================================== */
    acf_add_local_field_group( array(
        'key'      => 'group_emc_contact',
        'title'    => 'Contact Page Content',
        'location' => array( array( array(
            'param'    => 'page_template',
            'operator' => '==',
            'value'    => 'template-contact.php',
        ) ) ),
        'menu_order' => 0,
        'fields'     => array_merge(
            emc_acf_section( 'Contact Hero', 'contact_hero', array(
                emc_acf_text( 'contact_hero_badge', 'Badge Text', 'Get in Touch' ),
                emc_acf_text( 'contact_hero_title', 'Hero Title', 'Contact Us' ),
                emc_acf_textarea( 'contact_hero_desc', 'Hero Description', '' ),
            ) ),
            emc_acf_section( 'Contact Form', 'contact_form', array(
                emc_acf_text( 'contact_form_heading', 'Form Heading', 'Send a Message' ),
                emc_acf_textarea( 'contact_form_desc', 'Form Description', 'Fill out the form below and a member of our team will get back to you within 48 hours.' ),
                emc_acf_text( 'contact_label_firstname', 'Label: First Name', 'First Name *' ),
                emc_acf_text( 'contact_placeholder_firstname', 'Placeholder: First Name', 'Jane' ),
                emc_acf_text( 'contact_label_lastname', 'Label: Last Name', 'Last Name *' ),
                emc_acf_text( 'contact_placeholder_lastname', 'Placeholder: Last Name', 'Doe' ),
                emc_acf_text( 'contact_label_email', 'Label: Email', 'Email Address *' ),
                emc_acf_text( 'contact_placeholder_email', 'Placeholder: Email', 'jane@example.com' ),
                emc_acf_text( 'contact_label_subject', 'Label: Subject', 'Subject *' ),
                emc_acf_text( 'contact_select_placeholder', 'Select: Default Option', 'Select an enquiry type' ),
                emc_acf_text( 'contact_select_opt1', 'Select Option 1', 'General Enquiry' ),
                emc_acf_text( 'contact_select_opt2', 'Select Option 2', 'Services & Programmes' ),
                emc_acf_text( 'contact_select_opt3', 'Select Option 3', 'Donations & Finance' ),
                emc_acf_text( 'contact_select_opt4', 'Select Option 4', 'Reversion to Islam' ),
                emc_acf_text( 'contact_select_opt5', 'Select Option 5', 'Feedback / Complaint' ),
                emc_acf_text( 'contact_label_message', 'Label: Message', 'Message *' ),
                emc_acf_text( 'contact_placeholder_message', 'Placeholder: Message', 'How can we help you today?' ),
                emc_acf_text( 'contact_submit_btn', 'Submit Button Text', 'Send Message' ),
                emc_acf_text( 'contact_success_title', 'Success Title', 'Message Sent Successfully' ),
                emc_acf_text( 'contact_success_desc', 'Success Description', 'Thank you for reaching out. We will get back to you shortly.' ),
            ) ),
            emc_acf_section( 'Contact Info', 'contact_info', array(
                emc_acf_text( 'contact_visit_heading', 'Visit Us Heading', 'Visit Us' ),
                emc_acf_textarea( 'contact_visit_address', 'Full Address (overrides default)', '' ),
                emc_acf_text( 'contact_email_heading', 'Email Heading', 'Email Us' ),
                emc_acf_text( 'contact_email_hours', 'Email Monitored Hours', 'Monitored Monday–Friday, 9am–5pm' ),
                emc_acf_text( 'contact_hours_heading', 'Hours Heading', 'Opening Hours' ),
                emc_acf_textarea( 'contact_opening_hours', 'Opening Hours (overrides defaults below)', '' ),
                emc_acf_text( 'contact_hours_centre_label', 'Hours Label: Centre', 'Centre:' ),
                emc_acf_text( 'contact_hours_centre_val', 'Hours Value: Centre', 'Open daily for all 5 prayers' ),
                emc_acf_text( 'contact_hours_office_label', 'Hours Label: Office', 'Office:' ),
                emc_acf_text( 'contact_hours_office_val', 'Hours Value: Office', 'Mon-Fri, 10am - 4pm' ),
                emc_acf_text( 'contact_hours_jumuah_label', "Hours Label: Jumu'ah", "Jumu'ah:" ),
                emc_acf_text( 'contact_hours_jumuah_val', "Hours Value: Jumu'ah", 'Friday, 12:00pm - 2:00pm' ),
            ) ),
            emc_acf_section( 'Map', 'contact_map', array(
                emc_acf_textarea( 'contact_map_embed', 'Google Maps Embed URL', '' ),
            ) )
        ),
    ) );

    /* ======================================================================
       DONATE PAGE
       ====================================================================== */
    acf_add_local_field_group( array(
        'key'      => 'group_emc_donate',
        'title'    => 'Donate Page Content',
        'location' => array( array( array(
            'param'    => 'page_template',
            'operator' => '==',
            'value'    => 'template-donate.php',
        ) ) ),
        'menu_order' => 0,
        'fields'     => array_merge(
            emc_acf_section( 'Hero', 'donate_hero', array(
                emc_acf_text( 'donate_hero_badge', 'Badge Text', 'Make a Difference' ),
                emc_acf_text( 'donate_hero_title', 'Hero Title', 'Support Our Centre' ),
                emc_acf_textarea( 'donate_hero_desc', 'Hero Description', 'Your generosity funds Friday prayers, youth programmes, reversion support, and vital community welfare in Chelmsford.' ),
                emc_acf_text( 'donate_trust_stripe', 'Trust Signal 1 (Stripe)', 'Secured by Stripe' ),
                emc_acf_text( 'donate_trust_giftaid', 'Trust Signal 2 (Gift Aid)', 'Gift Aid Eligible' ),
            ) ),
            emc_acf_section( 'Tab Labels', 'donate_tabs', array(
                emc_acf_text( 'donate_tab_oneoff', 'Tab: One-Off Label', 'One-Off' ),
                emc_acf_text( 'donate_tab_regular', 'Tab: Regular Label', 'Regular' ),
                emc_acf_text( 'donate_tab_ramadan', 'Tab: Ramadan Label', 'Ramadan' ),
                emc_acf_text( 'donate_tab_zakat', 'Tab: Zakat Label', 'Zakat' ),
            ) ),
            emc_acf_section( 'One-Off Tab', 'donate_oneoff', array(
                emc_acf_text( 'donate_oneoff_heading', 'Heading', 'One-Off Donation' ),
                emc_acf_text( 'donate_oneoff_desc', 'Description', 'Every amount makes a real difference to our community.' ),
                emc_acf_text( 'donate_oneoff_amounts', 'Quick Amounts (comma-separated)', '£5,£10,£25,£50,£100' ),
                emc_acf_text( 'donate_fund_label', 'Fund Selector Label', 'Donation Fund' ),
                emc_acf_text( 'donate_fund_general', 'Fund: General', 'General Fund' ),
                emc_acf_text( 'donate_fund_building', 'Fund: Building', 'Building Fund' ),
                emc_acf_text( 'donate_fund_education', 'Fund: Education', 'Education' ),
                emc_acf_text( 'donate_fund_zakat', 'Fund: Zakat', 'Zakat' ),
                emc_acf_text( 'donate_fund_sadaqah', 'Fund: Sadaqah', 'Sadaqah' ),
                emc_acf_text( 'donate_fund_lillah', 'Fund: Lillah', 'Lillah' ),
                emc_acf_text( 'donate_giftaid_heading', 'Gift Aid Heading', 'Claim Gift Aid' ),
                emc_acf_textarea( 'donate_giftaid_text', 'Gift Aid Declaration Text', 'I am a UK taxpayer and understand that if I pay less Income Tax / Capital Gains Tax than the amount of Gift Aid claimed on all my donations, it is my responsibility to pay any difference. EMC can reclaim 25p of tax on every £1 I give.' ),
                emc_acf_text( 'donate_oneoff_btn', 'Submit Button Text', 'Donate Securely' ),
                emc_acf_text( 'donate_secure_note', 'Security Note', 'Encrypted & secured by Stripe. Your card details are never stored on our servers.' ),
            ) ),
            emc_acf_section( 'Regular Tab', 'donate_regular', array(
                emc_acf_text( 'donate_regular_heading', 'Heading', 'Regular Donation' ),
                emc_acf_text( 'donate_regular_desc', 'Description', 'Set up a recurring gift to provide ongoing support to the community.' ),
                emc_acf_text( 'donate_regular_amounts', 'Quick Amounts (comma-separated)', '£5,£10,£20,£50' ),
                emc_acf_text( 'donate_regular_giftaid_text', 'Gift Aid Short Text', 'I am a UK taxpayer. EMC can reclaim 25p of tax on every £1 I give at no extra cost to me.' ),
                emc_acf_text( 'donate_regular_btn', 'Submit Button Text', 'Set Up Monthly Giving' ),
                emc_acf_text( 'donate_portal_text', 'Donor Portal Text', 'Already a regular donor? Access your Donor Portal to view, pause, or cancel your giving schedule.' ),
                emc_acf_text( 'donate_portal_url', 'Donor Portal URL', '#' ),
            ) ),
            emc_acf_section( 'Ramadan Tab', 'donate_ramadan', array(
                emc_acf_text( 'donate_ramadan_badge', 'Ramadan Badge', 'Ramadan 1447 AH' ),
                emc_acf_text( 'donate_ramadan_heading', 'Heading', 'Ramadan Daily Giving' ),
                emc_acf_text( 'donate_ramadan_desc', 'Description', 'Schedule daily automatic donations for the blessed month of Ramadan.' ),
                emc_acf_text( 'donate_ramadan_amounts', 'Quick Amounts (comma-separated)', '£1,£3,£5,£10' ),
                emc_acf_text( 'donate_ramadan_btn', 'Submit Button Text', 'Schedule Ramadan Giving' ),
                emc_acf_text( 'donate_ramadan_period_full', 'Period: Full Ramadan', 'Full Ramadan' ),
                emc_acf_text( 'donate_ramadan_period_10', 'Period: Last 10 Nights', 'Last 10 Nights' ),
                emc_acf_text( 'donate_ramadan_period_odd', 'Period: Odd Nights', 'Odd Nights' ),
            ) ),
            emc_acf_section( 'Zakat Tab', 'donate_zakat', array(
                emc_acf_text( 'donate_zakat_heading', 'Heading', 'Zakat Calculator' ),
                emc_acf_text( 'donate_zakat_nisab', 'Nisab Threshold Text', 'Nisab (Silver): £452.06 | Zakat rate: 2.5%' ),
                emc_acf_text( 'donate_zakat_label_cash', 'Label: Cash & Savings', 'Cash & Bank Savings (£)' ),
                emc_acf_text( 'donate_zakat_label_gold', 'Label: Gold & Silver', 'Gold & Silver Value (£)' ),
                emc_acf_text( 'donate_zakat_label_biz', 'Label: Business Assets', 'Business / Trade Assets (£)' ),
                emc_acf_text( 'donate_zakat_label_owed', 'Label: Money Owed To You', 'Money Owed to You (£)' ),
                emc_acf_text( 'donate_zakat_label_deduct', 'Label: Money You Owe', 'Money You Owe (£) — Deduct' ),
                emc_acf_text( 'donate_zakat_result_label', 'Result Label', 'Your Estimated Zakat' ),
                emc_acf_text( 'donate_zakat_btn', 'Submit Button Text', 'Donate My Zakat' ),
            ) ),
            emc_acf_section( 'Impact Stats Sidebar', 'donate_impact', array(
                emc_acf_text( 'donate_impact_heading', 'Sidebar Heading', 'Your Impact' ),
                emc_acf_text( 'donate_impact_1_amount', 'Impact 1 Amount', '£5' ),
                emc_acf_text( 'donate_impact_1_desc',   'Impact 1 Description', 'Covers Friday prayer costs for one family' ),
                emc_acf_text( 'donate_impact_2_amount', 'Impact 2 Amount', '£25' ),
                emc_acf_text( 'donate_impact_2_desc',   'Impact 2 Description', 'Supports a youth programme session' ),
                emc_acf_text( 'donate_impact_3_amount', 'Impact 3 Amount', '£50' ),
                emc_acf_text( 'donate_impact_3_desc',   'Impact 3 Description', 'Helps a family in financial hardship' ),
                emc_acf_text( 'donate_impact_4_amount', 'Impact 4 Amount', '£100' ),
                emc_acf_text( 'donate_impact_4_desc',   'Impact 4 Description', 'Sponsors a reversion to Islam class' ),
            ) ),
            emc_acf_section( 'Campaign Sidebar', 'donate_campaign', array(
                emc_acf_text( 'donate_campaign_sidebar_heading', 'Sidebar Heading', 'Building Campaign' ),
                emc_acf_text( 'donate_campaign_name', 'Campaign Name', 'Be One of the 313' ),
                emc_acf_text( 'donate_campaign_raised', 'Amount Raised', '£62,500' ),
                emc_acf_text( 'donate_campaign_goal', 'Goal Amount', '£100,000' ),
                emc_acf_text( 'donate_campaign_percent', 'Progress % (number only)', '63' ),
                emc_acf_text( 'donate_campaign_btn', 'View Campaign Button', 'View Campaign' ),
            ) ),
            emc_acf_section( 'Trust Badges', 'donate_trust', array(
                emc_acf_text( 'donate_trust_badge_stripe', 'Badge: Stripe', 'Stripe Secured' ),
                emc_acf_text( 'donate_trust_badge_gdpr', 'Badge: GDPR', 'GDPR Compliant' ),
                emc_acf_text( 'donate_trust_badge_ga', 'Badge: Gift Aid', 'Gift Aid Registered' ),
            ) )
        ),
    ) );

    /* ======================================================================
       PRAYER TIMES PAGE
       ====================================================================== */
    acf_add_local_field_group( array(
        'key'      => 'group_emc_prayer',
        'title'    => 'Prayer Times Page Content',
        'location' => array( array( array(
            'param'    => 'page_template',
            'operator' => '==',
            'value'    => 'template-prayer-times.php',
        ) ) ),
        'menu_order' => 0,
        'fields'     => array_merge(
            emc_acf_section( 'Prayer Hero', 'prayer_hero', array(
                emc_acf_text( 'prayer_hero_badge', 'Badge Text', 'Daily Salah' ),
                emc_acf_text( 'prayer_hero_title', 'Hero Title', 'Prayer Times' ),
                emc_acf_textarea( 'prayer_hero_desc', 'Hero Description', 'Essex Muslim Centre, Chelmsford. All times are calculated for our location. Please check regularly as times change throughout the year.' ),
                emc_acf_text( 'prayer_hijri_date', 'Hijri Date (manual)', '' ),
                emc_acf_text( 'prayer_location_label', 'Location Label', 'Chelmsford, Essex' ),
                emc_acf_text( 'prayer_widget_label', "Widget Label 'Today's Prayer Times'", "Today's Prayer Times" ),
                emc_acf_text( 'prayer_next_label', 'Next Prayer Label', 'Next:' ),
                emc_acf_text( 'prayer_placeholder_embed_note', 'Placeholder Note (shown when no embed code)', 'In production, the live MasjidBox widget embed code will be placed here, replacing this mock-up.' ),
            ) ),
            emc_acf_section( 'Today\'s Prayer Times', 'prayer_today', emc_acf_numbered_items( 'prayer_time', 6, array(
                'name'  => array( 'Prayer Name', '' ),
                'icon'  => array( 'Icon Class', 'fas fa-sun' ),
                'adhan' => array( 'Adhan Time', '' ),
                'iqama' => array( 'Iqama Time', '' ),
            ) ) ),
            emc_acf_section( 'Info Cards', 'prayer_cards', array(
                emc_acf_text( 'prayer_jumuah_card_heading', 'Card 1 Heading', "Jumu'ah (Friday Prayer)" ),
                emc_acf_text( 'prayer_jumuah_1_label', 'Card 1: Label for 1st Khutbah', 'First Khutbah:' ),
                emc_acf_text( 'prayer_jumuah_1', 'Card 1: First Khutbah Time', '12:30 PM' ),
                emc_acf_text( 'prayer_jumuah_2_label', 'Card 1: Label for 2nd Khutbah', 'Second Khutbah:' ),
                emc_acf_text( 'prayer_jumuah_2', 'Card 1: Second Khutbah Time', '1:30 PM' ),
                emc_acf_text( 'prayer_taraweeh_card_heading', 'Card 2 Heading', 'Taraweeh (Ramadan)' ),
                emc_acf_text( 'prayer_taraweeh_note', 'Card 2: Note text', 'After Isha prayer' ),
                emc_acf_text( 'prayer_taraweeh_time', 'Card 2: Approx. Time', '10:30 PM' ),
                emc_acf_text( 'prayer_taraweeh_suffix', 'Card 2: Suffix', 'in Ramadan' ),
                emc_acf_text( 'prayer_location_card_heading', 'Card 3 Heading', 'Our Location' ),
                emc_acf_text( 'prayer_location_city', 'Card 3: City/Area', 'Chelmsford, Essex' ),
                emc_acf_text( 'prayer_alerts_card_heading', 'Card 4 Heading', 'Prayer Alerts' ),
                emc_acf_textarea( 'prayer_alerts_card_desc', 'Card 4: Description', 'Download a prayer app and set our Chelmsford location for automatic alerts.' ),
            ) ),
            emc_acf_section( 'Monthly Timetable', 'prayer_timetable', array(
                emc_acf_text( 'prayer_timetable_subtitle', 'Section Subtitle', 'Monthly View' ),
                emc_acf_text( 'prayer_timetable_note', 'Footer Note', 'Times shown are Adhan times. Iqama is typically 10-15 minutes after Adhan. All times are in local time (GMT+1 during BST). Please contact us if you have any questions.' ),
            ) ),
            emc_acf_section( 'MasjidBox Embed', 'prayer_embed', array(
                emc_acf_textarea( 'prayer_masjidbox_embed', 'MasjidBox Widget Embed Code (HTML — replaces mock table when filled)', '' ),
            ) )
        ),
    ) );

    /* ======================================================================
       EVENT SINGLE POST (emc_event CPT)
       ====================================================================== */
    acf_add_local_field_group( array(
        'key'      => 'group_emc_event_single',
        'title'    => 'Event Details (Extra Content)',
        'location' => array( array( array(
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'emc_event',
        ) ) ),
        'menu_order' => 1,
        'position'   => 'normal',
        'style'      => 'default',
        'fields'     => array_merge(
            emc_acf_section( 'Event Intro', 'evt_single_intro', array(
                emc_acf_textarea( 'evt_single_intro', 'Intro Paragraph (shown above content)', '' ),
            ) ),
            emc_acf_section( 'Key Highlights', 'evt_single_highlights', array_merge(
                array(
                    emc_acf_text( 'evt_single_highlights_heading', 'Highlights Heading', 'Event Highlights' ),
                ),
                emc_acf_numbered_items( 'evt_highlight', 4, array(
                    'icon'  => array( 'Icon (FontAwesome class)', 'fas fa-check-circle' ),
                    'title' => array( 'Title', '' ),
                    'desc'  => array( 'Description', '' ),
                ) )
            ) ),
            emc_acf_section( 'Sidebar CTA', 'evt_single_cta', array(
                emc_acf_text( 'evt_single_cta_heading',   'CTA Heading',  'Join This Event' ),
                emc_acf_textarea( 'evt_single_cta_desc',  'CTA Text',     'All are welcome. Contact us for more information.' ),
                emc_acf_text( 'evt_single_cta_btn_label', 'Button Label', 'Get in Touch' ),
                emc_acf_text( 'evt_single_cta_btn_url',   'Button URL',   '/contact/' ),
            ) )
        ),
    ) );

    /* ======================================================================
       SERVICE SINGLE POST (emc_service CPT)
       ====================================================================== */
    acf_add_local_field_group( array(
        'key'      => 'group_emc_service_single',
        'title'    => 'Service Details',
        'location' => array( array( array(
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'emc_service',
        ) ) ),
        'menu_order' => 0,
        'position'   => 'normal',
        'style'      => 'default',
        'fields'     => array_merge(
            emc_acf_section( 'Hero', 'svc_single_hero', array(
                emc_acf_text( 'svc_single_badge',      'Badge Text (e.g. Education, Welfare)', '' ),
                emc_acf_image_field( 'svc_single_hero_image', 'Hero Background Image (optional)' ),
            ) ),
            emc_acf_section( 'Intro', 'svc_single_intro', array(
                emc_acf_textarea( 'svc_single_intro', 'Intro Paragraph (shown above main content)', '' ),
            ) ),
            emc_acf_section( 'Key Highlights', 'svc_single_highlights', array_merge(
                array(
                    emc_acf_text( 'svc_single_highlights_heading', 'Highlights Section Heading', 'Key Highlights' ),
                ),
                emc_acf_numbered_items( 'svc_highlight', 6, array(
                    'icon'  => array( 'Icon (FontAwesome class)', 'fas fa-check-circle' ),
                    'title' => array( 'Title', '' ),
                    'desc'  => array( 'Description', '' ),
                ) )
            ) ),
            emc_acf_section( 'Sidebar CTA', 'svc_single_cta', array(
                emc_acf_text( 'svc_single_cta_heading',   'CTA Heading',      'Get Involved' ),
                emc_acf_textarea( 'svc_single_cta_desc',  'CTA Description',  'Interested in this service? We\'d love to hear from you.' ),
                emc_acf_text( 'svc_single_cta_btn_label', 'Button Label',     'Contact Us' ),
                emc_acf_text( 'svc_single_cta_btn_url',   'Button URL',       '/contact/' ),
            ) )
        ),
    ) );
}
add_action( 'acf/init', 'emc_register_acf_fields' );


/* ==========================================================================
   Field Definition Helpers
   These generate the array structure ACF expects for each field type.
   ========================================================================== */

/**
 * Wrap fields in a tab (accordion section) for better admin UX.
 */
function emc_acf_section( $label, $key, $fields ) {
    return array_merge(
        array( array(
            'key'   => 'field_emc_tab_' . $key,
            'label' => $label,
            'name'  => '',
            'type'  => 'tab',
            'placement' => 'top',
        ) ),
        $fields
    );
}

/**
 * Text field.
 */
function emc_acf_text( $name, $label, $default = '' ) {
    return array(
        'key'           => 'field_emc_' . $name,
        'label'         => $label,
        'name'          => $name,
        'type'          => 'text',
        'default_value' => $default,
    );
}

/**
 * Textarea field.
 */
function emc_acf_textarea( $name, $label, $default = '' ) {
    return array(
        'key'           => 'field_emc_' . $name,
        'label'         => $label,
        'name'          => $name,
        'type'          => 'textarea',
        'default_value' => $default,
        'rows'          => 3,
    );
}

/**
 * WYSIWYG (rich text) field.
 */
function emc_acf_wysiwyg( $name, $label, $default = '' ) {
    return array(
        'key'           => 'field_emc_' . $name,
        'label'         => $label,
        'name'          => $name,
        'type'          => 'wysiwyg',
        'default_value' => $default,
        'tabs'          => 'all',
        'toolbar'       => 'basic',
        'media_upload'  => 0,
    );
}

/**
 * Image field (return format: array).
 */
function emc_acf_image_field( $name, $label ) {
    return array(
        'key'           => 'field_emc_' . $name,
        'label'         => $label,
        'name'          => $name,
        'type'          => 'image',
        'return_format' => 'array',
        'preview_size'  => 'medium',
    );
}

/**
 * File field (for PDFs etc.)
 */
function emc_acf_file_field( $name, $label ) {
    return array(
        'key'           => 'field_emc_' . $name,
        'label'         => $label,
        'name'          => $name,
        'type'          => 'file',
        'return_format' => 'array',
    );
}

/**
 * Generate N numbered items with given sub-fields.
 * Replaces ACF PRO repeater for the free version.
 *
 * @param  string $prefix  e.g. 'about_trustee'
 * @param  int    $count   Number of items (e.g. 6)
 * @param  array  $schema  field_suffix => array( label, default [, type ] )
 * @return array  Flat array of ACF field definitions.
 */
function emc_acf_numbered_items( $prefix, $count, $schema ) {
    $fields = array();
    for ( $i = 1; $i <= $count; $i++ ) {
        // Add a message/divider for each item
        $fields[] = array(
            'key'     => 'field_emc_' . $prefix . '_' . $i . '_divider',
            'label'   => ucfirst( str_replace( array( 'svc_', 'about_', 'donate_', 'prayer_' ), '', $prefix ) ) . ' ' . $i,
            'name'    => '',
            'type'    => 'message',
            'message' => '<hr style="margin:0">',
        );
        foreach ( $schema as $suffix => $config ) {
            $label   = $config[0];
            $default = isset( $config[1] ) ? $config[1] : '';
            $type    = isset( $config[2] ) ? $config[2] : 'text';

            $field_name = $prefix . '_' . $i . '_' . $suffix;

            if ( $type === 'image' ) {
                $fields[] = emc_acf_image_field( $field_name, $label );
            } elseif ( $type === 'file' ) {
                $fields[] = emc_acf_file_field( $field_name, $label );
            } elseif ( $type === 'textarea' ) {
                $fields[] = emc_acf_textarea( $field_name, $label, $default );
            } else {
                $fields[] = emc_acf_text( $field_name, $label, $default );
            }
        }
    }
    return $fields;
}
