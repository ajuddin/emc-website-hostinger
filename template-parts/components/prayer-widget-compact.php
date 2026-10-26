<?php
/**
 * Template Part: Prayer Widget — Compact (Shortcode: [emc_prayer_widget type="compact"])
 * Shows today's 5 Jama'at (iqamah) times, with the begin (adhan) time underneath, in a slim horizontal strip.
 * Times are updated by script.js using prayer-data.json.
 *
 * @package emc-theme
 */
?>
<div class="prayer-widget-compact" aria-label="<?php esc_attr_e( "Today's Prayer Times", 'emc-theme' ); ?>">
    <div class="pwc-inner">
        <span class="pwc-label">
            <i class="fas fa-mosque" aria-hidden="true"></i>
            <?php esc_html_e( "Today's Jama'at", 'emc-theme' ); ?>
        </span>
        <?php
        $prayers = array( 'fajr' => 'Fajr', 'dhuhr' => 'Dhuhr', 'asr' => 'Asr', 'maghrib' => 'Maghrib', 'isha' => 'Isha' );
        foreach ( $prayers as $key => $label ) :
        ?>
        <div class="pwc-item" data-prayer="<?php echo esc_attr( $key ); ?>">
            <span class="pwc-name"><?php echo esc_html( $label ); ?></span>
            <span class="pwc-time" title="<?php esc_attr_e( "Jama'at (congregation) time", 'emc-theme' ); ?>">--:--</span>
            <span class="pwc-begins"><?php esc_html_e( 'Begins', 'emc-theme' ); ?> <span class="pwc-adhan">--:--</span></span>
        </div>
        <?php endforeach; ?>
        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'prayer-times' ) ) ?: home_url( '/prayer-times/' ) ); ?>" class="pwc-link">
            <?php esc_html_e( 'Full Timetable', 'emc-theme' ); ?> <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
</div>
