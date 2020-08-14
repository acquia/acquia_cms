/**
 * @file
 * Contains the watch tasks for acquia_claro.
 */

module.exports = function (gulp, plugins, options) {
  'use strict';

  //Detect changes in js
  gulp.task('watch:js', function () {
    return gulp.watch(options.js.files, gulp.series('lint:js', 'lint:css'));
  });

  //Detect changes in SCSS
  gulp.task('watch:sass', function () {
    return gulp.watch(options.sass.files, gulp.series('compile:sass', 'minify:css'));
  });

  gulp.task('watch', gulp.parallel('watch:js', 'watch:sass'));
};
