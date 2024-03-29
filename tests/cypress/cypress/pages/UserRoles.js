import utility from './Utility'
const testData = require("./TestData")
const passPolicy = require("./PasswordPolicy")

class UserRoles {

    // Get Add user link.
    get addUser() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(8) > ul > li:nth-child(1) > a")
    }

    // Get Admin Checkbox.
    get adminCheckBox() {
        return cy.get("#edit-roles-administrator")
    }

    // Get email Address textbox.
    get emailAdd() {
        return cy.get("#edit-mail")
    }
    // Get userName textbox.
    get userNameTextBox() {
        return cy.get("#edit-name")
    }
    // Get passWord textbox.
    get passwordTexBox() {
        return cy.get("#edit-pass-pass1")
    }
    // Get confirm password textbox.
    get confirmPassword() {
        return cy.get("#edit-pass-pass2")
    }

    // Create admin user.
    createAdminUser() {
        this.addUser.click({
            force: true
        })
        this.adminCheckBox.check()
        cy.wait(500)
        this.emailAdd.type(testData.$person_email)
        this.userNameTextBox.type(testData.$policy_username)
        this.passwordTexBox.type(testData.$policy_password)
        this.confirmPassword.type(testData.$policy_password)
        cy.wait(2000)
        utility.save.click()
        cy.get('.messages-list .messages--status .messages__content').contains("Created a new user account for QA_User. No email has been sent")
    }

    // Delete the created admin user
    deleteCreatedUser() {
        // Deleting created user.
        passPolicy.deleteUser()
    }

}
const userRoles = new UserRoles();
module.exports = userRoles;
