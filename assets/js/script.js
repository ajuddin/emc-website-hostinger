document.addEventListener('DOMContentLoaded', () => {
    /* ==========================================================================
       Header & Navigation (Spring Animation)
       ========================================================================== */
    const header = document.getElementById('header');
    const mobileToggle = document.getElementById('mobile-toggle');
    const closeToggle = document.getElementById('close-toggle');
    const mobileNav = document.getElementById('mobile-nav');

    // Sticky Header
    const handleScroll = () => {
        if (window.scrollY > 20) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    };
    
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll(); // Init

    /* ── Position header directly below the prayer top bar ─────────────────
       Uses real measured height so it works regardless of topbar content
       changes (prayer data loading, font size, etc.)
    ────────────────────────────────────────────────────────────────────── */
    const topbar = document.getElementById('prayer-top-bar');
    const adminBar = document.getElementById('wpadminbar');

    function positionHeader() {
        if (!header) return;
        const topbarRect = topbar?.getBoundingClientRect();
        const topbarVisible = !!topbarRect
            && topbarRect.height > 0
            && window.getComputedStyle(topbar).display !== 'none';
        const adminBarRect = adminBar?.getBoundingClientRect();
        const adminBarBottom = adminBarRect?.height > 0 ? adminBarRect.bottom : 0;
        const headerTop = topbarVisible ? topbarRect.bottom : adminBarBottom;
        const topbarH = topbarVisible ? topbarRect.height : 0;

        // Inline !important replaces the old fixed 90px/admin-bar offsets.
        // Measuring the actual bottom edge prevents any visible strip between bars.
        header.style.setProperty('top', Math.ceil(headerTop) + 'px', 'important');
        // Keep CSS custom property in sync for hero padding
        document.documentElement.style.setProperty('--topbar-h', topbarH + 'px');
        document.documentElement.style.setProperty(
            '--header-stack-h',
            Math.ceil(header.getBoundingClientRect().bottom - adminBarBottom) + 'px'
        );
    }

    positionHeader();                                          // run on load
    window.addEventListener('resize', positionHeader, { passive: true });
    // Re-run after prayer JS populates the topbar (may change its height)
    setTimeout(positionHeader, 800);
    setTimeout(positionHeader, 2000);
    if ('ResizeObserver' in window && topbar) {
        new ResizeObserver(positionHeader).observe(topbar);
    }

    // Mobile Menu Toggle
    if (mobileToggle && mobileNav && closeToggle) {
        const toggleMenu = () => {
            const isActive = mobileNav.classList.contains('active');
            if (!isActive) {
                mobileNav.classList.add('active');
                document.body.style.overflow = 'hidden';
            } else {
                mobileNav.classList.remove('active');
                document.body.style.overflow = '';
            }
        };

        mobileToggle.addEventListener('click', toggleMenu);
        closeToggle.addEventListener('click', toggleMenu);

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (mobileNav.classList.contains('active') && !mobileNav.contains(e.target) && !mobileToggle.contains(e.target)) {
                toggleMenu();
            }
        });
    }

    /* ==========================================================================
       Magnetic Buttons Effect
       ========================================================================== */
    const magneticBtns = document.querySelectorAll('.magnetic-btn');
    
    magneticBtns.forEach(wrapper => {
        const btn = wrapper.querySelector('.btn');
        
        wrapper.addEventListener('mousemove', (e) => {
            const rect = wrapper.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            // Subtle pull
            btn.style.transform = `translate(${x * 0.2}px, ${y * 0.2}px)`;
        });
        
        wrapper.addEventListener('mouseleave', () => {
            btn.style.transform = `translate(0px, 0px)`;
        });
    });

    /* ==========================================================================
       Quick Donate Interaction â€” Inline Custom Amount
       ========================================================================== */
    const amountBtns = document.querySelectorAll('.amount-btn');
    const customRow  = document.getElementById('custom-amount-row');
    const customInput = document.getElementById('custom-amount-input');

    if (amountBtns.length > 0) {
        amountBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();

                // Deactivate all buttons
                amountBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                if (btn.classList.contains('custom')) {
                    // Show inline input
                    if (customRow) {
                        customRow.classList.add('open');
                        customRow.setAttribute('aria-hidden', 'false');
                        // Focus the input after the transition starts
                        setTimeout(() => customInput && customInput.focus(), 100);
                    }
                } else {
                    // Hide inline input & clear it
                    if (customRow) {
                        customRow.classList.remove('open');
                        customRow.setAttribute('aria-hidden', 'true');
                    }
                    if (customInput) customInput.value = '';
                }
            });
        });

        // Typing in the custom input keeps "Other" active
        if (customInput) {
            customInput.addEventListener('input', () => {
                const customBtn = document.querySelector('.amount-btn.custom');
                if (customBtn) {
                    amountBtns.forEach(b => b.classList.remove('active'));
                    customBtn.classList.add('active');
                }
            });
        }
    }

    /* ==========================================================================
       Scroll Reveal Animations (Intersection Observer fallback/enhancement)
       ========================================================================== */
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('reveal');
                observer.unobserve(entry.target); // Only animate once
            }
        });
    }, observerOptions);

    const revealElements = document.querySelectorAll('.scroll-reveal');
    revealElements.forEach(el => observer.observe(el));

    /* ==========================================================================
       Newsletter Form
       ========================================================================== */
    const newsletterForm = document.getElementById('newsletter-form');
    const nlSuccess = document.getElementById('nl-success');
    const nlEmail = document.getElementById('nl-email');
    const nlError = document.getElementById('nl-error');

    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!nlEmail || !nlEmail.value.trim() || !nlEmail.validity.valid) {
                nlEmail.style.borderColor = '#E53935';
                nlEmail.focus();
                if (nlError) {
                    nlError.textContent = 'Please enter a valid email address.';
                    nlError.style.display = 'block';
                }
                return;
            }

            const consent = newsletterForm.querySelector('[name="consent"]');
            if (!consent?.checked) {
                consent?.focus();
                if (nlError) {
                    nlError.textContent = 'Please confirm that you agree to receive newsletter emails.';
                    nlError.style.display = 'block';
                }
                return;
            }

            const btn = newsletterForm.querySelector('[type="submit"]');
            const btnLabel = btn?.querySelector('span');
            const defaultLabel = btnLabel?.textContent || 'Subscribe';
            if (btnLabel) btnLabel.textContent = 'Subscribing...';
            btn.disabled = true;
            if (nlError) {
                nlError.textContent = '';
                nlError.style.display = 'none';
            }

            try {
                const response = await fetch(newsletterForm.dataset.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: new FormData(newsletterForm),
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result?.data?.message || 'Your signup could not be completed.');
                }
                newsletterForm.querySelector('.newsletter-input-wrap').style.display = 'none';
                btn.style.display = 'none';
                newsletterForm.querySelector('.newsletter-consent').style.display = 'none';
                newsletterForm.querySelector('.newsletter-disclaimer').style.display = 'none';
                if (nlSuccess) {
                    const successText = nlSuccess.querySelector('span');
                    if (successText) successText.textContent = result?.data?.message || 'Thank you for subscribing.';
                    nlSuccess.style.display = 'flex';
                }
            } catch (error) {
                if (nlError) {
                    nlError.textContent = error.message || 'Your signup could not be completed. Please try again.';
                    nlError.style.display = 'block';
                }
            } finally {
                btn.disabled = false;
                if (btnLabel) btnLabel.textContent = defaultLabel;
            }
        });

        if (nlEmail) {
            nlEmail.addEventListener('input', () => {
                nlEmail.style.borderColor = '';
                if (nlError) nlError.style.display = 'none';
            });
        }
    }

    /* ==========================================================================
       Cookie Consent Banner
       ========================================================================== */
    const COOKIE_KEY = 'emc_cookie_consent';

    if (!localStorage.getItem(COOKIE_KEY)) {
        const banner = document.createElement('div');
        banner.className = 'cookie-banner';
        banner.id = 'cookie-banner';
        banner.setAttribute('role', 'dialog');
        banner.setAttribute('aria-label', 'Cookie consent');
        banner.innerHTML = `
            <div class="cookie-banner-text">
                <i class="fas fa-cookie-bite"></i>
                <p>We use cookies to improve your experience and analyse site traffic.
                   By clicking "Accept", you consent to our use of cookies as described in our
                   <a href="privacy-policy.html">Privacy Policy</a>.</p>
            </div>
            <div class="cookie-banner-actions">
                <button class="cookie-decline" id="cookie-decline">Decline</button>
                <button class="cookie-accept" id="cookie-accept">Accept All</button>
            </div>
        `;
        document.body.appendChild(banner);

        requestAnimationFrame(() => {
            setTimeout(() => banner.classList.add('show'), 700);
        });

        const dismissBanner = (choice) => {
            banner.style.transition = 'transform 0.4s ease';
            banner.style.transform = 'translateY(110%)';
            localStorage.setItem(COOKIE_KEY, choice);
            setTimeout(() => banner.remove(), 450);
        };

        document.getElementById('cookie-accept').addEventListener('click', () => dismissBanner('accepted'));
        document.getElementById('cookie-decline').addEventListener('click', () => dismissBanner('declined'));
    }

    /* ==========================================================================
       Prayer Strip / Widgets — Load real today's times from prayer-data.json
       Updates:  .prayer-strip-item[data-prayer]  .prayer-strip-time
                 .pwc-item[data-prayer]            .pwc-time
                 .pwf-row[data-prayer]             .pwf-adhan / .pwf-iqamah
                 .prayer-row[data-prayer]          .prayer-time.adhan / .prayer-time.iqama  (hero widget)
                 #prayer-countdown                 live countdown
                 #header-next-prayer               header compact label
       ========================================================================== */
    if (typeof emcData !== 'undefined') {
        const dataUrl = emcData.prayerDataUrl || (emcData.themeUri + '/assets/js/prayer-data.json');

        // Parse "HH:MM" -> total minutes
        function parseMins(str) {
            if (!str || !str.trim()) return null;
            const p = str.trim().split(':');
            return p.length < 2 ? null : parseInt(p[0],10)*60 + parseInt(p[1],10);
        }

        // Format "HH:MM" (24h) -> "H:MM AM/PM"
        function fmtT(str) {
            if (!str || str === '') return '—';
            const [hh, mm] = str.split(':').map(Number);
            const ampm = hh < 12 ? 'AM' : 'PM';
            const h12  = hh % 12 || 12;
            return `${h12}:${String(mm).padStart(2,'0')} ${ampm}`;
        }

        // Build today's key "DD/MM/YYYY"
        const _now = new Date();
        const _dd  = String(_now.getDate()).padStart(2,'0');
        const _mm  = String(_now.getMonth()+1).padStart(2,'0');
        const todayKey = `${_dd}/${_mm}/${_now.getFullYear()}`;

        fetch(dataUrl)
            .then(r => r.json())
            .then(data => {
                const entry = data.find(e => e.date === todayKey);
                if (!entry) return;

                const adhan  = entry.adhan  || {};
                const iqamah = entry.iqamah || {};

                // ── Prayer strip (homepage section) ───────────────────────
                document.querySelectorAll('.prayer-strip-item[data-prayer]').forEach(el => {
                    const key    = el.getAttribute('data-prayer');
                    const timeEl = el.querySelector('.prayer-strip-time');
                    if (timeEl && adhan[key]) timeEl.textContent = fmtT(adhan[key]);
                });

                // ── Compact widget ─────────────────────────────────────────
                document.querySelectorAll('.pwc-item[data-prayer]').forEach(el => {
                    const key    = el.getAttribute('data-prayer');
                    const timeEl = el.querySelector('.pwc-time');
                    if (timeEl && adhan[key]) timeEl.textContent = fmtT(adhan[key]);
                });

                // ── Full widget ────────────────────────────────────────────
                document.querySelectorAll('.pwf-row[data-prayer]').forEach(el => {
                    const key     = el.getAttribute('data-prayer');
                    const adhanEl = el.querySelector('.pwf-adhan');
                    const iqamaEl = el.querySelector('.pwf-iqamah');
                    if (adhanEl && adhan[key])  adhanEl.textContent = fmtT(adhan[key]);
                    if (iqamaEl && iqamah[key]) iqamaEl.textContent = fmtT(iqamah[key]);
                });

                // ── Hero Daily Salah widget (.prayer-row) ─────────────────
                document.querySelectorAll('.prayer-row[data-prayer]').forEach(el => {
                    const key     = el.getAttribute('data-prayer');
                    const adhanEl = el.querySelector('.prayer-time.adhan');
                    const iqamaEl = el.querySelector('.prayer-time.iqama');
                    if (adhanEl && adhan[key])  adhanEl.textContent = fmtT(adhan[key]);
                    if (iqamaEl && iqamah[key]) iqamaEl.textContent = fmtT(iqamah[key]);
                });

                // ── Hero countdown + header compact ───────────────────────
                const PRAYERS_ORDER = ['fajr','dhuhr','asr','maghrib','isha'];
                const prayerMins = PRAYERS_ORDER
                    .map(k => ({ key: k, mins: parseMins(adhan[k]) }))
                    .filter(p => p.mins !== null);

                function updateHeroCountdown() {
                    const t       = new Date();
                    const nowMins = t.getHours()*60 + t.getMinutes();
                    const nowSecs = t.getSeconds();

                    let next = prayerMins.find(p => p.mins > nowMins) || prayerMins[0];

                    // Highlight active row in hero widget
                    document.querySelectorAll('.prayer-row').forEach(row => {
                        row.classList.toggle('active', row.getAttribute('data-prayer') === next.key);
                    });

                    // Hero countdown timer
                    const countdownEl = document.getElementById('prayer-countdown');
                    const labelEl     = document.getElementById('next-prayer-label');
                    if (countdownEl) {
                        let diff = next.mins - nowMins;
                        if (diff < 0) diff += 1440;
                        let secs = diff * 60 - nowSecs;
                        if (secs < 0) secs = 0;
                        const hh = Math.floor(secs/3600);
                        const mm = Math.floor((secs%3600)/60);
                        const ss = secs % 60;
                        const pad = n => String(n).padStart(2,'0');
                        countdownEl.textContent = `${pad(hh)}:${pad(mm)}:${pad(ss)}`;
                    }
                    if (labelEl) {
                        const label = next.key.charAt(0).toUpperCase() + next.key.slice(1);
                        labelEl.textContent = `Next Prayer: ${label}`;
                    }

                    // Header compact prayer label
                    const headerPrayer  = document.getElementById('header-next-prayer');
                    const mobilePrayer  = document.getElementById('mobile-next-prayer');
                    const label = next.key.charAt(0).toUpperCase() + next.key.slice(1);
                    if (headerPrayer) headerPrayer.textContent = label;
                    if (mobilePrayer) mobilePrayer.textContent = label;
                }

                updateHeroCountdown();
                setInterval(updateHeroCountdown, 1000);
            })
            .catch(() => { /* silently fail */ });
    }

});


