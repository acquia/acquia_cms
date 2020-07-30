/**
 * @file
 * Task: Lint: scss Files.
 */

/* global module */

module.exports = function (gulp, plugins, options) {
  'use strict';

  // Lint scss files.
  gulp.task('lint:css', function lintCSS () {
    return gulp.src(options.sass.files)
      .pipe(plugins.plumber())
      .pipe(plugins.gulpStylelint({
        reporters: [
          {
            formatter: 'string',
            console: true
          }
        ]
      }))
      .pipe(plugins.plumber.stop());
  });

  // Lint scss files and throw an error for a CI to catch.
  gulp.task('lint:css-with-fail', function lintWithFail () {
    return gulp.src(options.sass.files)
      .pipe(plugins.gulpStylelint({
        reporters: [
          {
            formatter: 'string',
            console: true,
            failAfterError: true
          }
        ]
      }));
  });
};
