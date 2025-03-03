<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

/**
 * Tests for acquia_cms_headless Hybrid mode.
 *
 * @group acquia_cms
 * @group acquia_cms_headless
 * @group medium_risk
 * @group push
 */
class HeadlessModeEnablementTest extends HeadlessTestBase {

  /**
   * Test Headless Dashboard in hybrid mode.
   */
  public function testAcmsWizardMenu(): void {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();

    // Click on Setup manually button to close the modal,
    // note there are two button with same label 'Setup manually'.
    $this->drupalGet('/admin/tour/dashboard');
    $acmsWelcomeModal = $assertSession->waitForElementVisible('css', '.acms-welcome-modal');
    $btnPanes = $assertSession->elementExists('css', '.ui-dialog-buttonpane', $acmsWelcomeModal);
    $assertSession->buttonExists('Setup Manually', $btnPanes)->press();
    $assertSession->waitForElementVisible('css', '.toolbar-icon-acquia-cms-tour-tour');
    $acmsWizardLi = $assertSession->elementExists('css', '.toolbar-icon-acquia-cms-tour-tour')->getParent();
    $this->assertNotEmpty($acmsWizardLi);
    $acmsWizardLi->mouseOver();
    $headlessDashboard = $assertSession->waitForElementVisible('css', '.toolbar-icon-acquia-cms-headless-dashboard');
    $this->assertEquals('Headless dashboard', $headlessDashboard->getText());

    // Test Enable headless mode checkbox functionality.
    $headlessForm = $assertSession->waitForElementVisible('css', '#acquia-cms-headless-form');
    $assertSession->elementExists('css', '.acquia-cms-headless-form summary', $headlessForm)->press();
    $assertSession->waitForElementVisible('css', '#acquia-cms-headless-form #edit-headless-mode');
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

    // Test if next js gets enabled or not.
    $assertSession->elementExists('css', '.acquia-cms-headless-form summary', $headlessForm)->press();

    // Test if next js gets enabled or not.
    $headlessForm->checkField('Enable Next.js starter kit');
    $headlessForm->pressButton('Save');

    // Ensure that after save button next.js mode is enabled.
    $assertSession->pageTextContains('Acquia CMS Next.js starter kit has been enabled.');

    // Test disabling headless works or not.
    $assertSession->elementExists('css', '.acquia-cms-headless-form summary', $headlessForm)->press();
    $headlessForm->uncheckField('Enable Headless mode');
    $headlessForm->pressButton('Save');

    // Ensure that after save button headless mode is disabled.
    $assertSession->pageTextContains('Acquia CMS Pure Headless has been disabled.');
  }

}
