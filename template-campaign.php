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

$emc_payments_available = function_exists( 'emc_payments_is_available' ) && emc_payments_is_available();
if ( $emc_payments_available ) {
    emc_payments_enqueue_assets( 'campaign' );
}

$campaign_css_path = EMC_DIR . '/assets/css/campaign.css';
wp_enqueue_style(
    'emc-page-campaign',
    EMC_ASSETS . '/css/campaign.css',
    array( 'emc-style', 'emc-page-donate' ),
    file_exists( $campaign_css_path ) ? filemtime( $campaign_css_path ) : EMC_VERSION
);

$campaign_js_path = EMC_DIR . '/assets/js/campaign.js';
if ( file_exists( $campaign_js_path ) ) {
    wp_enqueue_script( 'emc-page-campaign', EMC_ASSETS . '/js/campaign.js', array( 'emc-script', 'emc-page-donate' ), filemtime( $campaign_js_path ), true );
}

get_header();

if ( ! $emc_payments_available ) {
    emc_render_payment_plugin_required();
    get_footer();
    return;
}

if ( ! emc_payment_license_is_active() ) {
    emc_payment_license_render_required();
    get_footer();
    return;
}

// Campaign data from Customizer
$badge      = emc_option( 'emc_campaign_badge',   __( 'Building Fund', 'emc-theme' ) );
$heading    = emc_option( 'emc_campaign_heading',  __( 'Be One of the 313', 'emc-theme' ) );
$desc       = emc_option( 'emc_campaign_desc',     __( 'Help us build a lasting place of worship for future generations. Our building campaign needs your generous support. Every pound brings us closer to our goal.', 'emc-theme' ) );
$bank_pay_url = emc_site_setting( 'emc_bank_pay_url', 'https://paymentrequest.natwestpayit.com/reusable-link/39ee348b-8fe1-41fe-aa6b-9109dc847445' );
$phone_digits = preg_replace( '/\D+/', '', emc_option( 'emc_phone', '' ) );
$pledge_text  = rawurlencode( 'Assalamu alaikum, I would like to pledge towards the Badr Wall building fund.' );
$whatsapp_url = $phone_digits ? 'https://wa.me/' . $phone_digits . '?text=' . $pledge_text : ( get_permalink( get_page_by_path( 'contact' ) ) ?: home_url( '/contact/' ) );
$badr_levels          = emc_get_badr_levels();
$badr_total_remaining = array_sum( array_column( $badr_levels, 'remaining' ) );
$badr_tiles_url       = emc_get_badr_tiles_url();
$badr_tile_previews   = array_fill_keys( array_column( $badr_levels, 'id' ), array() );
$badr_tile_posts      = get_posts( array(
    'post_type'      => 'emc_badr_tile',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
) );

foreach ( $badr_tile_posts as $badr_tile_post ) {
    $tile_tier = get_post_meta( $badr_tile_post->ID, '_emc_badr_tier', true );

    if ( isset( $badr_tile_previews[ $tile_tier ] ) ) {
        $badr_tile_previews[ $tile_tier ][] = $badr_tile_post;
    }
}
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

                <!-- Badr Wall tile dashboard -->
                <div class="campaign-progress-box badr-dashboard">
                    <div class="badr-dashboard-heading">
                        <span><?php esc_html_e( 'Badr Wall Tile Status', 'emc-theme' ); ?></span>
                        <strong>
                            <?php
                            printf(
                                esc_html__( '%s tiles remaining', 'emc-theme' ),
                                esc_html( number_format_i18n( $badr_total_remaining ) )
                            );
                            ?>
                        </strong>
                    </div>
                    <div class="badr-dashboard-grid">
                        <?php foreach ( $badr_levels as $level ) :
                            $taken_percent = $level['total'] > 0 ? round( ( $level['filled'] / $level['total'] ) * 100 ) : 0;
                        ?>
                        <article class="badr-dashboard-tier <?php echo esc_attr( $level['class'] ); ?>">
                            <header>
                                <i class="<?php echo esc_attr( $level['icon'] ); ?>" aria-hidden="true"></i>
                                <div>
                                    <strong><?php echo esc_html( $level['label'] ); ?></strong>
                                    <span><?php echo esc_html( '£' . number_format( $level['amount'] ) . '+' ); ?></span>
                                </div>
                            </header>
                            <div class="badr-dashboard-stats">
                                <span><strong><?php echo esc_html( $level['total'] ); ?></strong><?php esc_html_e( 'Total', 'emc-theme' ); ?></span>
                                <span><strong><?php echo esc_html( $level['filled'] ); ?></strong><?php esc_html_e( 'Taken', 'emc-theme' ); ?></span>
                                <span class="is-remaining"><strong><?php echo esc_html( $level['remaining'] ); ?></strong><?php esc_html_e( 'Remaining', 'emc-theme' ); ?></span>
                            </div>
                            <div class="badr-dashboard-track" aria-label="<?php echo esc_attr( sprintf( __( '%1$d of %2$d tiles taken', 'emc-theme' ), $level['filled'], $level['total'] ) ); ?>">
                                <span style="width:<?php echo esc_attr( $taken_percent ); ?>%"></span>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="campaign-cta-row" style="display:flex;gap:1rem;flex-wrap:wrap;">
                    <a href="#badr-membership" class="btn btn-primary">
                        <i class="fas fa-heart" aria-hidden="true"></i>
                        <?php esc_html_e( 'Choose Your Badr Wall Level', 'emc-theme' ); ?>
                    </a>
                    <a href="<?php echo esc_url( $badr_tiles_url ); ?>" class="btn btn-outline">
                        <i class="fas fa-th-large" aria-hidden="true"></i>
                        <?php esc_html_e( 'View Named Tiles', 'emc-theme' ); ?>
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
                    <strong><?php echo esc_html( $badr_levels[0]['label'] ); ?></strong>
                    <span><?php echo esc_html( '£' . number_format_i18n( $badr_levels[0]['amount'] ) . '+' ); ?></span>
                </div>
            </div>
            <div class="tier-legend-item tier-cofunder">
                <i class="fas fa-star"></i>
                <div>
                    <strong><?php echo esc_html( $badr_levels[1]['label'] ); ?></strong>
                    <span><?php echo esc_html( '£' . number_format_i18n( $badr_levels[1]['amount'] ) . '+' ); ?></span>
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
                <div class="form-group">
                    <label for="badr-tile-name"><?php esc_html_e( 'Name for the Tile (optional)', 'emc-theme' ); ?></label>
                    <input type="text" id="badr-tile-name" class="form-control" maxlength="80" placeholder="<?php esc_attr_e( 'Defaults to your full name', 'emc-theme' ); ?>">
                </div>
                <div class="form-group">
                    <label for="badr-donor-address"><?php esc_html_e( 'Address Line 1 (optional)', 'emc-theme' ); ?></label>
                    <input type="text" id="badr-donor-address" class="form-control" autocomplete="address-line1">
                </div>
                <div class="form-group">
                    <label for="badr-donor-postcode"><?php esc_html_e( 'Postcode (optional)', 'emc-theme' ); ?></label>
                    <input type="text" id="badr-donor-postcode" class="form-control" autocomplete="postal-code" maxlength="8">
                </div>
                <div class="form-group badr-address-field">
                    <label for="badr-dedication"><?php esc_html_e( 'Short Dedication (optional)', 'emc-theme' ); ?></label>
                    <textarea id="badr-dedication" class="form-control" rows="3" maxlength="180" placeholder="<?php esc_attr_e( 'For example: In loving memory of our parents', 'emc-theme' ); ?>"></textarea>
                </div>
                <div class="form-group badr-address-field">
                    <label><input type="checkbox" id="badr-anonymous"> <?php esc_html_e( 'Display my tile publicly as Anonymous', 'emc-theme' ); ?></label>
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
                <a href="<?php echo esc_url( $whatsapp_url ); ?>" class="btn btn-outline" id="badr-whatsapp-pledge" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-whatsapp" aria-hidden="true"></i>
                    <?php esc_html_e( 'WhatsApp Pledge', 'emc-theme' ); ?>
                </a>
            </div>
            <p class="badr-payment-note">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <?php esc_html_e( 'For instalments, select WhatsApp Pledge. For bank payments, send your tile name and dedication to the team after transferring.', 'emc-theme' ); ?>
            </p>
        </div>

        <?php
        $tiers = $badr_levels;
        ?>

        <?php foreach ( $tiers as $tier ) :
            $tier_tiles       = $badr_tile_previews[ $tier['id'] ] ?? array();
            $visible_tiles    = array_slice( $tier_tiles, 0, 6 );
            $visible_count    = count( $visible_tiles );
            $published_label  = 1 === $visible_count
                ? __( '1 tile shown', 'emc-theme' )
                : sprintf( __( '%s tiles shown', 'emc-theme' ), number_format_i18n( $visible_count ) );
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
                <div class="badr-tier-metrics" aria-label="<?php esc_attr_e( 'Tile status', 'emc-theme' ); ?>">
                    <span><strong><?php echo esc_html( $tier['total'] ); ?></strong><?php esc_html_e( 'Total', 'emc-theme' ); ?></span>
                    <span><strong><?php echo esc_html( $tier['filled'] ); ?></strong><?php esc_html_e( 'Taken', 'emc-theme' ); ?></span>
                    <span class="is-remaining"><strong><?php echo esc_html( $tier['remaining'] ); ?></strong><?php esc_html_e( 'Remaining', 'emc-theme' ); ?></span>
                </div>
            </div>

            <div class="badr-tile-preview-toolbar">
                <span><?php echo esc_html( $published_label ); ?></span>
                <a href="#badr-membership" class="btn btn-outline" data-badr-tier="<?php echo esc_attr( $tier['id'] ); ?>">
                    <i class="fas fa-plus-circle" aria-hidden="true"></i>
                    <?php esc_html_e( 'Claim a Tile', 'emc-theme' ); ?>
                </a>
            </div>

            <?php if ( $visible_tiles ) : ?>
            <div class="badr-campaign-tile-grid">
                <?php foreach ( $visible_tiles as $tile_post ) :
                    $tile_id      = $tile_post->ID;
                    $dedication   = get_post_meta( $tile_id, '_emc_badr_dedication', true );
                    $is_anonymous = '1' === get_post_meta( $tile_id, '_emc_badr_anonymous', true );
                    $tile_number = absint( get_post_meta( $tile_id, '_emc_badr_tile_number', true ) );
                    $tile_name   = $is_anonymous ? __( 'Anonymous', 'emc-theme' ) : get_the_title( $tile_id );
                    $name_parts  = preg_split( '/\s+/', trim( wp_strip_all_tags( $tile_name ) ) );
                    $initials    = $is_anonymous ? 'A' : '';
                    if ( ! $is_anonymous && $name_parts ) {
                        $first_char = static function( $value ) { return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 1 ) : substr( $value, 0, 1 ); };
                        $initials = $first_char( reset( $name_parts ) );
                        if ( count( $name_parts ) > 1 ) $initials .= $first_char( end( $name_parts ) );
                        $initials = strtoupper( $initials );
                    }
                ?>
                <article class="badr-campaign-tile <?php echo esc_attr( $tier['class'] ); ?>">
                    <div class="badr-donor-avatar" aria-hidden="true"><?php echo esc_html( $initials ); ?></div>
                    <div class="badr-donor-profile"><h4><?php echo esc_html( $tile_name ); ?></h4><p><?php echo esc_html( $dedication ?: __( 'For the sake of Allah', 'emc-theme' ) ); ?></p><span><?php echo esc_html( $tier['label'] ); ?><?php if ( $tile_number ) echo ' · ' . esc_html( sprintf( __( 'Tile #%d', 'emc-theme' ), $tile_number ) ); ?></span></div>
                    <i class="fas <?php echo $is_anonymous ? 'fa-user-secret' : 'fa-check-circle'; ?> badr-donor-state" aria-label="<?php echo esc_attr( $is_anonymous ? __( 'Anonymous', 'emc-theme' ) : __( 'Confirmed', 'emc-theme' ) ); ?>"></i>
                </article>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <div class="badr-campaign-tile-empty">
                <i class="fas fa-star-and-crescent" aria-hidden="true"></i>
                <div>
                    <strong><?php esc_html_e( 'Names and dedications coming soon', 'emc-theme' ); ?></strong>
                    <span><?php esc_html_e( 'Approved tiles at this level will be displayed here.', 'emc-theme' ); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( count( $tier_tiles ) > $visible_count ) : ?>
            <a class="badr-view-all-tiles" href="<?php echo esc_url( $badr_tiles_url ); ?>">
                <?php esc_html_e( 'View all names and dedications', 'emc-theme' ); ?>
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="text-center" style="margin-top:3rem;">
            <div class="campaign-cta-row" style="display:flex;justify-content:center;gap:1rem;flex-wrap:wrap;">
                <a href="#badr-membership" class="btn btn-primary" style="font-size:var(--step-0);padding:1rem 2.5rem;">
                    <i class="fas fa-heart" aria-hidden="true"></i>
                    <?php esc_html_e( 'Secure Your Place on the Badr Wall', 'emc-theme' ); ?>
                </a>
                <a href="<?php echo esc_url( $badr_tiles_url ); ?>" class="btn btn-outline" style="font-size:var(--step-0);padding:1rem 2.5rem;">
                    <i class="fas fa-th-large" aria-hidden="true"></i>
                    <?php esc_html_e( 'View Names & Dedications', 'emc-theme' ); ?>
                </a>
            </div>
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
                    <div class="amount-grid" style="grid-template-columns: repeat(2, 1fr);">
                        <button class="amount-btn active">£5,000+</button>
                        <button class="amount-btn">£10,000+</button>
                    </div>

                    <div class="monthly-313-badge">
                        <i class="fas fa-award" aria-hidden="true"></i>
                        <div>
                            <strong><?php esc_html_e( 'Badr Wall recognition starts from £5,000', 'emc-theme' ); ?></strong>
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
