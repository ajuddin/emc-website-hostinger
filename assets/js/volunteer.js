document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('volunteer-form');
    if (!form) return;

    const submit = form.querySelector('.volunteer-submit');
    const submitLabel = submit?.querySelector('span');
    const status = form.querySelector('.volunteer-status');
    const success = document.getElementById('volunteer-success');
    const postcode = form.querySelector('[name="postcode"]');
    const otherToggle = document.getElementById('volunteer-interest-other-toggle');
    const otherFieldWrap = document.getElementById('volunteer-interest-other-field');
    const otherField = document.getElementById('volunteer-interest-other');
    const defaultLabel = submitLabel?.textContent || 'Submit Job Application';

    function clearError(field) {
        field.removeAttribute('aria-invalid');
        const error = field.parentElement?.querySelector('.volunteer-field-error');
        if (error) error.remove();
    }

    function setError(field, message) {
        clearError(field);
        field.setAttribute('aria-invalid', 'true');
        const error = document.createElement('span');
        error.className = 'volunteer-field-error';
        error.setAttribute('role', 'alert');
        error.textContent = message;
        field.insertAdjacentElement('afterend', error);
    }

    function validateField(field) {
        clearError(field);
        if (field.required && field.type === 'checkbox' && !field.checked) {
            setError(field, 'Please select this confirmation.');
            return false;
        }
        if (field.required && !field.value.trim()) {
            setError(field, 'This field is required.');
            return false;
        }
        if (field.type === 'email' && field.value && !field.validity.valid) {
            setError(field, 'Please enter a valid email address.');
            return false;
        }
        if (field.type === 'tel' && field.value.replace(/\D/g, '').length < 8) {
            setError(field, 'Please enter a valid phone number.');
            return false;
        }
        if (field.type === 'file' && field.files?.length) {
            const file = field.files[0];
            const extension = file.name.split('.').pop().toLowerCase();
            if (!['pdf', 'doc', 'docx'].includes(extension) || file.size > 5 * 1024 * 1024) {
                setError(field, 'Please upload a PDF, DOC or DOCX file no larger than 5 MB.');
                return false;
            }
        }
        if (field === postcode && !/^(GIR 0AA|(?:[A-Z]{1,2}\d[A-Z\d]?\s*\d[A-Z]{2}))$/i.test(field.value.trim())) {
            setError(field, 'Please enter a valid UK postcode.');
            return false;
        }
        return true;
    }

    function validateChoiceGroup(group) {
        const selected = group.querySelectorAll('input[type="checkbox"]:checked').length;
        const error = group.querySelector('.volunteer-group-error');
        if (error) error.textContent = selected ? '' : 'Please select at least one option.';
        return selected > 0;
    }

    function syncOtherInterest() {
        const selected = !!otherToggle?.checked;
        if (otherFieldWrap) otherFieldWrap.hidden = !selected;
        if (otherField) {
            otherField.required = selected;
            if (!selected) {
                otherField.value = '';
                clearError(otherField);
            }
        }
    }

    form.querySelectorAll('[required]').forEach(field => {
        field.addEventListener('blur', () => validateField(field));
        field.addEventListener('input', () => clearError(field));
        field.addEventListener('change', () => clearError(field));
    });
    form.querySelectorAll('.volunteer-choice-group input[type="checkbox"]').forEach(field => {
        field.addEventListener('change', () => validateChoiceGroup(field.closest('.volunteer-choice-group')));
    });
    otherToggle?.addEventListener('change', syncOtherInterest);
    postcode?.addEventListener('blur', () => {
        postcode.value = postcode.value.trim().toUpperCase().replace(/\s+/g, ' ');
    });
    syncOtherInterest();

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const fieldsValid = [...form.querySelectorAll('[required]')].map(validateField).every(Boolean);
        const groupsValid = [...form.querySelectorAll('.volunteer-choice-group')].map(validateChoiceGroup).every(Boolean);
        if (!fieldsValid || !groupsValid) {
            form.querySelector('[aria-invalid="true"]')?.focus();
            return;
        }

        if (postcode) postcode.value = postcode.value.trim().toUpperCase().replace(/\s+/g, ' ');
        if (submit) submit.disabled = true;
        if (submitLabel) submitLabel.textContent = 'Submitting…';
        if (status) {
            status.className = 'volunteer-status';
            status.textContent = '';
        }

        // The page markup can be served from a long-lived cache, so the nonce
        // baked into it at render time may have expired. Replace it with a
        // freshly issued one (this admin-ajax request is never page-cached)
        // before relying on it below.
        const nonceField = form.querySelector('[name="nonce"]');
        try {
            const nonceResponse = await fetch(form.dataset.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: new URLSearchParams({action: 'emc_fresh_nonce', nonce_action: 'emc_nonce'}),
            });
            const nonceResult = await nonceResponse.json();
            if (nonceField && nonceResponse.ok && nonceResult?.success && nonceResult?.data?.nonce) {
                nonceField.value = nonceResult.data.nonce;
            }
        } catch (error) {
            // Fall back to the nonce already on the page; the submit below will
            // surface a clear error if it turns out to be stale.
        }

        try {
            const response = await fetch(form.dataset.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: new FormData(form),
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result?.data?.message || 'Your application could not be submitted.');
            }
            form.hidden = true;
            if (success) {
                success.hidden = false;
                success.focus();
            }
        } catch (error) {
            if (status) {
                status.className = 'volunteer-status is-error';
                status.textContent = error.message || 'Your application could not be submitted. Please try again.';
                status.focus();
            }
        } finally {
            if (submit) submit.disabled = false;
            if (submitLabel) submitLabel.textContent = defaultLabel;
        }
    });
});
