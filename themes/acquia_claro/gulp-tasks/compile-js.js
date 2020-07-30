/**
 * @file
 * Task: Compile: JavaScript.
 */

/* global module */

module.exports = function (gulp, plugins, options) {
  'use strict';

  gulp.task('compile:js', gulp.series(function compile() {
    return gulp.src([
      options.js.files
    ])
      .pipe(plugins.plumber())
      .pipe(plugins.sourcemaps.init())
      .pipe(plugins.babel({
        presets: ['es2015']
      }))
      .pipe(plugins.sourcemaps.write())
      .pipe(plugins.plumber.stop())
      .pipe(gulp.dest(options.js.destination));
  }));
};
