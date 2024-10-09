<?php

namespace Drupal\acquia_drupal_starterkit_installer\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to choose the site template and optional add-on recipes.
 *
 * @todo Present this as a mini project browser once
 *   https://www.drupal.org/i/3450629 is fixed.
 */
final class RecipesAddOnForm extends InstallerFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'acquia_drupal_starterkit_recipes_addon_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#title'] = $this->t('Choose Add-ons to Extend Starter Kit');

    $form['help'] = [
      '#prefix' => '<p class="cms-installer__subhead">',
      '#markup' => $this->t('You can change your mind later.'),
      '#suffix' => '</p>',
    ];
    $options = [
      'acquia_drupal_starterkit_content_model' => $this->t('Content Model'),
      'acquia_drupal_starterkit_dam' => $this->t('DAM'),
      'acquia_drupal_starterkit_gdpr' => $this->t('GDPR'),
      'acquia_drupal_starterkit_media' => $this->t('Media'),
      'acquia_drupal_starterkit_search' => $this->t('Search'),
      'acquia_drupal_starterkit_audio' => $this->t('Audio'),
      'acquia_drupal_starterkit_document' => $this->t('Document'),
      'acquia_drupal_starterkit_image' => $this->t('image'),
      'acquia_drupal_starterkit_video' => $this->t('Video'),
    ];

    $form['add_ons'] = [
      '#prefix' => '<div class="cms-installer__form-group">',
      '#suffix' => '</div>',
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => [],
    ];
    $form['actions'] = [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#button_type' => 'primary',
      ],
      'skip' => [
        '#type' => 'submit',
        '#value' => $this->t('Skip this step'),
      ],
      '#type' => 'actions',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    global $install_state;
    $install_state['parameters']['recipes'] = $this->getRequest()->get('recipes') ?? [];
    $install_state['parameters']['recipes_starterkit_addons'] = $install_state['parameters']['recipes'];
    $pressed_button = $form_state->getTriggeringElement();
    // Only choose add-ons if the Next button was pressed.
    if ($pressed_button && end($pressed_button['#array_parents']) === 'submit') {
      $add_ons = $form_state->getValue('add_ons', []);
      $add_ons = array_filter($add_ons);
      array_push($install_state['parameters']['recipes'], ...array_values($add_ons));
      array_push($install_state['parameters']['recipes_starterkit_addons'], ...array_values($add_ons));
    }

  }

}
