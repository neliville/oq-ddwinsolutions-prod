import { expect, test } from '@playwright/test';

test.describe('Performance — couches JS motion', () => {
    test('accueil : data-page-motion et GSAP chargé', async ({ page }) => {
        await page.goto('/', { waitUntil: 'networkidle' });

        await expect(page.locator('html')).toHaveAttribute('data-page-motion', 'true');
        await page.waitForSelector('.home-page[data-controller~="home-entrance"]', { timeout: 10_000 });

        const heroAnimated = await page.waitForFunction(() => {
            const el = document.querySelector('#hero .hero-title');
            if (!el) {
                return false;
            }
            const style = getComputedStyle(el);
            return Number(style.opacity) >= 0.99;
        }, { timeout: 12_000 });
        expect(heroAnimated).toBeTruthy();
    });

    test('login : pas de GSAP global (Layer 2 non chargé)', async ({ page }) => {
        await page.goto('/login', { waitUntil: 'networkidle' });

        await expect(page.locator('html')).toHaveAttribute('data-page-motion', 'false');

        await page.waitForTimeout(1500);

        const gsapAbsent = await page.evaluate(() => typeof (window as Window & { gsap?: unknown }).gsap === 'undefined');
        expect(gsapAbsent).toBe(true);
    });

    test('login : bundle home-landing.css absent', async ({ page }) => {
        const responses: string[] = [];
        page.on('response', (res) => {
            if (res.url().includes('home-landing')) {
                responses.push(res.url());
            }
        });

        await page.goto('/login', { waitUntil: 'networkidle' });
        expect(responses).toHaveLength(0);
    });
});
