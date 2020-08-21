(function ($) {
  Drupal.behaviors.search_facets = {
    attach: function (context) {
      /* Search page is having some dependent facets block. Dependent facets block works fine without
      Cohesion components i.e., the other blocks are only visible when some value is selected in the
      Parent facet block ( Content Type ).
      But in this case as we have used the cohesion component to implement the accordion for the facets
      block, to allow maintainers to alter the accordion design easily. Even though if no value is selected
      in the Parent facet block ( Content Type ), other facets block accordion title are visible ( Which is the
      default functionality of cohesion accordion ). So to hide them on page load, we have added this snippet
      of code. */

      // Loading the initially hidden facets container to remove the jerk.
      $(context).find(".facets-column").show();
      /* Here we are hiding the cohesion accordion divs on the basis of the facets content. Facets helped us
      by adding a 'facet-empty' class which points that the facets is empty. And using that class we are
      hidding the cohesion component acccordion divs. */
      $(context).find(".coh-accordion-tabs-content-wrapper .coh-accordion-tabs-content").each(function() {
        if ($(this).find('.facet-empty').length === 1) {
          /* Adding the visibility of the cohesion accordion container as hidden because .hide() is not taking
          precedence over display:block inline css ( When we are using context ). */
          $(this).css('visibility', 'hidden');
          $(this).prev('.coh-style-accordion').hide();
        }
      });
    }
  }
})(jQuery);
