<?php
/**
 * Template Part: Newsletter Sign-Up Section
 * @package emc-theme
 */
?>
<section class="homepage-newsletter" aria-labelledby="newsletter-heading">
    <div class="container">
        <div class="newsletter-inner">
            <div class="newsletter-text scroll-reveal">
                <i class="fas fa-envelope-open-text newsletter-icon" aria-hidden="true"></i>
                <div>
                    <h2 id="newsletter-heading"><?php esc_html_e( 'Stay in the Loop', 'emc-theme' ); ?></h2>
                    <p>
                        <?php esc_html_e( 'Get the latest news, events, and community updates delivered directly to your inbox. Join over 1,200 subscribers.', 'emc-theme' ); ?>
                    </p>
                </div>
            </div>

            <form
                class="newsletter-form scroll-reveal"
                id="newsletter-form"
                style="transition-delay:0.15s;"
                method="post"
                data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
                novalidate
                aria-label="<?php esc_attr_e( 'Newsletter sign-up', 'emc-theme' ); ?>"
            >
                <input type="hidden" name="action" value="emc_newsletter_subscribe">
                <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'emc_newsletter_signup' ) ); ?>">
                <div class="newsletter-honeypot" aria-hidden="true">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>
                <div class="newsletter-input-wrap">
                    <i class="fas fa-envelope" aria-hidden="true"></i>
                    <label for="nl-email" class="screen-reader-text"><?php esc_html_e( 'Email address', 'emc-theme' ); ?></label>
                    <input
                        type="email"
                        id="nl-email"
                        name="email"
                        placeholder="<?php esc_attr_e( 'Your email address', 'emc-theme' ); ?>"
                        required
                        autocomplete="email"
                    >
                </div>
                <button type="submit" class="btn btn-primary">
                    <span><?php esc_html_e( 'Subscribe', 'emc-theme' ); ?></span>
                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                </button>
                <label class="newsletter-consent">
                    <input type="checkbox" name="consent" value="1" required>
                    <span><?php esc_html_e( 'I agree to receive news, events, and community updates by email.', 'emc-theme' ); ?> *</span>
                </label>
                <p class="newsletter-disclaimer">
                    <i class="fas fa-lock" aria-hidden="true"></i>
                    <?php esc_html_e( 'No spam, ever. Unsubscribe at any time.', 'emc-theme' ); ?>
                </p>
                <div class="newsletter-success" id="nl-success" style="display:none;" role="status" aria-live="polite">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <span><?php esc_html_e( 'You\'re subscribed! Jazakallahu Khayran.', 'emc-theme' ); ?></span>
                </div>
                <div class="newsletter-error" id="nl-error" style="display:none;" role="alert" aria-live="assertive"></div>
            </form>
        </div>
    </div>
</section>
