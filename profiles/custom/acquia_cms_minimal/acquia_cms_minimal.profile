<?php

/**
 * @file
 * ACMS minimal profile site installation helper.
 */

use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Implements hook_user_login().
 */
function acquia_cms_minimal_user_login(UserInterface $account) {
  // Ignore password reset.
  $route_name = \Drupal::routeMatch()->getRouteName();
  $user = \Drupal::currentUser();
  // Check for permission.
  $has_access = $user->hasPermission('access acquia cms tour dashboard');
  $selected_starter_kit = \Drupal::state()->get('acquia_cms.starter_kit');
  if ($route_name !== 'user.reset.login') {
    // Do not interfere if a destination was already set.
    $current_request = \Drupal::service('request_stack')->getCurrentRequest();
    if (!$current_request->query->get('destination')) {
      if (!$selected_starter_kit && $has_access) {
        // Default login destination to the dashboard.
        $current_request->query->set(
          'destination',
          Url::fromRoute('acquia_cms_tour.enabled_modules')->toString() . '?show_starter_kit_modal=TRUE'
        );
      }
    }
  }
}

/**
 * Prepares variables for install page templates.
 *
 * Default template: install-page.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content - An array of page content.
 *
 * @see template_preprocess_install_page()
 */
function acquia_cms_minimal_preprocess_install_page(array &$variables) {
  $variables['drupal_core_version'] = \Drupal::VERSION;
  $variables['#attached']['library'][] = 'acquia_claro/install-page';
  $acquia_cms_path = \Drupal::service('extension.list.profile')->getPath('acquia_cms_minimal');
  $variables['install_page_logo_path'] = '/' . $acquia_cms_path . '/acquia_cms.png';
}
