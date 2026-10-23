// k6 load test: the registration peak (docs/manual/ko/operations/load-testing.md).
//
//   k6 run --vus 200 --duration 2m -e BASE_URL=http://laravel.test -e ACCOUNTS=storage/app/load-accounts.csv tests/load/registration.js
//
// ACCOUNTS: CSV with email,password (from `setup:demo --size=large --student-accounts`).
// Each virtual user signs in as one student, opens the registration page, adds a section
// to the cart and enrolls, then drops it, so the data returns to its starting state.

import http from 'k6/http';
import { check, sleep } from 'k6';
import { SharedArray } from 'k6/data';
import papaparse from 'https://jslib.k6.io/papaparse/5.1.1/index.js';

const base = __ENV.BASE_URL || 'http://laravel.test';
const accounts = new SharedArray('accounts', () => papaparse.parse(open(__ENV.ACCOUNTS || '../../storage/app/load-accounts.csv'), { header: true }).data.filter((r) => r.email));

export const options = {
    thresholds: {
        http_req_failed: ['rate<0.01'],
        'http_req_duration{name:registration}': ['p(95)<800'],
        'http_req_duration{name:enroll}': ['p(95)<1500'],
    },
};

function token(html) {
    const m = html.match(/name="_token" value="([^"]+)"/) || html.match(/name="csrf-token" content="([^"]+)"/);
    return m ? m[1] : null;
}

export default function () {
    const account = accounts[(__VU - 1) % accounts.length];
    const jar = http.cookieJar();

    const login = http.get(`${base}/login`);
    const auth = http.post(`${base}/login`, { _token: token(login.body), email: account.email, password: account.password }, { redirects: 0 });
    check(auth, { 'signed in': (r) => r.status === 302 });

    const page = http.get(`${base}/portal/registration`, { tags: { name: 'registration' } });
    check(page, { 'registration page': (r) => r.status === 200 });

    // Livewire actions go through the component endpoint; the simplest stable path for a
    // load test is the same request the browser sends. The section id comes from the page.
    const section = (page.body.match(/data-section-id="(\d+)"/) || [])[1];

    if (section) {
        const enroll = http.post(`${base}/portal/registration/enroll`, { _token: token(page.body), section_id: section }, { tags: { name: 'enroll' }, redirects: 0 });
        check(enroll, { 'enroll handled': (r) => r.status === 302 || r.status === 200 || r.status === 422 });
        http.post(`${base}/portal/registration/drop`, { _token: token(page.body), section_id: section }, { redirects: 0 });
    }

    http.post(`${base}/logout`, { _token: token(page.body) }, { redirects: 0 });
    jar.clear(base);
    sleep(1);
}
