<?php

namespace Drupal\Tests\acquia_cms_article\Functional;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Article content type that ships with Acquia CMS.
 *
 * @group acquia_cms_article
 * @group acquia_cms
 * @group low_risk
 * @group pr
 * @group push
 */
#[Group('acquia_cms_article')]
#[Group('acquia_cms')]
#[Group('low_risk')]
#[Group('pr')]
#[Group('push')]
#[RunTestsInSeparateProcesses]
class BlogTest extends BrowserTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $nodeType = 'article';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_article',
    'menu_ui',
    'metatag_open_graph',
    'metatag_twitter_cards',
    'pathauto',
    'schema_article',
  ];

  /**
   * Disable strict config schema checks in this test.
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Force body field storage to text_with_summary for Drupal 11 compatibility.
    $config_factory = $this->container->get('config.factory');
    $body_storage = $config_factory->getEditable('field.storage.node.body');

    if ($body_storage && $body_storage->get('type') !== 'text_with_summary') {
      $body_storage->set('type', 'text_with_summary')->save();

      /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
      $entity_field_manager = $this->container->get('entity_field.manager');
      $entity_field_manager->clearCachedFieldDefinitions();

      /** @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $last_installed_repository */
      $last_installed_repository = $this->container->get('entity.last_installed_schema.repository');

      /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
      $update_manager = $this->container->get('entity.definition_update_manager');

      $storage_definitions = $entity_field_manager->getFieldStorageDefinitions('node');
      if (isset($storage_definitions['body'])) {
        $body_definition = $storage_definitions['body'];
        $update_manager->updateFieldStorageDefinition($body_definition);
        $last_installed_repository->setLastInstalledFieldStorageDefinition($body_definition);
      }
    }

    // Update form display to show summary field (display_summary: true requires show_summary: true).
    $form_display = $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'article', 'default');
    $body_component = $form_display->getComponent('body');
    if ($body_component && isset($body_component['settings'])) {
      $body_component['settings']['show_summary'] = TRUE;
      $form_display->setComponent('body', $body_component)->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testBlogArticles() {
    /** @var \Drupal\taxonomy\VocabularyInterface $article_type */
    $article_type = Vocabulary::load('article_type');
    // Note: Since this base class does not automatically provision categories,
    // make sure the categories vocabulary is loaded or created if needed by the view.
    $term = $this->createTerm($article_type, ['name' => 'Blog']);

    // Create a person that we can reference as the display author.
    $person_node = $this->drupalCreateNode([
      'title' => 'Example person',
      'type' => 'person',
      'moderation_state' => 'published',
    ]);
    $person_node_id = $person_node->id();
    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);
    $assert_session = $this->assertSession();

    for ($i = 0; $i < 3; $i++) {
      $this->drupalCreateNode([
        'type' => 'article',
        'title' => 'Blog article ' . $i,
        'moderation_state' => 'published',
        'field_categories' => NULL,
        'body' => [
          'value' => 'This is an example of body text',
          'summary' => '',
          'format' => 'basic_html',
        ],
        'field_article_type' => $term->id(),
        'field_display_author' => $person_node_id,
        'created' => time(),
      ]);
    }
    $this->drupalGet('/blog');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Blog article 0');
    $assert_session->pageTextContains('Blog article 1');
    $assert_session->pageTextContains('Blog article 2');
  }

}
