class Utility {

    // The save, submit button.
    get save() {
        return cy.get("#edit-submit")
    }

}

const utility = new Utility();
module.exports = utility;
