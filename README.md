# Acquia CMS

An opinionated implementation of Lightning tailored for running low or no-code websites in the Acquia platform.

# Getting Started.

BLT provides an automation layer for testing, building, and launching Drupal 8 applications.

1. Review the [Required / Recommended Skills](http://blt.readthedocs.io/en/latest/readme/skills) for working with a BLT project.
1. Ensure that your computer meets the minimum installation requirements (and then install the required applications). See the [System Requirements](http://blt.readthedocs.io/en/latest/INSTALL/#system-requirements).
1. [Install Docksal (if using macOS, choose the VirtualBox option).](https://docksal.io/installation#macos-virtualbox)
1. Request access to organization that owns the project repo in GitHub (if needed).
1. Clone your repository. By default, Git names this "origin" on your local.
   ```
   $ git clone git@github.com:acquia/acquia-cms.git
   ```
1. Change to the project directory
   ```
   $ cd acquia-cms
   ```
1. Initialize the project
   ```
   $ fin init
   ```
1. Once the init command is done, you should see a link to login to the site in the terminal.

Note the following properties of this project:

- Primary development branch: **develop**
- Local site URL: **http://acquia-cms.docksal**
- Any project dependencies that need to have their own git repositories checked out for effective development are automatically checked out in acquia-cms/upstreams. Repo fodlers are symlinked into the appropriate place inside the Drupal file structure via Composer. **Make sure to commit any changes to these projects as part of your daily workflow.**

---

# Resources

Additional [BLT documentation](https://docs.acquia.com/blt/) may be useful. You may also access a list of BLT commands by running this:

```
$ blt
```

## Working With a BLT Project

BLT projects are designed to instill software development best practices (including git workflows).

Our BLT Developer documentation includes an [example workflow](https://docs.acquia.com/blt/developer/dev-workflow/).

### Important Configuration Files

BLT uses a number of configuration (`.yml` or `.json`) files to define and customize behaviors. Some examples of these are:

- `blt/blt.yml` (formerly blt/project.yml prior to BLT 9.x)
- `blt/local.blt.yml` (local only specific blt configuration)
- `box/config.yml` (if using Drupal VM)
- `drush/sites` (contains Drush aliases for this project)
- `composer.json` (includes required components, including Drupal Modules, for this project)

## Resources

- JIRA - https://backlog.acquia.com/browse/ONE
- GitHub - https://github.com/acquia/acquia-cms
- TravisCI - https://travis-ci.com/acquia/acquia-cms
