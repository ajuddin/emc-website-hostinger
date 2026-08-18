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
 * Fields commonly used by event, class, and madrasa registrations.
 *
 * @return array<string,array>
 */
function emc_event_registration_field_presets() {
    return array(
        'first_name'       => array( 'label' => __( 'First name', 'emc-theme' ), 'type' => 'text', 'required' => true ),
        'last_name'        => array( 'label' => __( 'Last name', 'emc-theme' ), 'type' => 'text', 'required' => true ),
        'date_of_birth'    => array( 'label' => __( 'Student date of birth', 'emc-theme' ), 'type' => 'date', 'required' => true ),
        'parent_name'      => array( 'label' => __( 'Parent / guardian name', 'emc-theme' ), 'type' => 'text', 'required' => true ),
        'phone'            => array( 'label' => __( 'Contact number', 'emc-theme' ), 'type' => 'tel', 'required' => true ),
        'street_address'   => array( 'label' => __( 'Street address', 'emc-theme' ), 'type' => 'text', 'required' => false ),
        'postcode'         => array( 'label' => __( 'ZIP / postal code', 'emc-theme' ), 'type' => 'text', 'required' => false ),
        'country'          => array( 'label' => __( 'Country', 'emc-theme' ), 'type' => 'select', 'required' => false, 'options' => array( 'United Kingdom', 'Ireland', 'Other' ) ),
        'email'            => array( 'label' => __( 'Email address', 'emc-theme' ), 'type' => 'email', 'required' => true ),
        'emergency_name'   => array( 'label' => __( 'Emergency contact name', 'emc-theme' ), 'type' => 'text', 'required' => true ),
        'relationship'     => array( 'label' => __( 'Relationship', 'emc-theme' ), 'type' => 'text', 'required' => true ),
        'emergency_phone'  => array( 'label' => __( 'Emergency contact number', 'emc-theme' ), 'type' => 'tel', 'required' => true ),
        'medical_needs'    => array( 'label' => __( 'Food allergies, medical condition or special needs', 'emc-theme' ), 'type' => 'textarea', 'required' => false ),
        'photo_permission' => array( 'label' => __( 'Photo or video permission', 'emc-theme' ), 'type' => 'radio', 'required' => true, 'options' => array( 'Yes', 'No' ) ),
        'programmes'       => array( 'label' => __( 'Programme selection (tick all that apply)', 'emc-theme' ), 'type' => 'checkbox_group', 'required' => false, 'options' => array( 'Qur’an Beginners', 'Qur’an Recitation', 'Hifdh Programme', 'Islamic Studies' ) ),
        'school_year'      => array( 'label' => __( 'Student school year group', 'emc-theme' ), 'type' => 'select', 'required' => false, 'options' => array( 'Reception', 'Year 1', 'Year 2', 'Year 3', 'Year 4', 'Year 5', 'Year 6', 'Year 7', 'Year 8', 'Year 9', 'Year 10', 'Year 11', 'Year 12', 'Year 13' ) ),
        'previous_study'   => array( 'label' => __( 'Previous Islamic education (if any)', 'emc-theme' ), 'type' => 'textarea', 'required' => false ),
    );
}

/**
 * Return paid-registration configuration for an event.
 *
 * @param int $event_id Event post ID.
 * @return array
 */
function emc_event_payment_config( $event_id ) {
    $price = round( (float) get_post_meta( $event_id, '_emc_event_registration_price', true ), 2 );
    $saved_quantities = get_post_meta( $event_id, '_emc_event_ticket_quantities', true );
    $quantities       = is_array( $saved_quantities ) ? $saved_quantities : preg_split( '/[^0-9]+/', (string) $saved_quantities );
    $quantities       = array_values( array_unique( array_filter( array_map( 'absint', $quantities ), static function( $quantity ) {
        return $quantity >= 1 && $quantity <= 100;
    } ) ) );
    sort( $quantities, SORT_NUMERIC );
    if ( ! $quantities ) {
        $quantities = array( 1, 2, 3, 4 );
    }
    $saved_rules = get_post_meta( $event_id, '_emc_event_discount_rules', true );
    $rules       = array();

    if ( is_array( $saved_rules ) ) {
        foreach ( $saved_rules as $rule ) {
            $minimum = min( 100, max( 2, absint( $rule['minimum'] ?? 0 ) ) );
            $type    = in_array( $rule['type'] ?? '', array( 'fixed', 'percent' ), true ) ? $rule['type'] : 'fixed';
            $value   = round( (float) ( $rule['value'] ?? 0 ), 2 );
            if ( $value <= 0 || ( 'percent' === $type && $value > 100 ) ) {
                continue;
            }
            $rules[] = array( 'minimum' => $minimum, 'type' => $type, 'value' => $value );
        }
        usort( $rules, static function( $a, $b ) { return $a['minimum'] <=> $b['minimum']; } );
    }

    return array(
        'enabled' => '1' === get_post_meta( $event_id, '_emc_event_payment_enabled', true ) && $price >= 0.50,
        'price'   => $price,
        'pence'   => (int) round( $price * 100 ),
        'quantities' => $quantities,
        'discount_rules' => $rules,
    );
}

/**
 * Calculate the authoritative event total after the applicable quantity rule.
 * The rule with the highest qualifying ticket threshold is used.
 *
 * @param array $payment   Event payment configuration.
 * @param int   $attendees Number of tickets.
 * @return array{subtotal:int,discount:int,total:int,rule:?array}
 */
function emc_event_calculate_payment_total( $payment, $attendees ) {
    $attendees = min( 100, max( 1, absint( $attendees ) ) );
    $subtotal  = absint( $payment['pence'] ?? 0 ) * $attendees;
    $rule      = null;

    foreach ( $payment['discount_rules'] ?? array() as $candidate ) {
        if ( $attendees >= absint( $candidate['minimum'] ?? 0 ) ) {
            $rule = $candidate;
        }
    }

    $discount = 0;
    if ( $rule ) {
        $discount = 'percent' === $rule['type']
            ? (int) round( $subtotal * (float) $rule['value'] / 100 )
            : (int) round( (float) $rule['value'] * 100 );
        // Stripe requires a positive payable amount for this payment flow.
        $discount = min( max( 0, $discount ), max( 0, $subtotal - 50 ) );
    }

    return array(
        'subtotal' => $subtotal,
        'discount' => $discount,
        'total'    => $subtotal - $discount,
        'rule'     => $rule,
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

    return function_exists( 'emc_payment_license_is_active' ) && emc_payment_license_is_active();
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
 * Load WordPress's bundled sortable library on event edit screens.
 */
function emc_event_registration_admin_assets( $hook_suffix ) {
    if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( $screen && 'emc_event' === $screen->post_type ) {
        wp_enqueue_script( 'jquery-ui-sortable' );
    }
}
add_action( 'admin_enqueue_scripts', 'emc_event_registration_admin_assets' );

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
        'date'     => __( 'Date', 'emc-theme' ),
        'textarea' => __( 'Long text', 'emc-theme' ),
        'select'   => __( 'Dropdown', 'emc-theme' ),
        'radio'    => __( 'Radio choices', 'emc-theme' ),
        'checkbox_group' => __( 'Checkbox choices', 'emc-theme' ),
        'checkbox' => __( 'Checkbox', 'emc-theme' ),
    );
    $options = ! empty( $field['options'] ) && is_array( $field['options'] ) ? implode( "\n", $field['options'] ) : '';
    ?>
    <tr class="emc-event-field-row">
        <td class="emc-event-field-handle" title="<?php esc_attr_e( 'Drag to reorder', 'emc-theme' ); ?>"><span class="dashicons dashicons-move" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Drag to reorder', 'emc-theme' ); ?></span></td>
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
        <td><textarea class="widefat" data-field="options" name="emc_event_form_fields[<?php echo esc_attr( $index ); ?>][options]" rows="2" placeholder="<?php esc_attr_e( 'One choice per line', 'emc-theme' ); ?>"><?php echo esc_textarea( $options ); ?></textarea></td>
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
    $presets = emc_event_registration_field_presets();
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
        <span class="description"><?php esc_html_e( 'The final Stripe total is this price multiplied by the ticket quantity selected by the customer.', 'emc-theme' ); ?></span>
    </p>
    <p>
        <label for="emc-event-ticket-quantities"><strong><?php esc_html_e( 'Ticket quantity choices', 'emc-theme' ); ?></strong></label><br>
        <input type="text" id="emc-event-ticket-quantities" name="emc_event_ticket_quantities" value="<?php echo esc_attr( implode( ',', $payment['quantities'] ) ); ?>" class="regular-text" placeholder="1,2,3,4">
        <span class="description"><?php esc_html_e( 'Enter the quantities customers may select, separated by commas. Values can be from 1 to 100, for example: 1,2,3,4 or 1,3,5,10.', 'emc-theme' ); ?></span>
    </p>
    <div class="emc-event-discount-settings">
        <h4><?php esc_html_e( 'Quantity discounts', 'emc-theme' ); ?></h4>
        <p class="description"><?php esc_html_e( 'Optional. When several rules qualify, the rule with the highest minimum-ticket number is applied. Fixed discounts are deducted once from the complete order.', 'emc-theme' ); ?></p>
        <table class="widefat striped" id="emc-event-discount-rules">
            <thead><tr>
                <th><?php esc_html_e( 'Minimum tickets', 'emc-theme' ); ?></th>
                <th><?php esc_html_e( 'Discount type', 'emc-theme' ); ?></th>
                <th><?php esc_html_e( 'Discount value', 'emc-theme' ); ?></th>
                <th style="width:70px"></th>
            </tr></thead>
            <tbody>
                <?php foreach ( $payment['discount_rules'] as $index => $rule ) : ?>
                <tr>
                    <td><input data-discount-field="minimum" name="emc_event_discount_rules[<?php echo esc_attr( $index ); ?>][minimum]" type="number" min="2" max="100" step="1" value="<?php echo esc_attr( $rule['minimum'] ); ?>" class="small-text"></td>
                    <td><select data-discount-field="type" name="emc_event_discount_rules[<?php echo esc_attr( $index ); ?>][type]"><option value="fixed" <?php selected( $rule['type'], 'fixed' ); ?>><?php esc_html_e( 'Fixed amount off (£)', 'emc-theme' ); ?></option><option value="percent" <?php selected( $rule['type'], 'percent' ); ?>><?php esc_html_e( 'Percentage off (%)', 'emc-theme' ); ?></option></select></td>
                    <td><input data-discount-field="value" name="emc_event_discount_rules[<?php echo esc_attr( $index ); ?>][value]" type="number" min="0.01" max="10000" step="0.01" value="<?php echo esc_attr( $rule['value'] ); ?>" class="small-text"></td>
                    <td><button type="button" class="button-link-delete emc-remove-discount-rule"><?php esc_html_e( 'Remove', 'emc-theme' ); ?></button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><button type="button" class="button" id="emc-add-discount-rule"><?php esc_html_e( 'Add discount rule', 'emc-theme' ); ?></button></p>
    </div>
    <?php if ( '1' === get_post_meta( $post->ID, '_emc_event_payment_enabled', true ) && ! emc_event_stripe_is_available() ) : ?>
        <div class="notice notice-warning inline"><p><?php esc_html_e( 'Paid registration is enabled, but the EMC Payments Stripe connection is not currently available.', 'emc-theme' ); ?></p></div>
    <?php endif; ?>
    <?php if ( '1' === get_post_meta( $post->ID, '_emc_event_payment_enabled', true ) && $payment['price'] < 0.50 ) : ?>
        <div class="notice notice-warning inline"><p><?php esc_html_e( 'Enter a ticket price of at least £0.50 before paid registration can become active.', 'emc-theme' ); ?></p></div>
    <?php endif; ?>

    <hr>
    <h3><?php esc_html_e( 'Registration fields', 'emc-theme' ); ?></h3>
    <p class="description"><?php esc_html_e( 'Drag rows into any order. Add only the fields you need, mark them required, and enter dropdown, radio, or checkbox choices one per line.', 'emc-theme' ); ?></p>
    <table class="widefat striped" id="emc-event-form-builder">
        <thead><tr>
            <th style="width:38px"><span class="screen-reader-text"><?php esc_html_e( 'Order', 'emc-theme' ); ?></span></th>
            <th><?php esc_html_e( 'Field name', 'emc-theme' ); ?></th>
            <th style="width:150px"><?php esc_html_e( 'Type', 'emc-theme' ); ?></th>
            <th><?php esc_html_e( 'Choices', 'emc-theme' ); ?></th>
            <th style="width:75px;text-align:center"><?php esc_html_e( 'Required', 'emc-theme' ); ?></th>
            <th style="width:70px"></th>
        </tr></thead>
        <tbody>
            <?php foreach ( $fields as $index => $field ) { emc_event_registration_builder_row( $index, $field ); } ?>
        </tbody>
    </table>
    <p class="emc-event-builder-actions">
        <select id="emc-event-field-preset">
            <option value=""><?php esc_html_e( 'Choose a common field…', 'emc-theme' ); ?></option>
            <?php foreach ( $presets as $key => $preset ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $preset['label'] ); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="button button-secondary" id="emc-add-preset-event-field"><?php esc_html_e( 'Add selected field', 'emc-theme' ); ?></button>
        <button type="button" class="button" id="emc-add-event-field"><?php esc_html_e( 'Add custom field', 'emc-theme' ); ?></button>
    </p>
    <style>
        #emc-event-form-builder .emc-event-field-handle{cursor:move;text-align:center;vertical-align:middle;color:#646970}
        #emc-event-form-builder .emc-event-field-handle:hover{color:#2271b1}
        #emc-event-form-builder .ui-sortable-helper{display:table;background:#fff;box-shadow:0 3px 12px rgba(0,0,0,.16)}
        #emc-event-form-builder .emc-event-field-placeholder{height:58px;background:#f0f6fc}
        .emc-event-builder-actions{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
        .emc-event-discount-settings{max-width:850px;margin:18px 0;padding:16px;border:1px solid #dcdcde;background:#f6f7f7}
        .emc-event-discount-settings h4{margin:0 0 6px;font-size:14px}
        #emc-event-discount-rules{margin-top:12px}
    </style>
    <script>
    (() => {
        const table = document.querySelector('#emc-event-form-builder tbody');
        const addButton = document.getElementById('emc-add-event-field');
        const presetSelect = document.getElementById('emc-event-field-preset');
        const presetButton = document.getElementById('emc-add-preset-event-field');
        const presets = <?php echo wp_json_encode( $presets ); ?>;
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

        const addRow = (field = {}) => {
            const row = document.createElement('tr');
            row.className = 'emc-event-field-row';
            const key = field.key || `field_${Date.now()}`;
            const label = field.label || '';
            const type = field.type || 'text';
            const options = Array.isArray(field.options) ? field.options.join('\n') : '';
            row.innerHTML = `
                <td class="emc-event-field-handle" title="Drag to reorder"><span class="dashicons dashicons-move" aria-hidden="true"></span><span class="screen-reader-text">Drag to reorder</span></td>
                <td><input type="hidden" data-field="key"><input type="text" class="widefat" data-field="label" required></td>
                <td><select class="widefat" data-field="type"><option value="text">Text</option><option value="email">Email</option><option value="tel">Telephone</option><option value="number">Number</option><option value="date">Date</option><option value="textarea">Long text</option><option value="select">Dropdown</option><option value="radio">Radio choices</option><option value="checkbox_group">Checkbox choices</option><option value="checkbox">Checkbox</option></select></td>
                <td><textarea class="widefat" data-field="options" rows="2" placeholder="One choice per line"></textarea></td>
                <td style="text-align:center"><input type="checkbox" data-field="required" value="1"></td>
                <td><button type="button" class="button-link-delete emc-remove-event-field">Remove</button></td>`;
            row.querySelector('[data-field="key"]').value = key;
            row.querySelector('[data-field="label"]').value = label;
            row.querySelector('[data-field="type"]').value = type;
            row.querySelector('[data-field="options"]').value = options;
            row.querySelector('[data-field="required"]').checked = Boolean(field.required);
            table.appendChild(row);
            bindRemove(row);
            reindex();
            row.querySelector('[data-field="label"]').focus();
        };

        addButton.addEventListener('click', () => addRow());
        presetButton?.addEventListener('click', () => {
            const key = presetSelect?.value;
            if (!key || !presets[key]) return;
            addRow({...presets[key], key});
            presetSelect.value = '';
        });

        if (window.jQuery?.fn?.sortable) {
            window.jQuery(table).sortable({
                axis: 'y',
                handle: '.emc-event-field-handle',
                placeholder: 'emc-event-field-placeholder',
                forcePlaceholderSize: true,
                update: reindex
            });
        }

        const discountTable = document.querySelector('#emc-event-discount-rules tbody');
        const addDiscountButton = document.getElementById('emc-add-discount-rule');
        const reindexDiscounts = () => {
            discountTable?.querySelectorAll('tr').forEach((row, index) => {
                row.querySelectorAll('[data-discount-field]').forEach(input => {
                    input.name = `emc_event_discount_rules[${index}][${input.dataset.discountField}]`;
                });
            });
        };
        const bindDiscountRemove = row => row.querySelector('.emc-remove-discount-rule')?.addEventListener('click', () => {
            row.remove();
            reindexDiscounts();
        });
        discountTable?.querySelectorAll('tr').forEach(bindDiscountRemove);
        addDiscountButton?.addEventListener('click', () => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input data-discount-field="minimum" type="number" min="2" max="100" step="1" value="3" class="small-text"></td>
                <td><select data-discount-field="type"><option value="fixed">Fixed amount off (£)</option><option value="percent">Percentage off (%)</option></select></td>
                <td><input data-discount-field="value" type="number" min="0.01" max="10000" step="0.01" value="5.00" class="small-text"></td>
                <td><button type="button" class="button-link-delete emc-remove-discount-rule">Remove</button></td>`;
            discountTable.appendChild(row);
            bindDiscountRemove(row);
            reindexDiscounts();
            row.querySelector('[data-discount-field="minimum"]').focus();
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

    $allowed_types = array( 'text', 'email', 'tel', 'number', 'date', 'textarea', 'select', 'radio', 'checkbox_group', 'checkbox' );
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
        if ( in_array( $type, array( 'select', 'radio', 'checkbox_group' ), true ) ) {
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

    $raw_quantities = sanitize_text_field( wp_unslash( $_POST['emc_event_ticket_quantities'] ?? '' ) );
    $quantities     = preg_split( '/[^0-9]+/', $raw_quantities );
    $quantities     = array_values( array_unique( array_filter( array_map( 'absint', $quantities ), static function( $quantity ) {
        return $quantity >= 1 && $quantity <= 100;
    } ) ) );
    sort( $quantities, SORT_NUMERIC );
    update_post_meta( $post_id, '_emc_event_ticket_quantities', $quantities ?: array( 1 ) );

    $submitted_discounts = isset( $_POST['emc_event_discount_rules'] ) && is_array( $_POST['emc_event_discount_rules'] )
        ? wp_unslash( $_POST['emc_event_discount_rules'] )
        : array();
    $discount_rules = array();
    foreach ( array_slice( $submitted_discounts, 0, 20 ) as $rule ) {
        $minimum = absint( $rule['minimum'] ?? 0 );
        $type    = sanitize_key( $rule['type'] ?? '' );
        $value   = round( (float) ( $rule['value'] ?? 0 ), 2 );
        if ( $minimum < 2 || $minimum > 100 || ! in_array( $type, array( 'fixed', 'percent' ), true ) || $value <= 0 ) {
            continue;
        }
        $value = 'percent' === $type ? min( 100, $value ) : min( 10000, $value );
        $discount_rules[ $minimum ] = array( 'minimum' => $minimum, 'type' => $type, 'value' => $value );
    }
    ksort( $discount_rules, SORT_NUMERIC );
    update_post_meta( $post_id, '_emc_event_discount_rules', array_values( $discount_rules ) );
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
    $fields     = emc_event_registration_fields( $event_id );
    $payment    = emc_event_payment_config( $event_id );
    $ticket_quantities = $payment['enabled'] && $capacity
        ? array_values( array_filter( $payment['quantities'], static function( $quantity ) use ( $remaining ) { return $quantity <= $remaining; } ) )
        : $payment['quantities'];
    $is_full    = $capacity && $remaining < 1;
    $quantity_unavailable = $payment['enabled'] && ! $ticket_quantities;
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

        <?php if ( $is_full || $quantity_unavailable ) : ?>
            <div class="event-registration-full" role="status">
                <i class="fas fa-users" aria-hidden="true"></i>
                <?php echo esc_html( $is_full ? __( 'This event is currently fully booked.', 'emc-theme' ) : __( 'The remaining capacity is below the configured ticket quantities. Please contact the centre.', 'emc-theme' ) ); ?>
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
                    <div>
                        <span><?php esc_html_e( 'Ticket price', 'emc-theme' ); ?></span>
                        <?php if ( $payment['discount_rules'] ) : ?>
                            <small class="event-discount-offers">
                                <?php foreach ( $payment['discount_rules'] as $rule ) : ?>
                                    <span><?php
                                        echo esc_html( sprintf(
                                            'Buy %d+: %s off',
                                            $rule['minimum'],
                                            'percent' === $rule['type'] ? number_format_i18n( $rule['value'], 0 ) . '%' : '£' . number_format_i18n( $rule['value'], 2 )
                                        ) );
                                    ?></span>
                                <?php endforeach; ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    <strong><?php echo esc_html( '£' . number_format( $payment['price'], 2 ) ); ?> <?php esc_html_e( 'per attendee', 'emc-theme' ); ?></strong>
                </div>
            <?php endif; ?>

            <form class="emc-event-registration-form" method="post" novalidate data-paid="<?php echo $payment['enabled'] ? '1' : '0'; ?>" data-ticket-price="<?php echo esc_attr( $payment['pence'] ); ?>" data-discount-rules="<?php echo esc_attr( wp_json_encode( $payment['discount_rules'] ) ); ?>" data-stripe-key="<?php echo esc_attr( $payment['enabled'] && $stripe_ok ? emc_stripe_pub_key() : '' ); ?>">
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
                        if ( ! $key || ( $payment['enabled'] && 'attendees' === $key ) ) {
                            continue;
                        }
                        $field_id = 'emc-event-' . $event_id . '-' . $key;
                        $class    = in_array( $type, array( 'textarea', 'checkbox', 'radio', 'checkbox_group' ), true ) ? ' event-form-field-wide' : '';
                        $class   .= 'checkbox' === $type ? ' event-form-field-checkbox' : '';
                    ?>
                        <?php if ( in_array( $type, array( 'radio', 'checkbox_group' ), true ) ) : ?>
                            <fieldset class="event-form-field event-form-choice-group<?php echo esc_attr( $class ); ?>" <?php echo $required && 'checkbox_group' === $type ? 'data-choice-required="1"' : ''; ?>>
                                <legend><?php echo esc_html( $field['label'] ); ?><?php echo $required ? ' *' : ''; ?></legend>
                                <div class="event-form-choices">
                                    <?php foreach ( $field['options'] ?? array() as $option_index => $option ) :
                                        $option_id = $field_id . '-' . $option_index;
                                        $input_type = 'radio' === $type ? 'radio' : 'checkbox';
                                        $input_name = 'radio' === $type ? "fields[{$key}]" : "fields[{$key}][]";
                                    ?>
                                        <label for="<?php echo esc_attr( $option_id ); ?>">
                                            <input id="<?php echo esc_attr( $option_id ); ?>" type="<?php echo esc_attr( $input_type ); ?>" name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $option ); ?>" <?php echo $required && 'radio' === $type && 0 === $option_index ? 'required' : ''; ?>>
                                            <span><?php echo esc_html( $option ); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        <?php else : ?>
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
                                    <input id="<?php echo esc_attr( $field_id ); ?>" type="<?php echo esc_attr( $type ); ?>" name="fields[<?php echo esc_attr( $key ); ?>]" autocomplete="<?php echo esc_attr( $autocomplete ); ?>" <?php echo $is_attendees ? 'value="1" min="1" max="' . esc_attr( $capacity ? max( 1, min( 100, $remaining ) ) : 100 ) . '"' : ''; ?> <?php echo $required ? 'required' : ''; ?>>
                                <?php endif; ?>
                            <?php endif; ?>
                        </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <?php if ( $payment['enabled'] ) : ?>
                    <label class="event-form-field event-ticket-quantity" for="emc-event-ticket-quantity-<?php echo esc_attr( $event_id ); ?>">
                        <span><?php esc_html_e( 'Number of tickets', 'emc-theme' ); ?> *</span>
                        <select id="emc-event-ticket-quantity-<?php echo esc_attr( $event_id ); ?>" name="ticket_quantity" data-event-ticket-quantity required>
                            <?php foreach ( $ticket_quantities as $quantity ) : ?>
                                <option value="<?php echo esc_attr( $quantity ); ?>"><?php echo esc_html( sprintf( _n( '%d ticket', '%d tickets', $quantity, 'emc-theme' ), $quantity ) ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>

                <?php if ( $payment['enabled'] && $stripe_ok ) : ?>
                    <div class="event-payment-box">
                        <div class="event-payment-discount" data-event-payment-discount hidden>
                            <span><?php esc_html_e( 'Quantity discount', 'emc-theme' ); ?></span>
                            <strong data-event-payment-discount-amount></strong>
                        </div>
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

    $payment         = emc_event_payment_config( $event_id );
    $submitted_fields = isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ? $_POST['fields'] : array();
    if ( $payment['enabled'] ) {
        $ticket_quantity = absint( $_POST['ticket_quantity'] ?? 0 );
        if ( ! in_array( $ticket_quantity, $payment['quantities'], true ) ) {
            wp_send_json_error( array( 'message' => __( 'Please select an available ticket quantity.', 'emc-theme' ) ), 400 );
        }
        // Preserve compatibility with existing attendee fields and stored records.
        $submitted_fields['attendees'] = $ticket_quantity;
    }

    $validated = emc_event_validate_registration_fields( $event_id, $submitted_fields );
    if ( is_wp_error( $validated ) ) {
        wp_send_json_error( array( 'message' => $validated->get_error_message() ), 400 );
    }
    if ( $payment['enabled'] ) {
        $validated['attendees'] = $ticket_quantity;
        if ( empty( $validated['fields']['attendees'] ) ) {
            $validated['fields']['attendees'] = array(
                'label' => __( 'Number of tickets', 'emc-theme' ),
                'type'  => 'number',
                'value' => (string) $ticket_quantity,
            );
        }
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

    if ( ! $payment['enabled'] ) {
        set_transient( $rate_key, 1, MINUTE_IN_SECONDS );
        $result = emc_event_store_completed_registration( $pending );
        wp_send_json_success( $result );
    }

    if ( ! emc_event_stripe_is_available() ) {
        wp_send_json_error( array( 'message' => __( 'Online payment is temporarily unavailable. Please contact the centre.', 'emc-theme' ) ), 503 );
    }

    $token        = wp_generate_uuid4();
    $calculation  = emc_event_calculate_payment_total( $payment, $attendees );
    $amount_pence = $calculation['total'];
    $intent_body = array(
        'amount'                               => $amount_pence,
        'currency'                             => 'gbp',
        'automatic_payment_methods[enabled]'   => 'true',
        'description'                          => sprintf( 'Event registration: %s', get_the_title( $event_id ) ),
        'metadata[source]'                     => 'EMC Event Registration',
        'metadata[event_id]'                   => $event_id,
        'metadata[registration_token]'         => $token,
        'metadata[attendees]'                  => $attendees,
        'metadata[subtotal_pence]'             => $calculation['subtotal'],
        'metadata[discount_pence]'             => $calculation['discount'],
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
    $pending['subtotal_pence']    = $calculation['subtotal'];
    $pending['discount_pence']    = $calculation['discount'];
    if ( ! set_transient( 'emc_evt_pending_' . $token, $pending, 30 * MINUTE_IN_SECONDS ) ) {
        wp_send_json_error( array( 'message' => __( 'The registration payment session could not be saved. No payment has been taken.', 'emc-theme' ) ), 500 );
    }

    wp_send_json_success( array(
        'requiresPayment' => true,
        'clientSecret'    => $intent['client_secret'],
        'paymentIntent'   => $intent['id'],
        'token'           => $token,
        'amount'          => '£' . number_format( $amount_pence / 100, 2 ),
        'discount'        => $calculation['discount'] ? '£' . number_format( $calculation['discount'] / 100, 2 ) : '',
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
        } elseif ( 'checkbox_group' === $type ) {
            $selected = is_array( $raw ) ? $raw : array();
            $selected = array_map( 'sanitize_text_field', array_slice( $selected, 0, 50 ) );
            $selected = array_values( array_intersect( $field['options'] ?? array(), $selected ) );
            $value    = implode( ', ', $selected );
        } elseif ( 'textarea' === $type ) {
            $value = sanitize_textarea_field( $raw );
        } elseif ( 'email' === $type ) {
            $value = sanitize_email( $raw );
            if ( '' !== trim( (string) $raw ) && ! is_email( $value ) ) {
                return new WP_Error( 'invalid_email', sprintf( __( 'Please enter a valid value for “%s”.', 'emc-theme' ), $field['label'] ) );
            }
        } elseif ( 'number' === $type ) {
            $value = (string) absint( $raw );
        } elseif ( in_array( $type, array( 'select', 'radio' ), true ) ) {
            $value = sanitize_text_field( $raw );
            if ( '' !== $value && ! in_array( $value, $field['options'] ?? array(), true ) ) {
                return new WP_Error( 'invalid_option', sprintf( __( 'Please select a valid option for “%s”.', 'emc-theme' ), $field['label'] ) );
            }
        } elseif ( 'date' === $type ) {
            $value = sanitize_text_field( $raw );
            if ( '' !== $value ) {
                $parts = array_map( 'absint', explode( '-', $value ) );
                if ( 3 !== count( $parts ) || ! checkdate( $parts[1], $parts[2], $parts[0] ) ) {
                    return new WP_Error( 'invalid_date', sprintf( __( 'Please enter a valid date for "%s".', 'emc-theme' ), $field['label'] ) );
                }
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
            $identity['attendees'] = min( 100, max( 1, absint( $value ) ) );
            $values[ $key ]['value'] = (string) $identity['attendees'];
        }
    }

    if ( ! $identity['name'] && ( ! empty( $values['first_name']['value'] ) || ! empty( $values['last_name']['value'] ) ) ) {
        $identity['name'] = trim( ( $values['first_name']['value'] ?? '' ) . ' ' . ( $values['last_name']['value'] ?? '' ) );
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
        'attendees'      => min( 100, max( 1, absint( $pending['attendees'] ?? 1 ) ) ),
        'message'        => sanitize_textarea_field( $pending['message'] ?? '' ),
        'fields'         => is_array( $pending['fields'] ?? null ) ? $pending['fields'] : array(),
        'payment_status' => $payment_intent ? 'paid' : 'free',
        'payment_intent' => $payment_intent,
        'subtotal'       => $payment_intent ? number_format( absint( $payment['subtotal_pence'] ?? $payment['amount_pence'] ?? 0 ) / 100, 2, '.', '' ) : '0.00',
        'discount'       => $payment_intent ? number_format( absint( $payment['discount_pence'] ?? 0 ) / 100, 2, '.', '' ) : '0.00',
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
    if ( $payment_intent && (float) $registration['discount'] > 0 ) {
        $details[] = 'Quantity discount: £' . $registration['discount'] . ' (subtotal £' . $registration['subtotal'] . ')';
    }
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
            if ( (float) $registration['discount'] > 0 ) {
                $confirmation .= "\nQuantity discount: £" . $registration['discount'];
            }
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
        'subtotal_pence' => absint( $pending['subtotal_pence'] ?? $pending['amount_pence'] ),
        'discount_pence' => absint( $pending['discount_pence'] ?? 0 ),
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
                                <?php if ( (float) ( $registration['discount'] ?? 0 ) > 0 ) : ?>
                                    <small><?php echo esc_html( sprintf( __( '£%1$s discount from £%2$s', 'emc-theme' ), $registration['discount'], $registration['subtotal'] ?? $registration['amount'] ) ); ?></small><br>
                                <?php endif; ?>
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
