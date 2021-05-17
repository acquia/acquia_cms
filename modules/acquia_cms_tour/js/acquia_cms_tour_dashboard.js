
/**
 * @file
 * Contains client-side support code for Acquia CMS's dashboard page.
 */

(function ($, Drupal) {
  // Override the throbber icon.
  Drupal.theme.ajaxProgressThrobber = function () { return ""; };
  Drupal.behaviors.acquiaCmsDashboardDialog = {
    attach: function (context, settings) {
      $('.acms-dashboard-form-wrapper', context).once('acquiaCmsDashboardDialog').each(function () {
        if (settings.show_wizard_modal && !settings.wizard_completed){
          $('.acms-dashboard-modal-form').click();
        }
      });
    }
  }
})(jQuery, Drupal);
