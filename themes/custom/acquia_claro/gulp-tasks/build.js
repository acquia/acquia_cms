/**
 * @file
 * Task: Build.
 */

/* global module */

module.exports = function (gulp, plugins, options) {
  'use strict';
  plugins.runSequence.options.showErrorStackTrace = false;

  gulp.task('build', gulp.series(function building(cb) {
    plugins.runSequence(
      'compile:sass',
      ['minify:css'],
      ['lint:js-gulp',
        'lint:js-with-fail'],
      'compile:js',
      cb);
  }));

  gulp.task('build:dev', gulp.series(function (cb) {
    plugins.runSequence(
      'compile:sass',
      ['minify:css'],
      ['lint:js-gulp',
        'lint:js'],
      cb);
  }));
};
