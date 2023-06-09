<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests 'Logo card' cohesion component.
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
    $edit_form->pressButton('Select file');
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $this->assertNotEmpty($assertSession->waitForText('Entity Browser'));
    $assertSession->waitForElementVisible("css", ".media-library-content");
    $this->getSession()->switchToIFrame("ssa-dialog-iframe");
    $assertSession->waitForElementVisible('css', '#edit-soundcloud-url');
    $this->getSession()->getPage()->fillField('soundcloud_url', 'https://soundcloud.com/yungh-tej/na-na-na-official-song-osekhon-ft-tej-gill?utm_source=clipboard&utm_medium=text&utm_campaign=so');
    $this->getSession()->getPage()->find("css", "#edit-submit")->click();
    $assertSession->waitForElementVisible('css', '.field--name-name input[name="media[0][fields][name][0][value]"]');
    $this->getSession()->getPage()->find("css", ".media-library-add-form .form-actions .form-submit")->click();
    $this->selectMedia(0);
    $this->insertSelectedMedia();
  }

  /**
   * Tests that component can be edited by a specific user role.
   *
   * @param string $role
   *   The ID of the user role to test with.
   *
   * @dataProvider providerEditAccess
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
