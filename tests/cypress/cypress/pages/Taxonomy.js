import 'cypress-iframe'
import utility from './Utility'
const testData = require("./TestData")

class Taxonomy{
    // Get Taxonomy link.
    get taxonomyLink(){
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(3) > ul > li:nth-child(6) > a")
    }

    // Get add vocabulary button.
    get addVocabularyButton(){
        return cy.get("#block-acquia-claro-local-actions > ul > li > a")
    }

    // Get the name text field.
    get vocabName(){
        return cy.get("#edit-name")
    }

    // Get the description field.
    get vocabDescription(){
        return cy.get("#edit-description")
    }

    // Get Newly added vocabulary.
    get newlyAddedVocab(){
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(3) > ul > li:nth-child(6) > ul > li:nth-child(7) > a")
    }

    // Get delete link for added vocab.
    get deleteAddedVocab(){
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(3) > ul > li:nth-child(6) > ul > li:nth-child(7) > ul > li:nth-child(6) > a")
    }

    // Get link to add the term to the vocab.
    get addTermButton(){
        return cy.get("#block-acquia-claro-local-actions > ul > li > a")
    }

    // Get text box to add name to term.
    get termName(){
        return cy.get("#edit-name-0-value")
    }

    // Get delete link for the term.
    get deleteTermLink(){
        return cy.get("#taxonomy-overview-terms #taxonomy li.delete > a")
    }

    // Get newly added term to vocab.
    get newlyAddedTerm(){
        return cy.get('[id*="edit-terms-"]').first()
    }

    // Delete button to delete term.
    get deleteButtonTerm(){
        return cy.get(".ui-dialog .ui-dialog-buttonpane .ui-dialog-buttonset .button--primary")
    }

    // Create the vocabulary in the taxonomy.
    addVocabulary(){
        this.taxonomyLink.click({force:true})
        this.addVocabularyButton.click({force:true})
        // Name of the vocabulary.
        this.vocabName.type(testData.$vocab_name)
        cy.wait(500)
        // Description of the vocabulary.
        this.vocabDescription.type(testData.$vocab_description)
        // Save the Vocabulary.
        utility.save.click()
    }

    // Validate added vocabulary.
    validateAddedVocabulary(){
        this.newlyAddedVocab.should('have.text',testData.$vocab_name)
    }

    // Add term to vocabulary.
    termToVocab(){
        this.addVocabulary()
        this.addTermButton.click()
        this.termName.type(testData.$term_name)
        cy.wait(4000)
        cy.get('.ck-editor__main[role="presentation"]').then(($element) => {
            cy.get('.ck-content[contenteditable=true]').then(el => {
                // @ts-ignore.
                const editor = el[0].ckeditorInstance
                editor.setData(testData.$term_description)
            })
        });
        utility.save.click()
    }

    // Validate term added to vocabulary.
    validateAddedTerm(){
        this.newlyAddedVocab.click({force:true})
        this.newlyAddedTerm.should('have.text',testData.$term_name)
    }

    // Delete tem from vocabulary.
    deleteTerm(){
        this.deleteTermLink.click({force:true})
        this.deleteButtonTerm.click()
        cy.get(".messages-list .messages--status .messages__content").contains('Deleted term QA_term.')
    }

    // Delete vocabulary.
    deleteVocab(){
        this.deleteAddedVocab.click({force:true})
        utility.save.click()
        cy.get('.messages-list .messages--status .messages__content').contains("Your styles have been updated. Deleted vocabulary QA_Test_Vocab.")
    }

}

const taxonomy = new Taxonomy();
module.exports = taxonomy;
