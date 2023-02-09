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
    $assertSession = $this->assertSession();

    $acmsWizardLi = $assertSession->elementExists('css', '.toolbar-icon-acquia-cms-tour-tour')->getParent();
    $this->assertNotEmpty($acmsWizardLi);
    $acmsWizardLi->mouseOver();
    $headlessDashboard = $assertSession->waitForElementVisible('css', '.toolbar-icon-acquia-cms-headless-dashboard');
    $this->assertEquals('Headless dashboard', $headlessDashboard->getText());

    // Click on Setup manually button to close the modal,
    // note there are two button with same label 'Setup manually'.
    $this->drupalGet('/admin/tour/dashboard');
    $acmsWelcomeModal = $assertSession->waitForElementVisible('css', '.acms-welcome-modal');
    $btn_panes = $assertSession->elementExists('css', '.ui-dialog-buttonpane', $acmsWelcomeModal);
    $assertSession->buttonExists('Setup Manually', $btn_panes)->press();

    // Test Enable headless mode checkbox functionality.
    $headlessForm = $assertSession->elementExists('css', '#acquia-cms-headless-form');
    $assertSession->elementExists('css', '.claro-details__summary', $headlessForm)->press();
    $headlessForm->checkField('Enable Headless mode');
    $headlessForm->pressButton('Save');

    // Ensure that after save button headless mode is enabled.
    $assertSession->pageTextContains('Acquia CMS Pure Headless has been enabled.');

    // Test Headless Dashboard link is not available.
    $toolbar = $assertSession->elementExists('css', '#toolbar-administration');
    $tourLink = $assertSession->elementExists('named', ['link', 'Acquia CMS Wizard'], $toolbar);
    $this->assertSame('Acquia CMS Wizard', $tourLink->getText());
    $this->assertTrue($tourLink->hasClass('toolbar-icon'));

    // Headless dashboard should not show up.
    $assertSession->elementNotExists('named', ['link', 'Headless dashboard'], $toolbar);
  }

}
