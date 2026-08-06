/**
 * EMC Theme — media.js
 * Phase 10: Enhanced media page interactions.
 *
 * Features:
 *  - Tab system (Photos / Videos)
 *  - Lightbox with keyboard ← → navigation + touch swipe
 *  - Scroll reveal
 *
 * @package emc-theme
 */

document.addEventListener('DOMContentLoaded', () => {

    /* =====================
       Inline Video Players
       ===================== */
    const videoPlayers = [...document.querySelectorAll('.js-video-player')];
    let activeVideoPlayer = null;

    videoPlayers.forEach(player => {
        player._emcOriginalMarkup = player.innerHTML;

        const stopPlayer = () => {
            player.innerHTML = player._emcOriginalMarkup;
            player.classList.remove('is-playing');
            if (activeVideoPlayer === player) activeVideoPlayer = null;
        };

        const playVideo = () => {
            const type = player.dataset.videoType;
            const source = player.dataset.videoSrc;
            const title = player.dataset.videoTitle || 'Media video';
            if (!source) return;

            if (type === 'external') {
                window.open(source, '_blank', 'noopener,noreferrer');
                return;
            }

            if (activeVideoPlayer && activeVideoPlayer !== player) {
                activeVideoPlayer.innerHTML = activeVideoPlayer._emcOriginalMarkup;
                activeVideoPlayer.classList.remove('is-playing');
            }

            let media;
            if (type === 'youtube' || type === 'vimeo') {
                media = document.createElement('iframe');
                media.src = source;
                media.title = title;
                media.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
                media.allowFullscreen = true;
                media.referrerPolicy = 'strict-origin-when-cross-origin';
            } else {
                media = document.createElement('video');
                media.src = source;
                media.controls = true;
                media.autoplay = true;
                media.playsInline = true;
                media.setAttribute('preload', 'metadata');
            }

            media.className = 'video-inline-player';
            player.replaceChildren(media);
            player.classList.add('is-playing');
            activeVideoPlayer = player;
        };

        player.addEventListener('click', event => {
            if (player.classList.contains('is-playing')) return;
            event.preventDefault();
            playVideo();
        });

        player.addEventListener('keydown', event => {
            if ((event.key === 'Enter' || event.key === ' ') && !player.classList.contains('is-playing')) {
                event.preventDefault();
                playVideo();
            }
        });

        player._emcStopPlayer = stopPlayer;
    });

    /* =====================
       Custom Tabs
       ===================== */
    const tabBtns   = document.querySelectorAll('.media-tab-btn');
    const tabPanels = document.querySelectorAll('.media-tab-panel');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;

            if (target !== 'videos' && activeVideoPlayer?._emcStopPlayer) {
                activeVideoPlayer._emcStopPlayer();
            }

            tabBtns.forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            tabPanels.forEach(p => {
                p.classList.remove('active');
                p.setAttribute('hidden', '');
            });

            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');

            const panel = document.getElementById(`tab-${target}`);
            if (panel) {
                panel.classList.add('active');
                panel.removeAttribute('hidden');

                // Re-trigger scroll reveal for newly visible items
                panel.querySelectorAll('.scroll-reveal').forEach(el => {
                    el.classList.remove('reveal');
                    setTimeout(() => el.classList.add('reveal'), 60);
                });
            }
        });
    });

    /* =====================
       Gallery Category Filter
       ===================== */
    const filterBar   = document.getElementById('gallery-filter-bar');
    const galleryGrid = document.getElementById('photo-gallery');

    if (filterBar && galleryGrid) {
        const filterBtns = filterBar.querySelectorAll('.gallery-filter-btn');
        const allItems   = galleryGrid.querySelectorAll('.gallery-item');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.dataset.filter;

                // Update active button state
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Show / hide items
                allItems.forEach(item => {
                    const cats = item.dataset.category || '';
                    if (filter === 'all' || cats.split(' ').includes(filter)) {
                        item.classList.remove('gallery-item--hidden');
                    } else {
                        item.classList.add('gallery-item--hidden');
                    }
                });
            });
        });
    }

    /* =====================
       Lightbox
       ===================== */
    const galleryItems = Array.from(document.querySelectorAll('.gallery-item[data-index]'));
    const lightbox        = document.getElementById('lightbox');
    const lightboxImg     = document.getElementById('lightbox-img');
    const lightboxCaption = document.getElementById('lightbox-caption');
    const lightboxClose   = document.getElementById('lightbox-close');
    const lightboxPrev    = document.getElementById('lightbox-prev');
    const lightboxNext    = document.getElementById('lightbox-next');

    let currentIndex = 0;

    /**
     * Populate lightbox with the item at `index`.
     */
    function showLightbox(index) {
        const item = galleryItems[index];
        if (!item) return;

        currentIndex = index;
        const imgEl    = item.querySelector('img');
        const caption  = item.querySelector('.gallery-overlay span, [data-caption]');

        // Update image src (use data-full if provided, fall back to src)
        if (imgEl) {
            lightboxImg.src = item.dataset.full || imgEl.src;
            lightboxImg.alt = imgEl.alt || '';
            lightboxImg.style.display = 'block';
        } else {
            lightboxImg.style.display = 'none';
        }

        if (lightboxCaption) {
            lightboxCaption.textContent = caption ? caption.textContent : '';
        }

        // Update prev/next button visibility
        if (lightboxPrev) lightboxPrev.style.display = galleryItems.length > 1 ? '' : 'none';
        if (lightboxNext) lightboxNext.style.display = galleryItems.length > 1 ? '' : 'none';

        lightbox.classList.add('active');
        lightbox.removeAttribute('hidden');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        lightboxClose?.focus();
    }

    function closeLightbox() {
        lightbox.classList.remove('active');
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        window.setTimeout(() => {
            if (!lightbox.classList.contains('active')) lightbox.setAttribute('hidden', '');
        }, 300);
        // Restore focus to the gallery item that was clicked
        galleryItems[currentIndex]?.focus();
    }

    function prevSlide() {
        showLightbox((currentIndex - 1 + galleryItems.length) % galleryItems.length);
    }

    function nextSlide() {
        showLightbox((currentIndex + 1) % galleryItems.length);
    }

    // Assign indices via data-index or order
    galleryItems.forEach((item, i) => {
        item.setAttribute('data-index', i);
        item.setAttribute('tabindex', '0');
        item.setAttribute('role', 'button');
        item.setAttribute('aria-label', `View image ${i + 1}`);

        item.addEventListener('click', () => showLightbox(i));
        item.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                showLightbox(i);
            }
        });
    });

    // Close actions
    lightboxClose?.addEventListener('click', closeLightbox);
    lightbox?.addEventListener('click', (e) => {
        if (e.target === lightbox) closeLightbox();
    });

    // Prev / Next buttons
    lightboxPrev?.addEventListener('click', (e) => { e.stopPropagation(); prevSlide(); });
    lightboxNext?.addEventListener('click', (e) => { e.stopPropagation(); nextSlide(); });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (!lightbox?.classList.contains('active')) return;

        switch (e.key) {
            case 'Escape':      closeLightbox(); break;
            case 'ArrowLeft':   prevSlide();     break;
            case 'ArrowRight':  nextSlide();     break;
        }
    });

    // Touch swipe support
    let touchStartX = 0;
    lightbox?.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    lightbox?.addEventListener('touchend', (e) => {
        const delta = touchStartX - e.changedTouches[0].screenX;
        if (Math.abs(delta) > 50) {
            delta > 0 ? nextSlide() : prevSlide();
        }
    }, { passive: true });

    /* =====================
       Scroll Reveal
       ===================== */
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('reveal');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });

    document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));
});
