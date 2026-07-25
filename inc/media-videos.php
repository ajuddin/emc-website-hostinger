<?php
/**
 * Admin-managed media videos and frontend source helpers.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the Media Videos content type.
 */
function emc_register_media_video_type() {
    register_post_type( 'emc_video', array(
        'labels' => array(
            'name'               => __( 'Media Videos', 'emc-theme' ),
            'singular_name'      => __( 'Media Video', 'emc-theme' ),
            'add_new'            => __( 'Add Video', 'emc-theme' ),
            'add_new_item'       => __( 'Add Media Video', 'emc-theme' ),
            'edit_item'          => __( 'Edit Media Video', 'emc-theme' ),
            'new_item'           => __( 'New Media Video', 'emc-theme' ),
            'view_item'          => __( 'View Media Video', 'emc-theme' ),
            'search_items'       => __( 'Search Media Videos', 'emc-theme' ),
            'not_found'          => __( 'No media videos found.', 'emc-theme' ),
            'not_found_in_trash' => __( 'No media videos found in Trash.', 'emc-theme' ),
            'all_items'          => __( 'All Media Videos', 'emc-theme' ),
            'menu_name'          => __( 'Media Videos', 'emc-theme' ),
        ),
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => false,
        'menu_icon'           => 'dashicons-video-alt3',
        'menu_position'       => 24,
        'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'has_archive'         => false,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
    ) );
}
add_action( 'init', 'emc_register_media_video_type' );

/**
 * Add the video details box.
 */
function emc_media_video_meta_boxes() {
    add_meta_box(
        'emc-media-video-details',
        __( 'Video Details', 'emc-theme' ),
        'emc_media_video_meta_box',
        'emc_video',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'emc_media_video_meta_boxes' );

/**
 * Render the video details box.
 *
 * @param WP_Post $post Current video.
 */
function emc_media_video_meta_box( $post ) {
    $url         = get_post_meta( $post->ID, '_emc_video_url', true );
    $date        = get_post_meta( $post->ID, '_emc_video_date', true );
    $duration    = get_post_meta( $post->ID, '_emc_video_duration', true );
    $description = get_post_meta( $post->ID, '_emc_video_description', true );

    wp_nonce_field( 'emc_save_media_video', 'emc_media_video_nonce' );
    ?>
    <style>
        .emc-video-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        .emc-video-fields label { display: flex; flex-direction: column; gap: 6px; }
        .emc-video-field-full { grid-column: 1 / -1; }
        .emc-video-url-row { display: flex; gap: 8px; }
        .emc-video-url-row .button { flex: 0 0 auto; }
        .emc-video-help { margin: 0; color: #646970; }
        @media (max-width: 782px) {
            .emc-video-fields { grid-template-columns: 1fr; }
            .emc-video-field-full { grid-column: auto; }
            .emc-video-url-row { align-items: stretch; flex-direction: column; }
        }
    </style>
    <div class="emc-video-fields">
        <label class="emc-video-field-full">
            <strong><?php esc_html_e( 'YouTube or video URL', 'emc-theme' ); ?></strong>
            <span class="emc-video-url-row">
                <input type="url" class="widefat" id="emc_video_url" name="emc_video_url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://www.youtube.com/watch?v=...">
                <button type="button" class="button" id="emc-select-video"><?php esc_html_e( 'Select uploaded video', 'emc-theme' ); ?></button>
            </span>
            <span class="emc-video-help"><?php esc_html_e( 'Paste a YouTube, Vimeo, MP4, WebM or other direct video URL. YouTube and Vimeo play inside the page.', 'emc-theme' ); ?></span>
        </label>
        <label>
            <strong><?php esc_html_e( 'Display date', 'emc-theme' ); ?></strong>
            <input type="date" name="emc_video_date" value="<?php echo esc_attr( $date ); ?>">
        </label>
        <label>
            <strong><?php esc_html_e( 'Duration', 'emc-theme' ); ?></strong>
            <input type="text" name="emc_video_duration" value="<?php echo esc_attr( $duration ); ?>" placeholder="45:20">
        </label>
        <label class="emc-video-field-full">
            <strong><?php esc_html_e( 'Description', 'emc-theme' ); ?></strong>
            <textarea class="widefat" name="emc_video_description" rows="4"><?php echo esc_textarea( $description ); ?></textarea>
        </label>
        <p class="emc-video-help emc-video-field-full">
            <?php esc_html_e( 'Set a Featured Image for a custom thumbnail. For YouTube videos, the YouTube thumbnail is used automatically when no Featured Image is set. Use the Order field to control display order; the first video is featured.', 'emc-theme' ); ?>
        </p>
    </div>
    <?php
}

/**
 * Save video details.
 *
 * @param int $post_id Video ID.
 */
function emc_save_media_video( $post_id ) {
    if (
        ! isset( $_POST['emc_media_video_nonce'] )
        || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['emc_media_video_nonce'] ) ), 'emc_save_media_video' )
        || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        || ! current_user_can( 'edit_post', $post_id )
        || 'emc_video' !== get_post_type( $post_id )
    ) {
        return;
    }

    update_post_meta( $post_id, '_emc_video_url', esc_url_raw( wp_unslash( $_POST['emc_video_url'] ?? '' ) ) );
    update_post_meta( $post_id, '_emc_video_date', sanitize_text_field( wp_unslash( $_POST['emc_video_date'] ?? '' ) ) );
    update_post_meta( $post_id, '_emc_video_duration', sanitize_text_field( wp_unslash( $_POST['emc_video_duration'] ?? '' ) ) );
    update_post_meta( $post_id, '_emc_video_description', sanitize_textarea_field( wp_unslash( $_POST['emc_video_description'] ?? '' ) ) );
}
add_action( 'save_post_emc_video', 'emc_save_media_video' );

/**
 * Load the Media Library selector on video edit screens.
 *
 * @param string $hook Current admin page.
 */
function emc_media_video_admin_assets( $hook ) {
    if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( ! $screen || 'emc_video' !== $screen->post_type ) {
        return;
    }

    wp_enqueue_media();
    $script = EMC_DIR . '/assets/js/admin-videos.js';
    wp_enqueue_script(
        'emc-admin-videos',
        EMC_ASSETS . '/js/admin-videos.js',
        array(),
        file_exists( $script ) ? filemtime( $script ) : EMC_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'emc_media_video_admin_assets' );

/**
 * Extract playable source information from a video URL.
 *
 * @param string $url Video URL.
 * @return array
 */
function emc_get_video_source( $url ) {
    $url    = esc_url_raw( $url );
    $source = array(
        'type'      => 'external',
        'play_url'  => $url,
        'thumbnail' => '',
    );

    if ( ! $url ) {
        $source['type'] = 'none';
        return $source;
    }

    $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
    $path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );

    if ( false !== strpos( $host, 'youtu.be' ) ) {
        $youtube_id = explode( '/', $path )[0] ?? '';
    } elseif ( false !== strpos( $host, 'youtube.com' ) || false !== strpos( $host, 'youtube-nocookie.com' ) ) {
        parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
        $youtube_id = $query['v'] ?? '';
        if ( ! $youtube_id && preg_match( '#(?:embed|shorts|live)/([A-Za-z0-9_-]+)#', $path, $match ) ) {
            $youtube_id = $match[1];
        }
    } else {
        $youtube_id = '';
    }

    if ( $youtube_id && preg_match( '/^[A-Za-z0-9_-]{6,20}$/', $youtube_id ) ) {
        return array(
            'type'      => 'youtube',
            'play_url'  => 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $youtube_id ) . '?autoplay=1&rel=0',
            'thumbnail' => 'https://i.ytimg.com/vi/' . rawurlencode( $youtube_id ) . '/hqdefault.jpg',
        );
    }

    if ( false !== strpos( $host, 'vimeo.com' ) && preg_match( '#(?:video/)?([0-9]{6,12})#', $path, $match ) ) {
        return array(
            'type'      => 'vimeo',
            'play_url'  => 'https://player.vimeo.com/video/' . rawurlencode( $match[1] ) . '?autoplay=1',
            'thumbnail' => '',
        );
    }

    $extension = strtolower( pathinfo( (string) wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
    if ( in_array( $extension, array( 'mp4', 'm4v', 'webm', 'ogv', 'ogg' ), true ) ) {
        $source['type'] = 'self-hosted';
    }

    return $source;
}

/**
 * Return published videos for the media page.
 *
 * @return array[]
 */
function emc_get_media_videos() {
    $query = new WP_Query( array(
        'post_type'      => 'emc_video',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
        'order'          => 'ASC',
    ) );
    $videos = array();

    while ( $query->have_posts() ) {
        $query->the_post();
        $video_id = get_the_ID();
        $url      = get_post_meta( $video_id, '_emc_video_url', true );
        $source   = emc_get_video_source( $url );
        $thumb    = get_the_post_thumbnail_url( $video_id, 'large' );
        $date     = get_post_meta( $video_id, '_emc_video_date', true );

        $videos[] = array(
            'title'       => get_the_title(),
            'description' => get_post_meta( $video_id, '_emc_video_description', true ),
            'date'        => $date ? date_i18n( 'j F Y', strtotime( $date ) ) : '',
            'duration'    => get_post_meta( $video_id, '_emc_video_duration', true ),
            'thumbnail'   => $thumb ?: $source['thumbnail'],
            'type'        => $source['type'],
            'play_url'    => $source['play_url'],
        );
    }
    wp_reset_postdata();

    // Preserve a previously configured featured video until it is recreated
    // in Media Videos. Raw values are used because the old fields are no
    // longer shown in the editor.
    if ( ! $videos ) {
        $media_page = get_page_by_path( 'media' );
        $media_id   = $media_page ? $media_page->ID : 0;
        $legacy_url = get_theme_mod( 'media_video_url', '' );

        if ( ! $legacy_url && $media_id ) {
            $legacy_url = get_post_meta( $media_id, 'media_video_url', true );
        }

        if ( $legacy_url ) {
            $source       = emc_get_video_source( $legacy_url );
            $legacy_thumb = $media_id ? get_post_meta( $media_id, 'media_video_thumbnail', true ) : '';
            if ( is_numeric( $legacy_thumb ) ) {
                $legacy_thumb = wp_get_attachment_image_url( (int) $legacy_thumb, 'large' );
            } elseif ( is_array( $legacy_thumb ) ) {
                $legacy_thumb = $legacy_thumb['url'] ?? '';
            }

            $legacy_value = static function ( $key, $default = '' ) use ( $media_id ) {
                $value = get_theme_mod( $key, '' );
                if ( ! $value && $media_id ) {
                    $value = get_post_meta( $media_id, $key, true );
                }
                return $value ?: $default;
            };

            $videos[] = array(
                'title'       => $legacy_value( 'media_video_title', __( 'Featured Video', 'emc-theme' ) ),
                'description' => $legacy_value( 'media_video_desc', '' ),
                'date'        => $legacy_value( 'media_video_date', '' ),
                'duration'    => $legacy_value( 'media_video_duration', '' ),
                'thumbnail'   => $legacy_thumb ?: $source['thumbnail'],
                'type'        => $source['type'],
                'play_url'    => $source['play_url'],
            );
        }
    }

    return $videos;
}

/**
 * Add useful columns to the video list.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function emc_media_video_columns( $columns ) {
    return array(
        'cb'         => $columns['cb'],
        'thumbnail'  => __( 'Thumbnail', 'emc-theme' ),
        'title'      => __( 'Title', 'emc-theme' ),
        'video_url'  => __( 'Video source', 'emc-theme' ),
        'video_date' => __( 'Display date', 'emc-theme' ),
        'order'      => __( 'Order', 'emc-theme' ),
        'date'       => $columns['date'],
    );
}
add_filter( 'manage_emc_video_posts_columns', 'emc_media_video_columns' );

/**
 * Render video admin columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Video ID.
 */
function emc_media_video_column_content( $column, $post_id ) {
    if ( 'thumbnail' === $column ) {
        $thumbnail = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
        if ( $thumbnail ) {
            echo '<img src="' . esc_url( $thumbnail ) . '" alt="" style="width:70px;height:44px;object-fit:cover;border-radius:4px">';
        } else {
            echo '<span aria-hidden="true">—</span>';
        }
    } elseif ( 'video_url' === $column ) {
        $source = emc_get_video_source( get_post_meta( $post_id, '_emc_video_url', true ) );
        echo esc_html( ucfirst( str_replace( '-', ' ', $source['type'] ) ) );
    } elseif ( 'video_date' === $column ) {
        echo esc_html( get_post_meta( $post_id, '_emc_video_date', true ) ?: '—' );
    } elseif ( 'order' === $column ) {
        echo esc_html( (string) get_post_field( 'menu_order', $post_id ) );
    }
}
add_action( 'manage_emc_video_posts_custom_column', 'emc_media_video_column_content', 10, 2 );
