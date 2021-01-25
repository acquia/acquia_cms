/**
 * @file
 * Contains the cssbeautify task for acquia_claro.
 */

module.exports = function (gulp, plugins, options) {
  'use strict';
  gulp.task('cssbeautify', gulp.series(function cssbeautify() {
    return gulp.src(options.css.files)
      .pipe(plugins.plumber())
      .pipe(plugins.beautifyCode({
        selector_separator_newline: true,
        indent_size: 2,
        newline_between_rules: true,
        end_with_newline: true,
        space_around_combinator: true
      }))
      .pipe(plugins.plumber.stop())
      .pipe(gulp.dest(options.css.destination));
  }));
};
