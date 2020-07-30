/**
 * @file
 * Task: Default.
 */

/* global module */

module.exports = function (gulp, plugins, options) {
  'use strict';
  // The default task (called when you run `gulp` from cli)
  gulp.task('default', gulp.series('build'))
};
