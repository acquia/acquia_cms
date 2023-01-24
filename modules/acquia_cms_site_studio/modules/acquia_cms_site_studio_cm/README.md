# About

Contains configuration for config ignore & config split specific to site studio module, that allow better configuration
management between core config and site studio.

## Usages
In order to utilise this module's functionality, user needs to add scaffolding to their project.
they can run command `composer config --json extra.drupal-scaffold.allowed-packages ["acquia/acquia_cms_site_studio"] --merge && composer instal`
will do the scaffolding, and then enabling this module will create a config split named _sitestudio_
along with a config ignore and default Site Studio full export settings.
- Manually do scaffolding
- Enable this module.
- Run drush command `drush cex`.

### Scaffolding
This module provides composer scaffolding which updates default.settings.php to include _site_studio_sync_ directory path.
`$settings['site_studio_sync'] = '../config/sitestudio';`. It also provides post commands for `drush cex` & `drush cim`,
it means running commands will automatically run `drush sitestudio:package:export`
& `drush sitestudio:package:import`

## Documentation

User-facing documentation for Acquia CMS lives on
[Acquia's documentation website](https://docs.acquia.com).

* Please report issues in the [main Acquia CMS repository](https://github.com/acquia/acquia_cms).
* Read our [Contributing](/CONTRIBUTING.md) guide.

# License

Copyright (C) 2023 Acquia, Inc.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 2 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
