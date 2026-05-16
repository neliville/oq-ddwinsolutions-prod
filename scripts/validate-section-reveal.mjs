import { chromium } from '@playwright/test';

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
await page.waitForTimeout(1500);

const before = await page.evaluate(() => {
    const s = document.querySelector('#outils');
    const blocks = [...s.querySelectorAll(':scope > .container > *')];
    return {
        sectionOpacity: getComputedStyle(s).opacity,
        blocks: blocks.map((b) => getComputedStyle(b).opacity),
    };
});

await page.mouse.wheel(0, 850);
await page.waitForTimeout(400);

const mid = await page.evaluate(() => {
    const s = document.querySelector('#outils');
    const blocks = [...s.querySelectorAll(':scope > .container > *')];
    return {
        scrollY: window.scrollY,
        sectionOpacity: getComputedStyle(s).opacity,
        blocks: blocks.map((b) => getComputedStyle(b).opacity),
        stCount: window.ScrollTrigger?.getAll?.().length ?? 'no global ST',
    };
});

console.log('before', before);
console.log('mid', mid);
await browser.close();
