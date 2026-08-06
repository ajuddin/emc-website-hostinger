<?php
/**
 * EMC Theme — inc/shortcodes.php
 * Useful shortcodes for use inside Gutenberg blocks or classic editor.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * [emc_prayer_widget] — outputs the MasjidBox prayer times widget embed.
 * In production, replace the placeholder div with the real MasjidBox embed code.
 */
function emc_shortcode_prayer_widget( $atts ) {
    $atts = shortcode_atts( array(
        'type' => 'full', // 'full' | 'compact'
    ), $atts, 'emc_prayer_widget' );

    ob_start();
    if ( $atts['type'] === 'compact' ) {
        get_template_part( 'template-parts/components/prayer-widget-compact' );
    } else {
        get_template_part( 'template-parts/components/prayer-widget-full' );
    }
    return ob_get_clean();
}
add_shortcode( 'emc_prayer_widget', 'emc_shortcode_prayer_widget' );


/**
 * [emc_donate_button label="Give Now" class=""]
 */
function emc_shortcode_donate_button( $atts ) {
    $atts = shortcode_atts( array(
        'label' => __( 'Donate Now', 'emc-theme' ),
        'class' => '',
    ), $atts, 'emc_donate_button' );

    return emc_donate_button( $atts['label'], $atts['class'] );
}
add_shortcode( 'emc_donate_button', 'emc_shortcode_donate_button' );


/**
 * [emc_charity_number] — outputs Charity No. 1209815 badge.
 */
function emc_shortcode_charity_number() {
    return emc_charity_badge();
}
add_shortcode( 'emc_charity_number', 'emc_shortcode_charity_number' );


/**
 * [emc_campaign_bar id="123"] — outputs a campaign progress bar.
 */
function emc_shortcode_campaign_bar( $atts ) {
    $atts = shortcode_atts( array(
        'id'     => 0,
        'target' => 100000,
    ), $atts, 'emc_campaign_bar' );

    $post = get_post( (int) $atts['id'] );
    if ( ! $post ) return '';

    $raised  = function_exists( 'emc_campaign_amount_raised' ) ? emc_campaign_amount_raised( $post->ID ) : (float) get_post_meta( $post->ID, '_emc_raised', true );
    $stored_target = function_exists( 'emc_campaign_goal' ) ? emc_campaign_goal( $post->ID ) : (float) get_post_meta( $post->ID, '_emc_target', true );
    $target  = $stored_target > 0 ? $stored_target : (float) $atts['target'];
    $percent = $target > 0 ? min( 100, round( ( $raised / $target ) * 100 ) ) : 0;

    ob_start();
    ?>
    <div class="emc-campaign-bar">
        <div class="progress-bar-track">
            <div class="progress-bar-fill" style="width:<?php echo esc_attr( $percent ); ?>%"></div>
        </div>
        <div class="progress-labels">
            <span><?php echo esc_html( '£' . number_format( $raised ) . ' raised' ); ?></span>
            <span><?php echo esc_html( 'Goal: £' . number_format( $target ) ); ?></span>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'emc_campaign_bar', 'emc_shortcode_campaign_bar' );
