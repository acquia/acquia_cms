<?php

/**
 * @file
 * Contains install-time code for the Acquia CMS profile.
 */

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector as Environment;
use Drupal\acquia_cms\Facade\TelemetryFacade;
use Drupal\acquia_cms\Form\SiteConfigureForm;
use Drupal\Core\Installer\InstallerKernel;

/**
 * Implements hook_install_tasks().
 */
function acquia_cms_install_tasks(): array {
  $tasks = [];
  // Set default logo for ACMS.
  $tasks['acquia_cms_site_studio_set_logo'] = [];

  // Set default favicon for ACMS.
  $tasks['acquia_cms_site_studio_set_favicon'] = [];

  // Install default content for ACMS.
  $tasks['acquia_cms_import_default_content'] = [];

  $config = Drupal::config('cohesion.settings');
  $cohesion_configured = $config->get('api_key') && $config->get('organization_key');

  if ($cohesion_configured) {
    $installer = \Drupal::service('module_installer');
    // Install single module.
    $installer->install(['acquia_cms_site_studio']);
  }

  if (\Drupal::service('module_handler')->moduleExists('acquia_cms_site_studio')) {
    // If the user has configured their Cohesion keys, import all elements.
    $tasks['acquia_cms_site_studio_initialize_cohesion'] = [
      'display_name' => t('Import Site Studio elements'),
      'display' => $cohesion_configured,
      'type' => 'batch',
      'run' => $cohesion_configured ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ];
    $tasks['acquia_cms_site_studio_install_ui_kit'] = [
      'display_name' => t('Import Site Studio components'),
      'display' => $cohesion_configured,
      'type' => 'batch',
      'run' => $cohesion_configured ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ];

    // Don't include the rebuild task if installing via Drush, we automate that
    // elsewhere.
    if (PHP_SAPI !== 'cli') {
      $tasks['acquia_cms_site_studio_rebuild_site_studio'] = [
        'display_name' => t('Rebuild Site Studio'),
        'display' => $cohesion_configured,
        'type' => 'batch',
        'run' => $cohesion_configured ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
      ];
    }

    $tasks['acquia_cms_site_studio_install_additional_modules'] = [];

    // If the user has opted in for Acquia Telemetry, send heartbeat event.
    $tasks['acquia_cms_site_studio_send_heartbeat_event'] = [
      'run' => Drupal::service('module_handler')->moduleExists('acquia_telemetry') && Environment::isAhEnv() ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ];
  }
  return $tasks;
}

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

  // Site studio will rebuild packages (fetch HTML/CSS via the API) by default
  // on import. Passing this bool as TRUE will skip the rebuild, since we force
  // a total rebuild at the end. This cuts install times approximately in half,
  // especially via Drush.
  $operations = $facade->getAllOperations(TRUE);
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
 * Install default content as part of install task.
 */
function acquia_cms_import_default_content() {
  if (\Drupal::moduleHandler()->moduleExists('acquia_cms_image')) {
    \Drupal::service('default_content.importer')->importContent('acquia_cms_image');
  }
}
