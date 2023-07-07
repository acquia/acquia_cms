const page = require("../../pages/Page")
const content = require("../../pages/Content")


describe("Page - Create Page", () => {
    //TC-89
    //TC-92
    //TC-93
    //Page- Create content type Page with Site Studio
    context("Page - Create Page", () => {
        it("Mouse hover and click on 'Content>Add Content>Page' link, create/publish the page using layout components and visual page builder", () => {
            //Create the page with layout canvas edit it with Visual page builder
            page.createPageLayoutCanvas()
            //Validate the created page
            page.validateCreatedPage()
            //Delete the created page
            content.deleteContent()
        })
    })
})
