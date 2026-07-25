/* ==========================================================================
   Donate Page JavaScript — Stripe Payment Integration
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
    const donateParams = new URLSearchParams(window.location.search);
    const presetFund   = donateParams.get('fund') || '';
    const presetAmount = parseFloat(donateParams.get('amount') || '0');

    /* =====================================================================
       Tab Switching
    ===================================================================== */
    const tabBtns   = document.querySelectorAll('.tab-btn');
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

    /* =====================================================================
       Amount Button Selection
    ===================================================================== */
    document.querySelectorAll('.tab-panel').forEach(panel => {
        const amountBtns   = panel.querySelectorAll('.amount-btn');
        const customWrapper = panel.querySelector('.custom-amount-wrapper');

        amountBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                amountBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                if (btn.classList.contains('custom-other')) {
                    if (customWrapper) customWrapper.style.display = 'block';
                } else {
                    if (customWrapper) customWrapper.style.display = 'none';
                    const customBtn = panel.querySelector('.custom-other');
                    if (customBtn) customBtn.textContent = 'Other';
                }
                updateDonateButtonLabel(panel);
            });
        });

        // Update label when typing custom amount
        const customInput = panel.querySelector('.custom-amount-input, #custom-amount-input');
        if (customInput) {
            customInput.addEventListener('input', () => updateDonateButtonLabel(panel));
        }
    });

    if (presetAmount > 0) {
        const panel = document.getElementById('tab-one-off');
        const customBtn = panel?.querySelector('.custom-other');
        const customInput = panel?.querySelector('.custom-amount-input, #custom-amount-input');
        panel?.querySelectorAll('.amount-btn').forEach(btn => btn.classList.remove('active'));
        customBtn?.classList.add('active');
        if (customInput) customInput.value = presetAmount.toFixed(2);
        const customWrapper = panel?.querySelector('.custom-amount-wrapper');
        if (customWrapper) customWrapper.style.display = 'block';
        updateDonateButtonLabel(panel);
    }

    function getActiveAmountPence(panel) {
        const activeBtn = panel.querySelector('.amount-btn.active');
        if (!activeBtn) return 0;
        if (activeBtn.classList.contains('custom-other')) {
            const input = panel.querySelector('.custom-amount-input, #custom-amount-input');
            const val   = parseFloat(input?.value || 0);
            return Math.round(val * 100);
        }
        const text = activeBtn.textContent.replace(/[^0-9.]/g, '');
        return Math.round(parseFloat(text || 0) * 100);
    }

    function getActiveAmountDisplay(panel) {
        const pence = getActiveAmountPence(panel);
        return pence > 0 ? '£' + (pence / 100).toFixed(2) : '£?';
    }

    function updateDonateButtonLabel(panel) {
        if (!panel) return;
        const submitBtn = panel.querySelector('.donate-submit');
        if (!submitBtn) return;
        const amount = getActiveAmountDisplay(panel);
        const icon   = panel.id === 'tab-regular'
            ? '<i class="fas fa-sync-alt"></i>'
            : '<i class="fas fa-lock"></i>';
        if (panel.id === 'tab-zakat') return; // Zakat manages its own button
        submitBtn.innerHTML = `${icon} Donate ${amount} Securely`;
    }

    /* =====================================================================
       Category Buttons
    ===================================================================== */
    function syncGiftAidForFund(panel, fund) {
        if (!panel) return;

        const isZakat  = String(fund || '').trim().toLowerCase() === 'zakat';
        const giftBox  = panel.querySelector('.gift-aid-box');
        const giftCheck = panel.querySelector('.gift-aid-check');

        if (!giftBox) return;

        giftBox.hidden = isZakat;
        giftBox.style.display = isZakat ? 'none' : '';
        giftBox.setAttribute('aria-hidden', isZakat ? 'true' : 'false');

        if (isZakat && giftCheck) {
            giftCheck.checked = false;
            panel.querySelectorAll('.donor-address, .donor-postcode').forEach(field => {
                field.required = false;
                field.setAttribute('aria-required', 'false');
            });
            giftCheck.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    document.querySelectorAll('.cat-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const parent = btn.closest('.category-grid');
            parent?.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            syncGiftAidForFund(btn.closest('.tab-panel'), btn.dataset.cat);
        });
    });

    const oneOffPanel = document.getElementById('tab-one-off');
    if (presetFund && oneOffPanel) {
        const presetButton = Array.from(oneOffPanel.querySelectorAll('.cat-btn')).find(
            button => String(button.dataset.cat || '').toLowerCase() === presetFund.toLowerCase()
        );
        if (presetButton) {
            oneOffPanel.querySelectorAll('.cat-btn').forEach(button => button.classList.remove('active'));
            presetButton.classList.add('active');
        }
    }
    syncGiftAidForFund(
        oneOffPanel,
        oneOffPanel?.querySelector('.cat-btn.active')?.dataset.cat || presetFund
    );

    document.querySelectorAll('.regular-fund').forEach(select => {
        const panel = select.closest('.tab-panel');
        select.addEventListener('change', () => syncGiftAidForFund(panel, select.value));
        syncGiftAidForFund(panel, select.value);
    });

    /* =====================================================================
       Zakat Calculator
    ===================================================================== */
    const zakatInputs   = document.querySelectorAll('.zakat-input');
    const zakatAmountEl = document.getElementById('zakat-amount');
    const zakatNoteEl   = document.getElementById('zakat-note');
    const zakatDonateBtn = document.getElementById('donate-zakat-btn');
    const NISAB      = 452.06;
    const ZAKAT_RATE = 0.025;

    function calcZakat() {
        const cash     = parseFloat(document.getElementById('z-cash')?.value     || 0);
        const gold     = parseFloat(document.getElementById('z-gold')?.value     || 0);
        const business = parseFloat(document.getElementById('z-business')?.value || 0);
        const owed     = parseFloat(document.getElementById('z-owed')?.value     || 0);
        const deduct   = parseFloat(document.getElementById('z-deduct')?.value   || 0);
        const total    = cash + gold + business + owed - deduct;

        if (total <= 0) {
            if (zakatAmountEl) zakatAmountEl.textContent = '£0.00';
            if (zakatNoteEl)   zakatNoteEl.textContent   = 'Enter your assets above to calculate.';
            if (zakatDonateBtn) zakatDonateBtn.style.display = 'none';
            return;
        }
        if (total < NISAB) {
            if (zakatAmountEl) zakatAmountEl.textContent = '£0.00';
            if (zakatNoteEl)   zakatNoteEl.textContent   = `Your assets (£${total.toFixed(2)}) are below the Nisab (£${NISAB}). No Zakat is due.`;
            if (zakatDonateBtn) zakatDonateBtn.style.display = 'none';
        } else {
            const zakat = total * ZAKAT_RATE;
            if (zakatAmountEl) zakatAmountEl.textContent = `£${zakat.toFixed(2)}`;
            if (zakatNoteEl)   zakatNoteEl.textContent   = `2.5% of £${total.toFixed(2)}. Zakat is due.`;
            if (zakatDonateBtn) {
                zakatDonateBtn.style.display = 'flex';
                zakatDonateBtn.dataset.zakatAmount = Math.round(zakat * 100); // store pence
                zakatDonateBtn.innerHTML = `<i class="fas fa-hand-holding-usd"></i> Donate My Zakat (£${zakat.toFixed(2)})`;
            }
        }
    }
    zakatInputs.forEach(input => input.addEventListener('input', calcZakat));

    /* =====================================================================
       STRIPE PAYMENT INTEGRATION
    ===================================================================== */
    if (typeof emcStripeConfig === 'undefined' || typeof Stripe === 'undefined') {
        console.warn('EMC: Stripe config or Stripe.js not loaded.');
        return;
    }

    const { publishableKey, ajaxUrl, nonce } = emcStripeConfig;
    const hasStripeKey = typeof publishableKey === 'string' && /^pk_(test|live)_/.test(publishableKey.trim());
    const stripe = hasStripeKey ? Stripe(publishableKey.trim()) : null;

    if (!hasStripeKey) {
        console.warn('EMC: Stripe publishable key is missing or invalid. Add it in WP Admin > Settings > EMC Stripe.');
    }

    // ── State ──────────────────────────────────────────────────────────────
    let stripeElements  = null;
    let paymentElement  = null;
    let currentPiId     = null;
    let currentAmount   = 0;     // pence
    let currentFund     = 'General Fund';
    let currentGiftAid  = false;
    let currentMessage  = '';
    let currentName     = '';
    let currentEmail    = '';
    let currentAddress  = '';
    let currentPostcode = '';
    let currentTab      = 'one-off';
    let currentFrequency = 'monthly';
    let currentStartDate = '';
    let currentConfirmMode = 'payment';
    let currentSubscriptionId = '';
    let currentOccurrences = 0;

    // ── Inject Payment Modal ───────────────────────────────────────────────
    const modal = document.createElement('div');
    modal.id        = 'emc-stripe-modal';
    modal.className = 'emc-stripe-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-label', 'Secure payment');
    // modal starts hidden via CSS default (display:none — no is-visible class)
    modal.innerHTML = `
        <div class="emc-stripe-modal-backdrop"></div>
        <div class="emc-stripe-modal-card">
            <button class="emc-stripe-modal-close" aria-label="Close payment">&times;</button>

            <!-- Header -->
            <div class="esm-header">
                <div class="esm-lock"><i class="fas fa-lock"></i></div>
                <div>
                    <h3 class="esm-title">Complete Your Donation</h3>
                    <p class="esm-subtitle">Secured by <strong>Stripe</strong> · 256-bit encryption</p>
                </div>
            </div>

            <!-- Amount pill -->
            <div class="esm-amount-bar">
                <span class="esm-fund-name" id="esm-fund-name">General Fund</span>
                <span class="esm-amount-num" id="esm-amount-display">£0.00</span>
            </div>

            <!-- Loading state -->
            <div class="esm-loading" id="esm-loading">
                <div class="esm-spinner"></div>
                <p>Preparing secure payment…</p>
            </div>

            <!-- Stripe Payment Element -->
            <div id="payment-element" class="esm-payment-element" style="display:none;"></div>

            <!-- Error -->
            <div class="esm-error" id="esm-error" hidden></div>

            <!-- Submit -->
            <button class="btn btn-primary esm-pay-btn" id="esm-pay-btn" disabled>
                <i class="fas fa-lock"></i>
                <span id="esm-pay-label">Pay Now</span>
            </button>

            <!-- Success -->
            <div class="esm-success" id="esm-success" hidden>
                <div class="esm-success-icon"><i class="fas fa-check-circle"></i></div>
                <h3>JazakAllahu Khayran!</h3>
                <p id="esm-success-message">Your donation of <strong id="esm-success-amount"></strong> has been received.</p>
                <p class="esm-receipt-note">A receipt has been emailed to you.</p>
                <button class="btn btn-outline esm-close-btn" id="esm-success-close">Close</button>
            </div>

            <p class="esm-secure-note">
                <i class="fas fa-shield-alt"></i> Your card details are never stored on our servers.
                Powered by <a href="https://stripe.com" target="_blank" rel="noopener">Stripe</a>.
            </p>
        </div>`;
    document.body.appendChild(modal);

    // ── Modal helpers ──────────────────────────────────────────────────────
    function openModal() {
        modal.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
        setTimeout(() => modal.classList.add('is-open'), 10);
    }

    function closeModal() {
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
        setTimeout(() => {
            modal.classList.remove('is-visible');
            resetModal();
        }, 300);
    }

    function resetModal() {
        document.getElementById('esm-loading').style.display   = 'flex';
        document.getElementById('payment-element').style.display = 'none';
        document.getElementById('esm-error').hidden             = true;
        document.getElementById('esm-success').hidden           = true;
        document.getElementById('esm-pay-btn').disabled         = true;
        document.getElementById('esm-pay-btn').style.display    = 'flex';
        document.getElementById('esm-pay-btn').innerHTML        = '<i class="fas fa-lock"></i><span id="esm-pay-label">Pay Now</span>';
        document.querySelector('.esm-secure-note').hidden       = false;
        if (stripeElements) { stripeElements = null; paymentElement = null; }
    }

    // Close triggers
    modal.querySelector('.emc-stripe-modal-close').addEventListener('click', closeModal);
    modal.querySelector('.emc-stripe-modal-backdrop').addEventListener('click', closeModal);
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('is-visible')) closeModal(); });
    document.getElementById('esm-success-close')?.addEventListener('click', closeModal);

    // ── Create PaymentIntent & mount Stripe Element ────────────────────────
    async function initStripePayment(amountPence, fund, tab) {
        if (!stripe) {
            throw new Error('Stripe is not configured yet. Please add the publishable and secret keys in WP Admin > Settings > EMC Stripe.');
        }

        const isSubscription = tab === 'regular' || tab === 'ramadan-daily';
        const body = new URLSearchParams(
            isSubscription
                ? {
                    action     : 'emc_stripe_create_subscription',
                    nonce      : nonce,
                    amount     : amountPence,
                    fund       : fund,
                    frequency  : currentFrequency,
                    start_date : currentStartDate,
                    occurrences: currentOccurrences,
                    name       : currentName,
                    email      : currentEmail,
                    address    : currentAddress,
                    postcode   : currentPostcode,
                    gift_aid   : currentGiftAid ? '1' : '0',
                    message    : currentMessage,
                }
                : {
                    action : 'emc_stripe_create_intent',
                    nonce  : nonce,
                    amount : amountPence,
                    fund   : fund,
                    tab    : tab,
                    name   : currentName,
                    email  : currentEmail,
                    address: currentAddress,
                    postcode: currentPostcode,
                    gift_aid: currentGiftAid ? '1' : '0',
                }
        );

        const res  = await fetch(ajaxUrl, { method: 'POST', body });
        const data = await res.json();

        if (!data.success) throw new Error(data.data?.message || 'Payment gateway error.');

        currentPiId = data.data.pi_id;
        currentConfirmMode = data.data.confirm_mode || 'payment';
        currentSubscriptionId = data.data.subscription_id || '';
        if (data.data.start_date) {
            currentStartDate = data.data.start_date;
        }

        // Initialize Stripe Elements with the client secret
        stripeElements = stripe.elements({ clientSecret: data.data.client_secret,
            appearance: {
                theme : 'stripe',
                variables: {
                    colorPrimary       : '#2aaca0',
                    colorBackground    : '#ffffff',
                    colorText          : '#0c1f2e',
                    colorDanger        : '#df1b41',
                    fontFamily         : 'Inter, system-ui, sans-serif',
                    borderRadius       : '10px',
                    spacingUnit        : '4px',
                },
            },
        });

        paymentElement = stripeElements.create('payment', {
            layout: { type: 'tabs', defaultCollapsed: false },
        });

        const mountEl = document.getElementById('payment-element');
        paymentElement.mount(mountEl);

        paymentElement.on('ready', () => {
            document.getElementById('esm-loading').style.display   = 'none';
            mountEl.style.display                                   = 'block';
            const payBtn = document.getElementById('esm-pay-btn');
            payBtn.disabled = false;
            payBtn.innerHTML = isSubscription
                ? `<i class="fas fa-sync-alt"></i><span>Set Up ${getDisplayAmount(amountPence)} ${getFrequencyLabel(currentFrequency)}</span>`
                : `<i class="fas fa-lock"></i><span>Pay ${getDisplayAmount(amountPence)}</span>`;
        });
    }

    function getDisplayAmount(pence) {
        return '£' + (pence / 100).toFixed(2);
    }

    function getFrequencyLabel(frequency) {
        const labels = {
            weekly: 'Weekly',
            monthly: 'Monthly',
            quarterly: 'Quarterly',
            annually: 'Annually',
            daily: 'Daily',
        };
        return labels[frequency] || 'Monthly';
    }

    // ── Collect donor details from active panel ────────────────────────────
    function collectDonorDetails(panel) {
        currentName     = panel.querySelector('.donor-name, #donor-name')?.value?.trim() || '';
        currentEmail    = panel.querySelector('.donor-email, #donor-email')?.value?.trim() || '';
        currentAddress  = panel.querySelector('.donor-address, #donor-address')?.value?.trim() || '';
        const postcodeInput = panel.querySelector('.donor-postcode, #donor-postcode');
        currentPostcode = formatPostcode(postcodeInput?.value || '');
        if (postcodeInput) postcodeInput.value = currentPostcode;
        currentGiftAid  = !!(panel.querySelector('.gift-aid-check')?.checked);
        currentMessage  = panel.querySelector('#donor-message')?.value?.trim() || '';
        currentFund     = panel.querySelector('.cat-btn.active')?.dataset.cat
                       || panel.querySelector('.regular-fund')?.value
                       || 'General Fund';
        if (presetFund && panel.id === 'tab-one-off') {
            currentFund = presetFund;
        }
        currentFrequency = panel.querySelector('.regular-frequency')?.value || 'monthly';
        currentStartDate = panel.querySelector('.regular-start-date')?.value || '';
        currentOccurrences = 0;
    }

    function formatPostcode(value) {
        const original = String(value).toUpperCase().trim().replace(/\s+/g, ' ');
        const compact = original.replace(/[^A-Z0-9]/g, '');

        // Preserve numeric international postcodes instead of applying UK
        // postcode spacing to them.
        if (!/[A-Z]/.test(compact)) return original;

        return compact.length > 3
            ? `${compact.slice(0, -3)} ${compact.slice(-3)}`
            : compact;
    }

    function isValidPostcode(value) {
        return /^[A-Z0-9]{2,4} [A-Z0-9]{3}$/.test(value);
    }

    function validateDonorDetails(panel) {
        collectDonorDetails(panel);
        if (!currentName) {
            showInlineError(panel, 'Please enter your full name.');
            return false;
        }
        if (!currentEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(currentEmail)) {
            showInlineError(panel, 'Please enter a valid email address.');
            return false;
        }
        if (currentGiftAid && !currentAddress) {
            showInlineError(panel, 'Please enter the first line of your address.');
            return false;
        }
        if (currentGiftAid && !isValidPostcode(currentPostcode)) {
            showInlineError(panel, 'Please enter a valid UK postcode for Gift Aid.');
            return false;
        }
        return true;
    }

    document.querySelectorAll('.gift-aid-check').forEach(checkbox => {
        const syncAddressRequirement = () => {
            const panel = checkbox.closest('.tab-panel');
            panel?.querySelectorAll('.donor-address, .donor-postcode').forEach(field => {
                field.required = checkbox.checked;
                field.setAttribute('aria-required', checkbox.checked ? 'true' : 'false');
            });
        };
        checkbox.addEventListener('change', syncAddressRequirement);
        syncAddressRequirement();
    });

    // ── Handle "Donate Securely" button clicks (one-off & regular) ────────
    document.querySelectorAll('.tab-panel .donate-submit').forEach(btn => {
        if (btn.id === 'donate-zakat-btn') return;
        btn.addEventListener('click', async () => {
            const panel  = btn.closest('.tab-panel');
            const pence  = getActiveAmountPence(panel);
            currentTab   = panel.id.replace('tab-', '');

            if (pence < 50) {
                showInlineError(panel, 'Please select or enter a donation amount (minimum £0.50).');
                return;
            }
            if (!validateDonorDetails(panel)) return;
            currentAmount = pence;

            // Update modal display
            document.getElementById('esm-amount-display').textContent = getDisplayAmount(pence);
            document.getElementById('esm-fund-name').textContent       = currentFund;

            openModal();

            try {
                await initStripePayment(pence, currentFund, currentTab);
            } catch (err) {
                document.getElementById('esm-loading').style.display = 'none';
                const errEl = document.getElementById('esm-error');
                errEl.textContent = err.message;
                errEl.hidden = false;
            }
        });
    });

    // ── Zakat donate button ────────────────────────────────────────────────
    if (zakatDonateBtn) {
        zakatDonateBtn.addEventListener('click', async () => {
            const pence = parseInt(zakatDonateBtn.dataset.zakatAmount || 0);
            if (pence < 50) return;

            currentAmount  = pence;
            currentFund    = 'Zakat';
            currentTab     = 'zakat';
            const panel = zakatDonateBtn.closest('.tab-panel');
            if (!validateDonorDetails(panel)) return;
            currentFund    = 'Zakat';
            currentGiftAid = false;
            currentMessage = '';

            document.getElementById('esm-amount-display').textContent = getDisplayAmount(pence);
            document.getElementById('esm-fund-name').textContent       = 'Zakat';

            openModal();
            try {
                await initStripePayment(pence, 'Zakat', 'zakat');
            } catch (err) {
                document.getElementById('esm-loading').style.display = 'none';
                const errEl = document.getElementById('esm-error');
                errEl.textContent = err.message;
                errEl.hidden = false;
            }
        });
    }

    // ── Handle "Pay Now" submission ────────────────────────────────────────
    document.getElementById('esm-pay-btn')?.addEventListener('click', async () => {
        const payBtn  = document.getElementById('esm-pay-btn');
        const errEl   = document.getElementById('esm-error');

        payBtn.disabled = true;
        payBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…';
        errEl.hidden = true;

        try {
            const returnUrl = new URL(window.location.href);
            returnUrl.searchParams.set('donated', '1');

            const confirmParams = {
                return_url: returnUrl.toString(),
                payment_method_data: {
                    billing_details: {
                            name  : currentName  || undefined,
                            email : currentEmail || undefined,
                            address: currentAddress ? {
                                line1: currentAddress,
                                postal_code: currentPostcode || undefined,
                            } : undefined,
                    },
                },
            };

            const result = currentConfirmMode === 'setup'
                ? await stripe.confirmSetup({
                    elements: stripeElements,
                    redirect: 'if_required',
                    confirmParams,
                })
                : await stripe.confirmPayment({
                    elements  : stripeElements,
                    redirect  : 'if_required',
                    confirmParams,
                });

            const { error } = result;

            if (error) {
                errEl.textContent = error.message;
                errEl.hidden      = false;
                payBtn.disabled   = false;
                payBtn.innerHTML  = (currentTab === 'regular' || currentTab === 'ramadan-daily')
                    ? `<i class="fas fa-sync-alt"></i><span>Set Up ${getDisplayAmount(currentAmount)} ${getFrequencyLabel(currentFrequency)}</span>`
                    : `<i class="fas fa-lock"></i><span>Pay ${getDisplayAmount(currentAmount)}</span>`;
                return;
            }

            // Payment succeeded — record it
            if (currentConfirmMode === 'payment') {
                await recordDonation();
            }
            if ((currentTab === 'regular' || currentTab === 'ramadan-daily') && currentSubscriptionId) {
                await recordSubscriptionSetup();
            }
            showSuccess();

        } catch (err) {
            errEl.textContent = err.message || 'An unexpected error occurred.';
            errEl.hidden      = false;
            payBtn.disabled   = false;
            payBtn.innerHTML  = `<i class="fas fa-lock"></i><span>Try Again</span>`;
        }
    });

    // ── Record donation server-side ────────────────────────────────────────
    async function recordDonation() {
        const body = new URLSearchParams({
            action   : 'emc_stripe_record',
            nonce    : nonce,
            pi_id    : currentPiId,
            amount   : currentAmount,
            fund     : currentFund,
            name     : currentName,
            email    : currentEmail,
            address  : currentAddress,
            postcode : currentPostcode,
            gift_aid : currentGiftAid ? '1' : '0',
            message  : currentMessage,
        });
        await fetch(ajaxUrl, { method: 'POST', body });
    }

    // ── Show success state ─────────────────────────────────────────────────
    async function recordSubscriptionSetup() {
        const body = new URLSearchParams({
            action          : 'emc_stripe_record_subscription_setup',
            nonce           : nonce,
            subscription_id : currentSubscriptionId,
            setup_ref       : currentPiId,
            amount          : currentAmount,
            fund            : currentFund,
            frequency       : currentFrequency,
            start_date      : currentStartDate,
            occurrences     : currentOccurrences,
            name            : currentName,
            email           : currentEmail,
            address         : currentAddress,
            postcode        : currentPostcode,
            gift_aid        : currentGiftAid ? '1' : '0',
            message         : currentMessage,
        });
        await fetch(ajaxUrl, { method: 'POST', body });
    }

    function showSuccess() {
        document.getElementById('payment-element').style.display = 'none';
        document.getElementById('esm-pay-btn').style.display     = 'none';
        document.querySelector('.esm-secure-note').hidden         = true;
        document.getElementById('esm-error').hidden               = true;
        document.getElementById('esm-success-message').innerHTML = (currentTab === 'regular' || currentTab === 'ramadan-daily')
            ? `Your ${getFrequencyLabel(currentFrequency).toLowerCase()} donation of <strong id="esm-success-amount">${getDisplayAmount(currentAmount)}</strong> has been set up.`
            : `Your donation of <strong id="esm-success-amount">${getDisplayAmount(currentAmount)}</strong> has been received.`;
        document.getElementById('esm-success').hidden             = false;
    }

    // ── Inline error helper ────────────────────────────────────────────────
    function showInlineError(panel, message) {
        let err = panel.querySelector('.donate-inline-error');
        if (!err) {
            err = document.createElement('p');
            err.className = 'donate-inline-error';
            panel.querySelector('.donate-submit')?.before(err);
        }
        err.textContent = message;
        err.style.display = 'block';
        setTimeout(() => { err.style.display = 'none'; }, 4000);
    }
    // ── Global bridge — lets other scripts (ramadan.js) open the modal ────
    /**
     * window.emcOpenStripeModal({ amount, fund, tab, name, email, address, postcode, message, giftAid })
     * amount  : integer pence (e.g. 3000 = £30)
     * fund    : string label shown in modal  (e.g. 'Ramadan Giving')
     * tab     : string context key          (e.g. 'ramadan', 'fidya')
     */
    window.emcOpenStripeModal = function({ amount, fund = 'General Fund', tab = 'one-off',
                                           name = '', email = '', address = '', postcode = '', message = '', giftAid = false,
                                           frequency = 'monthly', startDate = '', occurrences = 0 }) {
        if (amount < 50) return;
        currentAmount  = amount;
        currentFund    = fund;
        currentTab     = tab;
        currentName    = name;
        currentEmail   = email;
        currentAddress = address;
        currentPostcode = formatPostcode(postcode);
        currentGiftAid = giftAid;
        currentMessage = message;
        currentFrequency = frequency;
        currentStartDate = startDate;
        currentOccurrences = parseInt(occurrences || 0, 10);

        document.getElementById('esm-amount-display').textContent = getDisplayAmount(amount);
        document.getElementById('esm-fund-name').textContent      = fund;

        openModal();

        initStripePayment(amount, fund, tab).catch(err => {
            document.getElementById('esm-loading').style.display = 'none';
            const errEl = document.getElementById('esm-error');
            errEl.textContent = err.message;
            errEl.hidden = false;
        });
    };

});
