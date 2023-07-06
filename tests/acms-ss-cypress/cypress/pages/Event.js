import 'cypress-iframe'
import content from './Content'
import testData from './TestData'
class Event {

    //Place page through mouse hover on admin tool bar
    get eventLink() {
        return cy.get("#toolbar-item-administration-tray > nav > div.toolbar-menu-administration > ul > li:nth-child(2) > ul > li:nth-child(2) > ul > li:nth-child(2) > a")
    }
    //get page title
    get eventEditTitle() {
        return cy.get("#edit-title-0-value")
    }
    //get HTML edit box
    get eventBodyEdit() {
        return cy.get("#edit-body-wrapper > div > div.js-form-type-textarea.js-form-item.form-item.form-type--textarea.js-form-item-body-0-value.form-item--body-0-value > div")
    }
    //get the format of editing
    get eventTextFormat() {
        return cy.get("#edit-body-0-format > div.form-item--editor-format.js-form-item.form-item.js-form-type-select.form-type--select.js-form-item-body-0-format.form-item--body-0-format > label")
    }
    //get the format of editing, dropdown
    get eventTextFormatDropdown() {
        return cy.get("#edit-body-0-format--2")
    }

    //get start and end date/time
    get startDate() {
        return cy.get("#edit-field-event-start-0-value-date")
    }

    get startTime() {
        return cy.get("#edit-field-event-start-0-value-time")
    }

    get endDate() {
        return cy.get("#edit-field-event-end-0-value-date")
    }

    get endTime() {
        return cy.get("#edit-field-event-end-0-value-time")
    }
    //get door date
    get doorDate() {
        return cy.get("#edit-field-door-time-0-value-date")
    }
    get doorTime() {
        return cy.get("#edit-field-door-time-0-value-time")
    }

    //get event duration
    get eventDuration() {
        return cy.get("#edit-field-event-duration-0-value")
    }
    //get place of event
    get eventPlace() {
        return cy.get("#edit-field-event-place")
    }
    //get tags of the event
    get eventTags() {
        return cy.get("#edit-field-tags-target-id")
    }
    //get type of the event
    get eventType() {
        return cy.get("#edit-field-event-type")
    }
    //get language for the event
    get eventLanguageSelect() {
        return cy.get("#edit-langcode-0-value")
    }
    //Save as dropdown
    get eventSaveAsDropdown() {
        return cy.get("#edit-moderation-state-0-state")
    }
    //save the event button
    get eventSave() {
        return cy.get("#edit-submit")
    }

    //Validation
    //Fetch created event title
    get fetchCreatedTitle() {
        return cy.get("body > div.dialog-off-canvas-main-canvas > div.coh-container.coh-style-focusable-content.coh-ce-6f78460f > div > article > div.coh-container.coh-style-padding-top-medium.coh-style-padding-bottom-large.coh-container-boxed > div:nth-child(1) > div > div > h1")
    }
    //Fetch place of the event
    get fetchPlaceEvent() {
        return cy.get('body > div.dialog-off-canvas-main-canvas > div.coh-container.coh-style-focusable-content.coh-ce-6f78460f > div > article > div.coh-container.coh-style-padding-top-medium.coh-style-padding-bottom-large.coh-container-boxed > div:nth-child(1) > div > div > ul > li:nth-child(2) > a')
    }
    //Fetch the inputed and published description
    get fetchDescription() {
        return cy.get('body > div.dialog-off-canvas-main-canvas > div.coh-container.coh-style-focusable-content.coh-ce-6f78460f > div > article > div.coh-container.coh-style-padding-top-medium.coh-style-padding-bottom-large.coh-container-boxed > div:nth-child(2) > div > div.coh-column.coh-visible-ps.coh-col-ps-12.coh-col-ps-push-0.coh-col-ps-pull-0.coh-col-ps-offset-0.coh-visible-md.coh-col-md-7.coh-col-md-push-0.coh-visible-xl.coh-col-xl-6.coh-col-xl-push-1 > div > p')
    }

    //Click and verify the creation page of events
    clickAndVerify() {
        //click on event link from mouse hover
        this.eventLink.click({
            force: true
        })
        //title of the event page
        content.pageTitle.should('have.text', 'Create Event')
        //edit title input box should be visible
        this.eventEditTitle.should("be.visible")
        //body edit summary text box should be visible
        this.eventBodyEdit.should("be.visible")
        //text format should be present with dropdown
        this.eventTextFormat.should('have.text', 'Text format')
        this.eventTextFormatDropdown.select('Filtered HTML', {
            force: true
        }).should('have.value', 'filtered_html')
        this.eventTextFormatDropdown.select('Site Studio', {
            force: true
        }).should('have.value', 'cohesion')
        //Event duration
        this.eventDuration.should("be.visible")
        //place of the event
        this.eventPlace.should("be.visible")
        //event tags should be visible
        this.eventTags.should("be.visible")
        //event type should be visible
        this.eventType.should("be.visible")
        //check the options of event type tab
        this.eventType.select('- None -', {
            force: true
        })
        this.eventType.select('Conference', {
            force: true
        })
        this.eventType.select('Meet-up', {
            force: true
        })
        this.eventType.select('Webinar', {
            force: true
        })
        this.eventType.select('Workshop', {
            force: true
        })
        //select the language of the event
        this.eventLanguageSelect.should("be.visible")
        this.eventLanguageSelect.select('English', {
            force: true
        }).should('have.value', 'en')
        //Save as option and its dropdown should be present
        this.eventSaveAsDropdown.should("be.visible")
        this.eventSaveAsDropdown.select('Draft', {
            force: true
        }).should('have.value', 'draft')
        this.eventSaveAsDropdown.select('In review', {
            force: true
        }).should('have.value', 'review')
        this.eventSaveAsDropdown.select('Published', {
            force: true
        }).should('have.value', 'published')
        //Save article button should be present at the bottom
        this.eventSave.should("be.visible")
    }

    //create event
    createEvent() {
        //Name of the event
        this.eventEditTitle.type(testData.$content_title, {
            force: true
        })
        //Input event's description
        cy.wait(4000)
        cy.get('.ck-editor__main[role="presentation"]').then(($element) => {
            cy.get('.ck-content[contenteditable=true]').then(el => {
                // @ts-ignore
                const editor = el[0].ckeditorInstance
                editor.setData(testData.$content)
            })
        });
        //Input time and date of the event
        this.startDate.type(testData.$start_date)
        this.startTime.type(testData.$start_time)
        this.endDate.type(testData.$end_date)
        this.endTime.type(testData.$end_time)

        //Input door date and time
        this.doorDate.type(testData.$door_date)
        this.doorTime.type(testData.$door_time)

        //Input event duration
        this.eventDuration.type('1 Hour')

        //Input place
        this.eventPlace.select(testData.$event_place)

        //save the event
        this.eventSaveAsDropdown.select('Published')

        //Save and publish the event
        this.eventSave.click()
    }

    //Validate Event
    validateEvent() {
        //Validate the title of the Event
        this.fetchCreatedTitle.should('have.text', " " + testData.$content_title + " ")
        //Validate the place of the event
        this.fetchPlaceEvent.should('have.text', " " + testData.$event_place + "    ")
        //Validate the description inputed
        this.fetchDescription.should('have.text', testData.$content)
    }
}

const event = new Event()
module.exports = event
