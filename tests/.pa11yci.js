/**
 * @file
 * Pa11y config.
 */

const baseURL = 'http://9a203c23-1b8c-4126-8956-de713daba0a6.web.ahdev.cloud/home?share=2e61964f-715c-4349-8cc8-f618c669aa6a';

// Add urls for a11y testing here.
const urls = [
    '/',
];

module.exports = {
  defaults: {
    standard: 'WCAG2AA',
    hideElements: ['svg'],
    ignore: ['notice', 'warning'],
    chromeLaunchConfig: {
      args: ['--no-sandbox']
    },
    hideElements: ['button.mobile-menu-button']
  },
  urls: urls.map(url => `${baseURL}`)
};
