<?php

namespace Drupal\acquia_cms_site_studio\Form;

use Drupal\acquia_cms\Form\SiteConfigureForm;
use Drupal\acquia_cms_tour\Form\AcquiaGoogleMapsAPIForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Installer\Form\SiteConfigureForm as CoreSiteConfigureForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends the installer's site configuration form to configure Cohesion.
 */
class AcquiaCmsSiteStudioSiteConfigureForm extends SiteConfigureForm {

  /**
   * The Cohesion API URL.
   *
   * @var string
   */
  private $apiUrl;

  /**
   * SiteConfigureForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param string $api_url
   *   The Cohesion API URL.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Installer\Form\SiteConfigureForm $site_form
   *   The decorated site configuration form object.
   * @param \Drupal\acquia_cms_tour\Form\AcquiaGoogleMapsAPIForm $maps_form
   *   The decorated Google Maps configuration form object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, string $api_url, ModuleInstallerInterface $module_installer, ModuleHandlerInterface $module_handler, CoreSiteConfigureForm $site_form, AcquiaGoogleMapsAPIForm $maps_form) {
    parent::__construct($config_factory, $module_installer, $module_handler, $site_form, $maps_form);
    $this->apiUrl = $api_url;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cohesion.api.utils')->getAPIServerURL(),
      $container->get('module_installer'),
      $container->get('module_handler'),
      CoreSiteConfigureForm::create($container),
      AcquiaGoogleMapsAPIForm::create($container)
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cohesion.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['cohesion'] = [
      'api_key' => [
        '#type' => 'textfield',
        '#title' => $this->t('API key'),
        '#default_value' => getenv('SITESTUDIO_API_KEY'),
      ],
      'organization_key' => [
        '#type' => 'textfield',
        '#title' => $this->t('Organization key'),
        '#default_value' => getenv('SITESTUDIO_ORG_KEY'),
      ],
      '#type' => 'details',
      '#title' => $this->t('Acquia Site Studio'),
      '#description' => $this->t('Enter your API key and organization key to automatically set up Acquia Site Studio (note that this process can take a while). If you do not want to use Site Studio right now, leave these fields blank -- you can always set it up later.'),
      '#tree' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $api_key = $form_state->getValue(['cohesion', 'api_key']);
    $org_key = $form_state->getValue(['cohesion', 'organization_key']);

    if ($api_key && $org_key) {
      // For reasons I can't fathom, not resetting the config factory causes
      // a non-interactive install (i.e., drush site:install) to be unable to
      // load the API and organization keys in acquia_cms_install_tasks(), which
      // in turn results in Cohesion's stuff not getting imported. So, although
      // this may look bizarre, leave it as-is.
      $this->resetConfigFactory();

      $this->config('cohesion.settings')
        ->set('api_url', $this->apiUrl)
        ->set('api_key', $api_key)
        ->set('organization_key', $org_key)
        ->save(TRUE);
    }
    parent::submitForm($form, $form_state);
  }

}
