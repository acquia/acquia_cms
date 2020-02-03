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
}

/**
 * Initializes the Cohesion module with default configuration.
 */
function acquia_cms_initialize_cohesion() {

  $cohesion_api_data = acquia_cms_fetch_cohesion_api_data();

  $config = \Drupal::configFactory()->getEditable('cohesion.settings');

  foreach($cohesion_api_data AS $key => $value) {
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
}

/**
 * This function should be customer aware, and should ask a Site Manager API
 * service for the customers' credentials.
 * TODO: This is stubbed out with demo creds until SM implements the required
 * API.
 */
function acquia_cms_fetch_cohesion_api_data() {
  return [
    'api_url' => 'https://api.cohesiondx.com',
    'api_key' => 'umberloris-97542',
    'organization_key' => 'acquia-502227',
  ];
}
