<?php

declare(strict_types=1);

namespace Drupal\acquia_drupal_starterkit_installer\Form;

use Drupal\Component\Utility\UserAgent;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;

abstract class InstallerFormBase extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    global $install_state;

    $languages = [];
    $standard_languages = LanguageManager::getStandardLanguageList();
    foreach ($standard_languages as $langcode => $language_names) {
      $languages[$langcode] = $language_names[1];
    }
    foreach ($install_state['translations'] as $langcode => $uri) {
      $languages[$langcode] = $standard_languages[$langcode][1] ?? $langcode;
    }
    asort($languages);

    // The front-end JavaScript should use this list to create a widget that
    // dynamically changes the URL `langcode` parameter.
    $form['#attached']['drupalSettings']['languages'] = $languages;
    return $form;
  }

}
