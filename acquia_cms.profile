<?php

/**
 * @file
 * Contains install-time code for the Acquia CMS profile.
 */

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector as Environment;
use Acquia\Utility\AcquiaTelemetry;
use Drupal\acquia_cms\Facade\TelemetryFacade;
use Drupal\acquia_cms\Form\SiteConfigureForm;
use Drupal\acquia_cms_site_studio\Facade\CohesionFacade;
use Drupal\Core\Installer\InstallerKernel;
use Symfony\Component\Console\Output\ConsoleOutput;

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
    $tasks[$first_key]['function'] = 'install_acms_pre_start_print_icon';
  }
  $tasks['install_bootstrap_full']['function'] = 'install_acms_pre_start';
}

/**
 * Print acquia cms icon on terminal and then start first active install task.
 */
function install_acms_pre_start_print_icon($install_state) {
  $function = $install_state['active_task'];
  acquia_cms_print_icon();
  return $function($install_state);
}

/**
 * Prints the acquia cms icon on terminal.
 */
function acquia_cms_print_icon() {
  $output = new ConsoleOutput();
  $profile_path = \Drupal::service('extension.path.resolver')->getPath('profile', 'acquia_cms');
  $icon_path = DRUPAL_ROOT . '/' . $profile_path . '/acquia_cms.icon.ascii';
  // For local development, we've created symlink. So, get symlink file path.
  $icon_path = !is_link($icon_path) ? $icon_path : readlink($icon_path);
  if (file_exists($icon_path)) {
    $output->writeln('<info>' . file_get_contents($icon_path) . '</info>');
  }
}

/**
 * Method that calls another method to capture the installation start time.
 */
function install_acms_pre_start($install_state) {
  $function = $install_state['active_task'];
  acquia_cms_set_install_time();
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
  $site_studio_api_key = getenv('SITESTUDIO_API_KEY');
  $site_studio_org_key = getenv('SITESTUDIO_ORG_KEY');
  /** @var \Drupal\acquia_cms\Facade\CohesionFacade $facade */
  $facade = Drupal::classResolver(CohesionFacade::class);

  // Site studio will rebuild packages (fetch HTML/CSS via the API) by default
  // on import. Passing this bool as TRUE will skip the rebuild, since we force
  // a total rebuild at the end. This cuts install times approximately in half,
  // especially via Drush.
  //  $operations = $facade->getAllOperations(TRUE);
  $modules = [];
  $operations[] = ['set_site_studio_credentials', [$site_studio_api_key, $site_studio_org_key]];
  foreach ($facade->getSortedModules() as $extension) {
    $modules[] = $extension->getName();
  }
  $operations[] = ['trigger_module_install_hook', [$modules]];
  $batch = [
    'title' => 'Importing site studio package from all modules',
    'operations' => $operations,
    'finished' => 'update_site_studio_settings',
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
 * Trigger module installed hook from cohesion_sync.
 */
function trigger_module_install_hook($modules) {
  Drupal::moduleHandler()->invoke('cohesion_sync', 'modules_installed', [$modules]);
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
 * Method that calls another method to capture the installation end time.
 */
function install_acms_finished() {
  $telemetry = Drupal::classResolver(AcquiaTelemetry::class);
  $telemetry->setTime('install_end_time');
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

  /*
   * We've to explicitly set purge plugin as acquia_purge, If Acquia Purge
   * module is enabled, else this would give below error:
   *
   * ERROR: Purgers:There is no purger loaded which means that you need a module
   * enabled to provide a purger plugin to clear your external cache or CDN.
   */
  if (Drupal::service('module_handler')->moduleExists('acquia_purge')) {
    $config = \Drupal::service('purge.purgers');
    $config->setPluginsEnabled(['cee22bc3fe' => 'acquia_purge']);
  }
}

/**
 * Set the path to the logo file based on install directory.
 */
function install_acms_set_logo() {
  $acquia_cms_path = \Drupal::service('extension.list.profile')->getPath('acquia_cms');

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
  $acquia_cms_path = \Drupal::service('extension.list.profile')->getPath('acquia_cms');

  Drupal::configFactory()
    ->getEditable('system.theme.global')
    ->set('favicon', [
      'path' => $acquia_cms_path . '/acquia_cms.png',
      'url' => '',
      'use_default' => FALSE,
    ])
    ->save(TRUE);
}

/**
 * Update config ignore settings.
 */
function acquia_cms_update_8001() {
  $config = \Drupal::configFactory()->getEditable('config_ignore.settings');
  // Get existing ignore config and append the new one.
  $existing_ignore_config = $config->get('ignored_config_entities');
  $new_ignore_config = [
    'cohesion.settings',
    'purge.plugins',
    'purge.logger_channels',
  ];
  $updated_ignore_config = array_unique(array_merge($existing_ignore_config, $new_ignore_config));
  $config->set('ignored_config_entities', $updated_ignore_config);
  $config->set('enable_export_filtering', TRUE);
  $config->save(TRUE);
}

/**
 * Uninstall page_cache module.
 */
function acquia_cms_update_8002() {
  if (\Drupal::moduleHandler()->moduleExists('page_cache')) {
    \Drupal::service('module_installer')->uninstall(['page_cache']);
  }
}

/**
 * Prepares variables for maintenance page templates.
 *
 * Default template: maintenance-page.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content - An array of page content.
 *
 * @see template_preprocess_maintenance_page()
 */
function acquia_cms_preprocess_maintenance_page(array &$variables) {
  $variables['#attached']['library'][] = 'seven/install-page';
  $variables['#attached']['library'][] = 'acquia_claro/install-page';
  $acquia_cms_path = \Drupal::service('extension.list.profile')->getPath('acquia_cms');
  $variables['install_page_logo_path'] = '/' . $acquia_cms_path . '/acquia_cms.png';
}

/**
 * Prepares variables for install page templates.
 *
 * Default template: install-page.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content - An array of page content.
 *
 * @see template_preprocess_install_page()
 */
function acquia_cms_preprocess_install_page(array &$variables) {
  $variables['drupal_core_version'] = \Drupal::VERSION;
  $variables['#attached']['library'][] = 'seven/install-page';
  $variables['#attached']['library'][] = 'acquia_claro/install-page';
  $acquia_cms_path = \Drupal::service('extension.list.profile')->getPath('acquia_cms');
  $variables['install_page_logo_path'] = '/' . $acquia_cms_path . '/acquia_cms.png';
}
