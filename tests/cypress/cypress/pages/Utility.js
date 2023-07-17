class Utility {

    // Save As dropdown.
    get saveAsDropdown() {
        return cy.get("#edit-moderation-state-0-state")
    }

    // Save content.
    get save() {
        return cy.get("#edit-submit")
    }
}
