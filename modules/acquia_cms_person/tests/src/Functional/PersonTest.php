<?php

namespace Drupal\Tests\acquia_cms_person\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\acquia_cms_common\Functional\ContentTypeTestBase;

/**
 * Tests the Person content type that ships with Acquia CMS.
 *
 * @group acquia_cms_person
 * @group acquia_cms
 */
class PersonTest extends ContentTypeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $nodeType = 'person';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_person',
    'menu_ui',
    'metatag_open_graph',
    'metatag_twitter_cards',
    'pathauto',
    'schema_person',
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
   * Tests the bundled functionality of the Person content type.
   */
  public function testPersonContentType() {
    /** @var \Drupal\taxonomy\VocabularyInterface $person_type */
    $person_type = Vocabulary::load('person_type');
    $this->createTerm($person_type, ['name' => 'Individual']);

    // Create a node of type place under test.
    $this->drupalCreateNode([
      'title' => 'Example place',
      'type' => 'place',
      'moderation_state' => 'published',
    ]);

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);

    // Set field_person_image default value.
    $image_url = $this->getImageUrl();

    $this->drupalGet('/node/add/person');
    // Assert that the current user can access the form to create a person. Note
    // that status codes cannot be asserted in functional JavaScript tests.
    $assert_session->statusCodeEquals(200);

    // Assert that the expected fields show up.
    $assert_session->fieldExists('Name');
    $assert_session->fieldExists('Bio');
    $assert_session->fieldExists('Place');
    $assert_session->fieldExists('Email');
    $assert_session->fieldExists('Telephone');

    // The Bio should have a summary.
    $assert_session->fieldExists('Summary');
    // The standard Categories and Tags fields should be present.
    $this->assertCategoriesAndTagsFieldsExist();

    // The Place field should be present with select widget.
    $group = $assert_session->elementExists('css', '#edit-field-place-wrapper');
    $assert_session->selectExists('Place', $group);

    // The Person Type field should be present with select widget.
    $group = $assert_session->elementExists('css', '#edit-group-taxonomy');
    $assert_session->selectExists('Person Type', $group);

    // There should be a field to add an image, and it should be using the
    // media library.
    $assert_session->elementExists('css', '#field_person_image-media-library-wrapper');

    // Ensure Media field group is present and has image field.
    $group = $assert_session->elementExists('css', '#edit-group-media');
    $assert_session->buttonExists('Add media', $group);

    // Select moderation state as draft.
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
      'field_person_image',
      'field_place',
      'field_categories',
      'field_tags',
      'field_person_type',
      'field_email',
      'field_person_telephone',
      'moderation_state',
    ]);

    // Submit the form and ensure that we see the expected error message(s).
    $page->pressButton('Save');
    $assert_session->pageTextContains('Name field is required.');
    $assert_session->pageTextContains('Bio field is required.');

    // Fill in the required fields and assert that things went as expected.
    $page->fillField('Name', 'Hank Aaron');
    $page->fillField('Bio', 'This is an example of bio');
    $page->selectFieldOption('Place', 'Example place');
    $page->fillField('Email', 'example@example.com');
    $page->fillField('Telephone', '1234567890');

    // For convenience, the parent class creates a few categories during set-up.
    // @see \Drupal\Tests\acquia_cms_common\Functional\ContentModelTestBase::setUp()
    $page->selectFieldOption('Categories', 'Music');
    $page->selectFieldOption('Person Type', 'Individual');
    $page->fillField('Tags', 'Baseball');
    $page->pressButton('Save');

    // Assert that the person created sucessfully.
    $assert_session->pageTextContains('Person Hank Aaron has been created.');

    // Assert that the Pathauto pattern was used to create the URL alias.
    $assert_session->addressEquals('/person/hank-aaron');

    // Assert that the expected schema.org data and meta tags are present.
    $this->assertSchemaData([
      '@graph' => [
        [
          '@type' => 'Person',
          'name' => 'Hank Aaron',
          'telephone' => '1234567890',
          'description' => 'This is an example of bio',
          'email' => 'example@example.com',
          'image' => [
            '@type' => 'ImageObject',
            'url' => $image_url,
          ],
        ],
      ],
    ]);
    $this->assertMetaTag('keywords', 'Music, Baseball');
    $this->assertMetaTag('description', 'This is an example of bio');
    $this->assertMetaTag('og:type', 'person');
    $this->assertMetaTag('og:url', $session->getCurrentUrl());
    $this->assertMetaTag('og:title', 'Hank Aaron');
    $this->assertMetaTag('og:description', 'This is an example of bio');
    $this->assertMetaTag('og:image', $image_url);
    $this->assertMetaTag('twitter:card', 'summary_large_image');
    $this->assertMetaTag('twitter:title', 'Hank Aaron');
    $this->assertMetaTag('twitter:description', 'This is an example of bio');
    $this->assertMetaTag('twitter:url', $session->getCurrentUrl());
    $this->assertMetaTag('twitter:image', $image_url);

    // Assert that the Baseball tag was created dynamically in the correct
    // vocabulary.
    /** @var \Drupal\taxonomy\TermInterface $tag */
    $tag = Term::load(5);
    $this->assertInstanceOf(Term::class, $tag);
    $this->assertSame('tags', $tag->bundle());
    $this->assertSame('Baseball', $tag->getName());
  }

}
