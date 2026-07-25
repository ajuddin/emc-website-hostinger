<?php
/**
 * Event registration forms, notifications, and admin screens.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the event registration settings with safe defaults.
 */
function emc_event_registration_settings() {
    return array(
        'enabled'            => '1' === (string) get_option( 'emc_event_registration_enabled', '1' ),
        'notification_email' => sanitize_email( get_option( 'emc_event_registration_email', 'info@essexmuslimcentre.org' ) ),
        'confirmation'       => '1' === (string) get_option( 'emc_event_confirmation_enabled', '1' ),
    );
}

/**
 * Whether the built-in registration form should be offered for an event.
 */
function emc_event_registration_is_open( $event_id ) {
    $settings = emc_event_registration_settings();
    if ( ! $settings['enabled'] || '1' === get_post_meta( $event_id, '_emc_event_registration_disabled', true ) ) {
        return false;
    }

    $date = get_post_meta( $event_id, '_emc_event_date', true );
    return ! $date || $date >= current_time( 'Y-m-d' );
}

/**
 * Count confirmed places for a given event.
 */
function emc_event_registered_places( $event_id ) {
    $registrations = get_option( 'emc_event_registrations', array() );
    $total         = 0;

    if ( ! is_array( $registrations ) ) {
        return 0;
    }

    foreach ( $registrations as $registration ) {
        if ( absint( $registration['event_id'] ?? 0 ) === absint( $event_id ) ) {
            $total += max( 1, absint( $registration['attendees'] ?? 1 ) );
        }
    }

    return $total;
}

/**
 * Render the registration form used on individual event pages.
 */
function emc_render_event_registration_form( $event_id ) {
    if ( ! emc_event_registration_is_open( $event_id ) ) {
        return;
    }

    $date       = get_post_meta( $event_id, '_emc_event_date', true );
    $time       = get_post_meta( $event_id, '_emc_event_time', true );
    $capacity   = absint( get_post_meta( $event_id, '_emc_event_capacity', true ) );
    $registered = emc_event_registered_places( $event_id );
    $remaining  = $capacity ? max( 0, $capacity - $registered ) : 0;
    $is_full    = $capacity && $remaining < 1;
    ?>
    <section class="event-registration-card scroll-reveal" id="event-registration" aria-labelledby="event-registration-title">
        <div class="event-registration-heading">
            <span class="event-registration-icon" aria-hidden="true"><i class="fas fa-ticket-alt"></i></span>
            <div>
                <span class="event-registration-kicker"><?php esc_html_e( 'Reserve your place', 'emc-theme' ); ?></span>
                <h2 id="event-registration-title"><?php esc_html_e( 'Register for this event', 'emc-theme' ); ?></h2>
                <?php if ( $date || $time ) : ?>
                <p>
                    <?php
                    if ( $date ) {
                        echo esc_html( date_i18n( 'l, j F Y', strtotime( $date ) ) );
                    }
                    if ( $date && $time ) {
                        echo ' &middot; ';
                    }
                    if ( $time ) {
                        echo esc_html( $time );
                    }
                    ?>
                </p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ( $is_full ) : ?>
            <div class="event-registration-full" role="status">
                <i class="fas fa-users" aria-hidden="true"></i>
                <?php esc_html_e( 'This event is currently fully booked.', 'emc-theme' ); ?>
            </div>
        <?php else : ?>
            <?php if ( $capacity ) : ?>
            <p class="event-registration-availability">
                <strong><?php echo esc_html( $remaining ); ?></strong>
                <?php echo esc_html( _n( 'place remaining', 'places remaining', $remaining, 'emc-theme' ) ); ?>
            </p>
            <?php endif; ?>

            <form class="emc-event-registration-form" method="post" novalidate>
                <input type="hidden" name="action" value="emc_event_register">
                <input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>">
                <div class="event-form-trap" aria-hidden="true">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="event-form-grid">
                    <label>
                        <span><?php esc_html_e( 'Full name', 'emc-theme' ); ?> *</span>
                        <input type="text" name="full_name" autocomplete="name" required>
                    </label>
                    <label>
                        <span><?php esc_html_e( 'Email address', 'emc-theme' ); ?> *</span>
                        <input type="email" name="email" autocomplete="email" required>
                    </label>
                    <label>
                        <span><?php esc_html_e( 'Phone number', 'emc-theme' ); ?></span>
                        <input type="tel" name="phone" autocomplete="tel">
                    </label>
                    <label>
                        <span><?php esc_html_e( 'Number of attendees', 'emc-theme' ); ?> *</span>
                        <input type="number" name="attendees" value="1" min="1" max="<?php echo esc_attr( $capacity ? max( 1, min( 20, $remaining ) ) : 20 ); ?>" required>
                    </label>
                </div>

                <label class="event-form-message">
                    <span><?php esc_html_e( 'Notes or accessibility requirements', 'emc-theme' ); ?></span>
                    <textarea name="message" rows="4"></textarea>
                </label>

                <label class="event-form-consent">
                    <input type="checkbox" name="consent" value="1" required>
                    <span><?php esc_html_e( 'I agree that Essex Muslim Centre may use these details to manage my event registration.', 'emc-theme' ); ?> *</span>
                </label>

                <button class="btn btn-primary event-register-submit" type="submit">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <span><?php esc_html_e( 'Complete Registration', 'emc-theme' ); ?></span>
                </button>
                <div class="event-form-status" role="status" aria-live="polite" tabindex="-1"></div>
            </form>
        <?php endif; ?>
    </section>
    <?php
}

/**
 * Process a public event registration.
 */
function emc_ajax_event_register() {
    check_ajax_referer( 'emc_event_registration', 'nonce' );

    if ( ! empty( $_POST['website'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Unable to submit this registration.', 'emc-theme' ) ), 400 );
    }

    $event_id = absint( $_POST['event_id'] ?? 0 );
    $event    = get_post( $event_id );
    if ( ! $event || 'emc_event' !== $event->post_type || 'publish' !== $event->post_status || ! emc_event_registration_is_open( $event_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Registration is not available for this event.', 'emc-theme' ) ), 400 );
    }

    $name      = sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) );
    $email     = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $phone     = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
    $attendees = min( 20, max( 1, absint( $_POST['attendees'] ?? 1 ) ) );
    $message   = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
    $consent   = '1' === (string) ( $_POST['consent'] ?? '' );

    if ( ! $name || ! is_email( $email ) || ! $consent ) {
        wp_send_json_error( array( 'message' => __( 'Please enter your name and a valid email address, then accept the consent statement.', 'emc-theme' ) ), 400 );
    }

    $capacity  = absint( get_post_meta( $event_id, '_emc_event_capacity', true ) );
    $remaining = $capacity ? max( 0, $capacity - emc_event_registered_places( $event_id ) ) : 0;
    if ( $capacity && $attendees > $remaining ) {
        $capacity_message = $remaining
            ? sprintf( _n( 'Only %d place remains. Please reduce the number of attendees.', 'Only %d places remain. Please reduce the number of attendees.', $remaining, 'emc-theme' ), $remaining )
            : __( 'This event has just become fully booked.', 'emc-theme' );
        wp_send_json_error( array( 'message' => $capacity_message ), 409 );
    }

    $rate_key = 'emc_evt_reg_' . md5( $event_id . '|' . strtolower( $email ) );
    if ( get_transient( $rate_key ) ) {
        wp_send_json_error( array( 'message' => __( 'A registration was just submitted with this email. Please wait a moment before trying again.', 'emc-theme' ) ), 429 );
    }
    set_transient( $rate_key, 1, MINUTE_IN_SECONDS );

    $registration = array(
        'id'        => wp_generate_uuid4(),
        'event_id'  => $event_id,
        'name'      => $name,
        'email'     => $email,
        'phone'     => $phone,
        'attendees' => $attendees,
        'message'   => $message,
        'date'      => current_time( 'mysql' ),
    );

    $registrations   = get_option( 'emc_event_registrations', array() );
    $registrations   = is_array( $registrations ) ? $registrations : array();
    $registrations[] = $registration;
    update_option( 'emc_event_registrations', array_slice( $registrations, -2000 ), false );

    $settings   = emc_event_registration_settings();
    $event_date = get_post_meta( $event_id, '_emc_event_date', true );
    $event_time = get_post_meta( $event_id, '_emc_event_time', true );
    $venue      = get_post_meta( $event_id, '_emc_event_venue', true );
    $details    = array(
        'Event: ' . get_the_title( $event_id ),
        'Date: ' . ( $event_date ? date_i18n( 'l, j F Y', strtotime( $event_date ) ) : 'To be confirmed' ),
        'Time: ' . ( $event_time ?: 'To be confirmed' ),
        'Venue: ' . ( $venue ?: 'To be confirmed' ),
        'Name: ' . $name,
        'Email: ' . $email,
        'Phone: ' . ( $phone ?: 'Not supplied' ),
        'Attendees: ' . $attendees,
        'Notes: ' . ( $message ?: 'None' ),
    );

    $headers = array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $name . ' <' . $email . '>' );
    wp_mail(
        $settings['notification_email'] ?: 'info@essexmuslimcentre.org',
        sprintf( __( 'New event registration: %s', 'emc-theme' ), get_the_title( $event_id ) ),
        implode( "\n", $details ),
        $headers
    );

    $confirmation_sent = false;
    if ( $settings['confirmation'] ) {
        $confirmation  = sprintf( __( "Assalamu Alaikum %s,\n\nYour registration has been received.", 'emc-theme' ), $name );
        $confirmation .= "\n\n" . implode( "\n", array_slice( $details, 0, 4 ) );
        $confirmation .= "\n" . sprintf( _n( 'Attendees: %d', 'Attendees: %d', $attendees, 'emc-theme' ), $attendees );
        $confirmation .= "\n\n" . __( 'If you need to change your registration, please reply to this email.', 'emc-theme' );
        $confirmation_sent = wp_mail( $email, sprintf( __( 'Registration confirmed: %s', 'emc-theme' ), get_the_title( $event_id ) ), $confirmation );
    }

    wp_send_json_success( array(
        'message' => $confirmation_sent
            ? __( 'Thank you. Your registration has been received and a confirmation has been sent to your email.', 'emc-theme' )
            : __( 'Thank you. Your registration has been received.', 'emc-theme' ),
    ) );
}
add_action( 'wp_ajax_emc_event_register', 'emc_ajax_event_register' );
add_action( 'wp_ajax_nopriv_emc_event_register', 'emc_ajax_event_register' );

/**
 * Add registrations and settings beneath Events in WordPress admin.
 */
function emc_event_registration_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=emc_event',
        __( 'Event Registrations', 'emc-theme' ),
        __( 'Registrations', 'emc-theme' ),
        'edit_posts',
        'emc-event-registrations',
        'emc_event_registrations_admin_page'
    );
    add_submenu_page(
        'edit.php?post_type=emc_event',
        __( 'Registration Settings', 'emc-theme' ),
        __( 'Registration Settings', 'emc-theme' ),
        'manage_options',
        'emc-event-registration-settings',
        'emc_event_registration_settings_page'
    );
}
add_action( 'admin_menu', 'emc_event_registration_admin_menu' );

/**
 * Register event registration options.
 */
function emc_event_registration_register_settings() {
    register_setting( 'emc_event_registration', 'emc_event_registration_email', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default'           => 'info@essexmuslimcentre.org',
    ) );
    register_setting( 'emc_event_registration', 'emc_event_registration_enabled', array(
        'type'              => 'string',
        'sanitize_callback' => function ( $value ) {
            return '1' === (string) $value ? '1' : '0';
        },
        'default'           => '1',
    ) );
    register_setting( 'emc_event_registration', 'emc_event_confirmation_enabled', array(
        'type'              => 'string',
        'sanitize_callback' => function ( $value ) {
            return '1' === (string) $value ? '1' : '0';
        },
        'default'           => '1',
    ) );
}
add_action( 'admin_init', 'emc_event_registration_register_settings' );

/**
 * Registrations listing.
 */
function emc_event_registrations_admin_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        return;
    }

    $registrations = get_option( 'emc_event_registrations', array() );
    $registrations = is_array( $registrations ) ? array_reverse( $registrations ) : array();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Event Registrations', 'emc-theme' ); ?></h1>
        <p>
            <?php esc_html_e( 'Form submissions from all events appear here.', 'emc-theme' ); ?>
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=emc_event&page=emc-event-registration-settings' ) ); ?>"><?php esc_html_e( 'Registration settings', 'emc-theme' ); ?></a>
        </p>

        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Submitted', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Event', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Name', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Email', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Phone', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Attendees', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Notes', 'emc-theme' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $registrations ) : ?>
                    <?php foreach ( $registrations as $registration ) :
                        $event_id = absint( $registration['event_id'] ?? 0 );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $registration['date'] ?? '' ); ?></td>
                        <td>
                            <?php if ( $event_id && get_post( $event_id ) ) : ?>
                                <a href="<?php echo esc_url( get_edit_post_link( $event_id ) ); ?>"><?php echo esc_html( get_the_title( $event_id ) ); ?></a>
                            <?php else : ?>
                                <?php esc_html_e( 'Deleted event', 'emc-theme' ); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $registration['name'] ?? '' ); ?></td>
                        <td><a href="mailto:<?php echo esc_attr( $registration['email'] ?? '' ); ?>"><?php echo esc_html( $registration['email'] ?? '' ); ?></a></td>
                        <td><?php echo esc_html( $registration['phone'] ?? '' ); ?></td>
                        <td><?php echo esc_html( $registration['attendees'] ?? 1 ); ?></td>
                        <td><?php echo nl2br( esc_html( $registration['message'] ?? '' ) ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="7"><?php esc_html_e( 'No registrations have been submitted yet.', 'emc-theme' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Registration settings screen.
 */
function emc_event_registration_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $settings = emc_event_registration_settings();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Event Registration Settings', 'emc-theme' ); ?></h1>
        <form action="options.php" method="post">
            <?php settings_fields( 'emc_event_registration' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Registration forms', 'emc-theme' ); ?></th>
                    <td>
                        <input type="hidden" name="emc_event_registration_enabled" value="0">
                        <label><input type="checkbox" name="emc_event_registration_enabled" value="1" <?php checked( $settings['enabled'] ); ?>> <?php esc_html_e( 'Enable registration forms for upcoming events', 'emc-theme' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="emc_event_registration_email"><?php esc_html_e( 'Notification email', 'emc-theme' ); ?></label></th>
                    <td>
                        <input type="email" class="regular-text" id="emc_event_registration_email" name="emc_event_registration_email" value="<?php echo esc_attr( $settings['notification_email'] ); ?>" required>
                        <p class="description"><?php esc_html_e( 'Every new registration is sent to this address.', 'emc-theme' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Attendee confirmation', 'emc-theme' ); ?></th>
                    <td>
                        <input type="hidden" name="emc_event_confirmation_enabled" value="0">
                        <label><input type="checkbox" name="emc_event_confirmation_enabled" value="1" <?php checked( $settings['confirmation'] ); ?>> <?php esc_html_e( 'Email a confirmation to the person registering', 'emc-theme' ); ?></label>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
