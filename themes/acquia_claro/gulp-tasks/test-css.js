/**
 * @file
 * Contains the test:css task for acquia_claro.
 */

/* global module */

module.exports = function (gulp, plugins, options) {
  'use strict';

  gulp.task('test:css', gulp.series('lint:css-with-fail', function testingCss () {
    return gulp.src(options.css.files)
      .pipe(plugins.plumber())
      .pipe(plugins.parker());
  }));
};
