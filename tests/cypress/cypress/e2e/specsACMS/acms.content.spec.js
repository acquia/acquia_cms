import 'cypress-iframe'
// TC-## are the qTest test case id's
/// <reference types="cypress" />

const content = require("../../pages/Content")
const article = require("../../pages/Article")
const place = require("../../pages/Place")
const event = require("../../pages/Event")
const person = require("../../pages/Person")
const page = require("../../pages/Page")
const extend = require("../../pages/Extend")
const tourPage = require("../../pages/TourPage")


describe("Verify the contents of content page", () => {
/*
    //TC-78
    context("Content page - Contents of the page", () => {
        it("Verify the contents of Content Page", () => {
            content.verify()
        })
    })

    //TC-79
    context("Add Content - Click and verify", () => {
        it("Click on Add content on Content tab from sub-admin tool bar, ", () => {
            content.clickAndVerify()
        })
    })

    //TC-80
    //Article
    context("Article - Click and verify", () => {
        it("Mouse hover on Content tab and navigate to Article link from sub-admin tool bar", () => {
            article.clickAndVerify()
        })
    })

    //Verify and extend required modules
    //Starter Module
    //TODO - Add the test case
    context("Verify and Extend Acquia Starter module", () => {
        it("Extend the Acquia CMS Starter module", () => {
            extend.extendStarterModule()
        })
    })

    //Visual Page Builder Module
    context("Verify and Extend Visule page builder module", () => {
        it("Extend the Visual Page Builder module", () => {
            extend.extendVPBModule()
        })
    })

    //Activate required Services
    //Active tour wizard setup
    context("Verify and setup wizard on the tour page", () => {
        it("Click and verify the tour page wizard to setup manually", () => {
            //extend.extendVPBModule()
            tourPage.setupWizardManually()
        })
    })

    //TC-81
    context("Article - Create Article", () => {
        it("Create, Save and publish the article", () => {
            //click on article link from mouse hover
            article.articleLink.click({
                force: true
            })
            //title of the article page
            content.pageTitle.should('have.text', 'Create Article')
            //create the article, save the article and Publish the artilce
            article.createArticle()
            //Validate the created article
            article.validateArticle()
            //delete the article- generalised method
            content.deleteContent()
        })
    })

    //Place
    //TC-82
    context("Place - Click and verify", () => {
        it("Mouse hover on Content tab and navigate to Place link from sub-admin tool bar", () => {
            place.clickAndVerify()
        })
    })*/
    //TC-83
    context("Place - Create Place", () => {
        it("Create, Save and publish the place", () => {
            //click on article link from mouse hover
            place.placeLink.click({
                force: true
            })
            //title of the article page
            content.pageTitle.should('have.text', 'Create Place')
            //create the article, save the article and Publish the artilce
            place.createPlace()
            //Validate the created article
            place.validatePlace()
            //delete the article- generalised method
            content.deleteContent()
        })
    })
/*
    //Event
    //TC-84
    context("Event - Click and verify", () => {
        it("Mouse hover on Content tab and navigate to Event link from sub-admin tool bar", () => {
            event.clickAndVerify()
        })
    })

    //Create event
    context("Event - Create Event", () => {
        it("Create, Save and publish the Event", () => {
            //click on article link from mouse hover
            event.eventLink.click({
                force: true
            })
            //title of the article page
            content.pageTitle.should('have.text', 'Create Event')
            //create the article, save the article and Publish the artilce
            event.createEvent()
            //Validate the created article
            event.validateEvent()
            //delete the article- generalised method
            content.deleteContent()
        })
    })

    //Page
    context("Page - Click and verify", () => {
        it("Mouse hover on Content tab and navigate to Page link from sub-admin tool bar", () => {
            //Verify all the elements/components are present on create person page
            page.clickAndVerify()
        })
    })

    context("Page - Create Page", () => {
        it("Create, Save and publish the Page", () => {
            //Create the page with layout canvas edit it with Visual page builder
            page.createPageLayoutCanvas()
            //Validate the created page
            page.validateCreatedPage()
            //Delete the created page
            content.deleteContent()
        })
    })

    //person
    context("Person - Click and verify", () => {
        it("Mouse hover on Content tab and navigate to Person link from sub-admin tool bar", () => {
            //Verify all the elements are present on create person page
            person.clickAndVerify()
        })
    })

    context("Person - Create Person", () => {
        it("Create, Save and publish the Person", () => {
            //creating the person
            person.createPerson()
            //validating created person
            person.validateCreatedPerson()
            //deleting created person
            content.deleteContent()
        })
    })*/
})
