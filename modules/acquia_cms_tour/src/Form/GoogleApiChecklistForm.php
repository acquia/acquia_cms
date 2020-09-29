<?php

namespace Drupal\acquia_cms_tour\Form;

/**
 * Provides checklist for Google API Configurations.
 */
final class GoogleApiChecklistForm extends EnabledConfigChecklistBaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_api_checklist_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getChecklistTitle() : string {
    return $this->t('Google API Configuration for CMS');
  }

  /**
   * {@inheritdoc}
   */
  public function getChecklistDescription() : string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getChecklistModules() : array {
    return [
      'google_analytics' => NULL,
      'google_tag' => 'google_tag.settings_form',
      'recaptcha' => NULL,
    ];
  }

}
