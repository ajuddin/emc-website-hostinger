<?php
/**
 * Template Part: Social Share Buttons
 * Outputs Facebook, X, WhatsApp, and copy-link share buttons for the current post.
 * @package emc-theme
 */

$post_url   = rawurlencode( get_permalink() );
$post_title = rawurlencode( get_the_title() );
$is_event   = 'emc_event' === get_post_type();
$share_aria = $is_event ? __( 'Share this event', 'emc-theme' ) : __( 'Share this post', 'emc-theme' );
?>
<div class="post-share" aria-label="<?php echo esc_attr( $share_aria ); ?>">
    <span class="post-share-label"><?php esc_html_e( 'Share:', 'emc-theme' ); ?></span>

    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $post_url; ?>"
       class="share-btn share-btn--facebook"
       target="_blank" rel="noopener noreferrer"
       aria-label="<?php esc_attr_e( 'Share on Facebook', 'emc-theme' ); ?>">
        <i class="fab fa-facebook-f" aria-hidden="true"></i>
        <span><?php esc_html_e( 'Facebook', 'emc-theme' ); ?></span>
    </a>

    <a href="https://twitter.com/intent/tweet?url=<?php echo $post_url; ?>&text=<?php echo $post_title; ?>"
       class="share-btn share-btn--twitter"
       target="_blank" rel="noopener noreferrer"
       aria-label="<?php esc_attr_e( 'Share on X', 'emc-theme' ); ?>">
        <i class="fab fa-x-twitter" aria-hidden="true"></i>
        <span><?php esc_html_e( 'X', 'emc-theme' ); ?></span>
    </a>

    <a href="https://wa.me/?text=<?php echo $post_title; ?>%20<?php echo $post_url; ?>"
       class="share-btn share-btn--whatsapp"
       target="_blank" rel="noopener noreferrer"
       aria-label="<?php esc_attr_e( 'Share on WhatsApp', 'emc-theme' ); ?>">
        <i class="fab fa-whatsapp" aria-hidden="true"></i>
        <span><?php esc_html_e( 'WhatsApp', 'emc-theme' ); ?></span>
    </a>

    <button type="button"
            class="share-btn share-btn--copy"
            data-copy-url="<?php echo esc_attr( get_permalink() ); ?>"
            aria-label="<?php esc_attr_e( 'Copy link', 'emc-theme' ); ?>">
        <i class="fas fa-link" aria-hidden="true"></i>
        <span><?php esc_html_e( 'Copy Link', 'emc-theme' ); ?></span>
    </button>
</div>
