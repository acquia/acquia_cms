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
   * Check if the minimum require configuration are already in place or not.
   *
   * Multiple modules can have multiple ways to set configurations. For e.g.
   * Site studio can be configured from site install page, from site studio
   * setting page or from dashboard.
   *
   * This method will return current state of the configurations.
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
