/**
 * EMC Prayer Times — prayer-times.js
 *
 * Uses real 2026 prayer data from prayer-data.json (exported from MasjidBox athan XLSX).
 * Features:
 *  - Today's widget: adhan + iqamah from real data, live countdown to next prayer
 *  - Hijri date display from real data
 *  - Monthly timetable: prev/next month navigation, today row highlighted
 *  - Friday Jumu'ah time shown in timetable
 *
 * Data shape (per entry):
 *  { date:"DD/MM/YYYY", hijri:"...", adhan:{fajr,sunrise,dhuhr,asr,maghrib,isha,jumuah}, iqamah:{fajr,dhuhr,asr,maghrib,isha,jumuah} }
 */
document.addEventListener('DOMContentLoaded', () => {

    const DAYS   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Parse "HH:MM" (24-hr) into total minutes from midnight.
     * Returns null if string is empty / falsy.
     */
    function parseTime(str) {
        if (!str || str.trim() === '') return null;
        const parts = str.trim().split(':');
        if (parts.length < 2) return null;
        return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
    }

    /**
     * Format minutes-from-midnight into "H:MM AM/PM".
     */
    function fmtTime(mins) {
        if (mins === null || mins === undefined) return '—';
        const h24 = Math.floor(mins / 60) % 24;
        const m   = mins % 60;
        const ampm = h24 < 12 ? 'AM' : 'PM';
        const h12  = h24 % 12 || 12;
        return `${h12}:${String(m).padStart(2, '0')} ${ampm}`;
    }

    /** Build a "DD/MM/YYYY" key from a Date object */
    function dateKey(d) {
        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        return `${dd}/${mm}/${d.getFullYear()}`;
    }

    /* ------------------------------------------------------------------ */
    /*  Load JSON data                                                      */
    /* ------------------------------------------------------------------ */

    // emcPrayer.dataUrl is injected by wp_localize_script in template-prayer-times.php
    const dataUrl = (typeof emcPrayer !== 'undefined' && emcPrayer.dataUrl)
        ? emcPrayer.dataUrl
        : '/wp-content/themes/emc-theme/assets/js/prayer-data.json'; // fallback

    fetch(dataUrl)
        .then(r => r.json())
        .then(rawData => {
            // Build a map: "DD/MM/YYYY" -> entry
            const dataMap = {};
            rawData.forEach(entry => { dataMap[entry.date] = entry; });

            initTodayWidget(dataMap);
            initMonthlyTimetable(dataMap);
            initScrollReveal();
        })
        .catch(err => {
            console.warn('[EMC] Could not load prayer-data.json:', err);
            // Graceful fallback: hide timetable loading state
            initScrollReveal();
        });

    /* ------------------------------------------------------------------ */
    /*  Today's Widget                                                      */
    /* ------------------------------------------------------------------ */

    function initTodayWidget(dataMap) {
        const today    = new Date();
        const key      = dateKey(today);
        const entry    = dataMap[key];

        if (!entry) return; // No data for today (e.g. next year)

        const adhan  = entry.adhan;
        const iqamah = entry.iqamah;

        // ── Hijri date ────────────────────────────────────────────────
        const hijriEl = document.getElementById('hijri-today');
        if (hijriEl && entry.hijri) {
            // Remove Arabic weekday prefix if present (keep date part only for cleanliness)
            // Format from data: "الجمعة 13 رجب 1447" — show as is
            hijriEl.textContent = entry.hijri;
        }

        // ── Gregorian date ────────────────────────────────────────────
        const gregEl = document.getElementById('gregorian-today');
        if (gregEl) {
            gregEl.textContent = today.toLocaleDateString('en-GB', {
                day: 'numeric', month: 'long', year: 'numeric'
            });
        }

        // ── Prayer rows in the widget ──────────────────────────────────
        // Ordered prayer list used by the widget (matches template PHP order)
        const WIDGET_PRAYERS = [
            { key: 'fajr',    label: 'Fajr',    hasIqamah: true  },
            { key: 'sunrise', label: 'Sunrise',  hasIqamah: false },
            { key: 'dhuhr',   label: 'Dhuhr',   hasIqamah: true  },
            { key: 'asr',     label: 'Asr',     hasIqamah: true  },
            { key: 'maghrib', label: 'Maghrib',  hasIqamah: true  },
            { key: 'isha',    label: 'Isha',    hasIqamah: true  },
        ];

        // Build a time-sorted list of adhan times for countdown
        const prayerTimesMinutes = [];
        WIDGET_PRAYERS.forEach(p => {
            const mins = parseTime(adhan[p.key]);
            if (mins !== null) {
                prayerTimesMinutes.push({ label: p.label, mins });
            }
        });

        // Update pt-row elements already in the DOM (set by PHP)
        const ptRows = document.querySelectorAll('.pt-row');
        ptRows.forEach(row => {
            const name = row.getAttribute('data-prayer');
            const prayer = WIDGET_PRAYERS.find(p => p.label === name);
            if (!prayer) return;

            const adhanTime  = adhan[prayer.key]  ? fmtTime(parseTime(adhan[prayer.key]))  : '—';
            const iqamaTime  = prayer.hasIqamah && iqamah[prayer.key] ? fmtTime(parseTime(iqamah[prayer.key])) : '—';

            const adhanEl = row.querySelector('.pt-adhan');
            const iqamaEl = row.querySelector('.pt-iqama');
            if (adhanEl) adhanEl.textContent = adhanTime;
            if (iqamaEl) iqamaEl.textContent = iqamaTime;
        });

        // ── Live Countdown to next prayer ──────────────────────────────
        const countdownEl     = document.getElementById('live-countdown');
        const nextPrayerNameEl = document.getElementById('next-prayer-name');

        function updateCountdown() {
            const now     = new Date();
            const nowMins = now.getHours() * 60 + now.getMinutes();
            const nowSecs = now.getSeconds();

            // Find next prayer (first one that is still in the future)
            let next = prayerTimesMinutes.find(p => p.mins > nowMins);
            if (!next) {
                // All passed today — next is Fajr tomorrow (data not available, show Fajr label)
                next = prayerTimesMinutes[0];
            }

            if (nextPrayerNameEl) nextPrayerNameEl.textContent = next.label;

            let diffMins = next.mins - nowMins;
            if (diffMins < 0) diffMins += 24 * 60; // next day
            let diffSecs = diffMins * 60 - nowSecs;
            if (diffSecs < 0) diffSecs = 0;

            const hh = Math.floor(diffSecs / 3600);
            const mm = Math.floor((diffSecs % 3600) / 60);
            const ss = diffSecs % 60;
            const pad = n => String(n).padStart(2, '0');

            if (countdownEl) countdownEl.textContent = `${pad(hh)}:${pad(mm)}:${pad(ss)}`;

            // Highlight active / next prayer row
            const ptRows2 = document.querySelectorAll('.pt-row');
            ptRows2.forEach(row => {
                const name = row.getAttribute('data-prayer');
                const isNext = (name === next.label);
                row.classList.toggle('active', isNext);
                const badge = row.querySelector('.next-badge');
                if (badge) badge.style.display = isNext ? '' : 'none';
            });
        }

        setInterval(updateCountdown, 1000);
        updateCountdown();
    }

    /* ------------------------------------------------------------------ */
    /*  Monthly Timetable                                                   */
    /* ------------------------------------------------------------------ */

    function initMonthlyTimetable(dataMap) {
        const today = new Date();
        let currentYear  = today.getFullYear();
        let currentMonth = today.getMonth(); // 0-indexed
        const downloadBtn = document.getElementById('download-timetable-pdf');

        // Update table headers to include Iqamah columns
        const thead = document.querySelector('#timetable thead tr');
        if (thead) {
            thead.innerHTML = `
                <th>Date</th>
                <th>Day</th>
                <th><i class="fas fa-sun"></i> Fajr</th>
                <th class="iqamah-col">Iqamah</th>
                <th><i class="fas fa-cloud-sun"></i> Sunrise</th>
                <th><i class="fas fa-sun"></i> Dhuhr</th>
                <th class="iqamah-col">Iqamah</th>
                <th><i class="fas fa-sun"></i> Asr</th>
                <th class="iqamah-col">Iqamah</th>
                <th><i class="fas fa-moon"></i> Maghrib</th>
                <th><i class="fas fa-moon"></i> Isha</th>
                <th class="iqamah-col">Iqamah</th>
                <th><i class="fas fa-star-and-crescent"></i> Jumu'ah</th>
            `;
        }

        function renderTimetable(year, month) {
            const body      = document.getElementById('timetable-body');
            const titleEl   = document.querySelector('.month-current');
            const headingEl = document.getElementById('timetable-heading');
            const prevBtn   = document.getElementById('prev-month');
            const nextBtn   = document.getElementById('next-month');
            if (!body) return;

            // Nav labels
            if (titleEl)  titleEl.textContent = `${MONTHS[month]} ${year}`;
            if (headingEl) headingEl.textContent = `${MONTHS[month]} ${year} Timetable`;
            if (prevBtn)  prevBtn.innerHTML = `<i class="fas fa-chevron-left"></i> ${MONTHS[(month - 1 + 12) % 12]}`;
            if (nextBtn)  nextBtn.innerHTML = `${MONTHS[(month + 1) % 12]} <i class="fas fa-chevron-right"></i>`;

            const daysInMonth    = new Date(year, month + 1, 0).getDate();
            const todayDate      = new Date();
            const isCurrentMonth = todayDate.getFullYear() === year && todayDate.getMonth() === month;

            let html = '';
            let hasData = false;
            for (let d = 1; d <= daysInMonth; d++) {
                const dateObj  = new Date(year, month, d);
                const dayName  = DAYS[dateObj.getDay()];
                const isFriday = dateObj.getDay() === 5;
                const isToday  = isCurrentMonth && todayDate.getDate() === d;

                const dd  = String(d).padStart(2, '0');
                const mm  = String(month + 1).padStart(2, '0');
                const key = `${dd}/${mm}/${year}`;
                const entry = dataMap[key];
                if (entry) hasData = true;

                const a = entry ? entry.adhan  : {};
                const q = entry ? entry.iqamah : {};

                const fmt = (obj, k) => {
                    const v = parseTime(obj[k]);
                    return v !== null ? fmtTime(v) : '—';
                };

                // Jumu'ah: show adhan time (Friday only)
                const jumuahCell = isFriday
                    ? `<td class="jumuah-col">${fmt(a, 'jumuah')}</td>`
                    : `<td class="jumuah-col">—</td>`;

                html += `
                    <tr class="${isToday ? 'is-today' : ''} ${isFriday ? 'is-friday' : ''}">
                        <td>${d}${isToday ? '<span class="today-tag">Today</span>' : ''}</td>
                        <td class="day-col">${dayName}</td>
                        <td>${fmt(a, 'fajr')}</td>
                        <td class="iqamah-col dimmed">${fmt(q, 'fajr')}</td>
                        <td>${fmt(a, 'sunrise')}</td>
                        <td>${fmt(a, 'dhuhr')}</td>
                        <td class="iqamah-col dimmed">${fmt(q, 'dhuhr')}</td>
                        <td>${fmt(a, 'asr')}</td>
                        <td class="iqamah-col dimmed">${fmt(q, 'asr')}</td>
                        <td>${fmt(a, 'maghrib')}</td>
                        <td>${fmt(a, 'isha')}</td>
                        <td class="iqamah-col dimmed">${fmt(q, 'isha')}</td>
                        ${jumuahCell}
                    </tr>`;
            }

            body.innerHTML = html || '<tr><td colspan="13" style="text-align:center;padding:2rem;color:#999;">No data available for this month.</td></tr>';

            if (downloadBtn) {
                downloadBtn.disabled = !hasData;
                downloadBtn.title = hasData
                    ? `Download ${MONTHS[month]} ${year} timetable as PDF`
                    : 'No timetable data is available for this month';
            }
        }

        renderTimetable(currentYear, currentMonth);

        document.getElementById('prev-month')?.addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 0) { currentMonth = 11; currentYear--; }
            renderTimetable(currentYear, currentMonth);
        });

        document.getElementById('next-month')?.addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 11) { currentMonth = 0; currentYear++; }
            renderTimetable(currentYear, currentMonth);
        });

        downloadBtn?.addEventListener('click', () => {
            if (!window.EMCPrayerPDF || downloadBtn.disabled) return;

            window.EMCPrayerPDF.downloadMonth(dataMap, currentYear, currentMonth, {
                siteName: (typeof emcPrayer !== 'undefined' && emcPrayer.siteName)
                    ? emcPrayer.siteName
                    : 'Essex Muslim Centre',
                location: (typeof emcPrayer !== 'undefined' && emcPrayer.location)
                    ? emcPrayer.location
                    : 'Chelmsford, Essex'
            });
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Scroll Reveal                                                       */
    /* ------------------------------------------------------------------ */

    function initScrollReveal() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('reveal');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));
    }
});
