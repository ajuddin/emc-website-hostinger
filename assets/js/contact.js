/**
 * EMC Theme — contact.js
 * Phase 10: Contact form with WordPress AJAX + real-time validation.
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
    }, { threshold: 0.1 });

    document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));

    /* =====================
       Contact Form — AJAX Submission
       ===================== */
    const contactForm    = document.getElementById('contact-form');
    const contactSuccess = document.getElementById('contact-success');

    if (!contactForm) return;

    // Real-time validation feedback
    contactForm.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
        field.addEventListener('blur', () => validateField(field));
        field.addEventListener('input', () => clearError(field));
    });

    contactForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Validate all required fields
        const fields   = contactForm.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid    = true;

        fields.forEach(field => {
            if (!validateField(field)) isValid = false;
        });

        if (!isValid) {
            // Focus first invalid field
            contactForm.querySelector('[aria-invalid="true"]')?.focus();
            return;
        }

        // Submit state
        const submitBtn  = contactForm.querySelector('[type="submit"]');
        const origHTML   = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
        submitBtn.disabled  = true;

        try {
            if (contactForm.dataset.ajaxUrl) {
                const formData = new FormData(contactForm);
                const res  = await fetch(contactForm.dataset.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData,
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.data?.message || 'Something went wrong. Please try again.');
                }
            } else {
                // Static prototype: simulate success
                await new Promise(resolve => setTimeout(resolve, 1500));
            }

            // Show success state
            contactForm.querySelectorAll('.form-group, .form-row, [type="submit"]').forEach(el => {
                el.style.display = 'none';
            });

            if (contactSuccess) {
                contactSuccess.style.display = 'flex';
                contactSuccess.setAttribute('role', 'status');
                contactSuccess.setAttribute('aria-live', 'polite');
                contactSuccess.focus?.();
            }

        } catch (err) {
            submitBtn.innerHTML = origHTML;
            submitBtn.disabled  = false;

            // Show inline error
            showFormError(contactForm, err.message || 'An error occurred. Please try again.');
        }
    });

    /**
     * Validate a single form field. Returns true if valid.
     */
    function validateField(field) {
        const val = field.value.trim();
        let errorMsg = '';

        if (!val) {
            errorMsg = `${getLabel(field)} is required.`;
        } else if (field.type === 'email' && !isValidEmail(val)) {
            errorMsg = 'Please enter a valid email address.';
        } else if (field.type === 'tel' && val.length < 8) {
            errorMsg = 'Please enter a valid phone number.';
        }

        if (errorMsg) {
            setError(field, errorMsg);
            return false;
        }

        clearError(field);
        return true;
    }

    function setError(field, message) {
        field.setAttribute('aria-invalid', 'true');
        field.style.borderColor = '#E53935';

        let errEl = field.nextElementSibling;
        if (!errEl || !errEl.classList.contains('field-error')) {
            errEl = document.createElement('span');
            errEl.className = 'field-error';
            errEl.setAttribute('aria-live', 'assertive');
            field.parentNode.insertBefore(errEl, field.nextSibling);
        }
        errEl.textContent = message;
    }

    function clearError(field) {
        field.removeAttribute('aria-invalid');
        field.style.borderColor = '';
        const errEl = field.nextElementSibling;
        if (errEl?.classList.contains('field-error')) errEl.textContent = '';
    }

    function showFormError(form, message) {
        let errBox = form.querySelector('.form-submit-error');
        if (!errBox) {
            errBox = document.createElement('p');
            errBox.className = 'form-submit-error';
            errBox.style.cssText = 'color:#E53935;font-size:0.875rem;margin-top:0.5rem;text-align:center;';
            errBox.setAttribute('role', 'alert');
            form.appendChild(errBox);
        }
        errBox.textContent = message;
    }

    function getLabel(field) {
        const label = field.closest('.form-group')?.querySelector('label');
        return label ? label.textContent.replace('*', '').trim() : 'This field';
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
});
