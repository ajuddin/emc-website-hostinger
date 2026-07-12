<?php
/**
 * One-time setup: seed the 8 core EMC service posts.
 * Run once by visiting: http://emc.local/?emc_seed_services=1
 * Delete this file afterwards.
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if ( empty($_GET['emc_seed_services']) || $_GET['emc_seed_services'] !== '1' ) {
    die('Add ?emc_seed_services=1 to the URL to run.');
}

if ( ! current_user_can('manage_options') ) {
    die('Unauthorised.');
}

$services = array(
    array(
        'title'   => 'Islamic Education',
        'slug'    => 'islamic-education',
        'icon'    => 'fas fa-book-open',
        'excerpt' => 'Weekend Madrasah, Quran lessons, and Islamic studies for children and adults of all levels.',
        'content' => '<h2>About Our Islamic Education Programme</h2>
<p>At Essex Muslim Centre, we believe that every Muslim should have the opportunity to connect with the Quran and Islamic teachings. Our Islamic Education programme serves children and adults across Chelmsford and Essex.</p>

<h3>What We Offer</h3>
<ul>
<li><strong>Weekend Madrasah:</strong> Saturday and Sunday classes covering Quran recitation with Tajweed, Islamic Studies, Seerah, and core Islamic knowledge.</li>
<li><strong>Adult Islamic Studies:</strong> Beginner-friendly classes covering worship, Quran, character, and everyday Islamic practice.</li>
<li><strong>Quran Memorisation (Hifz):</strong> Structured programme for dedicated students with individual mentor support.</li>
<li><strong>Quran Recitation & Tajweed:</strong> Perfecting pronunciation and application of Tajweed rules for all ages.</li>
</ul>

<h3>Timings</h3>
<p>Weekend classes run Saturday and Sunday mornings. Adult classes are available on weekday evenings. Contact us for the current timetable.</p>

<h3>How to Enrol</h3>
<p>Registration is open at the beginning of each term. Contact our office or visit the centre to pick up a registration form. Subsidised rates are available for families in financial need.</p>',
        'order'   => 1,
    ),
    array(
        'title'   => 'Nikah (Marriage)',
        'slug'    => 'nikah-marriage',
        'icon'    => 'fas fa-ring',
        'excerpt' => 'Islamic marriage ceremonies conducted by our Imam with pre-marriage guidance and support.',
        'content' => '<h2>Nikah Ceremonies at EMC</h2>
<p>Marriage is half of one\'s deen. At Essex Muslim Centre, our Imams conduct Islamic marriage (Nikah) ceremonies in a blessed and dignified environment, ensuring every couple begins their journey on a strong spiritual foundation.</p>

<h3>Our Services Include</h3>
<ul>
<li><strong>Nikah Ceremony:</strong> A formal Islamic marriage contract conducted in the presence of witnesses and according to Sunnah.</li>
<li><strong>Pre-Marriage Guidance:</strong> One-to-one confidential sessions with our Imam covering rights, responsibilities, and expectations in Islamic marriage.</li>
<li><strong>Documentation Support:</strong> Guidance on obtaining the relevant civil marriage documents alongside the Islamic ceremony.</li>
<li><strong>Venue:</strong> Our main hall can accommodate families and guests for the ceremony.</li>
</ul>

<h3>How to Book</h3>
<p>To book a Nikah ceremony, please contact our office at least 4 weeks in advance. Both parties and their walis (guardians) should be present for the initial consultation. A small administrative fee applies.</p>

<p>We also offer referrals to reputable civil ceremony venues if required.</p>',
        'order'   => 2,
    ),
    array(
        'title'   => 'Janaza Services',
        'slug'    => 'janaza-services',
        'icon'    => 'fas fa-praying-hands',
        'excerpt' => 'Compassionate funeral prayer, ghusl, and burial coordination services available 24/7.',
        'content' => '<h2>Janaza Services — Available 24/7</h2>
<p>Losing a loved one is one of life\'s most difficult moments. Essex Muslim Centre is here to support your family through the entire Janaza (funeral) process with compassion, care, and full adherence to Islamic tradition.</p>

<h3>Services We Provide</h3>
<ul>
<li><strong>Ghusl (Islamic Washing):</strong> Carried out by trained volunteers in our on-site ghusl facility, with separate facilities for male and female deceased.</li>
<li><strong>Kafan (Shrouding):</strong> Preparation and wrapping of the deceased according to Sunnah.</li>
<li><strong>Janaza Prayer (Salat ul-Janaza):</strong> Congregational funeral prayer led by our Imam.</li>
<li><strong>Burial Coordination:</strong> Liaison with local cemeteries including Chelmsford City Council cemeteries and Writtle Road Cemetery for Muslim burial plots.</li>
<li><strong>Repatriation Guidance:</strong> Support for families wishing to repatriate the deceased to their home country.</li>
</ul>

<h3>Contact Us Urgently</h3>
<p>For immediate Janaza support, contact our 24/7 emergency line. Our team will guide you through every step with sensitivity and Islamic care.</p>',
        'order'   => 3,
    ),
    array(
        'title'   => 'Meet an Imam',
        'slug'    => 'meet-an-imam',
        'icon'    => 'fas fa-user-tie',
        'excerpt' => 'Schedule a one-to-one appointment with our Imam for spiritual guidance, counselling, or Islamic advice.',
        'content' => '<h2>Meet an Imam — Private Appointments</h2>
<p>Our resident Imam is available for private, confidential one-to-one appointments. Whether you are seeking Islamic guidance, spiritual support, advice on a personal matter, or simply want to learn more about Islam, we are here to help.</p>

<h3>Areas of Guidance</h3>
<ul>
<li><strong>Spiritual Counselling:</strong> Personal struggles, mental wellbeing from an Islamic perspective, and strengthening one\'s relationship with Allah.</li>
<li><strong>Family & Relationship Advice:</strong> Islamic guidance on marriage, divorce, parenting, and family conflict resolution.</li>
<li><strong>Islamic Rulings (Fatawa):</strong> Questions on halal/haram, worship, business transactions, and day-to-day Islamic practice.</li>
<li><strong>Reversion Support:</strong> Dedicated guidance for those considering or who have recently embraced Islam.</li>
<li><strong>Grief & Bereavement:</strong> Spiritual support for those dealing with loss.</li>
</ul>

<h3>How to Book</h3>
<p>Appointments are available on weekday afternoons and by arrangement. Please contact the office to schedule a session. All conversations are strictly confidential. Female visitors may request a female companion be present.</p>',
        'order'   => 4,
    ),
    array(
        'title'   => 'Welfare Services',
        'slug'    => 'welfare-services',
        'icon'    => 'fas fa-hand-holding-heart',
        'excerpt' => 'Food bank partnerships, financial counselling, and support for families in need.',
        'content' => '<h2>Community Welfare & Support</h2>
<p>Islam places great emphasis on caring for those in need. Essex Muslim Centre works to support vulnerable members of our community through a range of welfare services, carried out with dignity and respect.</p>

<h3>What We Offer</h3>
<ul>
<li><strong>Emergency Food Parcels:</strong> In partnership with local food banks, we can arrange emergency food support for families in crisis.</li>
<li><strong>Zakat & Sadaqah Distribution:</strong> We collect and distribute Zakat and Sadaqah funds to eligible recipients in the local Muslim community.</li>
<li><strong>Financial Guidance:</strong> Signposting to halal financial advice, benefits entitlement support, and debt counselling services.</li>
<li><strong>Mental Health Referrals:</strong> Culturally sensitive referrals to NHS and third-sector mental health support services.</li>
<li><strong>Community Befriending:</strong> Connecting isolated elderly or vulnerable individuals with community volunteers for regular check-ins and companionship.</li>
</ul>

<h3>Confidential Support</h3>
<p>All welfare enquiries are handled confidentially by our trained volunteers. To speak to a welfare officer, please contact the centre directly. No one will be turned away without being signposted to the relevant support.</p>',
        'order'   => 5,
    ),
    array(
        'title'   => 'General Events',
        'slug'    => 'general-events',
        'icon'    => 'fas fa-calendar-alt',
        'excerpt' => 'Community gatherings, Islamic talks, sports days, and interfaith activities throughout the year.',
        'content' => '<h2>EMC Events Programme</h2>
<p>Throughout the year, Essex Muslim Centre organises a rich and varied programme of events for all members of the community — young and old, Muslim and non-Muslim alike.</p>

<h3>Types of Events</h3>
<ul>
<li><strong>Islamic Talks & Lectures:</strong> Monthly talks by visiting scholars and local Imams on topics relevant to contemporary Muslim life.</li>
<li><strong>Eid Celebrations:</strong> Annual Eid ul-Fitr and Eid ul-Adha community gatherings with food, activities, and celebrations.</li>
<li><strong>Ramadan Programme:</strong> Tarawih prayers, Iftar gatherings, Laylatul Qadr events, and charity fundraisers throughout the holy month.</li>
<li><strong>Youth Sports Days:</strong> Football, cricket, and multi-sport events for young people in the community.</li>
<li><strong>Interfaith Dialogue:</strong> Open days, mosque tours, and interfaith panel discussions welcoming neighbours of all faiths.</li>
<li><strong>Fundraising Dinners:</strong> Annual gala dinners and community fundraisers supporting the building campaign and charitable causes.</li>
</ul>

<h3>Stay Updated</h3>
<p>Follow our Events page and social media channels to stay up to date with upcoming events. All are welcome unless otherwise stated. Many events are free to attend.</p>',
        'order'   => 6,
    ),
    array(
        'title'   => 'School Visit',
        'slug'    => 'school-visit',
        'icon'    => 'fas fa-school',
        'excerpt' => 'Welcoming local schools for mosque visits, faith learning, guided tours, and Q&A sessions.',
        'content' => '<h2>School Visits to EMC</h2>
<p>Essex Muslim Centre welcomes local schools and educational groups for guided visits that help pupils learn about Islam, mosque life, and the Muslim community in Essex.</p>

<h3>Visits Can Include</h3>
<ul>
<li><strong>Mosque Tours:</strong> A guided walk through the centre and prayer spaces.</li>
<li><strong>Faith Learning:</strong> Age-appropriate introductions to Islamic beliefs, worship, and community life.</li>
<li><strong>Q&A Sessions:</strong> Opportunities for pupils and teachers to ask respectful questions.</li>
<li><strong>Curriculum Support:</strong> Visits tailored around RE, citizenship, diversity, and interfaith learning.</li>
</ul>

<h3>Arrange a Visit</h3>
<p>Please contact the centre with your preferred dates, year group, group size, and any curriculum topics you would like covered.</p>',
        'order'   => 7,
    ),
    array(
        'title'   => 'Bereavement Support',
        'slug'    => 'bereavement-support',
        'icon'    => 'fas fa-hands-helping',
        'excerpt' => 'Compassionate spiritual and practical support for individuals and families after the loss of a loved one.',
        'content' => '<h2>Bereavement Support</h2>
<p>Bereavement can be overwhelming. Essex Muslim Centre offers compassionate support for individuals and families dealing with loss, with care rooted in Islamic guidance and community support.</p>

<h3>How We Can Help</h3>
<ul>
<li><strong>Spiritual Support:</strong> Guidance, duas, and reminders for families navigating grief.</li>
<li><strong>Family Support:</strong> A confidential space to talk through difficult moments after a loss.</li>
<li><strong>Janaza Signposting:</strong> Help connecting families with the appropriate funeral and burial support.</li>
<li><strong>Ongoing Care:</strong> Follow-up support and signposting to suitable local services where needed.</li>
</ul>

<h3>Speak to Us</h3>
<p>Please contact the centre if you or your family would benefit from bereavement support. All enquiries are handled with sensitivity and confidentiality.</p>',
        'order'   => 8,
    ),
);

$created = array();
$skipped = array();

foreach ( $services as $svc ) {
    $existing = get_page_by_path( $svc['slug'], OBJECT, 'emc_service' );
    if ( $existing ) {
        $skipped[] = $svc['title'];
        continue;
    }

    $post_id = wp_insert_post( array(
        'post_type'    => 'emc_service',
        'post_title'   => $svc['title'],
        'post_name'    => $svc['slug'],
        'post_content' => $svc['content'],
        'post_excerpt' => $svc['excerpt'],
        'post_status'  => 'publish',
        'menu_order'   => $svc['order'],
    ) );

    if ( $post_id && ! is_wp_error( $post_id ) ) {
        update_post_meta( $post_id, '_emc_service_icon',     $svc['icon'] );
        update_post_meta( $post_id, '_emc_service_order',    $svc['order'] );
        update_post_meta( $post_id, '_emc_service_featured', '1' );
        $created[] = $svc['title'] . ' → /service/' . $svc['slug'] . '/';
    }
}

echo '<style>body{font-family:sans-serif;padding:2rem;max-width:800px;margin:0 auto}</style>';
echo '<h2>✅ EMC Services Seeded</h2>';
if ( $created ) {
    echo '<p><strong>Created:</strong></p><ul>';
    foreach ( $created as $c ) echo '<li>' . esc_html( $c ) . '</li>';
    echo '</ul>';
}
if ( $skipped ) {
    echo '<p><strong>Already existed (skipped):</strong></p><ul>';
    foreach ( $skipped as $s ) echo '<li>' . esc_html( $s ) . '</li>';
    echo '</ul>';
}
echo '<p><a href="' . home_url('/services/') . '">→ View Services Page</a></p>';
echo '<p style="color:red"><strong>⚠ Delete this file now:</strong> /wp-content/themes/emc-theme/seed-services.php</p>';
