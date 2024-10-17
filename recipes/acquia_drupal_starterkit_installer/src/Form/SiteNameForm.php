<?php

declare(strict_types=1);

namespace Drupal\acquia_drupal_starterkit_installer\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to set the site name.
 */
final class SiteNameForm extends InstallerFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'acquia_drupal_starterkit_installer_site_name_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    global $install_state;

    $form['#title'] = $this->t('Give your site a name');
    $form['help']['#markup'] = $this->t('You can change this later before publishing your site.');

    $form['site_name'] = [
      '#prefix' => '<div class="cms-installer__form-group">',
      '#suffix' => '</div>',
      '#type' => 'textfield',
      '#title' => $this->t('Site name'),
      '#required' => TRUE,
      '#default_value' => $install_state['forms']['install_configure_form']['site_name'] ?? $this->t('Acquia Drupal Starter Kit'),
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#button_type' => 'primary',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    global $install_state;
    $install_state['parameters']['site_name'] = $form_state->getValue('site_name');
  }

}
