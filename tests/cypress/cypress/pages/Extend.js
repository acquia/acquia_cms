import testData from './TestData'
import utility from './Utility'

class Extend {

    get extendTabLink() {
        return cy.get('#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(6) > a')
    }

    get filterModule() {
        return cy.get('#edit-text')
    }

    get acmsStarterCB() {
        return cy.get('#edit-modules-acquia-cms-starter-enable')
    }

    get vpbCheckBox() {
        return cy.get('#edit-modules-sitestudio-page-builder-enable')
    }

    get acquiaPurgeModule() {
        return cy.get('#module-acquia-purge')
    }

    get acquiaSearchModule() {
        return cy.get('#module-acquia-search')
    }

    get acquiaSASolrModule() {
        return cy.get('#module-search-api-solr')
    }

    get acquiaConnectorModule() {
        return cy.get('#module-acquia-connector')
    }

    get acquiaArticleModule() {
        return cy.get('#module-acquia-cms-article')
    }

    get acquiaAudioModule() {
        return cy.get('#module-acquia-cms-audio')
    }

    get acquiaComponentModule() {
        return cy.get('#module-acquia-cms-component')
    }

    get acquiaDamModule() {
        return cy.get('#module-acquia-cms-dam')
    }

    get acquiaDocumentModule() {
        return cy.get('#module-acquia-cms-document')
    }

    get acquiaEventModule() {
        return cy.get('#module-acquia-cms-event')
    }

    get acquiaHeadlessModule() {
        return cy.get('#module-acquia-cms-headless')
    }

    get acquiaImageModule() {
        return cy.get('#module-acquia-cms-image')
    }

    get acquiaPageModule() {
        return cy.get('#module-acquia-cms-page')
    }

    get acquiaPersonModule() {
        return cy.get('#module-acquia-cms-person')
    }

    get acquiaPlaceModule() {
        return cy.get('#module-acquia-cms-place')
    }

    get acquiaSearchModule() {
        return cy.get('#module-acquia-cms-search')
    }

    get acquiaSiteStudioModule() {
        return cy.get('#module-acquia-cms-site-studio')
    }

    get acquiaToolbarModule() {
        return cy.get('#module-acquia-cms-toolbar')
    }

    get acquiaTourModule() {
        return cy.get('#module-acquia-cms-tour')
    }

    get acquiaCMSVideoModule() {
        return cy.get('#module-acquia-cms-video')
    }

    get checkListApiModule() {
        return cy.get('#module-checklistapi')
    }

    get siteStudioCore() {
        return cy.get('#module-cohesion')
    }

    // Extend Acquia CMS starter module.
    extendStarterModule() {
        // Extend Acquia CMS starter module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("starter")
        cy.wait(500)
        this.acmsStarterCB.check()
        utility.save.click()
        cy.wait(2000)
    }

    // Extend VPB module.
    extendVPBModule() {
        // Extend Acquia CMS starter module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("page Builder")
        cy.wait(500)
        this.vpbCheckBox.check()
        utility.save.click()
        cy.wait(2000)
    }

    // Verify Acquia Purge module.
    extendPurgeModule() {
        // Extend Acquia purge module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("Acquia Purge")
        cy.wait(500)
        this.acquiaPurgeModule.should('have.text', 'Acquia Purge')
    }

    // Verify Acquia Search module.
    extendSearchModule() {
        // Extend Acquia search module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("acquia search")
        cy.wait(500)
        this.acquiaSearchModule.should('have.text', 'Acquia Search')
    }

    // Verify Acquia Search API Solr module.
    extendSASolrModule() {
        // Extend Acquia search api solr module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("search api solr")
        cy.wait(500)
        this.acquiaSASolrModule.should('have.text', 'Search API Solr')
    }

    // Verify Acquia Connector module.
    extendConnectorModule() {
        // Extend Acquia connector module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("connector")
        cy.wait(500)
        this.acquiaConnectorModule.should('have.text', 'Acquia Connector')
    }

    // Verify Acquia Connector module.
    extendArticleModule() {
        // Extend Acquia connector module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("article")
        cy.wait(500)
        this.acquiaArticleModule.should('have.text', 'Acquia CMS Article')
    }

    // Verify Acquia Connector module.
    extendAudioModule() {
        // Extend Acquia connector module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("audio")
        cy.wait(500)
        this.acquiaAudioModule.should('have.text', 'Acquia CMS Audio')
    }

    // Verify Acquia Connector module.
    extendComponentModule() {
        // Extend Acquia connector module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("component")
        cy.wait(500)
        this.acquiaComponentModule.should('have.text', 'Acquia CMS Component')
    }

    // Verify Acquia CMS DAM module.
    extendDamModule() {
        // Extend Acquia CMS DAM module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("DAM")
        cy.wait(500)
        this.acquiaDamModule.should('have.text', 'Acquia CMS DAM')
    }

    // Verify Acquia CMS Document module.
    extendDocumentModule() {
        // Extend Acquia CMS Document module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("document")
        cy.wait(500)
        this.acquiaDocumentModule.should('have.text', 'Acquia CMS Document')
    }

    // Verify Acquia CMS Event module.
    extendEventModule() {
        // Extend Acquia CMS Event module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("event")
        cy.wait(500)
        this.acquiaEventModule.should('have.text', 'Acquia CMS Event')
    }

    // Verify Acquia CMS Headless module.
    extendHeadlessModule() {
        // Extend Acquia CMS Headless module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("headless")
        cy.wait(500)
        this.acquiaHeadlessModule.should('have.text', 'Acquia CMS Headless')
    }

    // Verify Acquia CMS Image module.
    extendImageModule() {
        // Extend Acquia CMS Image module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("image")
        cy.wait(500)
        this.acquiaImageModule.should('have.text', 'Acquia CMS Image')
    }

    // Verify Acquia CMS Page module.
    extendPageModule() {
        // Extend Acquia CMS Page module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("page")
        cy.wait(500)
        this.acquiaPageModule.should('have.text', 'Acquia CMS Page')
    }

    // Verify Acquia CMS Person module.
    extendPersonModule() {
        // Extend Acquia CMS Person module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("person")
        cy.wait(500)
        this.acquiaPersonModule.should('have.text', 'Acquia CMS Person')
    }

    // Verify Acquia CMS Place module.
    extendPlaceModule() {
        // Extend Acquia CMS Place module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("place")
        cy.wait(500)
        this.acquiaPlaceModule.should('have.text', 'Acquia CMS Place')
    }

    // Verify Acquia CMS Search module.
    extendSearchModule() {
        // Extend Acquia CMS Search module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("search")
        cy.wait(500)
        this.acquiaSearchModule.should('have.text', 'Acquia CMS Search')
    }

    // Verify Acquia CMS Site Studio module.
    extendSiteStudioModule() {
        // Extend Acquia CMS Site Studio module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("site studio")
        cy.wait(500)
        this.acquiaSiteStudioModule.should('have.text', 'Acquia CMS Site Studio')
    }

    // Verify Acquia CMS Toolbar module.
    extendToolbarModule() {
       // Extend Acquia CMS Toolbar module.
       this.extendTabLink.click({
           force: true
       })
       this.filterModule.type("Toolbar")
       cy.wait(500)
       this.acquiaToolbarModule.should('have.text', 'Acquia CMS Toolbar')
   }

   // Verify Acquia CMS Tour module.
   extendTourModule() {
      // Extend Acquia CMS Tour module.
      this.extendTabLink.click({
          force: true
      })
      this.filterModule.type("Tour")
      cy.wait(500)
      this.acquiaTourModule.should('have.text', 'Acquia CMS Tour')
  }

     // Verify Acquia CMS Video module.
     extendCMSVideoModule() {
        // Extend Acquia CMS Video module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("CMS video")
        cy.wait(500)
        this.acquiaCMSVideoModule.should('have.text', 'Acquia CMS Video')
    }

    // Verify CheckList API module.
    extendCheckListAPIModule() {
        // Extend CheckList API module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("checklist")
        cy.wait(500)
        this.checkListApiModule.should('have.text', 'Checklist API')
    }

    // Verify Site Studio Core module.
    extendSiteStudioCoreModule() {
        // Extend Site Studio Core module.
        this.extendTabLink.click({
            force: true
        })
        this.filterModule.type("site studio core")
        cy.wait(500)
        this.siteStudioCore.should('have.text', 'Site Studio core')
    }
}

const extend = new Extend();
module.exports = extend;
