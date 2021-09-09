### Customizing Your Acquia CMS Installation

You may create your own custom profile in order to use a subset of Acquia CMS functionality and/or add additional contributed or custom modules. We have included a template to help you get started. Place the CUSTOM_PROFILE directory (found in the Acquia CMS root directory) in the /profiles/custom directory of your Drupal project, and rename the directory and CUSTOM_PROFILE.info.yml file to match your profile name.

Then, require the individual Acquia CMS modules you will use in your profile rather than acquia/acuia_cms itself.

For example, the require key in your composer.json file may look like:
~~~
  "require": {
    "acquia/blt": "^12",
    "acquia/blt-phpcs": "^1.0",
    "acquia/acquia_claro": "^1.0",
    "acquia/acquia_cms_article": "^1.0",
    "acquia/acquia_cms_document": "^1.0",
    "acquia/acquia_cms_search": "^1.0",
    "acquia/acquia_cms_support": "^1.0",
    "composer/installers": "^1.9",
    "cweagans/composer-patches": "^1.6",
    "drupal/core-composer-scaffold": "^9",
    "drupal/core-recommended": "^9",
    "drupal/mysql56": "^1.0",
    "oomphinc/composer-installers-extender": "^1.1 || ^2"
  },
~~~

Your profile can be as simple as including your CUSTOM_PROFILE.info.yml in your CUSTOM_PROFILE directory, but may optionally include:

- CUSTOM_PROFILE.install
- CUSTOM_PROFILE.profile
- /config
- /translations

Note that if you include a .install file in your profile that implements hook_install(), installation from configuration is not supported. Please see the full documentation on [drupal.org for more information about installing from configuration](https://www.drupal.org/docs/distributions/creating-distributions/how-to-write-a-drupal-installation-profile#config).

Drupal.org provides excellent documentation for creating custom profiles at https://www.drupal.org/docs/distributions/creating-distributions/how-to-write-a-drupal-installation-profile

### Additional information:

Acquia CMS components module provides patches for its dependencies,
in order to use them add following key in your root level composer.json file.
```
{
  "extra": {
      "enable-patching": true
  }
}
```

### Including Acquia CMS Site Studio

For custom profiles that include the Acquia CMS Site Studio module, include the contents of the [CUSTOM_PROFILE.profile.example](https://github.com/acquia/acquia_cms/blob/develop/CUSTOM_PROFILE/CUSTOM_PROFILE.profile.example) file to provide install tasks related to Site Studio.

### Known Issues

The module [acquia_cms_search](https://www.drupal.org/project/acquia_cms_search) is not compatible with Drupal core search module.

### Running Tests

For steps required for running tests related to your custom profile, head towards the testing document [here](TESTING.md) .
