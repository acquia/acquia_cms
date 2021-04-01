<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\SortArray;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Base class for testing Acquia CMS content models.
 */
abstract class ContentModelTestBase extends BrowserTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    /** @var \Drupal\taxonomy\VocabularyInterface $categories */
    $categories = Vocabulary::load('categories');
    $this->createTerm($categories, ['name' => 'Music']);
    $this->createTerm($categories, ['name' => 'Food']);
    $this->createTerm($categories, ['name' => 'Technology']);
  }

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

    $tags = $assert_session->fieldExists('Tags', $group);
    $this->assertTrue($tags->hasAttribute('data-autocomplete-path'));

    $categories = $assert_session->selectExists('Categories', $group);
    $this->assertTrue($categories->hasAttribute('multiple'));

    // Ensure that the select list has every term in the Categories vocabulary.
    $terms = $this->container->get('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => 'categories',
      ]);

    /** @var \Drupal\taxonomy\TermInterface $term */
    foreach ($terms as $term) {
      $assert_session->optionExists('Categories', $term->label(), $group);
    }
  }

  /**
   * Asserts that a meta tag with a specific name/property and value exists.
   *
   * @param string $name_or_property
   *   The meta tag's expected 'name' or 'property' attribute.
   * @param string $value
   *   The meta tag's expected value (i.e., 'content' property).
   */
  protected function assertMetaTag(string $name_or_property, string $value) {
    $content = $this->assertSession()
      ->elementExists('css', "meta[name='$name_or_property'], meta[property='$name_or_property']")
      ->getAttribute('content');

    $this->assertSame($value, $content);
  }

  /**
   * Asserts that certain schema.org data is present on the current page.
   *
   * @param array $expected_data
   *   (optional) Additional schema.org data we expect to see on the page (in a
   *   JSON-encoded script tag). This parameter can simply be a subset of all
   *   the schema.org data on the page.
   */
  protected function assertSchemaData(array $expected_data = []) {
    $expected_data += [
      '@context' => 'https://schema.org',
    ];

    $element = $this->assertSession()->elementExists('css', 'script[type="application/ld+json"]');
    $actual_data = Json::decode($element->getText());
    $this->assertIsArray($actual_data);
    // We were using assertArraySubset(), but it's been deprecated in
    // PHPUnit 9. See: https://github.com/sebastianbergmann/phpunit/issues/3494
    foreach ($expected_data as $key => $value) {
      $this->assertArrayHasKey($key, $actual_data);
      $this->assertSame($value, $actual_data[$key]);
    }
  }

  /**
   * Asserts that the components of an entity display are in a specific order.
   *
   * @param array[] $components
   *   The components in the entity display.
   * @param string[] $expected_order
   *   The components' keys, in the expected order.
   * @param string $message
   *   (optional) A message if the assertion fails.
   */
  protected function assertDisplayComponentsOrder(array $components, array $expected_order, string $message = '') {
    uasort($components, SortArray::class . '::sortByWeightElement');
    $components = array_intersect(array_keys($components), $expected_order);
    $this->assertSame($expected_order, array_values($components), $message);
  }

}
