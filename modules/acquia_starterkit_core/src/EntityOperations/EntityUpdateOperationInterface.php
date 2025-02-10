<?php

namespace Drupal\acquia_starterkit_core\EntityOperations;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface to react on entity update events.
 */
interface EntityUpdateOperationInterface extends EntityOperationInterface {

  /**
   * Responds on update of existing entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity that was just updated.
   *
   * @see hook_entity_update()
   */
  public function entityUpdate(ConfigEntityInterface $entity): void;

}
