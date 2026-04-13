/**
 * OWBN Client — viewer-local time conversion for chronicle session times.
 *
 * Looks for elements with .owc-session-time[data-chrono-tz][data-chrono-time]
 * and appends "(your time: HH:MM AM/PM)" computed in the viewer's local TZ.
 */
(function () {
    function viewerTz() {
        try {
            return Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
        } catch (e) {
            return 'UTC';
        }
    }

    // Convert a wall time in srcTz to viewer's local wall time.
    // Uses today's date in srcTz as the reference (handles DST correctly).
    function convertWallTime(srcTz, time, refDate) {
        if (!srcTz || !time || !/^\d{1,2}:\d{2}$/.test(time)) return null;
        try {
            // Build candidate UTC instant: refDate + time as if UTC
            let candidate = new Date(`${refDate}T${time}:00Z`);
            // Format candidate in srcTz to find the offset
            const srcParts = new Intl.DateTimeFormat('en-CA', {
                timeZone: srcTz, hour: '2-digit', minute: '2-digit', hour12: false
            }).format(candidate).split(':').map(Number);
            const wantMin = parseInt(time.split(':')[0], 10) * 60 + parseInt(time.split(':')[1], 10);
            const gotMin = srcParts[0] * 60 + srcParts[1];
            const deltaMin = wantMin - gotMin;
            candidate = new Date(candidate.getTime() + deltaMin * 60 * 1000);
            return candidate;
        } catch (e) {
            return null;
        }
    }

    function fmtLocal(date, tz, includeDay) {
        const opts = {
            timeZone: tz,
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
            timeZoneName: 'short',
        };
        if (includeDay) opts.weekday = 'short';
        return new Intl.DateTimeFormat(undefined, opts).format(date);
    }

    function todayIso(tz) {
        try {
            const parts = new Intl.DateTimeFormat('en-CA', {
                timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit'
            }).format(new Date());
            return parts; // YYYY-MM-DD
        } catch (e) {
            return new Date().toISOString().slice(0, 10);
        }
    }

    function init() {
        const local = viewerTz();
        const els = document.querySelectorAll('.owc-session-time[data-chrono-tz][data-chrono-time]');
        els.forEach(function (el) {
            const srcTz = el.getAttribute('data-chrono-tz');
            const time = el.getAttribute('data-chrono-time');
            const date = el.getAttribute('data-chrono-date') || todayIso(srcTz);
            if (!srcTz || srcTz === local) return; // skip if same TZ
            const converted = convertWallTime(srcTz, time, date);
            if (!converted) return;
            const includeDay = !!el.getAttribute('data-chrono-day') || !!el.getAttribute('data-chrono-date');
            const local_str = fmtLocal(converted, local, includeDay);
            const span = document.createElement('span');
            span.className = 'owc-session-time-local';
            span.textContent = ' [your time: ' + local_str + ']';
            el.appendChild(span);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
