document.addEventListener('DOMContentLoaded', () => {

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

    document.querySelectorAll('[data-badr-tier]').forEach(slot => {
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
        const postcodeInput = document.getElementById('badr-donor-postcode');
        const postcodeCompact = (postcodeInput?.value || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
        const postcode = postcodeCompact.length > 3
            ? `${postcodeCompact.slice(0, -3)} ${postcodeCompact.slice(-3)}`
            : postcodeCompact;
        if (postcodeInput) postcodeInput.value = postcode;

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
        if (postcode && !address) {
            showBadrError('Please enter the first line of your address.');
            return;
        }
        if ((address || postcode) && !/^[A-Z0-9]{2,4} [A-Z0-9]{3}$/.test(postcode)) {
            showBadrError('Please enter a valid postcode.');
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
            postcode,
            message: `Badr Wall membership level: ${label}`,
            giftAid: false,
        });
    });
});
