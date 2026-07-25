<?php
/**
 * Template Name: Gift Aid Declaration
 * Template Post Type: page
 *
 * @package emc-theme
 */

get_header();
?>

<main class="gift-aid-page">
    <section class="gift-aid-hero" aria-labelledby="gift-aid-page-title">
        <div class="container gift-aid-hero-inner">
            <span class="gift-aid-eyebrow"><i class="fas fa-hand-holding-heart" aria-hidden="true"></i> <?php esc_html_e( 'Make your donation go further', 'emc-theme' ); ?></span>
            <h1 id="gift-aid-page-title"><?php esc_html_e( 'Gift Aid Declaration', 'emc-theme' ); ?></h1>
            <p><?php esc_html_e( 'Boost your donation by 25p of Gift Aid for every £1 you donate, at no extra cost to you.', 'emc-theme' ); ?></p>
        </div>
    </section>

    <section class="gift-aid-section">
        <div class="container">
            <div class="gift-aid-layout">
                <article class="gift-aid-form-card">
                    <header class="gift-aid-form-header">
                        <div>
                            <span><?php esc_html_e( 'Essex Muslim Centre', 'emc-theme' ); ?></span>
                            <h2><?php esc_html_e( 'Complete your declaration', 'emc-theme' ); ?></h2>
                            <p><?php esc_html_e( 'Fields marked with * are required. Please enter your home address.', 'emc-theme' ); ?></p>
                        </div>
                        <div class="gift-aid-mark" aria-hidden="true">gift<span>aid</span>it</div>
                    </header>

                    <div class="gift-aid-accent" aria-hidden="true"></div>

                    <form
                        id="gift-aid-form"
                        class="gift-aid-form"
                        method="post"
                        data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
                        novalidate
                    >
                        <input type="hidden" name="action" value="emc_gift_aid">
                        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'emc_nonce' ) ); ?>">
                        <div class="gift-aid-honeypot" aria-hidden="true">
                            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <div class="gift-aid-grid gift-aid-grid--name">
                            <div class="gift-aid-field">
                                <label for="gift-aid-prefix"><?php esc_html_e( 'Prefix', 'emc-theme' ); ?></label>
                                <select id="gift-aid-prefix" name="prefix" autocomplete="honorific-prefix">
                                    <option value=""><?php esc_html_e( 'Select', 'emc-theme' ); ?></option>
                                    <option value="Mr"><?php esc_html_e( 'Mr', 'emc-theme' ); ?></option>
                                    <option value="Mrs"><?php esc_html_e( 'Mrs', 'emc-theme' ); ?></option>
                                    <option value="Miss"><?php esc_html_e( 'Miss', 'emc-theme' ); ?></option>
                                    <option value="Ms"><?php esc_html_e( 'Ms', 'emc-theme' ); ?></option>
                                    <option value="Dr"><?php esc_html_e( 'Dr', 'emc-theme' ); ?></option>
                                </select>
                            </div>
                            <div class="gift-aid-field">
                                <label for="gift-aid-first-name"><?php esc_html_e( 'First Name', 'emc-theme' ); ?> <span>*</span></label>
                                <input type="text" id="gift-aid-first-name" name="first_name" autocomplete="given-name" placeholder="<?php esc_attr_e( 'e.g. Mohammad', 'emc-theme' ); ?>" required>
                            </div>
                            <div class="gift-aid-field">
                                <label for="gift-aid-last-name"><?php esc_html_e( 'Last Name', 'emc-theme' ); ?> <span>*</span></label>
                                <input type="text" id="gift-aid-last-name" name="last_name" autocomplete="family-name" placeholder="<?php esc_attr_e( 'e.g. Hafeez', 'emc-theme' ); ?>" required>
                            </div>
                        </div>

                        <div class="gift-aid-field">
                            <label for="gift-aid-address"><?php esc_html_e( 'Home Address Line 1', 'emc-theme' ); ?> <span>*</span></label>
                            <input type="text" id="gift-aid-address" name="address_line_1" autocomplete="address-line1" placeholder="<?php esc_attr_e( 'e.g. 42 Wallaby Way', 'emc-theme' ); ?>" required>
                        </div>

                        <div class="gift-aid-field">
                            <label for="gift-aid-town"><?php esc_html_e( 'Town / City', 'emc-theme' ); ?> <span>*</span></label>
                            <input type="text" id="gift-aid-town" name="town_city" autocomplete="address-level2" placeholder="<?php esc_attr_e( 'e.g. Chelmsford', 'emc-theme' ); ?>" required>
                        </div>

                        <div class="gift-aid-grid">
                            <div class="gift-aid-field">
                                <label for="gift-aid-postcode"><?php esc_html_e( 'Postcode / ZIP Code', 'emc-theme' ); ?> <span id="gift-aid-postcode-required">*</span></label>
                                <input type="text" id="gift-aid-postcode" name="postcode" autocomplete="postal-code" placeholder="<?php esc_attr_e( 'e.g. CM2 6PB', 'emc-theme' ); ?>" maxlength="12" required>
                            </div>
                            <div class="gift-aid-field">
                                <label for="gift-aid-country"><?php esc_html_e( 'Country', 'emc-theme' ); ?> <span>*</span></label>
                                <select id="gift-aid-country" name="country" autocomplete="country-name" required>
                                    <option value="United Kingdom" selected><?php esc_html_e( 'United Kingdom', 'emc-theme' ); ?></option>
                                    <option value="Ireland"><?php esc_html_e( 'Ireland', 'emc-theme' ); ?></option>
                                    <option value="Isle of Man"><?php esc_html_e( 'Isle of Man', 'emc-theme' ); ?></option>
                                    <option value="Jersey"><?php esc_html_e( 'Jersey', 'emc-theme' ); ?></option>
                                    <option value="Guernsey"><?php esc_html_e( 'Guernsey', 'emc-theme' ); ?></option>
                                    <option value="Other"><?php esc_html_e( 'Other', 'emc-theme' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="gift-aid-field" id="gift-aid-other-country-field" hidden>
                            <label for="gift-aid-other-country"><?php esc_html_e( 'Country name', 'emc-theme' ); ?> <span>*</span></label>
                            <input type="text" id="gift-aid-other-country" name="country_other" autocomplete="country-name">
                        </div>

                        <div class="gift-aid-grid">
                            <div class="gift-aid-field">
                                <label for="gift-aid-phone"><?php esc_html_e( 'Phone', 'emc-theme' ); ?> <span>*</span></label>
                                <input type="tel" id="gift-aid-phone" name="phone" autocomplete="tel" placeholder="<?php esc_attr_e( 'e.g. +44 7123 456789', 'emc-theme' ); ?>" required>
                            </div>
                            <div class="gift-aid-field">
                                <label for="gift-aid-email"><?php esc_html_e( 'Email Address', 'emc-theme' ); ?> <span>*</span></label>
                                <input type="email" id="gift-aid-email" name="email" autocomplete="email" placeholder="<?php esc_attr_e( 'e.g. donor@example.com', 'emc-theme' ); ?>" required>
                            </div>
                        </div>

                        <fieldset class="gift-aid-confirmation">
                            <legend><?php esc_html_e( 'Confirm', 'emc-theme' ); ?></legend>
                            <label>
                                <input type="checkbox" name="accuracy_confirmed" value="1" required>
                                <span><?php esc_html_e( 'I confirm that the information provided is accurate and consent to Essex Muslim Centre processing it for Gift Aid administration.', 'emc-theme' ); ?> *</span>
                            </label>
                        </fieldset>

                        <section class="gift-aid-declaration" aria-labelledby="gift-aid-consent-title">
                            <div class="gift-aid-declaration-title">
                                <span class="gift-aid-heart" aria-hidden="true"><i class="fas fa-heart"></i></span>
                                <div>
                                    <span><?php esc_html_e( 'Gift Aid Consent', 'emc-theme' ); ?></span>
                                    <h3 id="gift-aid-consent-title"><?php esc_html_e( 'Your taxpayer declaration', 'emc-theme' ); ?></h3>
                                </div>
                            </div>

                            <label class="gift-aid-taxpayer-check">
                                <input type="checkbox" name="taxpayer_confirmed" value="1" required>
                                <span>
                                    <?php esc_html_e( 'I want to Gift Aid my donations to Essex Muslim Centre made in the past 4 years and any donations I make in the future. I am a UK taxpayer and understand that if I pay less Income Tax and/or Capital Gains Tax than the amount of Gift Aid claimed on all my donations in that tax year, it is my responsibility to pay any difference.', 'emc-theme' ); ?> *
                                </span>
                            </label>

                            <p><strong><?php esc_html_e( 'Using Gift Aid increases the amount EMC receives by 25%, at no extra cost to you.', 'emc-theme' ); ?></strong></p>
                            <p>
                                <?php esc_html_e( 'Please tell us if you want to cancel this declaration, change your name or home address, or no longer pay sufficient UK tax.', 'emc-theme' ); ?>
                                <a href="https://www.gov.uk/claim-gift-aid/gift-aid-declarations" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Check Gift Aid eligibility', 'emc-theme' ); ?></a>.
                            </p>
                        </section>

                        <button type="submit" class="btn btn-primary gift-aid-submit">
                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                            <span><?php esc_html_e( 'Complete Declaration', 'emc-theme' ); ?></span>
                        </button>

                        <div class="gift-aid-status" role="status" aria-live="polite" tabindex="-1"></div>
                    </form>

                    <div class="gift-aid-success" id="gift-aid-success" hidden tabindex="-1">
                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                        <h2><?php esc_html_e( 'Declaration received', 'emc-theme' ); ?></h2>
                        <p><?php esc_html_e( 'Thank you. Your Gift Aid declaration has been securely recorded.', 'emc-theme' ); ?></p>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-outline"><?php esc_html_e( 'Return to Home', 'emc-theme' ); ?></a>
                    </div>
                </article>

                <aside class="gift-aid-info-card">
                    <span class="gift-aid-info-icon" aria-hidden="true"><i class="fas fa-sterling-sign"></i></span>
                    <h2><?php esc_html_e( 'How Gift Aid helps', 'emc-theme' ); ?></h2>
                    <p><?php esc_html_e( 'For every £1 you donate, Essex Muslim Centre can claim an additional 25p from HMRC.', 'emc-theme' ); ?></p>
                    <ul>
                        <li><i class="fas fa-check" aria-hidden="true"></i> <?php esc_html_e( 'No extra cost to you', 'emc-theme' ); ?></li>
                        <li><i class="fas fa-check" aria-hidden="true"></i> <?php esc_html_e( 'Covers past four years and future donations', 'emc-theme' ); ?></li>
                        <li><i class="fas fa-check" aria-hidden="true"></i> <?php esc_html_e( 'You can cancel your declaration at any time', 'emc-theme' ); ?></li>
                    </ul>
                    <p class="gift-aid-privacy">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <?php esc_html_e( 'Your information is stored securely and available only to authorised administrators.', 'emc-theme' ); ?>
                    </p>
                </aside>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
