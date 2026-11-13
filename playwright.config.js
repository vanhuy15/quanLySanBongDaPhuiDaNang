const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tester/03_e2e_ui_test',
  outputDir: './tester/03_e2e_ui_test/test-results',
  timeout: 30000,
  fullyParallel: false,
  reporter: [
    ['list'],
    ['html', { outputFolder: 'tester/03_e2e_ui_test/playwright-report', open: 'never' }]
  ],
  use: {
    baseURL: 'http://localhost/quanlysanbong/',
    headless: true,
    screenshot: 'on',
    trace: 'on',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
