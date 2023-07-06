const { defineConfig } = require('cypress')

module.exports = defineConfig({
  projectId: 'ACMSQA_1.0.0',
  urlUATAdmin: 'http://orionacmsstage.prod.acquia-sites.com/user/login',
  urlQAAdmin: 'http://orionacmsode1.prod.acquia-sites.com/user/login',
  urlPRODAdmin: 'https://orionacms.prod.acquia-sites.com/user/login',
  urlODE5Admin: 'http://orionacmsode5.prod.acquia-sites.com/user/login',
  urlODE7Admin: 'http://orionacmsode7.prod.acquia-sites.com/user/login',
  urlODE8Admin: 'http://orionacmsode8.prod.acquia-sites.com/user/login',
  urlDEVAdmin: 'http://orionacmsdev.prod.acquia-sites.com/user/login',
  urlCIAdmin: 'http://127.0.0.1:8080/user/login',
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
      reportPageTitle: 'My Test Suite',
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
})
