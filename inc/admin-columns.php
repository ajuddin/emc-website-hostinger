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
            $new['event_venue']    = __( 'Venue', 'emc-theme' );
            $new['event_featured'] = __( 'Homepage', 'emc-theme' );
        }
    }
    return $new;
} );

add_action( 'manage_emc_event_posts_custom_column', function( $col, $post_id ) {
    switch ( $col ) {
        case 'event_date':
            $d = get_post_meta( $post_id, '_emc_event_date', true );
            echo $d
                ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $d ) ) )
                : esc_html( emc_get_event_display_day( $post_id ) ?: '—' );
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
