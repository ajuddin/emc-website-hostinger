<?php
/**
 * Template Name: Campaign
 * Template Post Type: page
 *
 * EMC Theme — Building Campaign page template.
 * Full campaign landing page with hero, progress, donor wall, and donate section.
 *
 * @package emc-theme
 */

get_header();

wp_enqueue_style( 'emc-page-donate',   EMC_ASSETS . '/css/donate.css',   array( 'emc-style' ), EMC_VERSION );
wp_enqueue_style( 'emc-page-campaign', EMC_ASSETS . '/css/campaign.css', array( 'emc-style', 'emc-page-donate' ), EMC_VERSION );

// Stripe.js - must load from js.stripe.com for PCI compliance.
wp_register_script( 'stripe-js', 'https://js.stripe.com/v3/', array(), null, true );
wp_enqueue_script( 'stripe-js' );

$donate_js_path = EMC_DIR . '/assets/js/donate.js';
if ( file_exists( $donate_js_path ) ) {
    wp_enqueue_script(
        'emc-page-donate',
        EMC_ASSETS . '/js/donate.js',
        array( 'emc-script', 'stripe-js' ),
        filemtime( $donate_js_path ),
        true
    );
    wp_localize_script( 'emc-page-donate', 'emcStripeConfig', array(
        'publishableKey' => emc_stripe_pub_key(),
        'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
        'nonce'          => wp_create_nonce( 'emc_donate_nonce' ),
    ) );
}

$campaign_js_path = EMC_DIR . '/assets/js/campaign.js';
if ( file_exists( $campaign_js_path ) ) {
    wp_enqueue_script( 'emc-page-campaign', EMC_ASSETS . '/js/campaign.js', array( 'emc-script', 'emc-page-donate' ), filemtime( $campaign_js_path ), true );
}

// Campaign data from Customizer
$badge      = emc_option( 'emc_campaign_badge',   __( 'Building Fund', 'emc-theme' ) );
$heading    = emc_option( 'emc_campaign_heading',  __( 'Be One of the 313', 'emc-theme' ) );
$desc       = emc_option( 'emc_campaign_desc',     __( 'Help us build a lasting place of worship for future generations. Our building campaign needs your generous support. Every pound brings us closer to our goal.', 'emc-theme' ) );
$raised     = (int) emc_option( 'emc_campaign_raised', 68400 );
$target     = (int) emc_option( 'emc_campaign_target', 100000 );
$donors     = (int) emc_option( 'emc_campaign_donors', 247 );
$cta_label  = emc_option( 'emc_campaign_cta_label', __( 'Choose Badr Wall Level', 'emc-theme' ) );
$cta_url    = emc_option( 'emc_campaign_cta_url', '' ) ?: '#badr-membership';
$percent    = $target > 0 ? min( 100, round( ( $raised / $target ) * 100 ) ) : 0;
$bank_pay_url = 'https://paymentrequest.natwestpayit.com/reusable-link/39ee348b-8fe1-41fe-aa6b-9109dc847445';
$phone_digits = preg_replace( '/\D+/', '', emc_option( 'emc_phone', '' ) );
$pledge_text  = rawurlencode( 'Assalamu alaikum, I would like to pledge towards the Badr Wall building fund.' );
$whatsapp_url = $phone_digits ? 'https://wa.me/' . $phone_digits . '?text=' . $pledge_text : ( get_permalink( get_page_by_path( 'contact' ) ) ?: home_url( '/contact/' ) );
$badr_levels  = array(
    array(
        'id'     => 'founder',
        'label'  => __( 'Founder of the Centre', 'emc-theme' ),
        'amount' => 10000,
        'icon'   => 'fas fa-trophy',
        'class'  => 'tier-founder',
    ),
    array(
        'id'     => 'co-founder',
        'label'  => __( 'Co-Founder of the Centre', 'emc-theme' ),
        'amount' => 5000,
        'icon'   => 'fas fa-star',
        'class'  => 'tier-cofunder',
    ),
    array(
        'id'     => 'golden',
        'label'  => __( 'Golden Donor of the Centre', 'emc-theme' ),
        'amount' => 3000,
        'icon'   => 'fas fa-gem',
        'class'  => 'tier-golden',
    ),
    array(
        'id'     => 'silver',
        'label'  => __( 'Silver Donor of the Centre', 'emc-theme' ),
        'amount' => 2000,
        'icon'   => 'fas fa-medal',
        'class'  => 'tier-silver',
    ),
    array(
        'id'     => 'friend',
        'label'  => __( 'Friends of the Centre', 'emc-theme' ),
        'amount' => 1000,
        'icon'   => 'fas fa-handshake',
        'class'  => 'tier-friend',
    ),
);
?>

<!-- Campaign Hero -->
<section class="campaign-hero" aria-labelledby="campaign-hero-heading">
    <div class="container">
        <div class="campaign-hero-inner">

            <!-- Text Column -->
            <div class="campaign-hero-text">
                <span class="campaign-tag">
                    <i class="fas fa-star-and-crescent" aria-hidden="true"></i>
                    <?php echo esc_html( $badge ); ?>
                </span>
                <h1 id="campaign-hero-heading">
                    <?php echo esc_html( $heading ); ?>
                </h1>
                <p><?php echo esc_html( $desc ); ?></p>

                <!-- Progress Box -->
                <div class="campaign-progress-box">
                    <div class="progress-stat-row">
                        <div class="progress-stat">
                            <span class="stat-value">£<?php echo esc_html( number_format( $raised ) ); ?></span>
                            <span class="stat-label"><?php esc_html_e( 'Raised', 'emc-theme' ); ?></span>
                        </div>
                        <div class="progress-stat center">
                            <span class="stat-value"><?php echo esc_html( $percent ); ?>%</span>
                            <span class="stat-label"><?php esc_html_e( 'Funded', 'emc-theme' ); ?></span>
                        </div>
                        <div class="progress-stat right">
                            <span class="stat-value">£<?php echo esc_html( number_format( $target ) ); ?></span>
                            <span class="stat-label"><?php esc_html_e( 'Target', 'emc-theme' ); ?></span>
                        </div>
                    </div>
                    <div class="campaign-track">
                        <div class="campaign-fill" style="width: <?php echo esc_attr( $percent ); ?>%">
                            <span class="campaign-pulse"></span>
                        </div>
                    </div>
                    <div class="donor-count-row">
                        <i class="fas fa-users" aria-hidden="true"></i>
                        <?php printf(
                            esc_html__( '%s donors have contributed', 'emc-theme' ),
                            '<strong>' . esc_html( number_format( $donors ) ) . '</strong>'
                        ); ?>
                    </div>
                </div>

                <div class="campaign-cta-row" style="display:flex;gap:1rem;flex-wrap:wrap;">
                    <a href="#badr-membership" class="btn btn-primary">
                        <i class="fas fa-heart" aria-hidden="true"></i>
                        <?php esc_html_e( 'Choose Your Badr Wall Level', 'emc-theme' ); ?>
                    </a>
                </div>
            </div>

            <!-- Visual Column -->
            <div class="campaign-hero-visual" aria-hidden="true">
                <div class="campaign-icon-tower">
                    <i class="fas fa-mosque"></i>
                    <div class="tower-rings">
                        <div class="tower-ring r1"></div>
                        <div class="tower-ring r2"></div>
                        <div class="tower-ring r3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Badr Wall — Tiered Donor Recognition -->
<section class="donor-wall section-padding" aria-label="<?php esc_attr_e( 'Badr Wall of Honour', 'emc-theme' ); ?>">
    <div class="container">
        <div class="text-center" style="margin-bottom:1.5rem;">
            <span class="campaign-tag" style="margin-bottom:1rem;"><i class="fas fa-star-and-crescent"></i> <?php esc_html_e( 'The Badr Wall', 'emc-theme' ); ?></span>
            <h2><?php esc_html_e( 'Wall of Honour', 'emc-theme' ); ?></h2>
            <p style="color:rgba(255,255,255,0.65);max-width:600px;margin:0 auto;">
                <?php esc_html_e( 'Inspired by the 313 companions of Badr — join our founding donors and be honoured permanently on this wall.', 'emc-theme' ); ?>
            </p>
        </div>

        <!-- Tier Legend -->
        <div class="badr-tier-legend">
            <div class="tier-legend-item tier-founder">
                <i class="fas fa-trophy"></i>
                <div>
                    <strong><?php esc_html_e( 'Founder of the Centre', 'emc-theme' ); ?></strong>
                    <span><?php esc_html_e( '£10,000+', 'emc-theme' ); ?></span>
                </div>
            </div>
            <div class="tier-legend-item tier-cofunder">
                <i class="fas fa-star"></i>
                <div>
                    <strong><?php esc_html_e( 'Co-Founder of the Centre', 'emc-theme' ); ?></strong>
                    <span><?php esc_html_e( '£5,000+', 'emc-theme' ); ?></span>
                </div>
            </div>
            <div class="tier-legend-item tier-golden">
                <i class="fas fa-gem"></i>
                <div>
                    <strong><?php esc_html_e( 'Golden Donor of the Centre', 'emc-theme' ); ?></strong>
                    <span><?php esc_html_e( '£3,000', 'emc-theme' ); ?></span>
                </div>
            </div>
            <div class="tier-legend-item tier-silver">
                <i class="fas fa-medal"></i>
                <div>
                    <strong><?php esc_html_e( 'Silver Donor of the Centre', 'emc-theme' ); ?></strong>
                    <span><?php esc_html_e( '£2,000', 'emc-theme' ); ?></span>
                </div>
            </div>
            <div class="tier-legend-item tier-friend">
                <i class="fas fa-handshake"></i>
                <div>
                    <strong><?php esc_html_e( 'Friends of the Centre', 'emc-theme' ); ?></strong>
                    <span><?php esc_html_e( '£1,000', 'emc-theme' ); ?></span>
                </div>
            </div>
        </div>

        <div class="badr-membership-card glass-card" id="badr-membership">
            <div class="badr-membership-head">
                <div>
                    <span class="campaign-tag"><i class="fas fa-heart" aria-hidden="true"></i> <?php esc_html_e( 'Badr Wall Membership', 'emc-theme' ); ?></span>
                    <h3><?php esc_html_e( 'Secure Your Place on the Badr Wall', 'emc-theme' ); ?></h3>
                    <p><?php esc_html_e( 'Choose your recognition level, then pay by card, pay by bank, or contact the team to agree an instalment schedule.', 'emc-theme' ); ?></p>
                </div>
                <div class="badr-selected-summary" aria-live="polite">
                    <span><?php esc_html_e( 'Selected', 'emc-theme' ); ?></span>
                    <strong id="badr-selected-label"><?php echo esc_html( $badr_levels[0]['label'] ); ?></strong>
                    <em id="badr-selected-amount"><?php echo esc_html( '£' . number_format( $badr_levels[0]['amount'] ) . '+' ); ?></em>
                </div>
            </div>

            <div class="badr-tier-picker" role="radiogroup" aria-label="<?php esc_attr_e( 'Choose Badr Wall level', 'emc-theme' ); ?>">
                <?php foreach ( $badr_levels as $index => $level ) : ?>
                <button
                    type="button"
                    class="badr-tier-option <?php echo esc_attr( $level['class'] ); ?><?php echo 0 === $index ? ' active' : ''; ?>"
                    data-tier="<?php echo esc_attr( $level['id'] ); ?>"
                    data-label="<?php echo esc_attr( $level['label'] ); ?>"
                    data-amount="<?php echo esc_attr( $level['amount'] ); ?>"
                    data-plus="<?php echo in_array( $level['id'], array( 'founder', 'co-founder' ), true ) ? '1' : '0'; ?>"
                    role="radio"
                    aria-checked="<?php echo 0 === $index ? 'true' : 'false'; ?>">
                    <i class="<?php echo esc_attr( $level['icon'] ); ?>" aria-hidden="true"></i>
                    <span><?php echo esc_html( $level['label'] ); ?></span>
                    <strong>
                        <?php
                        echo esc_html( '£' . number_format( $level['amount'] ) );
                        if ( in_array( $level['id'], array( 'founder', 'co-founder' ), true ) ) {
                            echo '+';
                        }
                        ?>
                    </strong>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="badr-donor-fields">
                <div class="form-group">
                    <label for="badr-donor-name"><?php esc_html_e( 'Full Name *', 'emc-theme' ); ?></label>
                    <input type="text" id="badr-donor-name" class="form-control" autocomplete="name" required>
                </div>
                <div class="form-group">
                    <label for="badr-donor-email"><?php esc_html_e( 'Email Address *', 'emc-theme' ); ?></label>
                    <input type="email" id="badr-donor-email" class="form-control" autocomplete="email" required>
                </div>
                <div class="form-group badr-address-field">
                    <label for="badr-donor-address"><?php esc_html_e( 'Address *', 'emc-theme' ); ?></label>
                    <textarea id="badr-donor-address" class="form-control" rows="2" autocomplete="street-address" required></textarea>
                </div>
            </div>

            <div class="badr-payment-actions">
                <button type="button" class="btn btn-primary" id="badr-card-pay">
                    <i class="fas fa-credit-card" aria-hidden="true"></i>
                    <?php esc_html_e( 'Pay by Card', 'emc-theme' ); ?>
                </button>
                <a href="<?php echo esc_url( $bank_pay_url ); ?>" class="btn btn-outline" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-university" aria-hidden="true"></i>
                    <?php esc_html_e( 'Pay by Bank', 'emc-theme' ); ?>
                </a>
                <a href="<?php echo esc_url( $whatsapp_url ); ?>" class="btn btn-outline" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-whatsapp" aria-hidden="true"></i>
                    <?php esc_html_e( 'WhatsApp Pledge', 'emc-theme' ); ?>
                </a>
            </div>
            <p class="badr-payment-note">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <?php esc_html_e( 'For instalments, select WhatsApp Pledge so the team can agree a payment schedule with you.', 'emc-theme' ); ?>
            </p>
        </div>

        <?php
        $tiers = array(
            array(
                'id'       => 'founder',
                'label'    => __( 'Founder of the Centre', 'emc-theme' ),
                'icon'     => 'fas fa-trophy',
                'class'    => 'tier-founder',
                'total'    => 10,
                'filled'   => min( (int) emc_option( 'emc_campaign_tier1_filled', 2 ), 10 ),
                'desc'     => __( '£10,000+ — founding places', 'emc-theme' ),
            ),
            array(
                'id'       => 'co-founder',
                'label'    => __( 'Co-Founder of the Centre', 'emc-theme' ),
                'icon'     => 'fas fa-star',
                'class'    => 'tier-cofunder',
                'total'    => 30,
                'filled'   => min( (int) emc_option( 'emc_campaign_tier2_filled', 8 ), 30 ),
                'desc'     => __( '£5,000+ — co-founder places', 'emc-theme' ),
            ),
            array(
                'id'       => 'golden',
                'label'    => __( 'Golden Donor of the Centre', 'emc-theme' ),
                'icon'     => 'fas fa-gem',
                'class'    => 'tier-golden',
                'total'    => 50,
                'filled'   => min( (int) emc_option( 'emc_campaign_tier3_filled', 12 ), 50 ),
                'desc'     => __( '£3,000 — golden donor places', 'emc-theme' ),
            ),
            array(
                'id'       => 'silver',
                'label'    => __( 'Silver Donor of the Centre', 'emc-theme' ),
                'icon'     => 'fas fa-medal',
                'class'    => 'tier-silver',
                'total'    => 80,
                'filled'   => min( (int) emc_option( 'emc_campaign_tier4_filled', 15 ), 80 ),
                'desc'     => __( '£2,000 — silver donor places', 'emc-theme' ),
            ),
            array(
                'id'       => 'friend',
                'label'    => __( 'Friends of the Centre', 'emc-theme' ),
                'icon'     => 'fas fa-handshake',
                'class'    => 'tier-friend',
                'total'    => 143,
                'filled'   => min( max( 0, $donors - 37 ), 143 ),
                'desc'     => __( '£1,000 — friends places', 'emc-theme' ),
            ),
        );
        ?>

        <?php foreach ( $tiers as $tier ) :
            $empty = $tier['total'] - $tier['filled'];
            $show_filled = min( $tier['filled'], 12 );
            $show_empty  = min( $empty, 6 );
        ?>
        <div class="badr-tier-section" id="badr-<?php echo esc_attr( $tier['id'] ); ?>">
            <div class="badr-tier-header">
                <div class="badr-tier-title <?php echo esc_attr( $tier['class'] ); ?>">
                    <i class="<?php echo esc_attr( $tier['icon'] ); ?>" aria-hidden="true"></i>
                    <div>
                        <h3><?php echo esc_html( $tier['label'] ); ?></h3>
                        <p><?php echo esc_html( $tier['desc'] ); ?></p>
                    </div>
                </div>
                <div class="badr-tier-count">
                    <span class="filled-count"><?php echo esc_html( $tier['filled'] ); ?></span>
                    <span class="total-count">/ <?php echo esc_html( $tier['total'] ); ?> <?php esc_html_e( 'filled', 'emc-theme' ); ?></span>
                </div>
            </div>
            <div class="donor-slots-grid badr-slots">
                <?php for ( $i = 0; $i < $show_filled; $i++ ) : ?>
                <div class="donor-slot filled <?php echo esc_attr( $tier['class'] ); ?>">
                    <i class="<?php echo esc_attr( $tier['icon'] ); ?>" aria-hidden="true"></i>
                    <span><?php printf( esc_html__( 'Donor #%d', 'emc-theme' ), $i + 1 ); ?></span>
                </div>
                <?php endfor; ?>
                <?php for ( $i = 0; $i < $show_empty; $i++ ) : ?>
                <a href="#badr-membership" class="donor-slot empty <?php echo esc_attr( $tier['class'] ); ?>-empty" data-badr-tier="<?php echo esc_attr( $tier['id'] ); ?>">
                    <i class="fas fa-plus-circle" aria-hidden="true"></i>
                    <span><?php esc_html_e( 'Claim your place', 'emc-theme' ); ?></span>
                </a>
                <?php endfor; ?>
                <?php if ( $tier['filled'] > $show_filled || $empty > $show_empty ) : ?>
                <div class="badr-more-slots">
                    <i class="fas fa-ellipsis-h"></i>
                    <span><?php printf( esc_html__( '%d more places available', 'emc-theme' ), max( 0, $empty ) ); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="text-center" style="margin-top:3rem;">
            <a href="#badr-membership" class="btn btn-primary" style="font-size:var(--step-0);padding:1rem 2.5rem;">
                <i class="fas fa-heart" aria-hidden="true"></i>
                <?php esc_html_e( 'Secure Your Place on the Badr Wall', 'emc-theme' ); ?>
            </a>
        </div>
    </div>
</section>


<!-- Campaign Donate Section -->
<section class="section-padding" style="background:var(--light-bg);" aria-label="<?php esc_attr_e( 'Campaign donation', 'emc-theme' ); ?>">
    <div class="container">
        <div class="campaign-donate-layout">

            <!-- Left: Donate Card -->
            <div>
                <div class="form-card glass-card">
                    <h3><?php esc_html_e( 'Join the Badr Wall', 'emc-theme' ); ?></h3>
                    <p class="form-desc"><?php esc_html_e( 'Choose one of the recognised Badr Wall levels and complete your membership payment above.', 'emc-theme' ); ?></p>
                    <div class="amount-grid" style="grid-template-columns: repeat(4, 1fr);">
                        <button class="amount-btn">£1,000</button>
                        <button class="amount-btn">£2,000</button>
                        <button class="amount-btn active">£3,000</button>
                        <button class="amount-btn">£5,000+</button>
                    </div>

                    <div class="monthly-313-badge">
                        <i class="fas fa-award" aria-hidden="true"></i>
                        <div>
                            <strong><?php esc_html_e( 'Badr Wall recognition starts from £1,000', 'emc-theme' ); ?></strong>
                            <p style="margin:0;color:var(--text-muted);font-size:var(--step--2);">
                                <?php esc_html_e( 'For instalments or a custom schedule, use the WhatsApp pledge option and the team will contact you.', 'emc-theme' ); ?>
                            </p>
                        </div>
                    </div>

                    <a href="#badr-membership" class="btn btn-primary donate-submit">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <?php esc_html_e( 'Choose Badr Wall Level', 'emc-theme' ); ?>
                    </a>
                    <p class="secure-note" style="text-align:center;margin-top:1rem;font-size:var(--step--2);color:var(--text-muted);">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <?php esc_html_e( 'Encrypted & secured by Stripe. 100% of your donation goes to the building fund.', 'emc-theme' ); ?>
                    </p>
                </div>
            </div>

            <!-- Right: Why It Matters -->
            <div>
                <div class="campaign-why-card glass-card">
                    <h4><i class="fas fa-mosque" aria-hidden="true"></i> <?php esc_html_e( 'Why This Matters', 'emc-theme' ); ?></h4>
                    <div class="why-list">
                        <div class="why-item">
                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                            <div>
                                <strong><?php esc_html_e( 'Purpose-Built Centre', 'emc-theme' ); ?></strong>
                                <p><?php esc_html_e( 'A permanent, purpose-built centre for Chelmsford\'s Muslim community.', 'emc-theme' ); ?></p>
                            </div>
                        </div>
                        <div class="why-item">
                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                            <div>
                                <strong><?php esc_html_e( 'Capacity for 500+', 'emc-theme' ); ?></strong>
                                <p><?php esc_html_e( 'Space for over 500 worshippers during Jumu\'ah prayers.', 'emc-theme' ); ?></p>
                            </div>
                        </div>
                        <div class="why-item">
                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                            <div>
                                <strong><?php esc_html_e( 'Community Wings', 'emc-theme' ); ?></strong>
                                <p><?php esc_html_e( 'Dedicated youth, women\'s, and education wings for all age groups.', 'emc-theme' ); ?></p>
                            </div>
                        </div>
                        <div class="why-item">
                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                            <div>
                                <strong><?php esc_html_e( 'Gift Aid Eligible', 'emc-theme' ); ?></strong>
                                <p><?php esc_html_e( 'All donations qualify for Gift Aid, adding 25% at no extra cost to you.', 'emc-theme' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// If the page has additional editor content, render it below
$content = get_the_content();
if ( $content && trim( strip_tags( $content ) ) ) :
?>
<section class="section-padding">
    <div class="container">
        <div class="entry-content prose" style="max-width:800px;margin:0 auto;">
            <?php the_content(); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
