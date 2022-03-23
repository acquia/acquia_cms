<?php

namespace Drupal\Tests\acquia_cms_place\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\geocoder\Entity\GeocoderProvider;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\acquia_cms_common\Functional\ContentTypeTestBase;

/**
 * Tests the Place content type that ships with Acquia CMS.
 *
 * @group acquia_cms_place
 * @group acquia_cms
 * @group low_risk
 * @group pr
 * @group push
 */
class PlaceTest extends ContentTypeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $nodeType = 'place';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_place',
    'menu_ui',
    'metatag_open_graph',
    'metatag_twitter_cards',
    'pathauto',
    'field_ui',
    'schema_place',
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
    /** @var \Drupal\taxonomy\VocabularyInterface $place_type */
    $place_type = Vocabulary::load('place_type');
    $this->createTerm($place_type, ['name' => 'Residential']);

    // Because this test class does not support JavaScript, it's not possible
    // for us to set address values in the UI, because we'd need to select a
    // country first, and that requires AJAX. To get around that, we set the
    // default country of field_place_address.
    FieldConfig::loadByName('node', 'place', 'field_place_address')
      ->setDefaultValue([
        'country_code' => 'US',
      ])
      ->save();

    // Use a random geocoder, since we don't want to actually call out to Google
    // Maps or any other real geocoding service in tests.
    GeocoderProvider::load('googlemaps')->setPlugin('random')->save();

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);

    $image_url = $this->getImageUrl();
    $this->drupalGet('/node/add/place');
    // Assert that the current user can access the form to create a place. Note
    // that status codes cannot be asserted in functional JavaScript tests.
    $assert_session->statusCodeEquals(200);
    // Assert that the expected fields show up.
    $assert_session->fieldExists('Title');
    $assert_session->fieldExists('First name');
    $assert_session->fieldExists('Last name');
    $assert_session->fieldExists('Street address');
    $assert_session->fieldExists('City');
    $assert_session->fieldExists('Country');
    $assert_session->fieldExists('State');
    $assert_session->fieldExists('Zip code');
    $assert_session->fieldExists('Telephone');
    $assert_session->fieldExists('Place Type');
    $page->fillField('Description', 'This is an awesome remix!');
    // The search description should have a summary.
    $assert_session->fieldExists('Summary');
    // The standard Categories and Tags fields should be present.
    $this->assertCategoriesAndTagsFieldsExist();
    // Ensure Media field group is present and has image field.
    $group = $assert_session->elementExists('css', '#edit-group-media');
    $assert_session->buttonExists('Add media', $group);
    // There should be a field to add an image, and it should be using the
    // media library.
    $assert_session->elementExists('css', '#field_place_image-media-library-wrapper');
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
    // Ensure it's possible to add a menu link, but only to the main menu, which
    // should be selected by default.
    $menu = $assert_session->selectExists('menu[menu_parent]');
    $this->assertSame('main:', $menu->getValue());
    $this->assertCount(1, $menu->findAll('css', 'option'));

    // Ensure Address field group is present and has address fields.
    $group = $assert_session->elementExists('css', '#edit-field-place-address-wrapper');
    $assert_session->fieldExists('Country', $group);
    $assert_session->fieldExists('First name', $group);
    $assert_session->fieldExists('Last name', $group);
    $assert_session->fieldExists('Company', $group);
    $assert_session->fieldExists('Street address', $group);
    $assert_session->fieldExists('City', $group);
    $assert_session->fieldExists('State', $group);
    $assert_session->fieldExists('Zip code', $group);

    // Ensure Latitude and Longitude group is present and has fields.
    $group = $assert_session->elementExists('css', '#edit-field-geofield-wrapper');
    $assert_session->fieldExists('Latitude', $group);
    $assert_session->fieldExists('Longitude', $group);

    // Assert that select fields are present with correct field type.
    $assert_session->selectExists('Place Type');

    // Assert that the fields are in the correct order.
    $this->assertFieldsOrder([
      'title',
      'body',
      'field_place_address',
      'field_geofield',
      'field_place_telephone',
      'field_place_image',
      'field_categories',
      'field_tags',
      'field_place_type',
      'moderation_state',
    ]);

    // Submit the form and ensure that we see the expected error message(s).
    $page->pressButton('Save');
    $assert_session->pageTextContains('Title field is required.');
    $assert_session->pageTextContains('Street address field is required.');
    $assert_session->pageTextContains('City field is required.');
    $assert_session->pageTextContains('State field is required.');
    $assert_session->pageTextContains('Zip code field is required.');

    // Fill in the required fields and assert that things went as expected.
    $page->fillField('Title', 'Living with video');

    // For convenience, the parent class creates a few categories during set-up.
    // @see \Drupal\Tests\acquia_cms_common\Functional\ContentModelTestBase::setUp()
    $page->selectFieldOption('Categories', 'Music');
    $page->fillField('Tags', 'techno');
    $page->fillField('First name', 'Anthony');
    $page->fillField('Last name', 'Disouza');
    $page->fillField('Street address', '12, block b');
    $page->fillField('City', 'Santa Clara');
    $page->fillField('Zip code', '95050');
    $page->fillField('Telephone', '9829838487');
    $page->selectFieldOption('Place Type', 'Residential');
    $page->selectFieldOption('State', 'CA');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Living with video has been created.');
    // Assert that the Pathauto pattern was used to create the URL alias.
    $assert_session->addressEquals('/place/residential/living-video');
    // Confirm that the Latitude and Longitude fields are filled.
    $this->drupalGet('/node/5/edit');
    [$previous_lat, $previous_lon] = $this->getCoordinates();

    // Change the ZIP code, which should cause the coordinates to change on
    // save.
    $page->fillField('Zip code', '94050');
    $page->pressButton('Save');

    $this->drupalGet('/node/5/edit');
    [$new_lat, $new_lon] = $this->getCoordinates();
    $page->pressButton('Save');
    // Assert that coordinates are updated after address change.
    $this->assertNotSame($previous_lat, $new_lat);
    $this->assertNotSame($previous_lon, $new_lon);

    // Assert that the expected schema.org data and meta tags are present.
    $this->assertSchemaData([
      '@graph' => [
        [
          '@type' => 'Place',
          'name' => 'Living with video',
          'telephone' => '9829838487',
          'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => '12, block b,',
            'addressLocality' => 'Santa Clara',
            'addressRegion' => 'CA',
            'postalCode' => '94050',
            'addressCountry' => 'United States',
          ],
          'description' => '<p>This is an awesome remix!</p>',
          'image' => [
            '@type' => 'ImageObject',
            'url' => $image_url,
          ],
        ],
      ],
    ]);
    $this->assertMetaTag('keywords', 'Music, techno');
    $this->assertMetaTag('description', 'This is an awesome remix!');
    $this->assertMetaTag('og:type', 'place');
    $this->assertMetaTag('og:url', $session->getCurrentUrl());
    $this->assertMetaTag('og:title', 'Living with video');
    $this->assertMetaTag('og:description', 'This is an awesome remix!');
    $this->assertMetaTag('og:image', $image_url);
    $this->assertMetaTag('twitter:card', 'summary_large_image');
    $this->assertMetaTag('twitter:title', 'Living with video');
    $this->assertMetaTag('twitter:description', 'This is an awesome remix!');
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

  /**
   * Returns the values of the latitude and longitude fields.
   *
   * @return mixed[]
   *   The non-empty values of the latitude and longitude fields.
   */
  private function getCoordinates() : array {
    $assert_session = $this->assertSession();
    $group = $assert_session->elementExists('css', '#edit-field-geofield-wrapper');
    $coordinates = [
      $assert_session->fieldExists('Latitude', $group)->getValue(),
      $assert_session->fieldExists('Longitude', $group)->getValue(),
    ];
    array_walk($coordinates, [$this, 'assertNotEmpty']);
    return $coordinates;
  }

}
