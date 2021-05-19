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
