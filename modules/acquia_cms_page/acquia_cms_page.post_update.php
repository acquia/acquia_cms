<?php

/**
 * @file
 * Post update functions for the acquia_cms_page module.
 */

use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_update_NAME().
 *
 * Re-save all page content to take effect of the
 * new translations setting for layout canvas fields.
 */
function acquia_cms_page_post_update_save_page_nodes(&$sandbox): void {
  if (!_acquia_cms_page_needs_page_update()) {
    $sandbox['total'] = 0;
    $sandbox['current'] = 0;
    $sandbox['#finished'] = 1;
  }
  $entity_type_manager = \Drupal::entityTypeManager();
  $default_lang = _site_get_default_language();
  $node_storage = $entity_type_manager->getStorage('node');
  if (!isset($sandbox['total'])) {
    $nids = $node_storage->getQuery()
      ->condition('type', 'page')
      ->condition('langcode', $default_lang)
      ->accessCheck(FALSE)
      ->execute();
    $sandbox['total'] = count($nids);
    $sandbox['current'] = 0;
  }

  $save_page_per_batch = 25;
  $page_nid = $node_storage->getQuery()
    ->condition('type', 'page')
    ->range($sandbox['current'], $sandbox['current'] + $save_page_per_batch)
    ->condition('langcode', $default_lang)
    ->accessCheck(FALSE)
    ->execute();
  $all_languages = \Drupal::service('language_manager')->getLanguages();
  unset($all_languages[$default_lang]);
  $layout_storage = $entity_type_manager->getStorage("cohesion_layout");
  // Re-save node of page type.
  foreach ($page_nid as $nid) {
    $node = $node_storage->load($nid);
    $needsUpdate = FALSE;
    foreach ($all_languages as $language) {
      if ($node->hasTranslation($language->getId())) {
        $translated_node = $node->getTranslation($language->getId());
        $layout_canvas = $layout_storage->loadRevision(_acquia_cms_page_get_layout_canvas_revision_id($node, $language->getId()));
        $data = $layout_canvas->toArray();
        if ($node->hasField("field_layout_canvas") && $node->field_layout_canvas->entity instanceof EntityInterface) {
          if (!$node->field_layout_canvas->entity->hasTranslation($language->getId())) {
            $needsUpdate = TRUE;
            $target_translation = $node->field_layout_canvas->entity->addTranslation($language->getId(),
              [
                'json_values' => $data['json_values'],
                'styles' => $data['styles'],
                'template' => $data['template'],
              ]
            );
            // Make sure we do not inherit the affected status
            // from the source values.
            if ($node->field_layout_canvas->entity->getEntityType()->isRevisionable()) {
              $target_translation->setRevisionTranslationAffected(NULL);
            }
            $target_translation->save();
            $translated_node->setRevisionTranslationAffected(TRUE);
          }
        }
      }
    }
    if ($needsUpdate) {
      $node->setRevisionTranslationAffected(NULL);
      $node->setNewRevision(TRUE);
      $node->revision_log = 'Site Studio translation configuration fixes.';
      $node->setRevisionCreationTime(\Drupal::time()->getCurrentTime());
      $node->save();
    }
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

/**
 * Determine if page update is required.
 */
function _acquia_cms_page_needs_page_update(): bool {
  $site_language = _site_get_default_language();
  $all_languages = \Drupal::service('language_manager')->getLanguages();
  unset($all_languages[$site_language]);
  return count($all_languages) > 0;
}

/**
 * Gets the default site language.
 */
function _site_get_default_language(): string {
  $language = \Drupal::service('language.default')->get();
  return $language->getId();
}

/**
 * Gets the latest layout_canvas revision_id.
 */
function _acquia_cms_page_get_layout_canvas_revision_id(EntityInterface $entity, string $lang_code): string {
  $query = \Drupal::database()->select('node__field_layout_canvas', 'f');
  return $query
    ->fields('f', ['field_layout_canvas_target_revision_id'])
    ->condition('entity_id', $entity->id())
    ->condition('revision_id', $entity->getRevisionId())
    ->condition('langcode', $lang_code)
    ->execute()
    ->fetchField();
}
