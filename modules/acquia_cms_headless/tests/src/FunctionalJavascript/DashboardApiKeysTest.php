<?php

namespace Drupal\Tests\acquia_cms_headless\FunctionalJavascript;

use Drupal\Tests\acquia_cms_headless\Traits\DashboardSectionTrait;
use Drupal\Tests\acquia_cms_headless\Traits\DashboardTableTrait;

/**
 * Tests headless dashboard API key.
 *
 * @group acquia_cms
 * @group acquia_cms_headless
 * @group medium_risk
 * @group push
 */
class DashboardApiKeysTest extends HeadlessTestBase {

  use DashboardTableTrait, DashboardSectionTrait;

  /**
   * {@inheritdoc}
   */
  protected string $sectionTitle = "API Keys";

  /**
   * {@inheritdoc}
   */
  protected string $sectionSelector = "#edit-consumers-api-keys";

  /**
   * {@inheritdoc}
   */
  public static function getHeaders(): array {
    return [
      [
        "headers" => ["Label", "Client ID", "Secret", "Operations"],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function testButtons(): void {
    $this->assertButton("Create new consumer");
  }

  /**
   * {@inheritdoc}
   */
  public function testSection(): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();

    // Test API Keys section exists, get API Keys section.
    $consumersFieldset = $assertSession->waitForElementVisible('css', $this->sectionSelector);

    // Test create new consumer button link has destination.
    $this->assertButtonLink($consumersFieldset, '/admin/config/services/consumer/add?destination=/admin/headless/dashboard');

    // Test table body exist and has data in same order.
    $this->assertEquals('Default Consumer', $this->getTableBodyColumn(0)->getText());
    // Test client ID exist and not empty.
    $this->assertNotEmpty($this->getTableBodyColumn(1)->getText());
    $this->assertEquals('N/A', $this->getTableBodyColumn(2)->getText());

    // Get the API Keys operations dropdown elements.
    $dropdownList = $consumersFieldset->findAll('css', 'ul li a');

    $this->assertCount(5, $dropdownList);

    // Click on Generate New Secret button.
    $this->testGenerateNewSecret($consumersFieldset);

    // Click Generate New Keys button.
    $this->testGenerateNewKeys($consumersFieldset);

    // Click on Delete button.
    $this->testDelete($consumersFieldset);

    // Click on Clone button.
    $this->testClone($consumersFieldset);
  }

  /**
   * {@inheritdoc}
   */
  private function testGenerateNewSecret(mixed $consumersFieldset): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $consumersFieldset->findButton('List additional actions')->click();
    $assertSession->elementExists('named', ['link', 'Generate New Secret'], $consumersFieldset)->click();
    $consumerModal = $assertSession->waitForElementVisible('css', '.ui-dialog');
    $this->assertNotEmpty($consumerModal);
    $this->assertEquals('Generate New Consumer Secret', $consumerModal->find('css', '.ui-dialog-title')->getText());
    $this->assertNotEmpty($consumerModal->find('css', '.headless-dashboard-modal'));
    $consumerModal->find('css', '.ui-dialog-titlebar-close')->click();
  }

  /**
   * {@inheritdoc}
   */
  private function testGenerateNewKeys(mixed $consumersFieldset): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $assertSession->elementExists('named', ['link', 'Generate New Keys'], $consumersFieldset)->click();
    $keysModal = $assertSession->waitForElementVisible('css', '.ui-dialog');
    $this->assertNotEmpty($keysModal);
    $this->assertEquals('Generate New API Keys', $keysModal->find('css', '.ui-dialog-title')->getText());
    $keysModalContent = $keysModal->find('css', '.headless-dashboard-modal');
    $this->assertNotEmpty($keysModalContent);
    $assertSession->elementExists('named', ['link', 'Oauth Settings'], $keysModalContent);
    $keysModal->find('css', '.ui-dialog-titlebar-close')->click();
  }

  /**
   * {@inheritdoc}
   */
  private function testDelete(mixed $consumersFieldset): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $consumersFieldset->findButton('List additional actions')->click();
    $assertSession->elementExists('named', ['link', 'Delete'], $consumersFieldset)->click();
    $page = $this->getSession()->getPage();
    $this->assertNotEmpty($page);
    $assertSession->pageTextContains('Access denied');
    $expectedUrl = $this->baseUrl . '/admin/config/services/consumer/1/delete?destination=/admin/headless/dashboard';
    $this->assertSame($expectedUrl, $this->getSession()->getCurrentUrl());
  }

  /**
   * {@inheritdoc}
   */
  private function testClone(mixed $consumersFieldset): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $this->drupalGet("/admin/headless/dashboard");
    $consumersFieldset->findButton('List additional actions')->click();
    $assertSession->elementExists('named', ['link', 'Clone'], $consumersFieldset)->click();
    $page = $this->getSession()->getPage();
    $this->assertNotEmpty($page);
    $assertSession->pageTextContains('Access denied');
    $expectedUrl = $this->baseUrl . '/entity_clone/consumer/1?destination=/admin/headless/dashboard';
    $this->assertSame($expectedUrl, $this->getSession()->getCurrentUrl());
  }

}
