/**
 * @file
 * Contains the clean:css task for acquia_claro.
 */

/* global module */

module.exports = function (gulp, plugins, options) {
  'use strict';

  // Clean CSS files.
  gulp.task('clean:css', gulp.series(async function () {
    plugins.del.sync([
      options.css.files
    ]);
  }));
};
