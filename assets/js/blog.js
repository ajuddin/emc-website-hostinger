document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.share-btn--copy[data-copy-url]').forEach(button => {
        button.addEventListener('click', async () => {
            const url = button.dataset.copyUrl || window.location.href;
            const label = button.querySelector('span');
            const originalLabel = label?.textContent || 'Copy Link';
            let copied = false;

            if (navigator.clipboard && window.isSecureContext) {
                try {
                    await navigator.clipboard.writeText(url);
                    copied = true;
                } catch (error) {
                    copied = false;
                }
            }

            if (!copied) {
                const temporary = document.createElement('textarea');
                temporary.value = url;
                temporary.setAttribute('readonly', '');
                temporary.style.position = 'fixed';
                temporary.style.opacity = '0';
                document.body.appendChild(temporary);
                temporary.select();
                temporary.setSelectionRange(0, temporary.value.length);
                try {
                    copied = document.execCommand('copy');
                } catch (error) {
                    copied = false;
                }
                temporary.remove();
            }

            if (copied) {
                button.classList.add('is-copied');
                button.setAttribute('aria-label', 'Link copied');
                if (label) label.textContent = 'Copied';
            } else {
                button.setAttribute('aria-label', 'Copy failed');
                if (label) label.textContent = 'Copy failed';
            }

            window.setTimeout(() => {
                button.classList.remove('is-copied');
                button.setAttribute('aria-label', 'Copy link');
                if (label) label.textContent = originalLabel;
            }, 2000);
        });
    });
});
