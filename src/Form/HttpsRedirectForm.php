<?php

namespace Drupal\acquia_cms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to configure the HTTPS redirects.
 *
 * @internal
 *   This is an internal part of Acquia CMS and may be changed in any way, or
 *   removed at any time, without warning. You shouldn't touch it. If you
 *   absolutely must touch it, please copy it into your own code base.
 */
class HttpsRedirectForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['acquia_cms.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_https_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['acquia_cms_https'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable enforced HTTPS redirects.'),
      '#default_value' => $this->config('acquia_cms.settings')->get('acquia_cms_https') ?? 0,
      '#description' => $this->t('Enable the HTTPS redirect'),
    ];

    $form['#cache']['tags'][] = 'config:acquia_cms.settings';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $https_status = $form_state->getValue('acquia_cms_https');

    // Save the configuration for the https status.
    $this->config('acquia_cms.settings')
      ->set('acquia_cms_https', $https_status)
      ->save(TRUE);

    return parent::submitForm($form, $form_state);
  }

}
