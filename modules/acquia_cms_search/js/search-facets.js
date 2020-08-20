(function ($) {
  Drupal.behaviors.search_facets = {
    attach: function (context) {
      // Loading the initially hiden facets container to remove the jerk.
      $(context).find(".facets-column").show();
      // Looping through each accordion tab content and searching for empty facets.
      $(context).find(".coh-accordion-tabs-content-wrapper .coh-accordion-tabs-content").each(function() {
        if ($(this).find('.facet-empty').length === 1) {
          $(this).hide();
          $(this).prev('.coh-style-accordion').hide();
        }
      });
    }
  }
})(jQuery);
