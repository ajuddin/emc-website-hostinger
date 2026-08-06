<?php
/**
 * Template Name: Ramadan Givings
 * Template Post Type: page
 *
 * EMC Theme — Dedicated Ramadan Giving page.
 * Countdown, daily giving scheduler, Fitrana/Fidya calculators,
 * Sadaqah Jariyah dedication.
 *
 * @package emc-theme
 */

$emc_payments_available = function_exists( 'emc_payments_is_available' ) && emc_payments_is_available();
if ( $emc_payments_available ) {
    emc_payments_enqueue_assets( 'ramadan' );
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

$donate_url = get_permalink( get_page_by_path( 'donate' ) ) ?: home_url( '/donate/' );
$ramadan_start_date = emc_site_setting( 'emc_ramadan_start_date', emc_acf( 'ramadan_start_date', '2027-02-08' ) );
if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ramadan_start_date ) ) {
    $ramadan_start_date = '2027-02-08';
}
$ramadan_amounts        = array_values( array_filter( array_map( 'floatval', explode( ',', emc_site_setting( 'emc_ramadan_amounts', '1,2,3,5,10' ) ) ) ) );
$ramadan_default_amount = (float) emc_site_setting( 'emc_ramadan_default_amount', 3 );
$fitrana_rate           = (float) emc_site_setting( 'emc_fitrana_rate', 7 );
$fidya_rate             = (float) emc_site_setting( 'emc_fidya_rate', 5 );
$fidya_max_days         = max( 1, absint( emc_site_setting( 'emc_fidya_max_days', 30 ) ) );
if ( ! $ramadan_amounts ) {
    $ramadan_amounts = array( 1, 2, 3, 5, 10 );
}
if ( ! in_array( $ramadan_default_amount, $ramadan_amounts, true ) ) {
    $ramadan_default_amount = (float) $ramadan_amounts[0];
}

wp_localize_script( 'emc-page-ramadan', 'emcRamadanConfig', array(
    'startDate' => $ramadan_start_date,
) );
?>

<!-- ════════════════════════════════════════
     HERO
     ════════════════════════════════════════ -->
<section class="ramadan-hero" aria-labelledby="ramadan-hero-heading">
    <div class="ramadan-stars" aria-hidden="true">
        <?php for ( $s = 0; $s < 30; $s++ ) : ?>
        <span class="rstar" style="
            top:<?php echo rand(5,90); ?>%;
            left:<?php echo rand(2,98); ?>%;
            width:<?php echo rand(2,5); ?>px;
            height:<?php echo rand(2,5); ?>px;
            animation-delay:<?php echo rand(0,30)/10; ?>s;
            animation-duration:<?php echo rand(20,40)/10; ?>s;
        "></span>
        <?php endfor; ?>
    </div>
    <div class="ramadan-crescent" aria-hidden="true"></div>

    <div class="container ramadan-hero-inner">
        <span class="ramadan-badge-pill">
            <i class="fas fa-moon"></i>
            <?php echo esc_html( emc_acf( 'ramadan_hero_badge', 'Ramadan 1447 AH' ) ); ?>
        </span>
        <h1 id="ramadan-hero-heading">
            <?php echo esc_html( emc_acf( 'ramadan_hero_title', 'Ramadan Daily Giving' ) ); ?>
        </h1>
        <p><?php echo esc_html( emc_acf( 'ramadan_hero_desc', 'The best of deeds in the best of months. Schedule your daily sadaqah, maximise the Last 10 Nights, and let your giving run automatically throughout the blessed month.' ) ); ?></p>

        <!-- Live Countdown -->
        <div class="ramadan-countdown-hero">
            <p class="countdown-label"><?php esc_html_e( 'Next Ramadan begins in:', 'emc-theme' ); ?></p>
            <div class="countdown-grid-hero">
                <div class="countdown-cell-hero"><span id="rmh-days">--</span><small><?php esc_html_e( 'Days', 'emc-theme' ); ?></small></div>
                <div class="countdown-sep">:</div>
                <div class="countdown-cell-hero"><span id="rmh-hours">--</span><small><?php esc_html_e( 'Hours', 'emc-theme' ); ?></small></div>
                <div class="countdown-sep">:</div>
                <div class="countdown-cell-hero"><span id="rmh-mins">--</span><small><?php esc_html_e( 'Mins', 'emc-theme' ); ?></small></div>
                <div class="countdown-sep">:</div>
                <div class="countdown-cell-hero"><span id="rmh-secs">--</span><small><?php esc_html_e( 'Secs', 'emc-theme' ); ?></small></div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════
     GIVING SCHEDULER
     ════════════════════════════════════════ -->
<section class="ramadan-section section-padding" id="ramadan-giving">
    <div class="container">
        <div class="ramadan-layout">

            <!-- Left: Giving Form -->
            <div class="ramadan-form-col">
                <div class="form-card glass-card">
                    <h2><?php esc_html_e( 'Schedule Your Ramadan Giving', 'emc-theme' ); ?></h2>
                    <p class="form-desc"><?php esc_html_e( 'Set a daily amount and choose your giving period — your donation is automatically processed each day.', 'emc-theme' ); ?></p>

                    <!-- Daily Amount -->
                    <div class="form-group">
                        <label><?php esc_html_e( 'Daily Sadaqah Amount', 'emc-theme' ); ?></label>
                        <div class="amount-grid" style="grid-template-columns:repeat(<?php echo esc_attr( max( 1, count( $ramadan_amounts ) ) ); ?>,1fr);">
                            <?php foreach ( $ramadan_amounts as $amount ) : ?>
                                <button class="amount-btn<?php echo $amount === $ramadan_default_amount ? ' active' : ''; ?>" data-amount="<?php echo esc_attr( $amount ); ?>">£<?php echo esc_html( $amount == (int) $amount ? (int) $amount : $amount ); ?></button>
                            <?php endforeach; ?>
                            <button class="amount-btn custom-other"><?php esc_html_e( 'Other', 'emc-theme' ); ?></button>
                        </div>
                        <div class="custom-amount-wrapper" id="ramadan-custom-wrapper" style="display:none;margin-top:0.75rem;">
                            <div class="input-prefix-wrap"><span class="input-prefix">£</span><input type="number" id="ramadan-custom-input" class="form-control" placeholder="0.00" min="1"></div>
                        </div>
                    </div>

                    <!-- Giving Period -->
                    <div class="form-group">
                        <label><?php esc_html_e( 'Giving Period', 'emc-theme' ); ?></label>
                        <div class="giving-period-grid">
                            <label class="period-option">
                                <input type="radio" name="rmperiod" value="30" checked>
                                <div class="period-card">
                                    <i class="fas fa-calendar-check"></i>
                                    <strong><?php esc_html_e( 'Full Ramadan', 'emc-theme' ); ?></strong>
                                    <small><?php esc_html_e( '29–30 days', 'emc-theme' ); ?></small>
                                </div>
                            </label>
                            <label class="period-option">
                                <input type="radio" name="rmperiod" value="10">
                                <div class="period-card">
                                    <i class="fas fa-star"></i>
                                    <strong><?php esc_html_e( 'Last 10 Nights', 'emc-theme' ); ?></strong>
                                    <small><?php esc_html_e( 'Final 10 days', 'emc-theme' ); ?></small>
                                </div>
                            </label>
                            <label class="period-option">
                                <input type="radio" name="rmperiod" value="5">
                                <div class="period-card">
                                    <i class="fas fa-moon"></i>
                                    <strong><?php esc_html_e( 'Odd Nights (27)', 'emc-theme' ); ?></strong>
                                    <small><?php esc_html_e( 'Nights 21,23,25,27,29', 'emc-theme' ); ?></small>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Sadaqah Jariyah Dedication -->
                    <div class="form-group">
                        <label for="ramadan-dedication">
                            <?php esc_html_e( 'Sadaqah Jariyah — Dedicate This Gift', 'emc-theme' ); ?>
                            <span style="font-weight:400;color:var(--text-muted);">(<?php esc_html_e( 'Optional', 'emc-theme' ); ?>)</span>
                        </label>
                        <input type="text" id="ramadan-dedication" class="form-control" placeholder="<?php esc_attr_e( 'e.g. In memory of my late father, Muhammad Ali...', 'emc-theme' ); ?>">
                        <p style="font-size:var(--step--2);color:var(--text-muted);margin-top:0.4rem;">
                            <?php esc_html_e( 'Your dedication will be noted with your donation — a lasting gift of ongoing reward.', 'emc-theme' ); ?>
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="ramadan-donor-name"><?php esc_html_e( 'Your Details', 'emc-theme' ); ?></label>
                        <input type="text" id="ramadan-donor-name" class="form-control" placeholder="<?php esc_attr_e( 'Full name *', 'emc-theme' ); ?>" required>
                        <input type="email" id="ramadan-donor-email" class="form-control" placeholder="<?php esc_attr_e( 'Email address *', 'emc-theme' ); ?>" required style="margin-top:0.75rem;">
                        <div class="form-row address-fields-row" style="margin-top:0.75rem;">
                            <div>
                                <label for="ramadan-donor-address" class="screen-reader-text"><?php esc_html_e( 'Address line 1', 'emc-theme' ); ?></label>
                                <input type="text" id="ramadan-donor-address" class="form-control" autocomplete="address-line1" placeholder="<?php esc_attr_e( 'Address line 1 (required for Gift Aid)', 'emc-theme' ); ?>">
                            </div>
                            <div>
                                <label for="ramadan-donor-postcode" class="screen-reader-text"><?php esc_html_e( 'Postcode', 'emc-theme' ); ?></label>
                                <input type="text" id="ramadan-donor-postcode" class="form-control" autocomplete="postal-code" maxlength="8" placeholder="<?php esc_attr_e( 'Postcode (required for Gift Aid)', 'emc-theme' ); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Summary -->
                    <div class="schedule-summary" id="rm-summary">
                        <p><?php esc_html_e( 'Your total scheduled giving:', 'emc-theme' ); ?></p>
                        <div class="total-amount" id="rm-total">£<?php echo esc_html( number_format( 30 * $ramadan_default_amount, 2 ) ); ?> <span>(30 × £<?php echo esc_html( $ramadan_default_amount ); ?>)</span></div>
                    </div>

                    <!-- Gift Aid -->
                    <div class="gift-aid-box">
                        <label class="gift-aid-label">
                            <input type="checkbox" class="gift-aid-check" id="ramadan-giftaid">
                            <div class="gift-aid-content">
                                <strong><?php esc_html_e( 'Claim Gift Aid', 'emc-theme' ); ?></strong>
                                <p><?php esc_html_e( 'I am a UK taxpayer. EMC can reclaim 25p of tax on every £1 I give at no extra cost to me. Address line 1 and postcode are required to claim Gift Aid.', 'emc-theme' ); ?></p>
                            </div>
                        </label>
                    </div>

                    <button id="ramadan-submit" class="btn btn-primary donate-submit" style="background:linear-gradient(135deg,#1A2050,#0F3A25);">
                        <i class="fas fa-moon" aria-hidden="true"></i>
                        <?php esc_html_e( 'Schedule My Ramadan Giving', 'emc-theme' ); ?>
                    </button>
                    <p class="secure-note"><i class="fas fa-lock"></i> <?php esc_html_e( 'Encrypted & secured. Donations are non-refundable.', 'emc-theme' ); ?></p>
                </div>
            </div>

            <!-- Right: Calculators -->
            <div class="ramadan-sidebar">

                <!-- Fitrana Calculator -->
                <div class="glass-card ramadan-calc-card" id="fitrana-calc">
                    <h3><i class="fas fa-calculator"></i> <?php esc_html_e( 'Fitrana (Zakat ul-Fitr)', 'emc-theme' ); ?></h3>
                    <p class="form-desc"><?php esc_html_e( 'Obligatory charity paid before Eid al-Fitr. Calculated per person in your household.', 'emc-theme' ); ?></p>
                    <div class="form-group">
                        <label><?php esc_html_e( 'Current Fitrana Rate (per person)', 'emc-theme' ); ?></label>
                        <div class="input-prefix-wrap"><span class="input-prefix">£</span><input type="number" id="fitrana-rate" class="form-control" value="<?php echo esc_attr( $fitrana_rate ); ?>" min="0" step="0.01"></div>
                    </div>
                    <div class="form-group">
                        <label><?php esc_html_e( 'Number of People in Household', 'emc-theme' ); ?></label>
                        <input type="number" id="fitrana-people" class="form-control" value="1" min="1">
                    </div>
                    <div class="fitrana-result">
                        <p><?php esc_html_e( 'Total Fitrana Due:', 'emc-theme' ); ?></p>
                        <div class="fitrana-amount" id="fitrana-total">£<?php echo esc_html( number_format( $fitrana_rate, 2 ) ); ?></div>
                    </div>
                    <a href="<?php echo esc_url( $donate_url ); ?>?fund=Zakat" class="btn btn-outline" style="width:100%;justify-content:center;margin-top:1rem;">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <?php esc_html_e( 'Pay Fitrana Now', 'emc-theme' ); ?>
                    </a>
                </div>

                <!-- Fidya Calculator -->
                <div class="glass-card ramadan-calc-card" id="fidya-calc">
                    <h3><i class="fas fa-hand-holding-heart"></i> <?php esc_html_e( 'Fidya', 'emc-theme' ); ?></h3>
                    <p class="form-desc"><?php esc_html_e( 'Compensation for missed fasts due to illness or old age (£5/day per person).', 'emc-theme' ); ?></p>
                    <div class="form-group">
                        <label><?php esc_html_e( 'Fidya Rate (per day/person)', 'emc-theme' ); ?></label>
                        <div class="input-prefix-wrap"><span class="input-prefix">£</span><input type="number" id="fidya-rate" class="form-control" value="<?php echo esc_attr( $fidya_rate ); ?>" min="0" step="0.01"></div>
                    </div>
                    <div class="form-group">
                        <label><?php esc_html_e( 'Number of Missed Fasts', 'emc-theme' ); ?></label>
                        <input type="number" id="fidya-days" class="form-control" value="1" min="1" max="<?php echo esc_attr( $fidya_max_days ); ?>">
                    </div>
                    <div class="form-group">
                        <label for="fidya-donor-name"><?php esc_html_e( 'Your Details', 'emc-theme' ); ?></label>
                        <input type="text" id="fidya-donor-name" class="form-control" autocomplete="name" placeholder="<?php esc_attr_e( 'Full name *', 'emc-theme' ); ?>" required>
                        <label for="fidya-donor-email" class="screen-reader-text"><?php esc_html_e( 'Email address', 'emc-theme' ); ?></label>
                        <input type="email" id="fidya-donor-email" class="form-control" autocomplete="email" placeholder="<?php esc_attr_e( 'Email address *', 'emc-theme' ); ?>" required style="margin-top:0.75rem;">
                        <div class="form-row address-fields-row" style="margin-top:0.75rem;">
                            <div>
                                <label for="fidya-donor-address" class="screen-reader-text"><?php esc_html_e( 'Address line 1', 'emc-theme' ); ?></label>
                                <input type="text" id="fidya-donor-address" class="form-control" autocomplete="address-line1" placeholder="<?php esc_attr_e( 'Address line 1 (optional)', 'emc-theme' ); ?>">
                            </div>
                            <div>
                                <label for="fidya-donor-postcode" class="screen-reader-text"><?php esc_html_e( 'Postcode', 'emc-theme' ); ?></label>
                                <input type="text" id="fidya-donor-postcode" class="form-control" autocomplete="postal-code" maxlength="8" placeholder="<?php esc_attr_e( 'Postcode (optional)', 'emc-theme' ); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="fitrana-result">
                        <p><?php esc_html_e( 'Total Fidya Due:', 'emc-theme' ); ?></p>
                        <div class="fitrana-amount" id="fidya-total">£<?php echo esc_html( number_format( $fidya_rate, 2 ) ); ?></div>
                    </div>
                    <button type="button" id="fidya-btn" class="btn btn-outline" style="width:100%;justify-content:center;margin-top:1rem;">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <?php esc_html_e( 'Pay Fidya Now', 'emc-theme' ); ?>
                    </button>
                    <p class="form-desc" style="margin-top:0.75rem;margin-bottom:0;">
                        <?php esc_html_e( 'Your details are used to process the payment and email your receipt.', 'emc-theme' ); ?>
                    </p>
                </div>

                <!-- Ramadan Impact -->
                <div class="glass-card ramadan-impact-card">
                    <h3><i class="fas fa-star"></i> <?php esc_html_e( 'Why Give in Ramadan?', 'emc-theme' ); ?></h3>
                    <ul class="ramadan-why-list">
                        <li><i class="fas fa-check-circle"></i> <?php esc_html_e( 'Rewards multiplied — every good deed magnified in Ramadan', 'emc-theme' ); ?></li>
                        <li><i class="fas fa-check-circle"></i> <?php esc_html_e( 'Laylatul Qadr — one night worth a thousand months', 'emc-theme' ); ?></li>
                        <li><i class="fas fa-check-circle"></i> <?php esc_html_e( 'Your gift funds Friday prayers, education & community welfare', 'emc-theme' ); ?></li>
                        <li><i class="fas fa-check-circle"></i> <?php esc_html_e( 'Gift Aid adds 25% at no cost to you', 'emc-theme' ); ?></li>
                        <li><i class="fas fa-check-circle"></i> <?php esc_html_e( 'Sadaqah Jariyah — ongoing reward after Ramadan ends', 'emc-theme' ); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
( function() {
    function ramadanCountdown() {
        var configuredDate = window.emcRamadanConfig && window.emcRamadanConfig.startDate ? window.emcRamadanConfig.startDate : <?php echo wp_json_encode( $ramadan_start_date ); ?>;
        var target = new Date( configuredDate + 'T00:00:00' );
        if ( isNaN( target.getTime() ) ) {
            target = new Date( <?php echo wp_json_encode( $ramadan_start_date . 'T00:00:00' ); ?> );
        }
        var now    = new Date();
        var diff   = target - now;
        if ( diff < 0 ) return;
        var d = Math.floor( diff / 86400000 );
        var h = Math.floor( ( diff % 86400000 ) / 3600000 );
        var m = Math.floor( ( diff % 3600000  ) / 60000   );
        var s = Math.floor( ( diff % 60000    ) / 1000    );
        var pad = function(n){ return String(n).padStart(2,'0'); };
        var el = function(id){ return document.getElementById(id); };
        if (el('rmh-days'))  el('rmh-days').textContent  = d;
        if (el('rmh-hours')) el('rmh-hours').textContent = pad(h);
        if (el('rmh-mins'))  el('rmh-mins').textContent  = pad(m);
        if (el('rmh-secs'))  el('rmh-secs').textContent  = pad(s);
    }
    ramadanCountdown();
    setInterval( ramadanCountdown, 1000 );

    // ── Giving scheduler totals are handled by ramadan.js ─────────────────
    // (amount buttons, period radios, and #rm-total updates live in ramadan.js)

    // ── Fitrana ────────────────────────────────────────────────────────────
    function calcFitrana() {
        var rate   = parseFloat( document.getElementById('fitrana-rate')?.value   ) || <?php echo wp_json_encode( $fitrana_rate ); ?>;
        var people = parseFloat( document.getElementById('fitrana-people')?.value ) || 1;
        var el = document.getElementById('fitrana-total');
        if (el) el.textContent = '£' + (rate * people).toFixed(2);
    }
    ['fitrana-rate','fitrana-people'].forEach(function(id){
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', calcFitrana);
    });
    calcFitrana();

    // ── Fidya ──────────────────────────────────────────────────────────────
    function calcFidya() {
        var rate = parseFloat( document.getElementById('fidya-rate')?.value ) || <?php echo wp_json_encode( $fidya_rate ); ?>;
        var days = parseFloat( document.getElementById('fidya-days')?.value ) || 1;
        var el = document.getElementById('fidya-total');
        if (el) el.textContent = '£' + (rate * days).toFixed(2);
    }
    ['fidya-rate','fidya-days'].forEach(function(id){
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', calcFidya);
    });
    calcFidya();
} )();
</script>

<?php get_footer(); ?>
