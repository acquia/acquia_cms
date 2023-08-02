// Login to ACMS as admin.
beforeEach("Login", () => {
    // Clears browser cache.
    cy.exec('npm cache clear --force')
    // Login Url.
    cy.visit(Cypress.env('loginUrl'), {
        auth: {
            username: Cypress.config().browserUname,
            password: Cypress.config().browserPassword
        }})
    cy.get('input[id="edit-name"]').type(Cypress.config().adminUser)
    cy.get('input[id="edit-pass"]').type(Cypress.config().adminPassword)
    cy.get('input[id="edit-submit"]').click()
})
