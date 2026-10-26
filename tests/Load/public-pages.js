// k6 load test for the public site: 200 concurrent visitors browsing the hot pages.
//
//   k6 run -e BASE_URL=https://dev.infokobuleti.com -e AUTH=user:password tests/Load/public-pages.js
//
// AUTH is the staging password (basic auth); leave it out for production. Pass when 95% of pages
// answer in under 800 ms and fewer than 1% fail.
import http from 'k6/http';
import { check, sleep } from 'k6';
import encoding from 'k6/encoding';

const BASE = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const headers = __ENV.AUTH ? { Authorization: `Basic ${encoding.b64encode(__ENV.AUTH)}` } : {};

export const options = {
    scenarios: {
        visitors: {
            executor: 'ramping-vus',
            stages: [
                { duration: '1m', target: 50 },
                { duration: '2m', target: 200 },
                { duration: '3m', target: 200 },
                { duration: '1m', target: 0 },
            ],
        },
    },
    thresholds: {
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(95)<800'],
    },
};

const LIST_PAGES = ['/', '/hotels', '/real-estate', '/classifieds', '/jobs', '/guide', '/events', '/news', '/en', '/ru/hotels'];

function links(body, prefix) {
    const found = [];
    const pattern = new RegExp(`href="${BASE.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(${prefix}/[a-z0-9-]+)"`, 'g');
    let match;

    while ((match = pattern.exec(body)) !== null && found.length < 20) {
        found.push(match[1]);
    }

    return found;
}

export default function () {
    const list = LIST_PAGES[Math.floor(Math.random() * LIST_PAGES.length)];
    const response = http.get(BASE + list, { headers, tags: { page: 'list' } });
    check(response, { 'list 200': (r) => r.status === 200 });
    sleep(1 + Math.random() * 2);

    const details = [...links(response.body, '/hotels'), ...links(response.body, '/real-estate'), ...links(response.body, '/jobs')];

    if (details.length > 0) {
        const detail = http.get(BASE + details[Math.floor(Math.random() * details.length)], { headers, tags: { page: 'detail' } });
        check(detail, { 'detail 200': (r) => r.status === 200 });
    }

    sleep(2 + Math.random() * 3);
}
