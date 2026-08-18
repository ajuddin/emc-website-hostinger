/**
 * EMC Theme — events.js
 * Phase 10: Events page interactions with URL hash sync.
 *
 * @package emc-theme
 */

document.addEventListener('DOMContentLoaded', () => {

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

    /* =====================
       Category Filter Chips + URL Hash Sync
       ===================== */
    const chips     = document.querySelectorAll('.filter-chip');
    const cards     = document.querySelectorAll('.event-hub-card');
    const noResults = document.getElementById('no-results');

    function applyFilter(filter) {
        let visibleCount = 0;

        // Update active chip
        chips.forEach(c => {
            const isActive = c.dataset.filter === filter;
            c.classList.toggle('active', isActive);
            c.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        // Filter cards with smooth transition
        cards.forEach(card => {
            const category = card.dataset.category || 'all';
            const isVisible = filter === 'all' || category === filter;

            if (isVisible) {
                card.classList.remove('filtered-out');
                visibleCount++;
            } else {
                card.classList.add('filtered-out');
            }
        });

        if (noResults) {
            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        // Sync URL hash (without triggering scroll)
        const newUrl = filter === 'all'
            ? window.location.pathname + window.location.search
            : `${window.location.pathname}${window.location.search}#${filter}`;
        history.replaceState(null, '', newUrl);
    }

    // Add ARIA pressed state to chips
    chips.forEach(chip => {
        chip.setAttribute('role', 'button');
        chip.setAttribute('aria-pressed', chip.classList.contains('active') ? 'true' : 'false');

        chip.addEventListener('click', () => {
            applyFilter(chip.dataset.filter);
        });

        // Keyboard support
        chip.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                chip.click();
            }
        });
    });

    // Read URL hash on load
    const hashFilter = window.location.hash.replace('#', '');
    if (hashFilter && document.querySelector(`.filter-chip[data-filter="${hashFilter}"]`)) {
        applyFilter(hashFilter);
    }

    /* =====================
       Grid / List View Toggle
       ===================== */
    const gridBtn   = document.getElementById('grid-view-btn');
    const listBtn   = document.getElementById('list-view-btn');
    const container = document.getElementById('events-container');
    const VIEW_KEY  = 'emc_events_view';

    function setView(mode) {
        if (mode === 'list') {
            container?.classList.add('list-view');
            listBtn?.classList.add('active');
            gridBtn?.classList.remove('active');
            listBtn?.setAttribute('aria-pressed', 'true');
            gridBtn?.setAttribute('aria-pressed', 'false');
        } else {
            container?.classList.remove('list-view');
            gridBtn?.classList.add('active');
            listBtn?.classList.remove('active');
            gridBtn?.setAttribute('aria-pressed', 'true');
            listBtn?.setAttribute('aria-pressed', 'false');
        }
        try { localStorage.setItem(VIEW_KEY, mode); } catch (_) {}
    }

    // Restore last-used view preference
    const savedView = localStorage.getItem(VIEW_KEY) || 'grid';
    setView(savedView);

    gridBtn?.addEventListener('click', () => setView('grid'));
    listBtn?.addEventListener('click', () => setView('list'));

    // ARIA
    gridBtn?.setAttribute('role', 'button');
    listBtn?.setAttribute('role', 'button');

    /* =====================
       Full Flyer Preview
       ===================== */
    const flyerModal = document.createElement('div');
    flyerModal.className = 'event-flyer-modal';
    flyerModal.innerHTML = `
        <button class="event-flyer-close" type="button" aria-label="Close flyer">&times;</button>
        <img src="" alt="Event flyer">
    `;
    document.body.appendChild(flyerModal);

    const flyerImg = flyerModal.querySelector('img');
    const closeFlyer = () => {
        flyerModal.classList.remove('is-visible');
        document.body.style.overflow = '';
        if (flyerImg) flyerImg.src = '';
    };

    document.querySelectorAll('.event-hub-img[data-flyer-url]').forEach(img => {
        img.setAttribute('role', 'button');
        img.setAttribute('tabindex', '0');
        img.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            if (!flyerImg) return;
            flyerImg.src = img.dataset.flyerUrl;
            flyerModal.classList.add('is-visible');
            document.body.style.overflow = 'hidden';
        });
        img.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                img.click();
            }
        });
    });

    flyerModal.addEventListener('click', event => {
        if (event.target === flyerModal || event.target.classList.contains('event-flyer-close')) {
            closeFlyer();
        }
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && flyerModal.classList.contains('is-visible')) closeFlyer();
    });

    /* =====================
       Event Registration
       ===================== */
    document.querySelectorAll('.emc-event-registration-form').forEach(form => {
        const submitButton = form.querySelector('.event-register-submit');
        const buttonLabel  = submitButton?.querySelector('span');
        const status       = form.querySelector('.event-form-status');
        const defaultLabel = buttonLabel?.textContent || 'Complete Registration';
        const isPaid       = form.dataset.paid === '1';
        const ticketPrice  = Number.parseInt(form.dataset.ticketPrice || '0', 10);
        const attendees    = form.querySelector('[name="fields[attendees]"]');
        const totalDisplay = form.querySelector('[data-event-payment-total]');
        const cardErrors   = form.querySelector('.event-card-errors');
        const requiredChoiceGroups = form.querySelectorAll('[data-choice-required="1"]');
        let stripe = null;
        let card = null;
        let paymentSession = null;
        let completedPayment = null;

        if (isPaid && form.dataset.stripeKey && typeof window.Stripe === 'function') {
            stripe = window.Stripe(form.dataset.stripeKey);
            card = stripe.elements().create('card', {
                hidePostalCode: false,
                style: {
                    base: { color: '#102333', fontSize: '16px', fontFamily: 'inherit', '::placeholder': { color: '#8aa0ad' } },
                    invalid: { color: '#b42318' },
                },
            });
            card.mount(form.querySelector('.event-stripe-card'));
            card.on('change', event => {
                if (cardErrors) cardErrors.textContent = event.error?.message || '';
            });
        }

        const attendeeCount = () => Math.min(20, Math.max(1, Number.parseInt(attendees?.value || '1', 10) || 1));
        const updateTotal = () => {
            if (totalDisplay && ticketPrice > 0) {
                totalDisplay.textContent = `£${((ticketPrice * attendeeCount()) / 100).toFixed(2)}`;
            }
        };
        attendees?.addEventListener('input', updateTotal);
        updateTotal();

        const validateChoiceGroups = () => {
            requiredChoiceGroups.forEach(group => {
                const choices = group.querySelectorAll('input[type="checkbox"]');
                const first = choices[0];
                if (!first) return;
                const hasSelection = Array.from(choices).some(choice => choice.checked);
                first.setCustomValidity(hasSelection ? '' : 'Please select at least one option.');
            });
        };
        requiredChoiceGroups.forEach(group => group.addEventListener('change', validateChoiceGroups));

        const postForm = async formData => {
            const response = await fetch(window.emcEventsConfig.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData,
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result?.data?.message || 'Your registration could not be submitted.');
            }
            return result.data;
        };

        form.addEventListener('submit', async event => {
            event.preventDefault();

            validateChoiceGroups();
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            if (!window.emcEventsConfig?.ajaxUrl || !window.emcEventsConfig?.nonce) {
                if (status) {
                    status.className = 'event-form-status is-error';
                    status.textContent = 'Registration is temporarily unavailable. Please try again later.';
                }
                return;
            }

            if (submitButton) submitButton.disabled = true;
            if (buttonLabel) buttonLabel.textContent = isPaid ? 'Preparing payment…' : 'Submitting…';
            if (status) {
                status.className = 'event-form-status';
                status.textContent = '';
            }

            try {
                if (isPaid && (!stripe || !card)) {
                    throw new Error('Secure card payment is temporarily unavailable. Please contact the centre.');
                }

                if (!completedPayment) {
                    let prepared = paymentSession;
                    if (!prepared) {
                        const formData = new FormData(form);
                        formData.set('nonce', window.emcEventsConfig.nonce);
                        prepared = await postForm(formData);
                        if (prepared.requiresPayment) paymentSession = prepared;
                    }

                    if (prepared.requiresPayment) {
                        if (buttonLabel) buttonLabel.textContent = `Paying ${prepared.amount}…`;
                        const nameInput = form.querySelector('[name="fields[full_name]"]') || form.querySelector('input[type="text"]');
                        const emailInput = form.querySelector('input[type="email"]');
                        const confirmation = await stripe.confirmCardPayment(prepared.clientSecret, {
                            payment_method: {
                                card,
                                billing_details: {
                                    name: nameInput?.value || undefined,
                                    email: emailInput?.value || undefined,
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
                        completedPayment = { freeResult: prepared };
                    }
                }

                let finalResult = completedPayment.freeResult;
                if (!finalResult) {
                    if (buttonLabel) buttonLabel.textContent = 'Confirming registration…';
                    const confirmationData = new FormData();
                    confirmationData.set('action', 'emc_event_confirm_registration');
                    confirmationData.set('nonce', window.emcEventsConfig.nonce);
                    confirmationData.set('token', completedPayment.token);
                    confirmationData.set('payment_intent', completedPayment.paymentIntent);
                    finalResult = await postForm(confirmationData);
                }

                form.reset();
                card?.clear();
                updateTotal();
                if (status) {
                    status.className = 'event-form-status is-success';
                    status.textContent = finalResult.message;
                    status.focus();
                }
                if (submitButton) submitButton.hidden = true;
            } catch (error) {
                if (status) {
                    status.className = 'event-form-status is-error';
                    status.textContent = error.message || 'Your registration could not be submitted. Please try again.';
                    status.focus();
                }
            } finally {
                if (submitButton && !submitButton.hidden) submitButton.disabled = false;
                if (buttonLabel) buttonLabel.textContent = defaultLabel;
            }
        });
    });
});
