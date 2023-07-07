import 'cypress-iframe'
import testData from './TestData'
import content from './Content'

class Article {

    //Article page through mouse hover on admin tool bar
    get articleLink() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(2) > ul > li:nth-child(2) > ul > li:nth-child(1) > a")
    }

    //Article Edit title bar
    get articleEditTitle() {
        return cy.get("#edit-title-0-value")
    }

    //Article body - edit summary
    get articleBodyEdit() {
        return cy.get("#edit-body-wrapper > div > div.js-form-type-textarea.js-form-item.form-item.form-type--textarea.js-form-item-body-0-value.form-item--body-0-value > div")
    }

    //Article format text
    get articleTextFormat() {
        return cy.get("#edit-body-0-format > div.form-item--editor-format.js-form-item.form-item.js-form-type-select.form-type--select.js-form-item-body-0-format.form-item--body-0-format > label")
    }
    get articleTextFormatDropdown() {
        return cy.get("#edit-body-0-format--2")
    }

    //Article author
    get articleAuthor() {
        return cy.get("#edit-field-display-author-wrapper")
    }

    //Article Save as dropdown
    get articleSaveAs() {
        return cy.get("#edit-moderation-state-0 > div")
    }
    get articleSaveAsDropdown() {
        return cy.get("#edit-moderation-state-0-state")
    }

    //Article Save button
    get articleSave() {
        return cy.get("#edit-submit")
    }

    //get created article title
    get createdArticleTitle() {
        return cy.get("body > div.dialog-off-canvas-main-canvas > div.coh-container.coh-style-focusable-content.coh-ce-6f78460f > div > article > div.coh-container.coh-style-padding-top-medium.coh-style-padding-bottom-large.coh-container-boxed > div > div > div.coh-column.coh-style-padding-bottom-small.coh-visible-ps.coh-col-ps-12.coh-visible-sm.coh-col-sm-10.coh-visible-xl.coh-col-xl-8 > h1")
    }

    //get created article type
    get createdArticleType() {
        return cy.get("body > div.dialog-off-canvas-main-canvas > div.coh-container.coh-style-focusable-content.coh-ce-6f78460f > div > article > div.coh-container.coh-style-padding-top-medium.coh-style-padding-bottom-large.coh-container-boxed > div > div > div.coh-column.coh-style-padding-bottom-small.coh-visible-ps.coh-col-ps-12.coh-visible-sm.coh-col-sm-10.coh-visible-xl.coh-col-xl-8 > ul > li:nth-child(1) > a")
    }

    //get created article author
    get createdArticleAuthor() {
        return cy.get("body > div.dialog-off-canvas-main-canvas > div.coh-container.coh-style-focusable-content.coh-ce-6f78460f > div > article > div.coh-container.coh-style-padding-top-medium.coh-style-padding-bottom-large.coh-container-boxed > div > div > div.coh-column.coh-style-padding-bottom-small.coh-visible-ps.coh-col-ps-12.coh-visible-sm.coh-col-sm-10.coh-visible-xl.coh-col-xl-8 > ul > li:nth-child(3) > a")
    }

    //get content of created article
    get createdArticleContent() {
        return cy.get("body > div.dialog-off-canvas-main-canvas > div.coh-container.coh-style-focusable-content.coh-ce-6f78460f > div > article > div.coh-container.coh-style-padding-top-medium.coh-style-padding-bottom-large.coh-container-boxed > div > div > div.coh-column.coh-visible-ps.coh-col-ps-12.coh-visible-sm.coh-col-sm-10.coh-visible-md.coh-col-md-8.coh-visible-xl.coh-col-xl-6 > div:nth-child(1) > p")
    }

    //Click and Verify
    clickAndVerify() {
        //click on article link from mouse hover
        this.articleLink.click({
            force: true
        })
        //title of the article page
        content.pageTitle.should('have.text', 'Create Article')
        //edit title input box should be visible
        this.articleEditTitle.should("be.visible")
        //body edit summary text box should be visible
        this.articleBodyEdit.should("be.visible")
        //text format should be present with dropdown
        this.articleTextFormat.should('have.text', 'Text format')
        this.articleTextFormatDropdown.select('Filtered HTML', {
            force: true
        }).should('have.value', 'filtered_html')
        this.articleTextFormatDropdown.select('Site Studio', {
            force: true
        }).should('have.value', 'cohesion')
        //Display author text box should be visible
        this.articleAuthor.should("be.visible")
        //Save as option and its dropdown should be present
        this.articleSaveAs.should("be.visible")
        this.articleSaveAsDropdown.select('Draft', {
            force: true
        }).should('have.value', 'draft')
        this.articleSaveAsDropdown.select('In review', {
            force: true
        }).should('have.value', 'review')
        this.articleSaveAsDropdown.select('Published', {
            force: true
        }).should('have.value', 'published')
        //Save article button should be present at the bottom
        this.articleSave.should("be.visible")
    }
    //Article - Create
    createArticle() {
        cy.get("#edit-title-0-value").type(testData.$content_title, {
            force: true
        })
        cy.wait(4000)
        cy.get('.ck-editor__main[role="presentation"]').then(($element) => {
            cy.get('.ck-content[contenteditable=true]').then(el => {
                // @ts-ignore
                const editor = el[0].ckeditorInstance
                editor.setData(testData.$content)
            })
        });

        cy.get("#edit-field-display-author-0-target-id").type(testData.$content_author, {
            force: true
        })
        cy.get("#edit-field-categories > option:nth-child(3)").click({
            force: true
        })
        cy.get("#edit-field-article-type").select(testData.$content_type, {
            force: true
        })
        article.articleSaveAsDropdown.select(testData.$publish_save_type, {
            force: true
        })
        article.articleSave.click()
    }
    //Article - Validate
    validateArticle() {
        //Validate the title of the article
        this.createdArticleTitle.should('have.text', " " + testData.$content_title + " ")
        //validate the type of the article
        this.createdArticleType.should('have.text', " " + testData.$content_type + "    ")
        //validate the auther of the article
        this.createdArticleAuthor.should('have.text', " " + testData.$content_author + "    ")
        //Validate the content of the artice
        this.createdArticleContent.should('have.text', testData.$content)
    }
}

const article = new Article();
module.exports = article;
