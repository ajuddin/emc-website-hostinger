document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('emc-reports-list');
    const addButton = document.getElementById('emc-add-report');
    const template = document.getElementById('emc-report-row-template');

    if (!list || !addButton || !template) return;

    let nextIndex = Number.parseInt(list.dataset.nextIndex || '0', 10);

    function refreshRows() {
        const rows = [...list.querySelectorAll('[data-report-row]')];

        rows.forEach((row, index) => {
            const number = row.querySelector('[data-report-number]');
            const title = row.querySelector('[data-report-title]');
            const titleInput = row.querySelector('input[name$="[title]"]');
            const up = row.querySelector('[data-report-up]');
            const down = row.querySelector('[data-report-down]');

            if (number) number.textContent = `Report ${index + 1}:`;
            if (title) title.textContent = titleInput?.value || 'New annual report';
            if (up) up.disabled = index === 0;
            if (down) down.disabled = index === rows.length - 1;
        });
    }

    function addReport() {
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
        list.insertAdjacentHTML('beforeend', html);
        refreshRows();
        list.lastElementChild?.querySelector('input[name$="[title]"]')?.focus();
    }

    function selectPdf(row) {
        if (!window.wp?.media) return;

        const frame = window.wp.media({
            title: 'Select annual report PDF',
            button: { text: 'Use this PDF' },
            library: { type: 'application/pdf' },
            multiple: false
        });

        frame.on('select', () => {
            const attachment = frame.state().get('selection').first()?.toJSON();
            const urlInput = row.querySelector('[data-report-url]');
            if (attachment?.url && urlInput) {
                urlInput.value = attachment.url;
                urlInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        frame.open();
    }

    addButton.addEventListener('click', addReport);

    list.addEventListener('input', event => {
        if (event.target.matches('input[name$="[title]"]')) refreshRows();
    });

    list.addEventListener('click', event => {
        const row = event.target.closest('[data-report-row]');
        if (!row) return;

        if (event.target.closest('[data-report-remove]')) {
            row.remove();
            refreshRows();
        } else if (event.target.closest('[data-report-up]')) {
            row.previousElementSibling?.before(row);
            refreshRows();
        } else if (event.target.closest('[data-report-down]')) {
            row.nextElementSibling?.after(row);
            refreshRows();
        } else if (event.target.closest('[data-report-media]')) {
            selectPdf(row);
        }
    });

    refreshRows();
});
