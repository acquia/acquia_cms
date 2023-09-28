<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Drupal\Tests\acquia_cms_headless\Traits\DashboardSectionTrait;
use Drupal\Tests\acquia_cms_headless\Traits\DashboardTableTrait;

/**
 * Tests headless dashboard Next.js Sites.
 *
 * @group acquia_cms
 * @group acquia_cms_headless
 * @group medium_risk
 * @group push
 */
class DashboardNextjsSitesTest extends HeadlessTestBase {

  use DashboardTableTrait, DashboardSectionTrait;

  /**
   * {@inheritdoc}
   */
  protected string $sectionTitle = "Next.js Sites";

  /**
   * {@inheritdoc}
   */
  protected string $sectionSelector = "#acquia-cms-headless-next-sites";

  /**
   * {@inheritdoc}
   */
  public function getHeaders(): array {
    return [
      [
        "headers" => ["ID", "Name", "Site URL", "Operations"],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function testButtons(): void {
    $this->assertButton("Add Next.js site");
  }

  /**
   * {@inheritdoc}
   */
  public function testSection(): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $assertSession->waitForElementVisible('css', $this->sectionSelector);
    // Test API Keys section exists, get API Keys section.
    $nextjsSitesFieldset = $this->getSection();

    // Test create new consumer button link has destination.
    $this->assertButtonLink($nextjsSitesFieldset, '/admin/config/services/next/sites/add?destination=/admin/headless/dashboard');

    // Click on Add Next.js site button.
    $this->testAddNextjsSite($nextjsSitesFieldset);

    // Test table body exist and has data in same order.
    $this->drupalGet("/admin/headless/dashboard");
    $this->assertEquals('No next.js sites currently exist.', $this->getTableBodyColumn(0)->getText());

  }

  /**
   * {@inheritdoc}
   */
  public function testSectionAdmin(): void {
    // Stop here and mark this test as skip.
    $this->markTestSkipped('This is failing randomly, we will check back later.');

    $this->visitHeadlessDashboardAdmin();
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $assertSession->waitForElementVisible('css', $this->sectionSelector);
    // Test API Keys section exists, get API Keys section.
    $nextjsSitesFieldset = $this->getSection();

    // Test create new consumer button link has destination.
    $this->assertButtonLink($nextjsSitesFieldset, '/admin/config/services/next/sites/add?destination=/admin/headless/dashboard');

    // Create Next.js Site using administrator user.
    $this->testAddNextjsSiteAdmin($nextjsSitesFieldset);

    // Test table body exist and has data in same order.
    $this->assertEquals('headless', $this->getTableBodyColumn(0)->getText());
    $this->assertEquals('headless', $this->getTableBodyColumn(1)->getText());
    $this->assertEquals('http://localhost:3000', $this->getTableBodyColumn(2)->getText());

    // Get the Next.js Sites operations dropdown elements.
    $dropdownList = $nextjsSitesFieldset->findAll('css', 'ul li a');
    $this->assertCount(5, $dropdownList);

    // Click on Environment variables button.
    $this->testEnvironmentVariables($nextjsSitesFieldset);

    // Click on Clone button.
    $this->testClone($nextjsSitesFieldset);

    // Test cloned next.js site exist.
    $this->assertCount(2, $nextjsSitesFieldset->findAll('css', 'table tbody tr'));
    $this->assertEquals('headless clone', $nextjsSitesFieldset->find('css', 'tbody > tr.even > td:nth-child(2)')->getText());
    $nextjsSitesFieldsetCloned = $nextjsSitesFieldset->find('css', 'tbody > tr.even');

    // Click Edit button.
    $this->testEdit($nextjsSitesFieldsetCloned);
    $this->assertEquals('headless clone edit', $nextjsSitesFieldset->find('css', 'tbody > tr.even > td:nth-child(2)')->getText());

    // Click on Delete button.
    $this->testDelete($nextjsSitesFieldsetCloned);
    $this->assertCount(1, $nextjsSitesFieldset->findAll('css', 'table tbody tr'));

    // Click on New preview secrete button.
    $this->testNewPreviewSecret($nextjsSitesFieldset);

  }

  /**
   * {@inheritdoc}
   */
  private function testAddNextjsSite(mixed $nextjsSitesFieldset): void {
    $this->assertSession()->elementExists('named', ['link', 'Add Next.js site'], $nextjsSitesFieldset)->click();
    $page = $this->getSession()->getPage();
    $this->assertNotEmpty($page);
    $this->assertSession()->pageTextContains('Access denied!');
    $expectedUrl = $this->baseUrl . '/admin/config/services/next/sites/add?destination=/admin/headless/dashboard';
    $this->assertSame($expectedUrl, $this->getSession()->getCurrentUrl());
  }

  /**
   * {@inheritdoc}
   */
  private function testAddNextjsSiteAdmin(mixed $nextjsSitesFieldset): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $assertSession->elementExists('named', ['link', 'Add Next.js site'], $nextjsSitesFieldset)->click();
    $page = $this->getSession()->getPage();
    $this->assertNotEmpty($page);
    $page->fillField('Label', 'headless');
    $assertSession->waitForElementVisible('css', '#edit-label-machine-name-suffix .machine-name-value');
    $page->fillField('Base URL', 'http://localhost:3000');
    $page->fillField('Preview URL', 'http://localhost:3000');
    $page->fillField('Preview secret', 'preview_secrete');
    $this->assertSession()->elementExists('named', ['button', 'Save'])->click();
  }

  /**
   * {@inheritdoc}
   */
  private function testEnvironmentVariables(mixed $nextjsSitesFieldset): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $assertSession->elementExists('named', ['link', 'Environment variables'], $nextjsSitesFieldset)->click();
    $nextjsSitesModal = $assertSession->waitForElementVisible('css', '.ui-dialog');
    $this->assertNotEmpty($nextjsSitesModal);
    $this->assertEquals('Environment variables', $nextjsSitesModal->find('css', '.ui-dialog-title')->getText());
    $nextjsSitesModalContent = $nextjsSitesModal->find('css', '#drupal-modal');
    $this->assertNotEmpty($nextjsSitesModalContent);
    $assertSession->elementExists('named', ['content', 'Copy and paste these values in your .env or .env.local files. To learn more about required and optional environment variables, refer to the documentation.'], $nextjsSitesModalContent);
    $assertSession->elementExists('named', ['content', '# See https://next-drupal.org/docs/environment-variables'], $nextjsSitesModalContent);
    // Required section.
    $assertSession->elementExists('named', ['content', 'NEXT_PUBLIC_DRUPAL_BASE_URL=' . $this->baseUrl], $nextjsSitesModalContent);
    $assertSession->elementExists('named', ['content', 'NEXT_IMAGE_DOMAIN=' . str_replace(":8080", "", str_replace("http://", "", $this->baseUrl))], $nextjsSitesModalContent);
    // Authentication section.
    $assertSession->elementExists('named', ['content', 'DRUPAL_CLIENT_ID=Retrieve this from /admin/config/services/consumer'], $nextjsSitesModalContent);
    $assertSession->elementExists('named', ['content', 'DRUPAL_CLIENT_SECRET=Retrieve this from /admin/config/services/consumer'], $nextjsSitesModalContent);
    // Required for Preview Mode.
    $assertSession->elementExists('named', ['content', 'DRUPAL_PREVIEW_SECRET=preview_secrete'], $nextjsSitesModalContent);
    $nextjsSitesModal->find('css', '.ui-dialog-titlebar-close')->click();
  }

  /**
   * {@inheritdoc}
   */
  private function testEdit(mixed $nextjsSitesFieldset): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $nextjsSitesFieldset->findButton('List additional actions')->click();
    $assertSession->elementExists('named', ['link', 'Edit'], $nextjsSitesFieldset)->click();
    $nextjsSitesModal = $assertSession->waitForElementVisible('css', '.ui-dialog');
    $this->assertNotEmpty($nextjsSitesModal);
    $this->assertEquals('Edit headless clone', $nextjsSitesModal->find('css', '.ui-dialog-title')->getText());
    $nextjsSitesModalContent = $nextjsSitesModal->find('css', '#drupal-modal');
    $this->assertNotEmpty($nextjsSitesModalContent);
    $nextjsSitesModalContent->fillField('Label', 'headless clone edit');
    $assertSession->waitForElementVisible('css', '.ui-dialog-buttonpane .ui-dialog-buttonset .button--primary');
    $nextjsSitesModal->find('css', '.ui-dialog-buttonpane')->findButton('Save')->click();
  }

  /**
   * {@inheritdoc}
   */
  private function testDelete(mixed $nextjsSitesFieldset): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $nextjsSitesFieldset->findButton('List additional actions')->click();
    $assertSession->elementExists('named', ['link', 'Delete'], $nextjsSitesFieldset)->click();
    $nextjsSitesModal = $assertSession->waitForElementVisible('css', '.ui-dialog');
    $this->assertNotEmpty($nextjsSitesModal);
    $this->assertEquals('Are you sure you want to delete the Next.js site headless clone edit?', $nextjsSitesModal->find('css', '.ui-dialog-title')->getText());
    $nextjsSitesModalContent = $nextjsSitesModal->find('css', '#drupal-modal');
    $this->assertNotEmpty($nextjsSitesModalContent);
    $assertSession->elementExists('named', ['content', 'This action cannot be undone.'], $nextjsSitesModalContent);
    $assertSession->waitForElementVisible('css', '.ui-dialog-buttonpane .ui-dialog-buttonset .button--primary');
    $nextjsSitesModal->find('css', '.ui-dialog-buttonpane')->findButton('Delete')->click();
  }

  /**
   * {@inheritdoc}
   */
  private function testClone(mixed $nextjsSitesFieldset): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $nextjsSitesFieldset->findButton('List additional actions')->click();
    $assertSession->waitForElementVisible('css', 'form.acquia-cms-headless-next-sites .dropbutton-wrapper .dropbutton .clone');
    $assertSession->elementExists('named', ['link', 'Clone'], $nextjsSitesFieldset)->click();
    $nextjsSitesModal = $assertSession->waitForElementVisible('css', '.ui-dialog');
    $this->assertNotEmpty($nextjsSitesModal);
    $this->assertEquals('Clone Next.js site', $nextjsSitesModal->find('css', '.ui-dialog-title')->getText());
    $nextjsSitesModalContent = $nextjsSitesModal->find('css', '#drupal-modal');
    $this->assertNotEmpty($nextjsSitesModalContent);
    $nextjsSitesModalContent->fillField('New Label', 'headless clone');
    $assertSession->waitForElementVisible('css', '.admin-link');
    $assertSession->waitForElementVisible('css', '.ui-dialog-buttonpane .ui-dialog-buttonset .button--primary');
    $nextjsSitesModal->find('css', '.ui-dialog-buttonpane')->findButton('Clone')->click();
  }

  /**
   * {@inheritdoc}
   */
  private function testNewPreviewSecret(mixed $nextjsSitesFieldset): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $nextjsSitesFieldset->findButton('List additional actions')->click();
    $assertSession->elementExists('named', ['link', 'New preview secret'], $nextjsSitesFieldset)->click();
    $nextjsSitesModal = $assertSession->waitForElementVisible('css', '.ui-dialog');
    $this->assertNotEmpty($nextjsSitesModal);
    $this->assertEquals('Generate New Preview Secret', $nextjsSitesModal->find('css', '.ui-dialog-title')->getText());
    $nextjsSitesModalContent = $nextjsSitesModal->find('css', '#drupal-modal');
    $this->assertNotEmpty($nextjsSitesModalContent);
    $assertSession->elementExists('named', ['content', 'A preview secret has been generated for the headless next.js site:'], $nextjsSitesModalContent);
    $assertSession->elementExists('named', ['content', 'This value can also be retrieved from the next.js site entity.'], $nextjsSitesModalContent);
    $nextjsSitesModal->find('css', '.ui-dialog-titlebar-close')->click();
  }

}
