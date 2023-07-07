/// <reference types="cypress" />
const tourPage = require("../../pages/TourPage")

//TC-100
describe("Tour - Content of the Tour page", () => {
    context('Contents of the tour page', () => {
        it("Verify the content of Tour page", () => {
            tourPage.validateTourPageContents()
        })
    })

    context('Wizard setup - Modal ', () => {
        it("Verify the opened wizard", () => {
            tourPage.wizardValidations()
        })
    })
})
