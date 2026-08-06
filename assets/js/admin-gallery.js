document.addEventListener('DOMContentLoaded', () => {
    const selectButton = document.getElementById('emc-gallery-select-images');
    const clearButton = document.getElementById('emc-gallery-clear-images');
    const importButton = document.getElementById('emc-gallery-import');
    const category = document.getElementById('emc-gallery-category');
    const idInput = document.getElementById('emc-gallery-attachment-ids');
    const previewGrid = document.getElementById('emc-gallery-preview-grid');
    const emptyState = document.getElementById('emc-gallery-empty');
    const count = document.getElementById('emc-gallery-selection-count');

    if (!selectButton || !window.wp?.media) return;

    const images = new Map();
    let frame;

    const updateState = () => {
        const ids = Array.from(images.keys());
        idInput.value = ids.join(',');
        count.textContent = `${ids.length} selected`;
        emptyState.hidden = ids.length > 0;
        clearButton.hidden = ids.length === 0;
        importButton.disabled = ids.length === 0 || !category.value;
    };

    const renderPreviews = () => {
        previewGrid.replaceChildren();

        images.forEach((attachment, id) => {
            const item = document.createElement('li');
            const image = document.createElement('img');
            const details = document.createElement('div');
            const title = document.createElement('strong');
            const remove = document.createElement('button');
            const sizes = attachment.sizes || {};

            image.src = sizes.thumbnail?.url || sizes.medium?.url || attachment.url;
            image.alt = attachment.alt || attachment.title || '';
            title.textContent = attachment.title || attachment.filename || 'Gallery image';
            remove.type = 'button';
            remove.className = 'button-link-delete';
            remove.textContent = 'Remove';
            remove.addEventListener('click', () => {
                images.delete(id);
                renderPreviews();
            });

            details.append(title, remove);
            item.append(image, details);
            previewGrid.append(item);
        });

        updateState();
    };

    selectButton.addEventListener('click', () => {
        if (!frame) {
            frame = window.wp.media({
                title: 'Select or upload gallery images',
                button: { text: 'Use selected images' },
                library: { type: 'image' },
                multiple: true,
            });

            frame.on('select', () => {
                frame.state().get('selection').toJSON().forEach(attachment => {
                    images.set(String(attachment.id), attachment);
                });
                renderPreviews();
            });
        }

        frame.open();
    });

    clearButton.addEventListener('click', () => {
        images.clear();
        frame?.state().get('selection').reset();
        renderPreviews();
    });

    category.addEventListener('change', updateState);
    updateState();
});
