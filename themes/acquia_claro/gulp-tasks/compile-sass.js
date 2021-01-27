/**
 * @file
 * Contains the compile:sass task for acquia_claro.
 */

/* global module */

module.exports = function (gulp, plugins, options) {
  'use strict';

  gulp.task('compile:sass', gulp.series('clean:css', async function compile () {
    return gulp.src([
      options.sass.files
    ])
      .pipe(plugins.plumber())
      .pipe(plugins.sassGlob())
      .pipe(plugins.sass({
        errLogToConsole: true,
        outputStyle: 'expanded'
      }))
      .pipe(plugins.autoprefixer({
        browsers: ['last 2 versions'],
        cascade: false
      }))
      .pipe(plugins.beautifyCode({
        selector_separator_newline: true,
        indent_size: 2,
        newline_between_rules: true,
        end_with_newline: true,
        space_around_combinator: true
      }))
      .pipe(gulp.dest(options.sass.destination));
  }));
};
