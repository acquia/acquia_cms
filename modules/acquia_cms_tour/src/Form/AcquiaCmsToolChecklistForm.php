<?php

namespace Drupal\acquia_cms_tour\Form;

/**
 * Provides checklist for Acquia CMS Tool Configurations.
 */
final class AcquiaCmsToolChecklistForm extends EnabledConfigChecklistBaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_csm_tool_checklist_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getChecklistTitle() : string {
    return $this->t('Acquia CMS Tool Configuration');
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
      'cohesion' => 'cohesion.configuration.account_settings',
      'acquia_search_solr' => NULL,
      'acquia_connector' => NULL,
      'acquia_lift' => NULL,
      'acquia_contenthub' => NULL,
    ];
  }

}
