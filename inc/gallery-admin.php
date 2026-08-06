<?php
/**
 * Gallery bulk upload and admin previews.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/** Register the bulk uploader beneath Gallery Items. */
function emc_gallery_bulk_upload_menu() {
    add_submenu_page(
        null,
        __( 'Bulk Add Gallery Images', 'emc-theme' ),
        __( 'Bulk Add Images', 'emc-theme' ),
        'upload_files',
        'emc-gallery-bulk-upload',
        'emc_gallery_bulk_upload_page'
    );
}
add_action( 'admin_menu', 'emc_gallery_bulk_upload_menu' );

/** Load the WordPress media picker and bulk-upload styling. */
function emc_gallery_bulk_upload_assets( $hook ) {
    if ( false === strpos( $hook, 'emc-gallery-bulk-upload' ) ) {
        return;
    }

    wp_enqueue_media();

    $script_path = EMC_DIR . '/assets/js/admin-gallery.js';
    $style_path  = EMC_DIR . '/assets/css/admin-gallery.css';

    wp_enqueue_script(
        'emc-admin-gallery',
        EMC_ASSETS . '/js/admin-gallery.js',
        array( 'media-editor' ),
        file_exists( $script_path ) ? filemtime( $script_path ) : EMC_VERSION,
        true
    );
    wp_enqueue_style(
        'emc-admin-gallery',
        EMC_ASSETS . '/css/admin-gallery.css',
        array(),
        file_exists( $style_path ) ? filemtime( $style_path ) : EMC_VERSION
    );
}
add_action( 'admin_enqueue_scripts', 'emc_gallery_bulk_upload_assets' );

/** Render the bulk upload screen. */
function emc_gallery_bulk_upload_page() {
    if ( ! current_user_can( 'upload_files' ) ) {
        wp_die( esc_html__( 'You do not have permission to upload gallery images.', 'emc-theme' ) );
    }

    $categories = get_terms( array(
        'taxonomy'   => 'gallery_category',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );
    ?>
    <div class="wrap emc-gallery-bulk-wrap">
        <h1><?php esc_html_e( 'Bulk Add Gallery Images', 'emc-theme' ); ?></h1>
        <p class="description"><?php esc_html_e( 'Choose a category once, select or upload multiple images, review them, and add them all to the gallery.', 'emc-theme' ); ?></p>

        <?php if ( isset( $_GET['emc_gallery_added'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p>
            <?php
            printf(
                esc_html__( '%d gallery images were added successfully.', 'emc-theme' ),
                absint( $_GET['emc_gallery_added'] )
            );
            ?>
        </p></div>
        <?php endif; ?>

        <?php if ( isset( $_GET['emc_gallery_failed'] ) && absint( $_GET['emc_gallery_failed'] ) ) : ?>
        <div class="notice notice-warning is-dismissible"><p>
            <?php
            printf(
                esc_html__( '%d files could not be added. Only valid images are accepted.', 'emc-theme' ),
                absint( $_GET['emc_gallery_failed'] )
            );
            ?>
        </p></div>
        <?php endif; ?>

        <form class="emc-gallery-bulk-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="emc_gallery_bulk_create">
            <input type="hidden" name="attachment_ids" id="emc-gallery-attachment-ids" value="">
            <?php wp_nonce_field( 'emc_gallery_bulk_create', 'emc_gallery_bulk_nonce' ); ?>

            <div class="emc-gallery-bulk-panel">
                <div class="emc-gallery-bulk-field">
                    <label for="emc-gallery-category"><strong><?php esc_html_e( 'Gallery Category', 'emc-theme' ); ?></strong></label>
                    <select id="emc-gallery-category" name="gallery_category" required>
                        <option value=""><?php esc_html_e( 'Select a category', 'emc-theme' ); ?></option>
                        <?php if ( ! is_wp_error( $categories ) ) : ?>
                            <?php foreach ( $categories as $category ) : ?>
                            <option value="<?php echo esc_attr( $category->term_id ); ?>"><?php echo esc_html( $category->name ); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'Every image in this batch will be assigned to this category.', 'emc-theme' ); ?></p>
                </div>

                <div class="emc-gallery-bulk-actions">
                    <button type="button" class="button button-primary button-hero" id="emc-gallery-select-images">
                        <span class="dashicons dashicons-images-alt2" aria-hidden="true"></span>
                        <?php esc_html_e( 'Select or Upload Images', 'emc-theme' ); ?>
                    </button>
                    <button type="button" class="button" id="emc-gallery-clear-images" hidden><?php esc_html_e( 'Clear Selection', 'emc-theme' ); ?></button>
                </div>
            </div>

            <div class="emc-gallery-selection-head">
                <h2><?php esc_html_e( 'Selected Images', 'emc-theme' ); ?></h2>
                <span id="emc-gallery-selection-count"><?php esc_html_e( '0 selected', 'emc-theme' ); ?></span>
            </div>
            <p class="emc-gallery-empty" id="emc-gallery-empty"><?php esc_html_e( 'Your image previews will appear here.', 'emc-theme' ); ?></p>
            <ul class="emc-gallery-preview-grid" id="emc-gallery-preview-grid" aria-live="polite"></ul>

            <div class="emc-gallery-submit-row">
                <button type="submit" class="button button-primary button-hero" id="emc-gallery-import" disabled>
                    <?php esc_html_e( 'Add Images to Gallery', 'emc-theme' ); ?>
                </button>
                <a class="button button-hero" href="<?php echo esc_url( admin_url( 'edit.php?post_type=emc_gallery' ) ); ?>"><?php esc_html_e( 'Back to Gallery Items', 'emc-theme' ); ?></a>
            </div>
        </form>
    </div>
    <?php
}

/** Convert selected media attachments into published gallery items. */
function emc_gallery_bulk_create() {
    if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_posts' ) ) {
        wp_die( esc_html__( 'You do not have permission to add gallery images.', 'emc-theme' ) );
    }

    check_admin_referer( 'emc_gallery_bulk_create', 'emc_gallery_bulk_nonce' );

    $category_id = absint( $_POST['gallery_category'] ?? 0 );
    $raw_ids     = sanitize_text_field( wp_unslash( $_POST['attachment_ids'] ?? '' ) );
    $image_ids   = array_slice( array_unique( array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) ) ), 0, 100 );
    $term        = $category_id ? term_exists( $category_id, 'gallery_category' ) : false;

    if ( ! $term || ! $image_ids ) {
        wp_safe_redirect( add_query_arg( 'emc_gallery_failed', max( 1, count( $image_ids ) ), admin_url( 'edit.php?post_type=emc_gallery&page=emc-gallery-bulk-upload' ) ) );
        exit;
    }

    $added  = 0;
    $failed = 0;

    foreach ( $image_ids as $attachment_id ) {
        if ( 'attachment' !== get_post_type( $attachment_id ) || ! wp_attachment_is_image( $attachment_id ) ) {
            $failed++;
            continue;
        }

        $existing = get_posts( array(
            'post_type'      => 'emc_gallery',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'   => '_emc_gallery_attachment_id',
                    'value' => $attachment_id,
                ),
                array(
                    'key'   => '_thumbnail_id',
                    'value' => $attachment_id,
                ),
            ),
        ) );

        if ( $existing ) {
            wp_update_post( array(
                'ID'          => $existing[0],
                'post_status' => 'publish',
            ) );
            set_post_thumbnail( $existing[0], $attachment_id );
            update_post_meta( $existing[0], '_emc_gallery_attachment_id', $attachment_id );
            wp_set_object_terms( $existing[0], array( $category_id ), 'gallery_category', true );
            $added++;
            continue;
        }

        $title = trim( get_the_title( $attachment_id ) );
        if ( ! $title ) {
            $file  = get_attached_file( $attachment_id );
            $title = $file ? pathinfo( $file, PATHINFO_FILENAME ) : __( 'Gallery Image', 'emc-theme' );
        }

        $gallery_id = wp_insert_post( array(
            'post_type'   => 'emc_gallery',
            'post_status' => 'publish',
            'post_title'  => sanitize_text_field( $title ),
        ), true );

        if ( is_wp_error( $gallery_id ) ) {
            $failed++;
            continue;
        }

        set_post_thumbnail( $gallery_id, $attachment_id );
        update_post_meta( $gallery_id, '_emc_gallery_attachment_id', $attachment_id );
        wp_set_object_terms( $gallery_id, array( $category_id ), 'gallery_category' );
        $added++;
    }

    $redirect_url = add_query_arg(
        array(
            'post_type'          => 'emc_gallery',
            'page'               => 'emc-gallery-bulk-upload',
            'emc_gallery_added'  => $added,
            'emc_gallery_failed' => $failed,
        ),
        admin_url( 'edit.php' )
    );

    wp_safe_redirect( $redirect_url );
    exit;
}
add_action( 'admin_post_emc_gallery_bulk_create', 'emc_gallery_bulk_create' );

/** Add image previews to the Gallery Items list table. */
function emc_gallery_admin_columns( $columns ) {
    $new_columns = array();

    foreach ( $columns as $key => $label ) {
        $new_columns[ $key ] = $label;
        if ( 'cb' === $key ) {
            $new_columns['emc_gallery_preview'] = __( 'Preview', 'emc-theme' );
        }
    }

    return $new_columns;
}
add_filter( 'manage_emc_gallery_posts_columns', 'emc_gallery_admin_columns' );

function emc_gallery_admin_column_content( $column, $post_id ) {
    if ( 'emc_gallery_preview' !== $column ) {
        return;
    }

    if ( has_post_thumbnail( $post_id ) ) {
        echo get_the_post_thumbnail( $post_id, array( 72, 54 ), array( 'style' => 'width:72px;height:54px;object-fit:cover;border-radius:5px' ) );
    } else {
        echo '<span aria-hidden="true">—</span>';
    }
}
add_action( 'manage_emc_gallery_posts_custom_column', 'emc_gallery_admin_column_content', 10, 2 );

/** Keep the bulk action visible from the Gallery Items list screen. */
function emc_gallery_bulk_upload_view_link( $views ) {
    $views['emc_gallery_bulk_upload'] = sprintf(
        '<a class="button button-primary" href="%s">%s</a>',
        esc_url( admin_url( 'edit.php?post_type=emc_gallery&page=emc-gallery-bulk-upload' ) ),
        esc_html__( 'Bulk Add Images', 'emc-theme' )
    );

    return $views;
}
add_filter( 'views_edit-emc_gallery', 'emc_gallery_bulk_upload_view_link' );
