<?php
/**
 * Template Part: Media & Latest News Preview
 * @package emc-theme
 */

$media_url = get_permalink( get_page_by_path( 'media' ) ) ?: home_url( '/media/' );

// Query latest blog posts
$news_query = new WP_Query( array(
    'post_type'      => 'post',
    'posts_per_page' => max( 1, absint( emc_site_setting( 'emc_home_news_count', 3 ) ) ),
    'post_status'    => 'publish',
) );

$emc_videos = array();
foreach ( emc_get_media_videos() as $video ) {
    if ( 'youtube' === $video['type'] && preg_match( '#/embed/([A-Za-z0-9_-]+)#', $video['play_url'], $match ) ) {
        $video['id'] = $match[1];
        $emc_videos[] = $video;
    }
}
$emc_videos = array_slice( $emc_videos, 0, max( 1, absint( emc_site_setting( 'emc_home_video_count', 3 ) ) ) );
$featured   = $emc_videos[0] ?? null;
$youtube_url = emc_option( 'emc_social_youtube', '' );
?>
<section class="homepage-media section-padding" id="media-news" style="background:var(--white);" aria-labelledby="media-heading">
    <div class="container">
        <div class="section-header">
            <span class="subtitle"><?php esc_html_e( 'Stay Connected', 'emc-theme' ); ?></span>
            <h2 id="media-heading"><?php esc_html_e( 'Media & Latest News', 'emc-theme' ); ?></h2>
        </div>

        <div class="media-news-layout">

            <!-- Left: YouTube Videos -->
            <div class="media-preview-col scroll-reveal" aria-label="<?php esc_attr_e( 'Latest Videos', 'emc-theme' ); ?>">
                <div class="media-preview-label">
                    <i class="fab fa-youtube" aria-hidden="true"></i>
                    <?php esc_html_e( 'Latest Videos', 'emc-theme' ); ?>
                </div>

                <?php if ( $featured ) : ?>
                <!-- Featured Video — managed in Media Videos -->
                <div class="media-preview-featured">
                    <button
                        class="media-preview-thumb yt-play-btn"
                        data-video-id="<?php echo esc_attr( $featured['id'] ); ?>"
                        aria-label="<?php printf( esc_attr__( 'Play: %s', 'emc-theme' ), esc_attr( $featured['title'] ) ); ?>"
                        style="all:unset;display:block;width:100%;position:relative;height:220px;overflow:hidden;cursor:pointer;"
                    >
                        <img
                            src="https://img.youtube.com/vi/<?php echo esc_attr( $featured['id'] ); ?>/hqdefault.jpg"
                            alt="<?php echo esc_attr( $featured['title'] ); ?>"
                            loading="lazy"
                            style="width:100%;height:100%;object-fit:cover;display:block;transition:transform .5s ease;"
                            onerror="this.src='https://img.youtube.com/vi/<?php echo esc_attr( $featured['id'] ); ?>/sddefault.jpg'"
                        >
                        <div class="media-play-overlay" aria-hidden="true">
                            <div class="play-btn yt-pulse"><i class="fab fa-youtube"></i></div>
                        </div>
                    </button>
                    <div class="media-preview-info">
                        <span class="media-preview-badge video">
                            <i class="fab fa-youtube" aria-hidden="true"></i>
                            <?php esc_html_e( 'YouTube', 'emc-theme' ); ?>
                        </span>
                        <h3><?php echo esc_html( $featured['title'] ); ?></h3>
                        <span class="media-preview-date"><?php echo esc_html( $featured['date'] ); ?></span>
                    </div>
                </div>

                <!-- Mini Video Cards — all 3 -->
                <div class="media-preview-grid">
                    <?php foreach ( $emc_videos as $vid ) : ?>
                    <button
                        class="media-mini-card yt-play-btn"
                        data-video-id="<?php echo esc_attr( $vid['id'] ); ?>"
                        aria-label="<?php printf( esc_attr__( 'Play: %s', 'emc-theme' ), esc_attr( $vid['title'] ) ); ?>"
                        style="all:unset;display:flex;flex-direction:column;cursor:pointer;border-radius:12px;overflow:hidden;border:1px solid var(--border-color);transition:transform .2s;width:100%;background:var(--white);"
                    >
                        <div class="media-mini-thumb" style="position:relative;overflow:hidden;height:80px;flex-shrink:0;">
                            <img
                                src="https://img.youtube.com/vi/<?php echo esc_attr( $vid['id'] ); ?>/mqdefault.jpg"
                                alt="<?php echo esc_attr( $vid['title'] ); ?>"
                                loading="lazy"
                                style="width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s;"
                                onerror="this.src='https://img.youtube.com/vi/<?php echo esc_attr( $vid['id'] ); ?>/default.jpg'"
                            >
                            <div class="mini-play-icon" aria-hidden="true"><i class="fab fa-youtube"></i></div>
                        </div>
                        <span style="display:block;padding:.5rem .6rem;font-size:calc(var(--step--2) - .1rem);color:var(--text-muted);line-height:1.3;"><?php echo esc_html( $vid['title'] ); ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>

                <?php else : ?>
                    <p><?php esc_html_e( 'New videos will be added here soon.', 'emc-theme' ); ?></p>
                <?php endif; ?>

                <?php if ( $youtube_url ) : ?>
                <a href="<?php echo esc_url( $youtube_url ); ?>" target="_blank" rel="noopener noreferrer"
                   class="btn btn-outline" style="width:100%;justify-content:center;margin-top:1.5rem;">
                    <i class="fab fa-youtube" aria-hidden="true"></i>
                    <?php esc_html_e( 'Visit Our YouTube Channel', 'emc-theme' ); ?>
                </a>
                <?php endif; ?>
            </div>

            <!-- Right: News -->
            <div class="news-preview-col">
                <div class="media-preview-label">
                    <i class="fas fa-newspaper" aria-hidden="true"></i>
                    <?php esc_html_e( 'Latest News', 'emc-theme' ); ?>
                </div>
                <div class="news-preview-list">
                    <?php if ( $news_query->have_posts() ) : ?>
                        <?php
                        $delay = 0;
                        while ( $news_query->have_posts() ) : $news_query->the_post();
                            $thumb = get_the_post_thumbnail_url( get_the_ID(), 'emc-thumbnail' );
                        ?>
                        <article class="news-preview-card scroll-reveal" style="transition-delay:<?php echo esc_attr( $delay . 's' ); ?>">
                            <div class="news-preview-img"
                                <?php if ( $thumb ) : ?>
                                    style="background-image:url('<?php echo esc_url( $thumb ); ?>')"
                                <?php else : ?>
                                    style="background:linear-gradient(135deg,var(--deep-blue),#0E3020)"
                                <?php endif; ?>
                                role="img"
                                aria-label="<?php the_title_attribute(); ?>"
                            ></div>
                            <div class="news-preview-body">
                                <div class="news-preview-meta">
                                    <?php
                                    $cats = get_the_category();
                                    if ( $cats ) :
                                    ?>
                                    <span class="news-preview-cat"><?php echo esc_html( $cats[0]->name ); ?></span>
                                    <?php endif; ?>
                                    <span class="news-preview-date"><?php echo get_the_date(); ?></span>
                                </div>
                                <h4><?php the_title(); ?></h4>
                                <p><?php echo wp_trim_words( get_the_excerpt(), 18 ); ?></p>
                                <a href="<?php the_permalink(); ?>" class="news-preview-link">
                                    <?php esc_html_e( 'Read More', 'emc-theme' ); ?>
                                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                        <?php
                        $delay = round( $delay + 0.1, 1 );
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    <?php elseif ( false ) : ?>
                        <!-- Static fallback cards until posts are published -->
                        <?php
                        $static_news = array(
                            array(
                                'cat'   => __( 'Announcement', 'emc-theme' ),
                                'date'  => '12 May 2026',
                                'title' => __( 'Building Fund Reaches 68% Target', 'emc-theme' ),
                                'desc'  => __( 'Alhamdulillah, through the generous support of our community, the campaign has reached an incredible milestone.', 'emc-theme' ),
                                'delay' => '0s',
                                'bg'    => 'background-image:url(' . EMC_ASSETS . '/gallery/Fundraising Event/20250316_170418-300x225.jpg);background-size:cover;background-position:center',
                            ),
                            array(
                                'cat'   => __( 'Community', 'emc-theme' ),
                                'date'  => '05 May 2026',
                                'title' => __( 'EMC Partners with Local Food Bank', 'emc-theme' ),
                                'desc'  => __( 'We are proud to announce a new partnership providing weekly collections and distribution support.', 'emc-theme' ),
                                'delay' => '0.1s',
                                'bg'    => 'background-image:url(' . EMC_ASSETS . '/gallery/Quran Group Study/WhatsApp-Image-2025-08-21-at-21.04.12-5-300x225.jpeg);background-size:cover;background-position:center',
                            ),
                            array(
                                'cat'   => __( 'Education', 'emc-theme' ),
                                'date'  => '28 Apr 2026',
                                'title' => __( 'New Weekend Madrasah Curriculum Launched', 'emc-theme' ),
                                'desc'  => __( 'Starting next term, our Madrasah rolls out a modernized curriculum focusing on interactive learning.', 'emc-theme' ),
                                'delay' => '0.2s',
                                'bg'    => 'background-image:url(' . EMC_ASSETS . '/gallery/Outdoor Activity 2024/out_3-1-300x300.jpeg);background-size:cover;background-position:center',
                            ),
                        );
                        foreach ( $static_news as $item ) :
                        ?>
                        <article class="news-preview-card scroll-reveal" style="transition-delay:<?php echo esc_attr( $item['delay'] ); ?>">
                            <div class="news-preview-img" style="<?php echo esc_attr( $item['bg'] ); ?>"></div>
                            <div class="news-preview-body">
                                <div class="news-preview-meta">
                                    <span class="news-preview-cat"><?php echo esc_html( $item['cat'] ); ?></span>
                                    <span class="news-preview-date"><?php echo esc_html( $item['date'] ); ?></span>
                                </div>
                                <h4><?php echo esc_html( $item['title'] ); ?></h4>
                                <p><?php echo esc_html( $item['desc'] ); ?></p>
                                <a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/news/' ) ); ?>" class="news-preview-link">
                                    <?php esc_html_e( 'Read More', 'emc-theme' ); ?>
                                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/news/' ) ); ?>" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:1.5rem;">
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    <?php esc_html_e( 'All News & Updates', 'emc-theme' ); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- YouTube Lightbox Modal (outside grid, at bottom of section) -->
<div id="yt-modal" class="yt-modal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Video player', 'emc-theme' ); ?>" style="display:none;">
    <div class="yt-modal-backdrop"></div>
    <div class="yt-modal-inner">
        <button class="yt-modal-close" aria-label="<?php esc_attr_e( 'Close video', 'emc-theme' ); ?>">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
        <div class="yt-modal-embed">
            <iframe
                id="yt-iframe"
                src=""
                title="<?php esc_attr_e( 'YouTube video player', 'emc-theme' ); ?>"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
            ></iframe>
        </div>
    </div>
</div>
<script>
( function() {
    var modal    = document.getElementById('yt-modal');
    var iframe   = document.getElementById('yt-iframe');
    var closeBtn = document.querySelector('.yt-modal-close');
    var backdrop = document.querySelector('.yt-modal-backdrop');
    if ( ! modal ) return;

    function openVideo( id ) {
        iframe.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0';
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        if ( closeBtn ) closeBtn.focus();
    }
    function closeVideo() {
        modal.style.display = 'none';
        iframe.src = '';
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.yt-play-btn').forEach( function(btn) {
        btn.addEventListener('click', function() { openVideo( this.dataset.videoId ); });
    });
    if ( closeBtn ) closeBtn.addEventListener('click', closeVideo);
    if ( backdrop ) backdrop.addEventListener('click', closeVideo);
    document.addEventListener('keydown', function(e) {
        if ( e.key === 'Escape' && modal.style.display !== 'none' ) closeVideo();
    });
} )();
</script>
