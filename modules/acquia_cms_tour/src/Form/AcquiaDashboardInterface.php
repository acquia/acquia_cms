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
  public function getConfigurationState();

  /**
   * Provides the name of the state variable for the form.
   */
  public function getStateName();

  /**
   * Set the state of the module's minimum required configurations.
   */
  public function setConfigurationState();

}
