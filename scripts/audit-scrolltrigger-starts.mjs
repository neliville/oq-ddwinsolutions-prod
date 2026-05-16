import { chromium } from '@playwright/test';

const STARTS = ['top 95%', 'top 90%', 'top 85%', 'top bottom-=100', 'top bottom'];

async function testStart(start) {
    const browser = await chromium.launch();
    const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });

    await page.addInitScript((s) => {
        window.__TEST_START = s;
    }, start);

    await page.goto('http://127.0.0.1:8000/', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1200);

    const outils = await page.evaluate(async (startVal) => {
        const section = document.querySelector('#outils');
        const { gsap } = await import('/assets/vendor/gsap/index.js').catch(() => ({}));
        void gsap;

        const results = [];
        let lastOpacity = getComputedStyle(section).opacity;

        for (let scrollY = 0; scrollY <= 1200; scrollY += 50) {
            window.scrollTo(0, scrollY);
            await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
            const rect = section.getBoundingClientRect();
            const opacity = getComputedStyle(section).opacity;
            const visiblePct = Math.max(0, Math.min(rect.bottom, window.innerHeight) - Math.max(rect.top, 0)) / rect.height;
            if (opacity !== lastOpacity) {
                results.push({
                    scrollY,
                    sectionTopInVp: Math.round(rect.top),
                    visiblePct: Math.round(visiblePct * 100),
                    opacity,
                    event: 'opacity-change',
                });
                lastOpacity = opacity;
            }
        }
        return {
            sectionHeight: Math.round(section.getBoundingClientRect().height),
            initialTop: 870,
            transitions: results,
            finalOpacity: getComputedStyle(section).opacity,
        };
    }, start);

    await browser.close();
    return { start, outils };
}

// Use built-in page with patched controller - simpler: evaluate ScrollTrigger math
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
await page.waitForTimeout(1000);

const analysis = await page.evaluate(() => {
    const section = document.querySelector('#outils');
    const vh = window.innerHeight;
    const sectionDocTop = section.getBoundingClientRect().top + window.scrollY;

    const starts = ['top 95%', 'top 90%', 'top 85%', 'top bottom-=100', 'top bottom'];
    const parseStart = (start) => {
        // simplified: "top X%" means when section top hits X% of viewport height
        const m = start.match(/top\s+(\d+)%/);
        if (m) return (parseInt(m[1], 10) / 100) * vh;
        if (start === 'top bottom') return vh;
        if (start.includes('bottom-=')) {
            const off = parseInt(start.match(/bottom-=(\d+)/)?.[1] ?? '0', 10);
            return vh - off;
        }
        return vh * 0.85;
    };

    return starts.map((start) => {
        const triggerLine = parseStart(start);
        const scrollWhenFires = sectionDocTop - triggerLine;
        const rectAtFire = {
            top: sectionDocTop - scrollWhenFires - window.scrollY,
        };
        rectAtFire.top = triggerLine;
        const visibleAtFire = Math.max(
            0,
            Math.min(section.getBoundingClientRect().height, vh - triggerLine),
        );
        const visiblePct = Math.round((visibleAtFire / section.getBoundingClientRect().height) * 100);
        return {
            start,
            scrollYWhenFires: Math.round(scrollWhenFires),
            triggerLinePx: Math.round(triggerLine),
            approxVisiblePctOfSection: visiblePct,
            userSeesSection: visiblePct > 15,
        };
    });
});

console.log('=== START COMPARISON (#outils, viewport 800px, docTop ~870) ===');
console.table(analysis);

// Live scroll: when does current site fire?
const liveFire = await page.evaluate(() => {
    const section = document.querySelector('#outils');
    const log = [];
    let fired = false;
    const obs = () => {
        const o = getComputedStyle(section).opacity;
        if (o === '1' && !fired) {
            fired = true;
            const rect = section.getBoundingClientRect();
            const visible = Math.max(0, Math.min(rect.bottom, innerHeight) - Math.max(rect.top, 0));
            log.push({
                scrollY: Math.round(window.scrollY),
                sectionTop: Math.round(rect.top),
                visiblePx: Math.round(visible),
                visiblePct: Math.round((visible / rect.height) * 100),
            });
        }
    };
    for (let y = 0; y <= 1000; y += 10) {
        window.scrollTo(0, y);
        obs();
    }
    return log[0] ?? null;
});

console.log('\n=== ACTUAL FIRE (current build) ===');
console.log(liveFire);

await browser.close();
