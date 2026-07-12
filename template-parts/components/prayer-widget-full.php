<?php
/**
 * Template Part: Prayer Widget — Full (Shortcode: [emc_prayer_widget type="full"])
 * Shows today's full prayer schedule (adhan + iqamah) with a live countdown.
 * Times are updated by script.js using prayer-data.json.
 *
 * @package emc-theme
 */
?>
<div class="prayer-widget-full glass-card" aria-label="<?php esc_attr_e( "Today's Prayer Times", 'emc-theme' ); ?>">
    <div class="pwf-header">
        <div>
            <p class="pwf-title"><i class="fas fa-mosque"></i> <?php esc_html_e( "Today's Prayer Times", 'emc-theme' ); ?></p>
            <p class="pwf-location"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html( emc_option( 'emc_location', 'Essex Muslim Centre, Cuton Hall Lane, CM2 6PB' ) ); ?></p>
        </div>
        <div class="pwf-countdown">
            <p class="pwf-next-label"><?php esc_html_e( 'Next:', 'emc-theme' ); ?> <strong id="pwf-next-name">—</strong></p>
            <div class="pwf-timer" id="pwf-timer">--:--:--</div>
        </div>
    </div>

    <div class="pwf-rows">
        <?php
        $prayers = array(
            'fajr'    => array( 'label' => 'Fajr',    'icon' => 'fas fa-sun',       'has_iqamah' => true  ),
            'sunrise' => array( 'label' => 'Sunrise',  'icon' => 'fas fa-cloud-sun', 'has_iqamah' => false ),
            'dhuhr'   => array( 'label' => 'Dhuhr',   'icon' => 'fas fa-sun',       'has_iqamah' => true  ),
            'asr'     => array( 'label' => 'Asr',     'icon' => 'fas fa-sun',       'has_iqamah' => true  ),
            'maghrib' => array( 'label' => 'Maghrib',  'icon' => 'fas fa-moon',      'has_iqamah' => true  ),
            'isha'    => array( 'label' => 'Isha',    'icon' => 'fas fa-moon',      'has_iqamah' => true  ),
        );
        foreach ( $prayers as $key => $p ) :
        ?>
        <div class="pwf-row" data-prayer="<?php echo esc_attr( $key ); ?>">
            <div class="pwf-name"><i class="<?php echo esc_attr( $p['icon'] ); ?>"></i> <?php echo esc_html( $p['label'] ); ?></div>
            <div class="pwf-adhan" data-type="adhan">--:--</div>
            <div class="pwf-iqamah" data-type="iqamah"><?php echo $p['has_iqamah'] ? '--:--' : '—'; ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="pwf-footer">
        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'prayer-times' ) ) ?: home_url( '/prayer-times/' ) ); ?>" class="btn btn-primary btn-sm">
            <?php esc_html_e( 'Full Monthly Timetable', 'emc-theme' ); ?> <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>
