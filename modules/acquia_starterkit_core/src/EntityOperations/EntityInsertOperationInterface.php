<?php

namespace Drupal\acquia_starterkit_core\EntityOperations;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface to react on entity insert events.
 */
interface EntityInsertOperationInterface extends EntityOperationInterface {

  /**
   * Responds to the creation of a new entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity that was just saved.
   *
   * @see hook_entity_insert()
   */
  public function entityInsert(ConfigEntityInterface $entity): void;

}
