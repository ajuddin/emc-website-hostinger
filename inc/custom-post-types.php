<?php
/**
 * EMC Theme — inc/custom-post-types.php
 * Registers all Custom Post Types, taxonomies, and archive query overrides.
 * Phase 5: Added Portfolio, Pricing, Case Studies + 3 new taxonomies.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   Register All Custom Post Types
   ========================================================================== */
function emc_register_post_types() {

    // ── 1. Events ─────────────────────────────────────────────────────────
    register_post_type( 'emc_event', array(
        'labels'        => emc_cpt_labels( 'Events', 'Event', 'Add New Event' ),
        'public'        => true,
        'has_archive'   => true,
        'menu_icon'     => 'dashicons-calendar-alt',
        'menu_position' => 5,
        'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        'show_in_rest'  => true,
        'rewrite'       => array( 'slug' => 'events' ),
    ) );

    // ── 2. Services ───────────────────────────────────────────────────────
    register_post_type( 'emc_service', array(
        'labels'        => emc_cpt_labels( 'Services', 'Service', 'Add New Service' ),
        'public'        => true,
        'has_archive'   => 'all-services',   // archive at /all-services/ — avoids hijacking /services/ static page
        'menu_icon'     => 'dashicons-heart',
        'menu_position' => 6,
        'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
        'show_in_rest'  => true,
        'rewrite'       => array( 'slug' => 'service' ),  // singular: /service/my-service/
    ) );

    // ── 3. Team Members ───────────────────────────────────────────────────
    register_post_type( 'emc_team', array(
        'labels'        => emc_cpt_labels( 'Team Members', 'Team Member', 'Add Team Member' ),
        'public'        => false,
        'show_ui'       => true,
        'menu_icon'     => 'dashicons-groups',
        'menu_position' => 7,
        'supports'      => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
        'show_in_rest'  => true,
    ) );

    // ── 4. Testimonials ───────────────────────────────────────────────────
    register_post_type( 'emc_testimonial', array(
        'labels'        => emc_cpt_labels( 'Testimonials', 'Testimonial', 'Add New Testimonial' ),
        'public'        => false,
        'show_ui'       => true,
        'menu_icon'     => 'dashicons-format-quote',
        'menu_position' => 8,
        'supports'      => array( 'title', 'editor', 'thumbnail' ),
        'show_in_rest'  => true,
    ) );

    // ── 5. FAQs ───────────────────────────────────────────────────────────
    register_post_type( 'emc_faq', array(
        'labels'        => emc_cpt_labels( 'FAQs', 'FAQ', 'Add New FAQ' ),
        'public'        => false,
        'show_ui'       => true,
        'menu_icon'     => 'dashicons-editor-help',
        'menu_position' => 9,
        'supports'      => array( 'title', 'editor', 'page-attributes' ),
        'show_in_rest'  => true,
    ) );

    // ── 6. Donations / Campaigns ──────────────────────────────────────────
    register_post_type( 'emc_campaign', array(
        'labels'        => emc_cpt_labels( 'Campaigns', 'Campaign', 'Add New Campaign' ),
        'public'        => true,
        'has_archive'   => true,
        'menu_icon'     => 'dashicons-chart-line',
        'menu_position' => 10,
        'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest'  => true,
        'rewrite'       => array( 'slug' => 'campaigns' ),
    ) );

    // ── 7. Vacancies ──────────────────────────────────────────────────────
    register_post_type( 'emc_vacancy', array(
        'labels'        => emc_cpt_labels( 'Job Roles', 'Job Role', 'Add New Job Role' ),
        'public'        => true,
        'has_archive'   => true,
        'menu_icon'     => 'dashicons-businessperson',
        'menu_position' => 11,
        'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest'  => true,
        'rewrite'       => array( 'slug' => 'vacancies' ),
    ) );

    register_post_type( 'emc_volunteer_role', array(
        'labels'        => emc_cpt_labels( 'Volunteer Roles', 'Volunteer Role', 'Add New Volunteer Role' ),
        'public'        => true,
        'has_archive'   => 'volunteer-opportunity',
        'menu_icon'     => 'dashicons-universal-access-alt',
        'menu_position' => 12,
        'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
        'show_in_rest'  => true,
        'rewrite'       => array( 'slug' => 'volunteer-opportunity' ),
    ) );

    // ── 8. Community Projects (Portfolio) ────────────────────────────────
    register_post_type( 'emc_portfolio', array(
        'labels'        => emc_cpt_labels( 'Projects', 'Project', 'Add New Project' ),
        'public'        => true,
        'has_archive'   => true,
        'menu_icon'     => 'dashicons-building',
        'menu_position' => 12,
        'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
        'show_in_rest'  => true,
        'rewrite'       => array( 'slug' => 'projects' ),
    ) );

    // ── 9. Programmes & Courses (Pricing) ────────────────────────────────
    register_post_type( 'emc_pricing', array(
        'labels'        => emc_cpt_labels( 'Programmes', 'Programme', 'Add New Programme' ),
        'public'        => false,
        'show_ui'       => true,
        'menu_icon'     => 'dashicons-tickets-alt',
        'menu_position' => 13,
        'supports'      => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
        'show_in_rest'  => true,
    ) );

    // ── 10. Impact Stories (Case Studies) ────────────────────────────────
    register_post_type( 'emc_case_study', array(
        'labels'        => emc_cpt_labels( 'Impact Stories', 'Impact Story', 'Add New Impact Story' ),
        'public'        => true,
        'has_archive'   => true,
        'menu_icon'     => 'dashicons-star-filled',
        'menu_position' => 14,
        'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest'  => true,
        'rewrite'       => array( 'slug' => 'impact-stories' ),
    ) );

    // ── 11. Media Gallery Items ───────────────────────────────────────────
    register_post_type( 'emc_gallery', array(
        'labels'        => emc_cpt_labels( 'Gallery Items', 'Gallery Item', 'Add Gallery Item' ),
        'public'        => false,
        'show_ui'       => true,
        'menu_icon'     => 'dashicons-format-gallery',
        'menu_position' => 15,
        'supports'      => array( 'title', 'thumbnail', 'custom-fields' ),
        'show_in_rest'  => true,
    ) );
}
add_action( 'init', 'emc_register_post_types' );


/* ==========================================================================
   Register Taxonomies
   ========================================================================== */
function emc_register_taxonomies() {

    // Event Categories
    register_taxonomy( 'event_category', array( 'emc_event' ), array(
        'labels'       => emc_tax_labels( 'Event Categories', 'Event Category' ),
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite'      => array( 'slug' => 'event-category' ),
    ) );

    // Service Categories
    register_taxonomy( 'service_category', array( 'emc_service' ), array(
        'labels'       => emc_tax_labels( 'Service Categories', 'Service Category' ),
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite'      => array( 'slug' => 'service-category' ),
    ) );

    // Team Departments
    register_taxonomy( 'team_department', array( 'emc_team' ), array(
        'labels'       => emc_tax_labels( 'Departments', 'Department' ),
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite'      => array( 'slug' => 'department' ),
    ) );

    // Testimonial Categories
    register_taxonomy( 'testimonial_category', array( 'emc_testimonial' ), array(
        'labels'       => emc_tax_labels( 'Testimonial Categories', 'Testimonial Category' ),
        'hierarchical' => false,
        'show_in_rest' => true,
        'rewrite'      => array( 'slug' => 'testimonial-category' ),
    ) );

    // Portfolio / Project Categories
    register_taxonomy( 'portfolio_category', array( 'emc_portfolio' ), array(
        'labels'       => emc_tax_labels( 'Project Categories', 'Project Category' ),
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite'      => array( 'slug' => 'project-category' ),
    ) );

    // Vacancy Types
    register_taxonomy( 'vacancy_type', array( 'emc_vacancy' ), array(
        'labels'       => emc_tax_labels( 'Job Role Types', 'Job Role Type' ),
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite'      => array( 'slug' => 'vacancy-type' ),
    ) );

    register_taxonomy( 'volunteer_role_type', array( 'emc_volunteer_role' ), array(
        'labels'       => emc_tax_labels( 'Volunteer Role Types', 'Volunteer Role Type' ),
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite'      => array( 'slug' => 'volunteer-role-type' ),
    ) );

    // Gallery Categories
    register_taxonomy( 'gallery_category', array( 'emc_gallery' ), array(
        'labels'            => emc_tax_labels( 'Gallery Categories', 'Gallery Category' ),
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => array( 'slug' => 'gallery-category' ),
    ) );
}
add_action( 'init', 'emc_register_taxonomies' );

/** Refresh permalinks once when the theme's public content routes change. */
function emc_maybe_refresh_content_rewrites() {
    $schema_version = '2026-08-volunteer-roles-2';
    if ( $schema_version === get_option( 'emc_content_rewrite_schema' ) ) {
        return;
    }
    flush_rewrite_rules( false );
    update_option( 'emc_content_rewrite_schema', $schema_version, false );
}
add_action( 'init', 'emc_maybe_refresh_content_rewrites', 99 );


/* ==========================================================================
   Archive Query Overrides  (pre_get_posts)
   ========================================================================== */
function emc_cpt_archive_queries( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    // Events archive: upcoming first, ordered by event date ascending
    if ( is_post_type_archive( 'emc_event' ) || is_tax( 'event_category' ) ) {
        $query->set( 'meta_key',  '_emc_event_date' );
        $query->set( 'orderby',   'meta_value' );
        $query->set( 'order',     'ASC' );
        $query->set( 'posts_per_page', 12 );
    }

    // Services archive: ordered by menu_order then title
    if ( is_post_type_archive( 'emc_service' ) || is_tax( 'service_category' ) ) {
        $query->set( 'orderby',        'menu_order title' );
        $query->set( 'order',          'ASC' );
        $query->set( 'posts_per_page', 12 );
    }

    // Projects archive: completed first, then by date descending
    if ( is_post_type_archive( 'emc_portfolio' ) || is_tax( 'portfolio_category' ) ) {
        $query->set( 'orderby',        'date' );
        $query->set( 'order',          'DESC' );
        $query->set( 'posts_per_page', 12 );
    }

    // Impact Stories: newest first
    if ( is_post_type_archive( 'emc_case_study' ) ) {
        $query->set( 'orderby',        'date' );
        $query->set( 'order',          'DESC' );
        $query->set( 'posts_per_page', 9 );
    }

    // Volunteer opportunities: newest first.
    if ( is_post_type_archive( 'emc_volunteer_role' ) || is_tax( 'volunteer_role_type' ) ) {
        $query->set( 'orderby', 'date' );
        $query->set( 'order', 'DESC' );
        $query->set( 'posts_per_page', 12 );
    }

    // Vacancies: newest first
    if ( is_post_type_archive( 'emc_vacancy' ) || is_tax( 'vacancy_type' ) ) {
        $query->set( 'orderby',        'date' );
        $query->set( 'order',          'DESC' );
        $query->set( 'posts_per_page', 12 );
    }
}
add_action( 'pre_get_posts', 'emc_cpt_archive_queries' );


/* ==========================================================================
   Label Helpers (reduce boilerplate)
   ========================================================================== */
function emc_cpt_labels( $plural, $singular, $add_new ) {
    return array(
        'name'               => __( $plural,   'emc-theme' ),
        'singular_name'      => __( $singular,  'emc-theme' ),
        'add_new_item'       => __( $add_new,   'emc-theme' ),
        'edit_item'          => sprintf( __( 'Edit %s',   'emc-theme' ), $singular ),
        'view_item'          => sprintf( __( 'View %s',   'emc-theme' ), $singular ),
        'search_items'       => sprintf( __( 'Search %s', 'emc-theme' ), $plural ),
        'not_found'          => sprintf( __( 'No %s found.', 'emc-theme' ), strtolower( $plural ) ),
        'not_found_in_trash' => sprintf( __( 'No %s in trash.', 'emc-theme' ), strtolower( $plural ) ),
        'all_items'          => sprintf( __( 'All %s', 'emc-theme' ), $plural ),
    );
}

function emc_tax_labels( $plural, $singular ) {
    return array(
        'name'          => __( $plural,   'emc-theme' ),
        'singular_name' => __( $singular, 'emc-theme' ),
        'all_items'     => sprintf( __( 'All %s', 'emc-theme' ), $plural ),
        'edit_item'     => sprintf( __( 'Edit %s', 'emc-theme' ), $singular ),
        'update_item'   => sprintf( __( 'Update %s', 'emc-theme' ), $singular ),
        'add_new_item'  => sprintf( __( 'Add New %s', 'emc-theme' ), $singular ),
        'new_item_name' => sprintf( __( 'New %s Name', 'emc-theme' ), $singular ),
        'search_items'  => sprintf( __( 'Search %s', 'emc-theme' ), $plural ),
    );
}
