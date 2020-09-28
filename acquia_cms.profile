<?php

/**
 * @file
 * Contains install-time code for the Acquia CMS profile.
 */

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector as Environment;
use Drupal\acquia_cms\Facade\CohesionFacade;
use Drupal\acquia_cms\Facade\TelemetryFacade;
use Drupal\acquia_cms\Form\SiteConfigureForm;
use Drupal\cohesion\Controller\AdministrationController;
use Drupal\cohesion_website_settings\Controller\WebsiteSettingsController;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function acquia_cms_form_user_login_form_alter(array &$form) {
  if (Drupal::config('acquia_cms.settings')->get('user_login_redirection')) {
    $form['#submit'][] = '\Drupal\acquia_cms\RedirectHandler::submitForm';
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function acquia_cms_form_cohesion_account_settings_form_alter(array &$form) {
  $config = Drupal::config('cohesion.settings');
  $cohesion_configured = $config->get('api_key') && $config->get('organization_key');
  // We should add submit handler, only if cohesion keys are not already set.
  if (!$cohesion_configured) {
    $form['#submit'][] = 'acquia_cms_cohesion_init';
    // Here we have added a separate submit handler to import UI kit because the
    // YAML validation is taking a lot of time and hence resulting into memory
    // limit.
    $form['#submit'][] = 'acquia_cms_import_ui_kit';
    // Here we are adding a separate submit handler to rebuild the cohesion
    // styles. Now the reason why we are doing this is because the rebuild is
    // expecting that all the entities of cohesion are in place but as the
    // cohesion is getting build for the first time and
    // acquia_cms_initialize_cohesion is responsible for importing the entities.
    // So we cannot execute both the batch process in a single function, Hence
    // to achieve the synchronous behaviour we have separated cohesion
    // configuration import and cohesion style rebuild functionality into
    // separate submit handlers.
    // @see \Drupal\cohesion_website_settings\Controller\WebsiteSettingsController::batch
    $form['#submit'][] = 'acquia_cms_rebuild_cohesion';
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

  // If the user has configured their Cohesion keys, import all elements.
  $tasks['acquia_cms_initialize_cohesion'] = [
    'display_name' => t('Import Site Studio elements'),
    'display' => $cohesion_configured,
    'type' => 'batch',
    'run' => $cohesion_configured ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
  ];
  $tasks['acquia_cms_install_ui_kit'] = [
    'display_name' => t('Import Site Studio components'),
    'display' => $cohesion_configured,
    'type' => 'batch',
    'run' => $cohesion_configured ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
  ];
  $tasks['acquia_cms_install_additional_modules'] = [];

  // If the user has opted in for Acquia Telemetry, send heartbeat event.
  $tasks['acquia_cms_send_heartbeat_event'] = [
    'run' => Drupal::service('module_handler')->moduleExists('acquia_telemetry') && Environment::isAhEnv() ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
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

  /** @var \Drupal\acquia_cms\Facade\CohesionFacade $facade */
  $facade = Drupal::classResolver(CohesionFacade::class);

  foreach ($facade->getAllPackages() as $package) {
    try {
      $facade->importPackage($package, $install_state['interactive']);
    }
    catch (Throwable $e) {
      Drupal::messenger()->addError($e->getMessage());
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
 * Installs additional required modules, depending on the environment.
 */
function acquia_cms_install_additional_modules() {
  $module_installer = Drupal::service('module_installer');

  if (Environment::isAhOdeEnv() || Environment::isAhIdeEnv() || Environment::isLocalEnv()) {
    $module_installer->install(['dblog', 'jsonapi_extras']);
  }
  else {
    $module_installer->install(['syslog']);
  }
}

/**
 * Imports all Cohesion elements.
 */
function acquia_cms_cohesion_init($form, FormStateInterface $form_state) {
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
  batch_set($batch);
}

/**
 * Imports cohesion ui kit, on submitting account settings form.
 */
function acquia_cms_import_ui_kit($form, FormStateInterface $form_state) {
  /** @var \Drupal\acquia_cms\Facade\CohesionFacade $facade */
  $facade = Drupal::classResolver(CohesionFacade::class);
  foreach ($facade->getAllPackages() as $package) {
    try {
      $facade->importPackage($package, TRUE);
    }
    catch (Throwable $e) {
      Drupal::messenger()->addError($e->getMessage());
    }
  }
}

/**
 * Rebuilds the cohesion componenets.
 */
function acquia_cms_rebuild_cohesion($form, FormStateInterface $form_state) {
  // Get the batch array filled with operations that should be performed during
  // rebuild.
  $batch = WebsiteSettingsController::batch(TRUE);
  if (isset($batch['error'])) {
    Drupal::messenger()->addError($batch['error']);
    return [];
  }
  batch_set($batch);
}
