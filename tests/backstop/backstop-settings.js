// grabs the process arguments
let arguments = require('minimist')(process.argv.slice(2));

// The default test host
if (!arguments.testhost) {
  arguments.testhost  = "http://127.0.0.1:8080";
}
// The default reference host
if (!arguments.refhost) {
  arguments.refhost  = "http://127.0.0.1:8080";
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
      "misMatchThreshold" : 0.1,
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
      "misMatchThreshold": 0.1,
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
      "hideSelectors": [".card-date"],
      "removeSelectors": [],
      "hoverSelector": "",
      "clickSelector": "",
      "postInteractionWait": 0,
      "selectors": [],
      "selectorExpansion": true,
      "expect": 0,
      "misMatchThreshold": 0.1,
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
      "misMatchThreshold": 0.1,
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
      "misMatchThreshold": 0.1,
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
      "misMatchThreshold": 0.1,
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
      "misMatchThreshold": 18,
      "requireSameDimensions": true
    },
    {
      "label": "ACMS Event (Starter)",
      "cookiePath": "backstop_data/engine_scripts/cookies.json",
      "url": arguments.testhost + "/node/10000",
      "referenceUrl": "",
      "readyEvent": "",
      "readySelector": "",
      "delay": 0,
      "hideSelectors": ['time'],
      "removeSelectors": [],
      "hoverSelector": "",
      "clickSelector": "",
      "postInteractionWait": 0,
      "selectors": [],
      "selectorExpansion": true,
      "expect": 0,
      "misMatchThreshold": 0.2,
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
      "misMatchThreshold": 0.1,
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

