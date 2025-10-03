<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Drupal\Tests\acquia_cms_headless\Traits\DashboardSectionTrait;

/**
 * Tests headless dashboard API Document.
 *
 * @group acquia_cms
 * @group acquia_cms_headless
 * @group medium_risk
 * @group push
 */
class DashboardApiDocumentTest extends HeadlessTestBase {

  use DashboardSectionTrait;

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
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();

    // Test API Documentation section exists, get API Documentation section.
    $element = $assertSession->waitForElementVisible('css', $this->sectionSelector);

    // Test OpenAPI Resources label exist.
    $openApiResource = $element->find("css", '.headless-dashboard-openapi-resources > p > a');
    $this->assertEquals('OpenAPI Resources', $openApiResource->getText());

    // Test OpenAPI Resources has a link.
    $actualUrl = $openApiResource->getAttribute('href');
    $this->assertSame(base_path(). 'admin/config/services/openapi', $actualUrl);

    // Test OpenAPI Resources is a link.
    $buttonAction = $openApiResource->getAttribute('target');
    $this->assertEquals($buttonAction, "_blank");

    // Test Explore with Swagger UI with headless role.
    $this->drupalGet("/admin/config/services/openapi/swagger/jsonapi");
    $assertSession->pageTextContains('Access denied!');

    // Test Explore with Swagger UI with admin role.
    /*$this->visitHeadlessDashboardAdmin();
    $this->drupalGet("/admin/config/services/openapi/swagger/jsonapi");
    $assertSession->waitForElementVisible('css', '#swagger-ui');
    $swaggerUi = $this->getSession()->getPage()->find('css', '#swagger-ui');
    $this->assertNotEmpty($swaggerUi);*/
  }

}
