document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('badr-tile-search');
    const filterButtons = Array.from(document.querySelectorAll('.badr-filter-button'));
    const sortButton = document.getElementById('badr-sort-button');
    const clearButton = document.getElementById('badr-clear-button');
    const tiles = Array.from(document.querySelectorAll('.badr-name-tile'));
    const groups = Array.from(document.querySelectorAll('.badr-tile-group'));
    const showingCount = document.getElementById('badr-showing-count');
    const noResults = document.getElementById('badr-no-results');

    if (!tiles.length) return;

    let activeFilter = 'all';
    let sortOrder = 'asc';

    const updateDirectory = () => {
        const term = (search?.value || '').trim().toLocaleLowerCase();
        let totalShown = 0;

        tiles.forEach(tile => {
            const matchesCategory = activeFilter === 'all'
                || tile.dataset.tier === activeFilter
                || (activeFilter === 'anonymous' && tile.dataset.anonymous === '1');
            const matchesSearch = !term || (tile.dataset.search || '').includes(term);
            const visible = matchesCategory && matchesSearch;

            tile.hidden = !visible;
            if (visible) totalShown += 1;
        });

        groups.forEach(group => {
            const visibleInGroup = Array.from(group.querySelectorAll('.badr-name-tile'))
                .filter(tile => !tile.hidden).length;
            const groupCount = group.querySelector('[data-group-count]');

            group.hidden = visibleInGroup === 0;
            if (groupCount) groupCount.textContent = `${visibleInGroup} shown`;
        });

        if (showingCount) showingCount.textContent = `Showing ${totalShown} of ${tiles.length}`;
        if (noResults) noResults.hidden = totalShown !== 0;
    };

    const sortTiles = order => {
        groups.forEach(group => {
            const grid = group.querySelector('.badr-tile-grid');
            if (!grid) return;

            Array.from(grid.querySelectorAll('.badr-name-tile'))
                .sort((first, second) => {
                    const comparison = (first.dataset.name || '').localeCompare(
                        second.dataset.name || '',
                        undefined,
                        { sensitivity: 'base' }
                    );
                    return order === 'asc' ? comparison : -comparison;
                })
                .forEach(tile => grid.appendChild(tile));
        });
    };

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            activeFilter = button.dataset.filter || 'all';

            filterButtons.forEach(item => {
                const isActive = item === button;
                item.classList.toggle('active', isActive);
                item.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            updateDirectory();
        });
    });

    search?.addEventListener('input', updateDirectory);

    sortButton?.addEventListener('click', () => {
        sortTiles(sortOrder);
        sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
        sortButton.dataset.order = sortOrder;
        sortButton.textContent = sortOrder === 'asc' ? 'Sort A–Z' : 'Sort Z–A';
    });

    clearButton?.addEventListener('click', () => {
        activeFilter = 'all';
        sortOrder = 'asc';
        if (search) search.value = '';
        sortTiles('asc');

        filterButtons.forEach(button => {
            const isActive = button.dataset.filter === 'all';
            button.classList.toggle('active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        if (sortButton) {
            sortButton.dataset.order = 'asc';
            sortButton.textContent = 'Sort A–Z';
        }

        updateDirectory();
        search?.focus();
    });

    updateDirectory();
});
