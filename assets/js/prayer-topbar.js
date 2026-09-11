/**
 * EMC Prayer Times Top Bar
 * Populates the fixed top bar with today's date and prayer times.
 */
document.addEventListener('DOMContentLoaded', () => {
    const PRAYERS = ['fajr', 'dhuhr', 'asr', 'maghrib', 'isha'];
    const HIJRI_MONTHS = ['Muharram', 'Safar', 'Rabi Al-Awwal', 'Rabi Al-Thani', 'Jumada Al-Awwal', 'Jumada Al-Thani', 'Rajab', 'Sha\'ban', 'Ramadan', 'Shawwal', 'Dhul-Qa\'dah', 'Dhul-Hijjah'];
    const SITE_TIME_ZONE = (typeof emcPrayer !== 'undefined' && emcPrayer.timezone)
        ? emcPrayer.timezone
        : Intl.DateTimeFormat().resolvedOptions().timeZone;

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

    function zonedParts(d) {
        const parts = new Intl.DateTimeFormat('en-GB', {
            timeZone: SITE_TIME_ZONE,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hourCycle: 'h23',
        }).formatToParts(d);

        const get = (type) => parts.find(part => part.type === type)?.value || '';
        return {
            year: parseInt(get('year'), 10),
            month: parseInt(get('month'), 10),
            day: parseInt(get('day'), 10),
            hour: parseInt(get('hour'), 10),
            minute: parseInt(get('minute'), 10),
            second: parseInt(get('second'), 10),
        };
    }

    function siteDateKey(d) {
        const parts = zonedParts(d);
        const dd = String(parts.day).padStart(2, '0');
        const mm = String(parts.month).padStart(2, '0');
        return `${dd}/${mm}/${parts.year}`;
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
        const parts = zonedParts(d);
        const month = new Intl.DateTimeFormat('en-GB', { timeZone: SITE_TIME_ZONE, month: 'long' }).format(d);
        return `${ordinal(parts.day)} ${month} ${parts.year}`;
    }

    function formatHijri(d) {
        try {
            const parts = new Intl.DateTimeFormat('en-GB-u-ca-islamic-umalqura', {
                timeZone: SITE_TIME_ZONE,
                day: 'numeric',
                month: 'numeric',
                year: 'numeric',
            }).formatToParts(d);
            const day = parts.find(part => part.type === 'day')?.value || '';
            const monthNumber = parseInt(parts.find(part => part.type === 'month')?.value || '', 10);
            const month = HIJRI_MONTHS[monthNumber - 1] || '';
            const year = parts.find(part => part.type === 'year')?.value || '';
            return `${day} ${month} ${year}`.trim();
        } catch (_) {
            return '';
        }
    }

    function updateDisplayedDates(d) {
        const gregorianEl = document.getElementById('ptb-gregorian');
        if (gregorianEl) {
            gregorianEl.textContent = formatGregorian(d);
        }

        const hijriEl = document.getElementById('ptb-hijri');
        if (hijriEl) {
            hijriEl.textContent = formatHijri(d);
        }
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
                const entry = dataMap[siteDateKey(today)];

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
                    const nowParts = zonedParts(new Date());
                    const nowMins = nowParts.hour * 60 + nowParts.minute;
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

            renderTopBar();
            setInterval(renderTopBar, 60000);
        })
        .catch(err => {
            updateDisplayedDates(new Date());
            setInterval(() => updateDisplayedDates(new Date()), 60000);
            console.warn('[EMC TopBar] Could not load prayer data:', err);
        });
});
