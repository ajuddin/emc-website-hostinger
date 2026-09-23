<?php
/**
 * EMC Theme — inc/admin-columns.php
 * Custom admin list table columns for all CPTs.
 * Phase 5: Events, Services, Team, Testimonials, Portfolio, Pricing, Case Studies.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   Events  (emc_event)
   ========================================================================== */
add_filter( 'manage_emc_event_posts_columns', function( $cols ) {
    $new = array();
    foreach ( $cols as $key => $label ) {
        $new[ $key ] = $label;
        if ( 'title' === $key ) {
            $new['event_date']     = __( 'Date / Day', 'emc-theme' );
            $new['event_time']     = __( 'Time', 'emc-theme' );
            $new['event_venue']    = __( 'Venue', 'emc-theme' );
            $new['event_featured'] = __( 'Homepage', 'emc-theme' );
        }
    }
    return $new;
} );

add_action( 'manage_emc_event_posts_custom_column', function( $col, $post_id ) {
    switch ( $col ) {
        case 'event_date':
            $day_slug = function_exists( 'emc_get_event_recurring_day_slug' ) ? emc_get_event_recurring_day_slug( $post_id ) : '';
            $day_raw  = get_post_meta( $post_id, '_emc_event_day', true );
            $day_val  = $day_raw ?: $day_slug;
            $day      = function_exists( 'emc_get_event_display_day' ) ? emc_get_event_display_day( $post_id ) : '';
            $d        = get_post_meta( $post_id, '_emc_event_date', true );
            echo '<span class="emc-col-day-slug" style="display:none;">' . esc_attr( $day_val ) . '</span>';
            echo '<span class="emc-col-date-val" style="display:none;">' . esc_attr( $d ) . '</span>';
            if ( $day ) {
                echo '<strong style="color:var(--wp-admin-theme-color,#0073aa);">' . esc_html( $day ) . '</strong>';
                if ( $d ) {
                    echo '<br><small style="color:#666;">' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $d ) ) ) . '</small>';
                }
            } elseif ( $d ) {
                echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $d ) ) );
            } else {
                echo '—';
            }
            break;
        case 'event_time':
            $t = get_post_meta( $post_id, '_emc_event_time', true );
            echo '<span class="emc-col-time-val" style="display:none;">' . esc_attr( $t ) . '</span>';
            echo $t ? '<span style="font-weight:600;">' . esc_html( $t ) . '</span>' : '—';
            break;
        case 'event_venue':
            $v = get_post_meta( $post_id, '_emc_event_venue', true );
            echo $v ? esc_html( $v ) : '—';
            break;
        case 'event_featured':
            echo get_post_meta( $post_id, '_emc_event_featured', true ) === '1'
                ? '<span style="color:#2ecc71">&#10004;</span>'
                : '—';
            break;
    }
}, 10, 2 );

/**
 * Render Quick Edit fields for Events (Day, Time, Date).
 */
add_action( 'quick_edit_custom_box', function( $column_name, $post_type ) {
    if ( 'emc_event' !== $post_type || 'event_date' !== $column_name ) {
        return;
    }
    wp_nonce_field( 'emc_event_quick_edit_nonce', 'emc_event_quick_edit_nonce_field' );
    ?>
    <fieldset class="inline-edit-col-left" style="margin-top:0.5rem;clear:both;">
        <div class="inline-edit-col">
            <span class="title" style="font-weight:600;display:block;margin-bottom:0.5rem;"><?php esc_html_e( 'Event Schedule & Timings', 'emc-theme' ); ?></span>
            <div class="inline-edit-group wp-clearfix">
                <label class="alignleft" style="margin-right:1rem;margin-bottom:0.5rem;">
                    <span class="title"><?php esc_html_e( 'Recurring Day', 'emc-theme' ); ?></span>
                    <select name="emc_event_day" class="emc-qe-event-day">
                        <option value=""><?php esc_html_e( '— Select Day —', 'emc-theme' ); ?></option>
                        <option value="none"><?php esc_html_e( 'None (One-time)', 'emc-theme' ); ?></option>
                        <option value="monday"><?php esc_html_e( 'Every Monday', 'emc-theme' ); ?></option>
                        <option value="tuesday"><?php esc_html_e( 'Every Tuesday', 'emc-theme' ); ?></option>
                        <option value="wednesday"><?php esc_html_e( 'Every Wednesday', 'emc-theme' ); ?></option>
                        <option value="thursday"><?php esc_html_e( 'Every Thursday', 'emc-theme' ); ?></option>
                        <option value="friday"><?php esc_html_e( 'Every Friday', 'emc-theme' ); ?></option>
                        <option value="saturday"><?php esc_html_e( 'Every Saturday', 'emc-theme' ); ?></option>
                        <option value="sunday"><?php esc_html_e( 'Every Sunday', 'emc-theme' ); ?></option>
                    </select>
                </label>
                <label class="alignleft" style="margin-right:1rem;margin-bottom:0.5rem;">
                    <span class="title"><?php esc_html_e( 'Time', 'emc-theme' ); ?></span>
                    <input type="text" name="emc_event_time" class="emc-qe-event-time" value="" placeholder="e.g. 10:00 AM – 4:00 PM">
                </label>
                <label class="alignleft" style="margin-bottom:0.5rem;">
                    <span class="title"><?php esc_html_e( 'Start Date', 'emc-theme' ); ?></span>
                    <input type="date" name="emc_event_date" class="emc-qe-event-date" value="">
                </label>
            </div>
        </div>
    </fieldset>
    <?php
}, 10, 2 );

/**
 * Save Quick Edit fields for Events.
 */
add_action( 'save_post_emc_event', function( $post_id ) {
    if ( ! isset( $_POST['emc_event_quick_edit_nonce_field'] ) ||
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['emc_event_quick_edit_nonce_field'] ) ), 'emc_event_quick_edit_nonce' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['emc_event_day'] ) ) {
        $event_day = sanitize_key( wp_unslash( $_POST['emc_event_day'] ) );
        update_post_meta(
            $post_id,
            '_emc_event_day',
            in_array( $event_day, array( 'none', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ), true ) ? $event_day : ''
        );
    }
    if ( isset( $_POST['emc_event_time'] ) ) {
        update_post_meta( $post_id, '_emc_event_time', sanitize_text_field( wp_unslash( $_POST['emc_event_time'] ) ) );
    }
    if ( isset( $_POST['emc_event_date'] ) ) {
        update_post_meta( $post_id, '_emc_event_date', sanitize_text_field( wp_unslash( $_POST['emc_event_date'] ) ) );
    }
} );

/**
 * Enqueue inline Quick Edit script for events admin list.
 */
add_action( 'admin_footer-edit.php', function() {
    global $post_type;
    if ( 'emc_event' !== $post_type ) {
        return;
    }
    ?>
    <script>
    jQuery(function($) {
        if (typeof inlineEditPost === 'undefined') {
            return;
        }
        var wp_inline_edit = inlineEditPost.edit;
        inlineEditPost.edit = function(id) {
            wp_inline_edit.apply(this, arguments);
            var postId = 0;
            if (typeof(id) === 'object') {
                postId = parseInt(this.getId(id));
            }
            if (postId > 0) {
                var $row = $('#post-' + postId);
                var $editRow = $('#edit-' + postId);
                var day = $row.find('.emc-col-day-slug').text().trim();
                var time = $row.find('.emc-col-time-val').text().trim();
                var date = $row.find('.emc-col-date-val').text().trim();
                if (day) {
                    $editRow.find('select[name="emc_event_day"]').val(day);
                }
                if (time && time !== '—') {
                    $editRow.find('input[name="emc_event_time"]').val(time);
                }
                if (date) {
                    $editRow.find('input[name="emc_event_date"]').val(date);
                }
            }
        };
    });
    </script>
    <?php
} );

add_filter( 'manage_edit-emc_event_sortable_columns', function( $cols ) {
    $cols['event_date'] = 'event_date';
    return $cols;
} );


/* ==========================================================================
   Services  (emc_service)
   ========================================================================== */
add_filter( 'manage_emc_service_posts_columns', function( $cols ) {
    $new = array();
    foreach ( $cols as $key => $label ) {
        $new[ $key ] = $label;
        if ( 'title' === $key ) {
            $new['service_icon']     = __( 'Icon', 'emc-theme' );
            $new['service_category'] = __( 'Category', 'emc-theme' );
            $new['service_order']    = __( 'Order', 'emc-theme' );
            $new['service_featured'] = __( 'Homepage', 'emc-theme' );
        }
    }
    return $new;
} );

add_action( 'manage_emc_service_posts_custom_column', function( $col, $post_id ) {
    switch ( $col ) {
        case 'service_icon':
            $icon = get_post_meta( $post_id, '_emc_service_icon', true ) ?: 'fas fa-star';
            printf( '<i class="%s" style="font-size:18px;color:#1a6b3a"></i> <code style="font-size:11px">%s</code>',
                esc_attr( $icon ), esc_html( $icon ) );
            break;
        case 'service_category':
            $terms = get_the_terms( $post_id, 'service_category' );
            echo $terms && ! is_wp_error( $terms )
                ? esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) )
                : '—';
            break;
        case 'service_order':
            echo absint( get_post_meta( $post_id, '_emc_service_order', true ) );
            break;
        case 'service_featured':
            echo get_post_meta( $post_id, '_emc_service_featured', true ) === '1'
                ? '<span style="color:#2ecc71">&#10004;</span>'
                : '—';
            break;
    }
}, 10, 2 );

add_filter( 'manage_edit-emc_service_sortable_columns', function( $cols ) {
    $cols['service_order'] = 'service_order';
    return $cols;
} );


/* ==========================================================================
   Team Members  (emc_team)
   ========================================================================== */
add_filter( 'manage_emc_team_posts_columns', function( $cols ) {
    $new = array();
    foreach ( $cols as $key => $label ) {
        $new[ $key ] = $label;
        if ( 'title' === $key ) {
            $new['team_thumb']      = __( 'Photo', 'emc-theme' );
            $new['team_role']       = __( 'Role', 'emc-theme' );
            $new['team_department'] = __( 'Department', 'emc-theme' );
            $new['team_order']      = __( 'Order', 'emc-theme' );
        }
    }
    unset( $new['date'] );
    return $new;
} );

add_action( 'manage_emc_team_posts_custom_column', function( $col, $post_id ) {
    switch ( $col ) {
        case 'team_thumb':
            if ( has_post_thumbnail( $post_id ) ) {
                echo get_the_post_thumbnail( $post_id, array( 48, 48 ),
                    array( 'style' => 'border-radius:50%;width:48px;height:48px;object-fit:cover' ) );
            } else {
                echo '<div style="width:48px;height:48px;border-radius:50%;background:#e0e0e0;display:flex;align-items:center;justify-content:center"><i class="dashicons dashicons-admin-users" style="color:#999"></i></div>';
            }
            break;
        case 'team_role':
            $r = get_post_meta( $post_id, '_emc_team_role', true );
            echo $r ? esc_html( $r ) : '—';
            break;
        case 'team_department':
            $terms = get_the_terms( $post_id, 'team_department' );
            echo $terms && ! is_wp_error( $terms )
                ? esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) )
                : '—';
            break;
        case 'team_order':
            echo absint( get_post_meta( $post_id, '_emc_team_order', true ) );
            break;
    }
}, 10, 2 );


/* ==========================================================================
   Testimonials  (emc_testimonial)
   ========================================================================== */
add_filter( 'manage_emc_testimonial_posts_columns', function( $cols ) {
    $new = array();
    foreach ( $cols as $key => $label ) {
        $new[ $key ] = $label;
        if ( 'title' === $key ) {
            $new['testimonial_author'] = __( 'Author', 'emc-theme' );
            $new['testimonial_role']   = __( 'Role', 'emc-theme' );
            $new['testimonial_rating'] = __( 'Rating', 'emc-theme' );
        }
    }
    return $new;
} );

add_action( 'manage_emc_testimonial_posts_custom_column', function( $col, $post_id ) {
    switch ( $col ) {
        case 'testimonial_author':
            $a = get_post_meta( $post_id, '_emc_testimonial_author', true );
            echo $a ? esc_html( $a ) : '—';
            break;
        case 'testimonial_role':
            $r = get_post_meta( $post_id, '_emc_testimonial_role', true );
            echo $r ? esc_html( $r ) : '—';
            break;
        case 'testimonial_rating':
            $rating = (int) ( get_post_meta( $post_id, '_emc_testimonial_rating', true ) ?: 5 );
            echo str_repeat( '<span style="color:#f5a623">&#9733;</span>', $rating )
               . str_repeat( '<span style="color:#ddd">&#9733;</span>', 5 - $rating );
            break;
    }
}, 10, 2 );


/* ==========================================================================
   Portfolio / Projects  (emc_portfolio)
   ========================================================================== */
add_filter( 'manage_emc_portfolio_posts_columns', function( $cols ) {
    $new = array();
    foreach ( $cols as $key => $label ) {
        $new[ $key ] = $label;
        if ( 'title' === $key ) {
            $new['portfolio_thumb']    = __( 'Image', 'emc-theme' );
            $new['portfolio_status']   = __( 'Status', 'emc-theme' );
            $new['portfolio_category'] = __( 'Category', 'emc-theme' );
            $new['portfolio_featured'] = __( 'Featured', 'emc-theme' );
        }
    }
    return $new;
} );

add_action( 'manage_emc_portfolio_posts_custom_column', function( $col, $post_id ) {
    switch ( $col ) {
        case 'portfolio_thumb':
            if ( has_post_thumbnail( $post_id ) ) {
                echo get_the_post_thumbnail( $post_id, array( 60, 45 ),
                    array( 'style' => 'object-fit:cover;width:60px;height:45px;border-radius:3px' ) );
            }
            break;
        case 'portfolio_status':
            $status = get_post_meta( $post_id, '_emc_portfolio_status', true ) ?: 'ongoing';
            $colors = array( 'ongoing' => '#2ecc71', 'completed' => '#3498db', 'planned' => '#e67e22' );
            $color  = $colors[ $status ] ?? '#999';
            printf( '<span style="color:%s;font-weight:600">%s</span>',
                esc_attr( $color ), esc_html( ucfirst( $status ) ) );
            break;
        case 'portfolio_category':
            $terms = get_the_terms( $post_id, 'portfolio_category' );
            echo $terms && ! is_wp_error( $terms )
                ? esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) )
                : '—';
            break;
        case 'portfolio_featured':
            echo get_post_meta( $post_id, '_emc_portfolio_featured', true ) === '1'
                ? '<span style="color:#2ecc71">&#10004;</span>'
                : '—';
            break;
    }
}, 10, 2 );


/* ==========================================================================
   Pricing / Programmes  (emc_pricing)
   ========================================================================== */
add_filter( 'manage_emc_pricing_posts_columns', function( $cols ) {
    $new = array();
    foreach ( $cols as $key => $label ) {
        $new[ $key ] = $label;
        if ( 'title' === $key ) {
            $new['pricing_price']    = __( 'Price', 'emc-theme' );
            $new['pricing_period']   = __( 'Period', 'emc-theme' );
            $new['pricing_featured'] = __( 'Highlighted', 'emc-theme' );
        }
    }
    return $new;
} );

add_action( 'manage_emc_pricing_posts_custom_column', function( $col, $post_id ) {
    switch ( $col ) {
        case 'pricing_price':
            $p = get_post_meta( $post_id, '_emc_pricing_price', true );
            echo $p ? '<strong>' . esc_html( $p ) . '</strong>' : '—';
            break;
        case 'pricing_period':
            $period_map = array(
                'monthly'     => __( 'Per Month', 'emc-theme' ),
                'annually'    => __( 'Per Year', 'emc-theme' ),
                'one-time'    => __( 'One-Time', 'emc-theme' ),
                'per-session' => __( 'Per Session', 'emc-theme' ),
                'free'        => __( 'Free', 'emc-theme' ),
            );
            $period = get_post_meta( $post_id, '_emc_pricing_period', true );
            echo isset( $period_map[ $period ] ) ? esc_html( $period_map[ $period ] ) : '—';
            break;
        case 'pricing_featured':
            echo get_post_meta( $post_id, '_emc_pricing_featured', true ) === '1'
                ? '<span style="color:#f5a623">&#9733; ' . esc_html__( 'Recommended', 'emc-theme' ) . '</span>'
                : '—';
            break;
    }
}, 10, 2 );


/* ==========================================================================
   Case Studies / Impact Stories  (emc_case_study)
   ========================================================================== */
add_filter( 'manage_emc_case_study_posts_columns', function( $cols ) {
    $new = array();
    foreach ( $cols as $key => $label ) {
        $new[ $key ] = $label;
        if ( 'title' === $key ) {
            $new['case_thumb'] = __( 'Image', 'emc-theme' );
            $new['case_date']  = __( 'Story Date', 'emc-theme' );
            $new['case_stats'] = __( 'Key Stats', 'emc-theme' );
        }
    }
    return $new;
} );

add_action( 'manage_emc_case_study_posts_custom_column', function( $col, $post_id ) {
    switch ( $col ) {
        case 'case_thumb':
            if ( has_post_thumbnail( $post_id ) ) {
                echo get_the_post_thumbnail( $post_id, array( 60, 45 ),
                    array( 'style' => 'object-fit:cover;width:60px;height:45px;border-radius:3px' ) );
            }
            break;
        case 'case_date':
            $d = get_post_meta( $post_id, '_emc_case_study_date', true );
            echo $d ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $d ) ) ) : '—';
            break;
        case 'case_stats':
            $parts = array();
            foreach ( array( 1, 2, 3 ) as $n ) {
                $num = get_post_meta( $post_id, "_emc_case_study_stat{$n}_num", true );
                $lbl = get_post_meta( $post_id, "_emc_case_study_stat{$n}_label", true );
                if ( $num && $lbl ) {
                    $parts[] = '<strong>' . esc_html( $num ) . '</strong> ' . esc_html( $lbl );
                }
            }
            echo $parts ? implode( ' &bull; ', $parts ) : '—';
            break;
    }
}, 10, 2 );
