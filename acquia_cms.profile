<?php

/**
 * @file
 * Acquia CMS standard profile.
 */

use Drupal\cohesion\Controller\AdministrationController;

/**
 * Implements hook_install_tasks().
 */
function acquia_cms_install_tasks() {
  $tasks = [];

  $tasks['acquia_cms_set_default_theme'] = [];
  $tasks['acquia_cms_prepare_administrator'] = [];
  $tasks['acquia_cms_initialize_cohesion'] = [];

  return $tasks;
}

/**
 * Sets the default and administration themes.
 */
function acquia_cms_set_default_theme() {
  Drupal::configFactory()
    ->getEditable('system.theme')
    ->set('default', 'cohesion_theme')
    ->set('admin', 'claro')
    ->save(TRUE);

  // Use the admin theme for creating content.
  if (Drupal::moduleHandler()->moduleExists('node')) {
    Drupal::configFactory()
      ->getEditable('node.settings')
      ->set('use_admin_theme', TRUE)
      ->save(TRUE);
  }
}

/**
 * Assigns the 'administrator' role to user 1.
 */
function acquia_cms_prepare_administrator() {
  /** @var \Drupal\user\UserInterface $account */
  $account = \Drupal::entityTypeManager()
    ->getStorage('user')
    ->load(1);
  if ($account) {
    $account->addRole('administrator');
    $account->save();
  }
}

/**
 * Initializes the Cohesion module with default configuration.
 */
function acquia_cms_initialize_cohesion() {

  $ui_kit_path = 'profiles/contrib/acquia_cms/misc/ui-kit.package.yml';

  $cohesion_api_data = acquia_cms_fetch_cohesion_api_data();

  $config = \Drupal::configFactory()->getEditable('cohesion.settings');

  foreach ($cohesion_api_data as $key => $value) {
    $config->set($key, $value);
  }
  $config->save();

  if ($config->get('api_key') !== '') {
    // Get a list of the batch items.
    $batch = AdministrationController::batchAction(TRUE);

    if (isset($batch['error'])) {
      return $batch;
    }

    foreach ($batch['operations'] as $operation) {
      $context = ['results' => []];
      $function = $operation[0];
      $args = $operation[1];

      if (function_exists($function)) {
        call_user_func_array($function, array_merge($args, [&$context]));
      }
    }

    // Give access to all routes.
    // Enable the routes.
    cohesion_website_settings_batch_import_finished(TRUE, $context['results'], '');

    if (isset($context['results']['error'])) {
      return ['error' => $context['results']['error']];
    }
  }
  else {
    return ['error' => t('Your Cohesion API KEY has not been set.') . $config->get('site_id')];
  }

  // Import UI kit.
  \Drupal::service('cohesion_sync.drush_helpers')->import(1, 0, $ui_kit_path, 0);
}

/**
 * Fetches Cohesion API keys.
 *
 * These are environment variables set by the Site Manager (or CI config).
 */
function acquia_cms_fetch_cohesion_api_data() {
  return [
    'api_url' => 'https://api.cohesiondx.com',
    'api_key' => getenv('COHESION_API_KEY'),
    'organization_key' => getenv('COHESION_ORG_KEY'),
  ];
}
