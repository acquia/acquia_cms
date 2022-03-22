<?php

/**
 * @file
 * Hooks provided by the acquia_cms_tour module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter acquia_cms_tour page behaviour.
 *
 * Acquia CMS tour module provides annotation based plugin which is being used
 * for creating Acquia CMS tour page with wizard that helps users to set/update
 * different contrib modules configuration from single place, it also provides
 * module specific config with save and ignore option individually.
 *
 * Possible uses:
 * - Create plugin using AcquiaCmsTour
 *
 * @AcquiaCmsTour(
 *   id = "cohesion",
 *   label = @Translation("Site Studio"),
 *   weight = 8
 * )
 * id - This should the module machine name whose configuration need to be
 * provided on acquia cms tour page.
 *
 * weight - This is being used to sort the plugin and show on wizard and
 * tour page accordingly.
 *
 * For more details see @ \Drupal\acquia_cms_tour\Plugin\AcquiaCmsTour.
 *
 * @ingroup acquia_cms_tour
 */

/**
 * Alter acquia_cms_tour plugin definitions.
 *
 * @param array $definitions
 *   The array of acquia_cms_tour plugin definitions, keyed by plugin ID.
 *
 * @see \Drupal\acquia_cms_tour\Annotation\AcquiaCmsTour
 * @see \Drupal\acquia_cms_tour\AcquiaCmsTourManager
 */
function hook_acquia_cms_tour_info_alter(array &$definitions) {
  if (isset($definitions['google_tag'])) {
    $definitions['google_tag']['weight'] = 1;
    $definitions['google_tag']['class'] = '\Drupal\custom_module\Form\GoogleForm';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
