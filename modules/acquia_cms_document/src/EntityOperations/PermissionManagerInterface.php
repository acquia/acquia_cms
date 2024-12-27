<?php

namespace Drupal\acquia_cms_document\EntityOperations;

/**
 * Interface to grant permission to roles.
 */
interface PermissionManagerInterface {

  /**
   * Update role permission handler.
   *
   * @param array|null $role_ids
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function grantPermissionToRoles(array $role_ids = NULL): void;

}
