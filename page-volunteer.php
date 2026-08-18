<?php
/**
 * Template Name: Job Application
 * Template Post Type: page
 *
 * @package emc-theme
 */

get_header();
?>

<main class="volunteer-page">
    <section class="volunteer-hero" aria-labelledby="volunteer-page-title">
        <div class="container volunteer-hero-inner">
            <span class="volunteer-eyebrow"><i class="fas fa-briefcase" aria-hidden="true"></i> <?php esc_html_e( 'Careers at Essex Muslim Centre', 'emc-theme' ); ?></span>
            <h1 id="volunteer-page-title"><?php esc_html_e( 'Job Application', 'emc-theme' ); ?></h1>
            <p><?php esc_html_e( 'Apply to join our team and help Essex Muslim Centre serve worshippers, families, young people, and the wider community.', 'emc-theme' ); ?></p>
        </div>
    </section>

    <section class="volunteer-section" id="volunteer-registration">
        <div class="container">
            <div class="volunteer-layout">
                <article class="volunteer-form-card">
                    <header class="volunteer-form-header">
                        <span class="volunteer-form-icon" aria-hidden="true"><i class="fas fa-user-tie"></i></span>
                        <div>
                            <span><?php esc_html_e( 'Employment Application', 'emc-theme' ); ?></span>
                            <h2><?php esc_html_e( 'Apply to join our team', 'emc-theme' ); ?></h2>
                            <p><?php esc_html_e( 'Complete the form and upload your CV. Our team will contact shortlisted applicants.', 'emc-theme' ); ?></p>
                        </div>
                    </header>

                    <form id="volunteer-form" class="volunteer-form" method="post" enctype="multipart/form-data" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" novalidate>
                        <input type="hidden" name="action" value="emc_job_application">
                        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'emc_nonce' ) ); ?>">
                        <div class="volunteer-honeypot" aria-hidden="true">
                            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <fieldset class="volunteer-form-section">
                            <legend><span>1</span> <?php esc_html_e( 'Your details', 'emc-theme' ); ?></legend>
                            <div class="volunteer-grid">
                                <div class="volunteer-field">
                                    <label for="volunteer-first-name"><?php esc_html_e( 'First Name', 'emc-theme' ); ?> *</label>
                                    <input type="text" id="volunteer-first-name" name="first_name" autocomplete="given-name" required>
                                </div>
                                <div class="volunteer-field">
                                    <label for="volunteer-last-name"><?php esc_html_e( 'Last Name', 'emc-theme' ); ?> *</label>
                                    <input type="text" id="volunteer-last-name" name="last_name" autocomplete="family-name" required>
                                </div>
                                <div class="volunteer-field">
                                    <label for="volunteer-email"><?php esc_html_e( 'Email Address', 'emc-theme' ); ?> *</label>
                                    <input type="email" id="volunteer-email" name="email" autocomplete="email" required>
                                </div>
                                <div class="volunteer-field">
                                    <label for="volunteer-phone"><?php esc_html_e( 'Phone Number', 'emc-theme' ); ?> *</label>
                                    <input type="tel" id="volunteer-phone" name="phone" autocomplete="tel" required>
                                </div>
                                <div class="volunteer-field">
                                    <label for="volunteer-postcode"><?php esc_html_e( 'Postcode', 'emc-theme' ); ?> *</label>
                                    <input type="text" id="volunteer-postcode" name="postcode" autocomplete="postal-code" maxlength="8" required>
                                </div>
                                <div class="volunteer-field">
                                    <label for="volunteer-age"><?php esc_html_e( 'Are you aged 18 or over?', 'emc-theme' ); ?> *</label>
                                    <select id="volunteer-age" name="over_18" required>
                                        <option value=""><?php esc_html_e( 'Select an option', 'emc-theme' ); ?></option>
                                        <option value="yes"><?php esc_html_e( 'Yes', 'emc-theme' ); ?></option>
                                        <option value="no"><?php esc_html_e( 'No', 'emc-theme' ); ?></option>
                                    </select>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="volunteer-form-section">
                            <legend><span>2</span> <?php esc_html_e( 'Role and availability', 'emc-theme' ); ?></legend>
                            <div class="volunteer-field">
                                <label for="job-position"><?php esc_html_e( 'Position applied for', 'emc-theme' ); ?> *</label>
                                <select id="job-position" name="position" required>
                                    <option value=""><?php esc_html_e( 'Select a position', 'emc-theme' ); ?></option>
                                    <?php $requested_position = sanitize_text_field( wp_unslash( $_GET['position'] ?? '' ) ); ?>
                                    <?php foreach ( get_posts( array( 'post_type' => 'emc_vacancy', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $vacancy ) : ?>
                                        <option value="<?php echo esc_attr( $vacancy->post_title ); ?>" <?php selected( $requested_position, $vacancy->post_title ); ?>><?php echo esc_html( $vacancy->post_title ); ?></option>
                                    <?php endforeach; ?>
                                    <option value="General application" <?php selected( $requested_position, 'General application' ); ?>><?php esc_html_e( 'General application', 'emc-theme' ); ?></option>
                                </select>
                            </div>
                            <div class="volunteer-field">
                                <label for="job-availability"><?php esc_html_e( 'Notice period / earliest available start date', 'emc-theme' ); ?></label>
                                <input type="text" id="job-availability" name="availability_details">
                            </div>
                        </fieldset>

                        <fieldset class="volunteer-form-section">
                            <legend><span>3</span> <?php esc_html_e( 'Your application', 'emc-theme' ); ?></legend>
                            <div class="volunteer-field">
                                <label for="job-cv"><?php esc_html_e( 'Upload your CV', 'emc-theme' ); ?> *</label>
                                <input type="file" id="job-cv" name="cv" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                                <small class="volunteer-section-help"><?php esc_html_e( 'PDF, DOC or DOCX, up to 5 MB.', 'emc-theme' ); ?></small>
                            </div>
                            <div class="volunteer-field">
                                <label for="volunteer-skills"><?php esc_html_e( 'Relevant skills or experience', 'emc-theme' ); ?></label>
                                <textarea id="volunteer-skills" name="skills" rows="4" placeholder="<?php esc_attr_e( 'Tell us about relevant skills, qualifications, languages or previous employment.', 'emc-theme' ); ?>"></textarea>
                            </div>
                            <div class="volunteer-field">
                                <label for="volunteer-motivation"><?php esc_html_e( 'Supporting statement', 'emc-theme' ); ?> *</label>
                                <textarea id="volunteer-motivation" name="motivation" rows="5" required></textarea>
                            </div>
                        </fieldset>

                        <?php $extra_fields = function_exists( 'emc_job_application_fields' ) ? emc_job_application_fields() : array(); ?>
                        <?php if ( $extra_fields ) : ?>
                        <fieldset class="volunteer-form-section">
                            <legend><span>4</span> <?php esc_html_e( 'Additional information', 'emc-theme' ); ?></legend>
                            <?php foreach ( $extra_fields as $field ) { emc_render_job_application_field( $field ); } ?>
                        </fieldset>
                        <?php endif; ?>

                        <fieldset class="volunteer-form-section volunteer-confirmations">
                            <legend><span><?php echo $extra_fields ? '5' : '4'; ?></span> <?php esc_html_e( 'Confirmations', 'emc-theme' ); ?></legend>
                            <label>
                                <input type="checkbox" name="checks_consent" value="1" required>
                                <span><?php esc_html_e( 'I understand that employment may be subject to references, right-to-work checks, safeguarding checks or a DBS check.', 'emc-theme' ); ?> *</span>
                            </label>
                            <label>
                                <input type="checkbox" name="privacy_consent" value="1" required>
                                <span><?php esc_html_e( 'I consent to Essex Muslim Centre storing and using this information to manage my job application.', 'emc-theme' ); ?> *</span>
                            </label>
                        </fieldset>

                        <button type="submit" class="btn btn-primary volunteer-submit">
                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            <span><?php esc_html_e( 'Submit Job Application', 'emc-theme' ); ?></span>
                        </button>
                        <div class="volunteer-status" role="status" aria-live="polite" tabindex="-1"></div>
                    </form>

                    <div class="volunteer-success" id="volunteer-success" hidden tabindex="-1">
                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                        <h2><?php esc_html_e( 'Application received', 'emc-theme' ); ?></h2>
                        <p><?php esc_html_e( 'Thank you for applying. Our team will review your application and contact you if you are shortlisted.', 'emc-theme' ); ?></p>
                        <a href="<?php echo esc_url( get_post_type_archive_link( 'emc_vacancy' ) ); ?>" class="btn btn-outline"><?php esc_html_e( 'View Current Opportunities', 'emc-theme' ); ?></a>
                    </div>
                </article>

                <aside class="volunteer-info">
                    <div class="volunteer-info-card">
                        <i class="fas fa-users" aria-hidden="true"></i>
                        <h2><?php esc_html_e( 'What happens next?', 'emc-theme' ); ?></h2>
                        <ol>
                            <li><span>1</span><?php esc_html_e( 'We review your application and CV.', 'emc-theme' ); ?></li>
                            <li><span>2</span><?php esc_html_e( 'Shortlisted applicants are contacted for the next stage.', 'emc-theme' ); ?></li>
                            <li><span>3</span><?php esc_html_e( 'Pre-employment checks are completed before appointment.', 'emc-theme' ); ?></li>
                        </ol>
                    </div>
                    <div class="volunteer-info-note">
                        <i class="fas fa-shield-alt" aria-hidden="true"></i>
                        <p><?php esc_html_e( 'Submitting an application does not guarantee an interview or employment. Appointment is subject to suitability and all required checks.', 'emc-theme' ); ?></p>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
