<?php
/**
 * EMC Theme — inc/demo-import.php
 * Phase 11: Import engine — programmatically creates all pages, menus,
 * widget assignments, theme_mods, and front-page settings.
 *
 * Called by the Setup Wizard AJAX handler.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/* Load demo data if not already included */
require_once get_template_directory() . '/inc/demo-data.php';


/* ==========================================================================
   Public API
   ========================================================================== */

/**
 * Run the full demo import. Returns a log array of completed steps.
 *
 * @return array  [ 'step' => string, 'status' => 'ok'|'skip'|'error', 'detail' => string ][]
 */
function emc_demo_run_import() {
    $log = array();

    $log = array_merge( $log, emc_demo_import_pages() );
    $log = array_merge( $log, emc_demo_import_menus() );
    $log[] = emc_demo_assign_front_page();
    $log[] = emc_demo_assign_blog_page();
    $log = array_merge( $log, emc_demo_apply_theme_mods() );
    $log = array_merge( $log, emc_demo_import_gallery() );
    $log = array_merge( $log, emc_demo_import_events() );
    $log[] = emc_demo_flush_rewrite_rules();

    return $log;
}


/* ==========================================================================
   Step 1 — Pages
   ========================================================================== */

/**
 * Create all demo pages (skips existing slugs).
 *
 * @return array  Log entries.
 */
function emc_demo_import_pages() {
    $log   = array();
    $pages = emc_demo_get_pages();

    // First pass: create parent pages and build a slug→ID map
    $id_map = array();
    foreach ( $pages as $page ) {
        if ( ! empty( $page['parent'] ) ) continue; // handle parents first

        $result   = emc_demo_create_page( $page, $id_map );
        $id_map[ $page['slug'] ] = $result['id'];
        $log[] = array(
            'step'   => sprintf( __( 'Page: %s', 'emc-theme' ), $page['title'] ),
            'status' => $result['status'],
            'detail' => $result['detail'],
        );
    }

    // Second pass: create child pages
    foreach ( $pages as $page ) {
        if ( empty( $page['parent'] ) ) continue;

        $result   = emc_demo_create_page( $page, $id_map );
        $id_map[ $page['slug'] ] = $result['id'];
        $log[] = array(
            'step'   => sprintf( __( 'Page: %s', 'emc-theme' ), $page['title'] ),
            'status' => $result['status'],
            'detail' => $result['detail'],
        );
    }

    return $log;
}

/**
 * Create a single page. Skips if a page with the same slug already exists.
 *
 * @param  array $page    Page definition from demo-data.php.
 * @param  array $id_map  slug→ID map for resolving parent slugs.
 * @return array  [ 'id', 'status', 'detail' ]
 */
function emc_demo_create_page( $page, $id_map ) {
    // Child pages must be checked by their full hierarchical path. Looking
    // up only the leaf slug makes WordPress miss an existing nested page and
    // create a numbered duplicate on every repeated import.
    $lookup_path = ! empty( $page['parent'] )
        ? trailingslashit( $page['parent'] ) . $page['slug']
        : $page['slug'];
    $existing = get_page_by_path( $lookup_path, OBJECT, 'page' );

    // Also recognise a matching page if its parent has been renamed.
    if ( ! $existing ) {
        $matching_pages = get_posts( array(
            'post_type'      => 'page',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
            'name'           => $page['slug'],
            'posts_per_page' => 1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ) );
        $existing = $matching_pages ? reset( $matching_pages ) : null;
    }

    if ( $existing ) {
        // Retroactively assign template if missing on existing page
        if ( ! empty( $page['template'] ) ) {
            $current_template = get_post_meta( $existing->ID, '_wp_page_template', true );
            if ( empty( $current_template ) || $current_template === 'default' ) {
                update_post_meta( $existing->ID, '_wp_page_template', $page['template'] );
            }
        }
        return array(
            'id'     => $existing->ID,
            'status' => 'skip',
            'detail' => sprintf( __( 'Already exists (ID %d)', 'emc-theme' ), $existing->ID ),
        );
    }

    $parent_id = 0;
    if ( ! empty( $page['parent'] ) && isset( $id_map[ $page['parent'] ] ) ) {
        $parent_id = (int) $id_map[ $page['parent'] ];
    } elseif ( ! empty( $page['parent'] ) ) {
        // Try to find parent by path
        $parent = get_page_by_path( $page['parent'], OBJECT, 'page' );
        if ( $parent ) $parent_id = $parent->ID;
    }

    $args = array(
        'post_title'     => sanitize_text_field( $page['title'] ),
        'post_name'      => sanitize_title( $page['slug'] ),
        'post_content'   => wp_kses_post( $page['content'] ),
        'post_status'    => 'publish',
        'post_type'      => 'page',
        'post_parent'    => $parent_id,
        'comment_status' => 'closed',
    );

    $id = wp_insert_post( $args, true );

    if ( is_wp_error( $id ) ) {
        return array(
            'id'     => 0,
            'status' => 'error',
            'detail' => $id->get_error_message(),
        );
    }

    // Assign page template if specified
    if ( ! empty( $page['template'] ) ) {
        update_post_meta( $id, '_wp_page_template', $page['template'] );
    }

    return array(
        'id'     => $id,
        'status' => 'ok',
        'detail' => sprintf( __( 'Created (ID %d)', 'emc-theme' ), $id ),
    );
}


/* ==========================================================================
   Step 2 — Menus
   ========================================================================== */

/**
 * Create + populate both nav menus and assign them to theme locations.
 *
 * @return array  Log entries.
 */
function emc_demo_import_menus() {
    $log = array();

    $log = array_merge( $log, emc_demo_build_menu(
        __( 'Primary Menu', 'emc-theme' ),
        'primary',
        emc_demo_get_primary_menu()
    ) );

    $log = array_merge( $log, emc_demo_build_menu(
        __( 'Footer Quick Links', 'emc-theme' ),
        'footer',
        emc_demo_get_footer_menu()
    ) );

    $log = array_merge( $log, emc_demo_build_menu(
        __( 'Footer Community Links', 'emc-theme' ),
        'footer-community',
        emc_demo_get_footer_community_menu()
    ) );

    $log = array_merge( $log, emc_demo_build_menu(
        __( 'Mobile Navigation', 'emc-theme' ),
        'mobile',
        emc_demo_get_footer_menu() // Same links for mobile nav
    ) );

    return $log;
}

/**
 * Create (or retrieve existing) a nav menu, populate it, and assign to location.
 *
 * @param  string $name      Menu name shown in Appearance > Menus.
 * @param  string $location  Theme location slug.
 * @param  array  $items     Items from demo-data.php.
 * @return array  Log entries.
 */
function emc_demo_build_menu( $name, $location, $items ) {
    $log = array();

    // Create menu (or get existing)
    $menu_id = wp_create_nav_menu( $name );
    if ( is_wp_error( $menu_id ) ) {
        // Menu name already exists — find it
        $existing = get_term_by( 'name', $name, 'nav_menu' );
        if ( $existing ) {
            $menu_id = $existing->term_id;
            $log[]   = array(
                'step'   => sprintf( __( 'Menu: %s', 'emc-theme' ), $name ),
                'status' => 'skip',
                'detail' => __( 'Already exists', 'emc-theme' ),
            );
        } else {
            $log[] = array(
                'step'   => sprintf( __( 'Menu: %s', 'emc-theme' ), $name ),
                'status' => 'error',
                'detail' => $menu_id->get_error_message(),
            );
            return $log;
        }
    } else {
        $log[] = array(
            'step'   => sprintf( __( 'Menu: %s', 'emc-theme' ), $name ),
            'status' => 'ok',
            'detail' => sprintf( __( 'Created (ID %d)', 'emc-theme' ), $menu_id ),
        );
    }

    // Populate menu items only if menu was freshly created (no items yet)
    $existing_items = wp_get_nav_menu_items( $menu_id );
    if ( empty( $existing_items ) ) {
        foreach ( $items as $item ) {
            $page       = get_page_by_path( $item['slug'], OBJECT, 'page' );
            $object_id  = $page ? $page->ID : 0;
            $type       = $object_id ? 'post_type' : 'custom';
            $url        = $object_id ? '' : home_url( '/' . $item['slug'] . '/' );

            $parent_item_id = wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title'     => sanitize_text_field( $item['label'] ),
                'menu-item-object'    => $object_id ? 'page' : 'custom',
                'menu-item-object-id' => $object_id,
                'menu-item-type'      => $type,
                'menu-item-url'       => $url,
                'menu-item-status'    => 'publish',
            ) );

            // Create children (drop-down items)
            if ( ! empty( $item['children'] ) && ! is_wp_error( $parent_item_id ) ) {
                foreach ( $item['children'] as $child ) {
                    $child_page = get_page_by_path( $child['slug'], OBJECT, 'page' );
                    $child_id   = $child_page ? $child_page->ID : 0;

                    wp_update_nav_menu_item( $menu_id, 0, array(
                        'menu-item-title'      => sanitize_text_field( $child['label'] ),
                        'menu-item-object'     => $child_id ? 'page' : 'custom',
                        'menu-item-object-id'  => $child_id,
                        'menu-item-type'       => $child_id ? 'post_type' : 'custom',
                        'menu-item-url'        => $child_id ? '' : home_url( '/' . $child['slug'] . '/' ),
                        'menu-item-status'     => 'publish',
                        'menu-item-parent-id'  => $parent_item_id,
                    ) );
                }
            }
        }

        $log[] = array(
            'step'   => sprintf( __( 'Menu items: %s', 'emc-theme' ), $name ),
            'status' => 'ok',
            'detail' => sprintf( __( '%d top-level items added', 'emc-theme' ), count( $items ) ),
        );
    }

    // Assign menu to theme location
    $locations = get_theme_mod( 'nav_menu_locations', array() );
    $locations[ $location ] = $menu_id;
    set_theme_mod( 'nav_menu_locations', $locations );

    return $log;
}


/* ==========================================================================
   Step 3 — Front Page & Blog Page Assignment
   ========================================================================== */

/**
 * Set the homepage to the 'home' page (static front page).
 *
 * @return array  Log entry.
 */
function emc_demo_assign_front_page() {
    $page = get_page_by_path( 'home', OBJECT, 'page' );

    if ( ! $page ) {
        return array(
            'step'   => __( 'Front Page assignment', 'emc-theme' ),
            'status' => 'error',
            'detail' => __( 'Home page not found', 'emc-theme' ),
        );
    }

    update_option( 'show_on_front', 'page' );
    update_option( 'page_on_front', $page->ID );

    return array(
        'step'   => __( 'Front Page assignment', 'emc-theme' ),
        'status' => 'ok',
        'detail' => sprintf( __( 'Set to "%s" (ID %d)', 'emc-theme' ), $page->post_title, $page->ID ),
    );
}

/**
 * Set the blog/news page.
 *
 * @return array  Log entry.
 */
function emc_demo_assign_blog_page() {
    $page = get_page_by_path( 'blog', OBJECT, 'page' );

    if ( ! $page ) {
        return array(
            'step'   => __( 'Blog Page assignment', 'emc-theme' ),
            'status' => 'skip',
            'detail' => __( 'Blog page not found — skipped', 'emc-theme' ),
        );
    }

    update_option( 'page_for_posts', $page->ID );

    return array(
        'step'   => __( 'Blog Page assignment', 'emc-theme' ),
        'status' => 'ok',
        'detail' => sprintf( __( 'Set to "%s" (ID %d)', 'emc-theme' ), $page->post_title, $page->ID ),
    );
}


/* ==========================================================================
   Step 4 — Theme Mods
   ========================================================================== */

/**
 * Apply all theme_mod defaults from demo-data.php.
 * Skips any mod that has already been explicitly set by the user.
 *
 * @return array  Log entries.
 */
function emc_demo_apply_theme_mods() {
    $log  = array();
    $mods = emc_demo_get_theme_mods();

    $skipped = 0;
    $applied = 0;

    foreach ( $mods as $key => $value ) {
        // Special handling for core WordPress options (blogname, blogdescription)
        if ( $key === 'blogname' || $key === 'blogdescription' ) {
            if ( ! get_option( $key ) ) {
                update_option( $key, $value );
                $applied++;
            } else {
                $skipped++;
            }
            continue;
        }

        // Apply theme_mod (don't overwrite existing user customisations)
        $current = get_theme_mod( $key, '__not_set__' );
        if ( $current === '__not_set__' ) {
            set_theme_mod( $key, $value );
            $applied++;
        } else {
            $skipped++;
        }
    }

    $log[] = array(
        'step'   => __( 'Theme Mods', 'emc-theme' ),
        'status' => 'ok',
        'detail' => sprintf(
            __( '%d applied, %d already set (preserved)', 'emc-theme' ),
            $applied,
            $skipped
        ),
    );

    return $log;
}


/* ==========================================================================
   Step 5 — Flush Rewrite Rules
   ========================================================================== */

/**
 * Flush WordPress rewrite rules so all new page slugs resolve.
 *
 * @return array  Log entry.
 */
function emc_demo_flush_rewrite_rules() {
    flush_rewrite_rules();
    return array(
        'step'   => __( 'Rewrite Rules', 'emc-theme' ),
        'status' => 'ok',
        'detail' => __( 'Flushed successfully', 'emc-theme' ),
    );
}


/* ==========================================================================
   Step 6 — Gallery Items
   ========================================================================== */

/**
 * Import gallery items from bundled assets/gallery/ images.
 * Creates emc_gallery posts with featured images uploaded to the Media Library.
 *
 * @return array  Log entries.
 */
function emc_demo_import_gallery() {
    $log   = array();
    $items = emc_demo_get_gallery_items();

    // Skip if gallery items already exist
    $existing = new WP_Query( array(
        'post_type'      => 'emc_gallery',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    ) );
    if ( $existing->have_posts() ) {
        $log[] = array(
            'step'   => __( 'Gallery Items', 'emc-theme' ),
            'status' => 'skip',
            'detail' => __( 'Gallery items already exist — skipped', 'emc-theme' ),
        );
        return $log;
    }

    // We need the media handling functions
    if ( ! function_exists( 'media_handle_sideload' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $created = 0;
    $errors  = 0;
    $gallery_dir = get_template_directory() . '/assets/gallery/';

    foreach ( $items as $item ) {
        $file_path = $gallery_dir . $item['file'];

        // Create the gallery post
        $post_id = wp_insert_post( array(
            'post_title'  => sanitize_text_field( $item['title'] ),
            'post_type'   => 'emc_gallery',
            'post_status' => 'publish',
        ), true );

        if ( is_wp_error( $post_id ) ) {
            $errors++;
            continue;
        }

        // Assign gallery category
        if ( ! empty( $item['category'] ) ) {
            wp_set_object_terms( $post_id, $item['category'], 'gallery_category' );
        }

        // Upload and attach the featured image
        if ( file_exists( $file_path ) ) {
            $filename  = basename( $file_path );
            $tmp_file  = wp_tempnam( $filename );
            copy( $file_path, $tmp_file );

            $file_array = array(
                'name'     => $filename,
                'tmp_name' => $tmp_file,
            );

            $attach_id = media_handle_sideload( $file_array, $post_id, $item['title'] );

            if ( ! is_wp_error( $attach_id ) ) {
                set_post_thumbnail( $post_id, $attach_id );
            }
        }

        $created++;
    }

    $log[] = array(
        'step'   => __( 'Gallery Items', 'emc-theme' ),
        'status' => $errors ? 'error' : 'ok',
        'detail' => sprintf(
            __( '%d gallery items created, %d errors', 'emc-theme' ),
            $created,
            $errors
        ),
    );

    return $log;
}


/* ==========================================================================
   Step 7 — Demo Events
   ========================================================================== */

/**
 * Import demo events as emc_event CPT posts with meta fields and featured images.
 *
 * @return array  Log entries.
 */
function emc_demo_import_events() {
    $log    = array();
    $events = function_exists( 'emc_demo_get_events' ) ? emc_demo_get_events() : array();

    if ( empty( $events ) ) {
        $log[] = array(
            'step'   => __( 'Demo Events', 'emc-theme' ),
            'status' => 'skip',
            'detail' => __( 'No event data found', 'emc-theme' ),
        );
        return $log;
    }

    // Skip if events already exist
    $existing = new WP_Query( array(
        'post_type'      => 'emc_event',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    ) );
    if ( $existing->have_posts() ) {
        $log[] = array(
            'step'   => __( 'Demo Events', 'emc-theme' ),
            'status' => 'skip',
            'detail' => __( 'Events already exist — skipped', 'emc-theme' ),
        );
        return $log;
    }

    // We need the media handling functions
    if ( ! function_exists( 'media_handle_sideload' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $created     = 0;
    $errors      = 0;
    $gallery_dir = get_template_directory() . '/assets/gallery/';

    foreach ( $events as $evt ) {
        $post_id = wp_insert_post( array(
            'post_title'   => sanitize_text_field( $evt['title'] ),
            'post_type'    => 'emc_event',
            'post_status'  => 'publish',
            'post_excerpt' => sanitize_text_field( $evt['excerpt'] ),
            'post_content' => wp_kses_post( $evt['content'] ),
        ), true );

        if ( is_wp_error( $post_id ) ) {
            $errors++;
            continue;
        }

        // Set event meta fields
        update_post_meta( $post_id, '_emc_event_date',     sanitize_text_field( $evt['date'] ) );
        update_post_meta( $post_id, '_emc_event_time',     sanitize_text_field( $evt['time'] ) );
        update_post_meta( $post_id, '_emc_event_end_time', sanitize_text_field( $evt['end_time'] ) );
        update_post_meta( $post_id, '_emc_event_location', sanitize_text_field( $evt['location'] ) );
        update_post_meta( $post_id, '_emc_event_venue',    sanitize_text_field( $evt['location'] ) );
        update_post_meta( $post_id, '_emc_event_category', sanitize_text_field( $evt['category'] ) );
        update_post_meta( $post_id, '_emc_event_capacity', sanitize_text_field( $evt['capacity'] ) );

        // Assign event_category taxonomy term
        if ( ! empty( $evt['category'] ) ) {
            wp_set_object_terms( $post_id, ucfirst( $evt['category'] ), 'event_category' );
        }

        // Upload and set featured image
        if ( ! empty( $evt['image'] ) ) {
            $file_path = $gallery_dir . $evt['image'];
            if ( file_exists( $file_path ) ) {
                $filename  = basename( $file_path );
                $tmp_file  = wp_tempnam( $filename );
                copy( $file_path, $tmp_file );

                $file_array = array(
                    'name'     => $filename,
                    'tmp_name' => $tmp_file,
                );

                $attach_id = media_handle_sideload( $file_array, $post_id, $evt['title'] );
                if ( ! is_wp_error( $attach_id ) ) {
                    set_post_thumbnail( $post_id, $attach_id );
                }
            }
        }

        $created++;
    }

    $log[] = array(
        'step'   => __( 'Demo Events', 'emc-theme' ),
        'status' => $errors ? 'error' : 'ok',
        'detail' => sprintf(
            __( '%d events created, %d errors', 'emc-theme' ),
            $created,
            $errors
        ),
    );

    return $log;
}

/* ==========================================================================
   AJAX Handler
   ========================================================================== */

/**
 * Admin-only AJAX: runs the import and returns JSON progress log.
 */
function emc_ajax_demo_import() {
    check_ajax_referer( 'emc_demo_import_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'emc-theme' ) ) );
    }

    $log = emc_demo_run_import();

    // Mark import as done
    update_option( 'emc_demo_imported', time() );

    wp_send_json_success( array(
        'log'     => $log,
        'message' => __( 'Import completed successfully!', 'emc-theme' ),
    ) );
}
add_action( 'wp_ajax_emc_demo_import_run', 'emc_ajax_demo_import' );


/**
 * Admin-only AJAX: resets the import flag so it can be re-run.
 */
function emc_ajax_demo_import_reset() {
    check_ajax_referer( 'emc_demo_import_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error();
    }

    delete_option( 'emc_demo_imported' );
    wp_send_json_success();
}
add_action( 'wp_ajax_emc_demo_import_reset', 'emc_ajax_demo_import_reset' );
