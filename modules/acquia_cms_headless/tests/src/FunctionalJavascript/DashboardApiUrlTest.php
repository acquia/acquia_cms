<?php

namespace Drupal\Tests\acquia_cms_headless\FunctionalJavascript;

use Drupal\Tests\acquia_cms_headless\Traits\DashboardSectionTrait;

/**
 * Tests headless dashboard API Url.
 *
 * @group acquia_cms
 * @group acquia_cms_headless
 * @group medium_risk
 * @group push
 */
class DashboardApiUrlTest extends HeadlessTestBase {

  use DashboardSectionTrait;

  /**
   * {@inheritdoc}
   */
  protected string $sectionTitle = "API URL";

  /**
   * {@inheritdoc}
   */
  protected string $sectionSelector = "#acquia-cms-headless-api-url";

  /**
   * {@inheritdoc}
   */
  public function testButtons(): void {
    $this->assertButton("Update Base API URL");
  }

  /**
   * {@inheritdoc}
   */
  public function testSection(): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();

    // Test API Keys section exists, get API Keys section.
    $assertSession->waitForElementVisible('css', $this->sectionSelector);
    $element = $assertSession->waitForElementVisible('css', '.headless-dashboard-api-url');
    $this->assertSame($element->getText(), 'Base API Url: ' . $this->baseUrl . "/jsonapi");
  }

}
