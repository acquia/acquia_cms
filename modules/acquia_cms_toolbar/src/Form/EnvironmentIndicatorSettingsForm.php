<?php

namespace Drupal\acquia_cms_toolbar\Form;

/**
 * @file
 * Contains Drupal\acquia_cms_toolbar\Form\EnvironmentIndicatorSettingsForm.
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Environment indicator Configuration form.
 *
 * @package Drupal\acquia_cms_toolbar\Form
 */
class EnvironmentIndicatorSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'environment_indicator.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'environment_indicator_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('environment_indicator.settings');
    $form = parent::buildForm($form, $form_state);
    $form['production'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Production Environment'),
      '#description' => $this->t('Please use hex color codes.'),
    ];

    $form['production']['prod_bgcolor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background color'),
      '#default_value' => $config->get('prod_bgcolor') ?? '#CD3A3D',
    ];

    $form['production']['prod_text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text Color'),
      '#default_value' => $config->get('prod_text_color') ?? '#FFFFFF',
    ];

    $form['staging'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Staging Environment'),
      '#description' => $this->t('Please use hex color codes.'),
    ];

    $form['staging']['stage_bgcolor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background color'),
      '#default_value' => $config->get('stage_bgcolor') ?? '#FEB32B',
    ];

    $form['staging']['stage_text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text Color'),
      '#default_value' => $config->get('stage_text_color') ?? '#000000',
    ];

    $form['development'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Development Environment'),
      '#description' => $this->t('Please use hex color codes.'),
    ];

    $form['development']['dev_bgcolor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background color'),
      '#default_value' => $config->get('dev_bgcolor') ?? '#498414',
    ];

    $form['development']['dev_text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text Color'),
      '#default_value' => $config->get('dev_text_color') ?? '#FFFFFF',
    ];

    $form['local'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Local Environment'),
      '#description' => $this->t('Please use hex color codes.'),
    ];

    $form['local']['local_bgcolor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background color'),
      '#default_value' => $config->get('local_bgcolor') ?? '#1078C2',
    ];

    $form['local']['local_text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text Color'),
      '#default_value' => $config->get('local_text_color') ?? '#FFFFFF',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('environment_indicator.settings');
    $values = $form_state->getValues();
    $config->set('prod_bgcolor', $values['prod_bgcolor'])
      ->set('prod_text_color', $values['prod_text_color'])
      ->set('stage_bgcolor', $values['stage_bgcolor'])
      ->set('stage_text_color', $values['stage_text_color'])
      ->set('dev_bgcolor', $values['dev_bgcolor'])
      ->set('dev_text_color', $values['dev_text_color'])
      ->set('local_bgcolor', $values['local_bgcolor'])
      ->set('local_text_color', $values['local_text_color'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
