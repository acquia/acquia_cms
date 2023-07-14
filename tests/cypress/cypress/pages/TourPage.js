const testData = require("./TestData")
class TourPage {

    // Get Tour Page Link.
    get tourPageLink() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(10) > a")
    }
    // Get heading of the tour page.
    get headingTourPage() {
        return cy.get("#block-acquia-claro-page-title > h1")
    }

    // Get Started page contents.

    // First Timer PopUp.
    get titlePopup() {
        return cy.get('[id*="edit-tour-dashboard--"]')
    }
    get getStartedPopUp() {
        return cy.get("body > div.ui-dialog.ui-corner-all.ui-widget.ui-widget-content.ui-front.acms-welcome-modal.ui-dialog-buttons > div.ui-dialog-buttonpane.ui-widget-content.ui-helper-clearfix > div > button.button.button--primary.button.js-form-submit.form-submit.ui-button.ui-corner-all.ui-widget")
    }

    // Close the popup.
    get closePopUp() {
      cy.get("body").then($body => {
        if ($body.find("div.ui-dialog.acms-welcome-modal > div.ui-dialog-titlebar > button > span.ui-icon-closethick").length > 0) {
          cy.get("div.ui-dialog.acms-welcome-modal > div.ui-dialog-titlebar > button > span.ui-icon-closethick").then($popup => {
            if ($popup.is(':visible')){
              cy.get("body > div.ui-dialog.acms-welcome-modal > div.ui-dialog-titlebar > button > span.ui-icon-closethick").click()
            }
          })
        }
      })
    }

    // Close wizard popup.
    get wizardClose() {
      cy.get("body").then($body => {
        if ($body.find("div.ui-dialog.acms-installation-wizard > div.ui-dialog-titlebar > button > span.ui-icon-closethick").length > 0) {
          cy.get("div.ui-dialog.acms-installation-wizard > div.ui-dialog-titlebar > button > span.ui-icon-closethick").then($popup => {
            if ($popup.is(':visible')){
              cy.get("body > div.ui-dialog.acms-installation-wizard > div.ui-dialog-titlebar > button > span.ui-icon-closethick").click()
            }
          })
        }
      })
    }

    // Get Heading of the page.
    get headingGetStarted() {
        return cy.get("#block-acquia-claro-page-title > h1")
    }
    // Wizard setup button.
    get wizardSetupButton() {
        return cy.get("#block-acquia-claro-content > div.section-top > div.wizard > a")
    }
    // Get Progress bar.
    get progressBar() {
        return cy.get("#block-acquia-claro-content > div.tour-checklist > div.progress__track > div")
    }

    // Get site studio core summary.
    get siteStudioCore() {
        return cy.get("#edit-cohesion > summary")
    }
    get textBoxApiKey() {
        return cy.get("#edit-api-key--2")
    }
    get textBoxAgencyKey() {
        return cy.get("#edit-agency-key")
    }
    get saveButtonSSC() {
        return cy.get("#edit-submit--8")
    }
    get ignoreButtonSSC() {
        return cy.get("#edit-ignore--8")
    }
    get advancedDescriptionSSC() {
        return cy.get("#edit-cohesion > div > div.dashboard-buttons-wrapper > div > a")
    }
    get advancedDescriptionIconSSC() {
        return cy.get("#edit-cohesion > div > div.dashboard-buttons-wrapper > div > b")
    }

    // Get Acquia Connector.
    get acquiaConnectorSummary() {
        return cy.get("#edit-acquia-connector > summary")
    }
    get saveButtonAC() {
        return cy.get("#edit-submit--7")
    }
    get ignoreButtonAC() {
        return cy.get("#edit-ignore--7")
    }
    get advancedDescriptionAC() {
        return cy.get("#edit-acquia-connector > div > div.dashboard-buttons-wrapper > div > a")
    }
    get advancedDescriptionIconAC() {
        return cy.get("#edit-acquia-connector > div > div.dashboard-buttons-wrapper > div > b")
    }

    // Acquia search.
    get acquiaSearchSummary() {
        return cy.get("#edit-acquia-search > summary")
    }
    get textBoxAcquiaSubscriptionIdentifier() {
        return cy.get("#edit-identifier")
    }
    get saveButtonAS() {
        return cy.get("#edit-submit--3")
    }
    get ignoreButtonAS() {
        return cy.get("#edit-ignore--3")
    }
    get advancedDescriptionAS() {
        return cy.get("#edit-acquia-search > div > div.dashboard-buttons-wrapper > div > a")
    }
    get advancedDescriptionIconAS() {
        return cy.get("#edit-acquia-search > div > div.dashboard-buttons-wrapper > div > b")
    }
    get acquiaConnectorKey() {
        return cy.get("#edit-api-key")
    }
    get searchApiHostname() {
        return cy.get("#edit-api-host")
    }
    get applicationUUID() {
        return cy.get("#edit-uuid")
    }

    // Geocoder.
    get geocoderSummary() {
        return cy.get("#edit-geocoder > summary")
    }
    get mapsApiKey() {
        return cy.get("#edit-maps-api-key")
    }
    get saveButtonG() {
        return cy.get("#edit-submit--2")
    }
    get ignoreButtonG() {
        return cy.get("#edit-ignore--2")
    }
    get advancedDescriptionG() {
        return cy.get("#edit-geocoder > div > div.dashboard-buttons-wrapper > div > a")
    }
    get advancedDescriptionIconG() {
        return cy.get("#edit-geocoder > div > div.dashboard-buttons-wrapper > div > b")
    }

    // reCAPTCHA.
    get reCapchaSummary() {
        return cy.get("#edit-recaptcha > summary")
    }
    get siteKey() {
        return cy.get("#edit-site-key")
    }
    get secretKey() {
        return cy.get("#edit-secret-key")
    }
    get saveButtonRC() {
        return cy.get("#edit-submit--6")
    }
    get ignoreButtonRC() {
        return cy.get("#edit-ignore--6")
    }
    get advancedDescriptionRC() {
        return cy.get("#edit-recaptcha > div > div.dashboard-buttons-wrapper > div > a")
    }
    get advancedDescriptionIconRC() {
        return cy.get("#edit-recaptcha > div > div.dashboard-buttons-wrapper > div > b")
    }

    // Google Tag Manager.
    get googleTagManagerSummary() {
        return cy.get("#edit-google-tag > summary")
    }
    get snippetParentURI() {
        return cy.get("#edit-snippet-parent-uri")
    }
    get saveButtonGTM() {
        return cy.get("#edit-submit--5")
    }
    get ignoreButtonGTM() {
        return cy.get("#edit-ignore--5")
    }
    get advancedDescriptionGTM() {
        return cy.get("#edit-google-tag > div > div.dashboard-buttons-wrapper > div > a")
    }
    get advancedDescriptionIconGTM() {
        return cy.get("#edit-google-tag > div > div.dashboard-buttons-wrapper > div > b")
    }

    // Acquia Telemetry.
    get acquiaTelemetrySummary() {
        return cy.get("#edit-acquia-telemetry > summary")
    }
    get anonymousDataOptIn() {
        return cy.get("#edit-opt-in")
    }
    get saveButtonAT() {
        return cy.get("#edit-submit")
    }
    get ignoreButtonAT() {
        return cy.get("#edit-ignore")
    }

    // Wizard Setup.
    get wizardHeading() {
        return cy.get("#ui-id-2")
        // return cy.xpath("//*[@id=\"ui-id-2\"]").
    }
    get wizardSaveButton() {
        return cy.get("body > div.ui-dialog.acms-installation-wizard > .ui-dialog-buttonpane > .ui-dialog-buttonset > button:nth-child(2)")
    }
    get wizardSkipStepButton() {
        return cy.get("body > div.ui-dialog.acms-installation-wizard > .ui-dialog-buttonpane > .ui-dialog-buttonset > button:nth-child(1)")
    }
    get getStartedWithWizard(){
        cy.get("body > div.ui-dialog.acms-welcome-modal > div.ui-dialog-buttonpane > div > button.button--primary.form-submit").click()
    }
    get setupManually(){
        return cy.get("body > div.ui-dialog.ui-corner-all.ui-widget.ui-widget-content.ui-front.acms-welcome-modal.ui-dialog-buttons > div.ui-dialog-buttonpane.ui-widget-content.ui-helper-clearfix > div > button.setup-manually.button.js-form-submit.form-submit.ui-button.ui-corner-all.ui-widget")
    }

    // Click on setup wizard manually.
    setupWizardManually() {
        this.tourPageLink.click()
        this.closePopUp
        this.wizardClose
        this.wizardSetupButton.click()
    }

    // Validate Contents of the tour page.
    validateTourPageContents() {
        this.tourPageLink.click()
        this.headingTourPage.should('have.text', testData.$heading_tour_page)
    }

    // Validate contents of the get started page.
    validateGetStartedPage() {
        this.tourPageLink.click()
        cy.wait(1000)
        this.wizardClose.click()
        this.headingGetStarted.should('have.text', testData.$heading_get_started)
        this.wizardSetupButton.should('be.visible').and('have.text', 'Wizard set-up')
        this.progressBar.should('be.visible')

        // Site Studio Core.
        this.siteStudioCore.click({
            force: true
        }).should('have.text', 'Site Studio core')
        this.textBoxApiKey.should('be.visible')
        this.textBoxAgencyKey.should('be.visible')
        this.saveButtonSSC.should('be.visible')
        this.ignoreButtonSSC.should('be.visible')
        this.advancedDescriptionSSC.should('be.visible')
        this.advancedDescriptionIconSSC.should('be.visible')
        this.siteStudioCore.click()

        // Acquia Connector.
        this.acquiaConnectorSummary.click({
            force: true
        }).should('have.text', 'Acquia Connector')
        this.saveButtonAC.should('be.visible')
        this.ignoreButtonAC.should('be.visible')
        this.advancedDescriptionAC.should('be.visible')
        this.advancedDescriptionIconAC.should('be.visible')
        this.acquiaConnectorSummary.click({
            force: true
        })

        // Acquia Search.
        this.acquiaSearchSummary.click({
            force: true
        }).should('have.text', 'Acquia Search')
        this.textBoxAcquiaSubscriptionIdentifier.should('be.visible')
        this.acquiaConnectorKey.should('be.visible')
        this.searchApiHostname.should('be.visible')
        this.applicationUUID.should('be.visible')
        this.saveButtonAS.should('be.visible')
        this.ignoreButtonAS.should('be.visible')
        this.advancedDescriptionAS.should('be.visible')
        this.advancedDescriptionIconAS.should('be.visible')
        this.acquiaSearchSummary.click({
            force: true
        })

        // Geocoder.
        this.geocoderSummary.click({
            force: true
        }).should('have.text', 'Geocoder')
        this.mapsApiKey.should('be.visible')
        this.saveButtonG.should('be.visible')
        this.ignoreButtonG.should('be.visible')
        this.advancedDescriptionG.should('be.visible')
        this.advancedDescriptionIconG.should('be.visible')
        this.geocoderSummary.click({
            force: true
        })

        // reCaptcha.
        this.reCapchaSummary.click({
            force: true
        }).should('have.text', 'reCAPTCHA')
        this.siteKey.should('be.visible')
        this.secretKey.should('be.visible')
        this.saveButtonRC.should('be.visible')
        this.ignoreButtonRC.should('be.visible')
        this.advancedDescriptionRC.should('be.visible')
        this.advancedDescriptionIconRC.should('be.visible')
        this.reCapchaSummary.click({
            force: true
        })

        // Google Tag Manager.
        this.googleTagManagerSummary.click({
            force: true
        }).should('have.text', 'Google Tag Manager')
        this.snippetParentURI.should('be.visible')
        this.saveButtonGTM.should('be.visible')
        this.ignoreButtonGTM.should('be.visible')
        this.advancedDescriptionGTM.should('be.visible')
        this.advancedDescriptionIconGTM.should('be.visible')
        this.googleTagManagerSummary.click({
            force: true
        })

        // Acquia Telemetry.
        this.acquiaTelemetrySummary.click({
            force: true
        }).should('have.text', 'Acquia Telemetry')
        this.anonymousDataOptIn.should('be.visible')
        this.saveButtonAT.should('be.visible')
        this.ignoreButtonAT.should('be.visible')
        this.acquiaTelemetrySummary.click({
            force: true
        })
    }

    // Wizard validations.
    wizardValidations() {
        this.tourPageLink.click()
        cy.wait(1000)
        this.closePopUp
        this.wizardClose
        this.wizardSetupButton.should('have.text', 'Wizard set-up')
        this.wizardSetupButton.click()
        cy.wait(4000)
        this.getStartedWithWizard
        cy.wait(2000)
        this.wizardSaveButton.should('be.visible')
        this.wizardSkipStepButton.should('be.visible').and('have.text', 'Skip this step')
        this.wizardClose

    }
}
const tourPage = new TourPage();
module.exports = tourPage;
