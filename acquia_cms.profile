<?php

/**
 * @file
 * Contains install-time code for the Acquia CMS profile.
 */

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector as Environment;
use Drupal\acquia_cms\Facade\TelemetryFacade;
use Drupal\acquia_cms\Form\SiteConfigureForm;
use Drupal\cohesion\Controller\AdministrationController;
use Drupal\Component\Serialization\Yaml;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function acquia_cms_form_user_login_form_alter(array &$form) {
  if (Drupal::config('acquia_cms.settings')->get('user_login_redirection')) {
    $form['#submit'][] = '\Drupal\acquia_cms\RedirectHandler::submitForm';
  }
}

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
  $send_telemetry = Drupal::service('module_handler')->moduleExists('acquia_telemetry') && Environment::isAhEnv();

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
  $tasks['acquia_cms_install_logger'] = [
    'run' => $cohesion_configured ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
  ];
  // If the user has opted in for Acquia Telemetry, send heartbeat event.
  $tasks['acquia_cms_send_heartbeat_event'] = [
    'run' => $send_telemetry ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
  ];

  return $tasks;
}

/**
 * Send heartbeat event after site installation.
 */
function acquia_cms_send_heartbeat_event() {
  \Drupal::service('acquia.telemetry')->sendTelemetry('acquia_cms_installed', [
    'Application UUID' => Environment::getAhApplicationUuid(),
    'Site Environment' => Environment::getAhEnv(),
  ]);
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
  // @todo When Cohesion provides a service to generate this batch job, use
  // that instead of calling an internal method of an internal controller, since
  // this may break at any time due to internal refactoring done by Cohesion.
  $batch = AdministrationController::batchAction(TRUE);
  if (isset($batch['error'])) {
    Drupal::messenger()->addError($batch['error']);
    return [];
  }
  return $batch;
}

/**
 * Implements hook_modules_installed().
 */
function acquia_cms_modules_installed(array $modules) {
  if (\Drupal::service('module_handler')->moduleExists('acquia_telemetry')) {
    Drupal::classResolver(TelemetryFacade::class)->modulesInstalled($modules);
  }
}

/**
 * Implements hook_modules_uninstalled().
 */
function acquia_cms_modules_uninstalled(array $modules) {
  if (\Drupal::service('module_handler')->moduleExists('acquia_telemetry')) {
    Drupal::classResolver(TelemetryFacade::class)->modulesUninstalled($modules);
  }
}

/**
 * Imports the Cohesion UI kit that ships with this profile.
 *
 * @param array $install_state
 *   The current state of the installation.
 *
 * @return array
 *   The batch job definition.
 */
function acquia_cms_install_ui_kit(array &$install_state) {
  // During testing, we don't import the UI kit, because it takes forever.
  // Instead, we swap in a pre-built directory of Cohesion templates and assets.
  if (getenv('COHESION_ARTIFACT')) {
    return [];
  }

  /** @var \Drupal\cohesion_sync\PackagerManager $packager */
  $packager = Drupal::service('cohesion_sync.packager');

  // Scan all installed modules for Cohesion sync packages.
  $packages = [];
  foreach (Drupal::moduleHandler()->getModuleList() as $module) {
    $module_path = $module->getPath();
    $package_list = "$module_path/config/dx8/packages.yml";

    if (file_exists($package_list)) {
      $package_list = file_get_contents($package_list);
      $package_list = Yaml::decode($package_list);

      foreach ($package_list as $package_file) {
        $packages[] = "$module_path/$package_file";
      }
    }
  }
  // Finally, import the main UI kit.
  $packages[] = __DIR__ . '/misc/ui-kit.package.yml';

  foreach ($packages as $package) {
    assert(file_exists($package), "The UI kit package ($package) does not exist.");

    // Prepare to import the package. This code is delicate because it was
    // basically written by rooting around in Cohesion's internals. So be
    // extremely careful when changing it.
    // @see \Drupal\cohesion_sync\Form\ImportFileForm::submitForm()
    // @see \Drupal\cohesion_sync\Drush\CommandHelpers::import()
    try {
      $action_data = $packager->validateYamlPackageStream($package);

      // Basically, overwrite everything without validating. This is equivalent
      // to passing the --overwrite-all and --force options to the 'sync:import'
      // Drush command.
      foreach ($action_data as &$action) {
        $action['entry_action_state'] = ENTRY_EXISTING_OVERWRITTEN;
      }
    }
    catch (\Throwable $e) {
      Drupal::messenger()->addError($e->getMessage());
      continue;
    }

    // If we are installing in the UI, prepare a batch job to import the
    // package. Otherwise, just execute the import right now.
    if ($install_state['interactive']) {
      $packager->applyBatchYamlPackageStream($package, $action_data);
    }
    else {
      $packager->applyYamlPackageStream($package, $action_data);
    }
  }

  if ($install_state['interactive']) {
    // We want to return the batch jobs by value, because the installer will
    // call batch_set() on them. However, because the packager has already done
    // that, we also need to clear the static variables maintained by
    // batch_get() so that the installer doesn't add more jobs than we actually
    // want to run.
    // @see \Drupal\cohesion_sync\PackagerManager::validateYamlPackageStream()
    // @see install_run_task()
    $batch = batch_get();
    $batch_static = &batch_get();
    $batch_static['sets'] = [];

    return $batch['sets'];
  }
  else {
    // We already imported the packages, so there's nothing else to do.
    return [];
  }
}

/**
 * Installs module to handle system logs on a specific environment.
 *
 * @param array $install_state
 *   The current state of the installation.
 */
function acquia_cms_install_logger(array &$install_state) {
  $module_handler = \Drupal::service('module_handler');
  $module_installer = \Drupal::service('module_installer');

  // Install dblog for system logs in Local, IDE or ODE environment.
  if (Environment::isAhOdeEnv() || Environment::isAhIdeEnv() || Environment::isLocalEnv()) {
    if (!$module_handler->moduleExists('dblog')) {
      $module_installer->install(['dblog']);
    }
  }
  // For other environments install syslog for system logs.
  else {
    if (!$module_handler->moduleExists('syslog')) {
      $module_installer->install(['syslog']);
    }
  }
}
