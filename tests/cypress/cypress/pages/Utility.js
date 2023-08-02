class Utility {

    // The save, submit button.
    get save() {
        return cy.get("#edit-submit")
    }

    // Save As dropdown.
    get saveAsDropdown() {
        return cy.get("#edit-moderation-state-0-state")
    }

    // Edit title of content.
    get editTitle() {
        return cy.get("#edit-title-0-value")
    }

    // Text format selector.
    get textFormatDropdown() {
        return cy.get("#edit-body-0-format--2")
    }

    // Content body - edit summary.
    get contentBodyEdit() {
        return cy.get("#edit-body-wrapper .form-item--body-0-value > div")
    }

    // Content format text.
    get contentTextFormat() {
        return cy.get("#edit-body-0-format .form-item--body-0-format > label")
    }

    // Get language for the content.
    get contentLanguageSelect() {
        return cy.get("#edit-langcode-0-value")
    }

    // Select media source.
    get selectedMediaType() {
        return cy.get(".ui-dialog.media-library-widget-modal #drupal-modal #media-library-wrapper #acquia-dam-source-menu-wrapper select").select('core')
    }

    // Select media.
    get selectMedia() {
        return cy.get('[id*="edit-media-library-select-form-2--"]')
    }

    // Insert selected media.
    get insertSelectedMedia() {
        return cy.get("body > div.ui-dialog.media-library-widget-modal > div.ui-dialog-buttonpane > div.ui-dialog-buttonset > button")
    }

    // Add content menu.
    $addContentMenu = '#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(2) > ul > li:nth-child(2) > ul > '

}

const utility = new Utility();
module.exports = utility;
