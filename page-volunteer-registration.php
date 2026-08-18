<?php
/**
 * Template Name: Volunteer Application
 * Template Post Type: page
 *
 * @package emc-theme
 */

get_header();
?>
<main class="volunteer-page">
    <section class="volunteer-hero" aria-labelledby="volunteer-page-title">
        <div class="container volunteer-hero-inner">
            <span class="volunteer-eyebrow"><i class="fas fa-hands-helping" aria-hidden="true"></i> <?php esc_html_e( 'Make a difference locally', 'emc-theme' ); ?></span>
            <h1 id="volunteer-page-title"><?php esc_html_e( 'Volunteer With Us', 'emc-theme' ); ?></h1>
            <p><?php esc_html_e( 'Share your time and skills to help Essex Muslim Centre support worshippers, families, young people, and the wider community.', 'emc-theme' ); ?></p>
        </div>
    </section>

    <section class="volunteer-section" id="volunteer-registration">
        <div class="container"><div class="volunteer-layout">
            <article class="volunteer-form-card">
                <header class="volunteer-form-header">
                    <span class="volunteer-form-icon" aria-hidden="true"><i class="fas fa-handshake"></i></span>
                    <div><span><?php esc_html_e( 'Volunteer Registration', 'emc-theme' ); ?></span><h2><?php esc_html_e( 'Tell us how you would like to help', 'emc-theme' ); ?></h2><p><?php esc_html_e( 'Complete this form and our team will contact you when a suitable opportunity is available.', 'emc-theme' ); ?></p></div>
                </header>

                <form id="volunteer-form" class="volunteer-form" method="post" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" novalidate>
                    <input type="hidden" name="action" value="emc_volunteer_signup"><input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'emc_nonce' ) ); ?>">
                    <div class="volunteer-honeypot" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                    <fieldset class="volunteer-form-section"><legend><span>1</span> <?php esc_html_e( 'Your details', 'emc-theme' ); ?></legend><div class="volunteer-grid">
                        <div class="volunteer-field"><label for="volunteer-first-name"><?php esc_html_e( 'First Name', 'emc-theme' ); ?> *</label><input type="text" id="volunteer-first-name" name="first_name" autocomplete="given-name" required></div>
                        <div class="volunteer-field"><label for="volunteer-last-name"><?php esc_html_e( 'Last Name', 'emc-theme' ); ?> *</label><input type="text" id="volunteer-last-name" name="last_name" autocomplete="family-name" required></div>
                        <div class="volunteer-field"><label for="volunteer-email"><?php esc_html_e( 'Email Address', 'emc-theme' ); ?> *</label><input type="email" id="volunteer-email" name="email" autocomplete="email" required></div>
                        <div class="volunteer-field"><label for="volunteer-phone"><?php esc_html_e( 'Phone Number', 'emc-theme' ); ?> *</label><input type="tel" id="volunteer-phone" name="phone" autocomplete="tel" required></div>
                        <div class="volunteer-field"><label for="volunteer-postcode"><?php esc_html_e( 'Postcode', 'emc-theme' ); ?> *</label><input type="text" id="volunteer-postcode" name="postcode" autocomplete="postal-code" maxlength="8" required></div>
                        <div class="volunteer-field"><label for="volunteer-age"><?php esc_html_e( 'Are you aged 18 or over?', 'emc-theme' ); ?> *</label><select id="volunteer-age" name="over_18" required><option value=""><?php esc_html_e( 'Select an option', 'emc-theme' ); ?></option><option value="yes"><?php esc_html_e( 'Yes', 'emc-theme' ); ?></option><option value="no"><?php esc_html_e( 'No', 'emc-theme' ); ?></option></select></div>
                    </div></fieldset>

                    <fieldset class="volunteer-form-section volunteer-choice-group" data-group="interests"><legend><span>2</span> <?php esc_html_e( 'How would you like to help?', 'emc-theme' ); ?></legend>
                    <?php $requested_role = sanitize_text_field( wp_unslash( $_GET['role'] ?? '' ) ); ?>
                    <div class="volunteer-field"><label for="volunteer-role"><?php esc_html_e( 'Volunteer role', 'emc-theme' ); ?> *</label><select id="volunteer-role" name="role" required><option value=""><?php esc_html_e( 'Select a volunteer role', 'emc-theme' ); ?></option><?php foreach ( get_posts( array( 'post_type' => 'emc_volunteer_role', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) ) as $role ) : ?><option value="<?php echo esc_attr( $role->post_title ); ?>" <?php selected( $requested_role, $role->post_title ); ?>><?php echo esc_html( $role->post_title ); ?></option><?php endforeach; ?><option value="General volunteering" <?php selected( $requested_role, 'General volunteering' ); ?>><?php esc_html_e( 'General volunteering', 'emc-theme' ); ?></option></select></div>
                    <p class="volunteer-section-help"><?php esc_html_e( 'Select one or more areas.', 'emc-theme' ); ?></p><div class="volunteer-choice-grid">
                        <label><input type="checkbox" name="interests[]" value="events"><span><i class="fas fa-calendar-alt"></i> <?php esc_html_e( 'Events', 'emc-theme' ); ?></span></label>
                        <label><input type="checkbox" name="interests[]" value="education"><span><i class="fas fa-book-open"></i> <?php esc_html_e( 'Education', 'emc-theme' ); ?></span></label>
                        <label><input type="checkbox" name="interests[]" value="welfare"><span><i class="fas fa-hand-holding-heart"></i> <?php esc_html_e( 'Welfare', 'emc-theme' ); ?></span></label>
                        <label><input type="checkbox" name="interests[]" value="fundraising"><span><i class="fas fa-sterling-sign"></i> <?php esc_html_e( 'Fundraising', 'emc-theme' ); ?></span></label>
                        <label><input type="checkbox" name="interests[]" value="admin"><span><i class="fas fa-clipboard"></i> <?php esc_html_e( 'Admin / Reception', 'emc-theme' ); ?></span></label>
                        <label><input type="checkbox" name="interests[]" value="facilities"><span><i class="fas fa-tools"></i> <?php esc_html_e( 'Facilities', 'emc-theme' ); ?></span></label>
                        <label><input type="checkbox" name="interests[]" value="media"><span><i class="fas fa-camera"></i> <?php esc_html_e( 'Media / Communications', 'emc-theme' ); ?></span></label>
                        <label><input type="checkbox" name="interests[]" value="other" id="volunteer-interest-other-toggle"><span><i class="fas fa-ellipsis-h"></i> <?php esc_html_e( 'Other', 'emc-theme' ); ?></span></label>
                    </div><div class="volunteer-field" id="volunteer-interest-other-field" hidden><label for="volunteer-interest-other"><?php esc_html_e( 'Describe your interest', 'emc-theme' ); ?> *</label><input type="text" id="volunteer-interest-other" name="interest_other"></div><div class="volunteer-group-error" role="alert"></div></fieldset>

                    <fieldset class="volunteer-form-section volunteer-choice-group" data-group="availability"><legend><span>3</span> <?php esc_html_e( 'When are you usually available?', 'emc-theme' ); ?></legend><p class="volunteer-section-help"><?php esc_html_e( 'Select one or more options.', 'emc-theme' ); ?></p><div class="volunteer-choice-grid">
                        <label><input type="checkbox" name="availability[]" value="weekday_day"><span><?php esc_html_e( 'Weekday daytime', 'emc-theme' ); ?></span></label><label><input type="checkbox" name="availability[]" value="weekday_evening"><span><?php esc_html_e( 'Weekday evenings', 'emc-theme' ); ?></span></label><label><input type="checkbox" name="availability[]" value="saturday"><span><?php esc_html_e( 'Saturdays', 'emc-theme' ); ?></span></label><label><input type="checkbox" name="availability[]" value="sunday"><span><?php esc_html_e( 'Sundays', 'emc-theme' ); ?></span></label><label><input type="checkbox" name="availability[]" value="occasional"><span><?php esc_html_e( 'Occasional events', 'emc-theme' ); ?></span></label>
                    </div><div class="volunteer-field"><label for="volunteer-availability-details"><?php esc_html_e( 'Additional availability details', 'emc-theme' ); ?></label><textarea id="volunteer-availability-details" name="availability_details" rows="3"></textarea></div><div class="volunteer-group-error" role="alert"></div></fieldset>

                    <fieldset class="volunteer-form-section"><legend><span>4</span> <?php esc_html_e( 'About you', 'emc-theme' ); ?></legend><div class="volunteer-field"><label for="volunteer-skills"><?php esc_html_e( 'Relevant skills or experience', 'emc-theme' ); ?></label><textarea id="volunteer-skills" name="skills" rows="4" placeholder="<?php esc_attr_e( 'Tell us about any skills, qualifications, languages or previous volunteering experience.', 'emc-theme' ); ?>"></textarea></div><div class="volunteer-field"><label for="volunteer-motivation"><?php esc_html_e( 'Why would you like to volunteer with EMC?', 'emc-theme' ); ?> *</label><textarea id="volunteer-motivation" name="motivation" rows="5" required></textarea></div></fieldset>

                    <fieldset class="volunteer-form-section volunteer-confirmations"><legend><span>5</span> <?php esc_html_e( 'Confirmations', 'emc-theme' ); ?></legend><label><input type="checkbox" name="checks_consent" value="1" required><span><?php esc_html_e( 'I understand that some roles may require references, safeguarding checks or a DBS check.', 'emc-theme' ); ?> *</span></label><label><input type="checkbox" name="privacy_consent" value="1" required><span><?php esc_html_e( 'I consent to Essex Muslim Centre storing and using this information to manage my volunteer application.', 'emc-theme' ); ?> *</span></label></fieldset>
                    <button type="submit" class="btn btn-primary volunteer-submit"><i class="fas fa-paper-plane" aria-hidden="true"></i><span><?php esc_html_e( 'Submit Volunteer Application', 'emc-theme' ); ?></span></button><div class="volunteer-status" role="status" aria-live="polite" tabindex="-1"></div>
                </form>
                <div class="volunteer-success" id="volunteer-success" hidden tabindex="-1"><i class="fas fa-check-circle" aria-hidden="true"></i><h2><?php esc_html_e( 'Application received', 'emc-theme' ); ?></h2><p><?php esc_html_e( 'Thank you for offering your time. Our team will review your application and contact you when a suitable opportunity is available.', 'emc-theme' ); ?></p><a href="<?php echo esc_url( get_post_type_archive_link( 'emc_volunteer_role' ) ); ?>" class="btn btn-outline"><?php esc_html_e( 'View Volunteer Opportunities', 'emc-theme' ); ?></a></div>
            </article>
            <aside class="volunteer-info"><div class="volunteer-info-card"><i class="fas fa-users" aria-hidden="true"></i><h2><?php esc_html_e( 'What happens next?', 'emc-theme' ); ?></h2><ol><li><span>1</span><?php esc_html_e( 'We review your interests and availability.', 'emc-theme' ); ?></li><li><span>2</span><?php esc_html_e( 'A team member contacts you about suitable roles.', 'emc-theme' ); ?></li><li><span>3</span><?php esc_html_e( 'Relevant induction and checks are completed.', 'emc-theme' ); ?></li></ol></div><div class="volunteer-info-note"><i class="fas fa-shield-alt" aria-hidden="true"></i><p><?php esc_html_e( 'Submitting this form does not guarantee placement. Opportunities depend on current needs, suitability, safeguarding, and available supervision.', 'emc-theme' ); ?></p></div></aside>
        </div></div>
    </section>
</main>
<?php get_footer(); ?>
