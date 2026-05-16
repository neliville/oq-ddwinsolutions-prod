import { expect, test } from '@playwright/test';

test.describe('Floating layer — portal + positionnement', () => {
    test('tooltip Ishikawa : portal body, visible, non tronqué', async ({ page }) => {
        await page.goto('/ishikawa', { waitUntil: 'domcontentloaded' });

        const trigger = page.locator('[data-slot="tooltip-trigger"]').first();
        await expect(trigger).toBeVisible();

        await trigger.hover();
        await page.waitForTimeout(350);

        const wrapper = page.locator('[data-slot="tooltip-wrapper"][data-portaled="true"][data-state="open"]');
        await expect(wrapper).toHaveCount(1);

        const parentTag = await wrapper.evaluate((el) => el.parentElement?.tagName ?? '');
        expect(parentTag).toBe('BODY');

        const box = await wrapper.boundingBox();
        expect(box).not.toBeNull();
        expect(box!.width).toBeGreaterThan(20);
        expect(box!.height).toBeGreaterThan(10);

        const viewport = page.viewportSize()!;
        expect(box!.x).toBeGreaterThanOrEqual(-4);
        expect(box!.y).toBeGreaterThanOrEqual(-4);
        expect(box!.x + box!.width).toBeLessThanOrEqual(viewport.width + 4);
    });

    test('tooltip : repositionnement après scroll', async ({ page }) => {
        await page.goto('/ishikawa', { waitUntil: 'domcontentloaded' });

        const trigger = page.locator('[data-slot="tooltip-trigger"]').first();
        await trigger.scrollIntoViewIfNeeded();
        await trigger.hover();
        await page.waitForTimeout(350);

        const wrapper = page.locator('[data-slot="tooltip-wrapper"][data-state="open"]');
        const before = await wrapper.boundingBox();

        await page.evaluate(() => window.scrollBy(0, 120));
        await page.waitForTimeout(150);

        const after = await wrapper.boundingBox();
        expect(before).not.toBeNull();
        expect(after).not.toBeNull();
        expect(Math.abs(after!.y - before!.y)).toBeGreaterThan(5);
    });
});
