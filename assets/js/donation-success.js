/**
 * Keep donation pages in sync with the EMC Payments modal after Stripe confirms.
 */
(function () {
    'use strict';

    const snapshots = new WeakMap();
    let activeRoot = null;
    let latestBanner = null;
    let bridgeWrapped = false;

    const donationRoots = () => Array.from(document.querySelectorAll(
        '.tab-panel, .ramadan-form-col .form-card, #fidya-calc'
    ));

    function snapshotRoot(root) {
        if (!root || snapshots.has(root)) return;
        snapshots.set(root, {
            fields: Array.from(root.querySelectorAll('input, textarea, select')).map(field => ({
                field,
                value: field.value,
                checked: field.checked,
                selectedIndex: field.selectedIndex,
            })),
            activeButtons: Array.from(root.querySelectorAll('.amount-btn, .cat-btn')).map(button => ({
                button,
                active: button.classList.contains('active'),
            })),
            wrappers: Array.from(root.querySelectorAll('.custom-amount-wrapper')).map(wrapper => ({
                wrapper,
                display: wrapper.style.display,
            })),
        });
    }

    function rootForTrigger(trigger) {
        if (!trigger) return null;
        return trigger.closest('.tab-panel')
            || trigger.closest('.ramadan-form-col .form-card')
            || trigger.closest('#fidya-calc');
    }

    function resetRoot(root) {
        const snapshot = snapshots.get(root);
        if (!snapshot) return;

        snapshot.fields.forEach(({field, value, checked, selectedIndex}) => {
            if (!field.isConnected) return;
            if (field instanceof HTMLSelectElement) {
                field.selectedIndex = selectedIndex;
            } else if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = checked;
            } else {
                field.value = value;
            }
            field.setCustomValidity?.('');
            field.dispatchEvent(new Event('input', {bubbles: true}));
            field.dispatchEvent(new Event('change', {bubbles: true}));
        });

        snapshot.activeButtons.forEach(({button, active}) => {
            if (button.isConnected) button.classList.toggle('active', active);
        });
        snapshot.wrappers.forEach(({wrapper, display}) => {
            if (wrapper.isConnected) wrapper.style.display = display;
        });

        // Ramadan's totals are held in its script state, so replay its default choices.
        root.querySelectorAll('input[type="radio"]:checked').forEach(field => {
            field.dispatchEvent(new Event('change', {bubbles: true}));
        });
        root.querySelectorAll('.amount-btn.active:not(.custom-other)').forEach(button => button.click());
    }

    function showPageConfirmation(root, modalMessage) {
        const host = root.matches('.form-card, .ramadan-calc-card')
            ? root
            : root.querySelector('.form-card') || root;
        host.querySelector('.emc-donation-page-success')?.remove();

        const banner = document.createElement('div');
        banner.className = 'emc-donation-page-success';
        banner.setAttribute('role', 'status');
        banner.setAttribute('aria-live', 'polite');
        banner.tabIndex = -1;

        const icon = document.createElement('span');
        icon.className = 'emc-donation-page-success-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.innerHTML = '<i class="fas fa-check-circle"></i>';

        const copy = document.createElement('div');
        const title = document.createElement('h3');
        title.textContent = 'Donation successful';
        const message = document.createElement('p');
        message.textContent = modalMessage
            ? `${modalMessage} A confirmation receipt will be sent to your email address.`
            : 'JazakAllahu Khairan. Your donation was received successfully. A confirmation receipt will be sent to your email address.';
        copy.append(title, message);
        banner.append(icon, copy);
        host.prepend(banner);
        latestBanner = banner;
    }

    function focusConfirmationAfterClose() {
        if (!latestBanner?.isConnected) return;
        window.setTimeout(() => {
            latestBanner.scrollIntoView({behavior: 'smooth', block: 'center'});
            latestBanner.focus({preventScroll: true});
        }, 450);
    }

    function handleSuccess(success) {
        if (!success || success.hidden || success.dataset.emcPageHandled === '1') return;
        success.dataset.emcPageHandled = '1';
        const root = activeRoot || document.querySelector('.tab-panel.active') || donationRoots()[0];
        if (!root) return;

        const modalMessage = document.getElementById('esm-success-message')?.textContent?.trim() || '';
        resetRoot(root);
        showPageConfirmation(root, modalMessage);

        document.getElementById('esm-success-close')?.addEventListener('click', focusConfirmationAfterClose, {once: true});
        document.querySelector('.emc-stripe-modal-close')?.addEventListener('click', focusConfirmationAfterClose, {once: true});
    }

    function wrapPaymentBridge() {
        if (bridgeWrapped || typeof window.emcOpenStripeModal !== 'function') return;
        const original = window.emcOpenStripeModal;
        window.emcOpenStripeModal = function (options = {}) {
            if (options.tab === 'ramadan-daily') {
                activeRoot = document.querySelector('.ramadan-form-col .form-card');
            } else if (options.tab === 'fidya') {
                activeRoot = document.getElementById('fidya-calc');
            }
            if (activeRoot) snapshotRoot(activeRoot);
            return original.apply(this, arguments);
        };
        bridgeWrapped = true;
    }

    function init() {
        donationRoots().forEach(snapshotRoot);
        document.addEventListener('click', event => {
            const trigger = event.target.closest('.donate-submit, #fidya-btn');
            if (!trigger) return;
            activeRoot = rootForTrigger(trigger);
            if (activeRoot) snapshotRoot(activeRoot);
        }, true);

        wrapPaymentBridge();
        const bridgeTimer = window.setInterval(() => {
            wrapPaymentBridge();
            if (bridgeWrapped) window.clearInterval(bridgeTimer);
        }, 100);
        window.setTimeout(() => window.clearInterval(bridgeTimer), 10000);

        const observer = new MutationObserver(() => {
            const success = document.getElementById('esm-success');
            if (success?.hidden) delete success.dataset.emcPageHandled;
            handleSuccess(success);
        });
        observer.observe(document.body, {subtree: true, childList: true, attributes: true, attributeFilter: ['hidden']});
        handleSuccess(document.getElementById('esm-success'));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, {once: true});
    } else {
        init();
    }
}());
