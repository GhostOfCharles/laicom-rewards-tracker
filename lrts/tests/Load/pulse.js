import http from 'k6/http';
import {check, sleep} from 'k6';
import {Trend} from 'k6/metrics';

// Dedicated test accounts only; supply JSON through the environment, never source control.
const accounts = JSON.parse(__ENV.RTS_LOAD_ACCOUNTS || '[]');
const base = __ENV.BASE_URL || 'http://127.0.0.1:8900';
if (!accounts.length) throw new Error('Supply RTS_LOAD_ACCOUNTS with isolated test accounts.');
if (!base.includes('127.0.0.1') && !base.includes('localhost') && __ENV.ALLOW_NONLOCAL_LOAD !== 'true') throw new Error('Explicitly authorize a nonlocal load-test target.');
const pulseDuration = new Trend('pulse_duration', true);
export const options = {vus:50, duration:'1m', thresholds:{pulse_duration:['p(95)<100'], checks:['rate>0.99']}};
let etag;
export default function () {
    const account = accounts[(__VU - 1) % accounts.length];
    const admin = account.role === 'admin';
    if (__ITER === 0) {
        const url = base + (admin ? '/admin/login' : '/login');
        const page = http.get(url);
        const token = /name="_token" value="([^"]+)"/.exec(page.body)?.[1];
        if (!token) throw new Error('Login CSRF token missing.');
        const login = http.post(url, {_token:token,email:account.email,password:account.password});
        check(login, {'login succeeded': r=>r.status===200});
        check(http.get(base+(admin?'/admin/receipts':'/dashboard')), {'list loaded':r=>r.status===200});
    }
    const headers = {Accept:'application/json'};
    if (etag) headers['If-None-Match']=etag;
    const response = http.get(base+(admin?'/admin/nav-counts':'/dashboard/pulse'),{headers});
    pulseDuration.add(response.timings.duration);
    check(response, {'pulse succeeded':r=>r.status===200 || r.status===304});
    etag=response.headers.Etag || response.headers.ETag || etag;
    sleep(admin?20:30);
}
