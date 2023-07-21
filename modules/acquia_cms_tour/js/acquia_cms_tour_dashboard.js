
/**
 * @file
 * Contains client-side support code for Acquia CMS's dashboard page.
 */

 (function ($, Drupal, once) {
  // Override the throbber icon.
  Drupal.theme.ajaxProgressThrobber = function () { return ""; };
  Drupal.behaviors.acquiaCmsDashboardDialog = {
    attach: function (context, settings) {
      $(once('acquiaCmsDashboardDialog','.acms-dashboard-form-wrapper',context)).each(function () {
        if (!settings.acquia_cms_existing_site && !settings.hide_starter_kit_wizard_modal && !settings.selected_starter_kit && settings.show_starter_kit_modal) {
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
})(jQuery, Drupal, once);
