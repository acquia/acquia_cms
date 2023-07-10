const testData = require("./TestData")
class ToolBar {
    //Get extend link
    get extendLink() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(6) > a")
    }
    //Get extend filter
    get extendFilter() {
        return cy.get("#edit-text")
    }
    //Get the check button for starter module
    get checkButton() {
        return cy.get("#edit-modules-acquia-cms-starter-enable")
    }
    //Get install module button
    get installModuleButton() {
        return cy.get("#edit-submit")
    }

    //Get List of modules
    //article
    get article_module() {
        return cy.get("#module-acquia-cms-article")
    }
    //audio
    get audio_module() {
        return cy.get("#module-acquia-cms-audio")
    }
    //common
    get common_module() {
        return cy.get("#module-acquia-cms-common")
    }
    //development
    get development_module() {
        return cy.get("#module-acquia-cms-development")
    }
    //document
    get document_module() {
        return cy.get("#module-acquia-cms-document")
    }
    //event
    get event_module() {
        return cy.get("#module-acquia-cms-event")
    }
    //image
    get image_module() {
        return cy.get("#module-acquia-cms-image")
    }
    //page
    get page_module() {
        return cy.get("#module-acquia-cms-page")
    }
    //person
    get person_module() {
        return cy.get("#module-acquia-cms-person")
    }
    //place
    get place_module() {
        return cy.get("#module-acquia-cms-place")
    }
    //search
    get search_module() {
        return cy.get("#module-acquia-cms-search")
    }
    //starter
    get starter_module() {
        return cy.get("#module-acquia-cms-starter")
    }
    //support
    get support_module() {
        return cy.get("#module-acquia-cms-support")
    }
    //toolbar
    get toolbar_module() {
        return cy.get("#module-acquia-cms-toolbar")
    }
    //tour
    get tour_module() {
        return cy.get("#module-acquia-cms-tour")
    }
    //video
    get video_module() {
        return cy.get("#module-acquia-cms-video")
    }

    //Get content Tab
    get contentTab() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(2) > a")
    }
    get structureTab() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(3) > a")
    }
    get siteStudioTab() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(4) > a")
    }
    get appearanceTab() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(5) > a")
    }
    get extendTab() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(6) > a")
    }
    get configurationTab() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(7) > a")
    }
    get peopleTab() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(8) > a")
    }
    get reportsTab() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(9) > a")
    }
    get tourTab() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(10) > a")
    }


    //Validate the Acquia CMS prefixed modules
    prefixModules() {
        this.extendLink.click()
        this.extendFilter.type("Acquia CMS")
        this.article_module.should('have.text', testData.$article_module)
        this.audio_module.should('have.text', testData.$audio_module)
        this.common_module.should('have.text', testData.$common_module)
        this.development_module.should('have.text', testData.$development_module)
        this.document_module.should('have.text', testData.$document_module)
        this.event_module.should('have.text', testData.$event_module)
        this.image_module.should('have.text', testData.$image_module)
        this.page_module.should('have.text', testData.$page_module)
        this.person_module.should('have.text', testData.$person_module)
        this.place_module.should('have.text', testData.$place_module)
        this.search_module.should('have.text', testData.$search_module)
        this.starter_module.should('have.text', testData.$starter_module)
        this.support_module.should('have.text', testData.$support_module)
        this.toolbar_module.should('have.text', testData.$toolbar_module)
        this.tour_module.should('have.text', testData.$tour_module)
        this.video_module.should('have.text', testData.$video_module)

    }

    //Validate the text of the tabs
    validateContent() {
        this.contentTab.should('have.text', testData.$content_tab)
    }
    validateStructure() {
        this.structureTab.should('have.text', testData.$structure_tab)
    }
    validateSiteStudio() {
        this.siteStudioTab.should('have.text', testData.$site_studio_tab)
    }
    validateAppearance() {
        this.appearanceTab.should('have.text', testData.$appearance_tab)
    }
    validateExtend() {
        this.extendTab.should('have.text', testData.$extend_tab)
    }
    validateConfiguration() {
        this.configurationTab.should('have.text', testData.$configuration_tab)
    }
    validatePeople() {
        this.peopleTab.should('have.text', testData.$people_tab)
    }
    validateReports() {
        this.reportsTab.should('have.text', testData.$reports_tab)
    }
    validateTour() {
        this.tourTab.should('have.text', testData.$tour_tab)
    }

}
const toolBar = new ToolBar();
module.exports = toolBar;
