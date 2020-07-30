/**
 * @file
 * Task: Test: CSS.
 */

/* global module */

module.exports = function (gulp, plugins, options) {
  'use strict';

  gulp.task('test:css', gulp.series('compile:sass',function testingCss () {
    return gulp.src(options.css.file)
      .pipe(plugins.plumber())
      .pipe(plugins.parker());
  }));
};
