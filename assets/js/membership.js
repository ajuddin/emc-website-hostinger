/**
 * EMC Membership page.
 *
 * This script does not talk to Stripe. It validates the application, records it
 * server-side as pending, then hands the amount to the EMC Payments plugin's
 * payment bridge — window.emcOpenStripeModal() — with tab "regular" and a monthly
 * frequency, which is the same path the Donate page's regular-giving option uses.
 *
 * The plugin creates the subscription and writes it to its own log; the theme
 * completes the membership record from there.
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('membership-form');
    if (!form) return;

    const submitButton = form.querySelector('.mem-submit');
    const buttonLabel = submitButton?.querySelector('span');
    const defaultLabel = buttonLabel?.textContent || 'Set Up Monthly Membership';
    const status = form.querySelector('.mem-form-status');
    const levelSelect = form.querySelector('#membership-level');
    const totalDisplay = form.querySelector('[data-membership-total]');

    let levels = [];
    try {
        const parsed = JSON.parse(form.dataset.levels || '[]');
        levels = Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        levels = [];
    }

    const currentLevel = () => levels.find(level => level.key === levelSelect?.value) || null;

    const money = pence => `£${(pence / 100).toFixed(pence % 100 === 0 ? 0 : 2)}`;

    const setStatus = (message, state = '') => {
        if (!status) return;
        status.className = `mem-form-status${state ? ` ${state}` : ''}`;
        status.textContent = message;
        if (message) status.focus();
    };

    const refreshLevel = () => {
        const level = currentLevel();
        if (totalDisplay) {
            totalDisplay.textContent = level ? `${money(level.pence)} / month` : '—';
        }
    };

    levelSelect?.addEventListener('change', refreshLevel);
    refreshLevel();

    // The dome cards scroll down to the form with that level pre-selected.
    document.querySelectorAll('[data-select-level]').forEach(button => {
        button.addEventListener('click', () => {
            if (levelSelect) {
                levelSelect.value = button.dataset.selectLevel;
                refreshLevel();
            }
            document.getElementById('membership-application')?.scrollIntoView({behavior: 'smooth', block: 'start'});
            form.querySelector('#membership-first-name')?.focus({preventScroll: true});
        });
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        if (!window.emcMembershipConfig?.ajaxUrl || !window.emcMembershipConfig?.nonce) {
            setStatus('Memberships are temporarily unavailable. Please try again later.', 'is-error');
            return;
        }

        // The bridge is injected by the EMC Payments plugin's donate script.
        if (typeof window.emcOpenStripeModal !== 'function') {
            setStatus('Card payments are not available on this page yet. Please contact the centre to join.', 'is-error');
            return;
        }

        if (submitButton) submitButton.disabled = true;
        if (buttonLabel) buttonLabel.textContent = 'Checking your details…';
        setStatus('');

        try {
            const formData = new FormData(form);
            formData.set('nonce', window.emcMembershipConfig.nonce);

            const response = await fetch(window.emcMembershipConfig.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData,
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result?.data?.message || 'Your application could not be submitted.');
            }

            // Hand over to the payments plugin. It owns the card form, the Stripe
            // subscription, and its own success and error states from here on.
            window.emcOpenStripeModal({
                amount: result.data.amount,
                fund: result.data.fund,
                tab: result.data.tab,
                frequency: result.data.frequency,
                name: result.data.name,
                email: result.data.email,
                address: result.data.address,
                postcode: result.data.postcode,
                giftAid: result.data.giftAid,
                message: result.data.message,
            });

            setStatus('Complete your card details in the secure payment window to finish setting up your membership.');
        } catch (error) {
            setStatus(error.message || 'Your application could not be submitted. Please try again.', 'is-error');
        } finally {
            if (submitButton) submitButton.disabled = false;
            if (buttonLabel) buttonLabel.textContent = defaultLabel;
        }
    });
});
