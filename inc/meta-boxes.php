<?php
/**
 * EMC Theme — inc/meta-boxes.php
 * Native WordPress meta boxes for CPT custom fields.
 * Phase 4: Service, Team, Testimonial, Event.
 * Phase 5: Portfolio, Pricing, Case Study.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   Register Meta Boxes
   ========================================================================== */
function emc_register_meta_boxes() {

    // ── Service: Icon & Display Options ──────────────────────────────────
    add_meta_box(
        'emc_service_meta',
        __( 'Service Details', 'emc-theme' ),
        'emc_service_meta_cb',
        'emc_service',
        'side',
        'high'
    );

    // ── Team Member: Role & Social ────────────────────────────────────────
    add_meta_box(
        'emc_team_meta',
        __( 'Team Member Details', 'emc-theme' ),
        'emc_team_meta_cb',
        'emc_team',
        'normal',
        'high'
    );

    // ── Testimonial: Author, Role, Rating ────────────────────────────────
    add_meta_box(
        'emc_testimonial_meta',
        __( 'Testimonial Details', 'emc-theme' ),
        'emc_testimonial_meta_cb',
        'emc_testimonial',
        'normal',
        'high'
    );

    // ── Event: Date, Time, Venue ──────────────────────────────────────────
    add_meta_box(
        'emc_event_meta',
        __( 'Event Details', 'emc-theme' ),
        'emc_event_meta_cb',
        'emc_event',
        'normal',
        'high'
    );

    // ── Portfolio: Status & Dates ─────────────────────────────────────────
    add_meta_box(
        'emc_portfolio_meta',
        __( 'Project Details', 'emc-theme' ),
        'emc_portfolio_meta_cb',
        'emc_portfolio',
        'normal',
        'high'
    );

    // ── Pricing / Programmes ─────────────────────────────────────────────
    add_meta_box(
        'emc_pricing_meta',
        __( 'Programme Details', 'emc-theme' ),
        'emc_pricing_meta_cb',
        'emc_pricing',
        'normal',
        'high'
    );

    // ── Case Study / Impact Story ─────────────────────────────────────────
    add_meta_box(
        'emc_case_study_meta',
        __( 'Impact Story Details', 'emc-theme' ),
        'emc_case_study_meta_cb',
        'emc_case_study',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'emc_register_meta_boxes' );


/* ==========================================================================
   Render Callbacks
   ========================================================================== */

/** Service meta box */
function emc_service_meta_cb( $post ) {
    wp_nonce_field( 'emc_service_meta_save', 'emc_service_meta_nonce' );
    $icon     = get_post_meta( $post->ID, '_emc_service_icon',     true ) ?: 'fas fa-star';
    $order    = get_post_meta( $post->ID, '_emc_service_order',    true ) ?: 0;
    $featured = get_post_meta( $post->ID, '_emc_service_featured', true );
    ?>
    <p>
        <label for="emc_service_icon"><strong><?php esc_html_e( 'Font Awesome Icon Class', 'emc-theme' ); ?></strong></label><br>
        <input type="text" id="emc_service_icon" name="emc_service_icon"
               value="<?php echo esc_attr( $icon ); ?>" class="widefat"
               placeholder="fas fa-praying-hands">
        <span class="description"><?php esc_html_e( 'e.g. fas fa-mosque, fas fa-book-open', 'emc-theme' ); ?></span>
    </p>
    <p>
        <label for="emc_service_order"><strong><?php esc_html_e( 'Display Order', 'emc-theme' ); ?></strong></label><br>
        <input type="number" id="emc_service_order" name="emc_service_order"
               value="<?php echo esc_attr( $order ); ?>" class="small-text" min="0">
    </p>
    <p>
        <label>
            <input type="checkbox" name="emc_service_featured" value="1" <?php checked( $featured, '1' ); ?>>
            <?php esc_html_e( 'Show on homepage', 'emc-theme' ); ?>
        </label>
    </p>
    <?php
}

/** Team member meta box */
function emc_team_meta_cb( $post ) {
    wp_nonce_field( 'emc_team_meta_save', 'emc_team_meta_nonce' );
    $role      = get_post_meta( $post->ID, '_emc_team_role',      true );
    $email     = get_post_meta( $post->ID, '_emc_team_email',     true );
    $facebook  = get_post_meta( $post->ID, '_emc_team_facebook',  true );
    $twitter   = get_post_meta( $post->ID, '_emc_team_twitter',   true );
    $linkedin  = get_post_meta( $post->ID, '_emc_team_linkedin',  true );
    $order     = get_post_meta( $post->ID, '_emc_team_order',     true ) ?: 0;
    ?>
    <table class="form-table" style="margin:0">
        <tr>
            <th><label for="emc_team_role"><?php esc_html_e( 'Role / Title', 'emc-theme' ); ?></label></th>
            <td><input type="text" id="emc_team_role" name="emc_team_role"
                       value="<?php echo esc_attr( $role ); ?>" class="widefat"
                       placeholder="<?php esc_attr_e( 'e.g. Chairman, Youth Coordinator', 'emc-theme' ); ?>"></td>
        </tr>
        <tr>
            <th><label for="emc_team_email"><?php esc_html_e( 'Contact Email', 'emc-theme' ); ?></label></th>
            <td><input type="email" id="emc_team_email" name="emc_team_email"
                       value="<?php echo esc_attr( $email ); ?>" class="widefat"></td>
        </tr>
        <tr>
            <th><label for="emc_team_facebook"><?php esc_html_e( 'Facebook URL', 'emc-theme' ); ?></label></th>
            <td><input type="url" id="emc_team_facebook" name="emc_team_facebook"
                       value="<?php echo esc_url( $facebook ); ?>" class="widefat"></td>
        </tr>
        <tr>
            <th><label for="emc_team_twitter"><?php esc_html_e( 'X / Twitter URL', 'emc-theme' ); ?></label></th>
            <td><input type="url" id="emc_team_twitter" name="emc_team_twitter"
                       value="<?php echo esc_url( $twitter ); ?>" class="widefat"></td>
        </tr>
        <tr>
            <th><label for="emc_team_linkedin"><?php esc_html_e( 'LinkedIn URL', 'emc-theme' ); ?></label></th>
            <td><input type="url" id="emc_team_linkedin" name="emc_team_linkedin"
                       value="<?php echo esc_url( $linkedin ); ?>" class="widefat"></td>
        </tr>
        <tr>
            <th><label for="emc_team_order"><?php esc_html_e( 'Display Order', 'emc-theme' ); ?></label></th>
            <td><input type="number" id="emc_team_order" name="emc_team_order"
                       value="<?php echo esc_attr( $order ); ?>" class="small-text" min="0"></td>
        </tr>
    </table>
    <?php
}

/** Testimonial meta box */
function emc_testimonial_meta_cb( $post ) {
    wp_nonce_field( 'emc_testimonial_meta_save', 'emc_testimonial_meta_nonce' );
    $author = get_post_meta( $post->ID, '_emc_testimonial_author', true );
    $role   = get_post_meta( $post->ID, '_emc_testimonial_role',   true );
    $rating = get_post_meta( $post->ID, '_emc_testimonial_rating', true ) ?: 5;
    ?>
    <p>
        <label for="emc_testimonial_author"><strong><?php esc_html_e( 'Author Name', 'emc-theme' ); ?></strong></label><br>
        <input type="text" id="emc_testimonial_author" name="emc_testimonial_author"
               value="<?php echo esc_attr( $author ); ?>" class="widefat"
               placeholder="<?php esc_attr_e( 'e.g. Sister Fatima A.', 'emc-theme' ); ?>">
    </p>
    <p>
        <label for="emc_testimonial_role"><strong><?php esc_html_e( 'Author Role / Location', 'emc-theme' ); ?></strong></label><br>
        <input type="text" id="emc_testimonial_role" name="emc_testimonial_role"
               value="<?php echo esc_attr( $role ); ?>" class="widefat"
               placeholder="<?php esc_attr_e( 'e.g. Community Member, Chelmsford', 'emc-theme' ); ?>">
    </p>
    <p>
        <label for="emc_testimonial_rating"><strong><?php esc_html_e( 'Star Rating (1–5)', 'emc-theme' ); ?></strong></label><br>
        <input type="number" id="emc_testimonial_rating" name="emc_testimonial_rating"
               value="<?php echo esc_attr( $rating ); ?>" class="small-text" min="1" max="5">
    </p>
    <?php
}

/** Event meta box */
function emc_event_meta_cb( $post ) {
    wp_nonce_field( 'emc_event_meta_save', 'emc_event_meta_nonce' );
    $date     = get_post_meta( $post->ID, '_emc_event_date',       true );
    $end_date = get_post_meta( $post->ID, '_emc_event_end_date',   true );
    $time     = get_post_meta( $post->ID, '_emc_event_time',       true );
    $venue    = get_post_meta( $post->ID, '_emc_event_venue',      true );
    $link     = get_post_meta( $post->ID, '_emc_event_reg_link',   true );
    $capacity = get_post_meta( $post->ID, '_emc_event_capacity',   true );
    $featured = get_post_meta( $post->ID, '_emc_event_featured',   true );
    $registration_disabled = get_post_meta( $post->ID, '_emc_event_registration_disabled', true );
    ?>
    <table class="form-table" style="margin:0">
        <tr>
            <th><label for="emc_event_date"><?php esc_html_e( 'Start Date', 'emc-theme' ); ?></label></th>
            <td><input type="date" id="emc_event_date" name="emc_event_date"
                       value="<?php echo esc_attr( $date ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="emc_event_end_date"><?php esc_html_e( 'End Date', 'emc-theme' ); ?></label></th>
            <td><input type="date" id="emc_event_end_date" name="emc_event_end_date"
                       value="<?php echo esc_attr( $end_date ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="emc_event_time"><?php esc_html_e( 'Time', 'emc-theme' ); ?></label></th>
            <td><input type="text" id="emc_event_time" name="emc_event_time"
                       value="<?php echo esc_attr( $time ); ?>" class="regular-text"
                       placeholder="<?php esc_attr_e( 'e.g. 10:00 AM – 4:00 PM', 'emc-theme' ); ?>"></td>
        </tr>
        <tr>
            <th><label for="emc_event_venue"><?php esc_html_e( 'Venue', 'emc-theme' ); ?></label></th>
            <td><input type="text" id="emc_event_venue" name="emc_event_venue"
                       value="<?php echo esc_attr( $venue ); ?>" class="widefat"
                       placeholder="<?php esc_attr_e( 'e.g. Chelmsford Park, Main Hall', 'emc-theme' ); ?>"></td>
        </tr>
        <tr>
            <th><label for="emc_event_capacity"><?php esc_html_e( 'Capacity', 'emc-theme' ); ?></label></th>
            <td><input type="number" id="emc_event_capacity" name="emc_event_capacity"
                       value="<?php echo esc_attr( $capacity ); ?>" class="small-text" min="0">
                <?php if ( function_exists( 'emc_event_registered_places' ) ) : ?>
                    <p class="description">
                        <?php
                        printf(
                            esc_html__( '%d places currently registered. Use 0 for unlimited capacity.', 'emc-theme' ),
                            emc_event_registered_places( $post->ID )
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label for="emc_event_reg_link"><?php esc_html_e( 'Registration URL', 'emc-theme' ); ?></label></th>
            <td><input type="url" id="emc_event_reg_link" name="emc_event_reg_link"
                       value="<?php echo esc_url( $link ); ?>" class="widefat">
                <p class="description"><?php esc_html_e( 'Optional external registration link. The built-in form is enabled by default.', 'emc-theme' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Registration', 'emc-theme' ); ?></th>
            <td><label>
                <input type="checkbox" name="emc_event_registration_disabled" value="1" <?php checked( $registration_disabled, '1' ); ?>>
                <?php esc_html_e( 'Disable the built-in registration form for this event', 'emc-theme' ); ?>
            </label></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Featured', 'emc-theme' ); ?></th>
            <td><label>
                <input type="checkbox" name="emc_event_featured" value="1" <?php checked( $featured, '1' ); ?>>
                <?php esc_html_e( 'Show on homepage', 'emc-theme' ); ?>
            </label></td>
        </tr>
    </table>
    <?php
}


/** Portfolio meta box */
function emc_portfolio_meta_cb( $post ) {
    wp_nonce_field( 'emc_portfolio_meta_save', 'emc_portfolio_meta_nonce' );
    $status     = get_post_meta( $post->ID, '_emc_portfolio_status',     true ) ?: 'ongoing';
    $start_date = get_post_meta( $post->ID, '_emc_portfolio_start_date', true );
    $end_date   = get_post_meta( $post->ID, '_emc_portfolio_end_date',   true );
    $link       = get_post_meta( $post->ID, '_emc_portfolio_link',       true );
    $featured   = get_post_meta( $post->ID, '_emc_portfolio_featured',   true );
    $statuses   = array(
        'ongoing'   => __( 'Ongoing', 'emc-theme' ),
        'completed' => __( 'Completed', 'emc-theme' ),
        'planned'   => __( 'Planned', 'emc-theme' ),
    );
    ?>
    <table class="form-table" style="margin:0">
        <tr>
            <th><label for="emc_portfolio_status"><?php esc_html_e( 'Project Status', 'emc-theme' ); ?></label></th>
            <td>
                <select id="emc_portfolio_status" name="emc_portfolio_status">
                    <?php foreach ( $statuses as $val => $label ) : ?>
                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $status, $val ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="emc_portfolio_start_date"><?php esc_html_e( 'Start Date', 'emc-theme' ); ?></label></th>
            <td><input type="date" id="emc_portfolio_start_date" name="emc_portfolio_start_date"
                       value="<?php echo esc_attr( $start_date ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="emc_portfolio_end_date"><?php esc_html_e( 'End Date', 'emc-theme' ); ?></label></th>
            <td><input type="date" id="emc_portfolio_end_date" name="emc_portfolio_end_date"
                       value="<?php echo esc_attr( $end_date ); ?>" class="regular-text">
                <span class="description"><?php esc_html_e( 'Leave blank if ongoing', 'emc-theme' ); ?></span></td>
        </tr>
        <tr>
            <th><label for="emc_portfolio_link"><?php esc_html_e( 'External Link', 'emc-theme' ); ?></label></th>
            <td><input type="url" id="emc_portfolio_link" name="emc_portfolio_link"
                       value="<?php echo esc_url( $link ); ?>" class="widefat"
                       placeholder="https://"></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Featured', 'emc-theme' ); ?></th>
            <td><label>
                <input type="checkbox" name="emc_portfolio_featured" value="1" <?php checked( $featured, '1' ); ?>>
                <?php esc_html_e( 'Show on homepage / featured sections', 'emc-theme' ); ?>
            </label></td>
        </tr>
    </table>
    <?php
}

/** Pricing / Programme meta box */
function emc_pricing_meta_cb( $post ) {
    wp_nonce_field( 'emc_pricing_meta_save', 'emc_pricing_meta_nonce' );
    $price    = get_post_meta( $post->ID, '_emc_pricing_price',     true );
    $period   = get_post_meta( $post->ID, '_emc_pricing_period',    true ) ?: 'monthly';
    $features = get_post_meta( $post->ID, '_emc_pricing_features',  true );
    $featured = get_post_meta( $post->ID, '_emc_pricing_featured',  true );
    $cta      = get_post_meta( $post->ID, '_emc_pricing_cta_label', true ) ?: __( 'Sign Up', 'emc-theme' );
    $cta_url  = get_post_meta( $post->ID, '_emc_pricing_cta_url',   true );
    $periods  = array(
        'monthly'     => __( 'Per Month', 'emc-theme' ),
        'annually'    => __( 'Per Year', 'emc-theme' ),
        'one-time'    => __( 'One-Time', 'emc-theme' ),
        'per-session' => __( 'Per Session', 'emc-theme' ),
        'free'        => __( 'Free', 'emc-theme' ),
    );
    ?>
    <table class="form-table" style="margin:0">
        <tr>
            <th><label for="emc_pricing_price"><?php esc_html_e( 'Price', 'emc-theme' ); ?></label></th>
            <td><input type="text" id="emc_pricing_price" name="emc_pricing_price"
                       value="<?php echo esc_attr( $price ); ?>" class="regular-text"
                       placeholder="<?php esc_attr_e( 'e.g. £10 or Free', 'emc-theme' ); ?>"></td>
        </tr>
        <tr>
            <th><label for="emc_pricing_period"><?php esc_html_e( 'Billing Period', 'emc-theme' ); ?></label></th>
            <td>
                <select id="emc_pricing_period" name="emc_pricing_period">
                    <?php foreach ( $periods as $val => $label ) : ?>
                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $period, $val ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="emc_pricing_features"><?php esc_html_e( 'Features List', 'emc-theme' ); ?></label></th>
            <td>
                <textarea id="emc_pricing_features" name="emc_pricing_features"
                          class="widefat" rows="6"
                          placeholder="<?php esc_attr_e( 'One feature per line', 'emc-theme' ); ?>"><?php echo esc_textarea( $features ); ?></textarea>
                <span class="description"><?php esc_html_e( 'Enter one feature per line.', 'emc-theme' ); ?></span>
            </td>
        </tr>
        <tr>
            <th><label for="emc_pricing_cta_label"><?php esc_html_e( 'Button Label', 'emc-theme' ); ?></label></th>
            <td><input type="text" id="emc_pricing_cta_label" name="emc_pricing_cta_label"
                       value="<?php echo esc_attr( $cta ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="emc_pricing_cta_url"><?php esc_html_e( 'Button URL', 'emc-theme' ); ?></label></th>
            <td><input type="url" id="emc_pricing_cta_url" name="emc_pricing_cta_url"
                       value="<?php echo esc_url( $cta_url ); ?>" class="widefat"
                       placeholder="https://"></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Highlighted', 'emc-theme' ); ?></th>
            <td><label>
                <input type="checkbox" name="emc_pricing_featured" value="1" <?php checked( $featured, '1' ); ?>>
                <?php esc_html_e( 'Show as highlighted / recommended plan', 'emc-theme' ); ?>
            </label></td>
        </tr>
    </table>
    <?php
}

/** Case Study / Impact Story meta box */
function emc_case_study_meta_cb( $post ) {
    wp_nonce_field( 'emc_case_study_meta_save', 'emc_case_study_meta_nonce' );
    $date   = get_post_meta( $post->ID, '_emc_case_study_date',         true );
    $s1_num = get_post_meta( $post->ID, '_emc_case_study_stat1_num',    true );
    $s1_lbl = get_post_meta( $post->ID, '_emc_case_study_stat1_label',  true );
    $s2_num = get_post_meta( $post->ID, '_emc_case_study_stat2_num',    true );
    $s2_lbl = get_post_meta( $post->ID, '_emc_case_study_stat2_label',  true );
    $s3_num = get_post_meta( $post->ID, '_emc_case_study_stat3_num',    true );
    $s3_lbl = get_post_meta( $post->ID, '_emc_case_study_stat3_label',  true );
    $link   = get_post_meta( $post->ID, '_emc_case_study_link',         true );
    ?>
    <table class="form-table" style="margin:0">
        <tr>
            <th><label for="emc_case_study_date"><?php esc_html_e( 'Story Date', 'emc-theme' ); ?></label></th>
            <td><input type="date" id="emc_case_study_date" name="emc_case_study_date"
                       value="<?php echo esc_attr( $date ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <td colspan="2"><hr><strong><?php esc_html_e( 'Impact Statistics', 'emc-theme' ); ?></strong>
                <span class="description"> — <?php esc_html_e( 'Up to 3 stats displayed on the story card', 'emc-theme' ); ?></span>
            </td>
        </tr>
        <?php
        $stats = array(
            array( '1', $s1_num, $s1_lbl ),
            array( '2', $s2_num, $s2_lbl ),
            array( '3', $s3_num, $s3_lbl ),
        );
        foreach ( $stats as list( $n, $num, $lbl ) ) : ?>
        <tr>
            <th><?php printf( esc_html__( 'Stat %s', 'emc-theme' ), $n ); ?></th>
            <td>
                <input type="text" name="emc_case_study_stat<?php echo esc_attr( $n ); ?>_num"
                       value="<?php echo esc_attr( $num ); ?>" class="small-text"
                       placeholder="<?php esc_attr_e( '250+', 'emc-theme' ); ?>">
                <input type="text" name="emc_case_study_stat<?php echo esc_attr( $n ); ?>_label"
                       value="<?php echo esc_attr( $lbl ); ?>" class="regular-text"
                       placeholder="<?php esc_attr_e( 'Families Helped', 'emc-theme' ); ?>">
                <span class="description"><?php esc_html_e( 'Number &amp; label', 'emc-theme' ); ?></span>
            </td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <th><label for="emc_case_study_link"><?php esc_html_e( 'External Link', 'emc-theme' ); ?></label></th>
            <td><input type="url" id="emc_case_study_link" name="emc_case_study_link"
                       value="<?php echo esc_url( $link ); ?>" class="widefat"
                       placeholder="https://"></td>
        </tr>
    </table>
    <?php
}


/* ==========================================================================
   Save Meta Box Data
   ========================================================================== */
function emc_save_meta_boxes( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) )    return;

    // ── Service ─────────────────────────────────────────────────────────
    if ( isset( $_POST['emc_service_meta_nonce'] ) &&
         wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['emc_service_meta_nonce'] ) ), 'emc_service_meta_save' ) ) {
        update_post_meta( $post_id, '_emc_service_icon',
            sanitize_text_field( wp_unslash( $_POST['emc_service_icon'] ?? '' ) ) );
        update_post_meta( $post_id, '_emc_service_order',
            absint( $_POST['emc_service_order'] ?? 0 ) );
        update_post_meta( $post_id, '_emc_service_featured',
            isset( $_POST['emc_service_featured'] ) ? '1' : '' );
    }

    // ── Team ────────────────────────────────────────────────────────────
    if ( isset( $_POST['emc_team_meta_nonce'] ) &&
         wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['emc_team_meta_nonce'] ) ), 'emc_team_meta_save' ) ) {
        $team_fields = array(
            '_emc_team_role'     => 'sanitize_text_field',
            '_emc_team_email'    => 'sanitize_email',
            '_emc_team_facebook' => 'esc_url_raw',
            '_emc_team_twitter'  => 'esc_url_raw',
            '_emc_team_linkedin' => 'esc_url_raw',
        );
        foreach ( $team_fields as $key => $sanitizer ) {
            $field = str_replace( '_emc_team_', 'emc_team_', $key );
            update_post_meta( $post_id, $key,
                $sanitizer( wp_unslash( $_POST[ $field ] ?? '' ) ) );
        }
        update_post_meta( $post_id, '_emc_team_order',
            absint( $_POST['emc_team_order'] ?? 0 ) );
    }

    // ── Testimonial ─────────────────────────────────────────────────────
    if ( isset( $_POST['emc_testimonial_meta_nonce'] ) &&
         wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['emc_testimonial_meta_nonce'] ) ), 'emc_testimonial_meta_save' ) ) {
        update_post_meta( $post_id, '_emc_testimonial_author',
            sanitize_text_field( wp_unslash( $_POST['emc_testimonial_author'] ?? '' ) ) );
        update_post_meta( $post_id, '_emc_testimonial_role',
            sanitize_text_field( wp_unslash( $_POST['emc_testimonial_role'] ?? '' ) ) );
        update_post_meta( $post_id, '_emc_testimonial_rating',
            min( 5, max( 1, absint( $_POST['emc_testimonial_rating'] ?? 5 ) ) ) );
    }

    // ── Event ────────────────────────────────────────────────────────────
    if ( isset( $_POST['emc_event_meta_nonce'] ) &&
         wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['emc_event_meta_nonce'] ) ), 'emc_event_meta_save' ) ) {
        update_post_meta( $post_id, '_emc_event_date',
            sanitize_text_field( wp_unslash( $_POST['emc_event_date'] ?? '' ) ) );
        update_post_meta( $post_id, '_emc_event_end_date',
            sanitize_text_field( wp_unslash( $_POST['emc_event_end_date'] ?? '' ) ) );
        update_post_meta( $post_id, '_emc_event_time',
            sanitize_text_field( wp_unslash( $_POST['emc_event_time'] ?? '' ) ) );
        update_post_meta( $post_id, '_emc_event_venue',
            sanitize_text_field( wp_unslash( $_POST['emc_event_venue'] ?? '' ) ) );
        update_post_meta( $post_id, '_emc_event_reg_link',
            esc_url_raw( wp_unslash( $_POST['emc_event_reg_link'] ?? '' ) ) );
        update_post_meta( $post_id, '_emc_event_capacity',
            absint( $_POST['emc_event_capacity'] ?? 0 ) );
        update_post_meta( $post_id, '_emc_event_featured',
            isset( $_POST['emc_event_featured'] ) ? '1' : '' );
        update_post_meta( $post_id, '_emc_event_registration_disabled',
            isset( $_POST['emc_event_registration_disabled'] ) ? '1' : '' );
    }

    // ── Portfolio ───────────────────────────────────────────────────────
    if ( isset( $_POST['emc_portfolio_meta_nonce'] ) &&
         wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['emc_portfolio_meta_nonce'] ) ), 'emc_portfolio_meta_save' ) ) {
        $allowed_statuses = array( 'ongoing', 'completed', 'planned' );
        $status = sanitize_text_field( wp_unslash( $_POST['emc_portfolio_status'] ?? 'ongoing' ) );
        update_post_meta( $post_id, '_emc_portfolio_status',
            in_array( $status, $allowed_statuses, true ) ? $status : 'ongoing' );
        update_post_meta( $post_id, '_emc_portfolio_start_date',
            sanitize_text_field( wp_unslash( $_POST['emc_portfolio_start_date'] ?? '' ) ) );
        update_post_meta( $post_id, '_emc_portfolio_end_date',
            sanitize_text_field( wp_unslash( $_POST['emc_portfolio_end_date'] ?? '' ) ) );
        update_post_meta( $post_id, '_emc_portfolio_link',
            esc_url_raw( wp_unslash( $_POST['emc_portfolio_link'] ?? '' ) ) );
        update_post_meta( $post_id, '_emc_portfolio_featured',
            isset( $_POST['emc_portfolio_featured'] ) ? '1' : '' );
    }

    // ── Pricing ─────────────────────────────────────────────────────────
    if ( isset( $_POST['emc_pricing_meta_nonce'] ) &&
         wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['emc_pricing_meta_nonce'] ) ), 'emc_pricing_meta_save' ) ) {
        $allowed_periods = array( 'monthly', 'annually', 'one-time', 'per-session', 'free' );
        $period = sanitize_text_field( wp_unslash( $_POST['emc_pricing_period'] ?? 'monthly' ) );
        update_post_meta( $post_id, '_emc_pricing_price',
            sanitize_text_field( wp_unslash( $_POST['emc_pricing_price'] ?? '' ) ) );
        update_post_meta( $post_id, '_emc_pricing_period',
            in_array( $period, $allowed_periods, true ) ? $period : 'monthly' );
        update_post_meta( $post_id, '_emc_pricing_features',
            sanitize_textarea_field( wp_unslash( $_POST['emc_pricing_features'] ?? '' ) ) );
        update_post_meta( $post_id, '_emc_pricing_cta_label',
            sanitize_text_field( wp_unslash( $_POST['emc_pricing_cta_label'] ?? '' ) ) );
        update_post_meta( $post_id, '_emc_pricing_cta_url',
            esc_url_raw( wp_unslash( $_POST['emc_pricing_cta_url'] ?? '' ) ) );
        update_post_meta( $post_id, '_emc_pricing_featured',
            isset( $_POST['emc_pricing_featured'] ) ? '1' : '' );
    }

    // ── Case Study ──────────────────────────────────────────────────────
    if ( isset( $_POST['emc_case_study_meta_nonce'] ) &&
         wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['emc_case_study_meta_nonce'] ) ), 'emc_case_study_meta_save' ) ) {
        update_post_meta( $post_id, '_emc_case_study_date',
            sanitize_text_field( wp_unslash( $_POST['emc_case_study_date'] ?? '' ) ) );
        foreach ( array( 1, 2, 3 ) as $n ) {
            update_post_meta( $post_id, "_emc_case_study_stat{$n}_num",
                sanitize_text_field( wp_unslash( $_POST[ "emc_case_study_stat{$n}_num" ] ?? '' ) ) );
            update_post_meta( $post_id, "_emc_case_study_stat{$n}_label",
                sanitize_text_field( wp_unslash( $_POST[ "emc_case_study_stat{$n}_label" ] ?? '' ) ) );
        }
        update_post_meta( $post_id, '_emc_case_study_link',
            esc_url_raw( wp_unslash( $_POST['emc_case_study_link'] ?? '' ) ) );
    }
}
add_action( 'save_post', 'emc_save_meta_boxes' );
