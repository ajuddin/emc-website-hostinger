<?php
/**
 * Template Part: CTA Strip
 * Full-width call-to-action banner driven by Customizer (emc_hp_cta section).
 * @package emc-theme
 */

$heading      = emc_option( 'emc_cta_heading',   __( 'Ready to Make a Difference?', 'emc-theme' ) );
$subtitle     = emc_option( 'emc_cta_subtitle',  __( 'Every donation, every volunteer hour, and every shared message helps us serve the community better.', 'emc-theme' ) );
$btn1_label   = emc_option( 'emc_cta_btn1_label', __( 'Donate Now', 'emc-theme' ) );
$btn1_url     = emc_option( 'emc_cta_btn1_url', '' ) ?: emc_get_donate_url();
$btn2_label   = emc_option( 'emc_cta_btn2_label', __( 'Get in Touch', 'emc-theme' ) );
$btn2_url     = emc_option( 'emc_cta_btn2_url', '' ) ?: ( get_permalink( get_page_by_path( 'contact' ) ) ?: home_url( '/contact/' ) );
?>
<section class="cta-strip section-padding" aria-labelledby="cta-strip-heading">
    <div class="container">
        <div class="cta-strip-inner scroll-reveal">

            <div class="cta-strip-icon" aria-hidden="true">
                <i class="fas fa-mosque"></i>
            </div>

            <div class="cta-strip-text">
                <h2 id="cta-strip-heading"><?php echo esc_html( $heading ); ?></h2>
                <p><?php echo esc_html( $subtitle ); ?></p>
            </div>

            <div class="cta-strip-actions">
                <a href="<?php echo esc_url( $btn1_url ); ?>" class="btn btn-primary">
                    <i class="fas fa-heart" aria-hidden="true"></i>
                    <?php echo esc_html( $btn1_label ); ?>
                </a>
                <?php if ( $btn2_label ) : ?>
                <a href="<?php echo esc_url( $btn2_url ); ?>" class="btn btn-outline">
                    <?php echo esc_html( $btn2_label ); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
