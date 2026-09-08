<?php
/**
 * Template Name: Membership
 * Template Post Type: page
 *
 * EMC Theme — Become a Member page.
 *
 * Headings, body copy, the three level names/amounts/descriptions and the
 * progress-bar figures are editable in Appearance → Customize → Page Content →
 * Membership Page. The benefits matrix, allocation chart and dome artwork are
 * part of the page design and live here.
 *
 * Membership fees are monthly Stripe subscriptions handled by inc/membership.php.
 *
 * @package emc-theme
 */

get_header();

$levels       = emc_membership_levels();
$stripe_ready = emc_membership_stripe_is_available();
$stripe_key   = $stripe_ready && function_exists( 'emc_stripe_pub_key' ) ? emc_stripe_pub_key() : '';
$contact_url  = get_permalink( get_page_by_path( 'contact' ) ) ?: home_url( '/contact/' );

/* Levels that can actually be charged: at or above Stripe's 50p minimum. */
$available_levels = array_filter( $levels, static function ( $level ) {
    return $level['pence'] >= 50;
} );

/* Progress bar. A goal of zero means "no target set", so the bar stays empty. */
$raised   = (float) get_theme_mod( 'mem_progress_raised', 0 );
$goal     = (float) get_theme_mod( 'mem_progress_goal', 0 );
$progress = $goal > 0 ? max( 0, min( 100, ( $raised / $goal ) * 100 ) ) : 0;

/* What your mosque delivers. */
$deliverables = array(
    __( 'All daily and special prayers', 'emc-theme' ),
    __( 'Full Ramadan programme', 'emc-theme' ),
    __( 'Nightly Iftar during Ramadan', 'emc-theme' ),
    __( "Children's Qur'an classes and youth activities", 'emc-theme' ),
    __( 'Educational programmes and study circles (Halaqa)', 'emc-theme' ),
    __( 'Family support services', 'emc-theme' ),
    __( 'Shahada support and pastoral care', 'emc-theme' ),
    __( 'Community events and interfaith engagement', 'emc-theme' ),
    __( 'New Muslims support and mentoring', 'emc-theme' ),
    __( 'Space hire for community activities', 'emc-theme' ),
    __( 'Funeral and bereavement guidance', 'emc-theme' ),
);

/* Benefits matrix. Each row lists the levels it applies to. */
$benefits = array(
    array( 'label' => __( 'Annual report', 'emc-theme' ),                                'levels' => array( 'supporters', 'companions', 'custodians' ) ),
    array( 'label' => __( 'One-time welcome gift', 'emc-theme' ),                        'levels' => array( 'supporters', 'companions', 'custodians' ) ),
    array( 'label' => __( 'Quarterly report', 'emc-theme' ),                             'levels' => array( 'companions', 'custodians' ) ),
    array( 'label' => __( 'Annual online meeting', 'emc-theme' ),                        'levels' => array( 'companions', 'custodians' ) ),
    array( 'label' => __( "A gift after 1 year — Qur'an", 'emc-theme' ),                 'levels' => array( 'companions', 'custodians' ) ),
    array( 'label' => __( 'First refusal to paid ticketed events', 'emc-theme' ),        'levels' => array( 'companions', 'custodians' ) ),
    array( 'label' => __( 'Option to go on a digital wall', 'emc-theme' ),               'levels' => array( 'companions', 'custodians' ) ),
    array( 'label' => __( 'Annual meeting and dinner with trustees and director', 'emc-theme' ), 'levels' => array( 'custodians' ) ),
    array( 'label' => __( 'Access to tickets for paid ticketed events', 'emc-theme' ),   'levels' => array( 'custodians' ) ),
    array( 'label' => __( 'Annual hosting for friends and family — tour, refreshments and a meeting room session, with the Imam or Director optional', 'emc-theme' ), 'levels' => array( 'custodians' ) ),
);

/* How membership income is allocated. Percentages must total 100. */
$allocations = array(
    array( 'label' => __( 'Facilities and Utilities', 'emc-theme' ),               'percent' => 70.0, 'colour' => '#1a3c2a' ),
    array( 'label' => __( 'Systems, Finance and Administration', 'emc-theme' ),    'percent' => 12.0, 'colour' => '#c9a227' ),
    array( 'label' => __( 'Outreach, Education and Events', 'emc-theme' ),         'percent' => 7.4,  'colour' => '#2d7a6e' ),
    array( 'label' => __( 'Professional Fees', 'emc-theme' ),                      'percent' => 5.6,  'colour' => '#a8442a' ),
    array( 'label' => __( 'Communication Costs', 'emc-theme' ),                    'percent' => 5.0,  'colour' => '#5a9e8e' ),
);
?>

<main class="emc-membership">

    <!-- ============================================================
         1. QUR'ANIC HEADER BANNER
         ============================================================ -->
    <section class="mem-ayah" id="membership-ayah" aria-labelledby="mem-ayah-title">
        <div class="mem-ayah-pattern" aria-hidden="true"></div>
        <div class="mem-container mem-ayah-inner">
            <h2 id="mem-ayah-title" class="mem-visually-hidden"><?php esc_html_e( 'Qur\'anic reminder', 'emc-theme' ); ?></h2>
            <p class="mem-ayah-arabic" lang="ar" dir="rtl"><?php echo esc_html( emc_acf( 'mem_ayah_arabic', 'إِنَّمَا يَعْمُرُ مَسَاجِدَ اللَّهِ مَنْ آمَنَ بِاللَّهِ وَالْيَوْمِ الْآخِرِ' ) ); ?></p>
            <p class="mem-ayah-english"><?php echo esc_html( emc_acf( 'mem_ayah_english', __( 'The mosques of Allah should only be maintained by those who believe in Allah and the Last Day, establish prayer, pay alms-tax, and fear none but Allah. It is right to hope that they will be among the truly guided.', 'emc-theme' ) ) ); ?></p>
            <p class="mem-ayah-ref"><?php echo esc_html( emc_acf( 'mem_ayah_reference', __( 'Qur\'an · Surah At-Tawbah (9:18)', 'emc-theme' ) ) ); ?></p>
        </div>
    </section>

    <!-- ============================================================
         2. BECOME A MEMBER — INTRODUCTION
         ============================================================ -->
    <section class="mem-section mem-intro" id="become-a-member" aria-labelledby="mem-intro-title">
        <div class="mem-container mem-narrow">
            <h1 id="mem-intro-title" class="mem-heading"><?php echo esc_html( emc_acf( 'mem_intro_heading', __( 'Become a Member', 'emc-theme' ) ) ); ?></h1>
            <p><?php echo esc_html( emc_acf( 'mem_intro_body_1', __( 'Essex Muslim Centre serves our community every single day through prayer, learning and care.', 'emc-theme' ) ) ); ?></p>
            <p><?php echo esc_html( emc_acf( 'mem_intro_body_2', __( 'Supporting your mosque means sharing in the reward of everything that takes place within it — quietly, consistently, and often unseen.', 'emc-theme' ) ) ); ?></p>
            <p class="mem-note"><?php echo esc_html( emc_acf( 'mem_intro_note', __( 'To learn more about what your regular donations cover, please see Membership Levels and Why Memberships Exist below.', 'emc-theme' ) ) ); ?></p>
        </div>
    </section>

    <!-- ============================================================
         3. MEMBERSHIP LEVELS
         ============================================================ -->
    <section class="mem-section mem-levels" id="membership-levels" aria-labelledby="mem-levels-title">
        <div class="mem-container">
            <header class="mem-section-head">
                <h2 id="mem-levels-title" class="mem-heading"><?php echo esc_html( emc_acf( 'mem_levels_heading', __( 'Membership Levels', 'emc-theme' ) ) ); ?></h2>
                <p><?php echo esc_html( emc_acf( 'mem_levels_intro', __( 'Choose the level of monthly support that feels right for you.', 'emc-theme' ) ) ); ?></p>
            </header>

            <div class="mem-domes">
                <?php foreach ( $levels as $level ) : ?>
                    <article class="mem-dome-card" id="level-<?php echo esc_attr( $level['key'] ); ?>">
                        <div class="mem-dome-art" aria-hidden="true">
                            <svg viewBox="0 0 200 170" role="presentation" focusable="false">
                                <!-- crescent finial -->
                                <path d="M100 6 a11 11 0 1 0 6.5 19.9 a8.6 8.6 0 1 1 0-17.2 A11 11 0 0 0 100 6 Z" fill="#c9a227"/>
                                <rect x="97.6" y="24" width="4.8" height="16" rx="2.4" fill="#c9a227"/>
                                <!-- dome -->
                                <path d="M100 38 C144 62 166 96 166 128 L166 152 L34 152 L34 128 C34 96 56 62 100 38 Z" fill="<?php echo esc_attr( $level['colour'] ); ?>"/>
                                <!-- base -->
                                <rect x="24" y="152" width="152" height="12" rx="4" fill="<?php echo esc_attr( $level['colour'] ); ?>" opacity="0.75"/>
                            </svg>
                        </div>

                        <h3 class="mem-dome-name"><?php echo esc_html( $level['name'] ); ?></h3>

                        <p class="mem-dome-price">
                            <strong><?php echo esc_html( '£' . number_format( $level['amount'], ( floor( $level['amount'] ) === $level['amount'] ) ? 0 : 2 ) ); ?></strong>
                            <span><?php esc_html_e( 'per month', 'emc-theme' ); ?></span>
                        </p>

                        <p class="mem-dome-desc"><?php echo esc_html( $level['desc'] ); ?></p>

                        <?php if ( isset( $available_levels[ $level['key'] ] ) && $stripe_ready ) : ?>
                            <button type="button" class="mem-btn mem-btn-outline" data-select-level="<?php echo esc_attr( $level['key'] ); ?>">
                                <?php
                                /* translators: %s: membership level name. */
                                echo esc_html( sprintf( __( 'Join as %s', 'emc-theme' ), $level['name'] ) );
                                ?>
                            </button>
                        <?php else : ?>
                            <a class="mem-btn mem-btn-outline" href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contact the centre', 'emc-theme' ); ?></a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Benefits matrix -->
            <h3 class="mem-subheading" id="membership-benefits"><?php echo esc_html( emc_acf( 'mem_benefits_heading', __( 'What Each Level Includes', 'emc-theme' ) ) ); ?></h3>

            <div class="mem-table-scroll">
                <table class="mem-benefits-table">
                    <caption class="mem-visually-hidden"><?php esc_html_e( 'Benefits included at each membership level', 'emc-theme' ); ?></caption>
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e( 'Benefit', 'emc-theme' ); ?></th>
                            <?php foreach ( $levels as $level ) : ?>
                                <th scope="col"><?php echo esc_html( $level['name'] ); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $benefits as $benefit ) : ?>
                            <tr>
                                <th scope="row"><?php echo esc_html( $benefit['label'] ); ?></th>
                                <?php foreach ( $levels as $level ) : ?>
                                    <?php $included = in_array( $level['key'], $benefit['levels'], true ); ?>
                                    <td class="<?php echo $included ? 'is-included' : 'is-excluded'; ?>">
                                        <span aria-hidden="true"><?php echo $included ? '&#10003;' : '&mdash;'; ?></span>
                                        <span class="mem-visually-hidden">
                                            <?php echo $included ? esc_html__( 'Included', 'emc-theme' ) : esc_html__( 'Not included', 'emc-theme' ); ?>
                                        </span>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- ============================================================
         4. WHAT YOUR MOSQUE DELIVERS
         ============================================================ -->
    <section class="mem-section mem-delivers" id="what-your-mosque-delivers" aria-labelledby="mem-delivers-title">
        <div class="mem-container">
            <header class="mem-section-head">
                <h2 id="mem-delivers-title" class="mem-heading"><?php echo esc_html( emc_acf( 'mem_delivers_heading', __( 'What Your Mosque Delivers', 'emc-theme' ) ) ); ?></h2>
                <p><?php echo esc_html( emc_acf( 'mem_delivers_intro', __( 'Your regular support keeps all of this running, all year round.', 'emc-theme' ) ) ); ?></p>
            </header>
            <ul class="mem-delivers-list">
                <?php foreach ( $deliverables as $item ) : ?>
                    <li><?php echo esc_html( $item ); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <!-- ============================================================
         5. WHY MEMBERSHIPS EXIST
         ============================================================ -->
    <section class="mem-section mem-why" id="why-memberships-exist" aria-labelledby="mem-why-title">
        <div class="mem-container mem-narrow">
            <h2 id="mem-why-title" class="mem-heading"><?php echo esc_html( emc_acf( 'mem_why_heading', __( 'Why Memberships Exist', 'emc-theme' ) ) ); ?></h2>
            <p><?php echo esc_html( emc_acf( 'mem_why_body_1', __( 'Much of EMC\'s work takes place every day and requires reliable support.', 'emc-theme' ) ) ); ?></p>
            <p><?php echo esc_html( emc_acf( 'mem_why_body_2', __( 'It costs a substantial annual sum to operate and maintain Essex Muslim Centre at its present level.', 'emc-theme' ) ) ); ?></p>
            <p><?php echo esc_html( emc_acf( 'mem_why_body_3', __( 'Memberships are about helping meet essential running costs so the centre remains stable and resilient.', 'emc-theme' ) ) ); ?></p>
        </div>
    </section>

    <!-- ============================================================
         6. HOW YOUR DONATIONS ARE USED
         ============================================================ -->
    <section class="mem-section mem-chart-section" id="how-donations-are-used" aria-labelledby="mem-chart-title">
        <div class="mem-container">
            <header class="mem-section-head">
                <h2 id="mem-chart-title" class="mem-heading"><?php echo esc_html( emc_acf( 'mem_chart_heading', __( 'How Your Donations Are Used', 'emc-theme' ) ) ); ?></h2>
                <p><?php echo esc_html( emc_acf( 'mem_chart_note', __( 'Approximate allocation of membership income across core running costs.', 'emc-theme' ) ) ); ?></p>
            </header>

            <div class="mem-chart-layout">
                <ul class="mem-chart-legend">
                    <?php foreach ( $allocations as $slice ) : ?>
                        <li>
                            <span class="mem-swatch" style="background:<?php echo esc_attr( $slice['colour'] ); ?>" aria-hidden="true"></span>
                            <span class="mem-legend-label"><?php echo esc_html( $slice['label'] ); ?></span>
                            <span class="mem-legend-value"><?php echo esc_html( number_format( $slice['percent'], ( floor( $slice['percent'] ) === $slice['percent'] ) ? 0 : 1 ) . '%' ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php
                /*
                 * Donut drawn with stroke-dasharray on a single circle per slice.
                 * Circumference of r=80 is ~502.65, so each slice's dash length is
                 * its percentage of that, and the offset is the running total.
                 */
                $circumference = 2 * M_PI * 80;
                $offset        = 0.0;
                ?>
                <div class="mem-chart">
                    <svg viewBox="0 0 200 200" role="img" aria-labelledby="mem-chart-desc">
                        <title id="mem-chart-desc"><?php esc_html_e( 'Allocation of membership income across core running costs', 'emc-theme' ); ?></title>
                        <g transform="rotate(-90 100 100)">
                            <?php foreach ( $allocations as $slice ) : ?>
                                <?php
                                $length  = $circumference * ( $slice['percent'] / 100 );
                                $dash    = sprintf( '%F %F', $length, $circumference - $length );
                                $dashoff = sprintf( '%F', -$offset );
                                $offset += $length;
                                ?>
                                <circle
                                    cx="100" cy="100" r="80"
                                    fill="none"
                                    stroke="<?php echo esc_attr( $slice['colour'] ); ?>"
                                    stroke-width="40"
                                    stroke-dasharray="<?php echo esc_attr( $dash ); ?>"
                                    stroke-dashoffset="<?php echo esc_attr( $dashoff ); ?>"
                                ></circle>
                            <?php endforeach; ?>
                        </g>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         7. FUNDS RAISED PROGRESS BAR
         ============================================================ -->
    <section class="mem-section mem-progress-section" id="funds-raised" aria-labelledby="mem-progress-title">
        <div class="mem-container mem-narrow">
            <h2 id="mem-progress-title" class="mem-heading mem-heading-sm"><?php echo esc_html( emc_acf( 'mem_progress_heading', __( 'Funds Raised To Date Via Memberships', 'emc-theme' ) ) ); ?></h2>

            <div class="mem-progress" role="img" aria-label="<?php echo esc_attr( sprintf( __( '%s per cent of the membership target raised so far', 'emc-theme' ), number_format( $progress, 0 ) ) ); ?>">
                <div class="mem-progress-track">
                    <div class="mem-progress-fill" style="width:<?php echo esc_attr( number_format( $progress, 2, '.', '' ) ); ?>%"></div>
                    <span class="mem-progress-dome" style="left:<?php echo esc_attr( number_format( $progress, 2, '.', '' ) ); ?>%" aria-hidden="true">
                        <svg viewBox="0 0 40 34" focusable="false"><path d="M20 2 C29 8 33 15 33 22 L33 28 L7 28 L7 22 C7 15 11 8 20 2 Z" fill="#1a3c2a"/><rect x="4" y="28" width="32" height="4" rx="2" fill="#1a3c2a"/></svg>
                    </span>
                </div>
                <div class="mem-progress-ticks" aria-hidden="true">
                    <?php for ( $tick = 0; $tick <= 10; $tick++ ) : ?><span></span><?php endfor; ?>
                </div>
                <p class="mem-progress-figures">
                    <strong><?php echo esc_html( number_format( $progress, 0 ) . '%' ); ?></strong>
                    <?php if ( $goal > 0 ) : ?>
                        <span><?php echo esc_html( sprintf( __( '£%1$s raised of £%2$s', 'emc-theme' ), number_format( $raised, 0 ), number_format( $goal, 0 ) ) ); ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </section>

    <!-- ============================================================
         8. STABILITY, WAQF & THE FUTURE
         ============================================================ -->
    <section class="mem-section mem-waqf" id="stability-waqf-future" aria-labelledby="mem-waqf-title">
        <div class="mem-container mem-narrow">
            <h2 id="mem-waqf-title" class="mem-heading"><?php echo esc_html( emc_acf( 'mem_waqf_heading', __( 'Stability, Waqf & The Future', 'emc-theme' ) ) ); ?></h2>
            <p><?php echo esc_html( emc_acf( 'mem_waqf_body_1', __( 'Once core running costs are covered, surplus funds allow EMC to plan responsibly for the future.', 'emc-theme' ) ) ); ?></p>
            <p><?php echo esc_html( emc_acf( 'mem_waqf_body_2', __( 'Regular support provides the stability required not only to maintain EMC today, but for the years ahead.', 'emc-theme' ) ) ); ?></p>
            <p class="mem-closing"><?php echo esc_html( emc_acf( 'mem_waqf_closing', __( 'If Essex Muslim Centre matters to you, Memberships are a way to support it with consistency, care and intention.', 'emc-theme' ) ) ); ?></p>
        </div>
    </section>

    <!-- ============================================================
         9. APPLICATION FORM
         ============================================================ -->
    <section class="mem-section mem-form-section" id="membership-application" aria-labelledby="mem-form-title">
        <div class="mem-container">
            <article class="mem-form-card">
                <header class="mem-form-head">
                    <h2 id="mem-form-title" class="mem-heading mem-heading-sm"><?php echo esc_html( emc_acf( 'mem_form_heading', __( 'Join Today', 'emc-theme' ) ) ); ?></h2>
                    <p><?php echo esc_html( emc_acf( 'mem_form_desc', __( 'Set up your monthly membership securely by card.', 'emc-theme' ) ) ); ?></p>
                </header>

                <?php if ( ! $stripe_ready || ! $available_levels ) : ?>

                    <div class="mem-notice" role="status">
                        <?php esc_html_e( 'Monthly card payments are temporarily unavailable, so memberships cannot be set up online right now.', 'emc-theme' ); ?>
                        <a href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contact the centre to join', 'emc-theme' ); ?></a>
                    </div>

                <?php else : ?>

                    <form
                        id="membership-form"
                        class="mem-form"
                        method="post"
                        novalidate
                        data-stripe-key="<?php echo esc_attr( $stripe_key ); ?>"
                        data-levels="<?php echo esc_attr( wp_json_encode( array_map( static function ( $level ) {
                            return array( 'key' => $level['key'], 'name' => $level['name'], 'pence' => $level['pence'] );
                        }, array_values( $available_levels ) ) ) ); ?>"
                    >
                        <input type="hidden" name="action" value="emc_membership_join">
                        <div class="mem-honeypot" aria-hidden="true">
                            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <div class="mem-field mem-field-wide">
                            <label for="membership-level"><?php esc_html_e( 'Membership level *', 'emc-theme' ); ?></label>
                            <select id="membership-level" name="level" required>
                                <?php foreach ( $available_levels as $level ) : ?>
                                    <option value="<?php echo esc_attr( $level['key'] ); ?>">
                                        <?php
                                        echo esc_html( sprintf(
                                            /* translators: 1: level name, 2: monthly amount. */
                                            __( '%1$s — £%2$s per month', 'emc-theme' ),
                                            $level['name'],
                                            number_format( $level['amount'], ( floor( $level['amount'] ) === $level['amount'] ) ? 0 : 2 )
                                        ) );
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mem-grid">
                            <div class="mem-field">
                                <label for="membership-first-name"><?php esc_html_e( 'First name *', 'emc-theme' ); ?></label>
                                <input type="text" id="membership-first-name" name="first_name" autocomplete="given-name" required>
                            </div>
                            <div class="mem-field">
                                <label for="membership-last-name"><?php esc_html_e( 'Last name *', 'emc-theme' ); ?></label>
                                <input type="text" id="membership-last-name" name="last_name" autocomplete="family-name" required>
                            </div>
                            <div class="mem-field">
                                <label for="membership-email"><?php esc_html_e( 'Email address *', 'emc-theme' ); ?></label>
                                <input type="email" id="membership-email" name="email" autocomplete="email" required>
                            </div>
                            <div class="mem-field">
                                <label for="membership-phone"><?php esc_html_e( 'Phone number', 'emc-theme' ); ?></label>
                                <input type="tel" id="membership-phone" name="phone" autocomplete="tel">
                            </div>
                            <div class="mem-field mem-field-wide">
                                <label for="membership-address-1"><?php esc_html_e( 'Address line 1 *', 'emc-theme' ); ?></label>
                                <input type="text" id="membership-address-1" name="address_1" autocomplete="address-line1" required>
                            </div>
                            <div class="mem-field mem-field-wide">
                                <label for="membership-address-2"><?php esc_html_e( 'Address line 2', 'emc-theme' ); ?></label>
                                <input type="text" id="membership-address-2" name="address_2" autocomplete="address-line2">
                            </div>
                            <div class="mem-field">
                                <label for="membership-city"><?php esc_html_e( 'Town or city', 'emc-theme' ); ?></label>
                                <input type="text" id="membership-city" name="city" autocomplete="address-level2">
                            </div>
                            <div class="mem-field">
                                <label for="membership-postcode"><?php esc_html_e( 'Postcode *', 'emc-theme' ); ?></label>
                                <input type="text" id="membership-postcode" name="postcode" autocomplete="postal-code" required>
                            </div>
                            <div class="mem-field mem-field-wide">
                                <label for="membership-notes"><?php esc_html_e( 'Anything else we should know?', 'emc-theme' ); ?></label>
                                <textarea id="membership-notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="mem-checkboxes">
                            <label class="mem-checkbox" for="membership-gift-aid">
                                <input type="checkbox" id="membership-gift-aid" name="gift_aid" value="1">
                                <span><?php echo esc_html( emc_acf( 'mem_giftaid_text', __( 'I am a UK taxpayer and Essex Muslim Centre may treat eligible payments as Gift Aid donations.', 'emc-theme' ) ) ); ?></span>
                            </label>
                            <label class="mem-checkbox" for="membership-consent">
                                <input type="checkbox" id="membership-consent" name="consent" value="1" required>
                                <span><?php echo esc_html( emc_acf( 'mem_consent_text', __( 'I agree that Essex Muslim Centre may hold these details to administer my membership.', 'emc-theme' ) ) ); ?></span>
                            </label>
                        </div>

                        <div class="mem-payment">
                            <div class="mem-total">
                                <span><?php esc_html_e( 'Charged monthly', 'emc-theme' ); ?></span>
                                <strong data-membership-total>&mdash;</strong>
                            </div>
                            <label for="membership-card"><?php esc_html_e( 'Card details', 'emc-theme' ); ?></label>
                            <div id="membership-card" class="mem-card-element"></div>
                            <p class="mem-card-errors" role="alert"></p>
                        </div>

                        <button type="submit" class="mem-btn mem-btn-primary mem-submit">
                            <span><?php echo esc_html( emc_acf( 'mem_form_button', __( 'Set Up Monthly Membership', 'emc-theme' ) ) ); ?></span>
                        </button>

                        <p class="mem-secure-note"><?php echo esc_html( emc_acf( 'mem_secure_note', __( 'Encrypted and secured by Stripe. Your card details are never stored on our website.', 'emc-theme' ) ) ); ?></p>
                        <p class="mem-form-status" role="status" tabindex="-1"></p>
                    </form>

                <?php endif; ?>

                <footer class="mem-form-footer">
                    <p><?php echo esc_html( emc_acf( 'mem_terms_text', __( 'Your membership renews automatically each month.', 'emc-theme' ) ) ); ?></p>
                    <p><?php echo esc_html( emc_acf( 'mem_contact_note', __( 'Prefer to join in person? Contact the centre office.', 'emc-theme' ) ) ); ?></p>
                </footer>
            </article>
        </div>
    </section>

</main>

<?php get_footer(); ?>
