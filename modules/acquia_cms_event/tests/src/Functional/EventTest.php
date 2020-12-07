<?php

namespace Drupal\Tests\acquia_cms_event\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\acquia_cms_common\Functional\ContentTypeTestBase;

/**
 * Tests the Event content type that ships with Acquia CMS.
 *
 * @group acquia_cms_event
 * @group acquia_cms
 */
class EventTest extends ContentTypeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $nodeType = 'event';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_event',
    'menu_ui',
    'metatag_open_graph',
    'metatag_twitter_cards',
    'pathauto',
    'schema_event',
  ];

  private $defaultTime;

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
  protected function setUp() {
    parent::setUp();

    // Normally, functional tests run in the Syndey time zone in order to catch
    // bugs and edge cases. However, in this test, we're doing all our date
    // manipulation in UTC, and therefore we want Drupal to output its dates
    // in UTC as well.
    $this->config('system.date')->set('timezone.default', 'UTC')->save();

    $this->defaultTime = gmmktime(0, 0, 0, 7, 2, 2020);

    // Because this test class doesn't support JavaScript, it's not possible for
    // us to choose dates and times in the UI. To work around that, give the
    // date/time fields of this content type a default value.
    foreach (['field_event_start', 'field_door_time', 'field_event_end'] as $field) {
      FieldConfig::loadByName('node', $this->nodeType, $field)
        ->setDefaultValue([
          'default_date_type' => 'relative',
          'default_date' => gmdate('c', $this->defaultTime),
        ])
        ->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestEditForm() : void {
    /** @var \Drupal\taxonomy\VocabularyInterface $event_type */
    $event_type = Vocabulary::load('event_type');
    $this->createTerm($event_type, ['name' => 'meetup']);

    // Create a node of type place under test.
    $this->drupalCreateNode([
      'title' => 'A great place',
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

    $image_url = $this->getImageUrl();

    $this->drupalGet('/node/add/event');
    // Assert that the current user can access the form to create a event. Note
    // that status codes cannot be asserted in functional JavaScript tests.
    $assert_session->statusCodeEquals(200);
    // Assert that the expected fields show up.
    $assert_session->fieldExists('Title');
    $assert_session->fieldExists('Description');
    $assert_session->fieldExists('Place');
    $assert_session->fieldExists('Duration');
    $assert_session->fieldExists('Event Type');

    // The search description should have a summary.
    $assert_session->fieldExists('Summary');
    // The standard Categories and Tags fields should be present.
    $this->assertCategoriesAndTagsFieldsExist();
    // There should be a field to add an image, and it should be using the
    // media library.
    $assert_session->elementExists('css', '#field_event_image-media-library-wrapper');
    // There should be a select list to choose the moderation state, and it
    // should default to Draft. Note that which moderation states are available
    // depends on the current user's permissions.
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
    // Ensure Media field group is present and has image field.
    $group = $assert_session->elementExists('css', '#edit-group-media');
    $assert_session->buttonExists('Add media', $group);
    // Ensure it's possible to add a menu link, but only to the main menu, which
    // should be selected by default.
    $menu = $assert_session->selectExists('menu[menu_parent]');
    $this->assertSame('main:', $menu->getValue());
    $this->assertCount(1, $menu->findAll('css', 'option'));

    // Ensure Date/Time field group is present and has expected fields.
    $group = $assert_session->elementExists('css', '#edit-group-date-time');
    $assert_session->fieldExists('field_event_start[0][value][date]', $group);
    $assert_session->fieldExists('field_event_start[0][value][time]', $group);
    $assert_session->fieldExists('field_event_end[0][value][date]', $group);
    $assert_session->fieldExists('field_event_end[0][value][time]', $group);
    $assert_session->fieldExists('field_door_time[0][value][date]', $group);
    $assert_session->fieldExists('field_door_time[0][value][time]', $group);

    // Assert that select fields are present with correct field type.
    $assert_session->selectExists('Place');
    $assert_session->selectExists('Event Type');

    // Assert that the fields are in the correct order.
    $this->assertFieldsOrder([
      'title',
      'body',
      'field_event_start',
      'field_event_end',
      'field_door_time',
      'field_event_duration',
      'field_event_place',
      'field_event_image',
      'field_categories',
      'field_tags',
      'moderation_state',
    ]);

    // Submit the form and ensure that we see the expected error message(s).
    $page->pressButton('Save');
    $assert_session->pageTextContains('Title field is required.');
    $assert_session->pageTextContains('Description field is required.');

    // Fill in the required fields and assert that things went as expected.
    $page->fillField('Title', 'Science fiction meetup');

    // For convenience, the parent class creates a few categories during set-up.
    // @see \Drupal\Tests\acquia_cms_common\Functional\ContentModelTestBase::setUp()
    $page->fillField('Description', 'A thrilling meetup');
    $page->fillField('Duration', '2 hours');
    $page->selectFieldOption('Categories', 'Music');
    $page->fillField('Tags', 'techno');
    $page->selectFieldOption('Event Type', 'meetup');
    $page->selectFieldOption('Place', 'A great place');
    // Assert that date time values matches with default values.
    // Time values not asserted due to difference in system and browser time.
    $default_date = gmdate('Y-m-d', $this->defaultTime);
    $assert_session->fieldValueEquals('field_event_start[0][value][date]', $default_date);
    $assert_session->fieldValueEquals('field_event_end[0][value][date]', $default_date);
    $assert_session->fieldValueEquals('field_door_time[0][value][date]', $default_date);
    $page->pressButton('Save');
    $assert_session->pageTextContains('Event Science fiction meetup has been created.');
    // Assert that the Pathauto pattern was used to create the URL alias.
    // $date = new DrupalDateTime('now');.
    $assert_session->addressEquals('/event/meetup/2020/07/science-fiction-meetup');
    // Assert that the expected schema.org data and meta tags are present.
    $expected_date = gmdate('c', $this->defaultTime);
    $this->assertSchemaData([
      '@graph' => [
        [
          '@type' => 'Event',
          'name' => 'Science fiction meetup',
          'description' => '<p>A thrilling meetup</p>',
          'image' => [
            '@type' => 'ImageObject',
            'url' => $image_url,
          ],
          'doorTime'  => $expected_date,
          'startDate' => $expected_date,
          'endDate' => $expected_date,
          'location' => [
            '@type' => 'Place',
            'name' => '<a href="/place/great-place" hreflang="en">A great place</a>',
          ],
        ],
      ],
    ]);
    $this->assertMetaTag('keywords', 'Music, techno');
    $this->assertMetaTag('description', 'A thrilling meetup');
    $this->assertMetaTag('og:type', 'event');
    $this->assertMetaTag('og:url', $session->getCurrentUrl());
    $this->assertMetaTag('og:title', 'Science fiction meetup');
    $this->assertMetaTag('og:description', 'A thrilling meetup');
    $this->assertMetaTag('og:image', $image_url);
    $this->assertMetaTag('twitter:card', 'summary_large_image');
    $this->assertMetaTag('twitter:title', 'Science fiction meetup');
    $this->assertMetaTag('twitter:description', 'A thrilling meetup');
    $this->assertMetaTag('twitter:url', $session->getCurrentUrl());
    $this->assertMetaTag('twitter:image', $image_url);
    // Assert that the techno tag was created dynamically in the correct
    // vocabulary.
    /** @var \Drupal\taxonomy\TermInterface $tag */
    $tag = Term::load(5);
    $this->assertInstanceOf(Term::class, $tag);
    $this->assertSame('tags', $tag->bundle());
    $this->assertSame('techno', $tag->getName());
  }

}
