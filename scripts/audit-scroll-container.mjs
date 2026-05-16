import { chromium } from '@playwright/test';

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
await page.waitForTimeout(1500);

const r = await page.evaluate(() => {
    const before = window.scrollY;
    window.scrollTo(0, 500);
    const afterSync = window.scrollY;
    return new Promise((resolve) => {
        requestAnimationFrame(() => {
            resolve({
                before,
                afterSync,
                afterRaf: window.scrollY,
                docScrollHeight: document.documentElement.scrollHeight,
                bodyScrollHeight: document.body.scrollHeight,
                htmlOverflow: getComputedStyle(document.documentElement).overflow,
                bodyOverflow: getComputedStyle(document.body).overflow,
                htmlHeight: getComputedStyle(document.documentElement).height,
                bodyHeight: getComputedStyle(document.body).height,
                mainOverflow: getComputedStyle(document.querySelector('#main-content')).overflowY,
                mainScrollTop: document.querySelector('#main-content')?.scrollTop,
                scrollers: [...document.querySelectorAll('*')]
                    .filter((el) => {
                        const s = getComputedStyle(el);
                        return (s.overflowY === 'auto' || s.overflowY === 'scroll') && el.scrollHeight > el.clientHeight + 10;
                    })
                    .slice(0, 8)
                    .map((el) => ({
                        tag: el.tagName,
                        id: el.id,
                        class: el.className?.toString?.().slice(0, 50),
                        scrollHeight: el.scrollHeight,
                        clientHeight: el.clientHeight,
                    })),
            });
        });
    });
});

console.log(JSON.stringify(r, null, 2));

await page.mouse.wheel(0, 2000);
await page.waitForTimeout(300);
const afterWheel = await page.evaluate(() => ({
    scrollY: window.scrollY,
    outilsTop: document.querySelector('#outils')?.getBoundingClientRect().top,
    outilsOpacity: getComputedStyle(document.querySelector('#outils')).opacity,
}));

console.log('after wheel', afterWheel);
await browser.close();
