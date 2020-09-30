/**
 * @file
 * Contains client-side support code for Acquia CMS's site-wide search page.
 */

(function ($) {
  Drupal.behaviors.acquiaCmsSearchPage = {
    // The search page has several facet blocks on it, and they are
    // conditionally dependent on each other. For example, if the
    // content type facet is "event", then facets like "article type"
    // don't appear, but "event type" does. When the facets are displayed
    // using the standard block system, this works fine. However, the search
    // page is using Cohesion's components to implement accordion behavior
    // for these facet blocks, to provide greater control to the site
    // builders. Unfortunately, Cohesion does not respect the inter-facet
    // dependencies, so it will display empty facet blocks and their titles,
    // which is bad. To work around that, we use this touch of JavaScript to
    // hide empty facet blocks on page load.
    hideEmptyFacets: function (context) {
      // Show the initially-hidden facets container, to remove the FOUC.
      $(context).find(".facets-column").show();

      // Hide the Cohesion accordion components that contain an empty facet.
      // The Facets module provides a helpful 'facets-empty' class to indicate
      // that a facet is empty, so we need to hide any accordion items which
      // contain that class.
      $(context).find(".coh-accordion-tabs-content-wrapper .coh-accordion-tabs-content")
        .has('.facet-empty')
        // Give the accordion container hidden visibility because $.hide() will
        // not take precedence over inline 'display: block' directives.
        .css('visibility', 'hidden')
        .prev('.coh-style-accordion')
        .hide();
    },
    attach: function (context) {
      this.hideEmptyFacets(context);
    }
  }
})(jQuery);
