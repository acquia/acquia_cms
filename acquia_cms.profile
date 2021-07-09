<?php

/**
 * @file
 * Contains install-time code for the Acquia CMS profile.
 */

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector as Environment;
use Drupal\acquia_cms\Facade\TelemetryFacade;
use Drupal\acquia_cms\Form\SiteConfigureForm;
use Drupal\acquia_cms_site_studio\Facade\CohesionFacade;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\media_library\MediaLibraryState;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request;

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
  if (PHP_SAPI == 'cli') {
    $first_key = key($tasks);
    $tasks[$first_key]['function'] = 'acquia_cms_pre_start_print_icon';
  }
  $tasks['install_bootstrap_full']['function'] = 'acquia_cms_pre_start';
}

/**
 * Method that calls another method to capture the installation start time.
 */
function acquia_cms_pre_start_print_icon($install_state) {
  $function = $install_state['active_task'];
  acquia_cms_set_install_time();
  return $function($install_state);
}

/**
 * Print acquia cms icon on terminal and then start first active install task.
 */
function acquia_cms_pre_start_print_icon($install_state) {
  $function = $install_state['active_task'];
  acquia_cms_print_icon();
  return $function($install_state);
}

/**
 * Set the install start time using state API.
 */
function acquia_cms_set_install_time() {
  $telemetry = Drupal::classResolver(AcquiaTelemetry::class);
  $telemetry->setTime('install_start_time');
}

/**
 * Prints the acquia cms icon on terminal.
 */
function acquia_cms_print_icon() {
  $output = new ConsoleOutput();
  $icon_path = DRUPAL_ROOT . '/' . drupal_get_path('profile', 'acquia_cms') . '/acquia_cms.icon.ascii';
  // For local development, we've created symlink. So, get symlink file path.
  $icon_path = !is_link($icon_path) ?: readlink($icon_path);
  if (file_exists($icon_path)) {
    $output->writeln('<info>' . file_get_contents($icon_path) . '</info>');
  }
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
function acquia_cms_send_heartbeat_event() {
  $telemetry = Drupal::classResolver(AcquiaTelemetry::class);
  $config = Drupal::config('cohesion.settings');
  $cohesion_configured = $config->get('api_key') && $config->get('organization_key');
  Drupal::configFactory()
    ->getEditable('acquia_telemetry.settings')
    ->set('api_key', 'e896d8a97a24013cee91e37a35bf7b0b')
    ->save();
  \Drupal::service('acquia.telemetry')->sendTelemetry('acquia_cms_installed', [
    'Application UUID' => Environment::getAhApplicationUuid(),
    'Site Environment' => Environment::getAhEnv(),
    'Install Time' => $telemetry->calculateTime('install_start_time', 'install_end_time'),
    'Rebuild Time' => $telemetry->calculateTime('rebuild_start_time', 'rebuild_end_time'),
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
 * Imports all Cohesion elements immediately in a batch process.
 */
function acquia_cms_cohesion_init() {
  /** @var \Drupal\acquia_cms\Facade\CohesionFacade $facade */
  $facade = Drupal::classResolver(CohesionFacade::class);
  $operations = $facade->getAllOperations(TRUE);
  // Instead of returning the batch array, we are just executing the batch here.
  $batch = acquia_cms_initialize_cohesion();
  $operations = array_merge($batch['operations'], $operations);
  $batch['operations'] = $operations;
  batch_set($batch);
}

/**
 * Rebuilds the cohesion components.
 */
function acquia_cms_rebuild_cohesion() {
  // Get the batch array filled with operations that should be performed during
  // rebuild.
  batch_set(acquia_cms_rebuild_site_studio());
}

/**
 * Rebuilds the site studio from installation.
 *
 * @return array
 *   Batch for rebuild operation.
 */
function acquia_cms_rebuild_site_studio() {
  $telemetry = Drupal::classResolver(AcquiaTelemetry::class);
  $telemetry->setTime('rebuild_start_time');
  // Get the batch array filled with operations that should be performed during
  // rebuild. Also, we explicitly do not clear the cache during site install.
  $batch = WebsiteSettingsController::batch(TRUE);

  if (isset($batch['error'])) {
    Drupal::messenger()->addError($batch['error']);
  }
  $batch['finished'] = 'acquia_cms_rebuild_site_studio_finished';
  return $batch;
}

/**
 * Method for performing functions once the rebuild is finished.
 */
function acquia_cms_rebuild_site_studio_finished() {
  // The 'success' parameter means no fatal PHP errors were detected. All
  // other error management should be handled using 'results'.
  $telemetry = Drupal::classResolver(AcquiaTelemetry::class);
  $telemetry->setTime('rebuild_end_time');
}

/**
 * Implements hook_form_alter().
 */
function acquia_cms_form_alter(array &$form, FormStateInterface $form_state, $form_id) : void {
  // Instead of directly adding a patch in core, we are modifying the ajax
  // callback.
  if ($form_id === 'views_form_media_library_widget_image') {
    $request = Drupal::request();
    $state = MediaLibraryState::fromRequest($request);
    if ($state->getOpenerId() === 'media_library.opener.cohesion') {
      $form['actions']['submit']['#ajax']['callback'] = 'alter_update_widget';
    }
  }
  // Trigger site studio config import and rebuild whenever user
  // try to save site studio account settings or the site studio core
  // form from tour dashboard page.
  $allowed_form_ids = [
    'cohesion_account_settings_form',
    'acquia_cms_site_studio_core_form',
    'acquia_cms_tour_installation_wizard',
  ];
  if (in_array($form_id, $allowed_form_ids)) {
    $config = Drupal::config('cohesion.settings');
    $cohesion_configured = $config->get('api_key') && $config->get('organization_key');
    // We should add submit handler, only if cohesion keys are not already set.
    if (!$cohesion_configured) {
      $form['#submit'][] = 'acquia_cms_cohesion_init';

      // Here we are adding a separate submit handler to rebuild the cohesion
      // styles. Now the reason why we are doing this is because the rebuild is
      // expecting that all the entities of cohesion are in place but as the
      // cohesion is getting build for the first time and
      // acquia_cms_initialize_cohesion is responsible for importing the
      // entities. So we cannot execute both the batch process in a single
      // function, Hence to achieve the synchronous behaviour we have separated
      // cohesion configuration import and cohesion style rebuild functionality
      // into separate submit handlers.
      // @see \Drupal\cohesion_website_settings\Controller\WebsiteSettingsController::batch
      $form['#submit'][] = 'acquia_cms_rebuild_cohesion';
    }
  }
}

/**
 * Callback for the media library image widget.
 */
function alter_update_widget(array &$form, FormStateInterface $form_state, Request $request) {
  // As cohesion is using angular for the media library popup, So the modal id
  // mismatch is causing the issue of no media selection. To resolve this we are
  // passing the selector in the CloseDialogCommand.
  // @see \Drupal\media_library\Plugin\views\field\MediaLibrarySelectForm::updateWidget().
  $field_id = $form_state->getTriggeringElement()['#field_id'];
  $selected_ids = $form_state->getValue($field_id);
  $selected_ids = $selected_ids ? array_filter(explode(',', $selected_ids)) : [];

  // Allow the opener service to handle the selection.
  $state = MediaLibraryState::fromRequest($request);

  return Drupal::service('media_library.opener_resolver')
    ->get($state)
    ->getSelectionResponse($state, $selected_ids)
    ->addCommand(new CloseDialogCommand('#modal-body'));
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
