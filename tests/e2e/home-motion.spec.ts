import { expect, test } from '@playwright/test';

test.describe('Home GSAP motion', () => {
    test.beforeEach(async ({ page }) => {
        await page.addInitScript(() => {
            localStorage.setItem('motion:debug', '1');
        });
    });

    test('single scroll container and hero animates on load', async ({ page }) => {
        const consoleErrors: string[] = [];
        page.on('console', (msg) => {
            if (msg.type() === 'error') {
                consoleErrors.push(msg.text());
            }
        });

        await page.goto('/', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('.home-page[data-controller~="home-entrance"]');

        const mainOverflow = await page.locator('#main-content').evaluate((el) => getComputedStyle(el).overflowY);
        expect(mainOverflow).toBe('visible');

        const heroTitle = page.locator('#hero .hero-title');
        await expect(heroTitle).toBeVisible();

        const sawMotion = await page.waitForFunction(() => {
            const el = document.querySelector('#hero .hero-title');
            if (!el) {
                return false;
            }
            const style = getComputedStyle(el);
            const opacity = Number(style.opacity);
            const transform = style.transform;
            const yOffset =
                transform !== 'none' &&
                !/matrix\(1,\s*0,\s*0,\s*1,\s*0,\s*0\)/.test(transform);
            return opacity < 0.99 || yOffset;
        }, { timeout: 8000 });

        expect(sawMotion).toBeTruthy();

        await page.waitForTimeout(800);

        const afterAnimation = await heroTitle.evaluate((el) => {
            const style = getComputedStyle(el);
            return {
                opacity: style.opacity,
                transform: style.transform,
            };
        });

        expect(Number(afterAnimation.opacity)).toBeGreaterThanOrEqual(0.99);
        expect(afterAnimation.transform).toMatch(/matrix\(1,\s*0,\s*0,\s*1,\s*0,\s*0\)/);

        const gsapErrors = consoleErrors.filter((t) => /gsap|scrolltrigger/i.test(t));
        expect(gsapErrors).toEqual([]);
    });

    test('section blocks reveal on scroll with visible stagger', async ({ page }) => {
        await page.goto('/', { waitUntil: 'networkidle' });
        await page.waitForSelector('#outils');

        const hiddenBeforeScroll = await page.evaluate(() => {
            const cards = [...document.querySelectorAll('#outils [data-home-reveal].home-tool-card')];
            const section = document.querySelector('#outils');
            return {
                sectionOpacity: getComputedStyle(section).opacity,
                cardCount: cards.length,
                cardsHidden: cards.every((b) => Number(getComputedStyle(b).opacity) < 0.05),
            };
        });

        expect(hiddenBeforeScroll.sectionOpacity).toBe('1');
        expect(hiddenBeforeScroll.cardCount).toBeGreaterThan(0);
        expect(hiddenBeforeScroll.cardsHidden).toBe(true);

        await page.locator('#outils').scrollIntoViewIfNeeded();
        await page.waitForFunction(
            () => {
                const cards = [...document.querySelectorAll('#outils [data-home-reveal].home-tool-card')];
                return cards.length > 0 && cards.every((b) => Number(getComputedStyle(b).opacity) > 0.95);
            },
            { timeout: 5000 },
        );
    });
});
