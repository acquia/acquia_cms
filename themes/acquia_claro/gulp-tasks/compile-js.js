/**
 * @file
 * Contains the compile:js task for acquia_claro.
 */

/* global module */

module.exports = function (gulp, plugins, options) {
  'use strict';

  gulp.task('compile:js', gulp.series(function compile() {
    return gulp.src([
      options.js.files
    ])
      .pipe(plugins.plumber())
      .pipe(plugins.babel({
        presets: ['es2015']
      }))
      .pipe(plugins.plumber.stop())
      .pipe(gulp.dest(options.js.destination));
  }));
};
