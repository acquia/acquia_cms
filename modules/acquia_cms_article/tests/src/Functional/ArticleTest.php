<?php

namespace Drupal\Tests\acquia_cms_article\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\acquia_cms_common\Functional\ContentTypeTestBase;

/**
 * Tests the Article content type that ships with Acquia CMS.
 *
 * @group acquia_cms_article
 * @group acquia_cms
 */
class ArticleTest extends ContentTypeTestBase {

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
  protected function doTestEditForm() : void {
    /** @var \Drupal\taxonomy\VocabularyInterface $article_type */
    $article_type = Vocabulary::load('article_type');
    $this->createTerm($article_type, ['name' => 'Blog']);

    // Create a person that we can reference as the display author.
    $person_node = $this->drupalCreateNode([
      'title' => 'Example person',
      'type' => 'person',
      'moderation_state' => 'published',
    ]);
    $person_node_id = $person_node->id();

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);

    $image_url = $this->getImageUrl();

    $this->drupalGet('/node/add/article');
    // Assert that the current user can access the form to create a article.
    // Note that status codes cannot be asserted in functional JavaScript tests.
    $assert_session->statusCodeEquals(200);

    // Assert that the expected fields show up.
    $assert_session->fieldExists('Title');
    $assert_session->fieldExists('Body');
    $assert_session->fieldExists('Categories');
    $assert_session->fieldExists('Tags');

    // The body should have a summary.
    $assert_session->fieldExists('Summary');
    // The standard Categories and Tags fields should be present.
    $this->assertCategoriesAndTagsFieldsExist();

    // The Display Author field should be present with autocomplete widget.
    $this->assertTrue($assert_session->fieldExists('Display Author')->hasAttribute('data-autocomplete-path'));

    // The Article Type field should be present.
    $group = $assert_session->elementExists('css', '#edit-group-taxonomy');
    $assert_session->selectExists('Article Type', $group);

    // There should be a field to add an image, and it should be using the
    // media library.
    // Check field_aticle_media exists.
    $assert_session->elementExists('css', '#field_article_media-media-library-wrapper');
    $group = $assert_session->elementExists('css', '#edit-group-media');
    $assert_session->buttonExists('Add media', $group);

    // Check field_aticle_image exists.
    $assert_session->elementExists('css', '#field_article_image-media-library-wrapper');
    $group = $assert_session->elementExists('css', '#edit-group-media');
    $assert_session->buttonExists('Add media', $group);

    // Set moderation state to draft.
    $assert_session->optionExists('Save as', 'Draft');

    // The "Published", "Promoted to front page", and "Sticky at top of lists"
    // checkboxes should not be anywhere on this form. We want to assert the
    // absence of these fields by their form element name, since it's possible
    // to change the labels using base field overrides.
    $assert_session->fieldNotExists('status[value]');
    $assert_session->fieldNotExists('promote[value]');
    $assert_session->fieldNotExists('sticky[value]');

    // Preview should be disabled for this content type.
    $assert_session->buttonNotExists('Preview');

    // Ensure it's possible to add a menu link, but only to the main menu, which
    // should be selected by default.
    $menu = $assert_session->selectExists('menu[menu_parent]');
    $this->assertSame('main:', $menu->getValue());
    $this->assertCount(1, $menu->findAll('css', 'option'));

    // Assert that the fields are in the correct order.
    $this->assertFieldsOrder([
      'title',
      'body',
      'field_display_author',
      'field_article_media',
      'field_article_image',
      'field_categories',
      'field_tags',
      'field_article_type',
      'moderation_state',
    ]);

    // Submit the form and ensure that we see the expected error message(s).
    $page->pressButton('Save');
    $assert_session->pageTextContains('Title field is required.');
    $assert_session->pageTextContains('Body field is required.');
    $assert_session->pageTextContains('Display Author field is required.');

    // Fill in the required fields and assert that things went as expected.
    $page->fillField('Title', 'Local news');
    $page->fillField('Body', 'This is an example of body text');
    $page->fillField('Display Author', "Example person ($person_node_id)");

    // For convenience, the parent class creates a few categories during set-up.
    // @see \Drupal\Tests\acquia_cms_common\Functional\ContentModelTestBase::setUp()
    $page->selectFieldOption('Categories', 'Technology');
    $page->selectFieldOption('Article Type', 'Blog');
    $page->fillField('Tags', 'Local');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Article Local news has been created.');

    // Assert that the Pathauto pattern was used to create the URL alias.
    $assert_session->addressEquals('/article/blog/local-news');

    // Assert that the expected schema.org data and meta tags are present.
    $this->assertSchemaData([
      '@graph' => [
        [
          '@type' => 'Article',
          'name' => 'Local news',
          'description' => '<p>This is an example of body text</p>',
          'image' => [
            '@type' => 'ImageObject',
            'url' => $image_url,
          ],
          "author" => [
            "@type" => "Person",
            "name" => '<a href="/person/example-person" hreflang="en">Example person</a>',
          ],
        ],
      ],
    ]);
    $this->assertMetaTag('keywords', 'Technology, Local');
    $this->assertMetaTag('description', 'This is an example of body text');
    $this->assertMetaTag('og:type', 'article');
    $this->assertMetaTag('og:url', $session->getCurrentUrl());
    $this->assertMetaTag('og:title', 'Local news');
    $this->assertMetaTag('og:description', 'This is an example of body text');
    $this->assertMetaTag('og:image', $image_url);
    $this->assertMetaTag('twitter:card', 'summary_large_image');
    $this->assertMetaTag('twitter:title', 'Local news');
    $this->assertMetaTag('twitter:description', 'This is an example of body text');
    $this->assertMetaTag('twitter:url', $session->getCurrentUrl());
    $this->assertMetaTag('twitter:image', $image_url);

    // Assert that the Local tag was created dynamically in the correct
    // vocabulary.
    /** @var \Drupal\taxonomy\TermInterface $tag */
    $tag = Term::load(5);
    $this->assertInstanceOf(Term::class, $tag);
    $this->assertSame('tags', $tag->bundle());
    $this->assertSame('Local', $tag->getName());
  }

}
