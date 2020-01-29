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

  return $tasks;
}

/**
 * Sets the default and administration themes.
 */
function acquia_cms_set_default_theme() {
  Drupal::configFactory()
    ->getEditable('system.theme')
    ->set('default', 'bartik')
    ->set('admin', 'claro')
    ->save(TRUE);
}
