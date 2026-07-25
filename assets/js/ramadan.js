document.addEventListener('DOMContentLoaded', () => {

    /* =====================
       Generate Twinkling Stars
       ===================== */
    const starsBg = document.getElementById('stars-bg');
    if (starsBg) {
        for (let i = 0; i < 180; i++) {
            const star = document.createElement('div');
            star.classList.add('star');
            const size = Math.random() * 3 + 0.5;
            star.style.cssText = `
                width: ${size}px;
                height: ${size}px;
                top: ${Math.random() * 100}%;
                left: ${Math.random() * 100}%;
                animation-duration: ${Math.random() * 4 + 2}s;
                animation-delay: ${Math.random() * 4}s;
                opacity: ${Math.random() * 0.7 + 0.2};
            `;
            starsBg.appendChild(star);
        }
    }

    /* =====================
       Live Countdown to Ramadan start date
       ===================== */
    const configuredRamadanDate = window.emcRamadanConfig?.startDate || '2027-02-08';
    const ramadanStartDate = new Date(`${configuredRamadanDate}T00:00:00`);
    if (Number.isNaN(ramadanStartDate.getTime())) {
        ramadanStartDate.setFullYear(2027, 1, 8);
        ramadanStartDate.setHours(0, 0, 0, 0);
    }
    const ramadanDate = ramadanStartDate.getTime();

    function updateCountdown() {
        const now = new Date().getTime();
        const diff = ramadanDate - now;

        if (diff <= 0) {
            document.querySelectorAll('.cdown-num, .c-num').forEach(el => el.textContent = '00');
            return;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const secs = Math.floor((diff % (1000 * 60)) / 1000);

        const pad = n => String(n).padStart(2, '0');

        const el = (id) => document.getElementById(id);
        if (el('c-days'))  el('c-days').textContent  = pad(days);
        if (el('c-hours')) el('c-hours').textContent = pad(hours);
        if (el('c-mins'))  el('c-mins').textContent  = pad(mins);
        if (el('c-secs'))  el('c-secs').textContent  = pad(secs);

        // Also update the hero tab countdown on donate page
        if (el('r-days'))  el('r-days').textContent  = pad(days);
        if (el('r-hours')) el('r-hours').textContent = pad(hours);
        if (el('r-mins'))  el('r-mins').textContent  = pad(mins);
    }

    setInterval(updateCountdown, 1000);
    updateCountdown();

    /* =====================
       Ramadan Schedule Calculator
       Uses selectors that match the actual template HTML
       ===================== */
    const amountBtns  = document.querySelectorAll('.ramadan-form-col .amount-btn');
    const customInput = document.getElementById('ramadan-custom-input');
    const rmTotal     = document.getElementById('rm-total');

    let currentDays   = 30;
    let currentAmount = 3;

    function getSelectedAmount() {
        const activeBtn = document.querySelector('.ramadan-form-col .amount-btn.active');
        if (!activeBtn) return currentAmount;
        if (activeBtn.classList.contains('custom-other')) {
            return parseFloat(customInput?.value) || 0;
        }
        return parseFloat(activeBtn.dataset.amount) || 3;
    }

    function updateSummary() {
        currentAmount = getSelectedAmount();
        const total   = currentAmount * currentDays;

        if (rmTotal) {
            rmTotal.innerHTML = `£${total.toFixed(2)} <span>(${currentDays} × £${currentAmount.toFixed(2)})</span>`;
        }

        const submitBtn = document.getElementById('ramadan-submit');
        if (submitBtn) {
            submitBtn.innerHTML = `<i class="fas fa-moon"></i> Schedule Daily Giving - £${currentAmount.toFixed(2)}/day`;
        }
    }

    // Period radios
    document.querySelectorAll('input[name="rmperiod"]').forEach(radio => {
        radio.addEventListener('change', () => {
            currentDays = parseInt(radio.value) || 30;
            updateSummary();
        });
    });

    // Amount buttons
    amountBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            amountBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const customWrapper = document.getElementById('ramadan-custom-wrapper');
            if (btn.classList.contains('custom-other')) {
                if (customWrapper) customWrapper.style.display = 'block';
            } else {
                if (customWrapper) customWrapper.style.display = 'none';
            }
            updateSummary();
        });
    });

    if (customInput) customInput.addEventListener('input', updateSummary);

    updateSummary();

    function formatDateForStripe(date) {
        const yyyy = date.getFullYear();
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const dd = String(date.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
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

    const ramadanGiftAid = document.getElementById('ramadan-giftaid');
    const syncRamadanAddressRequirement = () => {
        const required = !!ramadanGiftAid?.checked;
        ['ramadan-donor-address', 'ramadan-donor-postcode'].forEach(id => {
            const field = document.getElementById(id);
            if (!field) return;
            field.required = required;
            field.setAttribute('aria-required', required ? 'true' : 'false');
        });
    };
    ramadanGiftAid?.addEventListener('change', syncRamadanAddressRequirement);
    syncRamadanAddressRequirement();


    /* =====================
       Stripe — Schedule My Ramadan Giving
       ===================== */
    const ramadanSubmitBtn = document.getElementById('ramadan-submit');
    if (ramadanSubmitBtn) {
        ramadanSubmitBtn.addEventListener('click', () => {
            const dailyPence = Math.round(currentAmount * 100);
            if (dailyPence < 50) {
                alert('Please select a donation amount.');
                return;
            }
            const label = `Ramadan Daily Giving (${currentDays} days x £${currentAmount.toFixed(2)})`;

            const donorName = document.getElementById('ramadan-donor-name')?.value.trim() || '';
            const donorEmail = document.getElementById('ramadan-donor-email')?.value.trim() || '';
            const donorAddress = document.getElementById('ramadan-donor-address')?.value.trim() || '';
            const postcodeInput = document.getElementById('ramadan-donor-postcode');
            const donorPostcode = formatPostcode(postcodeInput?.value || '');
            const donorMessage = document.getElementById('ramadan-dedication')?.value.trim() || '';
            const giftAid = !!ramadanGiftAid?.checked;
            if (postcodeInput) postcodeInput.value = donorPostcode;

            if (!donorName || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(donorEmail)) {
                alert('Please enter your name and a valid email address.');
                return;
            }
            if (giftAid && !donorAddress) {
                alert('Please enter address line 1.');
                return;
            }
            if (giftAid && !isValidPostcode(donorPostcode)) {
                alert('Please enter a valid UK postcode for Gift Aid.');
                return;
            }

            if (typeof window.emcOpenStripeModal === 'function') {
                window.emcOpenStripeModal({
                    amount: dailyPence,
                    fund: label,
                    tab: 'ramadan-daily',
                    name: donorName,
                    email: donorEmail,
                    address: donorAddress,
                    postcode: donorPostcode,
                    message: donorMessage,
                    frequency: 'daily',
                    startDate: formatDateForStripe(ramadanStartDate),
                    occurrences: currentDays,
                    giftAid,
                });
            }
        });
    }

    /* =====================
       Stripe — Pay Fidya Now
       ===================== */
    const fidyaBtn = document.getElementById('fidya-btn');
    if (fidyaBtn) {
        fidyaBtn.addEventListener('click', () => {
            const rateInput = document.getElementById('fidya-rate');
            const daysInput = document.getElementById('fidya-days');
            const rate = parseFloat(rateInput?.value);
            const days = Number(daysInput?.value);

            if (!Number.isFinite(rate) || rate < 0.5) {
                alert('Please enter a valid Fidya amount.');
                rateInput?.focus();
                return;
            }
            if (!Number.isInteger(days) || days < 1 || days > 30) {
                alert('Please enter the number of missed fasts (between 1 and 30).');
                daysInput?.focus();
                return;
            }
            const totalPence = Math.round(rate * days * 100);

            const donorName = document.getElementById('fidya-donor-name')?.value.trim() || '';
            const donorEmail = document.getElementById('fidya-donor-email')?.value.trim() || '';
            const donorAddress = document.getElementById('fidya-donor-address')?.value.trim() || '';
            const postcodeInput = document.getElementById('fidya-donor-postcode');
            const donorPostcode = formatPostcode(postcodeInput?.value || '');
            if (postcodeInput) postcodeInput.value = donorPostcode;
            let invalidField = null;

            if (!donorName) {
                invalidField = document.getElementById('fidya-donor-name');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(donorEmail)) {
                invalidField = document.getElementById('fidya-donor-email');
            }

            if (invalidField) {
                alert('Please enter your name and a valid email address before paying.');
                invalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                invalidField.focus({ preventScroll: true });
                return;
            }

            const label = `Fidya — ${days} missed fast${days !== 1 ? 's' : ''} (£${rate}/day)`;

            if (typeof window.emcOpenStripeModal === 'function') {
                window.emcOpenStripeModal({
                    amount: totalPence,
                    fund: label,
                    tab: 'fidya',
                    name: donorName,
                    email: donorEmail,
                    address: donorAddress,
                    postcode: donorPostcode,
                });
            } else {
                alert('The secure payment form could not be loaded. Please refresh the page and try again.');
            }
        });
    }

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
    }, { threshold: 0.1 });

    document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));
});
