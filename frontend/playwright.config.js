import { defineConfig } from '@playwright/test'

export default defineConfig({

  testDir: './e2e',

  fullyParallel: false,

  retries: process.env.CI ? 2 : 0,

  reporter: 'html',

  use: {

    baseURL:
      process.env.UAT_BASE_URL
      || 'http://localhost:5173',

    browserName: 'chromium',

    screenshot: 'only-on-failure',

    trace: 'retain-on-failure',

  },

})