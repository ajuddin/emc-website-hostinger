(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('campaign-inline-donation-form');
        if (!form) return;

        const buttons = Array.from(form.querySelectorAll('.campaign-amount-button'));
        const customWrap = form.querySelector('.campaign-custom-amount');
        const customInput = document.getElementById('campaign-donation-custom-amount');
        const error = form.querySelector('.campaign-donation-error');

        function showError(message) {
            error.textContent = message;
            error.hidden = false;
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                buttons.forEach(function (item) { item.classList.remove('is-active'); });
                button.classList.add('is-active');
                const custom = button.classList.contains('is-custom');
                customWrap.hidden = !custom;
                if (custom) customInput.focus();
                error.hidden = true;
            });
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const active = form.querySelector('.campaign-amount-button.is-active');
            const amount = active && active.classList.contains('is-custom') ? Number(customInput.value) : Number(active && active.dataset.amount);
            const name = document.getElementById('campaign-donor-name').value.trim();
            const email = document.getElementById('campaign-donor-email').value.trim();
            const address = document.getElementById('campaign-donor-address').value.trim();
            const postcodeInput = document.getElementById('campaign-donor-postcode');
            const postcodeCompact = postcodeInput.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            const postcode = postcodeCompact.length > 3 ? postcodeCompact.slice(0, -3) + ' ' + postcodeCompact.slice(-3) : postcodeCompact;
            const giftAid = document.getElementById('campaign-donor-gift-aid').checked;
            const messages = window.emcCampaignDonation.messages;
            postcodeInput.value = postcode;

            if (!amount || amount < Number(window.emcCampaignDonation.minimum || 0.5)) return showError(messages.amount);
            if (!name) return showError(messages.name);
            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return showError(messages.email);
            if (giftAid && (!address || !/^[A-Z0-9]{2,4} [A-Z0-9]{3}$/.test(postcode))) return showError(messages.giftAid);
            if (typeof window.emcOpenStripeModal !== 'function') return showError(messages.unavailable);

            error.hidden = true;
            window.emcOpenStripeModal({
                amount: Math.round(amount * 100),
                fund: window.emcCampaignDonation.fund,
                tab: 'campaign',
                name: name,
                email: email,
                address: address,
                postcode: postcode,
                message: document.getElementById('campaign-donor-message').value.trim(),
                giftAid: giftAid
            });
        });
    });
})();
