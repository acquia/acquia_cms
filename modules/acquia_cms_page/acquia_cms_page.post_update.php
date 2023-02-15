<?php

/**
 * @file
 * Post update functions for the acquia_cms_page module.
 */

use Drupal\Core\Database\Connection;

/**
 * Implements hook_update_NAME().
 *
 * Re-save all page content to take effect of the
 * new translations setting for layout canvas fields.
 */
function acquia_cms_page_post_update_save_page_nodes(&$sandbox): void {
  $entity_type_manager = \Drupal::entityTypeManager();
  $database = \Drupal::database();
  if (!isset($sandbox['total'])) {
    $page_nid = $entity_type_manager
      ->getStorage('node')
      ->getQuery()
      ->condition('type', 'page')
      ->accessCheck(FALSE)
      ->execute();
    $sandbox['total'] = count($page_nid);
    $sandbox['current'] = 0;
  }

  $save_page_per_batch = 10;
  $page_nid = $entity_type_manager
    ->getStorage('node')
    ->getQuery()
    ->condition('type', 'page')
    ->range($sandbox['current'], $sandbox['current'] + $save_page_per_batch)
    ->accessCheck(FALSE)
    ->execute();

  // Loop though all node of page type.
  foreach ($page_nid as $nid) {
    $layout_canvas = $entity_type_manager
      ->getStorage('cohesion_layout')
      ->getQuery()
      ->condition('parent_id', $nid)
      ->accessCheck(FALSE)
      ->execute();
    $layout_canvas_field_id = reset($layout_canvas);
    $latest_revision_id = get_latest_revision_id($database, $layout_canvas_field_id);
    update_layout_canvas_revision_with_correct_revision($database, $layout_canvas_field_id, $latest_revision_id);

    $node = $entity_type_manager->getStorage('node')->load($nid);
    $node_array = $node->toArray();
    $field_layout_canvas_target_revision_id = $node_array['field_layout_canvas'][0]['target_revision_id'];

    // Update node with the latest layout canvas field revision id.
    if ($field_layout_canvas_target_revision_id != $latest_revision_id) {
      $node->set('field_layout_canvas', [
        [
          'target_id' => $layout_canvas_field_id,
          'target_revision_id' => $latest_revision_id,
        ],
      ])->save();
    }
    $sandbox['current']++;
  }

  \Drupal::messenger()
    ->addMessage($sandbox['current'] . ' page processed.');

  if ($sandbox['total'] == 0) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  }
}

/**
 * Update layout canvas revision with correct revision.
 *
 * @param \Drupal\Core\Database\Connection $database
 *   The connection object.
 * @param string $layout_canvas_field_id
 *   The layout canvas field id.
 * @param string $latest_revision_id
 *   The latest revision id.
 */
function update_layout_canvas_revision_with_correct_revision(Connection $database, string $layout_canvas_field_id, string $latest_revision_id) {
  $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
  $flc_latest_revisions = get_field_layout_canvas_latest_revision_id($database, $layout_canvas_field_id);
  foreach ($flc_latest_revisions as $revision) {
    $is_default_langcode = 0;
    if ($revision->langcode === $default_langcode) {
      $is_default_langcode = 1;
    }
    $database->update('cohesion_layout_field_revision')
      ->fields([
        'revision' => $latest_revision_id,
        'default_langcode' => $is_default_langcode,
      ])
      ->condition('revision', $revision->latest_revision_id)
      ->condition('id', $layout_canvas_field_id)
      ->condition('langcode', $revision->langcode)
      ->execute();
  }
}

/**
 * Get the latest revision id of particular layout canvas field.
 *
 * @param \Drupal\Core\Database\Connection $database
 *   The connection object.
 * @param string $layout_canvas_field_id
 *   The layout canvas field id.
 *
 * @return string
 *   Latest revision id.
 */
function get_latest_revision_id(Connection $database, string $layout_canvas_field_id): string {
  $query = $database->select('cohesion_layout_field_revision', 'rf');
  $query->fields('rf', ['id']);
  $query->condition('id', $layout_canvas_field_id);
  $query->addExpression('max(revision)', 'latest_revision_id');
  $query->groupBy('id');
  return $query->execute()->fetchObject()->latest_revision_id;
}

/**
 * Get the latest revision id of particular layout canvas field language wise.
 *
 * @param \Drupal\Core\Database\Connection $database
 *   The connection object.
 * @param string $layout_canvas_field_id
 *   The layout canvas field id.
 *
 * @return array
 *   Language specific revisions array.
 */
function get_field_layout_canvas_latest_revision_id(Connection $database, string $layout_canvas_field_id): array {
  $query = $database->select('cohesion_layout_field_revision', 'rf');
  $query->fields('rf', ['id', 'langcode']);
  $query->condition('id', $layout_canvas_field_id);
  $query->addExpression('max(revision)', 'latest_revision_id');
  $query->groupBy('langcode');
  return $query->execute()->fetchAll();
}
