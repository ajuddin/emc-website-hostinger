<?php
/**
 * Template Name: Donate
 * Template Post Type: page
 *
 * EMC Theme — Donate page template.
 * Hero, impact stats, and campaign sidebar editable via ACF.
 *
 * @package emc-theme
 */

get_header();

if ( ! emc_payment_license_is_active() ) {
    emc_payment_license_render_required();
    get_footer();
    return;
}

wp_enqueue_style( 'emc-page-donate',  EMC_ASSETS . '/css/donate.css',  array( 'emc-style' ), EMC_VERSION );
wp_enqueue_style( 'emc-page-ramadan', EMC_ASSETS . '/css/ramadan.css', array( 'emc-style' ), EMC_VERSION );

// Stripe.js — must load from js.stripe.com for PCI compliance
wp_register_script( 'stripe-js', 'https://js.stripe.com/v3/', array(), null, true );
wp_enqueue_script( 'stripe-js' );

$donate_js_path = EMC_DIR . '/assets/js/donate.js';
if ( file_exists( $donate_js_path ) ) {
    wp_enqueue_script(
        'emc-page-donate',
        EMC_ASSETS . '/js/donate.js',
        array( 'emc-script', 'stripe-js' ),
        filemtime( $donate_js_path ),
        true
    );
    wp_localize_script( 'emc-page-donate', 'emcStripeConfig', array(
        'publishableKey' => emc_stripe_pub_key(),
        'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
        'nonce'          => wp_create_nonce( 'emc_donate_nonce' ),
    ) );
}

$bank_pay_url = 'https://paymentrequest.natwestpayit.com/reusable-link/39ee348b-8fe1-41fe-aa6b-9109dc847445';
$bank_account_name = 'Essex Muslim Centre';
$bank_account_no   = '31512852';
$bank_sort_code    = '56-00-18';
$bank_bic          = 'NWBKGB2L';
$bank_iban         = 'GB38NWBK56001831512852';
$bank_post_address = "Essex Muslim Centre\nDairy Farm Cottage, Cuton Hall Lane\nChelmsford, CM2 6PB\nUnited Kingdom";
?>

<!-- Page Hero -->
<section class="page-hero donate-hero">
    <div class="container">
        <div class="page-hero-content">
            <span class="badge"><i class="fas fa-heart"></i> <?php echo esc_html( emc_acf( 'donate_hero_badge', __( 'Make a Difference', 'emc-theme' ) ) ); ?></span>
            <h1><?php echo esc_html( emc_acf( 'donate_hero_title', __( 'Support Our Centre', 'emc-theme' ) ) ); ?></h1>
            <p><?php echo esc_html( emc_acf( 'donate_hero_desc', __( 'Your generosity funds Friday prayers, youth programmes, reversion support, and vital community welfare in Chelmsford.', 'emc-theme' ) ) ); ?></p>
            <div class="trust-signals">
                <span><i class="fas fa-shield-alt"></i> <?php echo esc_html( emc_acf( 'donate_trust_stripe', 'Secured by Stripe' ) ); ?></span>
                <span><i class="fas fa-certificate"></i> <?php printf( esc_html__( 'Charity No. %s', 'emc-theme' ), esc_html( emc_option( 'emc_charity_number', '1209815' ) ) ); ?></span>
                <span><i class="fas fa-check-circle"></i> <?php echo esc_html( emc_acf( 'donate_trust_giftaid', 'Gift Aid Eligible' ) ); ?></span>
            </div>
        </div>
    </div>
</section>

<!-- Donation Tabs -->
<section class="donate-section">
    <div class="container">
        <div class="donate-layout">

            <!-- Left: Donation Form -->
            <div class="donate-form-col">
                <div class="tab-nav">
                    <button class="tab-btn active" data-tab="one-off"><i class="fas fa-hand-holding-heart"></i> <?php echo esc_html( emc_acf( 'donate_tab_oneoff', 'One-Off' ) ); ?></button>
                    <button class="tab-btn" data-tab="regular"><i class="fas fa-sync-alt"></i> <?php echo esc_html( emc_acf( 'donate_tab_regular', 'Regular' ) ); ?></button>
                    <button class="tab-btn" data-tab="ramadan-tab"><i class="fas fa-moon"></i> <?php echo esc_html( emc_acf( 'donate_tab_ramadan', 'Ramadan' ) ); ?></button>
                    <button class="tab-btn" data-tab="zakat"><i class="fas fa-calculator"></i> <?php echo esc_html( emc_acf( 'donate_tab_zakat', 'Zakat' ) ); ?></button>
                </div>

                <!-- ONE-OFF TAB -->
                <div class="tab-panel active" id="tab-one-off">
                    <div class="form-card glass-card">
                        <h3><?php echo esc_html( emc_acf( 'donate_oneoff_heading', 'One-Off Donation' ) ); ?></h3>
                        <p class="form-desc"><?php echo esc_html( emc_acf( 'donate_oneoff_desc', 'Every amount makes a real difference to our community.' ) ); ?></p>
                        <div class="amount-grid">
                            <button class="amount-btn">£5</button>
                            <button class="amount-btn">£10</button>
                            <button class="amount-btn active">£25</button>
                            <button class="amount-btn">£50</button>
                            <button class="amount-btn">£100</button>
                            <button class="amount-btn custom-other"><?php esc_html_e( 'Other', 'emc-theme' ); ?></button>
                        </div>
                        <div class="custom-amount-wrapper" id="custom-amount-wrapper" style="display:none;">
                            <label><?php esc_html_e( 'Enter Amount (£)', 'emc-theme' ); ?></label>
                            <div class="input-prefix-wrap"><span class="input-prefix">£</span><input type="number" id="custom-amount-input" class="form-control" placeholder="0.00" min="1"></div>
                        </div>
                        <div class="donor-details-grid">
                            <div class="form-group">
                                <label for="donor-name-one"><?php esc_html_e( 'Full Name *', 'emc-theme' ); ?></label>
                                <input type="text" id="donor-name-one" class="form-control donor-name" autocomplete="name" required>
                            </div>
                            <div class="form-group">
                                <label for="donor-email-one"><?php esc_html_e( 'Email Address *', 'emc-theme' ); ?></label>
                                <input type="email" id="donor-email-one" class="form-control donor-email" autocomplete="email" required>
                            </div>
                        </div>
                        <div class="form-row address-fields-row">
                            <div class="form-group">
                                <label for="donor-address-one"><?php esc_html_e( 'Address Line 1 (required for Gift Aid)', 'emc-theme' ); ?></label>
                                <input type="text" id="donor-address-one" class="form-control donor-address" autocomplete="address-line1" placeholder="<?php esc_attr_e( 'House number and street', 'emc-theme' ); ?>">
                            </div>
                            <div class="form-group">
                                <label for="donor-postcode-one"><?php esc_html_e( 'Postcode *', 'emc-theme' ); ?></label>
                                <input type="text" id="donor-postcode-one" class="form-control donor-postcode" autocomplete="postal-code" maxlength="8">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?php echo esc_html( emc_acf( 'donate_fund_label', 'Donation Fund' ) ); ?></label>
                            <div class="category-grid">
                                <button class="cat-btn active" data-cat="General Fund"><i class="fas fa-mosque"></i> <?php echo esc_html( emc_acf( 'donate_fund_general', 'General Fund' ) ); ?></button>
                                <button class="cat-btn" data-cat="Education"><i class="fas fa-book-open"></i> <?php echo esc_html( emc_acf( 'donate_fund_education', 'Education' ) ); ?></button>
                                <button class="cat-btn" data-cat="Zakat"><i class="fas fa-hand-holding-usd"></i> <?php echo esc_html( emc_acf( 'donate_fund_zakat', 'Zakat' ) ); ?></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?php esc_html_e( 'Payment Method', 'emc-theme' ); ?></label>
                            <div class="payment-method-grid">
                                <span class="payment-method-option active"><i class="fas fa-credit-card"></i> <?php esc_html_e( 'Online', 'emc-theme' ); ?></span>
                                <a class="payment-method-option" href="<?php echo esc_url( $bank_pay_url ); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-university"></i> <?php esc_html_e( 'Pay by Bank', 'emc-theme' ); ?></a>
                                <a class="payment-method-option" href="#other-ways-to-donate"><i class="fas fa-envelope-open-text"></i> <?php esc_html_e( 'Cheque', 'emc-theme' ); ?></a>
                                <a class="payment-method-option" href="#other-ways-to-donate"><i class="fas fa-mosque"></i> <?php esc_html_e( 'In-Mosque', 'emc-theme' ); ?></a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="donor-message"><?php esc_html_e( 'Personal Message', 'emc-theme' ); ?> <span style="font-weight:400; color:var(--text-muted)">(<?php esc_html_e( 'Optional', 'emc-theme' ); ?>)</span></label>
                            <textarea id="donor-message" class="form-control" rows="3" placeholder="<?php esc_attr_e( 'Add a personal message or dedication...', 'emc-theme' ); ?>"></textarea>
                        </div>
                        <div class="gift-aid-box">
                            <label class="gift-aid-label">
                                <input type="checkbox" id="gift-aid-one" class="gift-aid-check">
                                <div class="gift-aid-content">
                                    <strong><?php echo esc_html( emc_acf( 'donate_giftaid_heading', 'Claim Gift Aid' ) ); ?></strong>
                                    <p><?php echo esc_html( emc_acf( 'donate_giftaid_text', 'I am a UK taxpayer and understand that if I pay less Income Tax / Capital Gains Tax than the amount of Gift Aid claimed on all my donations, it is my responsibility to pay any difference. EMC can reclaim 25p of tax on every £1 I give.' ) ); ?></p>
                                </div>
                            </label>
                        </div>
                        <button class="btn btn-primary donate-submit"><i class="fas fa-lock"></i> <?php echo esc_html( emc_acf( 'donate_oneoff_btn', 'Donate Securely' ) ); ?></button>
                        <p class="secure-note"><i class="fas fa-lock"></i> <?php echo esc_html( emc_acf( 'donate_secure_note', 'Encrypted & secured by Stripe. Your card details are never stored on our servers.' ) ); ?></p>
                    </div>
                </div>

                <!-- REGULAR TAB -->
                <div class="tab-panel" id="tab-regular">
                    <div class="form-card glass-card">
                        <h3><?php echo esc_html( emc_acf( 'donate_regular_heading', 'Regular Donation' ) ); ?></h3>
                        <p class="form-desc"><?php echo esc_html( emc_acf( 'donate_regular_desc', 'Set up a recurring gift to provide ongoing support to the community.' ) ); ?></p>
                        <div class="amount-grid">
                            <button class="amount-btn">£5</button><button class="amount-btn">£10</button><button class="amount-btn active">£20</button><button class="amount-btn">£50</button>
                            <button class="amount-btn custom-other"><?php esc_html_e( 'Other', 'emc-theme' ); ?></button>
                        </div>
                        <div class="custom-amount-wrapper" style="display:none;">
                            <label><?php esc_html_e( 'Enter Amount (£)', 'emc-theme' ); ?></label>
                            <div class="input-prefix-wrap"><span class="input-prefix">£</span><input type="number" class="custom-amount-input form-control" placeholder="0.00" min="1"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label><?php esc_html_e( 'Frequency', 'emc-theme' ); ?></label><select class="form-control regular-frequency"><option value="daily"><?php esc_html_e( 'Daily', 'emc-theme' ); ?></option><option value="monthly" selected><?php esc_html_e( 'Monthly', 'emc-theme' ); ?></option><option value="weekly"><?php esc_html_e( 'Weekly', 'emc-theme' ); ?></option><option value="quarterly"><?php esc_html_e( 'Quarterly', 'emc-theme' ); ?></option><option value="annually"><?php esc_html_e( 'Annually', 'emc-theme' ); ?></option></select></div>
                            <div class="form-group"><label><?php esc_html_e( 'Start Date', 'emc-theme' ); ?></label><input type="date" class="form-control regular-start-date"></div>
                        </div>
                        <div class="form-group"><label><?php esc_html_e( 'Donation Fund', 'emc-theme' ); ?></label><select class="form-control regular-fund"><option value="General Fund"><?php esc_html_e( 'General Fund', 'emc-theme' ); ?></option><option value="Education"><?php esc_html_e( 'Education', 'emc-theme' ); ?></option><option value="Zakat"><?php esc_html_e( 'Zakat', 'emc-theme' ); ?></option></select></div>
                        <div class="donor-details-grid">
                            <div class="form-group">
                                <label for="donor-name-regular"><?php esc_html_e( 'Full Name *', 'emc-theme' ); ?></label>
                                <input type="text" id="donor-name-regular" class="form-control donor-name" autocomplete="name" required>
                            </div>
                            <div class="form-group">
                                <label for="donor-email-regular"><?php esc_html_e( 'Email Address *', 'emc-theme' ); ?></label>
                                <input type="email" id="donor-email-regular" class="form-control donor-email" autocomplete="email" required>
                            </div>
                        </div>
                        <div class="form-row address-fields-row">
                            <div class="form-group">
                                <label for="donor-address-regular"><?php esc_html_e( 'Address Line 1 (required for Gift Aid)', 'emc-theme' ); ?></label>
                                <input type="text" id="donor-address-regular" class="form-control donor-address" autocomplete="address-line1">
                            </div>
                            <div class="form-group">
                                <label for="donor-postcode-regular"><?php esc_html_e( 'Postcode *', 'emc-theme' ); ?></label>
                                <input type="text" id="donor-postcode-regular" class="form-control donor-postcode" autocomplete="postal-code" maxlength="8">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?php esc_html_e( 'Payment Method', 'emc-theme' ); ?></label>
                            <div class="payment-method-grid">
                                <span class="payment-method-option active"><i class="fas fa-credit-card"></i> <?php esc_html_e( 'Online', 'emc-theme' ); ?></span>
                                <a class="payment-method-option" href="<?php echo esc_url( $bank_pay_url ); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-university"></i> <?php esc_html_e( 'Pay by Bank', 'emc-theme' ); ?></a>
                                <a class="payment-method-option" href="#other-ways-to-donate"><i class="fas fa-file-alt"></i> <?php esc_html_e( 'Standing Order', 'emc-theme' ); ?></a>
                            </div>
                        </div>
                        <div class="gift-aid-box"><label class="gift-aid-label"><input type="checkbox" id="gift-aid-regular" class="gift-aid-check"><div class="gift-aid-content"><strong><?php echo esc_html( emc_acf( 'donate_giftaid_heading', 'Claim Gift Aid' ) ); ?></strong><p><?php echo esc_html( emc_acf( 'donate_regular_giftaid_text', 'I am a UK taxpayer. EMC can reclaim 25p of tax on every £1 I give at no extra cost to me.' ) ); ?></p></div></label></div>
                        <button class="btn btn-primary donate-submit"><i class="fas fa-sync-alt"></i> <?php echo esc_html( emc_acf( 'donate_regular_btn', 'Set Up Monthly Giving' ) ); ?></button>
                        <?php
                        $portal_url  = emc_acf( 'donate_portal_url', '#' );
                        $portal_text = emc_acf( 'donate_portal_text', 'Already a regular donor? Access your Donor Portal to view, pause, or cancel your giving schedule.' );
                        ?>
                        <div class="donor-portal-box"><i class="fas fa-user-circle"></i><p><?php echo wp_kses( $portal_text, array( 'a' => array( 'href' => true ) ) ); ?></p></div>
                    </div>
                </div>

                <!-- RAMADAN LINK CARD (replaces inline tab) -->
                <div class="tab-panel" id="tab-ramadan-tab">
                    <div class="form-card glass-card ramadan-link-card">
                        <div class="ramadan-link-inner">
                            <div class="ramadan-link-icon" aria-hidden="true">
                                <i class="fas fa-moon"></i>
                                <i class="fas fa-star ramadan-star"></i>
                            </div>
                            <div class="ramadan-link-text">
                                <span class="ramadan-badge"><i class="fas fa-moon"></i> <?php echo esc_html( emc_acf( 'donate_ramadan_badge', 'Ramadan 1447 AH' ) ); ?></span>
                                <h3><?php esc_html_e( 'Ramadan Daily Giving', 'emc-theme' ); ?></h3>
                                <p><?php esc_html_e( 'Schedule daily sadaqah, maximise your Last 10 Nights, and calculate your Fitrana — all on our dedicated Ramadan Giving page.', 'emc-theme' ); ?></p>
                                <ul class="ramadan-features">
                                    <li><i class="fas fa-check-circle"></i> <?php esc_html_e( 'Daily auto-giving for Full Ramadan / Last 10 Nights / Odd Nights', 'emc-theme' ); ?></li>
                                    <li><i class="fas fa-check-circle"></i> <?php esc_html_e( 'Live countdown to next Ramadan', 'emc-theme' ); ?></li>
                                    <li><i class="fas fa-check-circle"></i> <?php esc_html_e( 'Fitrana & Fidya calculator', 'emc-theme' ); ?></li>
                                    <li><i class="fas fa-check-circle"></i> <?php esc_html_e( 'Sadaqah Jariyah dedication', 'emc-theme' ); ?></li>
                                </ul>
                                <?php
                                $ramadan_url = get_permalink( get_page_by_path( 'ramadan-givings' ) )
                                               ?: get_permalink( get_page_by_path( 'ramadan' ) )
                                               ?: home_url( '/ramadan-givings/' );
                                ?>
                                <a href="<?php echo esc_url( $ramadan_url ); ?>" class="btn btn-primary" style="margin-top:1.5rem;">
                                    <i class="fas fa-moon" aria-hidden="true"></i>
                                    <?php esc_html_e( 'Go to Ramadan Giving Page', 'emc-theme' ); ?>
                                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ZAKAT TAB -->
                <div class="tab-panel" id="tab-zakat">
                    <div class="form-card glass-card">
                        <h3><?php echo esc_html( emc_acf( 'donate_zakat_heading', 'Zakat Calculator' ) ); ?></h3>
                        <p class="form-desc"><?php echo esc_html( emc_acf( 'donate_zakat_nisab', 'Nisab (Silver): £452.06 | Zakat rate: 2.5%' ) ); ?></p>
                        <div class="zakat-form">
                            <div class="donor-details-grid">
                                <div class="form-group">
                                    <label for="donor-name-zakat"><?php esc_html_e( 'Full Name *', 'emc-theme' ); ?></label>
                                    <input type="text" id="donor-name-zakat" class="form-control donor-name" autocomplete="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="donor-email-zakat"><?php esc_html_e( 'Email Address *', 'emc-theme' ); ?></label>
                                    <input type="email" id="donor-email-zakat" class="form-control donor-email" autocomplete="email" required>
                                </div>
                            </div>
                            <div class="form-row address-fields-row">
                                <div class="form-group">
                                    <label for="donor-address-zakat"><?php esc_html_e( 'Address Line 1 (optional)', 'emc-theme' ); ?></label>
                                    <input type="text" id="donor-address-zakat" class="form-control donor-address" autocomplete="address-line1">
                                </div>
                                <div class="form-group">
                                    <label for="donor-postcode-zakat"><?php esc_html_e( 'Postcode (optional)', 'emc-theme' ); ?></label>
                                    <input type="text" id="donor-postcode-zakat" class="form-control donor-postcode" autocomplete="postal-code" maxlength="8">
                                </div>
                            </div>
                            <?php
                            $zakat_fields = array(
                                array( 'id' => 'z-cash',     'key' => 'donate_zakat_label_cash',   'default' => 'Cash & Bank Savings (£)' ),
                                array( 'id' => 'z-gold',     'key' => 'donate_zakat_label_gold',   'default' => 'Gold & Silver Value (£)' ),
                                array( 'id' => 'z-business', 'key' => 'donate_zakat_label_biz',    'default' => 'Business / Trade Assets (£)' ),
                                array( 'id' => 'z-owed',     'key' => 'donate_zakat_label_owed',   'default' => 'Money Owed to You (£)' ),
                                array( 'id' => 'z-deduct',   'key' => 'donate_zakat_label_deduct', 'default' => 'Money You Owe (£) — Deduct' ),
                            );
                            foreach ( $zakat_fields as $zf ) : ?>
                            <div class="form-group"><label><?php echo esc_html( emc_acf( $zf['key'], $zf['default'] ) ); ?></label><div class="input-prefix-wrap"><span class="input-prefix">£</span><input type="number" class="form-control zakat-input" id="<?php echo esc_attr( $zf['id'] ); ?>" placeholder="0.00"></div></div>
                            <?php endforeach; ?>
                            <div class="zakat-result" id="zakat-result"><div class="zakat-result-inner"><p><?php echo esc_html( emc_acf( 'donate_zakat_result_label', 'Your Estimated Zakat' ) ); ?></p><div class="zakat-amount" id="zakat-amount">£0.00</div><p class="zakat-note" id="zakat-note"><?php esc_html_e( 'Enter your assets above to calculate.', 'emc-theme' ); ?></p></div></div>
                            <div class="form-group">
                                <label><?php esc_html_e( 'Payment Method', 'emc-theme' ); ?></label>
                                <div class="payment-method-grid">
                                    <span class="payment-method-option active"><i class="fas fa-credit-card"></i> <?php esc_html_e( 'Online', 'emc-theme' ); ?></span>
                                    <a class="payment-method-option" href="<?php echo esc_url( $bank_pay_url ); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-university"></i> <?php esc_html_e( 'Pay by Bank', 'emc-theme' ); ?></a>
                                    <a class="payment-method-option" href="#other-ways-to-donate"><i class="fas fa-envelope-open-text"></i> <?php esc_html_e( 'Cheque', 'emc-theme' ); ?></a>
                                    <a class="payment-method-option" href="#other-ways-to-donate"><i class="fas fa-mosque"></i> <?php esc_html_e( 'In-Mosque', 'emc-theme' ); ?></a>
                                </div>
                            </div>
                            <button class="btn btn-primary donate-submit" id="donate-zakat-btn" style="display:none;"><i class="fas fa-hand-holding-usd"></i> <?php echo esc_html( emc_acf( 'donate_zakat_btn', 'Donate My Zakat' ) ); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Sidebar -->
            <div class="donate-sidebar">
                <div class="sidebar-card glass-card">
                    <h4><i class="fas fa-chart-line"></i> <?php echo esc_html( emc_acf( 'donate_impact_heading', 'Your Impact' ) ); ?></h4>
                    <div class="impact-stats">
                        <?php
                        for ( $i = 1; $i <= 4; $i++ ) :
                            $amount = emc_acf( 'donate_impact_' . $i . '_amount', '' );
                            $desc   = emc_acf( 'donate_impact_' . $i . '_desc',   '' );
                            if ( empty( $amount ) ) continue;
                        ?>
                        <div class="impact-item">
                            <span class="impact-amount"><?php echo esc_html( $amount ); ?></span>
                            <span><?php echo esc_html( $desc ); ?></span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="sidebar-card glass-card campaign-sidebar">
                    <h4><i class="fas fa-mosque"></i> <?php echo esc_html( emc_acf( 'donate_campaign_sidebar_heading', 'Building Campaign' ) ); ?></h4>
                    <p class="campaign-name">"<?php echo esc_html( emc_acf( 'donate_campaign_name', 'Be One of the 313' ) ); ?>"</p>
                    <div class="progress-bar-wrap">
                        <div class="progress-bar-track">
                            <div class="progress-bar-fill" style="width: <?php echo esc_attr( emc_acf( 'donate_campaign_percent', '63' ) ); ?>%"></div>
                        </div>
                        <div class="progress-labels">
                            <span><?php echo esc_html( emc_acf( 'donate_campaign_raised', '£62,500' ) ); ?> raised</span>
                            <span>Goal: <?php echo esc_html( emc_acf( 'donate_campaign_goal', '£100,000' ) ); ?></span>
                        </div>
                    </div>
                    <?php
                    $campaign_page = get_page_by_path( 'campaign' );
                    $campaign_url  = $campaign_page ? get_permalink( $campaign_page ) : home_url( '/campaign/' );
                    ?>
                    <a href="<?php echo esc_url( $campaign_url ); ?>" class="btn btn-outline" style="width:100%; margin-top:1rem; justify-content:center;"><?php echo esc_html( emc_acf( 'donate_campaign_btn', 'View Campaign' ) ); ?></a>
                </div>

                <div class="sidebar-card trust-card">
                    <div class="trust-badge-grid">
                        <div class="trust-item"><i class="fab fa-stripe"></i> <?php echo esc_html( emc_acf( 'donate_trust_badge_stripe', 'Stripe Secured' ) ); ?></div>
                        <div class="trust-item"><i class="fas fa-user-shield"></i> <?php echo esc_html( emc_acf( 'donate_trust_badge_gdpr', 'GDPR Compliant' ) ); ?></div>
                        <div class="trust-item"><i class="fas fa-hand-holding-heart"></i> <?php echo esc_html( emc_acf( 'donate_trust_badge_ga', 'Gift Aid Registered' ) ); ?></div>
                        <div class="trust-item"><i class="fas fa-certificate"></i> <?php printf( esc_html__( 'Charity No. %s', 'emc-theme' ), esc_html( emc_option( 'emc_charity_number', '1209815' ) ) ); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     OTHER WAYS TO DONATE
     ═══════════════════════════════════════════════════════ -->
<section class="other-ways-section section-padding" id="other-ways-to-donate">
    <div class="container">
        <div class="section-header">
            <span class="subtitle"><i class="fas fa-hand-holding-heart"></i> <?php esc_html_e( 'More Ways to Give', 'emc-theme' ); ?></span>
            <h2><?php esc_html_e( 'Other Ways to Donate', 'emc-theme' ); ?></h2>
            <p style="color:var(--text-muted);max-width:640px;margin:0 auto;"><?php esc_html_e( 'Prefer to give offline? We\'re grateful for every contribution, however you choose to give.', 'emc-theme' ); ?></p>
        </div>

        <div class="other-ways-grid">

            <!-- Bank Transfer -->
            <div class="other-way-card">
                <h3><?php esc_html_e( 'Bank Transfer', 'emc-theme' ); ?></h3>
                <p class="other-way-desc"><?php esc_html_e( 'Use our NatWest Payit link or your banking app. Please include your name as the payment reference.', 'emc-theme' ); ?></p>
                <h4 class="bank-details-heading"><?php esc_html_e( 'UK Bank Payment', 'emc-theme' ); ?></h4>
                <div class="bank-details">
                    <div class="bank-row"><span><?php esc_html_e( 'Account Name', 'emc-theme' ); ?></span><strong><?php echo esc_html( $bank_account_name ); ?></strong></div>
                    <div class="bank-row"><span><?php esc_html_e( 'Account Number', 'emc-theme' ); ?></span><strong class="mono"><?php echo esc_html( $bank_account_no ); ?></strong></div>
                    <div class="bank-row"><span><?php esc_html_e( 'Sort Code', 'emc-theme' ); ?></span><strong class="mono"><?php echo esc_html( $bank_sort_code ); ?></strong></div>
                </div>
                <h4 class="bank-details-heading"><?php esc_html_e( 'International Bank Payment', 'emc-theme' ); ?></h4>
                <div class="bank-details">
                    <div class="bank-row"><span><?php esc_html_e( 'BIC / SWIFT', 'emc-theme' ); ?></span><strong class="mono"><?php echo esc_html( $bank_bic ); ?></strong></div>
                    <div class="bank-row"><span><?php esc_html_e( 'IBAN', 'emc-theme' ); ?></span><strong class="mono bank-value-long"><?php echo esc_html( $bank_iban ); ?></strong></div>
                </div>
                <a href="<?php echo esc_url( $bank_pay_url ); ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-university" aria-hidden="true"></i>
                    <?php esc_html_e( 'Pay by Your Bank', 'emc-theme' ); ?>
                </a>
            </div>

            <!-- Standing Order -->
            <div class="other-way-card">
                <h3><?php esc_html_e( 'Standing Order', 'emc-theme' ); ?></h3>
                <p class="other-way-desc"><?php esc_html_e( 'You can set up a regular donation by downloading, completing and posting this standing order form to your bank.', 'emc-theme' ); ?></p>
                <?php $so_url = emc_acf( 'donate_so_pdf', 'https://essexmuslimcentre.org/standing-order-form/' ); ?>
                <a href="<?php echo esc_url( $so_url ); ?>" class="btn btn-outline" <?php if ( $so_url !== '#' ) echo 'download target="_blank" rel="noopener"'; ?>>
                    <i class="fas fa-download" aria-hidden="true"></i>
                    <?php esc_html_e( 'Download PDF', 'emc-theme' ); ?>
                </a>
            </div>

            <!-- Cheque / Post -->
            <div class="other-way-card">
                <h3><?php esc_html_e( 'Post', 'emc-theme' ); ?></h3>
                <p class="other-way-desc"><?php esc_html_e( 'To send a cheque or postal order, make it payable to Essex Muslim Centre and post it to the address below.', 'emc-theme' ); ?></p>
                <div class="bank-details">
                    <div class="bank-row"><span><?php esc_html_e( 'Payable to', 'emc-theme' ); ?></span><strong><?php echo esc_html( $bank_account_name ); ?></strong></div>
                    <div class="bank-row"><span><?php esc_html_e( 'Post to', 'emc-theme' ); ?></span><strong><?php echo nl2br( esc_html( $bank_post_address ) ); ?></strong></div>
                </div>
                <p class="other-way-note"><?php esc_html_e( 'Please include a note containing your name, address and email so we can acknowledge your donation.', 'emc-theme' ); ?></p>
            </div>

            <!-- Cash / In-Mosque -->
            <div class="other-way-card glass-card legacy-other-way" hidden>
                <div class="other-way-icon"><i class="fas fa-mosque"></i></div>
                <h3><?php esc_html_e( 'In-Mosque Giving', 'emc-theme' ); ?></h3>
                <p class="other-way-desc"><?php esc_html_e( 'Drop your donation in the collection boxes at our reception or during Jumu\'ah prayers.', 'emc-theme' ); ?></p>
                <ul class="other-way-steps">
                    <li><span><i class="fas fa-box"></i></span><?php esc_html_e( 'Donation boxes at reception (open daily)', 'emc-theme' ); ?></li>
                    <li><span><i class="fas fa-praying-hands"></i></span><?php esc_html_e( 'Jumu\'ah collection — Fridays 13:15 & 14:15', 'emc-theme' ); ?></li>
                    <li><span><i class="fas fa-hand-holding-heart"></i></span><?php esc_html_e( 'Envelope donations available at the front desk', 'emc-theme' ); ?></li>
                </ul>
            </div>

            <!-- Membership -->
            <div class="other-way-card glass-card other-way-featured legacy-other-way" hidden>
                <div class="other-way-icon"><i class="fas fa-id-card"></i></div>
                <h3><?php esc_html_e( 'Membership', 'emc-theme' ); ?></h3>
                <p class="other-way-desc"><?php esc_html_e( 'Memberships enable regular support that gives your mosque the stability it needs. If EMC matters to you, a membership is a way to support it with consistency, care and intention.', 'emc-theme' ); ?></p>
                <?php $member_url = get_permalink( get_page_by_path( 'membership' ) ) ?: home_url( '/membership/' ); ?>
                <a href="<?php echo esc_url( $member_url ); ?>" class="btn btn-primary">
                    <i class="fas fa-id-card" aria-hidden="true"></i>
                    <?php esc_html_e( 'Become a Member', 'emc-theme' ); ?>
                </a>
            </div>

            <!-- Fundraise -->
            <div class="other-way-card glass-card legacy-other-way" hidden>
                <div class="other-way-icon"><i class="fas fa-running"></i></div>
                <h3><?php esc_html_e( 'Fundraise for Us', 'emc-theme' ); ?></h3>
                <p class="other-way-desc"><?php esc_html_e( 'Running, cycling, or organising an event? Raise funds for EMC through JustGiving or contact us to set up a bespoke campaign.', 'emc-theme' ); ?></p>
                <?php $jg_url = emc_acf( 'donate_justgiving_url', '#' ); ?>
                <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                    <?php if ( $jg_url && $jg_url !== '#' ) : ?>
                    <a href="<?php echo esc_url( $jg_url ); ?>" class="btn btn-outline" target="_blank" rel="noopener noreferrer">
                        <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                        <?php esc_html_e( 'JustGiving Page', 'emc-theme' ); ?>
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'contact' ) ) ?: home_url( '/contact/' ) ); ?>" class="btn btn-outline">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <?php esc_html_e( 'Get in Touch', 'emc-theme' ); ?>
                    </a>
                </div>
            </div>

        </div><!-- .other-ways-grid -->
    </div>
</section>

<?php get_footer(); ?>
