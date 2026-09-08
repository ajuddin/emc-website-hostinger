/**
 * EMC Membership page.
 *
 * Collects the application, takes the category fee with Stripe Elements, then
 * asks the server to verify the payment before the membership is stored.
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('membership-form');
    if (!form) return;

    const submitButton = form.querySelector('.membership-submit');
    const buttonLabel = submitButton?.querySelector('span');
    const defaultLabel = buttonLabel?.textContent || 'Join & Pay Securely';
    const status = form.querySelector('.membership-form-status');
    const tierSelect = form.querySelector('#membership-tier');
    const paymentBlock = form.querySelector('[data-membership-payment]');
    const totalDisplay = form.querySelector('[data-membership-total]');
    const cardErrors = form.querySelector('.membership-card-errors');
    const cardMount = form.querySelector('#membership-card');

    let tiers = [];
    try {
        const parsed = JSON.parse(form.dataset.tiers || '[]');
        tiers = Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        tiers = [];
    }

    let stripe = null;
    let card = null;
    let paymentSession = null;
    let completedPayment = null;

    const currentTier = () => tiers.find(tier => tier.key === tierSelect?.value) || null;

    const mountCard = () => {
        if (card || !form.dataset.stripeKey || typeof window.Stripe !== 'function' || !cardMount) return;
        stripe = window.Stripe(form.dataset.stripeKey);
        card = stripe.elements().create('card', {
            hidePostalCode: false,
            style: {
                base: {color: '#102333', fontSize: '16px', fontFamily: 'inherit', '::placeholder': {color: '#8aa0ad'}},
                invalid: {color: '#b42318'},
            },
        });
        card.mount(cardMount);
        card.on('change', event => {
            if (cardErrors) cardErrors.textContent = event.error?.message || '';
        });
    };

    const refreshTier = () => {
        paymentSession = null;
        completedPayment = null;

        const tier = currentTier();
        const isPaid = Boolean(tier?.paid);

        if (paymentBlock) paymentBlock.hidden = !isPaid;
        if (totalDisplay) {
            totalDisplay.textContent = tier
                ? (isPaid ? `£${(tier.pence / 100).toFixed(2)}` : 'Free')
                : '—';
        }
        if (buttonLabel) {
            buttonLabel.textContent = isPaid ? defaultLabel : 'Submit Application';
        }
        if (isPaid) mountCard();
    };

    tierSelect?.addEventListener('change', refreshTier);
    refreshTier();

    // Selecting a category card scrolls to the form with that category chosen.
    form.ownerDocument.querySelectorAll('[data-select-tier]').forEach(button => {
        button.addEventListener('click', () => {
            if (tierSelect) {
                tierSelect.value = button.dataset.selectTier;
                refreshTier();
            }
            document.getElementById('membership-application')?.scrollIntoView({behavior: 'smooth', block: 'start'});
            form.querySelector('#membership-first-name')?.focus({preventScroll: true});
        });
    });

    const postForm = async formData => {
        const response = await fetch(window.emcMembershipConfig.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result?.data?.message || 'Your application could not be submitted.');
        }
        return result.data;
    };

    const setStatus = (message, state = '') => {
        if (!status) return;
        status.className = `membership-form-status${state ? ` ${state}` : ''}`;
        status.textContent = message;
        if (message) status.focus();
    };

    form.addEventListener('submit', async event => {
        event.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        if (!window.emcMembershipConfig?.ajaxUrl || !window.emcMembershipConfig?.nonce) {
            setStatus('Membership applications are temporarily unavailable. Please try again later.', 'is-error');
            return;
        }

        const tier = currentTier();
        const isPaid = Boolean(tier?.paid);

        if (submitButton) submitButton.disabled = true;
        if (buttonLabel) buttonLabel.textContent = isPaid ? 'Preparing payment…' : 'Submitting…';
        setStatus('');

        try {
            if (isPaid && (!stripe || !card)) {
                throw new Error('Secure card payment is temporarily unavailable. Please contact the centre.');
            }

            if (!completedPayment) {
                let prepared = paymentSession;
                if (!prepared) {
                    const formData = new FormData(form);
                    formData.set('nonce', window.emcMembershipConfig.nonce);
                    prepared = await postForm(formData);
                    if (prepared.requiresPayment) paymentSession = prepared;
                }

                if (prepared.requiresPayment) {
                    if (buttonLabel) buttonLabel.textContent = `Paying ${prepared.amount}…`;
                    const confirmation = await stripe.confirmCardPayment(prepared.clientSecret, {
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
                        throw new Error(confirmation.error.message || 'Stripe could not complete the payment.');
                    }
                    if (confirmation.paymentIntent?.status !== 'succeeded') {
                        throw new Error('Stripe has not confirmed the payment. Please try again.');
                    }
                    completedPayment = {
                        token: prepared.token,
                        paymentIntent: confirmation.paymentIntent.id,
                    };
                } else {
                    completedPayment = {freeResult: prepared};
                }
            }

            let finalResult = completedPayment.freeResult;
            if (!finalResult) {
                if (buttonLabel) buttonLabel.textContent = 'Confirming membership…';
                const confirmationData = new FormData();
                confirmationData.set('action', 'emc_membership_confirm');
                confirmationData.set('nonce', window.emcMembershipConfig.nonce);
                confirmationData.set('token', completedPayment.token);
                confirmationData.set('payment_intent', completedPayment.paymentIntent);
                finalResult = await postForm(confirmationData);
            }

            form.reset();
            card?.clear();
            refreshTier();
            setStatus(finalResult.message, 'is-success');
            if (submitButton) submitButton.hidden = true;
        } catch (error) {
            setStatus(error.message || 'Your application could not be submitted. Please try again.', 'is-error');
        } finally {
            if (submitButton && !submitButton.hidden) submitButton.disabled = false;
            if (buttonLabel && !submitButton?.hidden) buttonLabel.textContent = currentTier()?.paid ? defaultLabel : 'Submit Application';
        }
    });
});
