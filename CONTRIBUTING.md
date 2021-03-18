# Contributing to Acquia CMS

### External Contributions

Like all open source software, you are permitted and encouraged to extend Acquia CMS as much as you like - via version control and build systems you maintain.

We accept suggestions for Acquia CMS through our Github issue queue. We do not currently accept pull requests or patches, as our CI tooling requires access to secure credentials that are restricted to Acquia employees only.

### Internal Acquia Contributions

Contributing to Acquia CMS requires the ability to push branches to the repository, [since we can not use forks](https://docs.travis-ci.com/user/environment-variables/#defining-variables-in-repository-settings). If you need access, speak to Acquia CMS engineering leadership.

Before opening a new branch, note the JIRA ticket number that you're going to work on (there can be multiple branches associated with a single ticket). The ticket number will have the format ACMS-N, where N is a number. The branch name should be prefixed by the ticket number, followed by a short description, and it should be branched from the `develop` branch. For example:
```
git checkout develop
git pull
git checkout -b ACMS-123/event-content-type
```
Once the branch is open, you can make as many commits to it as you like. All commit messages must be prefixed by the ticket number. For example: `ACMS-123: Add the event content type` (note the lack of a period at the end of the commit message) is a good example of a commit message.

When you're ready for your work to be reviewed, open a pull request to merge your branch into the `develop` branch. You should also periodically sync and rebase against `develop`.
