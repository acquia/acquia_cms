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

  /**
   * Tests update page design.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUpdatePage() {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/update.php');
    $assert_session = $this->assertSession();
    $banner_container = $assert_session->elementExists('css', '.banner-title-img > img');
    $this->assertSame('/profiles/contrib/acquia_cms/acquia_cms.png', $banner_container->getAttribute('src'));
    $assert_session->pageTextContains("Drupal database update");
  }

}
