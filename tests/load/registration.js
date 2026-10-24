// k6 load test: the registration peak (docs/manual/ko/operations/load-testing.md).
//
//   docker run --rm -i --network ninefruits-laravel_sail -v "$PWD":/work -w /work grafana/k6 run \
//       --vus 100 --duration 1m -e BASE_URL=http://laravel.test -e ACCOUNTS=/work/storage/app/private/load-accounts.csv tests/load/registration.js
//
// ACCOUNTS: CSV with email,password from `setup:demo --student-accounts`. Every iteration signs in as
// the next student (fresh cookie jar), opens the registration page, searches the catalog the way the
// browser does (Livewire update), registers for the first section found and drops it again so the
// data returns to its starting state. A refusal (full section, rule, closed window) is a handled
// outcome, not an error; only HTTP failures and slow pages fail the thresholds.
//
// SEARCH: catalog search text (default "GEN", the demo's general-education course codes; a code prefix or a title fragment). Needs an open registration
// window for the students in ACCOUNTS.

import http from 'k6/http';
import { check, sleep } from 'k6';
import { SharedArray } from 'k6/data';
import { Counter } from 'k6/metrics';
import papaparse from 'https://jslib.k6.io/papaparse/5.1.1/index.js';

const base = __ENV.BASE_URL || 'http://laravel.test';
const searchText = __ENV.SEARCH || 'GEN';
const accounts = new SharedArray('accounts', () => papaparse.parse(open(__ENV.ACCOUNTS || '../../storage/app/private/load-accounts.csv'), { header: true }).data.filter((r) => r.email));

const enrolled = new Counter('registration_enrolled');
const refused = new Counter('registration_refused');
const dropped = new Counter('registration_dropped');

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
// Livewire 4 serves its endpoint under a versioned prefix (livewire-<hash>/update); read it from the page.
const updateUri = (html) => { const m = html.match(/(livewire-[0-9a-f]+)\/livewire/); return `${base}/${m ? m[1] : 'livewire'}/update`; };
const dropIds = (html) => [...html.matchAll(/wire:click="drop\((\d+)\)"/g)].map((m) => Number(m[1]));

function livewire(uri, token, snap, updates, calls, name) {
    const payload = JSON.stringify({ _token: token, components: [{ snapshot: snap, updates, calls }] });
    const res = http.post(uri, payload, {
        headers: { 'Content-Type': 'application/json', 'X-Livewire': '1', 'X-Requested-With': 'XMLHttpRequest' },
        tags: { name },
    });
    let next = null;
    let html = '';
    try {
        const body = JSON.parse(res.body);
        next = body.components[0].snapshot;
        html = body.components[0].effects.html || '';
    } catch (e) { /* refused or error response: no new snapshot */ }
    return { res, next, html };
}

export default function () {
    http.cookieJar().clear(base);
    const account = accounts[(__VU - 1 + __ITER * 1000) % accounts.length];

    const login = http.get(`${base}/login`, { tags: { name: 'login_page' } });
    const auth = http.post(`${base}/login`, { _token: formToken(login.body), email: account.email, password: account.password }, { redirects: 0, tags: { name: 'login' } });
    check(auth, { 'signed in': (r) => r.status === 302 });

    const page = http.get(`${base}/portal/registration`, { tags: { name: 'registration_page' } });
    check(page, { 'registration page': (r) => r.status === 200 });

    const token = csrf(page.body);
    const uri = updateUri(page.body);
    let snap = snapshot(page.body);
    if (!token || !snap) { sleep(1); return; }

    // Type in the search box: the component returns the matching sections.
    const search = livewire(uri, token, snap, { search: searchText }, [], 'search');
    snap = search.next || snap;
    // Prefer a section with open seats (the seats line precedes the buttons in each result block);
    // fall back to the first result, whose refusal is still a realistic peak-time outcome.
    const withSeats = [...search.html.matchAll(/(\d+) open[\s\S]*?wire:click="enroll\((\d+)\)"/g)].find((m) => Number(m[1]) > 0);
    const section = withSeats ? withSeats[2] : (search.html.match(/wire:click="enroll\((\d+)\)"/) || [])[1];
    const before = dropIds(search.html);

    if (section) {
        const result = livewire(uri, token, snap, {}, [{ path: '', method: 'enroll', params: [Number(section)] }], 'enroll');
        snap = result.next || snap;
        const added = dropIds(result.html).filter((id) => !before.includes(id));

        if (added.length > 0) {
            enrolled.add(1);
            const drop = livewire(uri, token, snap, {}, [{ path: '', method: 'drop', params: [added[0]] }], 'drop');
            if (drop.next) { dropped.add(1); }
        } else {
            refused.add(1);
        }
    }

    sleep(1);
}
