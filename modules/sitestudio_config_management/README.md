# About

Contains configuration for config ignore & config split specific to site studio module, that allow better configuration
management between core config and site studio.

## Installation

This module doesn't work with [davidtrainer/blt-site-studio](https://github.com/davidtrainer/blt-site-studio) plugin.

**Configure Site Studio sync directory**

In `settings.php`, make sure you've set the Site Studio sync directory path. Ex:
```
$settings['site_studio_sync'] = '../config/site_studio/default/sync';
```

## Usage

This module provides the drush post-command-hook for `drush config:export`, `drush config:import` and also
provides the `drush config:import` event subscriber.
This means running commands `drush config:export` & `drush config:import` will automatically
run the `drush sitestudio:package:export` & `drush sitestudio:package:import`, `drush cohesion:import` etc. command simultaneously.

## Documentation

User-facing documentation for Acquia CMS lives on
[Acquia's documentation website](https://docs.acquia.com).
* Please report issues in the [main Acquia CMS repository](https://github.com/acquia/acquia_cms).
* Read our [Contributing](/CONTRIBUTING.md) guide.

# License

Copyright (C) 2023 Acquia, Inc.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 2 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
