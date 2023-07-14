// TC-## are the qTest test case id's <reference types="cypress" />.
const adminToolBar = require("../../pages/AdminToolBar")


describe("Verify the home page and its elements", () => {
    // TC-69.
    context('Verify tool bar items - Admin tool bar', () => {
        it("verify admin tool bar and the items in admin tool bar", () => {
            adminToolBar.inspectAndVerify()
        })
    })
    // TC-70.
    context('Verify Edit Button - eidit button should be functional', () => {
        it("Verify,click the Edit button on admin tool bar to activate and deactivate the mode", () => {
            adminToolBar.verifyEditButton()
        })
    })
    // TC-71.
    context('Verify Manage Button - Manage button should be functional', () => {
        it("Click on Manage button to hide/show sub-admin tool bar", () => {
            adminToolBar.verifyManageButton()
        })
    })
})
