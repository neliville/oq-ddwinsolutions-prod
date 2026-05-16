import { chromium } from '@playwright/test';

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
await page.waitForTimeout(1500);

const r = await page.evaluate(() => {
    const s = document.querySelector('#outils');
    const log = [];
    for (let y = 0; y <= 1200; y += 25) {
        window.scrollTo(0, y);
        const rect = s.getBoundingClientRect();
        const opacity = parseFloat(getComputedStyle(s).opacity);
        const vis = Math.max(0, Math.min(rect.bottom, innerHeight) - Math.max(rect.top, 0));
        log.push({ y, top: Math.round(rect.top), opacity, vis: Math.round(vis) });
    }
    const firstRise = log.find((x, i) => i > 0 && x.opacity > 0.2 && log[i - 1].opacity < 0.2);
    const at900 = log.find((x) => x.y === 900);
    return { firstRise, at900, initial: log[0], mid: log[8] };
});

console.log(JSON.stringify(r, null, 2));
await browser.close();
