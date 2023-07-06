/// <reference types="cypress" />

const passwordPolicy = require("../../pages/PasswordPolicy")

 
describe("Verify the password policy", () => {
    context('Password Policy - verify the checkboxes are present', () => {
        it("Verify the check boxes of password policy page", () => {
            passwordPolicy.verifyCheckBoxes()

        })
    })
    context('Password Policy - verify the textboxes are present', () => {
        it("Verify the text boxes of password policy page", () => {
            passwordPolicy.verifyTextBoxes()

        })
    })
    context('Password Policy - verify the buttons are present', () => {
        it("Verify the button of password policy page", () => {
            passwordPolicy.verifyButtons()

        })
    })
    context('Password Policy - Verify policy contraints', () => {
        it("Verify the password policy of new account creation", () => {
            passwordPolicy.verifyPasswordPolicy()
        })
    })
    context('Password Policy - Characters allowed in password', () => {
        it("Verify valid password allowed", () => {
            passwordPolicy.verifyPassword()
            passwordPolicy.deleteUser()
        })
    })
})