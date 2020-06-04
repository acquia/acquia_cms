<?php

namespace Drupal\Tests\acquia_cms_common\Traits;

/**
 * Provides helper methods for testing Acquia CMS content models.
 */
trait ContentModelTestTrait {

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
