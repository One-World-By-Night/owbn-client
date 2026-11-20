/**
 * Connect a character to your account: find it by name, then email the link to the address on its sheet.
 *
 * Reads window.oatClaimConfig: ajaxUrl, nonce, i18n (tooShort, searching, none, send, sending, sent, failed).
 */
(function () {
    'use strict';
    var config = window.oatClaimConfig;
    var root = document.querySelector('.oat-claim');
    if (!config || !root) return;

    var term = root.querySelector('.oat-claim-term');
    var findBtn = root.querySelector('.oat-claim-search');
    var list = root.querySelector('.oat-claim-results');
    var message = root.querySelector('.oat-claim-message');
    var i18n = config.i18n || {};

    function say(text, kind) {
        message.textContent = text || '';
        message.className = 'oat-claim-message' + (kind ? ' oat-claim-' + kind : '');
    }

    function post(action, data, done) {
        var fd = new FormData();
        fd.append('action', action);
        fd.append('nonce', config.nonce);
        Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
        fetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (r) { done(r && r.success ? { ok: true, data: r.data } : { ok: false, error: r && typeof r.data === 'string' ? r.data : i18n.failed }); })
            .catch(function () { done({ ok: false, error: i18n.failed }); });
    }

    function row(c) {
        var li = document.createElement('li');
        var label = document.createElement('span');
        var strong = document.createElement('strong');
        strong.textContent = c.name;
        label.appendChild(strong);
        var extra = [c.chronicle, c.status].filter(Boolean).join(', ');
        if (extra) label.appendChild(document.createTextNode(' (' + extra + ')'));
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'button oat-claim-send';
        btn.textContent = i18n.send;
        btn.addEventListener('click', function () {
            btn.disabled = true;
            btn.textContent = i18n.sending;
            say('', '');
            post('owc_oat_claim_request', { character_id: c.id }, function (r) {
                if (r.ok) {
                    list.innerHTML = '';
                    say(i18n.sent, 'ok');
                } else {
                    btn.disabled = false;
                    btn.textContent = i18n.send;
                    say(r.error, 'error');
                }
            });
        });
        li.appendChild(label);
        li.appendChild(btn);
        return li;
    }

    function find() {
        var q = term.value.trim();
        list.innerHTML = '';
        if (q.length < 3) {
            say(i18n.tooShort, 'error');
            return;
        }
        say(i18n.searching, '');
        post('owc_oat_claim_search', { term: q }, function (r) {
            if (!r.ok) {
                say(r.error, 'error');
                return;
            }
            if (!r.data.length) {
                say(i18n.none, 'error');
                return;
            }
            say('', '');
            r.data.forEach(function (c) { list.appendChild(row(c)); });
        });
    }

    findBtn.addEventListener('click', find);
    term.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            find();
        }
    });
})();
