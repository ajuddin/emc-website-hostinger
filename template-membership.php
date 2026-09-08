<?php
/**
 * Template Name: Membership
 * Template Post Type: page
 *
 * EMC Theme - Membership page template.
 *
 * Every string on this page is editable in Appearance → Customize → Page Content
 * → Membership Page. Fees are taken through the licensed EMC Payments Stripe
 * connection already used by donations and paid event registrations.
 *
 * @package emc-theme
 */

get_header();

$tiers           = emc_membership_tiers();
$available_tiers = emc_membership_available_tiers();
$stripe_ready    = emc_membership_stripe_is_available();
$payments_down   = count( $available_tiers ) < count( $tiers );
$contact_url     = get_permalink( get_page_by_path( 'contact' ) ) ?: home_url( '/contact/' );
$stripe_key      = $stripe_ready && function_exists( 'emc_stripe_pub_key' ) ? emc_stripe_pub_key() : '';
$first_tier      = $available_tiers ? reset( $available_tiers ) : null;
?>

<main class="membership-page">

    <section class="membership-hero" aria-labelledby="membership-page-title">
        <div class="container membership-hero-inner">
            <span class="membership-eyebrow">
                <i class="fas fa-id-card" aria-hidden="true"></i>
                <?php echo esc_html( emc_acf( 'mem_hero_badge', __( 'Join Our Community', 'emc-theme' ) ) ); ?>
            </span>
            <h1 id="membership-page-title"><?php echo esc_html( emc_acf( 'mem_hero_title', __( 'Become a Member', 'emc-theme' ) ) ); ?></h1>
            <p><?php echo esc_html( emc_acf( 'mem_hero_desc', __( 'Membership sustains the daily running of Essex Muslim Centre and gives you a voice in how your centre is run.', 'emc-theme' ) ) ); ?></p>
        </div>
    </section>

    <?php if ( ! $tiers ) : ?>

        <section class="membership-section">
            <div class="container">
                <div class="membership-notice" role="status">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    <?php esc_html_e( 'Membership categories have not been set up yet. Please contact the centre to join.', 'emc-theme' ); ?>
                    <?php if ( current_user_can( 'edit_theme_options' ) ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=emc_pg_membership' ) ); ?>"><?php esc_html_e( 'Set up membership categories', 'emc-theme' ); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

    <?php else : ?>

        <section class="membership-section membership-tiers-section" aria-labelledby="membership-tiers-title">
            <div class="container">
                <header class="membership-section-heading">
                    <span class="membership-subtitle"><?php echo esc_html( emc_acf( 'mem_tiers_subtitle', __( 'Choose Your Category', 'emc-theme' ) ) ); ?></span>
                    <h2 id="membership-tiers-title"><?php echo esc_html( emc_acf( 'mem_tiers_heading', __( 'Membership Categories', 'emc-theme' ) ) ); ?></h2>
                    <p><?php echo esc_html( emc_acf( 'mem_tiers_desc', __( 'Select the category that applies to you.', 'emc-theme' ) ) ); ?></p>
                </header>

                <div class="membership-tiers" data-membership-tiers>
                    <?php foreach ( $tiers as $tier ) : ?>
                        <article class="membership-tier<?php echo $tier['badge'] ? ' is-featured' : ''; ?>" data-tier-card="<?php echo esc_attr( $tier['key'] ); ?>">
                            <?php if ( $tier['badge'] ) : ?>
                                <span class="membership-tier-badge"><?php echo esc_html( $tier['badge'] ); ?></span>
                            <?php endif; ?>

                            <h3><?php echo esc_html( $tier['name'] ); ?></h3>

                            <p class="membership-tier-price">
                                <?php if ( $tier['paid'] ) : ?>
                                    <strong><?php echo esc_html( '£' . number_format( $tier['price'], 2 ) ); ?></strong>
                                <?php else : ?>
                                    <strong><?php esc_html_e( 'Free', 'emc-theme' ); ?></strong>
                                <?php endif; ?>
                                <?php if ( $tier['period'] ) : ?>
                                    <span><?php echo esc_html( $tier['period'] ); ?></span>
                                <?php endif; ?>
                            </p>

                            <?php if ( $tier['desc'] ) : ?>
                                <p class="membership-tier-desc"><?php echo esc_html( $tier['desc'] ); ?></p>
                            <?php endif; ?>

                            <?php if ( $tier['features'] ) : ?>
                                <ul class="membership-tier-features">
                                    <?php foreach ( $tier['features'] as $feature ) : ?>
                                        <li><i class="fas fa-check" aria-hidden="true"></i><?php echo esc_html( $feature ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <?php if ( isset( $available_tiers[ $tier['key'] ] ) ) : ?>
                                <button type="button" class="btn btn-outline membership-tier-select" data-select-tier="<?php echo esc_attr( $tier['key'] ); ?>">
                                    <?php
                                    /* translators: %s: membership category name. */
                                    echo esc_html( sprintf( __( 'Choose %s', 'emc-theme' ), $tier['name'] ) );
                                    ?>
                                </button>
                            <?php else : ?>
                                <a class="btn btn-outline membership-tier-select" href="<?php echo esc_url( $contact_url ); ?>">
                                    <?php esc_html_e( 'Contact the centre to join', 'emc-theme' ); ?>
                                </a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="membership-section membership-benefits-section" aria-labelledby="membership-benefits-title">
            <div class="container">
                <header class="membership-section-heading">
                    <h2 id="membership-benefits-title"><?php echo esc_html( emc_acf( 'mem_benefits_heading', __( 'What Membership Gives You', 'emc-theme' ) ) ); ?></h2>
                </header>
                <div class="membership-benefits">
                    <?php
                    $benefit_defaults = array(
                        1 => array( 'fas fa-vote-yea', __( 'A Voice', 'emc-theme' ), __( 'Vote at the Annual General Meeting.', 'emc-theme' ) ),
                        2 => array( 'fas fa-hands-helping', __( 'Sustaining Support', 'emc-theme' ), __( 'Your fee funds prayers, education, and welfare.', 'emc-theme' ) ),
                        3 => array( 'fas fa-envelope-open-text', __( 'Stay Informed', 'emc-theme' ), __( 'Receive member updates and meeting notices.', 'emc-theme' ) ),
                        4 => array( 'fas fa-users', __( 'Community', 'emc-theme' ), __( 'Join a growing body of members.', 'emc-theme' ) ),
                    );
                    foreach ( $benefit_defaults as $i => $benefit ) :
                        $title = emc_acf( 'mem_benefit_' . $i . '_title', $benefit[1] );
                        if ( '' === trim( (string) $title ) ) {
                            continue;
                        }
                        ?>
                        <div class="membership-benefit">
                            <span class="membership-benefit-icon" aria-hidden="true"><i class="<?php echo esc_attr( emc_acf( 'mem_benefit_' . $i . '_icon', $benefit[0] ) ); ?>"></i></span>
                            <h3><?php echo esc_html( $title ); ?></h3>
                            <p><?php echo esc_html( emc_acf( 'mem_benefit_' . $i . '_desc', $benefit[2] ) ); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="membership-section membership-form-section" id="membership-application" aria-labelledby="membership-form-title">
            <div class="container">
                <article class="membership-form-card">
                    <header class="membership-form-header">
                        <h2 id="membership-form-title"><?php echo esc_html( emc_acf( 'mem_form_heading', __( 'Membership Application', 'emc-theme' ) ) ); ?></h2>
                        <p><?php echo esc_html( emc_acf( 'mem_form_desc', __( 'Complete your details below and pay your membership fee securely by card.', 'emc-theme' ) ) ); ?></p>
                    </header>

                    <?php if ( $payments_down ) : ?>
                        <div class="membership-notice is-warning" role="status">
                            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                            <?php esc_html_e( 'Card payment is temporarily unavailable, so paid categories cannot be completed online right now.', 'emc-theme' ); ?>
                            <a href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contact the centre to join', 'emc-theme' ); ?></a>
                        </div>
                    <?php endif; ?>

                    <?php if ( $available_tiers ) : ?>

                    <form
                        id="membership-form"
                        class="membership-form"
                        method="post"
                        novalidate
                        data-stripe-key="<?php echo esc_attr( $stripe_key ); ?>"
                        data-tiers="<?php echo esc_attr( wp_json_encode( array_map( static function ( $tier ) {
                            return array( 'key' => $tier['key'], 'name' => $tier['name'], 'pence' => $tier['pence'], 'paid' => $tier['paid'] );
                        }, array_values( $available_tiers ) ) ) ); ?>"
                    >
                        <input type="hidden" name="action" value="emc_membership_join">
                        <div class="membership-honeypot" aria-hidden="true">
                            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <div class="membership-field membership-field-wide">
                            <label for="membership-tier"><?php esc_html_e( 'Membership category *', 'emc-theme' ); ?></label>
                            <select id="membership-tier" name="tier" required>
                                <?php foreach ( $available_tiers as $tier ) : ?>
                                    <option value="<?php echo esc_attr( $tier['key'] ); ?>" <?php selected( $first_tier && $tier['key'] === $first_tier['key'] ); ?>>
                                        <?php
                                        echo esc_html( $tier['paid']
                                            ? sprintf( '%s — £%s', $tier['name'], number_format( $tier['price'], 2 ) )
                                            : sprintf( '%s — %s', $tier['name'], __( 'Free', 'emc-theme' ) )
                                        );
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="membership-grid">
                            <div class="membership-field">
                                <label for="membership-first-name"><?php esc_html_e( 'First name *', 'emc-theme' ); ?></label>
                                <input type="text" id="membership-first-name" name="first_name" autocomplete="given-name" required>
                            </div>
                            <div class="membership-field">
                                <label for="membership-last-name"><?php esc_html_e( 'Last name *', 'emc-theme' ); ?></label>
                                <input type="text" id="membership-last-name" name="last_name" autocomplete="family-name" required>
                            </div>
                            <div class="membership-field">
                                <label for="membership-email"><?php esc_html_e( 'Email address *', 'emc-theme' ); ?></label>
                                <input type="email" id="membership-email" name="email" autocomplete="email" required>
                            </div>
                            <div class="membership-field">
                                <label for="membership-phone"><?php esc_html_e( 'Phone number', 'emc-theme' ); ?></label>
                                <input type="tel" id="membership-phone" name="phone" autocomplete="tel">
                            </div>
                            <div class="membership-field membership-field-wide">
                                <label for="membership-address-1"><?php esc_html_e( 'Address line 1 *', 'emc-theme' ); ?></label>
                                <input type="text" id="membership-address-1" name="address_1" autocomplete="address-line1" required>
                            </div>
                            <div class="membership-field membership-field-wide">
                                <label for="membership-address-2"><?php esc_html_e( 'Address line 2', 'emc-theme' ); ?></label>
                                <input type="text" id="membership-address-2" name="address_2" autocomplete="address-line2">
                            </div>
                            <div class="membership-field">
                                <label for="membership-city"><?php esc_html_e( 'Town or city', 'emc-theme' ); ?></label>
                                <input type="text" id="membership-city" name="city" autocomplete="address-level2">
                            </div>
                            <div class="membership-field">
                                <label for="membership-postcode"><?php esc_html_e( 'Postcode *', 'emc-theme' ); ?></label>
                                <input type="text" id="membership-postcode" name="postcode" autocomplete="postal-code" required>
                            </div>
                            <div class="membership-field membership-field-wide">
                                <label for="membership-notes"><?php esc_html_e( 'Anything else we should know?', 'emc-theme' ); ?></label>
                                <textarea id="membership-notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="membership-checkboxes">
                            <label class="membership-checkbox" for="membership-gift-aid">
                                <input type="checkbox" id="membership-gift-aid" name="gift_aid" value="1">
                                <span><?php echo esc_html( emc_acf( 'mem_giftaid_text', __( 'I am a UK taxpayer and Essex Muslim Centre may treat eligible payments as Gift Aid donations.', 'emc-theme' ) ) ); ?></span>
                            </label>
                            <label class="membership-checkbox" for="membership-consent">
                                <input type="checkbox" id="membership-consent" name="consent" value="1" required>
                                <span><?php echo esc_html( emc_acf( 'mem_consent_text', __( 'I agree that Essex Muslim Centre may hold these details to administer my membership.', 'emc-theme' ) ) ); ?></span>
                            </label>
                        </div>

                        <div class="membership-payment" data-membership-payment hidden>
                            <div class="membership-total">
                                <span><?php esc_html_e( 'Membership fee', 'emc-theme' ); ?></span>
                                <strong data-membership-total>&mdash;</strong>
                            </div>
                            <label for="membership-card"><?php esc_html_e( 'Card details', 'emc-theme' ); ?></label>
                            <div id="membership-card" class="membership-card-element"></div>
                            <p class="membership-card-errors" role="alert"></p>
                        </div>

                        <button type="submit" class="btn btn-primary membership-submit">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                            <span><?php echo esc_html( emc_acf( 'mem_form_button', __( 'Join & Pay Securely', 'emc-theme' ) ) ); ?></span>
                        </button>

                        <p class="membership-secure-note">
                            <i class="fas fa-shield-halved" aria-hidden="true"></i>
                            <?php echo esc_html( emc_acf( 'mem_secure_note', __( 'Encrypted and secured by Stripe. Your card details are never stored on our website.', 'emc-theme' ) ) ); ?>
                        </p>

                        <p class="membership-form-status" role="status" tabindex="-1"></p>
                    </form>

                    <?php endif; ?>

                    <footer class="membership-form-footer">
                        <p><?php echo esc_html( emc_acf( 'mem_terms_text', __( 'Membership is subject to the constitution of Essex Muslim Centre.', 'emc-theme' ) ) ); ?></p>
                        <p><?php echo esc_html( emc_acf( 'mem_contact_note', __( 'Prefer to join in person? Contact the centre office.', 'emc-theme' ) ) ); ?></p>
                    </footer>
                </article>
            </div>
        </section>

    <?php endif; ?>

</main>

<?php get_footer(); ?>
