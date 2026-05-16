import { chromium } from '@playwright/test';

const browser = await chromium.launch();
const page = await browser.newPage();
const logs = [];
page.on('console', (m) => logs.push(`[${m.type()}] ${m.text()}`));
page.on('pageerror', (e) => logs.push(`[pageerror] ${e.message}`));

await page.goto('http://127.0.0.1:8000/');

const samples = [];
for (const ms of [0, 50, 100, 150, 300, 800, 2000]) {
    if (ms > 0) await page.waitForTimeout(ms - (samples.at(-1)?.ms ?? 0));
    const snap = await page.evaluate((t) => {
        const title = document.querySelector('#hero .hero-title');
        return {
            ms: t,
            opacity: title ? getComputedStyle(title).opacity : null,
            transform: title ? getComputedStyle(title).transform : null,
        };
    }, ms);
    samples.push(snap);
}

const diag = await page.evaluate(() => {
    const title = document.querySelector('#hero .hero-title');
    const home = document.querySelector('.home-page');
    const main = document.querySelector('#main-content');
    const ctrl =
        window.Stimulus?.getControllerForElementAndIdentifier?.(home, 'home-entrance') ?? null;
    return {
        hasStimulus: !!window.Stimulus,
        homeController: home?.getAttribute('data-controller'),
        connected: !!ctrl,
        reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        mainOverflowY: main ? getComputedStyle(main).overflowY : null,
        titleOpacity: title ? getComputedStyle(title).opacity : null,
        titleTransform: title ? getComputedStyle(title).transform : null,
        gsapDefined: typeof window.gsap !== 'undefined',
    };
});

console.log('samples', JSON.stringify(samples, null, 2));
console.log(JSON.stringify(diag, null, 2));
console.log('--- console (motion/gsap/errors) ---');
for (const l of logs.filter((x) => /motion|gsap|error|stimulus/i.test(x))) {
    console.log(l);
}

await browser.close();
