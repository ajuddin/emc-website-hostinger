<?php
/**
 * Template Part: Prayer Times Strip
 * Compact prayer times bar below the hero.
 * @package emc-theme
 */
?>
<section class="prayer-times-strip section-padding-sm" aria-label="<?php esc_attr_e( 'Today\'s Prayer Times', 'emc-theme' ); ?>">
    <div class="container">
        <div class="prayer-strip-inner">
            <div class="prayer-strip-label">
                <i class="fas fa-mosque" aria-hidden="true"></i>
                <strong><?php esc_html_e( 'Today\'s Prayer Times', 'emc-theme' ); ?></strong>
                <span class="prayer-strip-location">— <?php echo esc_html( emc_option( 'emc_location', 'Essex Muslim Centre, Cuton Hall Lane, CM2 6PB' ) ); ?></span>
            </div>

            <div class="prayer-strip-times" id="prayer-strip-times">
                <?php
                $prayers = array( 'Fajr', 'Dhuhr', 'Asr', 'Maghrib', 'Isha' );
                foreach ( $prayers as $prayer ) :
                ?>
                <div class="prayer-strip-item" data-prayer="<?php echo esc_attr( strtolower( $prayer ) ); ?>">
                    <span class="prayer-strip-name"><?php echo esc_html( $prayer ); ?></span>
                    <span class="prayer-strip-time">--:--</span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="prayer-strip-actions">
                <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'prayer-times' ) ) ?: home_url( '/prayer-times/' ) ); ?>" class="prayer-strip-link btn btn-outline btn-sm">
                    <?php esc_html_e( 'Full Timetable', 'emc-theme' ); ?>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
                <a href="<?php echo esc_url( emc_option( 'emc_prayer_pdf_url', home_url( '/wp-content/uploads/2026-prayer-timetable.pdf' ) ) ); ?>" class="prayer-strip-link btn btn-primary btn-sm" download>
                    <i class="fas fa-file-pdf" aria-hidden="true"></i>
                    <?php esc_html_e( 'Download 2026 PDF', 'emc-theme' ); ?>
                </a>
            </div>
        </div>
    </div>
</section>
