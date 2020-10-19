
/**
 * @file
 * Contains client-side support code for Acquia CMS's PubSec demo site-wide pages.
 */

(function ($) {
  // Accessibility issue for main navigation bar:
  // Children menus of non-focused menu item doesn't hide when menus are accessed using 'tab' and 'down arrow' keys.
  // JavaScript to hide children menus of non-focused menu item after switching to next menu item.
  Drupal.behaviors.acquiaCmsCloseMenuOnFoucsOut = {
    attach: function () {
      $('nav .coh-menu-list-item a').on('focus', function(e) {
        $siblingsOfFocusedMenu = $(this).parent('.coh-menu-list-item').siblings('.has-children');
        $siblingsOfFocusedMenu.removeClass('is-expanded').addClass('is-collapsed');
        $siblingsOfFocusedMenu.children("ul").css('display', 'none')
        $siblingsOfFocusedMenu.children('a').attr('aria-expanded', 'false');
      });
    }
  }

})(jQuery);
