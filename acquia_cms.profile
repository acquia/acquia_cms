<?php

/**
 * @file
 * Acquia CMS standard profile.
 */

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

  $cohesion_api_data = acquia_cms_fetch_cohesion_api_data();

  Drupal::configFactory()
    ->getEditable('cohesion.settings')
    ->set('api_key', $cohesion_api_data['api_key'])
    ->set('organization_key', $cohesion_api_data['organization_key'])
    ->save(TRUE);
}

/**
 * Fetches Cohesion API keys.
 *
 * These are environment variables set by the Site Manager (or CI config).
 */
function acquia_cms_fetch_cohesion_api_data() {
  return [
    'api_key' => getenv('COHESION_API_KEY'),
    'organization_key' => getenv('COHESION_ORG_KEY'),
  ];
}
