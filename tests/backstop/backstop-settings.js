/*
  How to use

  backstop reference --configPath=backstop-settings.js
       backstop test --configPath=backstop-settings.js

  backstop reference --configPath=backstop-settings.js --refhost=http://example.com
       backstop test --configPath=backstop-settings.js --testhost=http://example.com

  backstop reference --configPath=backstop-settings.js --paths=/,/contact
       backstop test --configPath=backstop-settings.js --paths=/,/contact

  backstop reference --configPath=backstop-settings.js --pathfile=paths
       backstop test --configPath=backstop-settings.js --pathfile=paths

 */

/*
  Set up some variables
 */
var arguments = require('minimist')(process.argv.slice(2)); // grabs the process arguments
var defaultPaths = ['/']; // By default is just checks the homepage
var scenarios = []; // The array that'll have the pages to test

/*
  Work out the environments that are being compared
 */
// The host to test
if (!arguments.testhost) {
  arguments.testhost  = "http://127.0.0.1:8080"; // Default test host
}
// The host to reference
if (!arguments.refhost) {
  arguments.refhost  = "http://127.0.0.1:8080"; // Default test host
}

// Configuration
module.exports =
{
  "id": "backstop_default",
  "viewports": [
    {
      "label": "mobile",
      "width": 320,
      "height": 2000
    },
    {
      "label": "tablet",
      "width": 768,
      "height": 2000
    },
    {
      "label": "desktop",
      "width": 1170,
      "height": 2000
    }
  ],
  "onBeforeScript": "puppet/onBefore.js",
  "onReadyScript": "puppet/onReady.js",
  "scenarios": [
    {
      "label": "ACMS Homepage (Starter)",
      "cookiePath": "backstop_data/engine_scripts/cookies.json",
      "url": arguments.testhost,
      "referenceUrl": "",
      "readyEvent": "",
      "readySelector": "",
      "delay": 0,
      "hideSelectors": [],
      "removeSelectors": [],
      "hoverSelector": "",
      "clickSelector": "",
      "postInteractionWait": 0,
      "selectors": [],
      "selectorExpansion": true,
      "expect": 0,
      "misMatchThreshold" : 10.0,
      "requireSameDimensions": true
    },
    {
      "label": "ACMS Articles (Starter)",
      "cookiePath": "backstop_data/engine_scripts/cookies.json",
      "url": arguments.testhost + "/articles",
      "referenceUrl": "",
      "readyEvent": "",
      "readySelector": "",
      "delay": 0,
      "hideSelectors": [],
      "removeSelectors": [],
      "hoverSelector": "",
      "clickSelector": "",
      "postInteractionWait": 0,
      "selectors": [],
      "selectorExpansion": true,
      "expect": 0,
      "misMatchThreshold": 12.0,
      "requireSameDimensions": false
    },
    {
      "label": "ACMS Events (Starter)",
      "cookiePath": "backstop_data/engine_scripts/cookies.json",
      "url": arguments.testhost + "/events",
      "referenceUrl": "",
      "readyEvent": "",
      "readySelector": "",
      "delay": 0,
      "hideSelectors": [],
      "removeSelectors": [],
      "hoverSelector": "",
      "clickSelector": "",
      "postInteractionWait": 0,
      "selectors": [],
      "selectorExpansion": true,
      "expect": 0,
      "misMatchThreshold": 10.0,
      "requireSameDimensions": false
    },
    {
      "label": "ACMS People (Starter)",
      "cookiePath": "backstop_data/engine_scripts/cookies.json",
      "url": arguments.testhost + "/people",
      "referenceUrl": "",
      "readyEvent": "",
      "readySelector": "",
      "delay": 0,
      "hideSelectors": [],
      "removeSelectors": [],
      "hoverSelector": "",
      "clickSelector": "",
      "postInteractionWait": 0,
      "selectors": [],
      "selectorExpansion": true,
      "expect": 0,
      "misMatchThreshold": 10.0,
      "requireSameDimensions": true
    },
    {
      "label": "ACMS Places (Starter)",
      "cookiePath": "backstop_data/engine_scripts/cookies.json",
      "url": arguments.testhost + "/places",
      "referenceUrl": "",
      "readyEvent": "",
      "readySelector": "",
      "delay": 0,
      "hideSelectors": [],
      "removeSelectors": [],
      "hoverSelector": "",
      "clickSelector": "",
      "postInteractionWait": 0,
      "selectors": [],
      "selectorExpansion": true,
      "expect": 0,
      "misMatchThreshold": 10.0,
      "requireSameDimensions": true
    },
    {
      "label": "ACMS Person (Starter)",
      "cookiePath": "backstop_data/engine_scripts/cookies.json",
      "url": arguments.testhost + "/person/operations/alex-kowen",
      "referenceUrl": "",
      "readyEvent": "",
      "readySelector": "",
      "delay": 0,
      "hideSelectors": [],
      "removeSelectors": [],
      "hoverSelector": "",
      "clickSelector": "",
      "postInteractionWait": 0,
      "selectors": [],
      "selectorExpansion": true,
      "expect": 0,
      "misMatchThreshold": 10.0,
      "requireSameDimensions": true
    },
    {
      "label": "ACMS Place (Starter)",
      "cookiePath": "backstop_data/engine_scripts/cookies.json",
      "url": arguments.testhost + "/place/office/boston-head-office",
      "referenceUrl": "",
      "readyEvent": "",
      "readySelector": "",
      "delay": 0,
      "hideSelectors": [],
      "removeSelectors": [],
      "hoverSelector": "",
      "clickSelector": "",
      "postInteractionWait": 0,
      "selectors": [],
      "selectorExpansion": true,
      "expect": 0,
      "misMatchThreshold": 10.0,
      "requireSameDimensions": true
    },
    {
      "label": "ACMS Event (Starter)",
      "cookiePath": "backstop_data/engine_scripts/cookies.json",
      "url": arguments.testhost + "/event/webinar/2021/09/past-event-five-medium-length-placeholder-heading",
      "referenceUrl": "",
      "readyEvent": "",
      "readySelector": "",
      "delay": 0,
      "hideSelectors": [],
      "removeSelectors": [],
      "hoverSelector": "",
      "clickSelector": "",
      "postInteractionWait": 0,
      "selectors": [],
      "selectorExpansion": true,
      "expect": 0,
      "misMatchThreshold": 10.0,
      "requireSameDimensions": true
    },
    {
      "label": "ACMS Article (Starter)",
      "cookiePath": "backstop_data/engine_scripts/cookies.json",
      "url": arguments.testhost + "/article/blog/article-nine-medium-length-placeholder-heading",
      "referenceUrl": "",
      "readyEvent": "",
      "readySelector": "",
      "delay": 0,
      "hideSelectors": [],
      "removeSelectors": [],
      "hoverSelector": "",
      "clickSelector": "",
      "postInteractionWait": 0,
      "selectors": [],
      "selectorExpansion": true,
      "expect": 0,
      "misMatchThreshold": 12.0,
      "requireSameDimensions": false
    }
  ],
  "paths": {
    "bitmaps_reference": "tests/backstop/bitmaps_reference",
    "bitmaps_test": "tests/backstop/bitmaps_test",
    "engine_scripts": "tests/backstop/engine_scripts",
    "html_report": "tests/backstop/html_report",
    "ci_report": "tests/backstop/ci_report"
  },
  "report": ["browser"],
  "engine": "puppeteer",
  "engineOptions": {
    "args": ["--no-sandbox"]
  },
  "asyncCaptureLimit": 5,
  "asyncCompareLimit": 50,
  "debug": false,
  "debugWindow": false
}

