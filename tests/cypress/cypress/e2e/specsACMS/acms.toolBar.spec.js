/// <reference types="cypress" />
const toolBar = require("../../pages/ToolBar")

//TC-76
//TC-77
describe("ACMS Extend - Extend ACMS module", () => {
    context("ACMS Modules - Modules are prefixed with Acquia CMS", () => {
        it("ACMS modules are prefixed with Acquia CMS", () => {
            toolBar.prefixModules()
        })
    })

    //TODO - Dropdown Items Check(Mousehover item check)
    //content tab
    context("Content - Tab spell check", () => {
        it("Mouse hover on Content tab from sub-admin tool bar", () => {
            toolBar.validateContent()
        })

    })
    //structure tab
    context("Structure - Tab spell check", () => {
        it("Mouse hover on Structure tab from sub-admin tool bar", () => {
            toolBar.validateStructure()
        })

    })
    //site studio tab
    context("Site Studio - Tab spell check", () => {
        it("Mouse hover on Site Studio tab from sub-admin tool bar", () => {
            toolBar.validateSiteStudio()
        })
    })
    //appearance tab
    context("Appearance - Tab spell check", () => {
        it("Mouse hover on Appearance tab from sub-admin tool bar", () => {
            toolBar.validateAppearance()
        })
    })
    //extend tab
    context("Extend - Tab spell check", () => {
        it("Mouse hover on Extend tab from sub-admin tool bar", () => {
            toolBar.validateExtend()
        })
    })
    //configuration tab
    context("Configuration - Tab spell check", () => {
        it("Mouse hover on Configuration tab from sub-admin tool bar", () => {
            toolBar.validateConfiguration()
        })
    })
    //people tab
    context("People - Tab spell check", () => {
        it("Mouse hover on People tab from sub-admin tool bar", () => {
            toolBar.validatePeople()
        })
    })
    //reports tab
    context("Reports - Tab spell check", () => {
        it("Mouse hover on Reports tab from sub-admin tool bar", () => {

            toolBar.validateReports()
        })
    })
    //tour tab
    context("Tour - Tab spell check", () => {
        it("Mouse hover on Tour tab from sub-admin tool bar", () => {
            toolBar.validateTour()
        })
    })
})
