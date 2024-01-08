<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Tests update.php page as per ACMS new design.
 *
 * @group acquia_cms
 * @group low_risk
 * @group pr
 * @group push
 */
class UpdatePageTest extends ExistingSiteSelenium2DriverTestBase {

  protected function setUp(): void {
    parent::setUp();
    $this->container->get('module_installer')->install(['sitestudio_claro']);
  }

  /**
   * Tests update page design.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUpdatePage(): void {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $site_name = $this->container->get('config.factory')->get('system.site')->get('name');
    $this->drupalLogin($account);

    $this->drupalGet('/update.php');
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();
    $site_name_element = $assert_session->elementExists('css', 'header .site-name');
    $this->assertSame($site_name, $site_name_element->getText());
    $assert_session->pageTextContains("Drupal database update");
  }

  public function tearDown(): void {
    $this->container->get('module_installer')->uninstall(['sitestudio_claro']);
    parent::tearDown();
  }

}
