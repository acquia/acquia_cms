<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Behat\Mink\Element\NodeElement;

/**
 * Tests 'Audio' cohesion component.
 *
 * @group acquia_cms
 * @group site_studio
 * @group low_risk
 * @group pr
 * @group push
 */
class AudioComponentTest extends CohesionComponentTestBase {

  /**
   * Tests that the component can be added to a layout canvas.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testComponent(): void {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');

    // Add the component to the layout canvas.
    $edit_form = $this->getLayoutCanvas()->add('Audio')->edit();
    /** @var \Behat\Mink\Element\TraversableElement $edit_form */
    $edit_form->pressButton('Browse');
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $mediaLibraryContent = $assertSession->waitForElementVisible("css", ".media-library-content");
    $assertSession->waitForElementVisible("css", "#acquia-dam-user-authorization-skip");
    $damAuthorizeScreen = $mediaLibraryContent->find("css", "#acquia-dam-user-authorization-skip");
    // First time DAM show confirmation screen to authorize access.
    // We will press skip button only if it appears.
    if ($damAuthorizeScreen instanceof NodeElement) {
      $damAuthorizeScreen->click();
    }
    $this->assertTrue($assertSession->waitForText('Entity Browser'));
    $assertSession->waitForElementVisible("css", ".media-library-content");
    $assertSession->waitForElementVisible('css', '.form-item--soundcloud-url input');
    $this->getSession()->wait(1000);
    $this->getSession()->getPage()->fillField('soundcloud_url', 'https://soundcloud.com/yungh-tej/na-na-na-official-song-osekhon-ft-tej-gill?utm_source=clipboard&utm_medium=text&utm_campaign=so');
    $this->getSession()->getPage()->find("css", "#media-library-content input.button--primary")->click();
    $assertSession->waitForElementVisible('css', '.field--name-name input[name="media[0][fields][name][0][value]"]');
    $this->getSession()->wait(1000);
    $this->getSession()->getPage()->find("css", ".ui-dialog-buttonset button")->click();
    $this->getSession()->wait(1000);
    $this->selectMedia(0);
    $this->getSession()->wait(1000);
    $this->insertSelectedMedia();
  }

  /**
   * Tests that component can be edited by a specific user role.
   *
   * @param string $role
   *   The ID of the user role to test with.
   *
   * @dataProvider providerEditAccess
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testEditAccess(string $role): void {
    $account = $this->createUser();
    $account->addRole($role);
    $account->save();
    $this->drupalLogin($account);

    // Visit to cohesion components page.
    $this->drupalGet('/admin/cohesion/components/components');
    $this->editDefinition('Basic components', 'Audio');
  }

}
