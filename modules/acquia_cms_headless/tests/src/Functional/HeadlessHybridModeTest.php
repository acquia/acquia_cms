<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

/**
 * Tests for acquia_cms_headless Hybrid mode.
 *
 * @group acquia_cms_headless
 * @group low_risk
 */
class HeadlessHybridModeTest extends HeadlessTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_toolbar',
  ];

  /**
   * Test Headless Dashboard in hybrid mode.
   */
  public function testAcmsWizardMenu(): void {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);
    $assert_session = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    // Get Fieldset to fetch Headless Dashboard link.
    $menuItem = $assert_session->elementExists('css', '.toolbar-icon-acquia-cms-tour-tour')->getParent()->mouseOver();
    $headlessDashboard = $menuItem->getParent()->find('css', '.toolbar-icon-acquia-cms-headless-dashboard');
    $this->assertEquals('Headless dashboard', $headlessDashboard->getText());
    $assert_session->statusCodeEquals(200);

    // Test if Enable headless mode checkbox works.
    $this->drupalGet('/admin/tour/dashboard');
    $checkbox = $assert_session->elementExists('css', '#edit-headless-mode');
    $this->assertNotEmpty($checkbox);
    $checkbox->check();
    $page->pressButton('Save');

    // Test Headless Dashboard link is not available.
    $toolbar = $assert_session->elementExists('css', '#toolbar-administration');
    $tourLink = $assert_session->elementExists('named', ['link', 'Acquia CMS Wizard'], $toolbar);
    $this->assertSame('Acquia CMS Wizard', $tourLink->getText());
    $this->assertTrue($tourLink->hasClass('toolbar-icon'));

    // Headless dashboard should not show up.
    $assert_session->elementNotExists('named', ['link', 'Headless dashboard'], $toolbar);
  }

}
