<?php
/**
 * Template Part: Campaign Progress — Phase 4 fully customizer-driven.
 * @package emc-theme
 */

$badge       = emc_option( 'emc_campaign_badge',   __( 'Building Fund', 'emc-theme' ) );
$heading     = emc_option( 'emc_campaign_heading',  __( 'Be One of the 313', 'emc-theme' ) );
$desc        = emc_option( 'emc_campaign_desc',     __( 'Help us build a lasting place of worship for future generations. Our building campaign needs your generous support. Every pound brings us closer to our goal.', 'emc-theme' ) );
$raised      = (int) emc_option( 'emc_campaign_raised', 68400 );
$target      = (int) emc_option( 'emc_campaign_target', 100000 );
$donors      = (int) emc_option( 'emc_campaign_donors', 247 );
$cta_label   = emc_option( 'emc_campaign_cta_label', __( 'Choose Badr Wall Level', 'emc-theme' ) );
$learn_url   = get_permalink( get_page_by_path( 'campaign' ) ) ?: home_url( '/campaign/' );
$cta_url     = emc_option( 'emc_campaign_cta_url', '' ) ?: $learn_url . '#badr-membership';

$percent = $target > 0 ? min( 100, round( ( $raised / $target ) * 100 ) ) : 0;
?>
<section class="homepage-campaign section-padding" id="campaign" aria-labelledby="campaign-heading">
    <div class="container">
        <div class="campaign-inner">

            <!-- Text Column -->
            <div class="campaign-text-col scroll-reveal">
                <span class="badge badge-gold">
                    <i class="fas fa-star-and-crescent" aria-hidden="true"></i>
                    <?php echo esc_html( $badge ); ?>
                </span>
                <h2 id="campaign-heading"><?php echo esc_html( $heading ); ?></h2>
                <p><?php echo esc_html( $desc ); ?></p>

                <div class="campaign-stats-row" aria-label="<?php esc_attr_e( 'Campaign progress', 'emc-theme' ); ?>">
                    <div class="campaign-stat">
                        <span class="campaign-stat-num" data-target="<?php echo esc_attr( $raised ); ?>" data-prefix="£">£0</span>
                        <span class="campaign-stat-label"><?php esc_html_e( 'Raised', 'emc-theme' ); ?></span>
                    </div>
                    <div class="campaign-stat">
                        <span class="campaign-stat-num" data-target="<?php echo esc_attr( $target ); ?>" data-prefix="£">£0</span>
                        <span class="campaign-stat-label"><?php esc_html_e( 'Target', 'emc-theme' ); ?></span>
                    </div>
                    <div class="campaign-stat">
                        <span class="campaign-stat-num" data-target="<?php echo esc_attr( $donors ); ?>" data-suffix=" donors">0</span>
                        <span class="campaign-stat-label"><?php esc_html_e( 'Donors', 'emc-theme' ); ?></span>
                    </div>
                </div>

                <div
                    class="campaign-progress-wrap"
                    role="progressbar"
                    aria-valuenow="<?php echo esc_attr( $percent ); ?>"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-label="<?php echo esc_attr( sprintf( __( 'Campaign progress: %d%% of target reached', 'emc-theme' ), $percent ) ); ?>"
                >
                    <div class="campaign-progress-bar-track">
                        <div class="campaign-progress-bar-fill" data-percent="<?php echo esc_attr( $percent ); ?>"></div>
                    </div>
                    <div class="campaign-progress-labels">
                        <span class="campaign-pct" id="campaign-pct">0%</span>
                        <span style="color:var(--text-muted);font-size:var(--step--2);">
                            <?php
                            printf(
                                /* translators: %s: formatted target amount */
                                esc_html__( 'of £%s target', 'emc-theme' ),
                                esc_html( number_format( $target ) )
                            );
                            ?>
                        </span>
                    </div>
                </div>

                <div class="campaign-cta-row">
                    <a href="<?php echo esc_url( $cta_url ); ?>" class="btn btn-primary">
                        <i class="fas fa-heart" aria-hidden="true"></i>
                        <?php echo esc_html( $cta_label ); ?>
                    </a>
                    <a href="<?php echo esc_url( $learn_url ); ?>" class="btn btn-outline">
                        <?php esc_html_e( 'Learn More', 'emc-theme' ); ?>
                    </a>
                </div>
            </div>

            <!-- Visual Column -->
            <div class="campaign-visual-col scroll-reveal" style="transition-delay:.15s;" aria-hidden="true">
                <div class="campaign-card glass-card">
                    <div class="campaign-card-icon">
                        <i class="fas fa-mosque"></i>
                    </div>
                    <h3><?php esc_html_e( 'Why This Matters', 'emc-theme' ); ?></h3>
                    <ul class="campaign-why-list">
                        <li><i class="fas fa-check-circle"></i> <?php esc_html_e( 'A permanent, purpose-built centre for Chelmsford Muslims', 'emc-theme' ); ?></li>
                        <li><i class="fas fa-check-circle"></i> <?php esc_html_e( 'Space for 500+ worshippers during Jumu\'ah', 'emc-theme' ); ?></li>
                        <li><i class="fas fa-check-circle"></i> <?php esc_html_e( 'Dedicated youth, women\'s, and education wings', 'emc-theme' ); ?></li>
                        <li><i class="fas fa-check-circle"></i> <?php esc_html_e( 'All donations qualify for Gift Aid (+25%)', 'emc-theme' ); ?></li>
                    </ul>
                    <div class="campaign-trust-row">
                        <span><i class="fas fa-shield-alt"></i> <?php echo esc_html( emc_option( 'emc_charity_number', '1209815' ) ); ?></span>
                        <span><i class="fas fa-lock"></i> <?php esc_html_e( 'Stripe Secured', 'emc-theme' ); ?></span>
                        <span><i class="fas fa-check"></i> <?php esc_html_e( 'Gift Aid Ready', 'emc-theme' ); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
