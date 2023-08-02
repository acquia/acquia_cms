import 'cypress-iframe'
import testData from './TestData'
import content from './Content'
import utility from './Utility'

class Article {

    // Article page through mouse hover on admin tool bar.
    get articleLink() {
        return cy.get(utility.$addContentMenu + "li:nth-child(1) > a")
    }

    // Article author.
    get articleAuthor() {
        return cy.get("#edit-field-display-author-wrapper")
    }

    // Article Save as dropdown.
    get articleSaveAs() {
        return cy.get("#edit-moderation-state-0 > div")
    }

    // Get created article title.
    $articleSelector = 'body article .coh-style-padding-top-medium > div > div ';
    $articleFieldSelector = '.coh-style-padding-bottom-small ';
    get createdArticleTitle() {
        return cy.get(this.$articleSelector + this.$articleFieldSelector + " h1")
    }

    // Get created article type.
    get createdArticleType() {
        return cy.get(this.$articleSelector + this.$articleFieldSelector + " ul > li:nth-child(1) > a")
    }

    // Get created article author.
    get createdArticleAuthor() {
        return cy.get(this.$articleSelector + this.$articleFieldSelector + " ul > li:nth-child(3) > a")
    }

    // Get content of created article.
    get createdArticleContent() {
        return cy.get(this.$articleSelector + " .coh-column.coh-visible-ps p")
    }

    // Click and Verify.
    clickAndVerify() {
        // Click on article link from mouse hover.
        this.articleLink.click({
            force: true
        })
        // Title of the article page.
        content.pageTitle.should('have.text', 'Create Article')
        // Edit title input box should be visible.
        utility.editTitle.should("be.visible")
        // Body edit summary text box should be visible.
        utility.contentBodyEdit.should("be.visible")
        // Text format should be present with dropdown.
        utility.contentTextFormat.should('have.text', 'Text format')
        utility.textFormatDropdown.select('Filtered HTML', {
            force: true
        }).should('have.value', 'filtered_html')
        utility.textFormatDropdown.select('Site Studio', {
            force: true
        }).should('have.value', 'cohesion')
        // Display author text box should be visible.
        this.articleAuthor.should("be.visible")
        // Save as option and its dropdown should be present.
        this.articleSaveAs.should("be.visible")
        utility.saveAsDropdown.select('Draft', {
            force: true
        }).should('have.value', 'draft')
        utility.saveAsDropdown.select('In review', {
            force: true
        }).should('have.value', 'review')
        utility.saveAsDropdown.select('Published', {
            force: true
        }).should('have.value', 'published')
        // Save article button should be present at the bottom.
        utility.save.should("be.visible")
    }

    // Article - Create.
    createArticle() {
        utility.editTitle.type(testData.$content_title, {
            force: true
        })
        cy.wait(4000)
        cy.get('.ck-editor__main[role="presentation"]').then(($element) => {
            cy.get('.ck-content[contenteditable=true]').then(el => {
                // @ts-ignore.
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
        cy.log(utility.saveAsDropdown)
        utility.saveAsDropdown.select(testData.$publish_save_type, {
            force: true
        })
        utility.save.click()
    }

    // Article - Validate.
    validateArticle() {
        // Validate the title of the article.
        this.createdArticleTitle.should('have.text', " " + testData.$content_title + " ")
        // Validate the type of the article.
        this.createdArticleType.should('have.text', " " + testData.$content_type + "    ")
        // Validate the auther of the article.
        this.createdArticleAuthor.should('have.text', " " + testData.$content_author + "    ")
        // Validate the content of the artice.
        this.createdArticleContent.should('have.text', testData.$content)
    }
}

const article = new Article();
module.exports = article;
