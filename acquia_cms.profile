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
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Installer\InstallerKernel;

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
  // We want to capture the time when the installation starts.
  // This code helps capture time right when the drupal bootstrap happens.
  // The pre start function calls another method that performs the actual logic.
  $tasks['install_bootstrap_full']['function'] = 'acquia_cms_pre_start';
}

/**
 * Method that calls another method to capture the installation start time.
 */
function acquia_cms_pre_start($install_state) {
  $function = $install_state['active_task'];
  acquia_cms_set_install_time();
  return $function($install_state);
}

/**
 * Set the install start time using state API.
 */
function acquia_cms_set_install_time() {
  // The bootstrap method is called every time on UI installation.
  // Hence, it is important to check if variable is empty or not.
  // Set the time value only if variable is empty.
  // This helps in avoiding to capture incorrect start time.
  $start_time = \Drupal::state()->get('install_start_time');
  if (empty($start_time)) {
    $start_time = new DrupalDateTime();
    $formatted_time = acquia_cms_format_time($start_time);
    \Drupal::state()->set('install_start_time', $formatted_time);
  }
}

/**
 * Function for formatting date.
 *
 * @param \Drupal\Core\Datetime\DrupalDateTime $time
 *   Parameter that passes raw date value.
 *
 * @return \Drupal\Core\Datetime\DrupalDateTime
 *   The formatted date.
 */
function acquia_cms_format_time(DrupalDateTime $time) {
  $formatted = \Drupal::service('date.formatter')->format(
    $time->getTimestamp(), 'custom', 'Y-m-d h:i:s'
  );

  return $formatted;
}

/**
 * Implements hook_install_tasks().
 */
function acquia_cms_install_tasks(): array {
  $tasks = [];

  // Set default logo for ACMS.
  $tasks['install_acms_set_logo'] = [];

  // Set default favicon for ACMS.
  $tasks['install_acms_set_favicon'] = [];

  // Install default content for ACMS.
  $tasks['install_acms_import_default_content'] = [];

  // Install additional acquia cms modules.
  $tasks['install_acms_additional_modules'] = [];

  // If the user has configured their Cohesion keys,
  // and site studio module exists lets import all elements.
  $config = Drupal::config('cohesion.settings');
  $cohesion_configured = $config->get('api_key') && $config->get('organization_key');

  // Allow acquia_cms_site_studio module to be install using profile.
  if (Drupal::service('module_handler')->moduleExists('acquia_cms_site_studio')) {
    $tasks['install_acms_site_studio_initialize'] = [
      'display_name' => t('Import Site Studio elements'),
      'display' => $cohesion_configured,
      'type' => 'batch',
      'run' => $cohesion_configured ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ];
    $tasks['install_acms_site_studio_ui_kit'] = [
      'display_name' => t('Import Site Studio components'),
      'display' => $cohesion_configured,
      'type' => 'batch',
      'run' => $cohesion_configured ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ];
  }

  $tasks['install_acms_finished'] = [];

  // Don't include the rebuild task & don't send heartbeat event to telemetry.
  // if installing site via Drush.
  // @see src/Commands/SiteInstallCommands.php.
  // Also send hearbeat event only for UI here.
  // For cli we are sending it from file mentioned above.
  if (PHP_SAPI !== 'cli') {
    $tasks['install_acms_site_studio_rebuild'] = [
      'display_name' => t('Rebuild Site Studio'),
      'display' => $cohesion_configured,
      'type' => 'batch',
      'run' => $cohesion_configured ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ];
    $tasks['install_acms_send_heartbeat_event'] = [
      'run' => Drupal::service('module_handler')->moduleExists('acquia_telemetry') && Environment::isAhEnv() ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ];
  }

  return $tasks;
}

/**
 * Send heartbeat event after site installation.
 *
 * @see src/Commands/SiteInstallCommands.php
 */
function install_acms_send_heartbeat_event() {
  // Get time values and calculate the difference.
  $time_values = acquia_cms_process_time_values();
  $install_time_diff = acquia_cms_calculate_time_diff(
    $time_values['install_start_time'],
    $time_values['install_end_time']
  );
  $rebuild_time_diff = acquia_cms_calculate_time_diff(
    $time_values['rebuild_start_time'],
    $time_values['rebuild_end_time']
  );
  $config = Drupal::config('cohesion.settings');
  $cohesion_configured = $config->get('api_key') && $config->get('organization_key');
  Drupal::configFactory()
    ->getEditable('acquia_telemetry.settings')
    ->set('api_key', 'e896d8a97a24013cee91e37a35bf7b0b')
    ->save();
  \Drupal::service('acquia.telemetry')->sendTelemetry('acquia_cms_installed', [
    'Application UUID' => Environment::getAhApplicationUuid(),
    'Site Environment' => Environment::getAhEnv(),
    'Install Time' => $install_time_diff,
    'Rebuild Time' => $rebuild_time_diff,
    'Site Studio Install Status' => $cohesion_configured ? 1 : 0,
  ]);
}

/**
 * Function to process time values.
 *
 * @return array
 *   Returns all the processed time values.
 */
function acquia_cms_process_time_values() {
  // Get the install & rebuild time from state API.
  $install_start_time = \Drupal::state()->get('install_start_time');
  $install_end_time = \Drupal::state()->get('install_end_time');
  $rebuild_start_time = \Drupal::state()->get('rebuild_start_time');
  $rebuild_end_time = \Drupal::state()->get('rebuild_end_time');
  // Calculate the time diff from the function and pass it to telemetry.
  $time_values = [
    'install_start_time' => new DrupalDateTime($install_start_time),
    'install_end_time' => new DrupalDateTime($install_end_time),
    'rebuild_start_time' => new DrupalDateTime($rebuild_start_time),
    'rebuild_end_time' => new DrupalDateTime($rebuild_end_time),
  ];

  return $time_values;
}

/**
 * Function to calculate the time difference.
 *
 * @param \Drupal\Core\Datetime\DrupalDateTime $start_time
 *   Variable that stores the start time.
 * @param \Drupal\Core\Datetime\DrupalDateTime $end_time
 *   Variable that stores the end time.
 *
 * @return int
 *   Returns the time difference in seconds.
 */
function acquia_cms_calculate_time_diff(DrupalDateTime $start_time, DrupalDateTime $end_time) {
  // Perform the subtraction and return the time in seconds.
  $timeDiff = $end_time->getTimestamp() - $start_time->getTimestamp();

  // Return the difference.
  return $timeDiff;
}

/**
 * Import site studio uikit.
 *
 * @throws Exception
 */
function install_acms_site_studio_ui_kit() {
  // During testing, we don't import the UI kit, because it takes forever.
  // Instead, we swap in a pre-built directory of Cohesion templates and assets.
  if (getenv('COHESION_ARTIFACT')) {
    return [];
  }

  /** @var \Drupal\acquia_cms\Facade\CohesionFacade $facade */
  $facade = Drupal::classResolver(CohesionFacade::class);

  // Site studio will rebuild packages (fetch HTML/CSS via the API) by default
  // on import. Passing this bool as TRUE will skip the rebuild, since we force
  // a total rebuild at the end. This cuts install times approximately in half,
  // especially via Drush.
  $operations = $facade->getAllOperations(TRUE);
  $batch = [
    'operations' => $operations,
  ];

  // Set batch along with drush backend process if site is being
  // installed via Drush, so that we can show log on the screen during
  // site studio package import/validate.
  if (PHP_SAPI == 'cli') {
    batch_set($batch);
    drush_backend_batch_process();
  }
  else {
    return $batch;
  }
}

/**
 * Install default content as part of install task.
 */
function install_acms_import_default_content() {
  if (\Drupal::moduleHandler()->moduleExists('acquia_cms_image')) {
    \Drupal::service('default_content.importer')->importContent('acquia_cms_image');
  }
}

/**
 * Implements hook_modules_installed().
 */
function acquia_cms_modules_installed(array $modules) : void {
  // Don't do anything during site installation, since that can break things in
  // a big way if modules are being installed due to changes made on the site
  // configuration form.
  if (InstallerKernel::installationAttempted()) {
    return;
  }

  $module_handler = Drupal::moduleHandler();

  if ($module_handler->moduleExists('acquia_telemetry')) {
    Drupal::classResolver(TelemetryFacade::class)->modulesInstalled($modules);
  }

  if ($module_handler->moduleExists('cohesion_sync')) {
    $module_handler->invoke('cohesion_sync', 'modules_installed', [$modules]);
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
 * Implements hook_module_implements_alter().
 */
function acquia_cms_module_implements_alter(array &$implementations, string $hook) : void {
  // @todo The below code needs to be updated once the memory limit issue is
  // fixed by the site studio.
  if ($hook === 'modules_installed') {
    // Prevent cohesion_sync from reacting to module installation, for an
    // excellent reason: it tries to import all of the new module's sync
    // packages, at once, in the current request, which leads to memory errors.
    // We replace it with a slightly smarter implementation that uses the batch
    // system when installing a module via the UI.
    unset($implementations['cohesion_sync']);
  }
}

/**
 * Method that calls another method to capture the installation end time.
 */
function install_acms_finished() {
  // The 'success' parameter means no fatal PHP errors were detected. All
  // other error management should be handled using 'results'.
  $end_time = new DrupalDateTime();
  $formatted_time = acquia_cms_format_time($end_time);
  \Drupal::state()->set('install_end_time', $formatted_time);
}

/**
 * Installs additional required modules, depending on the environment.
 */
function install_acms_additional_modules() {
  // Call ToggleModules Service.
  \Drupal::service('acquia_cms_common.toggle_modules')->ToggleModules();
  // Save configuration for imagemagick.
  if (Environment::isAhEnv() && !Environment::isAhIdeEnv()) {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('imagemagick')) {
      \Drupal::configFactory()
        ->getEditable('imagemagick.settings')
        ->set('path_to_binaries', '/usr/bin/')
        ->save();
      \Drupal::configFactory()
        ->getEditable('system.image')
        ->set('toolkit', 'imagemagick')
        ->save();
    }
  }
}

/**
 * Set the path to the logo file based on install directory.
 */
function install_acms_set_logo() {
  $acquia_cms_path = drupal_get_path('profile', 'acquia_cms');

  Drupal::configFactory()
    ->getEditable('system.theme.global')
    ->set('logo', [
      'path' => $acquia_cms_path . '/acquia_cms.png',
      'url' => '',
      'use_default' => FALSE,
    ])
    ->save(TRUE);
}

/**
 * Set the path to the favicon file based on install directory.
 */
function install_acms_set_favicon() {
  $acquia_cms_path = drupal_get_path('profile', 'acquia_cms');

  Drupal::configFactory()
    ->getEditable('system.theme.global')
    ->set('favicon', [
      'path' => $acquia_cms_path . '/acquia_cms.png',
      'url' => '',
      'use_default' => FALSE,
    ])
    ->save(TRUE);
}
