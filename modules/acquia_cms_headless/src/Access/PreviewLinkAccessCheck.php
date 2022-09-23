<?php

namespace Drupal\acquia_cms_headless\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * Determines access to for block add pages.
 */
class PreviewLinkAccessCheck implements AccessInterface {

  /**
   * Checks access to the block add page for the block type.
   */
  public function access(AccountInterface $account, Node $node) {
    // These permissions will be checked for the access to the preview link.
    $nodeType = $node->bundle();
    $permissions = [
      "create $nodeType content",
      "edit own $nodeType content",
      "edit any $nodeType content",
    ];
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

}
