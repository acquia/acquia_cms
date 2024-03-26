
/**
 * @file
 * Contains toolbar styles for Acquia CMS's site-wide pages.
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.acquiaCmsToolbarOffset = {
    // Avoid main menu's overlapping with admin toolbar.
    // Spacing from top should be equal to the height of admin toolbar.
    // JS code only gets applied when user is logged in and has access to admin toolbar.
    // This was causing contentHub UI issue, hence added only for non-admin route.
    attach(context) {
      function adjustTopPadding() {
        // Since toolbar-icon-menu was adding its own js,
        // we have adjusted margin when user clicks on manage button
        if($('body #toolbar-administration-secondary').is(':visible') == false) {
          $('body').css('margin-top', 10);
        }
      }
      window.onload = adjustTopPadding;
      $(window).resize(() => adjustTopPadding());
      $('.toolbar-icon-menu').click(() => adjustTopPadding());
    },
  };
})(jQuery, Drupal, drupalSettings);
