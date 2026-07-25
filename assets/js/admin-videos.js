document.addEventListener('DOMContentLoaded', () => {
    const selectButton = document.getElementById('emc-select-video');
    const urlInput = document.getElementById('emc_video_url');

    if (!selectButton || !urlInput || !window.wp?.media) return;

    selectButton.addEventListener('click', () => {
        const frame = window.wp.media({
            title: 'Select or upload a video',
            button: { text: 'Use this video' },
            library: { type: 'video' },
            multiple: false
        });

        frame.on('select', () => {
            const attachment = frame.state().get('selection').first()?.toJSON();
            if (attachment?.url) {
                urlInput.value = attachment.url;
                urlInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        frame.open();
    });
});
