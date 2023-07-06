const testData = require("./TestData")
class MenuTabs {

    //Get Home menu tab
    get homeMenu() {
        return cy.get("body > div.dialog-off-canvas-main-canvas > header > div.coh-container.coh-ce-cpt_site_header-6577ed22 > div.coh-container.coh-ce-cpt_site_header-e2a0ade6 > div > div > nav > ul > li:nth-child(1) > a")
    }
    //Get Articles menu tab
    get articlesMenu() {
        return cy.get("body > div.dialog-off-canvas-main-canvas > header > div.coh-container.coh-ce-cpt_site_header-6577ed22 > div.coh-container.coh-ce-cpt_site_header-e2a0ade6 > div > div > nav > ul > li:nth-child(2) > a")
    }
    //Get Event menu tab
    get eventMenu() {
        return cy.get("body > div.dialog-off-canvas-main-canvas > header > div.coh-container.coh-ce-cpt_site_header-6577ed22 > div.coh-container.coh-ce-cpt_site_header-e2a0ade6 > div > div > nav > ul > li:nth-child(3) > a")
    }
    //Get People menu tab
    get peopleMenu() {
        return cy.get("body > div.dialog-off-canvas-main-canvas > header > div.coh-container.coh-ce-cpt_site_header-6577ed22 > div.coh-container.coh-ce-cpt_site_header-e2a0ade6 > div > div > nav > ul > li:nth-child(4) > a")
    }
    //Get Places menu tab
    get placesMenu() {
        return cy.get("body > div.dialog-off-canvas-main-canvas > header > div.coh-container.coh-ce-cpt_site_header-6577ed22 > div.coh-container.coh-ce-cpt_site_header-e2a0ade6 > div > div > nav > ul > li:nth-child(5) > a")
    }

    //Get primary menu items
    //Get view primary menu
    get viewMenu() {
        return cy.get("#block-tabs-2 > nav > ul > li:nth-child(1) > a")
    }

    //Get scheduled primary menu
    get scheduledMenu() {
        return cy.get("#block-tabs-2 > nav > ul > li:nth-child(2) > a")
    }
    //Get edit primary menu
    get editMenu() {
        return cy.get("#block-acquia-claro-primary-local-tasks > nav > ul > li:nth-child(4) > a")
    }

    //Get clone primary menu
    get cloneMenu() {
        return cy.get("#block-acquia-claro-primary-local-tasks > nav > ul > li:nth-child(6) > a")
    }
    //Get moderation dashboard primary menu
    get moderation_dashboardMenu() {
        return cy.get("#block-acquia-claro-primary-local-tasks > nav > ul > li:nth-child(5) > a")
    }

    //Validate the spellsings for Menu items
    spell_Validations_Menu() {
        this.homeMenu.should('have.text', 'Home')
        this.articlesMenu.should('have.text', 'Articles')
        this.eventMenu.should('have.text', 'Events')
        this.peopleMenu.should('have.text', 'People')
        this.placesMenu.should('have.text', 'Places')
    }

    //Validate menu items are clickable
    click_menu_items() {

        this.homeMenu.click({
            force: true
        })
        cy.url().should('eq', testData.$home_url)

        this.articlesMenu.click({
            force: true
        })
        cy.url().should('eq', testData.$article_url)

        this.eventMenu.click({
            force: true
        })
        cy.url().should('eq', testData.$events_url)

        this.peopleMenu.click({
            force: true
        })
        cy.url().should('eq', testData.$people_url)

        this.placesMenu.click({
            force: true
        })
        cy.url().should('eq', testData.$places_url)

    }
    spell_validations_primary_menu() {
        this.viewMenu.click().should('have.text', "View")
        this.scheduledMenu.click().should('have.text', "Scheduled")
        this.editMenu.click().should('have.text', "Edit")
        this.cloneMenu.click().should('have.text', "Clone")
        this.moderation_dashboardMenu.click().should('have.text', "Moderation Dashboard")
    }

    click_primary_menu_items() {

        this.viewMenu.click()
        cy.url().should('eq', testData.$view_url)
        this.scheduledMenu.click()
        cy.url().should('eq', testData.$scheduled_url)
        this.editMenu.click()
        cy.url().should('eq', testData.$edit_url)
        this.cloneMenu.click()
        cy.url().should('eq', testData.$clone_url)
        this.moderation_dashboardMenu.click()
        cy.url().should('eq', testData.$moderation_dashboard_url)

    }
}
const menuTabs = new MenuTabs();
module.exports = menuTabs;
