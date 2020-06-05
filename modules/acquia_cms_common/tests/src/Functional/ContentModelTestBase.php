<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for testing Acquia CMS content models.
 */
abstract class ContentModelTestBase extends BrowserTestBase {

  /**
   * Asserts that configurable fields are translatable.
   *
   * This will assert that all configurable fields for a specific entity type
   * and bundle, and their storage definitions, are configured to be
   * translatable.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle machine name.
   */
  protected function assertConfigurableFieldsAreTranslatable(string $entity_type_id, string $bundle) {
    $field_definitions = $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->loadByProperties([
        'entity_type' => $entity_type_id,
        'bundle' => $bundle,
      ]);

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    foreach ($field_definitions as $id => $field_definition) {
      $this->assertTrue($field_definition->isTranslatable(), "$id is not translatable, but it should be.");
      $this->assertTrue($field_definition->getFieldStorageDefinition()->isTranslatable(), "$id storage is not translatable, but it should be.");
    }
  }

  /**
   * Asserts that the Categories and Tags fields are visible.
   *
   * We expect that:
   * - Categories will be a select list, and every term in that vocabulary will
   *   be in it.
   * - Tags should be an auto-completing text field.
   * - Both fields should be in a Taxonomy group.
   */
  protected function assertCategoriesAndTagsFieldsExist() {
    $assert_session = $this->assertSession();

    $group = $assert_session->elementExists('css', '#edit-group-taxonomy');
    $tags_field = $assert_session->fieldExists('Tags', $group);
    $this->assertTrue($tags_field->hasAttribute('data-autocomplete-path'));
    $assert_session->selectExists('Categories', $group);

    $categories = $this->container->get('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => 'categories',
      ]);

    /** @var \Drupal\taxonomy\TermInterface $category */
    foreach ($categories as $category) {
      $assert_session->optionExists('Categories', $category->label(), $group);
    }
  }

}
