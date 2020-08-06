'use strict';

/**
 * @file
 * Preview for the Acquia Claro theme.
 * Edit preview.es6.js then run 'npm run color' to build preview.js.
 * Do not edit preview.js.
 */
(function ($, Drupal, drupalSettings) {
  Drupal.color = {
    logoChanged: false,
    callback: function callback(context, settings, $form) {

      var $colorPreview = $form.find('.color-preview');
      var $colorPalette = $form.find('.js-color-palette');

      // Header background.
      $colorPreview.find('.content-header').css('background-color', $colorPalette.find('input[name="palette[contentheader]"]').val());

      // Breadcrumbs.
      $colorPreview.find('.breadcrumb__item a').css('color', $colorPalette.find('input[name="palette[pagetitle]"]').val());

      // Title.
      $colorPreview.find('.block-page-title-block').css('color', $colorPalette.find('input[name="palette[pagetitle]"]').val());

      // Text color.
      $colorPreview.find('.page-wrapper').css('color', $colorPalette.find('input[name="palette[text]"]').val());

      $colorPreview.find('.admin-item__description').css('color', $colorPalette.find('input[name="palette[text]"]').val());

      // Link color.
      $colorPreview.find('.panel a').css('color', $colorPalette.find('input[name="palette[link]"]').val());

      $colorPreview.find('.content-header a').hover(function () {
        $(this).css('color', $colorPalette.find('input[name="palette[link]"]').val());
      }, function () {
        $(this).css('color', '#222330');
      });

      $colorPreview.find('.tabs__link').css('color', $colorPalette.find('input[name="palette[primarytabs]"]').val());

      $colorPreview.find('.tabs__link.active').css('border-color', $colorPalette.find('input[name="palette[primarytabs]"]').val());

      // Primary Tabs.
      $colorPreview.find('.is-horizontal .tabs__link').hover(function () {
        $(this).css('color', $colorPalette.find('input[name="palette[link]"]').val());
      }, function () {
        $(this).css('color', $colorPalette.find('input[name="palette[primarytabs]"]').val());
      });

      $colorPreview.find('.is-horizontal .tabs__link').hover(function () {
        $(this).css('background', $colorPalette.find('input[name="palette[primarytabshover]"]').val());
      }, function () {
        $(this).css('background', '');
      });

      $colorPreview.find('.panel__title').css('background-color', $colorPalette.find('input[name="palette[primarytabshover]"]').val());

      // Icon Color.
      var $iconColor = $colorPalette.find('input[name="palette[link]"]').val();
      $colorPreview.find('.admin-item__title').before('<svg height="14" width="9"><path d="M 1.7109375,0.31445312 0.2890625,1.7226562 5.5917969,7.0761719 0.2890625,12.429688 1.7109375,13.837891 8.4082031,7.0761719 Z" fill="' + $iconColor + '";/></svg>');

      // Button.
      $colorPreview.find('.button').css('background', $colorPalette.find('input[name="palette[link]"]').val());

      // Button hover.
      $colorPreview.find('.button').hover(function () {
        $(this).css('background', $colorPalette.find('input[name="palette[primarybuttonhover]"]').val());
      }, function () {
        $(this).css('background', $colorPalette.find('input[name="palette[link]"]').val());
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
