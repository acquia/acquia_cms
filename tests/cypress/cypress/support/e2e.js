// Import commands.js using ES2015 syntax.
import "./commands"
require('./commands')
Cypress.on('uncaught:exception', (err, runnable) => {
  // Returning false here prevents Cypress from failing the test.
  return false
})
import 'cypress-mochawesome-reporter/register';
