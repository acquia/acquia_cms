import 'cypress-iframe'
import testData from './TestData'
import content from './Content'
class Place {
    // Place page through mouse hover on admin tool bar.
    get placeLink() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(2) > ul > li:nth-child(2) > ul > li:nth-child(5) > a")
    }
    // Place Edit title bar.
    get placeEditTitle() {
        return cy.get("#edit-title-0-value")
    }
    // Place body - edit summary.
    get placeBodyEdit() {
        return cy.get("#edit-body-wrapper > div > div.js-form-type-textarea.js-form-item.form-item.form-type--textarea.js-form-item-body-0-value.form-item--body-0-value > div")
    }
    // Place format text.
    get placeTextFormat() {
        return cy.get("#edit-body-0-format > div.form-item--editor-format.js-form-item.form-item.js-form-type-select.form-type--select.js-form-item-body-0-format.form-item--body-0-format > label")
    }
    get placeTextFormatDropdown() {
        return cy.get("#edit-body-0-format--2")
    }
    // Country selector box.
    get placeSelectCountry() {
        return cy.get("#edit-field-place-address-0-address-country-code--2")
    }
    // First name and last name text boxes.
    get placeFirstName() {
        return cy.get("#edit-field-place-address-0-address-given-name")
    }
    get placeLastName() {
        return cy.get("#edit-field-place-address-0-address-family-name")
    }
    // Company name text box.
    get placeCompanyName() {
        return cy.get("#edit-field-place-address-0-address-organization")
    }
    // Street address text box.
    get placeStreetAddressL1() {
        return cy.get("#edit-field-place-address-0-address-address-line1")
    }
    get placeStreetAddressL2() {
        return cy.get("#edit-field-place-address-0-address-address-line2")
    }
    // City text box.
    get placeCity() {
        return cy.get('[id*="edit-field-place-address-0-address-locality"]')
    }
    // Potal code Checkbox.
    get placePostalCode() {
        return cy.get('[id*="edit-field-place-address-0-address-postal-code"]')
    }

    // Geofield, Longitude  and lattitude.
    get placeLongitude() {
        return cy.get("#edit-field-geofield-0-value-lon")
    }
    get placeLatitude() {
        return cy.get("#edit-field-geofield-0-value-lat")
    }
    // Telephone Box.
    get placeTelephone() {
        return cy.get("#edit-field-place-telephone-0-value")
    }
    // PlaceType.
    get placeType() {
        return cy.get("#edit-field-place-type")
    }
    // Place Save as dropdown.
    get placeSaveAs() {
        return cy.get("#edit-moderation-state-0 > div")
    }
    get placeSaveAsDropdown() {
        return cy.get("#edit-moderation-state-0-state")
    }
    // Place save button.
    get placeSave() {
        return cy.get("#edit-submit")
    }
    // Selecting media for place.
    get placeInsertMediaButton() {
        return cy.get("#edit-field-place-image-open-button")
    }
    get placeCheckMedia() {
        return cy.get('[id*="edit-media-library-select-form-2--"]')
    }
    get insertSelectedButton() {
        return cy.get("body > div.ui-dialog.media-library-widget-modal > div.ui-dialog-buttonpane > div.ui-dialog-buttonset > button")
    }
    // Select media source.
    get selectedMediaType() {
        return cy.get(".ui-dialog.media-library-widget-modal #drupal-modal #media-library-wrapper #acquia-dam-source-menu-wrapper select").select('core')
    }

    // Select state.
    get placeState() {
        return cy.get('[id*="edit-field-place-address-0-address-administrative-area--"]')
    }



    // Click and verify.
    clickAndVerify() {
        // Click on place link from mouse hover.
        this.placeLink.click({
            force: true
        })
        // Title of the place page.
        content.pageTitle.should('have.text', 'Create Place')
        // Edit title input box should be visible.
        this.placeEditTitle.should("be.visible")
        // Body edit summary text box should be visible.
        this.placeBodyEdit.should("be.visible")
        // Text format should be present with dropdown.
        this.placeTextFormat.should('have.text', 'Text format')
        this.placeTextFormatDropdown.select('Filtered HTML', {
            force: true
        }).should('have.value', 'filtered_html')
        this.placeTextFormatDropdown.select('Site Studio', {
            force: true
        }).should('have.value', 'cohesion')
        // Country selector box should be visible.
        this.placeSelectCountry.should("be.visible")
        // Adress of place, First name last name of the individual.
        this.placeFirstName.should("be.visible")
        this.placeLastName.should("be.visible")
        // Company name of the individual.
        this.placeCompanyName.should("be.visible")
        // Adress of the location.
        this.placeStreetAddressL1.should("be.visible")
        this.placeStreetAddressL2.should("be.visible")
        cy.wait(500)
        this.placeCity.should("be.visible")
        this.placePostalCode.should("be.visible")
        // Place.placeState.should("be.visible").
        this.placeLongitude.should("be.visible")
        this.placeLatitude.should("be.visible")
        // Contact Number.
        this.placeTelephone.should("be.visible")
        // Save as option and its dropdown should be present.
        this.placeSaveAs.should("be.visible")
        this.placeSaveAsDropdown.select('Draft', {
            force: true
        }).should('have.value', 'draft')
        this.placeSaveAsDropdown.select('In review', {
            force: true
        }).should('have.value', 'review')
        this.placeSaveAsDropdown.select('Published', {
            force: true
        }).should('have.value', 'published')
        // Save article button should be present at the bottom.
        this.placeSave.should("be.visible")
    }

    // Place - Create.
    createPlace() {
        // Input place title.
        this.placeEditTitle.type(testData.$content_title, {
            force: true
        })
        // Input place's description.
        cy.wait(4000)
        cy.get('.ck-editor__main[role="presentation"]').then(($element) => {
            cy.get('.ck-content[contenteditable=true]').then(el => {
                // @ts-ignore.
                const editor = el[0].ckeditorInstance
                editor.setData(testData.$content)
            })
        });
        // Input first name and last name.
        this.placeFirstName.type(testData.$first_name, {
            force: true
        })
        this.placeLastName.type(testData.$last_name, {
            force: true
        })
        // Input Company Name and adress.
        place.placeCompanyName.type(testData.$company_name, {
            force: true
        })
        place.placeStreetAddressL1.type(testData.$adress_line_01, {
            force: true
        })
        place.placeStreetAddressL2.type(testData.$adress_line_02, {
            force: true
        })
        // Input Lattitude and longitude for the map.
        this.placeLongitude.type(testData.$longitude, {
            force: true
        })
        this.placeLatitude.type(testData.$latitude, {
            force: true
        })
        // Input telephone Number.
        this.placeTelephone.type(testData.$telephone_number, {
            force: true
        })
        // Input place type.
        this.placeType.select(testData.$place_type, {
            force: true
        })
        // Input Country.
        this.placeSelectCountry.select(testData.$country, {
            force: true
        })
        cy.wait(2000)
        // Input Postal Code.
        this.placePostalCode.type(testData.$postal_code, {
            force: true
        })
        cy.wait(500)
        // Input city.
        this.placeCity.type(testData.$city, {
            force: true
        })
        cy.wait(500)
        // Input state.
        this.placeState.select(testData.$state, {
            force: true
        })
        // Select Media Image for the place.
        this.placeInsertMediaButton.click({
            force: true
        })
        cy.wait(2000)
        cy.scrollTo(0, 500)
        // Select media source.
        this.selectedMediaType
        this.placeCheckMedia.check()
        this.insertSelectedButton.click()
        cy.wait(2000)
        // Select Saving Type.
        this.placeSaveAsDropdown.select('Published')
        // Save the place.
        this.placeSave.click()
    }

    // Fetch name of created place's title.
    get fetchCreatedTitle() {
        return cy.get("body article .coh-style-padding-top-bottom-medium .coh-row .coh-column h1")
    }
    // Fetch type of the place.
    get fetchPlaceType() {
        return cy.get("body article .coh-style-padding-top-bottom-medium .coh-row .coh-column:nth-child(1)  > ul > li:nth-child(1) > a")
    }
    // Fetch place's city.
    get fetchCity() {
        return cy.get("body article .coh-style-padding-top-bottom-medium .coh-row .coh-column:nth-child(1) > ul > li:nth-child(2) > span")
    }
    // Fetch content of the place.
    get fetchContent() {
        return cy.get("body article .coh-style-padding-top-bottom-medium .coh-row .coh-column:nth-child(3) > div > p")
    }
    // Place - Validate.
    validatePlace() {
        // Validate the title of the place.
        this.fetchCreatedTitle.should('have.text', " " + testData.$content_title + " ")
        // Validate the type of the place.
        this.fetchPlaceType.should('have.text', " " + testData.$place_type + "    ")
        // Validate the auther of the place.
        this.fetchCity.should('have.text', testData.$city)
        // Validate the content of the place.
        this.fetchContent.should('have.text', testData.$content)
    }

}
const place = new Place();
module.exports = place;
