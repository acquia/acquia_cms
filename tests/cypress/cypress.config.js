const { defineConfig } = require('cypress')

module.exports = defineConfig({
  projectId: 'ACMSQA_1.0.0',
  browserUname: 'acms',
  browserPassword: 'DmpU05sN13o1@bQ!',
  adminUser: 'admin',
  adminPassword: 'admin',
  reporter: 'cypress-multi-reporters',
  reporterOptions: {
    reporterEnabled: 'cypress-mochawesome-reporter, mocha-junit-reporter',
    cypressMochawesomeReporterReporterOptions: {
      reportDir: 'cypress/reports',
      charts: true,
      reportPageTitle: 'Acquia CMS Cypress tests report.',
      embeddedScreenshots: true,
      inlineAssets: true,
    },
    mochaJunitReporterReporterOptions: {
      mochaFile: 'cypress/reports/junit/results-[hash].xml',
    },
  },
  video: false,
  e2e: {
    // We've imported your old cypress plugins here.
    // You may want to clean this up later by importing these.
    setupNodeEvents(on, config) {
      return require('./cypress/plugins/index.js')(on, config)
    },
    specPattern: 'cypress/e2e/**/*.{js,jsx,ts,tsx}',
  },
  viewportWidth: 1792,
  viewportHeight: 1120,
})
