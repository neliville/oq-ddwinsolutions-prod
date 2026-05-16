import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: 'tests/e2e',
    timeout: 30_000,
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8000',
        headless: true,
    },
    webServer: process.env.PLAYWRIGHT_SKIP_WEBSERVER
        ? undefined
        : {
              command: 'php -S 127.0.0.1:8000 -t public',
              url: 'http://127.0.0.1:8000',
              reuseExistingServer: true,
              timeout: 120_000,
          },
});
