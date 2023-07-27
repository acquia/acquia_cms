import 'cypress-iframe'
import content from './Content'
import utility from './Utility'
const testData = require("./TestData")

class Page {
    // Get the link of page from the content.
    get pageLink() {
        return cy.get(utility.$addContentMenu + "li:nth-child(3) > a")
    }
    // Layout canvas block should be present with the plus key.
    get layoutCanvas() {
        return cy.get("#react-collapsed-toggle-1 > span")
    }
    get addLayoutButton() {
        return cy.get("#react-collapsed-panel-1 div.sc-lirkk2-0.ssa-layout-canvas div.sc-lirkk2-1:nth-child(1) button.ssa-btn.ssa-btn-primary")
    }

    // Click and verify if the components are present.
    clickAndVerify() {
        // Get to the Page creation from content dropdown.
        this.pageLink.click({
            force: true
        })
        // Validate title of the page.
        content.pageTitle.should('have.text', 'Create Page')
        // Validate the title is present on the page.
        utility.editTitle.should("be.visible")
        // Validate layout canvas should be visible.
        page.layoutCanvas.should("be.visible")
        // Add layout button should be visible.
        page.addLayoutButton.should("be.visible")
        // Language dropdown should be visible.
        utility.contentLanguageSelect.should("be.visible")
        // Save as dropdown should be visible.
        utility.saveAsDropdown.should("be.visible")
        // Save button should be visible.
        utility.save.should("be.visible")
    }

    // Create page with layout canvas.
    createPageLayoutCanvas() {
        // Get to the Page creation from content dropdown.
        this.pageLink.click({
            force: true
        })
        // Enter the title for the page.
        utility.editTitle.type(testData.$content_title)
        cy.wait(4000)
        // Input the description.
        cy.get('.ck-editor__main[role="presentation"]').then(($element) => {
            cy.get('.ck-content[contenteditable=true]').then(el => {
                // @ts-ignore.
                const editor = el[0].ckeditorInstance
                editor.setData(testData.$content)
            })
        });
        // Create the page.
        this.createPage()
        // Publish and save the page.
        utility.saveAsDropdown.select('Published')
        utility.save.click({
            force: true
        })

        // Extend Visual page builder first.
        page.extendTab.click({
            force: true
        })
        page.filterModule.type("visual page")
        cy.wait(500)
        page.vpbCheckBox.check()
        utility.save.click()
        cy.wait(2000)

        // Search for created Page.
        content.contentPage.click()
        // search for newly created content.
        content.searchFilter.type(testData.$content_title, {
            force: true
        })
        // Filter with the newly created content name.
        content.filterButton.click({
            force: true
        })
        // Click on created content.
        page.createdContent.click()
        // Waiting for Visual page builder to load.
        cy.wait(5000)
        this.editCreatedPageVPB()

    }
    // Get Page creation components.
    $pageSelector = '#ssa-sidebar-browser > div.sc-alxsbm-9.ssa-sidebar-browser--list > ';
    get readMore() {
        return cy.get(this.$pageSelector + " div:nth-child(9) > ul > li > button")
        }
    // Get Blockquoate.
    get blockquote() {
        return cy.get(this.$pageSelector + " div:nth-child(3) > ul > li:nth-child(7) > button")
    }
    // Get Event slider.
    get eventSlider() {
        return cy.get(this.$pageSelector + " div:nth-child(4) > ul > li > button")
    }
    // Get Article slider.
    get articleSlider() {
        return cy.get(this.$pageSelector + " div:nth-child(4) > ul > li:nth-child(3) > button")
    }
    // Get Hero.
    get hero() {
        return cy.get(this.$pageSelector + " div:nth-child(1) > ul > li > button")
    }
    // Get extend module tab.
    get extendTab() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(6) > a")
    }
    // Get filter the modules.
    get filterModule() {
        return cy.get("#edit-text")
    }
    // Get checkbox of visual page builder.
    get vpbCheckBox() {
        return cy.get("#edit-modules-sitestudio-page-builder-enable")
    }

    // Get content tab.
    get createdContent() {
        return cy.get("tbody > :nth-child(1) > .views-field-title")
    }


    // Create the page.
    createPage() {
        // Add the component.
        this.addLayoutButton.click()
        cy.wait(1000)
        // Add read me at the bottom of the page.
        this.readMore.click({
            multiple: true
        })
        cy.wait(500)
        // Add Event Slider.
        this.eventSlider.click({
            multiple: true
        })
        cy.wait(500)
        // Add Article Slider.
        this.articleSlider.click({
            multiple: true
        })
        cy.wait(500)
        // Add BlockQuote.
        this.blockquote.click({
            multiple: true
        })
        cy.wait(500)
        // Add Hero.
        this.hero.click({
            multiple: true
        })
        cy.wait(500)
    }

    // Get Visual Page Builder.
    get vpb() {
        return cy.get("#coh-builder-btn")
    }
    // Get Add Button on VPB.
    get addVPB() {
        return cy.get('[id*="add-button-cohcanvas-"]')
    }
    // Get Hero add button from VPB.
    get addHeroVPB() {
        return cy.get(this.$pageSelector + "div:nth-child(1) > ul > li > button")
    }
    // Get Save button VPB.
    $vpbSelector = '#ssaApp > div.sc-wvs7do-0.ssa-edit-button-container > ';
    get saveVPB() {
        return cy.get(this.$vpbSelector + " div > div.sc-1j6p5lt-0.save-button-wrapper > button")
    }
    // Get Exit VPB.
    get exitVPB() {
        return cy.get(this.$vpbSelector + " button")
    }
    // Edit page with VPB - visual page builder.
    editCreatedPageVPB() {
        // Click on VPB.
        this.vpb.click({
            force: true
        })
        cy.wait(5000)
        // Click on add button.
        this.addVPB.click({
            multiple: true
        })
        // Add new Hero.
        this.addHeroVPB.click()
        cy.wait(5000)
        // Save the edited page.
        this.saveVPB.click({
            force: true
        })
        cy.wait(5000)
        cy.reload()
        // Exit VPB.
        this.exitVPB.click({
            force: true
        })
    }

    // Validate created page.
    validateCreatedPage() {
        // Validate the URL.
        cy.url().should('eq', testData.$published_url)
    }
}

const page = new Page()
module.exports = page
