<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for acquia cms dashboard.
 */
interface AcquiaDashboardInterface {

  /**
   * Provides module status.
   */
  public function getModuleStatus();

  /**
   * Provides progress bar status of a module.
   */
  public function getProgressState();

  /**
   * {@inheritdoc}
   */
  public function ignoreConfig(array &$form, FormStateInterface $form_state);

}
