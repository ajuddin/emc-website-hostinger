<?php
/**
 * EMC Theme — inc/demo-data.php
 * Phase 11: Static demo content arrays consumed by the import engine.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns all pages to create during demo import.
 * Each entry: [ slug, title, template, content, parent_slug ]
 *
 * @return array[]
 */
function emc_demo_get_pages() {
    return array(

        /* ── Core Site Pages ─────────────────────────────────── */
        array(
            'slug'     => 'home',
            'title'    => 'Home',
            'template' => 'front-page.php',
            'content'  => '<!-- EMC Homepage — managed via theme template front-page.php -->',
            'parent'   => '',
        ),
        array(
            'slug'     => 'about',
            'title'    => 'About Us',
            'template' => 'template-about.php',
            'content'  => '<!-- Managed via page template -->',
            'parent'   => '',
        ),
        array(
            'slug'     => 'services',
            'title'    => 'Services',
            'template' => 'template-services.php',
            'content'  => '<!-- Managed via page template -->',
            'parent'   => '',
        ),
        array(
            'slug'     => 'events',
            'title'    => 'Events',
            'template' => 'template-events.php',
            'content'  => '<!-- Managed via page template -->',
            'parent'   => '',
        ),
        array(
            'slug'     => 'prayer-times',
            'title'    => 'Prayer Times',
            'template' => 'template-prayer-times.php',
            'content'  => '<!-- Managed via page template -->',
            'parent'   => '',
        ),
        array(
            'slug'     => 'media',
            'title'    => 'Media',
            'template' => 'template-media.php',
            'content'  => '<!-- Managed via page template -->',
            'parent'   => '',
        ),
        array(
            'slug'     => 'contact',
            'title'    => 'Contact',
            'template' => 'template-contact.php',
            'content'  => '<!-- Managed via page template -->',
            'parent'   => '',
        ),

        /* ── Giving & Donations ──────────────────────────────── */
        array(
            'slug'     => 'donate',
            'title'    => 'Donate',
            'template' => 'template-donate.php',
            'content'  => '<!-- Managed via page template -->',
            'parent'   => '',
        ),
        array(
            'slug'     => 'ramadan',
            'title'    => 'Ramadan Giving',
            'template' => '',
            'content'  => '<!-- Managed via page template -->',
            'parent'   => 'donate',
        ),
        array(
            'slug'     => 'campaign',
            'title'    => 'Our Campaign',
            'template' => '',
            'content'  => '<!-- Managed via page template -->',
            'parent'   => 'donate',
        ),
        array(
            'slug'     => 'gift-aid',
            'title'    => 'Gift Aid Declaration',
            'template' => '',
            'content'  => '<!-- Managed via page template -->',
            'parent'   => 'donate',
        ),
        array(
            'slug'     => 'standing-order',
            'title'    => 'Standing Order',
            'template' => '',
            'content'  => '<!-- Managed via page template -->',
            'parent'   => 'donate',
        ),

        /* ── Community & Info ────────────────────────────────── */
        array(
            'slug'     => 'vacancies',
            'title'    => 'Vacancies & Volunteering',
            'template' => '',
            'content'  => '<!-- Managed via archive-emc_vacancy.php -->',
            'parent'   => '',
        ),

        /* ── Legal ───────────────────────────────────────────── */
        array(
            'slug'     => 'privacy-policy',
            'title'    => 'Privacy Policy',
            'template' => '',
            'content'  => emc_demo_privacy_content(),
            'parent'   => '',
        ),
        array(
            'slug'     => 'blog',
            'title'    => 'News & Blog',
            'template' => '',
            'content'  => '',
            'parent'   => '',
        ),
    );
}


/**
 * Returns the primary navigation menu items.
 *
 * @return array[]  [ label, slug, children[] ]
 */
function emc_demo_get_primary_menu() {
    return array(
        array( 'label' => 'About Us',    'slug' => 'about',    'children' => array() ),
        array(
            'label'    => 'Services',
            'slug'     => 'services',
            'children' => array(
                array( 'label' => 'Islamic Education',   'slug' => 'islamic-education' ),
                array( 'label' => 'Nikah Marriage',      'slug' => 'nikah-marriage' ),
                array( 'label' => 'Janaza Services',     'slug' => 'janaza-services' ),
                array( 'label' => 'Meet an Imam',        'slug' => 'meet-an-imam' ),
                array( 'label' => 'Welfare Services',    'slug' => 'welfare-services' ),
                array( 'label' => 'General Events',      'slug' => 'general-events' ),
                array( 'label' => 'School Visit',        'slug' => 'school-visit' ),
                array( 'label' => 'Bereavement Support', 'slug' => 'bereavement-support' ),
            ),
        ),
        array( 'label' => 'Events',      'slug' => 'events',     'children' => array() ),
        array( 'label' => 'Membership',  'slug' => 'membership', 'children' => array() ),
        array( 'label' => 'Contact',     'slug' => 'contact',    'children' => array() ),
    );
}


/**
 * Returns footer quick-links menu items.
 *
 * @return array[]
 */
function emc_demo_get_footer_menu() {
    return array(
        array( 'label' => 'About Us',     'slug' => 'about' ),
        array( 'label' => 'Our Services', 'slug' => 'services' ),
        array( 'label' => 'Prayer Times', 'slug' => 'prayer-times' ),
        array( 'label' => 'Donate',       'slug' => 'donate' ),
        array( 'label' => 'Events',       'slug' => 'events' ),
        array( 'label' => 'Media',        'slug' => 'media' ),
        array( 'label' => 'Vacancies',    'slug' => 'vacancies' ),
        array( 'label' => 'Contact',      'slug' => 'contact' ),
        array( 'label' => 'Privacy Policy', 'slug' => 'privacy-policy' ),
    );
}


/**
 * Returns all theme_mod settings to apply during demo import.
 * These are the defaults that make the theme look great out of the box.
 *
 * @return array  key => value pairs
 */
function emc_demo_get_theme_mods() {
    return array(
        /* ── Identity ─────────────────────────────────────────── */
        'blogname'        => 'Essex Muslim Centre',
        'blogdescription' => 'Faith · Community · Welfare',

        /* ── Colours ──────────────────────────────────────────── */
        'emc_color_primary'      => '#2AACA0',
        'emc_color_primary_dark' => '#1A7A72',
        'emc_color_accent'       => '#C4956A',
        'emc_color_deep_blue'    => '#0C1F2E',
        'emc_color_text'         => '#2D3E4A',
        'emc_color_light_bg'     => '#F5F9F9',
        'emc_bg_header'          => '#FFFFFF',
        'emc_bg_footer'          => '#0C1F2E',
        'emc_bg_section_alt'     => '#EAF4F3',

        /* ── Typography ───────────────────────────────────────── */
        'emc_font_heading'    => 'Outfit',
        'emc_font_body'       => 'Inter',
        'emc_font_size_base'  => '16',

        /* ── Buttons ──────────────────────────────────────────── */
        'emc_btn_radius'    => '999px',
        'emc_btn_padding_x' => '1.75rem',
        'emc_btn_padding_y' => '0.75rem',

        /* ── Layout ───────────────────────────────────────────── */
        'emc_container_max_width' => '1280px',
        'emc_section_padding_y'   => '5rem',
        'emc_border_radius'       => '0.75rem',

        /* ── Header ───────────────────────────────────────────── */
        'emc_header_sticky'       => true,
        'emc_header_prayer'       => true,
        'emc_header_donate_btn'   => true,
        'emc_header_donate_label' => 'Donate Now',

        /* ── Contact Info ─────────────────────────────────────── */
        'emc_location'         => 'Essex Muslim Centre, Cuton Hall Lane, CM2 6PB',
        'emc_address_line1'    => 'Cuton Hall Lane',
        'emc_address_city'     => '',
        'emc_address_postcode' => 'CM2 6PB',
        'emc_admin_email'      => 'admin@essexmuslimcentre.org',
        'emc_charity_number'   => '1209815',

        /* ── Footer ───────────────────────────────────────────── */
        'emc_footer_newsletter'         => true,
        'emc_footer_newsletter_heading' => 'Stay in the Loop',
        'emc_footer_newsletter_sub'     => 'Get the latest news, events and announcements delivered to your inbox.',
        'emc_footer_about_text'         => 'Advancing Islamic faith, education, and community welfare from Cuton Hall Lane.',
        'emc_footer_col2_heading'       => 'Quick Links',
        'emc_footer_col3_heading'       => 'Community',
        'emc_footer_col4_heading'       => 'Contact Us',
        'emc_footer_show_privacy'       => true,
        'emc_footer_show_gift_aid'      => true,

        /* ── Homepage Sections ────────────────────────────────── */
        'emc_hero_title'    => 'Welcome to Essex Muslim Centre',
        'emc_hero_subtitle' => 'A place of worship, learning and community in the heart of Chelmsford, Essex.',

        /* ── Cookie & Announcement ────────────────────────────── */
        'emc_cookie_enabled'     => true,
        'emc_announcement_enabled' => false,

        /* ── SEO ──────────────────────────────────────────────── */
        'emc_meta_description' => 'Essex Muslim Centre (EMC) — A registered charity providing Islamic services, education, and community welfare in Chelmsford, Essex.',
    );
}


/**
 * Minimal privacy policy content.
 *
 * @return string
 */
function emc_demo_privacy_content() {
    return '<h2>Privacy Policy</h2>
<p>Essex Muslim Centre (Registered Charity No. 1209815) is committed to protecting your personal data.</p>
<h3>What We Collect</h3>
<p>We may collect your name, email address, and donation details when you interact with our website or services.</p>
<h3>How We Use It</h3>
<p>Your data is used only to process donations, respond to enquiries, and send you updates you have requested.</p>
<h3>Cookie Policy</h3>
<p>We use essential cookies to improve your experience. No personal data is sold to third parties.</p>
<h3>Contact</h3>
<p>For data requests, email: <a href="mailto:admin@essexmuslimcentre.org">admin@essexmuslimcentre.org</a></p>';
}


/**
 * Returns all gallery items to import during demo setup.
 * Each entry: [ title, file (relative to assets/gallery/), category ]
 *
 * @return array[]
 */
function emc_demo_get_gallery_items() {
    return array(
        // Friday Prayer
        array( 'title' => 'Friday Prayer Service',        'file' => 'Friday Prayer/FPS-600x600.jpeg',       'category' => 'Friday Prayer' ),
        array( 'title' => 'Friday Prayer Congregation',   'file' => 'Friday Prayer/Friday-1-600x450.jpeg',  'category' => 'Friday Prayer' ),

        // Eid Celebration
        array( 'title' => 'Eid Celebration',               'file' => 'Eid Celebration/eid_3-600x600.jpeg',   'category' => 'Eid Celebration' ),

        // Activity During Ramadan
        array( 'title' => 'Activity During Ramadan',       'file' => 'Activity During Ramadan/r2-300x300.jpeg', 'category' => 'Activity During Ramadan' ),
        array( 'title' => 'Ramadan Gathering',             'file' => 'Activity During Ramadan/r3-300x300.jpeg', 'category' => 'Activity During Ramadan' ),

        // Visit to the Cambridge Mosque
        array( 'title' => 'Visit to the Cambridge Mosque', 'file' => 'Visit to the Cambridge Mosque/visit_1-600x338.jpeg', 'category' => 'Visit to the Cambridge Mosque' ),

        // Career Advise
        array( 'title' => 'Career Advise Session',   'file' => 'Career Advise/WhatsApp-Image-2025-08-21-at-21.05.39-1-1-300x169.jpeg', 'category' => 'Career Advise' ),
        array( 'title' => 'Career Workshop',         'file' => 'Career Advise/WhatsApp-Image-2025-08-21-at-21.05.39-2-1-300x169.jpeg', 'category' => 'Career Advise' ),
        array( 'title' => 'Career Mentoring',        'file' => 'Career Advise/WhatsApp-Image-2025-08-21-at-21.05.39-3-2-300x169.jpeg', 'category' => 'Career Advise' ),
        array( 'title' => 'Career Guidance Talk',    'file' => 'Career Advise/WhatsApp-Image-2025-08-21-at-21.05.39-4-2-300x169.jpeg', 'category' => 'Career Advise' ),
        array( 'title' => 'Career Panel Discussion', 'file' => 'Career Advise/WhatsApp-Image-2025-08-21-at-21.05.39-5-2-300x169.jpeg', 'category' => 'Career Advise' ),
        array( 'title' => 'Career Workshop Group',   'file' => 'Career Advise/WhatsApp-Image-2025-08-21-at-21.05.39-6-2-300x169.jpeg', 'category' => 'Career Advise' ),
        array( 'title' => 'Youth Career Day',        'file' => 'Career Advise/WhatsApp-Image-2025-08-21-at-21.05.39-7-2-300x169.jpeg', 'category' => 'Career Advise' ),
        array( 'title' => 'Career Advise Panel',     'file' => 'Career Advise/WhatsApp-Image-2025-08-21-at-21.05.39-8-2-300x169.jpeg', 'category' => 'Career Advise' ),

        // Community Support Services
        array( 'title' => 'Community Support Services', 'file' => 'Community Support Services/New-Muslim-600x338.jpeg', 'category' => 'Community Support Services' ),

        // Fundraising Event
        array( 'title' => 'Fundraising Event',       'file' => 'Fundraising Event/20250316_170418-300x225.jpg', 'category' => 'Fundraising Event' ),
        array( 'title' => 'Fundraising Dinner',      'file' => 'Fundraising Event/20250316_172759-300x225.jpg', 'category' => 'Fundraising Event' ),
        array( 'title' => 'Fundraising Community',    'file' => 'Fundraising Event/20250316_173031-300x225.jpg', 'category' => 'Fundraising Event' ),
        array( 'title' => 'Fundraising Night',        'file' => 'Fundraising Event/20250316_173439-300x225.jpg', 'category' => 'Fundraising Event' ),
        array( 'title' => 'Community Dinner',         'file' => 'Fundraising Event/20250316_181413-300x225.jpg', 'category' => 'Fundraising Event' ),
        array( 'title' => 'Fundraising Celebration',  'file' => 'Fundraising Event/20250316_185011-300x225.jpg', 'category' => 'Fundraising Event' ),

        // Outdoor Activity 2024
        array( 'title' => 'Outdoor Activity 2024',    'file' => 'Outdoor Activity 2024/out_1-1-300x300.jpeg', 'category' => 'Outdoor Activity 2024' ),
        array( 'title' => 'Outdoor Fun Day',          'file' => 'Outdoor Activity 2024/out_3-1-300x300.jpeg', 'category' => 'Outdoor Activity 2024' ),
        array( 'title' => 'Youth Outdoor Day',        'file' => 'Outdoor Activity 2024/out_6-1-300x300.jpeg', 'category' => 'Outdoor Activity 2024' ),

        // Quran Group Study
        array( 'title' => 'Quran Study Circle',   'file' => 'Quran Group Study/WhatsApp-Image-2025-08-21-at-21.04.12-300x225.jpeg',   'category' => 'Quran Group Study' ),
        array( 'title' => 'Quran Group Session',  'file' => 'Quran Group Study/WhatsApp-Image-2025-08-21-at-21.04.12-5-300x225.jpeg', 'category' => 'Quran Group Study' ),
        array( 'title' => 'Quran Learning',       'file' => 'Quran Group Study/WhatsApp-Image-2025-08-21-at-21.04.12-6-300x151.jpeg', 'category' => 'Quran Group Study' ),
        array( 'title' => 'Quran Discussion',     'file' => 'Quran Group Study/WhatsApp-Image-2025-08-21-at-21.04.12-7-300x225.jpeg', 'category' => 'Quran Group Study' ),
        array( 'title' => 'Quran Recitation',     'file' => 'Quran Group Study/WhatsApp-Image-2025-08-21-at-21.05.39-2-300x169.jpeg', 'category' => 'Quran Group Study' ),
        array( 'title' => 'Quran Group Study',    'file' => 'Quran Group Study/WhatsApp-Image-2025-08-21-at-21.05.39-7-300x169.jpeg', 'category' => 'Quran Group Study' ),
    );
}


/**
 * Returns demo events to seed during initial setup.
 * Each entry has title, excerpt, content, date, time, end_time, location, category, capacity, and image file.
 *
 * @return array[]
 */
function emc_demo_get_events() {
    // Generate future dates relative to today
    $base = current_time( 'Y-m-d' );

    return array(
        array(
            'title'    => 'Friday Prayer (Jumu\'ah)',
            'excerpt'  => 'Weekly congregational Friday prayers with Khutbah.',
            'content'  => 'Join us every Friday for the weekly Jumu\'ah prayers. The Khutbah is delivered in English and Arabic. Doors open 30 minutes before the prayer begins. All are welcome.',
            'date'     => date( 'Y-m-d', strtotime( $base . ' +3 days' ) ),
            'time'     => '12:30 PM',
            'end_time' => '2:00 PM',
            'location' => 'Main Hall',
            'category' => 'religious',
            'capacity' => 'Open to all',
            'image'    => 'Friday Prayer/FPS-600x600.jpeg',
        ),
        array(
            'title'    => 'Community Support Services',
            'excerpt'  => 'Supporting new Muslims and community members with guidance and resources.',
            'content'  => 'Our community support team provides guidance, counselling, and resources for new Muslims and community members in need. Drop in for confidential support, mentorship, and practical help with integration.',
            'date'     => date( 'Y-m-d', strtotime( $base . ' +7 days' ) ),
            'time'     => '10:00 AM',
            'end_time' => '4:00 PM',
            'location' => 'EMC Centre',
            'category' => 'community',
            'capacity' => 'Free entry',
            'image'    => 'Community Support Services/New-Muslim-600x338.jpeg',
        ),
        array(
            'title'    => 'Youth Career Advise Seminar',
            'excerpt'  => 'Career guidance and mentorship for youth aged 14–25.',
            'content'  => 'An interactive seminar with guest speakers from various professional backgrounds. Topics include CV writing, interview skills, and career pathways in STEM, law, and business. Light refreshments provided.',
            'date'     => date( 'Y-m-d', strtotime( $base . ' +14 days' ) ),
            'time'     => '6:00 PM',
            'end_time' => '8:00 PM',
            'location' => 'Main Hall',
            'category' => 'youth',
            'capacity' => '40 spots',
            'image'    => 'Career Advise/WhatsApp-Image-2025-08-21-at-21.05.39-1-1-300x169.jpeg',
        ),
        array(
            'title'    => 'Fundraising Dinner',
            'excerpt'  => 'An elegant fundraising dinner to support our building campaign.',
            'content'  => 'Join us for an evening of food, community, and fundraising. All proceeds go towards our building fund. Tickets include a three-course dinner, live nasheeds, and guest speakers. Tables of 8 available.',
            'date'     => date( 'Y-m-d', strtotime( $base . ' +21 days' ) ),
            'time'     => '7:00 PM',
            'end_time' => '10:00 PM',
            'location' => 'Chelmsford City Racecourse',
            'category' => 'fundraising',
            'capacity' => 'Tickets: £30',
            'image'    => 'Fundraising Event/20250316_170418-300x225.jpg',
        ),
        array(
            'title'    => 'Outdoor Family Fun Day',
            'excerpt'  => 'Fun outdoor activities for the whole family — sports, games, and BBQ.',
            'content'  => 'Bring the whole family for a day of fun in the sun! Activities include football, cricket, tug-of-war, face painting, bouncy castle, and a community BBQ. All ages welcome.',
            'date'     => date( 'Y-m-d', strtotime( $base . ' +28 days' ) ),
            'time'     => '10:00 AM',
            'end_time' => '3:00 PM',
            'location' => 'Central Park, Chelmsford',
            'category' => 'community',
            'capacity' => 'Free entry',
            'image'    => 'Outdoor Activity 2024/out_1-1-300x300.jpeg',
        ),
        array(
            'title'    => 'Quran Study Circle',
            'excerpt'  => 'Weekly Quran study and tafseer session for adults.',
            'content'  => 'A weekly gathering to study the Quran with experienced teachers. Sessions cover recitation improvement, tafseer (explanation), and practical application of Quranic teachings in daily life. All levels welcome.',
            'date'     => date( 'Y-m-d', strtotime( $base . ' +5 days' ) ),
            'time'     => '7:30 PM',
            'end_time' => '9:00 PM',
            'location' => 'EMC Centre',
            'category' => 'religious',
            'capacity' => 'Open to all',
            'image'    => 'Quran Group Study/WhatsApp-Image-2025-08-21-at-21.04.12-300x225.jpeg',
        ),
    );
}
