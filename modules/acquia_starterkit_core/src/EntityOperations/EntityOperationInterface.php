<?php

namespace Drupal\acquia_starterkit_core\EntityOperations;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface to react on entity events.
 */
interface EntityOperationInterface {

  /**
   * Determines whether we need to react on entity operations.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The given entity object.
   */
  public function shouldSkipOperations(ConfigEntityInterface $entity): bool;

}
