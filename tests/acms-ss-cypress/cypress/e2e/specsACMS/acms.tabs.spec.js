/// <reference types="cypress" />
const menuTabs = require("../../pages/MenuTabs")
 //TC-74
describe("Verify Menu and Primary tabs on ACMS Home Page", () => {
    context("Verify Menu items - Name and Clickable", () => {
        it("Verify name of menu items and make sure its clickable", () => {
            menuTabs.spell_Validations_Menu()
            menuTabs.click_menu_items()
        })
    })
//TC-75
    context("Verify primary tabs on ACMS - Name and clickable", () => {
        it("Verify name of primary tabs and make sure its clickable", () => {
            menuTabs.spell_validations_primary_menu()
            menuTabs.click_primary_menu_items()
        })
    })
})
