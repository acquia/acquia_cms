<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

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

    // Configure nextJs entity types and Validate nextJs entity config.
    $this->drupalGet("admin/config/services/next/entity-types/add");
    $assert->selectExists('id')->selectOption('node.test');
    $assert->waitForElementVisible('css', '.settings-container');
    $this->assertTrue($assert->optionExists('id', 'node.test')->isSelected());
    $assert->selectExists('site_resolver')->selectOption('site_selector');
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForText('Next.js sites');
    $this->assertTrue($assert->optionExists('site_resolver', 'site_selector')->isSelected());
    $page->checkField('sites[headless_site_one]');
    $assert->checkboxChecked('sites[headless_site_one]');
    $page->checkField('sites[headless_site_two]');
    $assert->checkboxChecked('sites[headless_site_two]');
    $assert->buttonExists('Save')->press();

    // Create test node.
    $node = $this->drupalCreateNode([
      'type' => 'test',
      'title' => 'Headless Test Page',
    ]);
    $this->drupalGet('node/' . $node->id() . '/edit');

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
