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
function acquia_cms_install_tasks(): array {
  $tasks = [];

  // Set default logo for ACMS.
  $tasks['acquia_cms_set_logo'] = [];

  // Set default favicon for ACMS.
  $tasks['acquia_cms_set_favicon'] = [];

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

  // Don't include the rebuild task if installing via Drush, we automate that
  // elsewhere.
  if (PHP_SAPI !== 'cli') {
    $tasks['acquia_cms_rebuild_site_studio'] = [
      'display_name' => t('Rebuild Site Studio'),
      'display' => $cohesion_configured,
      'type' => 'batch',
      'run' => $cohesion_configured ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ];
  }

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
function acquia_cms_initialize_cohesion(): array {
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
 * Imports the Cohesion UI kit that ships with this profile.
 *
 * @param array $install_state
 *   The current state of the installation.
 *
 * @return array
 *   The batch job definition.
 */
function acquia_cms_install_ui_kit(array $install_state) {
  // During testing, we don't import the UI kit, because it takes forever.
  // Instead, we swap in a pre-built directory of Cohesion templates and assets.
  if (getenv('COHESION_ARTIFACT')) {
    return [];
  }

  /** @var \Drupal\acquia_cms\Facade\CohesionFacade $facade */
  $facade = Drupal::classResolver(CohesionFacade::class);

  // Interactive state means the site install is happening in the browser.
  $operations = ($install_state['interactive']) ? $facade->getAllOperations() : $facade->getAllOperations(TRUE);
  $batch = ['operations' => $operations];

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
 * Installs additional required modules, depending on the environment.
 */
function acquia_cms_install_additional_modules() {
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

  // Get the batch array filled with operations that should be performed during
  // rebuild. Also, we explicitly do not clear the cache during site install.
  $batch = WebsiteSettingsController::batch(TRUE);
  if (isset($batch['error'])) {
    Drupal::messenger()->addError($batch['error']);
  }

  return $batch;
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

/**
 * Set the path to the favicon file based on install directory.
 */
function acquia_cms_set_favicon() {
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
