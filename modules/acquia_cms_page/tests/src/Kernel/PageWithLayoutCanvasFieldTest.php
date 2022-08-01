<?php

namespace Drupal\Tests\acquia_cms_page\Kernel;

use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests the Page content type that ships with Acquia CMS.
 *
 * @group acquia_cms_page
 * @group low_risk
 * @group pr
 * @group push
 */
class PageWithLayoutCanvasFieldTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'media',
    'acquia_cms_page',
    'cohesion_elements',
    'acquia_cms_site_studio',
    'entity_reference_revisions',
  ];

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * The field_config object.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('field_config');
    $this->fieldDefinition = $this->container->get('entity_type.manager')->getStorage('field_config');
  }

  /**
   * Tests facet facade to verify facet entity.
   */
  public function testPageContentTypeWithFields() {
    $expected_fields = [
      'title',
      'body',
      'field_layout_canvas',
      'field_categories',
      'field_page_image',
      'field_tags',
      'moderation_state',
    ];
    $field_definitions = $this->fieldDefinition->loadByProperties([
      'entity_type' => 'node',
      'bundle' => 'page',
    ]);

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    foreach ($field_definitions as $field_definition) {
      $field_name = $field_definition->getFieldStorageDefinition()->getName();
      $this->assertTrue(in_array($field_name, $expected_fields), "$field_name does not exists!");
    }
  }

}
