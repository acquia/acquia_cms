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
class DashboardApiUsersTest extends DashboardTestBase {

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
  public function getHeaders(): array {
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

    $assertSession = $this->assertSession();

    // Test API Users section exists, get API Users section.
    $usersFieldset = $assertSession->elementExists('css', $this->sectionSelector);

    // Test add API user button link has destination.
    $this->assertButtonLink($usersFieldset, '/admin/people/create?destination=/admin/headless/dashboard');

    // Test table body exist and has data in same order.
    $this->assertEquals('Headless', $this->getTableBodyColumn(0)->getText());
    $this->assertEquals('Headless Role', $this->getTableBodyColumn(1)->getText());

    // Get the API Users operations dropdown elements.
    $dropdownList = $usersFieldset->findAll('css', 'tbody tr:nth-child(1) ul li a');
    $this->assertCount(3, $dropdownList);

    // Click on Edit button.
    $expectedUrl = $this->baseUrl . '/user/2/edit?destination=/admin/headless/dashboard';
    $usersFieldset->findButton('List additional actions')->click();
    $this->testOperation($usersFieldset, 'Edit', $expectedUrl, 'Whoops!');

    // Click on Clone button.
    $this->drupalGet("/admin/headless/dashboard");
    $usersFieldset->findButton('List additional actions')->click();
    $expectedUrl = $this->baseUrl . '/entity_clone/user/2?destination=/admin/headless/dashboard';
    $this->testOperation($usersFieldset, 'Clone', $expectedUrl, 'Whoops!');
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
