// <reference types="cypress" />
const userRoles = require("../../pages/UserRoles")
 //TC-96
 //TC-97
describe("People - Add new user with admin role", () => {
    context("Create new user with admin role", () => {
        it("Add new user by filling in the required details with role as an administrator ", () => {
            userRoles.createAdminUser()
            userRoles.deleteCreatedUser()
        })
    })
})
