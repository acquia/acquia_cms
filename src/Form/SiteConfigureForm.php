<?php

namespace Drupal\acquia_cms\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Installer\Form\SiteConfigureForm as CoreSiteConfigureForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends the installer's site configuration form to configure Cohesion.
 */
final class SiteConfigureForm extends ConfigFormBase {

  /**
   * The decorated form object.
   *
   * @var \Drupal\Core\Installer\Form\SiteConfigureForm
   */
  private $decorated;

  /**
   * SiteConfigureForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Installer\Form\SiteConfigureForm $decorated
   *   The decorated form object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CoreSiteConfigureForm $decorated) {
    parent::__construct($config_factory);
    $this->decorated = $decorated;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      CoreSiteConfigureForm::create($container)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->decorated->getFormId();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = $this->decorated->buildForm($form, $form_state);

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => getenv('COHESION_API_KEY'),
    ];
    $form['organization_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organization key'),
      '#default_value' => getenv('COHESION_ORG_KEY'),
    ];

    return $form;
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->decorated->submitForm($form, $form_state);

    $api_key = $form_state->getValue('api_key');
    $org_key = $form_state->getValue('organization_key');

    if (empty($api_key) || empty($org_key)) {
      return;
    }
    $this->config('cohesion.settings')
      ->set('api_url', 'https://api.cohesiondx.com')
      ->set('api_key', $api_key)
      ->set('organization_key', $org_key)
      ->save();
  }

}
