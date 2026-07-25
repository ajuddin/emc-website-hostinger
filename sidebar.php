<?php
/**
 * Dynamic blog sidebar.
 *
 * Manage its contents from Appearance > Widgets > Blog Sidebar. The template
 * intentionally has no hardcoded fallback widgets.
 *
 * @package emc-theme
 */

if ( ! (bool) emc_option( 'emc_blog_show_sidebar', 1 ) || ! is_active_sidebar( 'sidebar-blog' ) ) {
    return;
}
?>
<aside class="blog-sidebar" aria-label="<?php esc_attr_e( 'Blog sidebar', 'emc-theme' ); ?>">
    <?php dynamic_sidebar( 'sidebar-blog' ); ?>
</aside>
