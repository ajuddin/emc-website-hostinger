/**
 * EMC Membership page — monthly subscription signup.
 *
 * Two server round-trips either side of one Stripe card confirmation:
 *   1. emc_membership_join     → validates and returns a SetupIntent client secret
 *   2. stripe.confirmCardSetup → collects and authenticates the card in the browser
 *   3. emc_membership_confirm  → verifies the card and creates the monthly subscription
 *
 * The card is confirmed before the subscription exists, so an abandoned or failed
 * card step cannot leave a half-created subscription behind.
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
    const cardErrors = form.querySelector('.mem-card-errors');
    const cardMount = form.querySelector('#membership-card');

    let levels = [];
    try {
        const parsed = JSON.parse(form.dataset.levels || '[]');
        levels = Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        levels = [];
    }

    let stripe = null;
    let card = null;
    let setupSession = null;      // SetupIntent handed back by step 1
    let confirmedSetup = null;    // SetupIntent id once the card is authenticated

    const currentLevel = () => levels.find(level => level.key === levelSelect?.value) || null;

    const money = pence => `£${(pence / 100).toFixed(pence % 100 === 0 ? 0 : 2)}`;

    const setStatus = (message, state = '') => {
        if (!status) return;
        status.className = `mem-form-status${state ? ` ${state}` : ''}`;
        status.textContent = message;
        if (message) status.focus();
    };

    if (form.dataset.stripeKey && typeof window.Stripe === 'function' && cardMount) {
        stripe = window.Stripe(form.dataset.stripeKey);
        card = stripe.elements().create('card', {
            hidePostalCode: false,
            style: {
                base: {color: '#1a3c2a', fontSize: '16px', fontFamily: 'inherit', '::placeholder': {color: '#8a9a91'}},
                invalid: {color: '#a8442a'},
            },
        });
        card.mount(cardMount);
        card.on('change', event => {
            if (cardErrors) cardErrors.textContent = event.error?.message || '';
        });
    }

    const refreshLevel = () => {
        // Any change to the chosen level invalidates a prepared session.
        setupSession = null;
        confirmedSetup = null;

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

    const post = async formData => {
        const response = await fetch(window.emcMembershipConfig.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result?.data?.message || 'Your membership could not be set up.');
        }
        return result.data;
    };

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

        if (submitButton) submitButton.disabled = true;
        setStatus('');

        try {
            if (!stripe || !card) {
                throw new Error('Secure card payment is temporarily unavailable. Please contact the centre.');
            }

            // Step 1 — validate and get a SetupIntent. Skipped on retry.
            if (!confirmedSetup) {
                if (!setupSession) {
                    if (buttonLabel) buttonLabel.textContent = 'Checking details…';
                    const formData = new FormData(form);
                    formData.set('nonce', window.emcMembershipConfig.nonce);
                    setupSession = await post(formData);
                }

                // Step 2 — confirm the card. Any 3-D Secure challenge happens here.
                if (buttonLabel) buttonLabel.textContent = 'Confirming your card…';
                const confirmation = await stripe.confirmCardSetup(setupSession.clientSecret, {
                    payment_method: {
                        card,
                        billing_details: {
                            name: `${form.querySelector('#membership-first-name')?.value || ''} ${form.querySelector('#membership-last-name')?.value || ''}`.trim() || undefined,
                            email: form.querySelector('#membership-email')?.value || undefined,
                            address: {
                                line1: form.querySelector('#membership-address-1')?.value || undefined,
                                line2: form.querySelector('#membership-address-2')?.value || undefined,
                                city: form.querySelector('#membership-city')?.value || undefined,
                                postal_code: form.querySelector('#membership-postcode')?.value || undefined,
                                country: 'GB',
                            },
                        },
                    },
                });

                if (confirmation.error) {
                    throw new Error(confirmation.error.message || 'Stripe could not confirm your card.');
                }
                if (confirmation.setupIntent?.status !== 'succeeded') {
                    throw new Error('Stripe has not confirmed your card. Please try again.');
                }

                confirmedSetup = confirmation.setupIntent.id;
            }

            // Step 3 — create the monthly subscription.
            if (buttonLabel) buttonLabel.textContent = 'Setting up your membership…';
            const confirmData = new FormData();
            confirmData.set('action', 'emc_membership_confirm');
            confirmData.set('nonce', window.emcMembershipConfig.nonce);
            confirmData.set('token', setupSession.token);
            confirmData.set('setup_intent', confirmedSetup);
            const result = await post(confirmData);

            form.reset();
            card.clear();
            refreshLevel();
            setStatus(result.message, 'is-success');
            if (submitButton) submitButton.hidden = true;
        } catch (error) {
            setStatus(error.message || 'Your membership could not be set up. Please try again.', 'is-error');
        } finally {
            if (submitButton && !submitButton.hidden) {
                submitButton.disabled = false;
                if (buttonLabel) buttonLabel.textContent = defaultLabel;
            }
        }
    });
});
