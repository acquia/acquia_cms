<?php

namespace Drupal\Tests\acquia_cms_tour\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Acquia CMS Tour module's integration with the Acquia Telemetry.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 */
class AcquiaTelemetryTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
  ];

  /**
   * Tests if Acquia Telemetry can be managed via tour page.
   */
  public function testAcquiaTelemetry() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser([
      'access acquia cms tour dashboard',
    ]);
    $this->drupalLogin($account);

    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(200);

    // Initially the checkbox should be turned off.
    $assert_session->checkboxNotChecked('Send anonymous data about Acquia product usage');
    // Ensure we can enable the Acquia Telemetry module.
    $page->checkField('Send anonymous data about Acquia product usage');
    $page->pressButton('Save');
    // Check if module is installed or not.
    $assert_session->pageTextContains('You have opted into Acquia Telemetry. Thank you for helping improve Acquia products.');
    $this->rebuildContainer();
    $this->assertTrue($this->container->get('module_handler')->moduleExists('acquia_telemetry'));
    // Ensure we can uninstall the Acquia Telemetry module.
    $page->uncheckField('Send anonymous data about Acquia product usage');
    $page->pressButton('Save');
    // Check if module is uninstalled or not.
    $assert_session->pageTextContains('You have successfully opted out of Acquia Telemetry. Anonymous usage information will no longer be collected.');
    $this->rebuildContainer();
    $this->assertFalse($this->container->get('module_handler')->moduleExists('acquia_telemetry'));
  }

}
