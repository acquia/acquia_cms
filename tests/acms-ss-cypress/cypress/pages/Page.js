import 'cypress-iframe'
import content from './Content'
const testData = require("./TestData")

class Page {
    //Get the required components
    //Get the link of page from the content
    get pageLink() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(2) > ul > li:nth-child(2) > ul > li:nth-child(3) > a")
    }
    //Get edit title
    get editTitle() {
        return cy.get("#edit-title-0-value")
    }
    //Layout canvas block should be present with the plus key
    get layoutCanvas() {
        return cy.get("#react-collapsed-toggle-1 > span")
    }
    get addLayoutButton() {
        return cy.get("#react-collapsed-panel-1 div.sc-lirkk2-0.ssa-layout-canvas div.sc-lirkk2-1:nth-child(1) button.ssa-btn.ssa-btn-primary")
    }
    //Get Language dropdown
    get languageDropdown() {
        return cy.get("#edit-langcode-0-value")
    }
    //Save as dropdown
    get saveAsDropdown() {
        return cy.get("#edit-moderation-state-0-state")
    }
    //Get Save as button
    get pageSave() {
        return cy.get("#edit-submit")
    }


    //Click and verify if the components are present
    clickAndVerify() {
        //Get to the Page creation from content dropdown
        this.pageLink.click({
            force: true
        })
        //Validate title of the page
        content.pageTitle.should('have.text', 'Create Page')
        //Validate the title is present on the page
        page.editTitle.should("be.visible")
        //Validate layout canvas should be visible
        page.layoutCanvas.should("be.visible")
        //Add layout button should be visible
        page.addLayoutButton.should("be.visible")
        //Language dropdown should be visible
        page.languageDropdown.should("be.visible")
        //Save as dropdown should be visible
        page.saveAsDropdown.should("be.visible")
        //Save button should be visible
        page.pageSave.should("be.visible")
    }

    //Create page with layout canvas
    createPageLayoutCanvas() {
        //Get to the Page creation from content dropdown
        this.pageLink.click({
            force: true
        })
        //Enter the title for the page
        page.editTitle.type(testData.$content_title)
        cy.wait(4000)
        //Input the description
        cy.get('.ck-editor__main[role="presentation"]').then(($element) => {
            cy.get('.ck-content[contenteditable=true]').then(el => {
                // @ts-ignore
                const editor = el[0].ckeditorInstance
                editor.setData(testData.$content)
            })
        });
        //Create the page
        this.createPage()
        //Publish and save the page
        page.saveAsDropdown.select('Published')
        page.pageSave.click({
            force: true
        })

        //Extend Visual page builder first
        page.extendTab.click({
            force: true
        })
        page.filterModule.type("visual page")
        cy.wait(500)
        page.vpbCheckBox.check()
        page.installModule.click()
        cy.wait(2000)

        //Search for created Page
        content.contentPage.click()
        //search for newly created content
        content.searchFilter.type(testData.$content_title, {
            force: true
        })
        //filter with the newly created content name
        content.filterButton.click({
            force: true
        })
        //Click on created content
        page.createdContent.click()
        //Waiting for Visual page builder to load
        cy.wait(5000)
        this.editCreatedPageVPB()

    }
    //Get Page creation components
    get readMore() {
        return cy.get("#ssa-sidebar-browser > div.sc-alxsbm-9.ssa-sidebar-browser--list > div:nth-child(9) > ul > li > button")
        }
    //Get Blockquoate
    get blockquote() {
        return cy.get("#ssa-sidebar-browser > div.sc-alxsbm-9.ssa-sidebar-browser--list > div:nth-child(3) > ul > li:nth-child(7) > button")
    }
    //Get Event slider
    get eventSlider() {
        return cy.get("#ssa-sidebar-browser > div.sc-alxsbm-9.ssa-sidebar-browser--list > div:nth-child(4) > ul > li > button")
    }
    //Get Article slider
    get articleSlider() {
        return cy.get("#ssa-sidebar-browser > div.sc-alxsbm-9.ssa-sidebar-browser--list > div:nth-child(4) > ul > li:nth-child(3) > button")
    }
    //Get Hero
    get hero() {
        return cy.get("#ssa-sidebar-browser > div.sc-alxsbm-9.ssa-sidebar-browser--list > div:nth-child(1) > ul > li > button")
    }
    //Get extend module tab
    get extendTab() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(6) > a")
    }
    //Get filter the modules
    get filterModule() {
        return cy.get("#edit-text")
    }
    //Get checkbox of visual page builder
    get vpbCheckBox() {
        return cy.get("#edit-modules-sitestudio-page-builder-enable")
    }
    //Get Install button
    get installModule() {
        return cy.get("#edit-submit")
    }
    //Get content tab
    get createdContent() {
        return cy.get("tbody > :nth-child(1) > .views-field-title")
    }


    //Create the page
    createPage() {
        //Add the component -
        this.addLayoutButton.click()
        cy.wait(1000)
        //Add read me at the bottom of the page
        this.readMore.click({
            multiple: true
        })
        cy.wait(500)
        //Add Event Slider
        this.eventSlider.click({
            multiple: true
        })
        cy.wait(500)
        //Add Article Slider
        this.articleSlider.click({
            multiple: true
        })
        cy.wait(500)
        //Add BlockQuote
        this.blockquote.click({
            multiple: true
        })
        cy.wait(500)
        //Add Hero
        this.hero.click({
            multiple: true
        })
        cy.wait(500)


    }

    //Get Visual Page Builcder
    get vpb() {
        return cy.get("#coh-builder-btn")
    }
    //Get Add Button on VPB
    get addVPB() {
        return cy.get('[id*="add-button-cohcanvas-"]')
    }
    //Get Hero add button from VPB
    get addHeroVPB() {
        //return cy.xpath("//*[@id=\"coh-sidebar-browser\"]/div[3]/div[1]/ul/li/button")
        return cy.get("#ssa-sidebar-browser > div.sc-alxsbm-9.ssa-sidebar-browser--list > div:nth-child(1) > ul > li > button")
    }
    //Get Save button VPB
    get saveVPB() {
        return cy.get("#ssaApp > div.sc-wvs7do-0.ghBhLA.ssa-edit-button-container > div > div.sc-1j6p5lt-0.cTtgIT.save-button-wrapper > button")
    }
    //Get Exit VPB
    get exitVPB() {
        return cy.get("#ssaApp > div.sc-wvs7do-0.ghBhLA.ssa-edit-button-container > button")
    }
    //Edit page with VPB - visual page builder
    editCreatedPageVPB() {
        //Click on VPB
        this.vpb.click({
            force: true
        })
        cy.wait(5000)
        //Click on add button
        this.addVPB.click({
            multiple: true
        })
        //Add new Hero
        this.addHeroVPB.click()
        cy.wait(5000)
        //Save the edited page
        this.saveVPB.click({
            force: true
        })
        cy.wait(5000)
        cy.reload()
        //Exit VPB
        this.exitVPB.click({
            force: true
        })



    }

    //TODO - More validations are needed
    //Validate created page
    validateCreatedPage() {
        //Validate the URL
        cy.url().should('eq', testData.$published_url)
    }
}

const page = new Page()
module.exports = page
