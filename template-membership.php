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
 * Membership fees are monthly giving schedules created by the licensed EMC
 * Payments plugin, which owns the card form and the Stripe subscription.
 *
 * @package emc-theme
 */

/*
 * Load the payment plugin's modal and its window.emcOpenStripeModal() bridge.
 * The "campaign" context is used because, like this page, the campaign pages
 * only need the bridge and the modal rather than the Donate page's own form.
 */
$emc_payments_available = emc_membership_payments_available();
if ( $emc_payments_available && function_exists( 'emc_payments_enqueue_assets' ) ) {
    emc_payments_enqueue_assets( 'campaign' );
}

get_header();

$levels       = emc_membership_levels();
$stripe_ready = $emc_payments_available;
$contact_url  = get_permalink( get_page_by_path( 'contact' ) ) ?: home_url( '/contact/' );

/* Levels that can actually be charged: at or above Stripe's 50p minimum. */
$available_levels = array_filter( $levels, static function ( $level ) {
    return $level['pence'] >= 50;
} );

/*
 * Highlight the middle tier so the three cards have a visual anchor rather than
 * reading as three equal-weight options. Derived from position, not a hardcoded
 * key, so renaming a level in the Customizer does not break the highlight.
 */
$level_keys   = array_values( wp_list_pluck( $levels, 'key' ) );
$featured_key = count( $level_keys ) > 2
    ? $level_keys[ (int) floor( ( count( $level_keys ) - 1 ) / 2 ) ]
    : '';

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

            <?php
            /*
             * All three lines share one centred column so the Arabic, the
             * translation and the reference stack on a common axis. Without the
             * wrapper the translation's auto side margins lose to the broader
             * ".emc-membership p" rule and it drifts flush left.
             */
            ?>
            <div class="mem-ayah-stack">
                <p class="mem-ayah-arabic" lang="ar" dir="rtl"><?php echo esc_html( emc_acf( 'mem_ayah_arabic', 'إِنَّمَا يَعْمُرُ مَسَاجِدَ اللَّهِ مَنْ آمَنَ بِاللَّهِ وَالْيَوْمِ الْآخِرِ' ) ); ?></p>
                <p class="mem-ayah-english"><?php echo esc_html( emc_acf( 'mem_ayah_english', __( 'The mosques of Allah should only be maintained by those who believe in Allah and the Last Day, establish prayer, pay alms-tax, and fear none but Allah. It is right to hope that they will be among the truly guided.', 'emc-theme' ) ) ); ?></p>
                <p class="mem-ayah-ref"><?php echo esc_html( emc_acf( 'mem_ayah_reference', __( 'Qur\'an · Surah At-Tawbah (9:18)', 'emc-theme' ) ) ); ?></p>
            </div>
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
                    <?php $is_featured = ( $featured_key && $level['key'] === $featured_key ); ?>
                    <article class="mem-dome-card<?php echo $is_featured ? ' is-featured' : ''; ?>" id="level-<?php echo esc_attr( $level['key'] ); ?>">
                        <?php if ( $is_featured ) : ?>
                            <p class="mem-dome-flag"><?php esc_html_e( 'Most chosen', 'emc-theme' ); ?></p>
                        <?php endif; ?>
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
                            <button type="button" class="mem-btn mem-btn-primary mem-dome-cta" data-select-level="<?php echo esc_attr( $level['key'] ); ?>">
                                <?php
                                /* translators: %s: membership level name. */
                                echo esc_html( sprintf( __( 'Join as %s', 'emc-theme' ), $level['name'] ) );
                                ?>
                            </button>
                            <p class="mem-dome-cta-note">
                                <?php
                                /* translators: %s: formatted monthly amount, e.g. £10. */
                                echo esc_html( sprintf( __( '%s each month · cancel any time', 'emc-theme' ), '£' . number_format( $level['amount'], ( floor( $level['amount'] ) === $level['amount'] ) ? 0 : 2 ) ) );
                                ?>
                            </p>
                        <?php else : ?>
                            <a class="mem-btn mem-btn-primary mem-dome-cta" href="<?php echo esc_url( $contact_url ); ?>">
                                <?php
                                /* translators: %s: membership level name. */
                                echo esc_html( sprintf( __( 'Enquire about %s', 'emc-theme' ), $level['name'] ) );
                                ?>
                            </a>
                            <p class="mem-dome-cta-note"><?php esc_html_e( 'Online sign-up is coming soon', 'emc-theme' ); ?></p>
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
                                    <?php /* data-label drives the stacked mobile layout below 700px. */ ?>
                                    <td class="<?php echo $included ? 'is-included' : 'is-excluded'; ?>" data-label="<?php echo esc_attr( $level['name'] ); ?>">
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
                <p><?php echo esc_html( emc_acf( 'mem_chart_note', __( 'Approximate allocation of membership income across core running costs and education.', 'emc-theme' ) ) ); ?></p>
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
                        <title id="mem-chart-desc"><?php esc_html_e( 'Allocation of membership income across core running costs and education', 'emc-theme' ); ?></title>
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

            <?php
            /*
             * The percentage is handed to CSS as a custom property rather than as
             * an inline width/left. That lets the stylesheet clamp the dome so it
             * stays inside the track at 0% and 100% instead of hanging over the
             * edge, and keeps the empty state looking deliberate.
             */
            $progress_attr = number_format( $progress, 2, '.', '' );

            $progress_label = $goal > 0
                ? sprintf(
                    /* translators: 1: percentage raised, 2: amount raised, 3: target amount. */
                    __( '%1$s per cent of the membership target raised so far — £%2$s of £%3$s', 'emc-theme' ),
                    number_format( $progress, 0 ),
                    number_format( $raised, 0 ),
                    number_format( $goal, 0 )
                )
                : __( 'Membership target not yet published', 'emc-theme' );
            ?>

            <div class="mem-progress<?php echo $progress <= 0 ? ' is-empty' : ''; ?>" style="--mem-pct:<?php echo esc_attr( $progress_attr ); ?>">
                <div class="mem-progress-track" role="img" aria-label="<?php echo esc_attr( $progress_label ); ?>">
                    <div class="mem-progress-fill"></div>
                    <span class="mem-progress-dome" aria-hidden="true">
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
                    <?php else : ?>
                        <span><?php echo esc_html( sprintf( __( '£%s raised so far', 'emc-theme' ), number_format( $raised, 0 ) ) ); ?></span>
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
    <?php
    /*
     * With card payments switched off the form collapses to a short notice. Left
     * inside the full-height card and section padding that leaves a large band of
     * dead space above the footer, so the whole section is rendered compact.
     */
    $form_is_compact = ( ! $stripe_ready || ! $available_levels );
    ?>
    <section class="mem-section mem-form-section<?php echo $form_is_compact ? ' is-compact' : ''; ?>" id="membership-application" aria-labelledby="mem-form-title">
        <div class="mem-container">
            <article class="mem-form-card<?php echo $form_is_compact ? ' is-compact' : ''; ?>">
                <header class="mem-form-head">
                    <h2 id="mem-form-title" class="mem-heading mem-heading-sm"><?php echo esc_html( emc_acf( 'mem_form_heading', __( 'Join Today', 'emc-theme' ) ) ); ?></h2>
                    <p>
                        <?php if ( $form_is_compact ) : ?>
                            <?php esc_html_e( 'Online card sign-up is not switched on yet, so memberships are being set up by the centre directly.', 'emc-theme' ); ?>
                        <?php else : ?>
                            <?php echo esc_html( emc_acf( 'mem_form_desc', __( 'Set up your monthly membership securely by card.', 'emc-theme' ) ) ); ?>
                        <?php endif; ?>
                    </p>
                </header>

                <?php if ( $form_is_compact ) : ?>

                    <a class="mem-btn mem-btn-primary mem-compact-cta" href="<?php echo esc_url( $contact_url ); ?>">
                        <?php esc_html_e( 'Contact the centre to join', 'emc-theme' ); ?>
                    </a>

                <?php else : ?>

                    <form
                        id="membership-form"
                        class="mem-form"
                        method="post"
                        novalidate
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
                            <p class="mem-payment-note">
                                <?php esc_html_e( 'Card details are entered in the secure payment window that opens next.', 'emc-theme' ); ?>
                            </p>
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

                    <?php
                    /*
                     * wa.me wants the number in full international form with no
                     * punctuation; the Customizer sanitiser guarantees that, and
                     * returns an empty string when the button should be hidden.
                     */
                    $whatsapp_number = preg_replace( '/\D+/', '', (string) get_theme_mod( 'mem_whatsapp_number', '447399079436' ) );

                    if ( $whatsapp_number ) :
                        ?>
                        <a
                            class="mem-whatsapp"
                            href="<?php echo esc_url( 'https://wa.me/' . $whatsapp_number ); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <svg class="mem-whatsapp-icon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                                <path fill="currentColor" d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.87 9.87 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm0 18.15h-.01a8.2 8.2 0 0 1-4.18-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 0 1-1.26-4.38c0-4.54 3.7-8.23 8.24-8.23a8.18 8.18 0 0 1 8.23 8.24c0 4.54-3.7 8.23-8.23 8.23Zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.16.24-.64.8-.78.97-.15.16-.29.18-.53.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.01-.38.11-.5.11-.11.25-.29.37-.43.13-.15.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.23.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.47-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.15-1.18-.06-.1-.22-.16-.47-.28Z"/>
                            </svg>
                            <?php echo esc_html( emc_acf( 'mem_whatsapp_label', __( 'Join Via WhatsApp', 'emc-theme' ) ) ); ?>
                        </a>
                    <?php endif; ?>
                </footer>
            </article>
        </div>
    </section>

</main>

<?php get_footer(); ?>
