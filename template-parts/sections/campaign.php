<?php
/**
 * Template Part: Campaign Progress — Phase 4 fully customizer-driven.
 * @package emc-theme
 */

$badge       = emc_option( 'emc_campaign_badge',   __( 'Building Fund', 'emc-theme' ) );
$heading     = emc_option( 'emc_campaign_heading',  __( 'Be One of the 313', 'emc-theme' ) );
$desc        = emc_option( 'emc_campaign_desc',     __( 'Help us build a lasting place of worship for future generations. Our building campaign needs your generous support. Every pound brings us closer to our goal.', 'emc-theme' ) );
$cta_label   = emc_option( 'emc_campaign_cta_label', __( 'Choose Badr Wall Level', 'emc-theme' ) );
$learn_url   = emc_get_campaign_url();
$custom_cta  = trim( (string) emc_option( 'emc_campaign_cta_url', '' ) );
$cta_url     = $custom_cta
    ? ( 0 === strpos( $custom_cta, '#' ) ? $learn_url . $custom_cta : $custom_cta )
    : $learn_url . '#badr-membership';
$badr_levels = emc_get_badr_levels();
$badr_total_remaining = array_sum( array_column( $badr_levels, 'remaining' ) );
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

                <div class="home-badr-dashboard" aria-label="<?php esc_attr_e( 'Badr Wall tile availability', 'emc-theme' ); ?>">
                    <div class="home-badr-dashboard-head">
                        <span><?php esc_html_e( 'Badr Wall Tile Status', 'emc-theme' ); ?></span>
                        <strong>
                            <?php
                            printf(
                                esc_html__( '%s remaining', 'emc-theme' ),
                                esc_html( number_format_i18n( $badr_total_remaining ) )
                            );
                            ?>
                        </strong>
                    </div>
                    <div class="home-badr-grid">
                        <?php foreach ( $badr_levels as $level ) :
                            $taken_percent = $level['total'] > 0 ? round( ( $level['filled'] / $level['total'] ) * 100 ) : 0;
                            ?>
                            <article class="home-badr-tier <?php echo esc_attr( $level['class'] ); ?>">
                                <div class="home-badr-tier-head">
                                    <span class="home-badr-icon" aria-hidden="true">
                                        <i class="<?php echo esc_attr( $level['icon'] ); ?>"></i>
                                    </span>
                                    <div>
                                        <h3><?php echo esc_html( $level['label'] ); ?></h3>
                                        <span><?php echo esc_html( '£' . number_format_i18n( $level['amount'] ) . '+' ); ?></span>
                                    </div>
                                </div>
                                <dl class="home-badr-stats">
                                    <div>
                                        <dt><?php esc_html_e( 'Total', 'emc-theme' ); ?></dt>
                                        <dd><?php echo esc_html( number_format_i18n( $level['total'] ) ); ?></dd>
                                    </div>
                                    <div>
                                        <dt><?php esc_html_e( 'Taken', 'emc-theme' ); ?></dt>
                                        <dd><?php echo esc_html( number_format_i18n( $level['filled'] ) ); ?></dd>
                                    </div>
                                    <div class="is-remaining">
                                        <dt><?php esc_html_e( 'Remaining', 'emc-theme' ); ?></dt>
                                        <dd><?php echo esc_html( number_format_i18n( $level['remaining'] ) ); ?></dd>
                                    </div>
                                </dl>
                                <div
                                    class="home-badr-track"
                                    role="progressbar"
                                    aria-label="<?php echo esc_attr( sprintf( __( '%s tiles taken', 'emc-theme' ), $level['label'] ) ); ?>"
                                    aria-valuenow="<?php echo esc_attr( $level['filled'] ); ?>"
                                    aria-valuemin="0"
                                    aria-valuemax="<?php echo esc_attr( $level['total'] ); ?>"
                                >
                                    <span style="width: <?php echo esc_attr( $taken_percent ); ?>%"></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
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
