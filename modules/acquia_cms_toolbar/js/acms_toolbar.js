
/**
 * @file
 * Contains toolbar styles for Acquia CMS's site-wide pages.
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.acquiaCmsToolbarOffset = {
    // Avoid main menu's overlapping with admin toolbar.
    // Spacing from top should be equal to the height of admin toolbar.
    // JS code only gets applied when user is logged in and has access to admin toolbar.
    attach(context) {
      function adjustTopPadding() {
        // Since toolbar-icon-menu was adding its own js,
        // we have adjusted margin when user clicks on manage button.
        var body_padding_top = $('body').css('padding-top');
        var new_height = body_padding_top.replace('px', '') - 5;
        // Js should not be applied for desktop.
        if ($(window).width() < 768) {
          $('body').css('margin-top', $('#toolbar-bar').height()- new_height);
        }
      }
      window.onload = adjustTopPadding;
      $(window).resize(() => adjustTopPadding());
      $('.toolbar-icon-menu').click(() => adjustTopPadding());
    },
  };
})(jQuery, Drupal, drupalSettings);
