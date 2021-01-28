/**
 * @file
 * Contains the build task for acquia_claro.
 */

module.exports = function (gulp, plugins, options) {
  'use strict';
  plugins.runSequence.options.showErrorStackTrace = false;

  gulp.task('build', gulp.series(function building(cb) {
    plugins.runSequence(
      'compile:sass',
      'compile:js',
      ['lint:js-gulp',
        'lint:js-with-fail',
        'lint:css-with-fail'],
      cb);
  }));

  gulp.task('build:dev', gulp.series(function (cb) {
    plugins.runSequence(
      'compile:sass',
      ['minify:css'],
      ['lint:js-gulp',
        'lint:js',
        'lint:css'],
      cb);
  }));
};
