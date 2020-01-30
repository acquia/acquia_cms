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
  $tasks['acquia_cms_initialize_cohesion'] = [];

  return $tasks;
}

/**
 * Sets the default and administration themes.
 */
function acquia_cms_set_default_theme() {
  Drupal::configFactory()
    ->getEditable('system.theme')
    ->set('default', 'cohesion')
    ->set('admin', 'claro')
    ->save(TRUE);
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
    ->set('image_browser.config.type', 'entity_imagebrowser')
    ->set('image_browser.config.dx8_entity_browser', 'media_browser')
    ->set('image_browser.content.type', 'entity_imagebrowser')
    ->set('image_browser.content.dx8_entity_browser', 'media_browser')
    ->save(TRUE);
}

/**
 * This function should be customer aware, and should ask a Site Manager API
 * service for the customers' credentials.
 * TODO: This is stubbed out with demo creds until SM implements the required
 * API.
 */
function acquia_cms_fetch_cohesion_api_data() {
  return [
    'api_key' => 'umberloris-97542',
    'organization_key' => 'acquia-502227',
  ];
}