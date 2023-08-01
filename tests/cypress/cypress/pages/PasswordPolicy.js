import testData from './TestData'
import utility from './Utility'

class PasswordPolicy {

    // Get people tab link.
    get people(){
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(8) > a")
    }
    // Get the link to add user.
    get addUser() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(8) > ul > li:nth-child(1) > a")
    }

    // Get CheckBoxes.
    get adminCheckBox() {
        return cy.get("#edit-roles-administrator")
    }
    get devCheckBox() {
        return cy.get("#edit-roles-developer")
    }
    get siteBuilderCheckBox() {
        return cy.get("#edit-roles-site-builder")
    }
    get contentAdminCheckBox() {
        return cy.get("#edit-roles-content-administrator")
    }
    get contentAuthorCheckBox() {
        return cy.get("#edit-roles-content-author")
    }
    get contentEditorCheckBox() {
        return cy.get("#edit-roles-content-editor")
    }
    get userAdminCheckBox() {
        return cy.get("#edit-roles-user-administrator")
    }

    // Get the text boxes.
    get emailTextBox() {
        return cy.get("#edit-mail")
    }
    get usernameTextBox() {
        return cy.get("#edit-name")
    }
    get passwordTextBox() {
        return cy.get("#edit-pass-pass1")
    }
    get urlAliasTextBox() {
        return cy.get("#edit-path-0-alias")
    }

    // Get the status and password policy.
    get passPolycy1(){
        return cy.get("#password-policy-status > table > tbody > tr:nth-child(1) > td:nth-child(3)")
    }

    get passPolycy2(){
        return cy.get("#password-policy-status > table > tbody > tr:nth-child(2) > td:nth-child(3)")
    }

    get passPolycy3(){
        return cy.get("#password-policy-status > table > tbody > tr:nth-child(3) > td:nth-child(3)")
    }

    // Get password text box.
    get password(){
        return cy.get("#edit-pass-pass1")
    }

    // Get Confirm password.
    get confirmPassword(){
        return cy.get("#edit-pass-pass2")
    }

    // Search the created user.
    get searchUser(){
        return cy.get("#edit-user")
    }

    // Hit the filter user button.
    get filterUser(){
        return cy.get("#edit-submit-user-admin-people")
    }

    // Check all the searched user.
    get checkSearchedUser(){
        return cy.get("#views-form-user-admin-people-page-1 > table > thead > tr > th.select-all.views-field.views-field-user-bulk-form > input")
    }

    // Select the option of deleting the user from the dropdown.
    get selectAction(){
        return cy.get("#edit-action")
    }

    // Apply the action selector.
    get applyAction(){
        return cy.get("#edit-submit--2")
    }

    // Delete the user radio option.
    get deleteUser(){
        return cy.get('[type="radio"].edit-user-cancel-method-user-cancel-delete')
    }

    // Verify the contents of password policy page.
    verifyCheckBoxes() {
        // Navigating to the add user page.
        this.addUser.click({force:true})
        cy.wait(2000)
        // Validating all the checkboxes are visible.
        this.adminCheckBox.should("be.visible")
        this.devCheckBox.should("be.visible")
        this.siteBuilderCheckBox.should("be.visible")
        this.contentAdminCheckBox.should("be.visible")
        this.contentAuthorCheckBox.should("be.visible")
        this.contentEditorCheckBox.should("be.visible")
        this.userAdminCheckBox.should("be.visible")
    }

    verifyTextBoxes() {
        // Navigating to the add user page.
        this.addUser.click({force:true})
        cy.wait(2000)
        // Validating all text boxes are visible.
        this.emailTextBox.should("be.visible")
        this.usernameTextBox.should("be.visible")
        this.passwordTextBox.should("be.visible")
        this.urlAliasTextBox.should("be.visible")
    }

    // Verify the create new account button is present.
    verifyButtons(){
        // Navigating to the add user page.
        this.addUser.click({force:true})
        cy.wait(2000)
        // Validate the button present.
        utility.save.should("be.visible")
    }

    // Verify the password policy.
    verifyPasswordPolicy() {
        this.addUser.click({force:true})
        cy.wait(2000)
        this.passPolycy1.should('have.text',testData.$policy1)
        this.passPolycy2.should('have.text',testData.$policy2)
        this.passPolycy3.should('have.text',testData.$policy3)
    }

    // Put the expected password and verify.
    verifyPassword(){
        this.addUser.click({force:true})
        this.userAdminCheckBox.check()
        cy.wait(1000)
        this.emailTextBox.type(testData.$person_email)
        this.usernameTextBox.type(testData.$policy_username)
        this.password.type(testData.$policy_password)
        cy.wait(2000)
        this.confirmPassword.click()
        cy.wait(2000)
        this.confirmPassword.type(testData.$policy_password)
        cy.wait(200)
        utility.save.click()
        cy.get('.messages-list .messages--status .messages__content').contains("Created a new user account for QA_User. No email has been sent")
        }

    // Cancel and delete the created user.
    deleteUser(){
        this.people.click()
        this.searchUser.type(testData.$policy_username)
        this.filterUser.click()
        this.checkSearchedUser.check()
        this.selectAction.select("Cancel the selected user account(s)")
        utility.save.click()
        cy.get("#edit-user-cancel-method-user-cancel-delete").check().click()
        utility.save.click()
        cy.wait(1000)
        cy.get('.messages-list .messages--status .messages__content').contains("Account QA_User has been deleted.")
    }
}

const passwordPolicy = new PasswordPolicy();
module.exports = passwordPolicy;
