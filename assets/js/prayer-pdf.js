/**
 * EMC monthly prayer timetable PDF generator.
 *
 * Produces a dependency-free, landscape A4 PDF from the same data used by the
 * on-screen timetable.
 */
(function () {
    'use strict';

    const MONTHS = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    function byteLength(value) {
        return new TextEncoder().encode(value).length;
    }

    function ascii(value) {
        return String(value || '')
            .normalize('NFKD')
            .replace(/[^\x20-\x7E]/g, '-');
    }

    function pdfEscape(value) {
        return ascii(value)
            .replace(/\\/g, '\\\\')
            .replace(/\(/g, '\\(')
            .replace(/\)/g, '\\)');
    }

    function compactTime(value) {
        if (!value || !/^\d{1,2}:\d{2}/.test(value)) return '-';

        const parts = value.split(':');
        const hour = parseInt(parts[0], 10);
        const minute = parseInt(parts[1], 10);
        if (Number.isNaN(hour) || Number.isNaN(minute)) return '-';

        const suffix = hour < 12 ? 'AM' : 'PM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${String(minute).padStart(2, '0')} ${suffix}`;
    }

    function buildRows(dataMap, year, month) {
        const rows = [];
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const key = `${String(day).padStart(2, '0')}/${String(month + 1).padStart(2, '0')}/${year}`;
            const entry = dataMap[key] || {};
            const adhan = entry.adhan || {};
            const iqamah = entry.iqamah || {};
            const isFriday = date.getDay() === 5;

            rows.push({
                isFriday,
                cells: [
                    String(day),
                    DAYS[date.getDay()],
                    compactTime(adhan.fajr),
                    compactTime(iqamah.fajr),
                    compactTime(adhan.sunrise),
                    compactTime(adhan.dhuhr),
                    compactTime(iqamah.dhuhr),
                    compactTime(adhan.asr),
                    compactTime(iqamah.asr),
                    compactTime(adhan.maghrib),
                    compactTime(adhan.isha),
                    compactTime(iqamah.isha),
                    isFriday ? compactTime(adhan.jumuah) : '-'
                ]
            });
        }

        return rows;
    }

    function makePdf(dataMap, year, month, options) {
        const pageWidth = 842;
        const pageHeight = 595;
        const tableX = 24;
        const tableTop = 518;
        const headerHeight = 21;
        const rowHeight = 14;
        const widths = [34, 36, 68, 60, 68, 68, 60, 68, 60, 68, 68, 60, 76];
        const headers = [
            'Date', 'Day', 'Fajr', 'Iqamah', 'Sunrise', 'Dhuhr', 'Iqamah',
            'Asr', 'Iqamah', 'Maghrib', 'Isha', 'Iqamah', "Jumu'ah"
        ];
        const rows = buildRows(dataMap, year, month);
        const commands = [];

        function text(x, y, size, value, bold, colour) {
            const font = bold ? 'F2' : 'F1';
            commands.push(
                'BT',
                `${colour || '0.08 0.15 0.20'} rg`,
                `/${font} ${size} Tf`,
                `1 0 0 1 ${x.toFixed(2)} ${y.toFixed(2)} Tm`,
                `(${pdfEscape(value)}) Tj`,
                'ET'
            );
        }

        function centredText(x, width, y, size, value, bold, colour) {
            const estimatedWidth = ascii(value).length * size * 0.27;
            text(x + Math.max(3, (width - estimatedWidth) / 2), y, size, value, bold, colour);
        }

        function fillRect(x, y, width, height, colour) {
            commands.push(`${colour} rg`, `${x} ${y} ${width} ${height} re f`);
        }

        function line(x1, y1, x2, y2, colour, width) {
            commands.push(
                `${colour || '0.82 0.87 0.88'} RG`,
                `${width || 0.35} w`,
                `${x1} ${y1} m ${x2} ${y2} l S`
            );
        }

        const title = `${MONTHS[month]} ${year} Prayer Timetable`;
        const centreName = options.siteName || 'Essex Muslim Centre';
        const location = options.location || 'Chelmsford, Essex';

        text(24, 558, 20, centreName, true, '0.08 0.15 0.20');
        text(24, 540, 11, title, true, '0.16 0.63 0.58');
        text(pageWidth - 285, 558, 8, location, false, '0.35 0.43 0.48');
        text(pageWidth - 285, 543, 7, 'Adhan and Iqamah times - local UK time', false, '0.35 0.43 0.48');

        const headerBottom = tableTop - headerHeight;
        fillRect(tableX, headerBottom, 794, headerHeight, '0.16 0.63 0.58');

        let columnX = tableX;
        headers.forEach((header, index) => {
            centredText(columnX, widths[index], headerBottom + 7.5, 6.4, header, true, '1 1 1');
            columnX += widths[index];
        });

        rows.forEach((row, rowIndex) => {
            const bottom = headerBottom - ((rowIndex + 1) * rowHeight);
            if (row.isFriday) {
                fillRect(tableX, bottom, 794, rowHeight, '0.99 0.96 0.87');
            } else if (rowIndex % 2 === 1) {
                fillRect(tableX, bottom, 794, rowHeight, '0.97 0.98 0.98');
            }

            let cellX = tableX;
            row.cells.forEach((cell, index) => {
                const colour = row.isFriday && (index === 1 || index === 12)
                    ? '0.68 0.45 0.18'
                    : '0.12 0.20 0.25';
                centredText(cellX, widths[index], bottom + 4.6, 6.15, cell, row.isFriday && index === 1, colour);
                cellX += widths[index];
            });

            line(tableX, bottom, tableX + 794, bottom);
        });

        const tableBottom = headerBottom - (rows.length * rowHeight);
        columnX = tableX;
        line(tableX, tableTop, tableX + 794, tableTop, '0.16 0.63 0.58', 0.7);
        for (let index = 0; index <= widths.length; index++) {
            line(columnX, tableBottom, columnX, tableTop);
            if (index < widths.length) columnX += widths[index];
        }

        text(24, 36, 7, 'Please check the website regularly for timetable updates and special announcements.', false, '0.35 0.43 0.48');
        text(24, 23, 7, `Generated ${new Date().toLocaleDateString('en-GB')} | ${centreName}`, false, '0.35 0.43 0.48');

        const stream = `${commands.join('\n')}\n`;
        const objects = [
            '',
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
            `<< /Length ${byteLength(stream)} >>\nstream\n${stream}endstream`
        ];

        let pdf = '%PDF-1.4\n% EMC Prayer Timetable\n';
        const offsets = [0];

        for (let index = 1; index < objects.length; index++) {
            offsets[index] = byteLength(pdf);
            pdf += `${index} 0 obj\n${objects[index]}\nendobj\n`;
        }

        const xrefOffset = byteLength(pdf);
        pdf += `xref\n0 ${objects.length}\n`;
        pdf += '0000000000 65535 f \n';
        for (let index = 1; index < objects.length; index++) {
            pdf += `${String(offsets[index]).padStart(10, '0')} 00000 n \n`;
        }
        pdf += `trailer\n<< /Size ${objects.length} /Root 1 0 R >>\n`;
        pdf += `startxref\n${xrefOffset}\n%%EOF`;

        return new Blob([pdf], { type: 'application/pdf' });
    }

    function downloadMonth(dataMap, year, month, options) {
        const pdf = makePdf(dataMap, year, month, options || {});
        const url = URL.createObjectURL(pdf);
        const link = document.createElement('a');
        const monthName = MONTHS[month].toLowerCase();

        link.href = url;
        link.download = `essex-muslim-centre-prayer-timetable-${monthName}-${year}.pdf`;
        document.body.appendChild(link);
        link.click();
        link.remove();

        window.setTimeout(() => URL.revokeObjectURL(url), 1500);
    }

    window.EMCPrayerPDF = { downloadMonth };
}());
