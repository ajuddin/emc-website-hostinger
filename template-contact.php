<?php
/**
 * Template Name: Contact
 * Template Post Type: page
 *
 * EMC Theme — Contact page template.
 * All content editable via ACF fields.
 *
 * @package emc-theme
 */

get_header();

wp_enqueue_style( 'emc-page-contact', EMC_ASSETS . '/css/contact.css', array( 'emc-style' ), EMC_VERSION );

$contact_js_path = EMC_DIR . '/assets/js/contact.js';
if ( file_exists( $contact_js_path ) ) {
    wp_enqueue_script( 'emc-page-contact', EMC_ASSETS . '/js/contact.js', array( 'emc-script' ), filemtime( $contact_js_path ), true );
}
?>

<!-- Page Hero -->
<section class="page-hero" style="background: linear-gradient(135deg, var(--deep-blue) 0%, #1A2B4C 100%);">
    <div class="container">
        <div class="page-hero-content">
            <span class="badge" style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.2);color:var(--white);"><i class="fas fa-envelope"></i> <?php echo esc_html( emc_acf( 'contact_hero_badge', __( 'Get in Touch', 'emc-theme' ) ) ); ?></span>
            <h1><?php echo esc_html( emc_acf( 'contact_hero_title', __( 'Contact Us', 'emc-theme' ) ) ); ?></h1>
            <p><?php echo esc_html( emc_acf( 'contact_hero_desc', __( 'We\'re here to help. Whether you have a question about our services, want to volunteer, or need support, our team is ready to assist you.', 'emc-theme' ) ) ); ?></p>
        </div>
    </div>
</section>

<section class="contact-section section-padding">
    <div class="container">
        <div class="contact-layout">

            <!-- Contact Form -->
            <div class="contact-form-card glass-card scroll-reveal">
                <h2><?php echo esc_html( emc_acf( 'contact_form_heading', __( 'Send a Message', 'emc-theme' ) ) ); ?></h2>
                <p style="color:var(--text-muted); font-size:var(--step--1); margin-bottom:2rem;"><?php echo esc_html( emc_acf( 'contact_form_desc', __( 'Fill out the form below and a member of our team will get back to you within 48 hours.', 'emc-theme' ) ) ); ?></p>

                <form id="contact-form" novalidate>
                    <?php wp_nonce_field( 'emc_contact_form', 'emc_contact_nonce' ); ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName"><?php echo esc_html( emc_acf( 'contact_label_firstname', 'First Name *' ) ); ?></label>
                            <input type="text" id="firstName" name="first_name" class="form-control" placeholder="<?php echo esc_attr( emc_acf( 'contact_placeholder_firstname', 'Jane' ) ); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName"><?php echo esc_html( emc_acf( 'contact_label_lastname', 'Last Name *' ) ); ?></label>
                            <input type="text" id="lastName" name="last_name" class="form-control" placeholder="<?php echo esc_attr( emc_acf( 'contact_placeholder_lastname', 'Doe' ) ); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email"><?php echo esc_html( emc_acf( 'contact_label_email', 'Email Address *' ) ); ?></label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="<?php echo esc_attr( emc_acf( 'contact_placeholder_email', 'jane@example.com' ) ); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="subject"><?php echo esc_html( emc_acf( 'contact_label_subject', 'Subject *' ) ); ?></label>
                        <select id="subject" name="subject" class="form-control" required>
                            <option value="" disabled selected><?php echo esc_html( emc_acf( 'contact_select_placeholder', 'Select an enquiry type' ) ); ?></option>
                            <option value="general"><?php echo esc_html( emc_acf( 'contact_select_opt1', 'General Enquiry' ) ); ?></option>
                            <option value="services"><?php echo esc_html( emc_acf( 'contact_select_opt2', 'Services & Programmes' ) ); ?></option>
                            <option value="donations"><?php echo esc_html( emc_acf( 'contact_select_opt3', 'Donations & Finance' ) ); ?></option>
                            <option value="reversion"><?php echo esc_html( emc_acf( 'contact_select_opt4', 'Reversion to Islam' ) ); ?></option>
                            <option value="feedback"><?php echo esc_html( emc_acf( 'contact_select_opt5', 'Feedback / Complaint' ) ); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message"><?php echo esc_html( emc_acf( 'contact_label_message', 'Message *' ) ); ?></label>
                        <textarea id="message" name="message" class="form-control" rows="5" placeholder="<?php echo esc_attr( emc_acf( 'contact_placeholder_message', 'How can we help you today?' ) ); ?>" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 1rem;">
                        <i class="fas fa-paper-plane"></i> <?php echo esc_html( emc_acf( 'contact_submit_btn', 'Send Message' ) ); ?>
                    </button>

                    <div id="contact-success" class="contact-success" style="display:none;">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong><?php echo esc_html( emc_acf( 'contact_success_title', __( 'Message Sent Successfully', 'emc-theme' ) ) ); ?></strong>
                            <p><?php echo esc_html( emc_acf( 'contact_success_desc', __( 'Thank you for reaching out. We will get back to you shortly.', 'emc-theme' ) ) ); ?></p>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Contact Info & Map -->
            <div class="contact-info-col scroll-reveal" style="transition-delay: 0.15s;">

                <div class="info-blocks">
                    <div class="info-block">
                        <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <h3><?php echo esc_html( emc_acf( 'contact_visit_heading', __( 'Visit Us', 'emc-theme' ) ) ); ?></h3>
                            <?php
                            $custom_address = emc_acf( 'contact_visit_address', '' );
                            if ( $custom_address ) :
                                echo '<p>' . nl2br( esc_html( $custom_address ) ) . '</p>';
                            else :
                            ?>
                            <p>
                                <?php echo esc_html( get_bloginfo( 'name' ) ); ?><br>
                                <?php echo wp_kses( emc_get_address(), array( 'br' => array() ) ); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-block">
                        <div class="info-icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <h3><?php echo esc_html( emc_acf( 'contact_email_heading', __( 'Email Us', 'emc-theme' ) ) ); ?></h3>
                            <?php $admin_email = emc_option( 'emc_admin_email', 'admin@essexmuslimcentre.org' ); ?>
                            <p><a href="mailto:<?php echo esc_attr( $admin_email ); ?>"><?php echo esc_html( $admin_email ); ?></a></p>
                            <small><?php echo esc_html( emc_acf( 'contact_email_hours', __( 'Monitored Monday–Friday, 9am–5pm', 'emc-theme' ) ) ); ?></small>
                        </div>
                    </div>

                    <div class="info-block">
                        <div class="info-icon"><i class="fas fa-clock"></i></div>
                        <div>
                            <h3><?php echo esc_html( emc_acf( 'contact_hours_heading', 'Opening Hours' ) ); ?></h3>
                            <?php
                            $custom_hours = emc_acf( 'contact_opening_hours', '' );
                            if ( $custom_hours ) :
                                echo '<p>' . nl2br( esc_html( $custom_hours ) ) . '</p>';
                            else :
                            ?>
                            <p>
                                <strong><?php echo esc_html( emc_acf( 'contact_hours_centre_label', 'Centre:' ) ); ?></strong> <?php echo esc_html( emc_acf( 'contact_hours_centre_val', 'Open daily for all 5 prayers' ) ); ?><br>
                                <strong><?php echo esc_html( emc_acf( 'contact_hours_office_label', 'Office:' ) ); ?></strong> <?php echo esc_html( emc_acf( 'contact_hours_office_val', 'Mon-Fri, 10am - 4pm' ) ); ?><br>
                                <strong><?php echo esc_html( emc_acf( 'contact_hours_jumuah_label', "Jumu'ah:" ) ); ?></strong> <?php echo esc_html( emc_acf( 'contact_hours_jumuah_val', 'Friday, 12:00pm - 2:00pm' ) ); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Map Container -->
                <div class="map-container">
                    <?php
                    $map_embed = emc_acf( 'contact_map_embed', '' );
                    $default_map = 'https://www.google.com/maps?q=51.745083,0.507917&output=embed';
                    $map_src = $map_embed ? $map_embed : $default_map;
                    ?>
                    <iframe
                        src="<?php echo esc_url( $map_src ); ?>"
                        width="100%"
                        height="100%"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>

                <div class="getting-there-card glass-card">
                    <h3><i class="fas fa-route" aria-hidden="true"></i> <?php esc_html_e( 'Getting There', 'emc-theme' ); ?></h3>
                    <p><?php esc_html_e( 'Use the exact map pin for Essex Muslim Centre, Cuton Hall Lane, CM2 6PB. The postcode may not always land on the precise entrance, so use coordinates 51.745083, 0.507917 where possible.', 'emc-theme' ); ?></p>
                    <ul>
                        <li><strong><?php esc_html_e( 'By train from London:', 'emc-theme' ); ?></strong> <?php esc_html_e( 'Take a Greater Anglia service from London Liverpool Street to Chelmsford station, then continue by local bus or taxi to the exact map pin.', 'emc-theme' ); ?></li>
                        <li><strong><?php esc_html_e( 'By bus:', 'emc-theme' ); ?></strong> <?php esc_html_e( 'From Chelmsford city centre, use local services towards Springfield/Cuton Hall Lane and check live bus options before travelling.', 'emc-theme' ); ?></li>
                        <li><strong><?php esc_html_e( 'By taxi/car:', 'emc-theme' ); ?></strong> <?php esc_html_e( 'For door-to-door travel from London or Chelmsford station, share the exact map link or coordinates 51.745083, 0.507917 with your driver.', 'emc-theme' ); ?></li>
                    </ul>
                    <a href="https://maps.app.goo.gl/ctL7XFdazy4xsHAA7" class="btn btn-outline" target="_blank" rel="noopener noreferrer">
                        <i class="fas fa-map-marked-alt" aria-hidden="true"></i>
                        <?php esc_html_e( 'Open Exact Map Pin', 'emc-theme' ); ?>
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<?php get_footer(); ?>
