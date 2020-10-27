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
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\media_library\MediaLibraryState;
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

  // Set default logo for ACMS.
  $tasks['acquia_cms_set_logo'] = [];

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
  Drupal::configFactory()
    ->getEditable('acquia_telemetry.settings')
    ->set('api_key', 'e896d8a97a24013cee91e37a35bf7b0b')
    ->save();
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
function acquia_cms_modules_installed(array $modules) : void {
  // Don't do anything during site installation, since that can break things in
  // a big way if modules are being installed due to changes made on the site
  // configuration form.
  if (InstallerKernel::installationAttempted()) {
    return;
  }

  // @todo The below code needs to be updated, or possibly removed outright,
  // once Site Studio's imports no longer cause memory exhaustion.
  $module_handler = Drupal::moduleHandler();

  if ($module_handler->moduleExists('acquia_telemetry')) {
    Drupal::classResolver(TelemetryFacade::class)->modulesInstalled($modules);
  }

  if ($module_handler->moduleExists('cohesion_sync')) {
    if (PHP_SAPI === 'cli') {
      $module_handler->invoke('cohesion_sync', 'modules_installed', [$modules]);
    }
    else {
      $modules = array_map([$module_handler, 'getModule'], $modules);
      // Instead of just adding the package import code we have used the batch
      // process here to overcome the memory limit exausted error.
      // To reproduce this issue just remove the below code and replace it with
      // the acquia_cms_module_cohesion_config_import function code. And this
      // memory limit exausted error occurs because cohesion is doing an
      // incredibly heavy operation while importing the cohesion configuration.
      //
      // Here we are checking if a batch is already running, if yes then it
      // appending a new batch set else creating a new batch to import cohesion
      // configurations. This is done because when we install the site via UI,
      // a batch process executes to install the site configuration, modules,
      // etc.
      // @see \Drupal\cohesion_sync\PackagerManager::getConfigImporter()
      $batch = batch_get();
      if (empty($batch['id'])) {
        $batch['operations'][] = [
          'acquia_cms_module_cohesion_config_import', [$modules],
        ];
        batch_set($batch);
      }
      else {
        $batch['current_set'] = 'cohesion_config_import';
        $batch['sets']['cohesion_config_import']['operations'][] = [
          'acquia_cms_module_cohesion_config_import', [$modules],
        ];
        _batch_append_set($batch, []);
      }
    }
  }
}

/**
 * Imports the module cohesion configuration via batch.
 */
function acquia_cms_module_cohesion_config_import(array $modules) {
  /** @var \Drupal\acquia_cms\Facade\CohesionFacade $facade */
  $facade = Drupal::classResolver(CohesionFacade::class);
  foreach ($modules as $module) {
    $packages = $facade->getPackagesFromExtension($module);
    foreach ($packages as $package) {
      try {
        $facade->importPackage($package, TRUE);
      }
      catch (Throwable $e) {
        Drupal::messenger()->addError($e->getMessage());
      }
    }
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

  if ($install_state['interactive']) {
    $batch = new BatchBuilder();
  }

  foreach ($facade->getAllPackages() as $package) {
    if (isset($batch)) {
      $batch->addOperation('_acquia_cms_install_ui_kit_package', [$package]);
    }
    else {
      _acquia_cms_install_ui_kit_package($package);
    }
  }

  if (isset($batch)) {
    return [
      $batch->toArray(),
    ];
  }
  else {
    // We already imported the packages, so there's nothing else to do.
    return [];
  }
}

/**
 * Imports a single sync package during site installation.
 *
 * @param string $package
 *   The path to the sync package, relative to the Drupal root.
 */
function _acquia_cms_install_ui_kit_package(string $package) : void {
  /** @var \Drupal\acquia_cms\Facade\CohesionFacade $facade */
  $facade = Drupal::classResolver(CohesionFacade::class);

  try {
    $facade->importPackage($package, FALSE);
  }
  catch (Throwable $e) {
    Drupal::messenger()->addError($e->getMessage());
  }
}

/**
 * Installs additional required modules, depending on the environment.
 */
function acquia_cms_install_additional_modules() {
  $module_installer = Drupal::service('module_installer');

  $is_dev = Environment::isAhIdeEnv() || Environment::isLocalEnv();

  if (Environment::isAhOdeEnv() || $is_dev) {
    $module_installer->install(['dblog', 'jsonapi_extras']);
  }
  else {
    $module_installer->install(['syslog']);
  }

  if (!$is_dev) {
    $module_installer->install(['autologout']);
  }

  // @todo once PF-3025 has been resolved, update this to work on IDEs too.
  if (Environment::isAhEnv() && !Environment::isAhIdeEnv()) {
    $module_installer->install(['imagemagick']);
    Drupal::configFactory()
      ->getEditable('imagemagick.settings')
      ->set('path_to_binaries', '/usr/bin/')
      ->save();
    Drupal::configFactory()
      ->getEditable('system.image')
      ->set('toolkit', 'imagemagick')
      ->save();
  }
}

/**
 * Imports all Cohesion elements immediately in a batch process.
 */
function acquia_cms_cohesion_init() {
  // Instead of returning the batch array, we are just executing the batch here.
  batch_set(acquia_cms_initialize_cohesion());
}

/**
 * Imports cohesion ui kit, on submitting account settings form.
 */
function acquia_cms_import_ui_kit() {
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
function acquia_cms_rebuild_cohesion() {
  // Get the batch array filled with operations that should be performed during
  // rebuild.
  $batch = WebsiteSettingsController::batch(TRUE);
  if (isset($batch['error'])) {
    Drupal::messenger()->addError($batch['error']);
  }
  batch_set($batch);
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
function acquia_cms_set_logo() {
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
