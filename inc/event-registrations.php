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
 * Default fields used until an event gets its own form configuration.
 *
 * @return array
 */
function emc_event_registration_default_fields() {
    return array(
        array( 'key' => 'full_name', 'label' => __( 'Full name', 'emc-theme' ), 'type' => 'text', 'required' => true, 'options' => array() ),
        array( 'key' => 'email', 'label' => __( 'Email address', 'emc-theme' ), 'type' => 'email', 'required' => true, 'options' => array() ),
        array( 'key' => 'phone', 'label' => __( 'Phone number', 'emc-theme' ), 'type' => 'tel', 'required' => false, 'options' => array() ),
        array( 'key' => 'attendees', 'label' => __( 'Number of attendees', 'emc-theme' ), 'type' => 'number', 'required' => true, 'options' => array() ),
        array( 'key' => 'message', 'label' => __( 'Notes or accessibility requirements', 'emc-theme' ), 'type' => 'textarea', 'required' => false, 'options' => array() ),
        array( 'key' => 'consent', 'label' => __( 'I agree that Essex Muslim Centre may use these details to manage my event registration.', 'emc-theme' ), 'type' => 'checkbox', 'required' => true, 'options' => array() ),
    );
}

/**
 * Return the configured form fields for an event.
 *
 * @param int $event_id Event post ID.
 * @return array
 */
function emc_event_registration_fields( $event_id ) {
    $saved = get_post_meta( $event_id, '_emc_event_registration_fields', true );
    return is_array( $saved ) ? $saved : emc_event_registration_default_fields();
}

/**
 * Return paid-registration configuration for an event.
 *
 * @param int $event_id Event post ID.
 * @return array
 */
function emc_event_payment_config( $event_id ) {
    $price = round( (float) get_post_meta( $event_id, '_emc_event_registration_price', true ), 2 );

    return array(
        'enabled' => '1' === get_post_meta( $event_id, '_emc_event_payment_enabled', true ) && $price >= 0.50,
        'price'   => $price,
        'pence'   => (int) round( $price * 100 ),
    );
}

/**
 * Whether the existing EMC Payments Stripe connection can take event fees.
 *
 * @return bool
 */
function emc_event_stripe_is_available() {
    if ( ! function_exists( 'emc_stripe_request' ) || ! function_exists( 'emc_stripe_pub_key' ) || ! function_exists( 'emc_stripe_secret_key' ) || ! emc_stripe_pub_key() || ! emc_stripe_secret_key() ) {
        return false;
    }

    return ! function_exists( 'emc_payment_license_is_active' ) || emc_payment_license_is_active();
}

/**
 * Add the per-event form builder and payment controls.
 */
function emc_event_registration_meta_box() {
    add_meta_box(
        'emc-event-registration-form-builder',
        __( 'Registration Form & Payment', 'emc-theme' ),
        'emc_event_registration_meta_box_html',
        'emc_event',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes_emc_event', 'emc_event_registration_meta_box' );

/**
 * Render one form-builder row.
 *
 * @param int   $index Row index.
 * @param array $field Field configuration.
 */
function emc_event_registration_builder_row( $index, $field ) {
    $types = array(
        'text'     => __( 'Text', 'emc-theme' ),
        'email'    => __( 'Email', 'emc-theme' ),
        'tel'      => __( 'Telephone', 'emc-theme' ),
        'number'   => __( 'Number', 'emc-theme' ),
        'textarea' => __( 'Long text', 'emc-theme' ),
        'select'   => __( 'Dropdown', 'emc-theme' ),
        'checkbox' => __( 'Checkbox', 'emc-theme' ),
    );
    $options = ! empty( $field['options'] ) && is_array( $field['options'] ) ? implode( "\n", $field['options'] ) : '';
    ?>
    <tr class="emc-event-field-row">
        <td>
            <input type="hidden" data-field="key" name="emc_event_form_fields[<?php echo esc_attr( $index ); ?>][key]" value="<?php echo esc_attr( $field['key'] ?? '' ); ?>">
            <input type="text" class="widefat" data-field="label" name="emc_event_form_fields[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $field['label'] ?? '' ); ?>" required>
        </td>
        <td>
            <select class="widefat" data-field="type" name="emc_event_form_fields[<?php echo esc_attr( $index ); ?>][type]">
                <?php foreach ( $types as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $field['type'] ?? 'text', $value ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><textarea class="widefat" data-field="options" name="emc_event_form_fields[<?php echo esc_attr( $index ); ?>][options]" rows="2" placeholder="<?php esc_attr_e( 'One dropdown option per line', 'emc-theme' ); ?>"><?php echo esc_textarea( $options ); ?></textarea></td>
        <td style="text-align:center"><input type="checkbox" data-field="required" name="emc_event_form_fields[<?php echo esc_attr( $index ); ?>][required]" value="1" <?php checked( ! empty( $field['required'] ) ); ?>></td>
        <td><button type="button" class="button-link-delete emc-remove-event-field"><?php esc_html_e( 'Remove', 'emc-theme' ); ?></button></td>
    </tr>
    <?php
}

/**
 * Render form-builder and Stripe settings on an event edit screen.
 *
 * @param WP_Post $post Event post.
 */
function emc_event_registration_meta_box_html( $post ) {
    wp_nonce_field( 'emc_event_registration_config_save', 'emc_event_registration_config_nonce' );
    $fields  = emc_event_registration_fields( $post->ID );
    $payment = emc_event_payment_config( $post->ID );
    ?>
    <h3><?php esc_html_e( 'Payment', 'emc-theme' ); ?></h3>
    <p>
        <label>
            <input type="checkbox" name="emc_event_payment_enabled" value="1" <?php checked( '1', get_post_meta( $post->ID, '_emc_event_payment_enabled', true ) ); ?>>
            <strong><?php esc_html_e( 'Require Stripe payment to complete registration', 'emc-theme' ); ?></strong>
        </label>
    </p>
    <p>
        <label for="emc-event-registration-price"><strong><?php esc_html_e( 'Price per attendee (£)', 'emc-theme' ); ?></strong></label><br>
        <input type="number" id="emc-event-registration-price" name="emc_event_registration_price" value="<?php echo esc_attr( $payment['price'] ? number_format( $payment['price'], 2, '.', '' ) : '' ); ?>" min="0.50" max="10000" step="0.01" class="small-text" placeholder="10.00">
        <span class="description"><?php esc_html_e( 'The final Stripe total is this price multiplied by the Number of attendees field. If that field is removed, one ticket is charged.', 'emc-theme' ); ?></span>
    </p>
    <?php if ( '1' === get_post_meta( $post->ID, '_emc_event_payment_enabled', true ) && ! emc_event_stripe_is_available() ) : ?>
        <div class="notice notice-warning inline"><p><?php esc_html_e( 'Paid registration is enabled, but the EMC Payments Stripe connection is not currently available.', 'emc-theme' ); ?></p></div>
    <?php endif; ?>
    <?php if ( '1' === get_post_meta( $post->ID, '_emc_event_payment_enabled', true ) && $payment['price'] < 0.50 ) : ?>
        <div class="notice notice-warning inline"><p><?php esc_html_e( 'Enter a ticket price of at least £0.50 before paid registration can become active.', 'emc-theme' ); ?></p></div>
    <?php endif; ?>

    <hr>
    <h3><?php esc_html_e( 'Registration fields', 'emc-theme' ); ?></h3>
    <p class="description"><?php esc_html_e( 'Edit labels and field types, mark fields required, or add and remove fields. Dropdown choices should be entered one per line.', 'emc-theme' ); ?></p>
    <table class="widefat striped" id="emc-event-form-builder">
        <thead><tr>
            <th><?php esc_html_e( 'Field name', 'emc-theme' ); ?></th>
            <th style="width:150px"><?php esc_html_e( 'Type', 'emc-theme' ); ?></th>
            <th><?php esc_html_e( 'Dropdown options', 'emc-theme' ); ?></th>
            <th style="width:75px;text-align:center"><?php esc_html_e( 'Required', 'emc-theme' ); ?></th>
            <th style="width:70px"></th>
        </tr></thead>
        <tbody>
            <?php foreach ( $fields as $index => $field ) { emc_event_registration_builder_row( $index, $field ); } ?>
        </tbody>
    </table>
    <p><button type="button" class="button" id="emc-add-event-field"><?php esc_html_e( 'Add field', 'emc-theme' ); ?></button></p>
    <script>
    (() => {
        const table = document.querySelector('#emc-event-form-builder tbody');
        const addButton = document.getElementById('emc-add-event-field');
        if (!table || !addButton) return;

        const reindex = () => {
            table.querySelectorAll('tr').forEach((row, index) => {
                row.querySelectorAll('[data-field]').forEach(input => {
                    input.name = `emc_event_form_fields[${index}][${input.dataset.field}]`;
                });
            });
        };
        const bindRemove = row => row.querySelector('.emc-remove-event-field')?.addEventListener('click', () => {
            row.remove();
            reindex();
        });
        table.querySelectorAll('tr').forEach(bindRemove);

        addButton.addEventListener('click', () => {
            const row = document.createElement('tr');
            row.className = 'emc-event-field-row';
            row.innerHTML = `
                <td><input type="hidden" data-field="key" value="field_${Date.now()}"><input type="text" class="widefat" data-field="label" required></td>
                <td><select class="widefat" data-field="type"><option value="text">Text</option><option value="email">Email</option><option value="tel">Telephone</option><option value="number">Number</option><option value="textarea">Long text</option><option value="select">Dropdown</option><option value="checkbox">Checkbox</option></select></td>
                <td><textarea class="widefat" data-field="options" rows="2" placeholder="One dropdown option per line"></textarea></td>
                <td style="text-align:center"><input type="checkbox" data-field="required" value="1"></td>
                <td><button type="button" class="button-link-delete emc-remove-event-field">Remove</button></td>`;
            table.appendChild(row);
            bindRemove(row);
            reindex();
            row.querySelector('[data-field="label"]').focus();
        });
    })();
    </script>
    <?php
}

/**
 * Save per-event registration form and payment settings.
 *
 * @param int $post_id Event ID.
 */
function emc_save_event_registration_config( $post_id ) {
    if ( ! isset( $_POST['emc_event_registration_config_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['emc_event_registration_config_nonce'] ) ), 'emc_event_registration_config_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $allowed_types = array( 'text', 'email', 'tel', 'number', 'textarea', 'select', 'checkbox' );
    $submitted     = isset( $_POST['emc_event_form_fields'] ) && is_array( $_POST['emc_event_form_fields'] ) ? wp_unslash( $_POST['emc_event_form_fields'] ) : array();
    $fields        = array();
    $used_keys     = array();

    foreach ( array_slice( $submitted, 0, 30 ) as $index => $field ) {
        $label = sanitize_text_field( $field['label'] ?? '' );
        $type  = sanitize_key( $field['type'] ?? 'text' );
        $key   = sanitize_key( $field['key'] ?? '' );
        if ( ! $label || ! in_array( $type, $allowed_types, true ) ) {
            continue;
        }
        if ( ! $key || isset( $used_keys[ $key ] ) ) {
            $key = 'field_' . substr( md5( $post_id . '|' . $index . '|' . $label ), 0, 12 );
        }
        $used_keys[ $key ] = true;

        $options = array();
        if ( 'select' === $type ) {
            $raw_options = preg_split( '/\r\n|\r|\n/', (string) ( $field['options'] ?? '' ) );
            foreach ( array_slice( $raw_options, 0, 50 ) as $option ) {
                $option = sanitize_text_field( $option );
                if ( '' !== $option ) {
                    $options[] = $option;
                }
            }
        }

        $fields[] = array(
            'key'      => $key,
            'label'    => $label,
            'type'     => $type,
            'required' => ! empty( $field['required'] ),
            'options'  => array_values( array_unique( $options ) ),
        );
    }

    update_post_meta( $post_id, '_emc_event_registration_fields', $fields );
    update_post_meta( $post_id, '_emc_event_payment_enabled', isset( $_POST['emc_event_payment_enabled'] ) ? '1' : '' );
    $price = round( (float) wp_unslash( $_POST['emc_event_registration_price'] ?? 0 ), 2 );
    update_post_meta( $post_id, '_emc_event_registration_price', $price >= 0.50 && $price <= 10000 ? number_format( $price, 2, '.', '' ) : '' );
}
add_action( 'save_post_emc_event', 'emc_save_event_registration_config' );

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
    $fields     = emc_event_registration_fields( $event_id );
    $payment    = emc_event_payment_config( $event_id );
    $stripe_ok  = ! $payment['enabled'] || emc_event_stripe_is_available();
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

            <?php if ( $payment['enabled'] ) : ?>
                <div class="event-registration-price">
                    <span><?php esc_html_e( 'Ticket price', 'emc-theme' ); ?></span>
                    <strong><?php echo esc_html( '£' . number_format( $payment['price'], 2 ) ); ?> <?php esc_html_e( 'per attendee', 'emc-theme' ); ?></strong>
                </div>
            <?php endif; ?>

            <form class="emc-event-registration-form" method="post" novalidate data-paid="<?php echo $payment['enabled'] ? '1' : '0'; ?>" data-ticket-price="<?php echo esc_attr( $payment['pence'] ); ?>" data-stripe-key="<?php echo esc_attr( $payment['enabled'] && $stripe_ok ? emc_stripe_pub_key() : '' ); ?>">
                <input type="hidden" name="action" value="emc_event_register">
                <input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>">
                <div class="event-form-trap" aria-hidden="true">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="event-form-grid">
                    <?php foreach ( $fields as $field ) :
                        $key      = sanitize_key( $field['key'] ?? '' );
                        $type     = $field['type'] ?? 'text';
                        $required = ! empty( $field['required'] );
                        if ( ! $key ) {
                            continue;
                        }
                        $field_id = 'emc-event-' . $event_id . '-' . $key;
                        $class    = in_array( $type, array( 'textarea', 'checkbox' ), true ) ? ' event-form-field-wide' : '';
                        $class   .= 'checkbox' === $type ? ' event-form-field-checkbox' : '';
                    ?>
                        <label class="event-form-field<?php echo esc_attr( $class ); ?>" for="<?php echo esc_attr( $field_id ); ?>">
                            <?php if ( 'checkbox' === $type ) : ?>
                                <input id="<?php echo esc_attr( $field_id ); ?>" type="checkbox" name="fields[<?php echo esc_attr( $key ); ?>]" value="1" <?php echo $required ? 'required' : ''; ?>>
                                <span><?php echo esc_html( $field['label'] ); ?><?php echo $required ? ' *' : ''; ?></span>
                            <?php else : ?>
                                <span><?php echo esc_html( $field['label'] ); ?><?php echo $required ? ' *' : ''; ?></span>
                                <?php if ( 'textarea' === $type ) : ?>
                                    <textarea id="<?php echo esc_attr( $field_id ); ?>" name="fields[<?php echo esc_attr( $key ); ?>]" rows="4" <?php echo $required ? 'required' : ''; ?>></textarea>
                                <?php elseif ( 'select' === $type ) : ?>
                                    <select id="<?php echo esc_attr( $field_id ); ?>" name="fields[<?php echo esc_attr( $key ); ?>]" <?php echo $required ? 'required' : ''; ?>>
                                        <option value=""><?php esc_html_e( 'Select an option', 'emc-theme' ); ?></option>
                                        <?php foreach ( $field['options'] ?? array() as $option ) : ?>
                                            <option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else :
                                    $autocomplete = 'email' === $type ? 'email' : ( 'tel' === $type ? 'tel' : ( 'full_name' === $key ? 'name' : 'off' ) );
                                    $is_attendees = 'attendees' === $key && 'number' === $type;
                                ?>
                                    <input id="<?php echo esc_attr( $field_id ); ?>" type="<?php echo esc_attr( $type ); ?>" name="fields[<?php echo esc_attr( $key ); ?>]" autocomplete="<?php echo esc_attr( $autocomplete ); ?>" <?php echo $is_attendees ? 'value="1" min="1" max="' . esc_attr( $capacity ? max( 1, min( 20, $remaining ) ) : 20 ) . '"' : ''; ?> <?php echo $required ? 'required' : ''; ?>>
                                <?php endif; ?>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <?php if ( $payment['enabled'] && $stripe_ok ) : ?>
                    <div class="event-payment-box">
                        <div class="event-payment-total">
                            <span><?php esc_html_e( 'Total to pay', 'emc-theme' ); ?></span>
                            <strong data-event-payment-total><?php echo esc_html( '£' . number_format( $payment['price'], 2 ) ); ?></strong>
                        </div>
                        <label><span><?php esc_html_e( 'Card details', 'emc-theme' ); ?> *</span></label>
                        <div class="event-stripe-card" aria-label="<?php esc_attr_e( 'Card details', 'emc-theme' ); ?>"></div>
                        <div class="event-card-errors" role="alert"></div>
                        <p class="event-payment-secure"><i class="fas fa-lock" aria-hidden="true"></i> <?php esc_html_e( 'Secure payment processed by Stripe.', 'emc-theme' ); ?></p>
                    </div>
                <?php elseif ( $payment['enabled'] ) : ?>
                    <div class="event-form-status is-error"><?php esc_html_e( 'Online payment is temporarily unavailable. Please contact the centre.', 'emc-theme' ); ?></div>
                <?php endif; ?>

                <button class="btn btn-primary event-register-submit" type="submit" <?php disabled( ! $stripe_ok ); ?>>
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <span><?php echo esc_html( $payment['enabled'] ? __( 'Pay and Complete Registration', 'emc-theme' ) : __( 'Complete Registration', 'emc-theme' ) ); ?></span>
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

    $validated = emc_event_validate_registration_fields( $event_id, $_POST['fields'] ?? array() );
    if ( is_wp_error( $validated ) ) {
        wp_send_json_error( array( 'message' => $validated->get_error_message() ), 400 );
    }

    $attendees = $validated['attendees'];

    $capacity  = absint( get_post_meta( $event_id, '_emc_event_capacity', true ) );
    $remaining = $capacity ? max( 0, $capacity - emc_event_registered_places( $event_id ) ) : 0;
    if ( $capacity && $attendees > $remaining ) {
        $capacity_message = $remaining
            ? sprintf( _n( 'Only %d place remains. Please reduce the number of attendees.', 'Only %d places remain. Please reduce the number of attendees.', $remaining, 'emc-theme' ), $remaining )
            : __( 'This event has just become fully booked.', 'emc-theme' );
        wp_send_json_error( array( 'message' => $capacity_message ), 409 );
    }

    $rate_identity = $validated['email'];
    if ( ! $rate_identity ) {
        $rate_identity = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : wp_generate_uuid4();
    }
    $rate_key = 'emc_evt_reg_' . md5( $event_id . '|' . strtolower( $rate_identity ) );
    if ( get_transient( $rate_key ) ) {
        wp_send_json_error( array( 'message' => __( 'A registration was just submitted with these details. Please wait a moment before trying again.', 'emc-theme' ) ), 429 );
    }

    $pending = array(
        'event_id'      => $event_id,
        'fields'        => $validated['fields'],
        'name'          => $validated['name'],
        'email'         => $validated['email'],
        'phone'         => $validated['phone'],
        'attendees'     => $attendees,
        'message'       => $validated['message'],
        'rate_key'      => $rate_key,
    );

    $payment = emc_event_payment_config( $event_id );
    if ( ! $payment['enabled'] ) {
        set_transient( $rate_key, 1, MINUTE_IN_SECONDS );
        $result = emc_event_store_completed_registration( $pending );
        wp_send_json_success( $result );
    }

    if ( ! emc_event_stripe_is_available() ) {
        wp_send_json_error( array( 'message' => __( 'Online payment is temporarily unavailable. Please contact the centre.', 'emc-theme' ) ), 503 );
    }

    $token        = wp_generate_uuid4();
    $amount_pence = $payment['pence'] * $attendees;
    $intent_body = array(
        'amount'                               => $amount_pence,
        'currency'                             => 'gbp',
        'automatic_payment_methods[enabled]'   => 'true',
        'description'                          => sprintf( 'Event registration: %s', get_the_title( $event_id ) ),
        'metadata[source]'                     => 'EMC Event Registration',
        'metadata[event_id]'                   => $event_id,
        'metadata[registration_token]'         => $token,
        'metadata[attendees]'                  => $attendees,
    );
    if ( $validated['email'] ) {
        $intent_body['receipt_email'] = $validated['email'];
    }
    $intent = emc_stripe_request( 'POST', 'payment_intents', $intent_body );

    if ( is_wp_error( $intent ) || empty( $intent['client_secret'] ) || empty( $intent['id'] ) ) {
        $message = is_wp_error( $intent ) ? $intent->get_error_message() : __( 'Stripe could not prepare this payment.', 'emc-theme' );
        wp_send_json_error( array( 'message' => $message ), 502 );
    }

    $pending['token']             = $token;
    $pending['payment_intent']    = sanitize_text_field( $intent['id'] );
    $pending['amount_pence']      = $amount_pence;
    if ( ! set_transient( 'emc_evt_pending_' . $token, $pending, 30 * MINUTE_IN_SECONDS ) ) {
        wp_send_json_error( array( 'message' => __( 'The registration payment session could not be saved. No payment has been taken.', 'emc-theme' ) ), 500 );
    }

    wp_send_json_success( array(
        'requiresPayment' => true,
        'clientSecret'    => $intent['client_secret'],
        'paymentIntent'   => $intent['id'],
        'token'           => $token,
        'amount'          => '£' . number_format( $amount_pence / 100, 2 ),
    ) );
}
add_action( 'wp_ajax_emc_event_register', 'emc_ajax_event_register' );
add_action( 'wp_ajax_nopriv_emc_event_register', 'emc_ajax_event_register' );

/**
 * Sanitize and validate an event's configured public fields.
 *
 * @param int   $event_id  Event ID.
 * @param mixed $submitted Submitted field values.
 * @return array|WP_Error
 */
function emc_event_validate_registration_fields( $event_id, $submitted ) {
    $submitted = is_array( $submitted ) ? wp_unslash( $submitted ) : array();
    $values    = array();
    $identity  = array( 'name' => '', 'email' => '', 'phone' => '', 'message' => '', 'attendees' => 1 );

    foreach ( emc_event_registration_fields( $event_id ) as $field ) {
        $key   = sanitize_key( $field['key'] ?? '' );
        $type  = $field['type'] ?? 'text';
        $raw   = $submitted[ $key ] ?? '';
        $value = '';

        if ( 'checkbox' === $type ) {
            $value = '1' === (string) $raw ? 'Yes' : '';
        } elseif ( 'textarea' === $type ) {
            $value = sanitize_textarea_field( $raw );
        } elseif ( 'email' === $type ) {
            $value = sanitize_email( $raw );
            if ( '' !== trim( (string) $raw ) && ! is_email( $value ) ) {
                return new WP_Error( 'invalid_email', sprintf( __( 'Please enter a valid value for “%s”.', 'emc-theme' ), $field['label'] ) );
            }
        } elseif ( 'number' === $type ) {
            $value = (string) absint( $raw );
        } elseif ( 'select' === $type ) {
            $value = sanitize_text_field( $raw );
            if ( '' !== $value && ! in_array( $value, $field['options'] ?? array(), true ) ) {
                return new WP_Error( 'invalid_option', sprintf( __( 'Please select a valid option for “%s”.', 'emc-theme' ), $field['label'] ) );
            }
        } else {
            $value = sanitize_text_field( $raw );
        }

        if ( ! empty( $field['required'] ) && '' === $value ) {
            return new WP_Error( 'required_field', sprintf( __( '“%s” is required.', 'emc-theme' ), $field['label'] ) );
        }

        $values[ $key ] = array(
            'label' => sanitize_text_field( $field['label'] ),
            'type'  => $type,
            'value' => $value,
        );

        if ( 'full_name' === $key ) {
            $identity['name'] = $value;
        }
        if ( 'email' === $type && ! $identity['email'] ) {
            $identity['email'] = $value;
        }
        if ( 'phone' === $key || ( 'tel' === $type && ! $identity['phone'] ) ) {
            $identity['phone'] = $value;
        }
        if ( 'message' === $key || ( 'textarea' === $type && ! $identity['message'] ) ) {
            $identity['message'] = $value;
        }
        if ( 'attendees' === $key && 'number' === $type ) {
            $identity['attendees'] = min( 20, max( 1, absint( $value ) ) );
            $values[ $key ]['value'] = (string) $identity['attendees'];
        }
    }

    if ( ! $identity['name'] ) {
        foreach ( $values as $field ) {
            if ( 'text' === $field['type'] && $field['value'] ) {
                $identity['name'] = $field['value'];
                break;
            }
        }
    }

    return array_merge( array( 'fields' => $values ), $identity );
}

/**
 * Save a completed registration and send notifications.
 *
 * @param array $pending Validated registration data.
 * @param array $payment Confirmed payment data, when paid.
 * @return array AJAX success response.
 */
function emc_event_store_completed_registration( $pending, $payment = array() ) {
    $event_id = absint( $pending['event_id'] ?? 0 );
    $email    = sanitize_email( $pending['email'] ?? '' );
    $name     = sanitize_text_field( $pending['name'] ?? '' );
    $payment_intent = sanitize_text_field( $payment['payment_intent'] ?? '' );

    $registrations = get_option( 'emc_event_registrations', array() );
    $registrations = is_array( $registrations ) ? $registrations : array();
    if ( $payment_intent ) {
        foreach ( $registrations as $existing ) {
            if ( $payment_intent === ( $existing['payment_intent'] ?? '' ) ) {
                return array( 'message' => __( 'Thank you. Your payment and registration have been confirmed.', 'emc-theme' ) );
            }
        }
    }

    $registration = array(
        'id'             => wp_generate_uuid4(),
        'event_id'       => $event_id,
        'name'           => $name,
        'email'          => $email,
        'phone'          => sanitize_text_field( $pending['phone'] ?? '' ),
        'attendees'      => min( 20, max( 1, absint( $pending['attendees'] ?? 1 ) ) ),
        'message'        => sanitize_textarea_field( $pending['message'] ?? '' ),
        'fields'         => is_array( $pending['fields'] ?? null ) ? $pending['fields'] : array(),
        'payment_status' => $payment_intent ? 'paid' : 'free',
        'payment_intent' => $payment_intent,
        'amount'         => $payment_intent ? number_format( absint( $payment['amount_pence'] ?? 0 ) / 100, 2, '.', '' ) : '0.00',
        'date'           => current_time( 'mysql' ),
    );
    $registrations[] = $registration;
    update_option( 'emc_event_registrations', array_slice( $registrations, -2000 ), false );

    $event_date = get_post_meta( $event_id, '_emc_event_date', true );
    $event_time = get_post_meta( $event_id, '_emc_event_time', true );
    $venue      = get_post_meta( $event_id, '_emc_event_venue', true );
    $details    = array(
        'Event: ' . get_the_title( $event_id ),
        'Date: ' . ( $event_date ? date_i18n( 'l, j F Y', strtotime( $event_date ) ) : ( emc_get_event_display_day( $event_id ) ?: 'To be confirmed' ) ),
        'Time: ' . ( $event_time ?: 'To be confirmed' ),
        'Venue: ' . ( $venue ?: 'To be confirmed' ),
    );
    foreach ( $registration['fields'] as $field ) {
        $details[] = $field['label'] . ': ' . ( '' !== $field['value'] ? $field['value'] : 'Not supplied' );
    }
    $details[] = $payment_intent ? 'Payment: £' . $registration['amount'] . ' (paid)' : 'Payment: Free registration';
    if ( $payment_intent ) {
        $details[] = 'Stripe reference: ' . $payment_intent;
    }

    $settings = emc_event_registration_settings();
    $headers  = array( 'Content-Type: text/plain; charset=UTF-8' );
    if ( $email ) {
        $headers[] = 'Reply-To: ' . ( $name ?: $email ) . ' <' . $email . '>';
    }
    $notification_sent = emc_send_form_notification(
        'event_registration',
        sprintf( __( 'New event registration: %s', 'emc-theme' ), get_the_title( $event_id ) ),
        implode( "\n", $details ),
        $headers
    );
    $registration['notification_sent'] = $notification_sent ? 'sent' : 'failed';
    $registrations[ count( $registrations ) - 1 ] = $registration;
    update_option( 'emc_event_registrations', array_slice( $registrations, -2000 ), false );

    $confirmation_sent = false;
    if ( $settings['confirmation'] && $email ) {
        $confirmation  = sprintf( __( "Assalamu Alaikum %s,\n\nYour registration has been confirmed.", 'emc-theme' ), $name ?: __( 'guest', 'emc-theme' ) );
        $confirmation .= "\n\n" . implode( "\n", array_slice( $details, 0, 4 ) );
        $confirmation .= "\n" . sprintf( _n( 'Attendees: %d', 'Attendees: %d', $registration['attendees'], 'emc-theme' ), $registration['attendees'] );
        if ( $payment_intent ) {
            $confirmation .= "\nPayment: £" . $registration['amount'] . "\nStripe reference: " . $payment_intent;
        }
        $confirmation .= "\n\n" . __( 'If you need to change your registration, please reply to this email.', 'emc-theme' );
        $confirmation_sent = wp_mail( $email, sprintf( __( 'Registration confirmed: %s', 'emc-theme' ), get_the_title( $event_id ) ), $confirmation );
    }

    return array(
        'message' => $confirmation_sent
            ? __( 'Thank you. Your registration is confirmed and a confirmation has been sent to your email.', 'emc-theme' )
            : __( 'Thank you. Your registration has been confirmed.', 'emc-theme' ),
    );
}

/**
 * Verify Stripe success and finalize a paid event registration.
 */
function emc_ajax_event_confirm_registration() {
    check_ajax_referer( 'emc_event_registration', 'nonce' );

    $token = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
    $pi_id = sanitize_text_field( wp_unslash( $_POST['payment_intent'] ?? '' ) );
    if ( ! $token || ! $pi_id || ! emc_event_stripe_is_available() ) {
        wp_send_json_error( array( 'message' => __( 'The paid registration could not be verified.', 'emc-theme' ) ), 400 );
    }

    $registrations = get_option( 'emc_event_registrations', array() );
    if ( is_array( $registrations ) ) {
        foreach ( $registrations as $registration ) {
            if ( $pi_id === ( $registration['payment_intent'] ?? '' ) ) {
                wp_send_json_success( array( 'message' => __( 'Thank you. Your payment and registration have been confirmed.', 'emc-theme' ) ) );
            }
        }
    }

    $pending = get_transient( 'emc_evt_pending_' . $token );
    if ( ! is_array( $pending ) || $pi_id !== ( $pending['payment_intent'] ?? '' ) ) {
        wp_send_json_error( array( 'message' => __( 'This registration payment session has expired. Please contact the centre if payment was taken.', 'emc-theme' ) ), 410 );
    }

    $intent = emc_stripe_request( 'GET', 'payment_intents/' . rawurlencode( $pi_id ) );
    if ( is_wp_error( $intent ) ) {
        wp_send_json_error( array( 'message' => $intent->get_error_message() ), 502 );
    }
    $metadata = is_array( $intent ) && is_array( $intent['metadata'] ?? null ) ? $intent['metadata'] : array();
    $valid = is_array( $intent )
        && 'succeeded' === ( $intent['status'] ?? '' )
        && 'gbp' === strtolower( $intent['currency'] ?? '' )
        && absint( $intent['amount_received'] ?? $intent['amount'] ?? 0 ) === absint( $pending['amount_pence'] ?? 0 )
        && $token === ( $metadata['registration_token'] ?? '' )
        && absint( $pending['event_id'] ?? 0 ) === absint( $metadata['event_id'] ?? 0 );

    if ( ! $valid ) {
        wp_send_json_error( array( 'message' => __( 'Stripe has not confirmed the expected payment. Please try again or contact the centre.', 'emc-theme' ) ), 409 );
    }

    set_transient( $pending['rate_key'], 1, MINUTE_IN_SECONDS );
    $result = emc_event_store_completed_registration( $pending, array(
        'payment_intent' => $pi_id,
        'amount_pence'   => absint( $pending['amount_pence'] ),
    ) );
    delete_transient( 'emc_evt_pending_' . $token );
    wp_send_json_success( $result );
}
add_action( 'wp_ajax_emc_event_confirm_registration', 'emc_ajax_event_confirm_registration' );
add_action( 'wp_ajax_nopriv_emc_event_confirm_registration', 'emc_ajax_event_confirm_registration' );

/**
 * Add registrations and settings beneath Events in WordPress admin.
 */
function emc_event_registration_admin_menu() {
    add_submenu_page(
        null,
        __( 'Event Registrations', 'emc-theme' ),
        __( 'Registrations', 'emc-theme' ),
        'edit_posts',
        'emc-event-registrations',
        'emc_event_registrations_admin_page'
    );
    add_submenu_page(
        null,
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
                    <th><?php esc_html_e( 'Attendees', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Registration details', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Payment', 'emc-theme' ); ?></th>
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
                        <td><?php echo esc_html( $registration['attendees'] ?? 1 ); ?></td>
                        <td>
                            <?php if ( ! empty( $registration['fields'] ) && is_array( $registration['fields'] ) ) : ?>
                                <?php foreach ( $registration['fields'] as $field ) : ?>
                                    <strong><?php echo esc_html( $field['label'] ?? '' ); ?>:</strong>
                                    <?php
                                    $value = $field['value'] ?? '';
                                    if ( 'email' === ( $field['type'] ?? '' ) && is_email( $value ) ) {
                                        echo '<a href="mailto:' . esc_attr( $value ) . '">' . esc_html( $value ) . '</a>';
                                    } else {
                                        echo nl2br( esc_html( $value ?: '—' ) );
                                    }
                                    ?><br>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <strong><?php esc_html_e( 'Name:', 'emc-theme' ); ?></strong> <?php echo esc_html( $registration['name'] ?? '' ); ?><br>
                                <strong><?php esc_html_e( 'Email:', 'emc-theme' ); ?></strong> <?php echo esc_html( $registration['email'] ?? '' ); ?><br>
                                <strong><?php esc_html_e( 'Phone:', 'emc-theme' ); ?></strong> <?php echo esc_html( $registration['phone'] ?? '' ); ?><br>
                                <strong><?php esc_html_e( 'Notes:', 'emc-theme' ); ?></strong> <?php echo nl2br( esc_html( $registration['message'] ?? '' ) ); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( 'paid' === ( $registration['payment_status'] ?? '' ) ) : ?>
                                <strong><?php echo esc_html( '£' . ( $registration['amount'] ?? '0.00' ) ); ?></strong><br>
                                <code><?php echo esc_html( $registration['payment_intent'] ?? '' ); ?></code>
                            <?php else : ?>
                                <?php esc_html_e( 'Free', 'emc-theme' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="5"><?php esc_html_e( 'No registrations have been submitted yet.', 'emc-theme' ); ?></td></tr>
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
