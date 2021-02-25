<?php

namespace Drupal\acquia_cms_tour\Form;

/**
 * Interface for acquia cms dashboard.
 */
interface AcquiaDashboardInterface {

  /**
   * Provides module status.
   */
  public function isModuleEnabled();

  /**
   * Provides progress bar status of a module.
   */
  public function getProgressState();

  /**
   * Provides the name of the state variable for the form.
   */
  public function getState();

  /**
   * Provides the name of the state variable for the form.
   */
  public function setState();

}
