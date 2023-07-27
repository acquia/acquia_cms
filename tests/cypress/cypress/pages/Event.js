import 'cypress-iframe'
import content from './Content'
import testData from './TestData'
import utility from './Utility'
class Event {

    // Place page through mouse hover on admin tool bar.
    get eventLink() {
        return cy.get(utility.$addContentMenu + " li:nth-child(2) > a")
    }

    // Get start and end date/time.
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
    // Get door date.
    get doorDate() {
        return cy.get("#edit-field-door-time-0-value-date")
    }
    get doorTime() {
        return cy.get("#edit-field-door-time-0-value-time")
    }

    // Get event duration.
    get eventDuration() {
        return cy.get("#edit-field-event-duration-0-value")
    }
    // Get place of event.
    get eventPlace() {
        return cy.get("#edit-field-event-place")
    }
    // Get tags of the event.
    get eventTags() {
        return cy.get("#edit-field-tags-target-id")
    }
    // Get type of the event.
    get eventType() {
        return cy.get("#edit-field-event-type")
    }

    // Validation.
    // Fetch created event title.
    $eventSelector = 'body article .coh-style-padding-top-medium ';
    get fetchCreatedTitle() {
        return cy.get(this.$eventSelector + " div:nth-child(1) > div > div > h1")
    }
    // Fetch place of the event.
    get fetchPlaceEvent() {
        return cy.get(this.$eventSelector + " div:nth-child(1) > div > div > ul > li:nth-child(2) > a")
    }
    // Fetch the inputed and published description.
    get fetchDescription() {
        return cy.get(this.$eventSelector + " div:nth-child(2) > div > div.coh-column.coh-visible-ps > div > p")
    }

    // Click and verify the creation page of events.
    clickAndVerify() {
        // Click on event link from mouse hover.
        this.eventLink.click({
            force: true
        })
        // Title of the event page.
        content.pageTitle.should('have.text', 'Create Event')
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
        // Event duration.
        this.eventDuration.should("be.visible")
        // Place of the event.
        this.eventPlace.should("be.visible")
        // Event tags should be visible.
        this.eventTags.should("be.visible")
        // Event type should be visible.
        this.eventType.should("be.visible")
        // Check the options of event type tab.
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
        // Select the language of the event.
        utility.contentLanguageSelect.should("be.visible")
        utility.contentLanguageSelect.select('English', {
            force: true
        }).should('have.value', 'en')
        // Save as option and its dropdown should be present.
        utility.saveAsDropdown.should("be.visible")
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

    // Create event.
    createEvent() {
        // Name of the event.
        utility.editTitle.type(testData.$content_title, {
            force: true
        })
        // Input event's description.
        cy.wait(4000)
        cy.get('.ck-editor__main[role="presentation"]').then(($element) => {
            cy.get('.ck-content[contenteditable=true]').then(el => {
                // @ts-ignore.
                const editor = el[0].ckeditorInstance
                editor.setData(testData.$content)
            })
        });
        // Input time and date of the event.
        this.startDate.type(testData.$start_date)
        this.startTime.type(testData.$start_time)
        this.endDate.type(testData.$end_date)
        this.endTime.type(testData.$end_time)

        // Input door date and time.
        this.doorDate.type(testData.$door_date)
        this.doorTime.type(testData.$door_time)

        // Input event duration.
        this.eventDuration.type('1 Hour')

        // Input place.
        this.eventPlace.select(testData.$event_place)

        // Save the event.
        utility.saveAsDropdown.select('Published')

        // Save and publish the event.
        utility.save.click()
    }

    // Validate Event.
    validateEvent() {
        // Validate the title of the Event.
        this.fetchCreatedTitle.should('have.text', " " + testData.$content_title + " ")
        // Validate the place of the event.
        this.fetchPlaceEvent.should('have.text', " " + testData.$event_place + "    ")
        // Validate the description inputed.
        this.fetchDescription.should('have.text', testData.$content)
    }
}

const event = new Event()
module.exports = event
