<?php
/**
 * Template Name: Prayer Times
 * Template Post Type: page
 *
 * EMC Theme — Prayer Times page template.
 * Prayer times, Jumu'ah schedule, and timetable editable via ACF.
 *
 * @package emc-theme
 */

get_header();

wp_enqueue_style( 'emc-page-prayer', EMC_ASSETS . '/css/prayer-times.css', array( 'emc-style' ), EMC_VERSION );

$prayer_js_path     = EMC_DIR . '/assets/js/prayer-times.js';
$prayer_pdf_js_path = EMC_DIR . '/assets/js/prayer-pdf.js';
$prayer_data_url    = emc_prayer_data_url();
$prayer_js_deps     = array( 'emc-script' );
if ( file_exists( $prayer_pdf_js_path ) ) {
    wp_enqueue_script( 'emc-prayer-pdf', EMC_ASSETS . '/js/prayer-pdf.js', array(), filemtime( $prayer_pdf_js_path ), true );
    $prayer_js_deps[] = 'emc-prayer-pdf';
}
if ( file_exists( $prayer_js_path ) ) {
    wp_enqueue_script( 'emc-page-prayer', EMC_ASSETS . '/js/prayer-times.js', $prayer_js_deps, filemtime( $prayer_js_path ), true );
    wp_localize_script( 'emc-page-prayer', 'emcPrayer', array(
        'dataUrl'  => $prayer_data_url,
        'siteName' => get_bloginfo( 'name' ),
        'location' => emc_acf( 'prayer_location_city', 'Essex Muslim Centre, Cuton Hall Lane, CM2 6PB' ),
        'timezone' => wp_timezone_string() ?: 'UTC',
    ) );
}
?>

<!-- Prayer Hero -->
<section class="prayer-hero">
    <div class="prayer-hero-bg"></div>
    <div class="container prayer-hero-content">
        <div class="prayer-hero-text">
            <span class="badge"><i class="fas fa-mosque"></i> <?php echo esc_html( emc_acf( 'prayer_hero_badge', __( 'Daily Salah', 'emc-theme' ) ) ); ?></span>
            <h1><?php echo esc_html( emc_acf( 'prayer_hero_title', __( 'Prayer Times', 'emc-theme' ) ) ); ?></h1>
            <p><?php echo esc_html( emc_acf( 'prayer_hero_desc', __( 'Essex Muslim Centre, Chelmsford. All times are calculated for our location. Please check regularly as times change throughout the year.', 'emc-theme' ) ) ); ?></p>
            <div class="hijri-display">
                <i class="fas fa-star-and-crescent"></i>
                <span id="gregorian-today"><?php echo esc_html( wp_date( 'jS F Y' ) ); ?></span>
                <span aria-hidden="true">&middot;</span>
                <span id="hijri-today"><?php esc_html_e( 'Hijri date loading', 'emc-theme' ); ?></span>
            </div>
        </div>

        <!-- MAIN PRAYER WIDGET -->
        <div class="today-widget glass-card">
            <div class="today-widget-header">
                <div>
                    <p class="today-label"><?php echo esc_html( emc_acf( 'prayer_widget_label', "Today's Prayer Times" ) ); ?></p>
                    <p class="today-location"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html( emc_acf( 'prayer_location_label', 'Essex Muslim Centre, Cuton Hall Lane, CM2 6PB' ) ); ?></p>
                </div>
                <div class="countdown-badge">
                    <p><?php echo esc_html( emc_acf( 'prayer_next_label', 'Next:' ) ); ?> <strong id="next-prayer-name"><?php esc_html_e( 'Asr', 'emc-theme' ); ?></strong></p>
                    <div class="live-countdown" id="live-countdown">01:12:45</div>
                </div>
            </div>

            <?php
            // Check if MasjidBox embed code is provided
            $masjidbox = emc_acf( 'prayer_masjidbox_embed', '' );
            if ( $masjidbox ) :
                // Render the live widget embed
                echo wp_kses( $masjidbox, array(
                    'iframe' => array( 'src' => true, 'width' => true, 'height' => true, 'style' => true, 'frameborder' => true, 'allowfullscreen' => true, 'loading' => true ),
                    'div'    => array( 'class' => true, 'id' => true, 'style' => true ),
                    'script' => array( 'src' => true, 'type' => true ),
                ) );
            else :
            ?>
            <div class="prayer-times-table">
                <div class="pt-header-row">
                    <span><?php esc_html_e( 'Prayer', 'emc-theme' ); ?></span>
                    <span><?php esc_html_e( 'Adhan', 'emc-theme' ); ?></span>
                    <span><?php esc_html_e( 'Iqama', 'emc-theme' ); ?></span>
                </div>
                <?php
                $prayer_defaults = array(
                    array( 'name' => 'Fajr',    'icon' => 'fas fa-sun',       'adhan' => '04:15',    'iqama' => '04:30' ),
                    array( 'name' => 'Sunrise', 'icon' => 'fas fa-cloud-sun', 'adhan' => '05:40',    'iqama' => '—' ),
                    array( 'name' => 'Dhuhr',   'icon' => 'fas fa-sun',       'adhan' => '01:05 PM', 'iqama' => '01:15 PM' ),
                    array( 'name' => 'Asr',     'icon' => 'fas fa-sun',       'adhan' => '05:20 PM', 'iqama' => '05:30 PM' ),
                    array( 'name' => 'Maghrib', 'icon' => 'fas fa-moon',      'adhan' => '08:35 PM', 'iqama' => '08:40 PM' ),
                    array( 'name' => 'Isha',    'icon' => 'fas fa-moon',      'adhan' => '10:00 PM', 'iqama' => '10:15 PM' ),
                );
                for ( $i = 1; $i <= 6; $i++ ) :
                    $d     = $prayer_defaults[ $i - 1 ];
                    $name  = emc_acf( 'prayer_time_' . $i . '_name',  $d['name'] );
                    $icon  = emc_acf( 'prayer_time_' . $i . '_icon',  $d['icon'] );
                    $adhan = emc_acf( 'prayer_time_' . $i . '_adhan', $d['adhan'] );
                    $iqama = emc_acf( 'prayer_time_' . $i . '_iqama', $d['iqama'] );
                    $is_active = ( $i === 4 ); // Asr default active
                ?>
                <div class="pt-row<?php echo $is_active ? ' active' : ''; ?>" data-prayer="<?php echo esc_attr( $name ); ?>">
                    <div class="pt-name"><i class="<?php echo esc_attr( $icon ); ?>"></i> <?php echo esc_html( $name ); ?><?php if ( $is_active ) : ?> <span class="next-badge"><?php esc_html_e( 'Next', 'emc-theme' ); ?></span><?php endif; ?></div>
                    <div class="pt-adhan"><?php echo esc_html( $adhan ); ?></div>
                    <div class="pt-iqama"><?php echo esc_html( $iqama ); ?></div>
                </div>
                <?php endfor; ?>
            </div>

            <div class="masjidbox-placeholder">
                <i class="fas fa-info-circle"></i>
                <?php echo esc_html( emc_acf( 'prayer_placeholder_embed_note', 'In production, the live MasjidBox widget embed code will be placed here, replacing this mock-up.' ) ); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Quick Info Cards -->
<section class="prayer-info-section">
    <div class="container">
        <div class="prayer-info-grid">
            <div class="prayer-info-card scroll-reveal">
                <div class="info-icon"><i class="fas fa-clock"></i></div>
                <h4><?php echo esc_html( emc_acf( 'prayer_jumuah_card_heading', "Jumu'ah (Friday Prayer)" ) ); ?></h4>
                <p><?php echo esc_html( emc_acf( 'prayer_jumuah_1_label', 'First Khutbah:' ) ); ?> <strong><?php echo esc_html( emc_acf( 'prayer_jumuah_1', '12:30 PM' ) ); ?></strong></p>
                <p><?php echo esc_html( emc_acf( 'prayer_jumuah_2_label', 'Second Khutbah:' ) ); ?> <strong><?php echo esc_html( emc_acf( 'prayer_jumuah_2', '1:30 PM' ) ); ?></strong></p>
            </div>
            <div class="prayer-info-card scroll-reveal" style="transition-delay:0.1s">
                <div class="info-icon"><i class="fas fa-moon"></i></div>
                <h4><?php echo esc_html( emc_acf( 'prayer_taraweeh_card_heading', 'Taraweeh (Ramadan)' ) ); ?></h4>
                <p><?php echo esc_html( emc_acf( 'prayer_taraweeh_note', 'After Isha prayer' ) ); ?></p>
                <p><?php esc_html_e( 'Approx.', 'emc-theme' ); ?> <strong><?php echo esc_html( emc_acf( 'prayer_taraweeh_time', '10:30 PM' ) ); ?></strong> <?php echo esc_html( emc_acf( 'prayer_taraweeh_suffix', 'in Ramadan' ) ); ?></p>
            </div>
            <div class="prayer-info-card scroll-reveal" style="transition-delay:0.2s">
                <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                <h4><?php echo esc_html( emc_acf( 'prayer_location_card_heading', 'Our Location' ) ); ?></h4>
                <p><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
                <p><?php echo esc_html( emc_acf( 'prayer_location_city', 'Essex Muslim Centre, Cuton Hall Lane, CM2 6PB' ) ); ?></p>
            </div>
            <div class="prayer-info-card scroll-reveal" style="transition-delay:0.3s">
                <div class="info-icon"><i class="fas fa-mobile-alt"></i></div>
                <h4><?php echo esc_html( emc_acf( 'prayer_alerts_card_heading', 'Prayer Alerts' ) ); ?></h4>
                <p><?php echo esc_html( emc_acf( 'prayer_alerts_card_desc', 'Download a prayer app and set our Chelmsford location for automatic alerts.' ) ); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Monthly Timetable -->
<section class="monthly-timetable section-padding">
    <div class="container">
        <div class="section-header">
            <span class="subtitle"><?php echo esc_html( emc_acf( 'prayer_timetable_subtitle', 'Monthly View' ) ); ?></span>
            <h2 id="timetable-heading"><?php echo esc_html( wp_date( 'F Y' ) ); ?> <?php esc_html_e( 'Timetable', 'emc-theme' ); ?></h2>
        </div>

        <div class="timetable-toolbar">
            <div class="month-nav">
                <button class="month-nav-btn" id="prev-month" type="button"><i class="fas fa-chevron-left"></i> <?php echo esc_html( wp_date( 'F', strtotime( '-1 month' ) ) ); ?></button>
                <span class="month-current"><?php echo esc_html( wp_date( 'F Y' ) ); ?></span>
                <button class="month-nav-btn" id="next-month" type="button"><?php echo esc_html( wp_date( 'F', strtotime( '+1 month' ) ) ); ?> <i class="fas fa-chevron-right"></i></button>
            </div>
            <button class="timetable-download-btn" id="download-timetable-pdf" type="button" disabled>
                <i class="fas fa-file-pdf" aria-hidden="true"></i>
                <span><?php esc_html_e( 'Download PDF', 'emc-theme' ); ?></span>
            </button>
        </div>

        <div class="timetable-scroll-wrapper">
            <table class="timetable-table" id="timetable">
                <thead>
                    <tr>
                        <!-- Headers rebuilt by prayer-times.js using real data -->
                        <th><?php esc_html_e( 'Date', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Day', 'emc-theme' ); ?></th>
                        <th><i class="fas fa-sun"></i> <?php esc_html_e( 'Fajr', 'emc-theme' ); ?></th>
                        <th class="iqamah-col"><?php esc_html_e( 'Iqamah', 'emc-theme' ); ?></th>
                        <th><i class="fas fa-cloud-sun"></i> <?php esc_html_e( 'Sunrise', 'emc-theme' ); ?></th>
                        <th><i class="fas fa-sun"></i> <?php esc_html_e( 'Dhuhr', 'emc-theme' ); ?></th>
                        <th class="iqamah-col"><?php esc_html_e( 'Iqamah', 'emc-theme' ); ?></th>
                        <th><i class="fas fa-sun"></i> <?php esc_html_e( 'Asr', 'emc-theme' ); ?></th>
                        <th class="iqamah-col"><?php esc_html_e( 'Iqamah', 'emc-theme' ); ?></th>
                        <th><i class="fas fa-moon"></i> <?php esc_html_e( 'Maghrib', 'emc-theme' ); ?></th>
                        <th><i class="fas fa-moon"></i> <?php esc_html_e( 'Isha', 'emc-theme' ); ?></th>
                        <th class="iqamah-col"><?php esc_html_e( 'Iqamah', 'emc-theme' ); ?></th>
                        <th><i class="fas fa-star-and-crescent"></i> <?php esc_html_e( "Jumu'ah", 'emc-theme' ); ?></th>
                    </tr>
                </thead>
                <tbody id="timetable-body">
                    <tr><td colspan="13" style="text-align:center;padding:2rem;color:#aaa;"><i class="fas fa-spinner fa-spin"></i> <?php esc_html_e( 'Loading timetable…', 'emc-theme' ); ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="timetable-note">
            <i class="fas fa-info-circle"></i>
            <p><?php echo esc_html( emc_acf( 'prayer_timetable_note', __( 'Times shown are Adhan times. Iqama is typically 10–15 minutes after Adhan. All times are in local time (GMT+1 during BST). Please contact us if you have any questions.', 'emc-theme' ) ) ); ?></p>
        </div>
    </div>
</section>

<?php get_footer(); ?>
