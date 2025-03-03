<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Drupal\Tests\acquia_cms_headless\Traits\DashboardSectionTrait;
use Drupal\Tests\acquia_cms_headless\Traits\DashboardTableTrait;

/**
 * Tests headless dashboard API Users.
 *
 * @group acquia_cms
 * @group acquia_cms_headless
 * @group medium_risk
 * @group push
 */
class DashboardApiUsersTest extends HeadlessTestBase {

  use DashboardTableTrait, DashboardSectionTrait;

  /**
   * {@inheritdoc}
   */
  protected string $sectionTitle = "API Users";

  /**
   * {@inheritdoc}
   */
  protected string $sectionSelector = "#acquia-cms-headless-api-users";

  /**
   * {@inheritdoc}
   */
  public static function getHeaders(): array {
    return [
      [
        "headers" => ["User Name", "Roles", "Status", "Operations"],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function testButtons(): void {
    $this->assertButton("Add API User");
  }

  /**
   * {@inheritdoc}
   */
  public function testSection(): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();

    // Test API Users section exists, get an API Users section.
    $usersFieldset = $assertSession->waitForElementVisible('css', $this->sectionSelector);

    // Test add API user button link has destination.
    $this->assertButtonLink($usersFieldset, '/admin/people/create?destination=/admin/headless/dashboard');

    // Test table body exists and has data in the same order.
    $this->assertEquals('Headless', $this->getTableBodyColumn(0, 1)->getText());
    $this->assertEquals('Headless Administrator', $this->getTableBodyColumn(1)->getText());

    // Get the API Users operations dropdown elements.
    $dropdownList = $usersFieldset->findAll('css', 'tbody tr:nth-child(1) ul li a');
    $this->assertCount(3, $dropdownList);

    // Click on the Edit button.
    $expectedUrl = $this->baseUrl . '/user/2/edit?destination=/admin/headless/dashboard';
    $usersFieldset->findButton('List additional actions')->click();
    $this->testOperation($usersFieldset, 'Edit', $expectedUrl, 'Access denied');

    // Click on Clone button.
    $this->drupalGet("/admin/headless/dashboard");
    $usersFieldset->findButton('List additional actions')->click();
    $expectedUrl = $this->baseUrl . '/entity_clone/user/2?destination=/admin/headless/dashboard';
    $this->testOperation($usersFieldset, 'Clone', $expectedUrl, 'Access denied');
  }

  /**
   * {@inheritdoc}
   */
  private function testOperation(mixed $usersFieldset, string $operation, string $expectedUrl, string $pageText): void {
    $this->assertSession()->elementExists('named', ['link', $operation], $usersFieldset)->click();
    $page = $this->getSession()->getPage();
    $this->assertNotEmpty($page);
    $this->assertSession()->pageTextContains($pageText);
    $this->assertSame($expectedUrl, $this->getSession()->getCurrentUrl());
  }

}
