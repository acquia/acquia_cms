CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Recommended modules
* Installation
* Configuration
* Maintainers
* License

# Introduction
The Acquia CMS Event module provides an Event content type and related
configuration and is part of the Acquia CMS ecosystem.

User-facing documentation for Acquia CMS lives on
[Acquia's documentation website](https://docs.acquia.com).

* Please report issues in the [main Acquia CMS repository](https://github.com/acquia/acquia_cms).
* Read our [Contributing](/CONTRIBUTING.md) guide.

# Requirements
This module requires the Acquia CMS Place module.

# Recommended modules
We recommend using this module together with:
* Acquia CMS Article
* Acquia CMS Page
* Acquia CMS Search
* Acquia CMS Toolbar

# Installation
Add the necessary repositories in your project's composer.json file.

`composer config repositories.drupal composer https://packages.drupal.org/8`

`composer config repositories.asset-packagist composer https://asset-packagist.org`

Require Acquia CMS Event.

`composer require drupal/acquia_cms_event`

#Configuration
This module is pre-configured to provide an Event content type.

# Maintainers
Current maintainers:
* Michael Sherron (msherron) - https://www.drupal.org/u/msherron
* Katherine Druckman (katherined) - https://www.drupal.org/u/katherined
* Vishal Khode (vishalkhode) - https://www.drupal.org/u/vishalkhode-0

This project has been sponsored by:
* Acquia

# License

Copyright (C) 2021 Acquia, Inc.

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License version 2 as published by the Free
Software Foundation.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
