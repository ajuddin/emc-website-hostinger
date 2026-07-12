document.addEventListener('DOMContentLoaded', () => {

    /* =====================
       Animate Campaign Progress Bar on Load
       ===================== */
    const fill = document.getElementById('campaign-fill');
    // Slight delay so the animation is visible to the user
    if (fill) {
        setTimeout(() => {
            fill.style.width = '63%';
        }, 400);
    }

    /* =====================
       Animate Counter Number
       ===================== */
    function animateCount(el, target, prefix = '', suffix = '') {
        if (!el) return;
        const duration = 2000;
        const start = performance.now();

        function update(time) {
            const elapsed = time - start;
            const progress = Math.min(elapsed / duration, 1);
            // Ease out cubic
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(eased * target);
            el.textContent = prefix + current.toLocaleString() + suffix;
            if (progress < 1) requestAnimationFrame(update);
        }

        requestAnimationFrame(update);
    }

    // Animate the raised amount & donor count when hero is in view
    const progressBox = document.querySelector('.campaign-progress-box');
    if (progressBox) {
        const obs = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCount(document.getElementById('amount-raised'), 62500, '£');
                    animateCount(document.getElementById('donor-count'), 142);
                    obs.disconnect();
                }
            });
        }, { threshold: 0.2 });
        obs.observe(progressBox);
    }

    /* =====================
       Tab Switching (Campaign Page)
       ===================== */
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanels.forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(`tab-${target}`)?.classList.add('active');
        });
    });

    /* =====================
       Amount Button Selection
       ===================== */
    document.querySelectorAll('.tab-panel').forEach(panel => {
        const btns = panel.querySelectorAll('.amount-btn');
        btns.forEach(btn => {
            btn.addEventListener('click', () => {
                btns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });
    });

    /* =====================
       Badr Wall membership selection and card payment
       ===================== */
    const badrCard = document.getElementById('badr-membership');
    const badrTierBtns = badrCard ? badrCard.querySelectorAll('.badr-tier-option') : [];
    const badrLabel = document.getElementById('badr-selected-label');
    const badrAmount = document.getElementById('badr-selected-amount');

    function formatMoney(amount, plus = false) {
        return `£${Number(amount || 0).toLocaleString()}${plus ? '+' : ''}`;
    }

    function selectBadrTier(btn) {
        if (!btn) return;
        badrTierBtns.forEach(option => {
            option.classList.remove('active');
            option.setAttribute('aria-checked', 'false');
        });
        btn.classList.add('active');
        btn.setAttribute('aria-checked', 'true');

        const label = btn.dataset.label || '';
        const amount = parseInt(btn.dataset.amount || '0', 10);
        const plus = btn.dataset.plus === '1';
        if (badrLabel) badrLabel.textContent = label;
        if (badrAmount) badrAmount.textContent = formatMoney(amount, plus);
    }

    function showBadrError(message) {
        if (!badrCard) return;
        let err = badrCard.querySelector('.badr-inline-error');
        if (!err) {
            err = document.createElement('p');
            err.className = 'badr-inline-error';
            badrCard.querySelector('.badr-payment-actions')?.before(err);
        }
        err.textContent = message;
        err.hidden = false;
    }

    badrTierBtns.forEach(btn => {
        btn.addEventListener('click', () => selectBadrTier(btn));
    });

    document.querySelectorAll('.donor-slot.empty').forEach(slot => {
        slot.addEventListener('click', event => {
            event.preventDefault();
            const tier = slot.dataset.badrTier;
            const matchingTier = tier ? badrCard?.querySelector(`.badr-tier-option[data-tier="${tier}"]`) : null;
            selectBadrTier(matchingTier || badrCard?.querySelector('.badr-tier-option.active'));
            badrCard?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    document.getElementById('badr-card-pay')?.addEventListener('click', () => {
        const activeTier = badrCard?.querySelector('.badr-tier-option.active');
        const amount = parseInt(activeTier?.dataset.amount || '0', 10);
        const label = activeTier?.dataset.label || 'Badr Wall Membership';
        const name = document.getElementById('badr-donor-name')?.value.trim() || '';
        const email = document.getElementById('badr-donor-email')?.value.trim() || '';
        const address = document.getElementById('badr-donor-address')?.value.trim() || '';

        if (!amount) {
            showBadrError('Please choose a Badr Wall level.');
            return;
        }
        if (!name) {
            showBadrError('Please enter your full name.');
            return;
        }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showBadrError('Please enter a valid email address.');
            return;
        }
        if (!address) {
            showBadrError('Please enter your address.');
            return;
        }
        if (typeof window.emcOpenStripeModal !== 'function') {
            showBadrError('Card payments are not available yet. Please use Pay by Bank or WhatsApp Pledge.');
            return;
        }

        const err = badrCard?.querySelector('.badr-inline-error');
        if (err) err.hidden = true;

        window.emcOpenStripeModal({
            amount: amount * 100,
            fund: `Badr Wall - ${label}`,
            tab: 'badr-wall',
            name,
            email,
            address,
            message: `Badr Wall membership level: ${label}`,
            giftAid: false,
        });
    });
});
