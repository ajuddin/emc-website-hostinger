(function () {
    'use strict';

    const ready = (callback) => {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', callback);
        else callback();
    };

    const clean = (value) => String(value || '').replace(/\s+/g, ' ').trim();
    const money = (value) => Number(clean(value).replace(/[^0-9.-]/g, '')) || 0;

    ready(() => {
        const wrap = document.querySelector('.wrap');
        if (!wrap) return;

        const tables = [...wrap.querySelectorAll('table.widefat')].filter((table) => table.querySelector('tbody'));
        if (!tables.length) return;

        wrap.classList.add('emc-donations-enhanced');

        const dialog = document.createElement('dialog');
        dialog.className = 'emc-record-dialog';
        dialog.innerHTML = '<div class="emc-record-dialog-head"><h2>Record details</h2><button type="button" class="emc-record-close" aria-label="Close">&times;</button></div><dl class="emc-record-details"></dl>';
        document.body.appendChild(dialog);
        dialog.querySelector('.emc-record-close').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });

        const controllers = tables.map((table, tableIndex) => enhanceTable(table, tableIndex, dialog));
        addSummary(wrap, controllers);
    });

    function headingsFor(table) {
        return [...table.querySelectorAll('thead th')].map((cell) => clean(cell.textContent));
    }

    function cellIndex(headings, candidates) {
        return headings.findIndex((heading) => candidates.some((candidate) => heading.toLowerCase().includes(candidate)));
    }

    function enhanceTable(table, tableIndex, dialog) {
        const heading = table.previousElementSibling && /^H[1-6]$/.test(table.previousElementSibling.tagName)
            ? table.previousElementSibling
            : [...table.parentElement.children].reverse().find((node) => node.compareDocumentPosition(table) & Node.DOCUMENT_POSITION_FOLLOWING && /^H[1-6]$/.test(node.tagName));
        const title = clean(heading?.textContent) || (tableIndex ? 'Payments received' : 'Scheduled subscriptions');
        const headings = headingsFor(table);
        const statusIndex = cellIndex(headings, ['status']);
        const fundIndex = cellIndex(headings, ['fund']);
        const amountIndex = cellIndex(headings, ['amount']);
        const rows = [...table.querySelectorAll('tbody tr')].filter((row) => row.children.length > 1);

        const section = document.createElement('section');
        section.className = 'emc-history-section';
        if (heading) heading.parentNode.insertBefore(section, heading);
        else table.parentNode.insertBefore(section, table);
        if (heading) section.appendChild(heading);

        const toolbar = document.createElement('div');
        toolbar.className = 'emc-history-toolbar';
        toolbar.innerHTML = '<input type="search" class="emc-history-search" placeholder="Search all records…" aria-label="Search ' + title + '"><select class="emc-history-filter" aria-label="Filter ' + title + '"><option value="">All ' + (statusIndex >= 0 ? 'statuses' : 'funds') + '</option></select><select class="emc-history-per-page" aria-label="Records per page"><option value="10">10 per page</option><option value="25">25 per page</option><option value="50">50 per page</option><option value="100">100 per page</option></select>';
        section.appendChild(toolbar);

        const tableWrap = document.createElement('div');
        tableWrap.className = 'emc-history-table-wrap';
        table.parentNode.insertBefore(tableWrap, table);
        tableWrap.appendChild(table);
        section.appendChild(tableWrap);

        const detailHead = document.createElement('th');
        detailHead.textContent = 'Details';
        table.querySelector('thead tr')?.appendChild(detailHead);

        rows.forEach((row) => {
            const detailCell = document.createElement('td');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'button emc-view-record';
            button.textContent = 'View';
            button.addEventListener('click', () => showDetails(dialog, title, headings, row));
            detailCell.appendChild(button);
            row.appendChild(detailCell);

            if (statusIndex >= 0 && row.children[statusIndex]) {
                const value = clean(row.children[statusIndex].textContent);
                if (value) row.children[statusIndex].innerHTML = '<span class="emc-status-badge is-' + value.toLowerCase().replace(/[^a-z0-9]+/g, '-') + '">' + escapeHtml(value) + '</span>';
            }
        });

        const filterIndex = statusIndex >= 0 ? statusIndex : fundIndex;
        const filter = toolbar.querySelector('.emc-history-filter');
        const options = [...new Set(rows.map((row) => clean(row.children[filterIndex]?.textContent)).filter(Boolean))].sort();
        if (filterIndex < 0 || !options.length) filter.hidden = true;
        options.forEach((value) => {
            const option = document.createElement('option');
            option.value = value.toLowerCase();
            option.textContent = value;
            filter.appendChild(option);
        });

        const footer = document.createElement('div');
        footer.className = 'emc-history-footer';
        footer.innerHTML = '<span class="emc-history-count"></span><nav class="emc-pagination" aria-label="' + title + ' pages"></nav>';
        section.appendChild(footer);

        const state = { page: 1, perPage: 10, search: '', filter: '' };
        const render = () => {
            const matches = rows.filter((row) => {
                const searchMatch = !state.search || clean(row.textContent).toLowerCase().includes(state.search);
                const filterValue = filterIndex >= 0 ? clean(row.children[filterIndex]?.textContent).toLowerCase() : '';
                return searchMatch && (!state.filter || filterValue === state.filter);
            });
            const pages = Math.max(1, Math.ceil(matches.length / state.perPage));
            state.page = Math.min(state.page, pages);
            rows.forEach((row) => { row.hidden = true; });
            matches.slice((state.page - 1) * state.perPage, state.page * state.perPage).forEach((row) => { row.hidden = false; });
            footer.querySelector('.emc-history-count').textContent = matches.length
                ? 'Showing ' + ((state.page - 1) * state.perPage + 1) + '–' + Math.min(state.page * state.perPage, matches.length) + ' of ' + matches.length
                : 'No matching records';
            renderPagination(footer.querySelector('.emc-pagination'), state, pages, render);
        };

        toolbar.querySelector('.emc-history-search').addEventListener('input', (event) => {
            state.search = clean(event.target.value).toLowerCase();
            state.page = 1;
            render();
        });
        filter.addEventListener('change', (event) => {
            state.filter = event.target.value;
            state.page = 1;
            render();
        });
        toolbar.querySelector('.emc-history-per-page').addEventListener('change', (event) => {
            state.perPage = Number(event.target.value) || 10;
            state.page = 1;
            render();
        });
        render();

        return { title, headings, rows, amountIndex, statusIndex };
    }

    function renderPagination(container, state, pages, render) {
        container.innerHTML = '';
        const add = (label, page, disabled, current) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = label;
            button.disabled = disabled;
            if (current) button.classList.add('is-current');
            button.addEventListener('click', () => { state.page = page; render(); });
            container.appendChild(button);
        };
        add('‹', Math.max(1, state.page - 1), state.page === 1, false);
        const start = Math.max(1, state.page - 2);
        const end = Math.min(pages, start + 4);
        for (let page = Math.max(1, end - 4); page <= end; page++) add(String(page), page, false, page === state.page);
        add('›', Math.min(pages, state.page + 1), state.page === pages, false);
    }

    function showDetails(dialog, title, headings, row) {
        dialog.querySelector('h2').textContent = title.replace(/s$/, '') + ' details';
        const cells = [...row.children].slice(0, headings.length);
        dialog.querySelector('.emc-record-details').innerHTML = headings.map((heading, index) => '<div class="emc-record-detail"><dt>' + escapeHtml(heading || 'Field') + '</dt><dd>' + escapeHtml(clean(cells[index]?.textContent) || 'Not supplied') + '</dd></div>').join('');
        if (typeof dialog.showModal === 'function') dialog.showModal();
        else dialog.setAttribute('open', '');
    }

    function addSummary(wrap, controllers) {
        const subscription = controllers.find((item) => item.statusIndex >= 0) || controllers[0];
        const payments = controllers.find((item) => item !== subscription) || controllers[1];
        const active = subscription ? subscription.rows.filter((row) => clean(row.children[subscription.statusIndex]?.textContent).toLowerCase() === 'active').length : 0;
        const paymentTotal = payments ? payments.rows.reduce((total, row) => total + money(row.children[payments.amountIndex]?.textContent), 0) : 0;
        const giftAidIndex = payments ? cellIndex(payments.headings, ['gift aid']) : -1;
        const giftAid = payments && giftAidIndex >= 0 ? payments.rows.filter((row) => /^yes$/i.test(clean(row.children[giftAidIndex]?.textContent))).length : 0;
        const summary = document.createElement('div');
        summary.className = 'emc-donation-summary';
        summary.innerHTML = summaryCard('Payments received', payments?.rows.length || 0)
            + summaryCard('Total received', '£' + paymentTotal.toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 }))
            + summaryCard('Active schedules', active)
            + summaryCard('Gift Aid payments', giftAid);
        const intro = [...wrap.children].find((node) => node.tagName === 'P');
        if (intro) intro.insertAdjacentElement('afterend', summary);
        else wrap.insertBefore(summary, wrap.firstChild.nextSibling);
    }

    function summaryCard(label, value) {
        return '<div class="emc-summary-card"><span>' + escapeHtml(label) + '</span><strong>' + escapeHtml(value) + '</strong></div>';
    }

    function escapeHtml(value) {
        const element = document.createElement('div');
        element.textContent = String(value ?? '');
        return element.innerHTML;
    }
})();
