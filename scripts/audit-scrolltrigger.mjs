import { chromium } from '@playwright/test';

const STARTS = ['top 95%', 'top 90%', 'top 85%', 'top bottom-=100'];

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });

await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
await page.waitForTimeout(1500);

const baseAudit = await page.evaluate(() => {
    const home = document.querySelector('.home-page');
    const directSections = home ? [...home.querySelectorAll(':scope > section')] : [];
    const nonHero = directSections.filter((s) => s.id !== 'hero');

    const st = window.ScrollTrigger;
    const triggers = st ? st.getAll() : [];

    const sectionInfo = nonHero.map((section) => {
        const rect = section.getBoundingClientRect();
        const style = getComputedStyle(section);
        let parentTransforms = [];
        let el = section.parentElement;
        while (el && el !== document.body) {
            const t = getComputedStyle(el).transform;
            const ov = getComputedStyle(el).overflow;
            if (t !== 'none') parentTransforms.push({ tag: el.tagName, id: el.id, class: el.className?.slice?.(0, 60), transform: t });
            if (ov === 'hidden' || ov === 'clip') parentTransforms.push({ overflow: ov, tag: el.tagName, id: el.id });
            el = el.parentElement;
        }
        return {
            id: section.id || '(no id)',
            className: section.className?.slice?.(0, 80),
            top: Math.round(rect.top),
            bottom: Math.round(rect.bottom),
            height: Math.round(rect.height),
            inViewportOnLoad: rect.top < window.innerHeight && rect.bottom > 0,
            aboveFold: rect.top < window.innerHeight * 0.85,
            opacity: style.opacity,
            visibility: style.visibility,
            transform: style.transform,
            display: style.display,
            overflow: style.overflow,
            parentIssues: parentTransforms,
        };
    });

    const triggerDetails = triggers.map((t) => ({
        trigger: t.trigger?.id || t.trigger?.className?.slice?.(0, 40) || '?',
        start: t.vars?.start ?? t.start,
        progress: t.progress?.toFixed?.(3),
        isActive: t.isActive,
        direction: t.direction,
    }));

    return {
        homeFound: !!home,
        directSectionCount: directSections.length,
        nonHeroCount: nonHero.length,
        scrollTriggerGlobal: !!st,
        scrollTriggerCount: triggers.length,
        triggerDetails,
        sectionInfo,
        mainOverflow: document.querySelector('#main-content') ? getComputedStyle(document.querySelector('#main-content')).overflowY : null,
        htmlOverflow: getComputedStyle(document.documentElement).overflowY,
        bodyOverflow: getComputedStyle(document.body).overflowY,
        scrollHeight: document.documentElement.scrollHeight,
        innerHeight: window.innerHeight,
    };
});

console.log('=== BASE AUDIT ===');
console.log(JSON.stringify(baseAudit, null, 2));

// Scroll each section into view and sample opacity
console.log('\n=== SCROLL REVEAL SAMPLING ===');
for (const section of baseAudit.sectionInfo) {
    await page.locator(`#${section.id === '(no id)' ? 'outils' : section.id}`).scrollIntoViewIfNeeded().catch(() => {});
    if (section.id !== '(no id)') {
        await page.locator(`#${section.id}`).scrollIntoViewIfNeeded();
    }
    await page.waitForTimeout(400);
    const snap = await page.evaluate((id) => {
        const sel = id === '(no id)' ? '.home-page > section:not(#hero)' : `#${id}`;
        const el = document.querySelector(sel);
        if (!el) return { id, missing: true };
        const style = getComputedStyle(el);
        const st = window.ScrollTrigger?.getAll().find((t) => t.trigger === el);
        return {
            id,
            opacity: style.opacity,
            transform: style.transform,
            triggerProgress: st?.progress,
            triggerIsActive: st?.isActive,
        };
    }, section.id);
    console.log(JSON.stringify(snap));
}

await browser.close();
