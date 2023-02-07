<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\next\Entity\NextEntityTypeConfig;

/**
 * Tests headless content site preview.
 *
 * @group acquia_cms
 * @group acquia_cms_headless
 * @group medium_risk
 * @group push
 */
class SitePreviewTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'acquia_cms_headless',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // @todo Remove this check when Acquia Cloud IDEs support running functional
    // JavaScript tests.
    if (AcquiaDrupalEnvironmentDetector::isAhIdeEnv()) {
      $this->markTestSkipped('This test cannot run in an Acquia Cloud IDE.');
    }
    parent::setUp();
    $account = $this->drupalCreateUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    // Visit content page.
    $this->drupalGet("admin/content");

    // Set up a content type.
    $this->drupalCreateContentType([
      'type' => 'test',
      'name' => 'Test',
      'third_party_settings' => [
        "acquia_cms_common" => [
          "workflow_id" => "editorial",
        ],
      ],
    ]);
  }

  /**
   * Content preview test.
   */
  public function testContentPreview(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Visit add nextJs site page.
    $this->drupalGet("admin/config/services/next/sites/add");
    // Fields exists check.
    $assert->fieldExists('label');
    $assert->fieldExists('base_url');
    $assert->fieldExists('preview_url');
    $assert->fieldExists('preview_secret');
    $assert->buttonExists('Save');

    // Setup nextJS site.
    $page->fillField('Label', 'Headless Site One');
    $page->fillField('base_url', 'https://localhost.com:3000');
    $page->fillField('preview_url', 'https://localhost.com:3000/api/preview');
    $page->fillField('preview_secret', 'secret1one');
    $assert->waitForElementVisible('css', '.admin-link');
    $assert->elementExists('named', ['button', 'Save'])->click();

    // Setup another nextJs site.
    $this->drupalGet("admin/config/services/next/sites/add");
    $page->fillField('Label', 'Headless Site Two');
    $page->fillField('base_url', 'https://localhost.com:3001');
    $page->fillField('preview_url', 'https://localhost.com:3001/api/preview');
    $page->fillField('preview_secret', 'secret2two');
    $assert->waitForElementVisible('css', '.admin-link');
    $assert->elementExists('named', ['button', 'Save'])->click();

    // Configure nextJs entity types.
    $nextEntityTypeConfig = NextEntityTypeConfig::create([
      'id' => 'node.test',
      'site_resolver' => 'site_selector',
      'configuration' => [
        'sites' => [
          'headless_site_one' => 'headless_site_one',
          'headless_site_two' => 'headless_site_two',
        ],
      ],
    ]);
    $nextEntityTypeConfig->save();

    // Create test content page.
    $node = $this->drupalCreateNode([
      'type' => 'test',
      'title' => 'Headless Test Page',
    ]);
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Validate nextJs entity config.
    $this->drupalGet("admin/config/services/next/entity-types/node.test/edit");
    $this->assertTrue($assert->optionExists('id', 'node.test')->isSelected());
    $this->assertTrue($assert->optionExists('site_resolver', 'site_selector')->isSelected());
    $assert->checkboxChecked('sites[headless_site_one]');
    $assert->checkboxChecked('sites[headless_site_two]');
    $assert->buttonExists('Save')->press();

    // Visit content preview.
    $this->drupalGet('node/' . $node->id() . '/site-preview');
    $this->assertTrue($assert->optionExists('edit-site', 'headless_site_one')->isSelected());
    $assert->selectExists('edit-site')->selectOption('headless_site_two');
    $assert->buttonExists('Submit')->press();
    $this->assertTrue($assert->optionExists('edit-site', 'headless_site_two')->isSelected());
    $assert->elementExists('css', 'li.live-link a[target=_blank]');
    $assert->selectExists('edit-new-state')->selectOption('published');
    $assert->buttonExists('Apply')->press();
  }

}
