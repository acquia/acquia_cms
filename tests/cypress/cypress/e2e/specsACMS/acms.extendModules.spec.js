import 'cypress-iframe'
// TC-## are the qTest test case id's <reference types="cypress" />.

const extend = require("../../pages/Extend")


describe("Verify the modules of Acquia CMS", () => {

    // Acquia Purge module.
    context("Verify and Extend Acquia purge module", () => {
        it("Extend the Acquia Purge module for execution", () => {
            extend.extendPurgeModule()
        })
    })

    // Acquia Search module.
    context("Verify and Extend Acquia search module", () => {
        it("Extend the Acquia Search module for execution", () => {
            extend.extendSearchModule()
        })
    })

    // Acquia search api solr module.
    context("Verify and Extend Acquia search api solr module", () => {
        it("Extend the Acquia Search api solr module for execution", () => {
            extend.extendSASolrModule()
        })
    })

    // Acquia connector module.
    context("Verify and Extend Acquia connector module", () => {
        it("Extend the Acquia connector module for execution", () => {
            extend.extendConnectorModule()
        })
    })

    // Article.
    context("Verify and Extend Acquia CMS Article module", () => {
        it("Extend the Acquia CMS Article module for execution", () => {
            extend.extendArticleModule()
        })
    })

    // Audio.
    context("Verify and Extend Acquia CMS Audio module", () => {
        it("Extend the Acquia CMS Audio module for execution", () => {
            extend.extendAudioModule()
        })
    })

    // Component.
    context("Verify and Extend Acquia CMS Component module", () => {
        it("Extend the Acquia CMS Component module for execution", () => {
            extend.extendComponentModule()
        })
    })

    // Dam.
    context("Verify and Extend Acquia CMS DAM module", () => {
        it("Extend the Acquia CMS DAM module for execution", () => {
            extend.extendDamModule()
        })
    })

    // Document.
    context("Verify and Extend Acquia CMS Document module", () => {
        it("Extend the Acquia CMS Document module for execution", () => {
            extend.extendDocumentModule()
        })
    })

    // Event.
    context("Verify and Extend Acquia CMS Event module", () => {
        it("Extend the Acquia CMS Event module for execution", () => {
            extend.extendEventModule()
        })
    })

    // Headless.
    context("Verify and Extend Acquia CMS Headless module", () => {
        it("Extend the Acquia CMS Headless module for execution", () => {
            extend.extendHeadlessModule()
        })
    })

    // Image.
    context("Verify and Extend Acquia CMS Image module", () => {
        it("Extend the Acquia CMS Image module for execution", () => {
            extend.extendImageModule()
        })
    })

    //Page
    context("Verify and Extend Acquia CMS Page module", () => {
        it("Extend the Acquia CMS Page module for execution", () => {
            extend.extendPageModule()
        })
    })

    // Person.
    context("Verify and Extend Acquia CMS Person module", () => {
        it("Extend the Acquia CMS Person module for execution", () => {
            extend.extendPersonModule()
        })
    })

    // Place.
    context("Verify and Extend Acquia CMS Place module", () => {
        it("Extend the Acquia CMS Place module for execution", () => {
            extend.extendPlaceModule()
        })
    })

    // Search.
    context("Verify and Extend Acquia CMS Search module", () => {
        it("Extend the Acquia CMS Search module for execution", () => {
            extend.extendSearchModule()
        })
    })

    // Site Studio.
    context("Verify and Extend Acquia CMS Site Studio module", () => {
        it("Extend the Acquia CMS Site Studio module for execution", () => {
            extend.extendSiteStudioModule()
        })
    })

    // Toolbar.
    context("Verify and Extend Acquia CMS Toolbar module", () => {
        it("Extend the Acquia CMS Toolbar module for execution", () => {
            extend.extendToolbarModule()
        })
    })

    // Tour.
    context("Verify and Extend Acquia CMS Tour module", () => {
        it("Extend the Acquia CMS Tour module for execution", () => {
            extend.extendTourModule()
        })
    })

    //Acquia CMS Video
    context("Verify and Extend Acquia CMS Video module", () => {
        it("Extend the Acquia CMS Video module for execution", () => {
            extend.extendCMSVideoModule()
        })
    })

    // Checklist API.
    context("Verify and Extend CheckList API module", () => {
        it("Verify the Checklist APi module for execution", () => {
            extend.extendCheckListAPIModule()
        })
    })

    // Site Studio Core.
    context("Verify and Extend Site Studio Core module", () => {
        it("Verify the Site Studio Core module for execution", () => {
            extend.extendSiteStudioCoreModule()
        })
    })

})
