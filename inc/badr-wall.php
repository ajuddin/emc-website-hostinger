<?php
/**
 * Badr Wall tile records, administration, and page provisioning.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/** Register the private, administrator-managed tile post type. */
function emc_register_badr_tiles() {
    register_post_type( 'emc_badr_tile', array(
        'labels' => array(
            'name'               => __( 'Badr Wall Tiles', 'emc-theme' ),
            'singular_name'      => __( 'Badr Wall Tile', 'emc-theme' ),
            'add_new_item'       => __( 'Add Badr Wall Tile', 'emc-theme' ),
            'edit_item'          => __( 'Edit Badr Wall Tile', 'emc-theme' ),
            'all_items'          => __( 'All Badr Wall Tiles', 'emc-theme' ),
            'search_items'       => __( 'Search Badr Wall Tiles', 'emc-theme' ),
            'not_found'          => __( 'No Badr Wall tiles found.', 'emc-theme' ),
            'not_found_in_trash' => __( 'No Badr Wall tiles found in Trash.', 'emc-theme' ),
        ),
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'menu_icon'           => 'dashicons-awards',
        'menu_position'       => 11,
        'supports'            => array( 'title', 'page-attributes' ),
    ) );
}
add_action( 'init', 'emc_register_badr_tiles' );

/** Use a helpful title prompt in the tile editor. */
function emc_badr_tile_title_placeholder( $title, $post ) {
    if ( $post && 'emc_badr_tile' === $post->post_type ) {
        return __( 'Name to display on the tile', 'emc-theme' );
    }

    return $title;
}
add_filter( 'enter_title_here', 'emc_badr_tile_title_placeholder', 10, 2 );

/** Register tile details. */
function emc_register_badr_tile_meta_box() {
    add_meta_box(
        'emc_badr_tile_details',
        __( 'Tile Details', 'emc-theme' ),
        'emc_badr_tile_meta_box',
        'emc_badr_tile',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'emc_register_badr_tile_meta_box' );

/** Render the tile details editor. */
function emc_badr_tile_meta_box( $post ) {
    $tier       = get_post_meta( $post->ID, '_emc_badr_tier', true ) ?: 'founder';
    $dedication = get_post_meta( $post->ID, '_emc_badr_dedication', true );
    $anonymous  = '1' === get_post_meta( $post->ID, '_emc_badr_anonymous', true );
    $number     = absint( get_post_meta( $post->ID, '_emc_badr_tile_number', true ) );
    $payment    = get_post_meta( $post->ID, '_emc_badr_payment_ref', true );

    wp_nonce_field( 'emc_badr_tile_save', 'emc_badr_tile_nonce' );
    ?>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="emc_badr_tier"><?php esc_html_e( 'Recognition level', 'emc-theme' ); ?></label></th>
            <td>
                <select id="emc_badr_tier" name="emc_badr_tier">
                    <option value="founder" <?php selected( $tier, 'founder' ); ?>><?php echo esc_html( emc_site_setting( 'emc_badr_tier1_label', __( 'Founder of the Centre', 'emc-theme' ) ) ); ?></option>
                    <option value="co-founder" <?php selected( $tier, 'co-founder' ); ?>><?php echo esc_html( emc_site_setting( 'emc_badr_tier2_label', __( 'Co-Founder of the Centre', 'emc-theme' ) ) ); ?></option>
                    <option value="sponsor" <?php selected( $tier, 'sponsor' ); ?>><?php esc_html_e( 'Sponsored Tile', 'emc-theme' ); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="emc_badr_dedication"><?php esc_html_e( 'Short dedication', 'emc-theme' ); ?></label></th>
            <td>
                <textarea id="emc_badr_dedication" name="emc_badr_dedication" class="large-text" rows="3" maxlength="180"><?php echo esc_textarea( $dedication ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Up to 180 characters. For example: In loving memory of our parents.', 'emc-theme' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="emc_badr_tile_number"><?php esc_html_e( 'Tile number', 'emc-theme' ); ?></label></th>
            <td><input type="number" id="emc_badr_tile_number" name="emc_badr_tile_number" min="1" max="313" value="<?php echo esc_attr( $number ); ?>"></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Public name', 'emc-theme' ); ?></th>
            <td>
                <label><input type="checkbox" name="emc_badr_anonymous" value="1" <?php checked( $anonymous ); ?>> <?php esc_html_e( 'Display this tile as Anonymous', 'emc-theme' ); ?></label>
            </td>
        </tr>
        <?php if ( $payment ) : ?>
        <tr>
            <th><?php esc_html_e( 'Payment reference', 'emc-theme' ); ?></th>
            <td><code><?php echo esc_html( $payment ); ?></code></td>
        </tr>
        <?php endif; ?>
    </table>
    <?php
}

/** Save tile details. */
function emc_save_badr_tile( $post_id ) {
    if ( ! isset( $_POST['emc_badr_tile_nonce'] ) ) {
        return;
    }

    $nonce = sanitize_text_field( wp_unslash( $_POST['emc_badr_tile_nonce'] ) );
    if ( ! wp_verify_nonce( $nonce, 'emc_badr_tile_save' ) ||
        ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
        ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $tier = sanitize_key( wp_unslash( $_POST['emc_badr_tier'] ?? 'founder' ) );
    if ( ! in_array( $tier, array( 'founder', 'co-founder', 'sponsor' ), true ) ) {
        $tier = 'founder';
    }

    update_post_meta( $post_id, '_emc_badr_tier', $tier );
    update_post_meta(
        $post_id,
        '_emc_badr_dedication',
        wp_html_excerpt( sanitize_textarea_field( wp_unslash( $_POST['emc_badr_dedication'] ?? '' ) ), 180, '' )
    );
    update_post_meta( $post_id, '_emc_badr_tile_number', min( 313, absint( $_POST['emc_badr_tile_number'] ?? 0 ) ) );
    update_post_meta( $post_id, '_emc_badr_anonymous', isset( $_POST['emc_badr_anonymous'] ) ? '1' : '' );
}
add_action( 'save_post_emc_badr_tile', 'emc_save_badr_tile' );

/** Add useful columns to the tile manager. */
function emc_badr_tile_columns( $columns ) {
    return array(
        'cb'              => $columns['cb'],
        'title'           => __( 'Tile Name', 'emc-theme' ),
        'badr_tier'       => __( 'Level', 'emc-theme' ),
        'badr_dedication' => __( 'Dedication', 'emc-theme' ),
        'badr_number'     => __( 'Tile', 'emc-theme' ),
        'date'            => $columns['date'],
    );
}
add_filter( 'manage_emc_badr_tile_posts_columns', 'emc_badr_tile_columns' );

/** Render tile manager columns. */
function emc_badr_tile_column( $column, $post_id ) {
    if ( 'badr_tier' === $column ) {
        $tier = get_post_meta( $post_id, '_emc_badr_tier', true );
        $labels = array(
            'founder'    => emc_site_setting( 'emc_badr_tier1_label', __( 'Founder', 'emc-theme' ) ),
            'co-founder' => emc_site_setting( 'emc_badr_tier2_label', __( 'Co-Founder', 'emc-theme' ) ),
            'sponsor'    => __( 'Sponsor', 'emc-theme' ),
        );
        echo esc_html( $labels[ $tier ] ?? $labels['founder'] );
    } elseif ( 'badr_dedication' === $column ) {
        echo esc_html( wp_trim_words( get_post_meta( $post_id, '_emc_badr_dedication', true ), 12 ) );
    } elseif ( 'badr_number' === $column ) {
        $number = absint( get_post_meta( $post_id, '_emc_badr_tile_number', true ) );
        echo $number ? esc_html( '#' . $number ) : '&mdash;';
    }
}
add_action( 'manage_emc_badr_tile_posts_custom_column', 'emc_badr_tile_column', 10, 2 );

/** Return the public named-tile gallery URL. */
function emc_get_badr_tiles_url() {
    $page_id = absint( get_option( 'emc_badr_tiles_page_id' ) );
    if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
        return get_permalink( $page_id );
    }

    $page = get_page_by_path( 'badr-wall-tiles' );
    return $page ? get_permalink( $page ) : home_url( '/badr-wall-tiles/' );
}

/** Create the gallery page once so campaign links always resolve. */
function emc_ensure_badr_tiles_page() {
    $page_id = absint( get_option( 'emc_badr_tiles_page_id' ) );
    if ( $page_id && get_post( $page_id ) ) {
        return;
    }

    $page = get_page_by_path( 'badr-wall-tiles', OBJECT, 'page' );
    if ( $page ) {
        update_option( 'emc_badr_tiles_page_id', $page->ID );
        return;
    }

    $page_id = wp_insert_post( array(
        'post_type'   => 'page',
        'post_status' => 'publish',
        'post_title'  => __( 'Badr Wall Tiles', 'emc-theme' ),
        'post_name'   => 'badr-wall-tiles',
        'meta_input'  => array( '_wp_page_template' => 'page-badr-wall-tiles.php' ),
    ) );

    if ( $page_id && ! is_wp_error( $page_id ) ) {
        update_option( 'emc_badr_tiles_page_id', $page_id );
    }
}
add_action( 'init', 'emc_ensure_badr_tiles_page', 30 );

/**
 * Create a pending tile from a confirmed Badr Wall card payment.
 * Administrators publish it after checking the public name and dedication.
 *
 * @param array $payment Confirmed payment data.
 * @return int|false
 */
function emc_badr_create_tile_from_payment( $payment ) {
    $fund = sanitize_text_field( $payment['fund'] ?? '' );
    if ( 0 !== stripos( $fund, 'Badr Wall -' ) ) {
        return false;
    }

    $payment_ref = sanitize_text_field( $payment['payment_ref'] ?? '' );
    if ( $payment_ref ) {
        $existing = get_posts( array(
            'post_type'      => 'emc_badr_tile',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_emc_badr_payment_ref',
            'meta_value'     => $payment_ref,
        ) );
        if ( $existing ) {
            return (int) $existing[0];
        }
    }

    $message    = sanitize_textarea_field( $payment['message'] ?? '' );
    $donor_name = sanitize_text_field( $payment['name'] ?? '' );
    $tile_name  = $donor_name ?: __( 'Anonymous', 'emc-theme' );
    $dedication = '';
    $anonymous  = false;

    if ( preg_match( '/^Tile name:\s*(.+)$/mi', $message, $match ) ) {
        $tile_name = sanitize_text_field( $match[1] );
    }
    if ( preg_match( '/^Dedication:\s*(.+)$/mi', $message, $match ) ) {
        $dedication = wp_html_excerpt( sanitize_text_field( $match[1] ), 180, '' );
    }
    if ( preg_match( '/^Display anonymously:\s*yes$/mi', $message ) ) {
        $anonymous = true;
    }

    $tier = false !== stripos( $fund, 'Co-Founder' ) ? 'co-founder' : 'founder';
    $tile_id = wp_insert_post( array(
        'post_type'   => 'emc_badr_tile',
        'post_status' => 'pending',
        'post_title'  => $tile_name ?: __( 'Anonymous', 'emc-theme' ),
    ) );

    if ( ! $tile_id || is_wp_error( $tile_id ) ) {
        return false;
    }

    update_post_meta( $tile_id, '_emc_badr_tier', $tier );
    update_post_meta( $tile_id, '_emc_badr_dedication', $dedication );
    update_post_meta( $tile_id, '_emc_badr_anonymous', $anonymous ? '1' : '' );
    update_post_meta( $tile_id, '_emc_badr_payment_ref', $payment_ref );

    return (int) $tile_id;
}

/** Import any earlier Badr Wall card payments into the moderation queue once. */
function emc_import_existing_badr_payments() {
    if ( get_option( 'emc_badr_payment_import_v1' ) ) {
        return;
    }

    $payments = get_option( 'emc_donations_log', array() );
    if ( is_array( $payments ) ) {
        foreach ( $payments as $payment ) {
            if ( ! is_array( $payment ) ) {
                continue;
            }

            emc_badr_create_tile_from_payment( array(
                'payment_ref' => sanitize_text_field( $payment['pi_id'] ?? '' ),
                'fund'        => sanitize_text_field( $payment['fund'] ?? '' ),
                'name'        => sanitize_text_field( $payment['name'] ?? '' ),
                'message'     => sanitize_textarea_field( $payment['message'] ?? '' ),
            ) );
        }
    }

    update_option( 'emc_badr_payment_import_v1', '1' );
}
add_action( 'init', 'emc_import_existing_badr_payments', 40 );
