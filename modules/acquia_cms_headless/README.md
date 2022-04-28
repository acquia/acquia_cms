# About

Provides functionality for Progressively Decoupled and Purely Headless sites using Node and Next JS and related configuration.

## Installation Instructions

This module is part of the Acquia CMS project. 

**Prequisites**

1. Install the Acquia CMS project first, see installation instructions in the Acquia CMS Tour module README.

2. Install the ACMS/NEXT library by running this command:
```
npx create-next-app -e https://github.com/acquia/next-acms
```

Once Acquia CMS and ACMS.NEXT library are installed, in your drupal site go to the Extend menu and enable Acquia CMS Headless module. In the top bar menu link select "Tour", which takes you to the "Get Started" page. Click the "Get Started" button. You will see your dashboard. Locate the "Headless" section to reveal two checkbox options: 

* **Enable Next.js starter kit** When the Next.js starter kit option is enabled, dependencies related to the Next.js module will be enabled providing users with the ability to use Drupal as a backend for a decoupled NodeJS app while also retaining Drupal’s default front-end. E.g., with a custom theme.
 
* **Enable Headless mode** When Headless Mode is enabled, it turns on all the capabilities that allows Drupal to be used as a backend for a decoupled Node JS app AND turns off all of Drupal’s front-end features so that the application is purely headless.

	_Unchecking these boxes will revert any changes_

### Enable Next.js starter kit
**Important** When you click the save button, the initialization service is run and you will be presented with your secret and additional information. 

This is an opinionated architecture and automates the many steps required to set up a partially headless site. This option will automatically create a consumer, keys, user with role and permissions, and a next js site. However, you will need to set your env.local file manually. Rename the env.example file in your local filesystem inside your next project folder to env.local and per the following (see /admin/config/services/next/sites/headless/env):

```
Required 
NEXT_PUBLIC_DRUPAL_BASE_URL=automatically fetched 
NEXT_IMAGE_DOMAIN=automatically fetched
DRUPAL_PREVIEW_SECRET=automatically fetched
DRUPAL_CLIENT_ID=the UUID found on admin/config/services/consumer
DRUPAL_CLIENT_SECRET=Presented to you on initialization
```
### Enable Headless mode
This option streamlines the Drupal UI and allows you to create a purely headless site of your own choosing, unimpeded by unnecessary front-end options provided by Drupal.


# License

Copyright (C) 2022 Acquia, Inc.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 2 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
