/**
 * Gift Aid declaration form submission and validation.
 */

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('gift-aid-form');
    if (!form) return;

    const submitButton = form.querySelector('.gift-aid-submit');
    const submitLabel  = submitButton?.querySelector('span');
    const status       = form.querySelector('.gift-aid-status');
    const success      = document.getElementById('gift-aid-success');
    const postcode     = form.querySelector('[name="postcode"]');
    const country      = form.querySelector('[name="country"]');
    const postcodeMark = document.getElementById('gift-aid-postcode-required');
    const otherCountryField = document.getElementById('gift-aid-other-country-field');
    const otherCountry = form.querySelector('[name="country_other"]');
    const defaultLabel = submitLabel?.textContent || 'Complete Declaration';

    const normalizePostcode = value => value.trim().toUpperCase().replace(/\s+/g, ' ');
    const validUkPostcode = value => /^(GIR 0AA|(?:[A-Z]{1,2}\d[A-Z\d]?\s*\d[A-Z]{2}))$/i.test(value.trim());
    const noPostcodeCountries = ['Isle of Man', 'Jersey', 'Guernsey'];

    function syncPostcodeRequirement() {
        if (!postcode || !country) return;
        const postcodeRequired = !noPostcodeCountries.includes(country.value);
        postcode.required = postcodeRequired;
        postcode.disabled = !postcodeRequired;
        if (!postcodeRequired) {
            postcode.value = '';
            clearFieldError(postcode);
        }
        if (postcodeMark) postcodeMark.hidden = !postcodeRequired;

        const needsCountryName = country.value === 'Other';
        if (otherCountryField) otherCountryField.hidden = !needsCountryName;
        if (otherCountry) {
            otherCountry.required = needsCountryName;
            if (!needsCountryName) {
                otherCountry.value = '';
                clearFieldError(otherCountry);
            }
        }
    }

    function clearFieldError(field) {
        field.removeAttribute('aria-invalid');
        const error = field.parentElement?.querySelector('.gift-aid-field-error');
        if (error) error.remove();
    }

    function setFieldError(field, message) {
        clearFieldError(field);
        field.setAttribute('aria-invalid', 'true');
        const error = document.createElement('span');
        error.className = 'gift-aid-field-error';
        error.setAttribute('role', 'alert');
        error.textContent = message;
        field.insertAdjacentElement('afterend', error);
    }

    function validateField(field) {
        clearFieldError(field);

        if (field.required && field.type === 'checkbox' && !field.checked) {
            setFieldError(field, 'Please select this confirmation.');
            return false;
        }

        if (field.required && !field.value.trim()) {
            setFieldError(field, 'This field is required.');
            return false;
        }

        if (field.type === 'email' && field.value && !field.validity.valid) {
            setFieldError(field, 'Please enter a valid email address.');
            return false;
        }

        if (field.type === 'tel' && field.value.replace(/\D/g, '').length < 8) {
            setFieldError(field, 'Please enter a valid phone number.');
            return false;
        }

        if (field === postcode && country?.value === 'United Kingdom' && !validUkPostcode(field.value)) {
            setFieldError(field, 'Please enter a valid UK postcode.');
            return false;
        }

        return true;
    }

    form.querySelectorAll('[required]').forEach(field => {
        field.addEventListener('blur', () => validateField(field));
        field.addEventListener('change', () => clearFieldError(field));
        field.addEventListener('input', () => clearFieldError(field));
    });

    postcode?.addEventListener('blur', () => {
        postcode.value = normalizePostcode(postcode.value);
    });

    country?.addEventListener('change', () => {
        syncPostcodeRequirement();
        if (postcode?.value) validateField(postcode);
    });

    syncPostcodeRequirement();

    form.addEventListener('submit', async event => {
        event.preventDefault();

        const requiredFields = [...form.querySelectorAll('[required]')];
        const isValid = requiredFields.map(validateField).every(Boolean);
        if (!isValid) {
            form.querySelector('[aria-invalid="true"]')?.focus();
            return;
        }

        if (postcode) postcode.value = normalizePostcode(postcode.value);

        if (submitButton) submitButton.disabled = true;
        if (submitLabel) submitLabel.textContent = 'Submitting…';
        if (status) {
            status.className = 'gift-aid-status';
            status.textContent = '';
        }

        try {
            const response = await fetch(form.dataset.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: new FormData(form),
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result?.data?.message || 'Your declaration could not be submitted.');
            }

            form.hidden = true;
            if (success) {
                success.hidden = false;
                success.focus();
            }
        } catch (error) {
            if (status) {
                status.className = 'gift-aid-status is-error';
                status.textContent = error.message || 'Your declaration could not be submitted. Please try again.';
                status.focus();
            }
        } finally {
            if (submitButton) submitButton.disabled = false;
            if (submitLabel) submitLabel.textContent = defaultLabel;
        }
    });
});
