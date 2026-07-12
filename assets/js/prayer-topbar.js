/**
 * EMC Prayer Times Top Bar
 * Populates the fixed top bar with today's date and prayer times.
 */
document.addEventListener('DOMContentLoaded', () => {
    const PRAYERS = ['fajr', 'dhuhr', 'asr', 'maghrib', 'isha'];

    function parseTime(str) {
        if (!str || !str.trim()) return null;
        const parts = str.trim().split(':');
        if (parts.length < 2) return null;
        return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
    }

    function fmt24(str) {
        if (!str || !str.trim()) return '--:--';
        return str.trim().substring(0, 5);
    }

    function dateKey(d) {
        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        return `${dd}/${mm}/${d.getFullYear()}`;
    }

    function ordinal(n) {
        const mod100 = n % 100;
        if (mod100 >= 11 && mod100 <= 13) return `${n}th`;

        switch (n % 10) {
            case 1: return `${n}st`;
            case 2: return `${n}nd`;
            case 3: return `${n}rd`;
            default: return `${n}th`;
        }
    }

    function formatGregorian(d) {
        const month = new Intl.DateTimeFormat('en-GB', { month: 'long' }).format(d);
        return `${ordinal(d.getDate())} ${month} ${d.getFullYear()}`;
    }

    function updateDisplayedDates(d) {
        const gregorianEl = document.getElementById('ptb-gregorian');
        if (gregorianEl) {
            gregorianEl.textContent = formatGregorian(d);
        }

        const hijriEl = document.getElementById('ptb-hijri');
        if (!hijriEl) return;

        try {
            const parts = new Intl.DateTimeFormat('en-GB-u-ca-islamic', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            }).formatToParts(d);
            const day = parts.find(part => part.type === 'day')?.value || '';
            const month = parts.find(part => part.type === 'month')?.value || '';
            const year = parts.find(part => part.type === 'year')?.value || '';
            hijriEl.textContent = `${day} ${month} ${year}`.trim();
        } catch (_) {
            hijriEl.textContent = '';
        }
    }

    function msUntilNextLocalDay() {
        const now = new Date();
        const next = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0, 2);
        return next.getTime() - now.getTime();
    }

    function clearPrayerColumns() {
        PRAYERS.forEach(prayer => {
            const adhanEl = document.getElementById(`ptb-adhan-${prayer}`);
            const iqamahEl = document.getElementById(`ptb-iqamah-${prayer}`);
            if (adhanEl) adhanEl.textContent = '--:--';
            if (iqamahEl) iqamahEl.textContent = '--:--';
        });
    }

    const dataUrl = (typeof emcPrayer !== 'undefined' && emcPrayer.dataUrl)
        ? emcPrayer.dataUrl
        : '/wp-content/themes/emc-theme/assets/js/prayer-data.json';

    updateDisplayedDates(new Date());

    fetch(dataUrl)
        .then(r => r.json())
        .then(rawData => {
            const dataMap = {};
            rawData.forEach(entry => {
                dataMap[entry.date] = entry;
            });

            let activeHighlightTimer = null;

            function renderTopBar() {
                const today = new Date();
                const entry = dataMap[dateKey(today)];

                updateDisplayedDates(today);

                if (!entry) {
                    if (activeHighlightTimer) {
                        clearInterval(activeHighlightTimer);
                        activeHighlightTimer = null;
                    }
                    clearPrayerColumns();
                    return;
                }

                const adhan = entry.adhan || {};
                const iqamah = entry.iqamah || {};

                const jumuahEl = document.getElementById('ptb-jumuah');
                if (jumuahEl) {
                    const jTime = fmt24(adhan.jumuah);
                    jumuahEl.textContent = jTime !== '--:--' ? jTime : '13:15';
                }

                PRAYERS.forEach(prayer => {
                    const adhanEl = document.getElementById(`ptb-adhan-${prayer}`);
                    const iqamahEl = document.getElementById(`ptb-iqamah-${prayer}`);
                    if (adhanEl) adhanEl.textContent = fmt24(adhan[prayer]);
                    if (iqamahEl) iqamahEl.textContent = fmt24(iqamah[prayer]);
                });

                function highlightActive() {
                    const now = new Date();
                    const nowMins = now.getHours() * 60 + now.getMinutes();
                    let nextKey = null;
                    let minDiff = Infinity;

                    PRAYERS.forEach(prayer => {
                        const mins = parseTime(adhan[prayer]);
                        if (mins === null) return;
                        const diff = mins - nowMins;
                        if (diff >= 0 && diff < minDiff) {
                            minDiff = diff;
                            nextKey = prayer;
                        }
                    });

                    if (!nextKey) nextKey = 'isha';

                    document.querySelectorAll('.ptb-prayer-col').forEach(col => {
                        col.classList.toggle('ptb-active', col.getAttribute('data-prayer') === nextKey);
                    });
                }

                if (activeHighlightTimer) {
                    clearInterval(activeHighlightTimer);
                }
                highlightActive();
                activeHighlightTimer = setInterval(highlightActive, 60000);
            }

            function scheduleDateSync() {
                setTimeout(() => {
                    renderTopBar();
                    scheduleDateSync();
                }, msUntilNextLocalDay());
            }

            renderTopBar();
            scheduleDateSync();
        })
        .catch(err => {
            updateDisplayedDates(new Date());
            setTimeout(() => updateDisplayedDates(new Date()), msUntilNextLocalDay());
            console.warn('[EMC TopBar] Could not load prayer data:', err);
        });
});
