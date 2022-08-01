
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
        if (!settings.existing_site_acquia_cms && !settings.hide_starter_kit_wizard_modal && !settings.selected_starter_kit && settings.show_starter_kit_modal) {
          $('.acms-starterkit-modal-form').click();
        }
        if (settings.show_wizard_modal && !settings.wizard_completed){
          $('.acms-dashboard-modal-form').click();
        }
        $(document).on('change','body .acms-starter-kit-wizard .fieldset__wrapper select',function(e){
          if ($('.acms-starter-kit-wizard [data-drupal-states].form-item--no-label').is(":visible")){
            $('.acms-starter-kit-wizard .ui-dialog-buttonpane [type="button"]:last-child').prop('disabled', true);
          }
          else{
            $('.acms-starter-kit-wizard .ui-dialog-buttonpane [type="button"]:last-child').prop('disabled', false);
          }
        });
      });
    }
  }
})(jQuery, Drupal);
