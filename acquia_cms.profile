<?php

/**
 * @file
 * Contains install-time code for the Acquia CMS profile.
 */

use Drupal\acquia_cms\Form\SiteConfigureForm;
use Drupal\cohesion\Controller\AdministrationController;

/**
 * Implements hook_install_tasks_alter().
 */
function acquia_cms_install_tasks_alter(array &$tasks) {
  // Decorate the site configuration form to allow the user to configure their
  // Cohesion keys at install time.
  $tasks['install_configure_form']['function'] = SiteConfigureForm::class;
}

/**
 * Implements hook_install_tasks().
 */
function acquia_cms_install_tasks() {
  $tasks = [];

  $config = Drupal::config('cohesion.settings');
  $cohesion_configured = $config->get('api_key') && $config->get('organization_key');

  // If the user has configured their Cohesion keys, import all elements.
  $tasks['acquia_cms_initialize_cohesion'] = [
    'display_name' => t('Import Cohesion elements'),
    'display' => $cohesion_configured,
    'type' => 'batch',
    'run' => $cohesion_configured ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
  ];
  $tasks['acquia_cms_install_ui_kit'] = [
    'display_name' => t('Import Cohesion components'),
    'display' => $cohesion_configured,
    'type' => 'batch',
    'run' => $cohesion_configured ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
  ];
  return $tasks;
}

/**
 * Imports all Cohesion elements.
 *
 * @return array
 *   The batch job definition.
 */
function acquia_cms_initialize_cohesion() {
  // Build and run the batch job for the initial import of Cohesion elements and
  // assets.
  $batch = AdministrationController::batchAction(TRUE);
  if (isset($batch['error'])) {
    Drupal::messenger()->addError($batch['error']);
    return [];
  }
  return $batch;
}

/**
 * Imports the Cohesion UI kit that ships with this profile.
 *
 * @return array
 *   The batch job definition.
 */
function acquia_cms_install_ui_kit() {
  $ui_kit = __DIR__ . '/misc/ui-kit.package.yml';
  assert(file_exists($ui_kit), "The UI kit package ($ui_kit) does not exist.");

  // Import the UI kit from the YAML file we ship with this profile. This code
  // is delicate because it was basically written by rooting around in
  // Cohesion's internals. So be extremely careful when changing it.
  // @see \Drupal\cohesion_sync\Form\ImportFileForm::submitForm()
  // @see \Drupal\cohesion_sync\Drush\CommandHelpers::import()
  /** @var \Drupal\cohesion_sync\PackagerManager $packager */
  $packager = Drupal::service('cohesion_sync.packager');
  try {
    $action_data = $packager->validateYamlPackageStream($ui_kit);

    // Basically, overwrite everything without validating. This is equivalent
    // to passing the --overwrite-all and --force options to the 'sync:import'
    // Drush command.
    foreach ($action_data as &$action) {
      $action['entry_action_state'] = ENTRY_EXISTING_OVERWRITTEN;
    }
    $packager->applyBatchYamlPackageStream($ui_kit, $action_data);
  }
  catch (\Throwable $e) {
    Drupal::messenger()->addError($e->getMessage());
    return [];
  }

  // We want to return the batch jobs by value, because the installer will call
  // batch_set() on them. However, because the packager has already done that,
  // we also need to clear the static variables maintained by batch_get() so
  // that the installer doesn't add more jobs than we actually want to run.
  // @see \Drupal\cohesion_sync\PackagerManager::validateYamlPackageStream()
  // @see install_run_task()
  $batch = batch_get();
  $batch_static = &batch_get();
  $batch_static['sets'] = [];

  return $batch['sets'];
}
