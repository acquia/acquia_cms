<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

/**
 * Tests headless dashboard API Document.
 *
 * @group acquia_cms
 * @group acquia_cms_headless
 * @group medium_risk
 * @group push
 */
class DashboardApiDocumentTest extends DashboardWebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected string $sectionTitle = "API Documentation";

  /**
   * {@inheritdoc}
   */
  protected string $sectionSelector = "#acquia-cms-headless-api-docs";

  /**
   * {@inheritdoc}
   */
  public function testButtons(): void {
    $this->assertButton("Explore with Redoc");
    $this->assertButton("Explore with Swagger UI");
  }

  /**
   * {@inheritdoc}
   */
  public function testSection(): void {
    $assertSession = $this->assertSession();

    // Test API Documentation section exists, get API Documentation section.
    $element = $assertSession->elementExists('css', $this->sectionSelector);

    // Test OpenAPI Resources label exist.
    $openApiResource = $element->find("css", '.headless-dashboard-openapi-resources > p > a');
    $this->assertEquals('OpenAPI Resources', $openApiResource->getText());

    // Test OpenAPI Resources has a link.
    $actualUrl = $openApiResource->getAttribute('href');
    $this->assertSame('/admin/config/services/openapi', $actualUrl);

    // Test OpenAPI Resources is a link.
    $buttonAction = $openApiResource->getAttribute('target');
    $this->assertEquals($buttonAction, "_blank");
  }

}
