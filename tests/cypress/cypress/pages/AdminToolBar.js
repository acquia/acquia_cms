const testData = require("./TestData")

class AdminToolBar {
    // Get admin tool bar items.
    get editOption() {
        return cy.get("#toolbar-bar > div.contextual-toolbar-tab.toolbar-tab > button")
    }

    // Get manage button.
    get manageButton() {
        return cy.get("#toolbar-item-administration")
    }

    // Get the environment.
    get environment() {
        return cy.get("#toolbar-bar > div:nth-child(9) > a")
    }

    // Get admin icon on the toolbar.
    get adminIcon() {
        return cy.get("#toolbar-item-user")
    }

    // Get responsive icon.
    get responsiveIcon() {
        return cy.get("#responsive-preview-toolbar-tab > button")
    }

    // Inspect and verify the admin toolbar items.
    inspectAndVerify() {
        this.editOption.should('have.text', 'Edit')
        this.manageButton.should('have.text', 'Manage')
        this.adminIcon.should('have.text', 'admin')
        this.responsiveIcon.should('have.text', 'Layout preview')
    }

    // Verify the edit/manage button on admin tool bar.
    verifyEditButton() {
        this.editOption.click().invoke('attr', 'aria-pressed').should('equal', 'true')

    }
    verifyManageButton() {
        this.manageButton.click().invoke('attr', 'class').should('equal', 'toolbar-icon toolbar-icon-menu trigger toolbar-item')
    }
}
const adminToolBar = new AdminToolBar();
module.exports = adminToolBar;
