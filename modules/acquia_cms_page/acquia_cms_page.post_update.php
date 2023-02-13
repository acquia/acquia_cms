<?php

/**
 * @file
 * Post update functions for the acquia_cms_page module.
 */

/**
 * Implements hook_update_NAME().
 *
 * Re-save all page content to take effect of the
 * new translations setting for layout canvas fields.
 */
function acquia_cms_page_post_update_save_page_nodes(&$sandbox): void {
  $entity_type_manager = \Drupal::entityTypeManager()->getStorage('node');
  if (!isset($sandbox['total'])) {
    $page_nid = $entity_type_manager->getQuery()
      ->condition('type', 'page')
      ->accessCheck(FALSE)
      ->execute();
    $sandbox['total'] = count($page_nid);
    $sandbox['current'] = 0;
  }

  $save_page_per_batch = 25;
  $page_nid = $entity_type_manager->getQuery()
    ->condition('type', 'page')
    ->range($sandbox['current'], $sandbox['current'] + $save_page_per_batch)
    ->accessCheck(FALSE)
    ->execute();

  // Re-save node of page type.
  foreach ($page_nid as $nid) {
    $node = $entity_type_manager->load($nid);
    $node->save();
    $sandbox['current']++;
  }
  \Drupal::messenger()->addMessage($sandbox['current'] . ' page processed.');

  if ($sandbox['total'] == 0) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  }
}
