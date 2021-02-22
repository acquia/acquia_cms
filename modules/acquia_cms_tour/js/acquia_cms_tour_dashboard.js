
/**
 * @file
 * Contains client-side support code for Acquia CMS's dashboard page.
 */

(function ($, Drupal, drupalSettings) {
  // Override the throbber icon.
  Drupal.theme.ajaxProgressThrobber = function () { return ""; };
  Drupal.behaviors.acquiaCmsWelcomeDialog = {
    attach: function () {
      // @todo: Open modal only if show_wizard_modal is TRUE and its not an AJAX call
      if (drupalSettings.show_wizard_modal){
        $('.welcome-modal-form').click();
      }
    }
  }
})(jQuery, Drupal, drupalSettings);
