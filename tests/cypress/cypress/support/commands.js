// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })

//login to ACMS as admin with environmental login
beforeEach("Login", () => {
        //Clears browser cache
        cy.exec('npm cache clear --force')
        //Login Url
        cy.visit(Cypress.env('loginUrl'), {
            auth: {
                username: Cypress.config().browserUname,
                password: Cypress.config().browserPassword
            }})
        cy.get('input[id="edit-name"]').type(Cypress.config().adminUser)
        cy.get('input[id="edit-pass"]').type(Cypress.config().adminPassword)
        cy.get('input[id="edit-submit"]').click()
    })
