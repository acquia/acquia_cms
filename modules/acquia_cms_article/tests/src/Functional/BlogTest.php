<?php

namespace Drupal\Tests\acquia_cms_article\Functional;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests the Article content type that ships with Acquia CMS.
 *
 * @group acquia_cms_article
 * @group acquia_cms
 * @group low_risk
 * @group pr
 * @group push
 */
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
    'cohesion_custom_styles',
    'cohesion_website_settings',
  ];

  /**
   * Disable strict config schema checks in this test.
   *
   * Cohesion has a lot of config schema errors, and until they are all fixed,
   * this test cannot pass unless we disable strict config schema checking
   * altogether. Since strict config schema isn't critically important in
   * testing this functionality, it's okay to disable it for now, but it should
   * be re-enabled (i.e., this property should be removed) as soon as possible.
   *
   * @var bool
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  public function testBlogArticles() {
    /** @var \Drupal\taxonomy\VocabularyInterface $article_type */
    $article_type = Vocabulary::load('article_type');
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
        'Body' => 'This is an example of body text',
        'field_article_type' => $term->id(),
        'field_display_author' => $person_node_id,
        'created' => $time = time(),
      ]);
    }
    $this->drupalGet('/blog');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Blog article 0');
    $assert_session->pageTextContains('Blog article 1');
    $assert_session->pageTextContains('Blog article 2');
  }

}
