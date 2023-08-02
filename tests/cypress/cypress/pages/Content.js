const testData = require("./TestData")
const utility = require("./Utility")
//import utility from "./Utility"

class Content {
    get contentPage() {
        cy.get("body").then($body => {
            if ($body.find("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(2) > a").length > 0) {
                // Evaluates as true if button exists at all.
                cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(2) > a").then($header => {
                  if ($header.is(':hidden')){
                    cy.get('#toolbar-administration #toolbar-item-administration').click()
                  }
                });
            }
        });
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(2) > a")
    }

    // Get page title.
    get pageTitle() {
        return cy.get("#block-acquia-claro-page-title > h1")
    }

    // Get content tab on the page.
    get contentTab() {
        return cy.get("#block-acquia-claro-primary-local-tasks > nav > ul > li:nth-child(1) > a")
    }

    // Get file tab on the page.
    get fileTab() {
        return cy.get("#block-acquia-claro-primary-local-tasks > nav > ul > li:nth-child(2) > a")
    }

    // Get media tab on the page.
    get mediaTab() {
        return cy.get("#block-acquia-claro-primary-local-tasks > nav > ul > li:nth-child(3) > a")
    }

    // Get name of the tab for assertion.
    get tabName() {
        return cy.get("#block-acquia-claro-primary-local-tasks > nav > ul > li.tabs__tab.js-tab.is-active.js-active-tab > a")
    }

    // Get add content button.
    get addContentButton() {
        return cy.get("#block-acquia-claro-local-actions > ul > li > a")
    }

    // Get search box from filter.
    get searchFilter() {
        return cy.get("#edit-title")
    }

    // Get filter button.
    get filterButton() {
        return cy.get("#edit-submit-content")
    }

    // Get filtered article name- todo (ask akshay).
    get articleName() {
        return cy.get("#views-form-content-page-1 > table.views-table.views-view-table.cols-7.responsive-enabled.sticky-enabled.sticky-table > tbody > tr > td.views-field.views-field-title")
    }

    // Get action dropdown.
    get actionDropdown() {
        return cy.get("#edit-action")
    }

    // Get title bar.
    get titleBar() {
        return cy.get("table.views-view-table thead th#view-title-table-column > a")
    }

    // Navigation buttons at the footer.
    get nextNavButton() {
        return cy.get("#block-acquia-claro-content > div > div > nav > ul > li.pager__item.pager__item--action.pager__item--next > a")
    }

    get lastNavButton() {
        return cy.get("#block-acquia-claro-content > div > div > nav > ul > li.pager__item.pager__item--action.pager__item--last > a")
    }

    // Mouse hover content tab and accessing add content.
    get addContent() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(2) > ul > li:nth-child(1) > a")
    }
    get article() {
        return cy.get("#block-acquia-claro-content > dl > div:nth-child(1) > dt")
    }
    get event() {
        return cy.get("#block-acquia-claro-content > dl > div:nth-child(2) > dt")
    }
    get page() {
        return cy.get("#block-acquia-claro-content > dl > div:nth-child(3) > dt")
    }
    get person() {
        return cy.get("#block-acquia-claro-content > dl > div:nth-child(4) > dt")
    }
    get place() {
        return cy.get("#block-acquia-claro-content > dl > div:nth-child(5) > dt")
    }

    // Delete checkbox.
    get articleToDelete() {
        return cy.get("#views-form-content-page-1 > table.views-table > thead > tr > th.select-all.views-field.views-field-node-bulk-form > input")
    }

    // Verify the content page.
    verify() {
        // Click on the content tab from sub-admin toolbar.
        this.contentPage.click()
        // Content name should be visible by default.
        this.pageTitle.should('have.text', 'Content')
        // Content, Files and Media Tabs are visble with expected name and working.
        this.fileTab.click({
            force: true
        })
        this.tabName.should('have.text', 'Files(active tab)')
        this.mediaTab.click({
            force: true
        })
        this.tabName.should('have.text', 'Media(active tab)')
        this.contentTab.click({
            force: true
        })
        this.tabName.should('have.text', 'Content(active tab)')
        // Add content button should be visible.
        this.addContentButton.should("be.visible")
        // Search for content using filter.
        this.searchFilter.type("Article one", {
            force: true
        })
        this.filterButton.click({
            force: true
        })
        this.contentTab.click({
            force: true
        })
        // Action dropdown should be present with defined values.
        this.actionDropdown.select('Delete content', {
            force: true
        }).should('have.value', 'node_delete_action')
        this.actionDropdown.select('Make content sticky', {
            force: true
        }).should('have.value', 'node_make_sticky_action')
        this.actionDropdown.select('Make content unsticky', {
            force: true
        }).should('have.value', 'node_make_unsticky_action')
        this.actionDropdown.select('Promote content to front page', {
            force: true
        }).should('have.value', 'node_promote_action')
        this.actionDropdown.select('Publish content', {
            force: true
        }).should('have.value', 'node_publish_action')
        this.actionDropdown.select('Save content', {
            force: true
        }).should('have.value', 'node_save_action')
        this.actionDropdown.select('Remove content from front page', {
            force: true
        }).should('have.value', 'node_unpromote_action')
        this.actionDropdown.select('Unpublish content', {
            force: true
        }).should('have.value', 'node_unpublish_action')
        this.actionDropdown.select('Update URL alias', {
            force: true
        }).should('have.value', 'pathauto_update_alias_node')
        // Apply to selected item button should be present.
        utility.save.should("be.visible")
        // Title bar should be present.
        this.titleBar.should('have.text', 'Title')
        // Check for navigation button at the bottom.
        this.nextNavButton.should("be.visible")
        this.lastNavButton.should("be.visible")
    }

    // Click and verify.
    clickAndVerify() {
        // Verifying mouse hover on Content Tab.
        this.contentPage.invoke('show').click()
        // On mouse hover clicking on Add Content and navigating to Add Content page.
        this.addContentButton.click({
            force: true
        })
        // Verifying the Add Content page.
        this.pageTitle.should('have.text', 'Add content')
        this.article.should('contain', 'Article')
        this.event.should('contain', 'Event')
        this.page.should('contain', 'Page')
        this.person.should('contain', 'Person')
        this.place.should('contain', 'Place')
    }

    // Delete content.
    deleteContent() {
        // Go to all content.
        this.contentPage.click()
        // Search for newly created content.
        this.searchFilter.type(testData.$content_title, {
            force: true
        })
        // Filter with the newly created content name.
        this.filterButton.click({
            force: true
        })
        // Click the checkbox to delete.
        this.articleToDelete.check()
        // Select actions from action dropdown as delete.
        this.actionDropdown.select('Delete content', {
            force: true
        }).should('have.value', 'node_delete_action')
        // Click apply button to delete.
        utility.save.click({
            force: true
        })
        // Confirm delete.
        utility.save.click({
            force: true
        })
    }

}


const content = new Content();
module.exports = content;
