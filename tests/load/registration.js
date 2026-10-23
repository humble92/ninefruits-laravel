// k6 load test: the registration peak (docs/manual/ko/operations/load-testing.md).
//
//   docker run --rm -i --network ninefruits-laravel_sail -v "$PWD":/work -w /work grafana/k6 run \
//       --vus 100 --duration 1m -e BASE_URL=http://laravel.test -e ACCOUNTS=/work/storage/app/load-accounts.csv tests/load/registration.js
//
// ACCOUNTS: CSV with email,password from `setup:demo --student-accounts`. Each virtual user
// signs in as one student, opens the registration page, and drives the Livewire component the
// way the browser does: enroll in the first open section, then drop it, so the data returns to
// its starting state. A refusal (full section, rule) counts as handled, not as an error.

import http from 'k6/http';
import { check, sleep } from 'k6';
import { SharedArray } from 'k6/data';
import papaparse from 'https://jslib.k6.io/papaparse/5.1.1/index.js';

const base = __ENV.BASE_URL || 'http://laravel.test';
const accounts = new SharedArray('accounts', () => papaparse.parse(open(__ENV.ACCOUNTS || '../../storage/app/load-accounts.csv'), { header: true }).data.filter((r) => r.email));

export const options = {
    thresholds: {
        http_req_failed: ['rate<0.01'],
        'http_req_duration{name:registration_page}': ['p(95)<800'],
        'http_req_duration{name:enroll}': ['p(95)<1500'],
    },
};

const unescape = (s) => s.replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
const formToken = (html) => (html.match(/name="_token" value="([^"]+)"/) || [])[1];
const csrf = (html) => (html.match(/data-csrf="([^"]+)"/) || [])[1];
const snapshot = (html) => { const m = html.match(/wire:snapshot="([^"]+)"/); return m ? unescape(m[1]) : null; };

function livewireCall(token, snap, method, params, name) {
    const payload = JSON.stringify({ _token: token, components: [{ snapshot: snap, updates: {}, calls: [{ path: '', method, params }] }] });
    const res = http.post(`${base}/livewire/update`, payload, { headers: { 'Content-Type': 'application/json', 'X-Livewire': '1', 'X-Requested-With': 'XMLHttpRequest' }, tags: { name } });
    let next = null;
    let html = '';
    try {
        const body = JSON.parse(res.body);
        next = body.components[0].snapshot;
        html = body.components[0].effects.html || '';
    } catch (e) { /* refused or error response */ }
    return { res, next, html };
}

export default function () {
    const account = accounts[(__VU - 1) % accounts.length];

    const login = http.get(`${base}/login`, { tags: { name: 'login_page' } });
    const auth = http.post(`${base}/login`, { _token: formToken(login.body), email: account.email, password: account.password }, { redirects: 0, tags: { name: 'login' } });
    check(auth, { 'signed in': (r) => r.status === 302 });

    const page = http.get(`${base}/portal/registration`, { tags: { name: 'registration_page' } });
    check(page, { 'registration page': (r) => r.status === 200 });

    const token = csrf(page.body);
    let snap = snapshot(page.body);
    const section = (page.body.match(/wire:click="enroll\((\d+)\)"/) || [])[1];

    if (token && snap && section) {
        const enrolled = livewireCall(token, snap, 'enroll', [Number(section)], 'enroll');
        check(enrolled.res, { 'enroll handled': (r) => r.status === 200 });
        snap = enrolled.next || snap;

        const enrollment = (enrolled.html.match(/wire:click="drop\((\d+)\)"/) || [])[1];
        if (enrollment) {
            const dropped = livewireCall(token, snap, 'drop', [Number(enrollment)], 'drop');
            check(dropped.res, { 'drop handled': (r) => r.status === 200 });
        }
    }

    http.post(`${base}/logout`, { _token: token || formToken(page.body) }, { redirects: 0, tags: { name: 'logout' } });
    http.cookieJar().clear(base);
    sleep(1);
}
