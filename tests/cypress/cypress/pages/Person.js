import 'cypress-iframe'
import content from './Content'
const testData = require("./TestData")

class Person {

    // Get person's link through content dropdown.
    get personLink() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(2) > ul > li:nth-child(2) > ul > li:nth-child(4) > a")
    }
    // Get person's name.
    get personName() {
        return cy.get("#edit-title-0-value")
    }
    // Get person's job title.
    get jobTitle() {
        return cy.get("#edit-field-job-title-0-value")
    }
    // Get Bio.
    get personBio() {
        return cy.get("#edit-body-wrapper > div > div.js-form-type-textarea.js-form-item.form-item.form-type--textarea.js-form-item-body-0-value.form-item--body-0-value > label")
    }
    // Get Language dropdown.
    get languageDropdown() {
        return cy.get("#edit-langcode-0-value")
    }
    // Get Add media button.
    get addMedia() {
        return cy.get("#edit-field-person-image-open-button")
    }
    // Select media source.
    get selectedMediaType() {
        return cy.get(".ui-dialog.media-library-widget-modal #drupal-modal #media-library-wrapper #acquia-dam-source-menu-wrapper select").select('core')
    }
    // Select the profile picture.
    get profilePicture() {
        return cy.get('[id*="edit-media-library-select-form-2--"]')
    }
    // Insert the profile picture.
    get insertSelectedButton() {
        return cy.get("body > div.ui-dialog.media-library-widget-modal > div.ui-dialog-buttonpane > div.ui-dialog-buttonset > button")
    }
    // Add place of the person.
    get personPlace() {
        return cy.get("#edit-field-place")
    }
    // Add person type.
    get personType() {
        return cy.get("#edit-field-person-type")
    }
    // Add person's email.
    get personEmail() {
        return cy.get("#edit-field-email-0-value")
    }
    // Add person's telephone.
    get personTelephone() {
        return cy.get("#edit-field-person-telephone-0-value")
    }
    // Save As dropdown.
    get saveAsDropdown() {
        return cy.get("#edit-moderation-state-0-state")
    }
    // Save person.
    get savePerson() {
        return cy.get("#edit-submit")
    }

    // Click and verify.
    clickAndVerify() {
        // Click on person link from mouse hover.
        this.personLink.click({
            force: true
        })
        // Title of the person page.
        content.pageTitle.should('have.text', 'Create Person')
        // Edit name box should be visible.
        this.personName.should("be.visible")
        // Job title box should be visible.
        this.jobTitle.should("be.visible")
        // Bio body should be visible.
        this.personBio.should("be.visible")
        // Language dropdown should be visible.
        this.languageDropdown.should("be.visible")
        // Media Image box and add media button should be visible.
        this.addMedia.should("be.visible")
        // Place dropdown should be visible.
        this.personPlace.should("be.visible")
        // Person type should be visible.
        this.personType.should("be.visible")
        // Email and telephone should be visible.
        this.personEmail.should("be.visible")
        this.personTelephone.should("be.visible")
        // Save as dropdown and save button should be visible.
        this.saveAsDropdown.should("be.visible")
        this.savePerson.should("be.visible")
    }


    // Person - create.
    createPerson() {
        // Click on person link from mouse hover.
        this.personLink.click({
            force: true
        })
        //Input person name.
        this.personName.type(testData.$content_title, {
            force: true
        })
        // Input person's Job title.
        this.jobTitle.type(testData.$job_title, {
            force: true
        })
        cy.wait(4000)
        // Input person's Bio.
        cy.get('.ck-editor__main[role="presentation"]').then(($element) => {
            cy.get('.ck-content[contenteditable=true]').then(el => {
                // @ts-ignore.
                const editor = el[0].ckeditorInstance
                editor.setData(testData.$content)
            })
        });
        // Input preffered languafe for the person.
        this.languageDropdown.select(testData.$language)
        // Add profile photo for the person through media.
        // Click on add media button.
        this.addMedia.click()
        cy.wait(2000)
        cy.scrollTo(0, 1000)
        // Select media source.
        this.selectedMediaType
        // Select the profile picture.
        this.profilePicture.check()
        // Insert the profile picture.
        this.insertSelectedButton.click()
        cy.wait(2000)
        // Select place for the person.
        this.personPlace.select(testData.$event_place)
        // Select Person Type.
        this.personType.select(testData.$person_type)
        // Input the email of the person.
        this.personEmail.type(testData.$person_email)
        this.personTelephone.type(testData.$telephone_number)

        // Save dropdown - select as published.
        this.saveAsDropdown.select(testData.$publish_save_type)
        this.savePerson.click()
    }

    // Get created person's name.
    get createdPersonsName() {
        return cy.get("body article .coh-style-padding-top-bottom-medium .coh-row .coh-column > h1")
    }
    // Get Created person's job title.
    get createdPJobTitle() {
        return cy.get("body article .coh-style-padding-top-bottom-medium .coh-row .coh-column > ul > li:nth-child(1)")
    }
    // Get Created persons offce address, email address and telephone number.
    get createdPOffice() {
        return cy.get("body article .coh-style-padding-top-bottom-medium .coh-row .coh-column > ul > li:nth-child(2) > a")
    }
    get createdPEmail() {
        return cy.get("body article .coh-style-padding-top-bottom-medium .coh-row .coh-column > ul > li:nth-child(3) > a")
    }
    get createdPTelephone() {
        return cy.get("body article .coh-style-padding-top-bottom-medium .coh-row .coh-column > ul > li:nth-child(4) > span")
    }

    // Validation of the cases of created person.
    validateCreatedPerson() {
        cy.wait(2000)
        // Validate created person's name.
        this.createdPersonsName.should('have.text', " " + testData.$content_title + " ")
        // Validate created person's job title.
        this.createdPJobTitle.should('have.text', " " + testData.$job_title + " ")
        // Validate all stationary details.
        this.createdPOffice.should('have.text', " " + testData.$event_place + "    ")
        this.createdPEmail.should('have.text', " " + testData.$person_email + "    ")
        this.createdPTelephone.should('have.text', testData.$telephone_number)
    }
}

const person = new Person()
module.exports = person
