<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Behat\Mink\Element\ElementInterface;
use Drupal\Tests\acquia_cms\Traits\AwaitTrait;
use Drupal\Tests\acquia_cms_common\Traits\MediaTestTrait;
use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Defines a base class for testing Acquia CMS's Cohesion integration.
 */
abstract class CohesionTestBase extends ExistingSiteSelenium2DriverTestBase {

  use AwaitTrait;
  use MediaTestTrait {
    createMedia as traitCreateMedia;
  }

  /**
   * Waits for a layout canvas to appear.
   *
   * @return \Drupal\Tests\acquia_cms\ExistingSiteJavascript\LayoutCanvas
   *   A wrapper object for interacting with the layout canvas.
   */
  protected function getLayoutCanvas() : LayoutCanvas {
    $element = $this->waitForElementVisible('css', '.coh-layout-canvas', $this->getSession()->getPage());
    return new LayoutCanvas($element->getXpath(), $element->getSession());
  }

  /**
   * Tries to open the edit form for a component in the administrative UI.
   *
   * @param string $group
   *   The group to which the component belongs.
   * @param string $label
   *   The label of the component.
   */
  protected function editDefinition(string $group, string $label) {
    $assert_session = $this->assertSession();

    // Ensure that the component's group container is open.
    $group = $assert_session->elementExists('css', "details > summary:contains($group)");
    if ($group->getParent()->hasAttribute('open') === FALSE) {
      $group->click();
    }

    $assert_session->elementExists('css', "tr:contains('$label')", $group->getParent())
      ->clickLink('Edit');
  }

  /**
   * Opens the media library from a component edit form, and enters its iFrame.
   *
   * Normally the iFrame does not have a 'name' attribute, but we need it to
   * have one in order for Mink to switch into it. So, if the frame has no name,
   * we assign it one automatically.
   *
   * @param \Behat\Mink\Element\ElementInterface $edit_form
   *   The component edit form.
   * @param string $button_text
   *   The text of the button which opens the media library.
   */
  protected function openMediaLibrary(ElementInterface $edit_form, string $button_text) {
    $edit_form->pressButton($button_text);
    $this->assertNotEmpty($this->assertSession()->waitForText('Media Library'));

    $session = $this->getSession();

    $selector = 'iframe[title="Media Library"]';
    $frame = $this->waitForElementVisible('css', $selector, $session->getPage());
    $name = $frame->getAttribute('name');
    if (empty($name)) {
      $name = 'media_library_iframe';
      $session->executeScript("document.querySelector('$selector').setAttribute('name', '$name')");
    }
    $session->switchToIFrame($name);
  }

  /**
   * Selects a media item in the media library.
   *
   * @param int $position
   *   The zero-based index of the media item to select.
   */
  protected function selectMedia(int $position) : void {
    $this->waitForElementVisible('named', ['field', "media_library_select_form[$position]"], $this->getSession()->getPage())->check();
  }

  /**
   * Inserts the selected media items and exits the media library's iFrame.
   */
  protected function insertSelectedMedia() : void {
    $session = $this->getSession();
    $session->getPage()->pressButton('Insert selected');
    $session->switchToIFrame(NULL);
    $this->assertTrue($session->wait(10000, 'typeof window.media_library_iframe === "undefined"'));
  }

  /**
   * {@inheritdoc}
   */
  protected function createMedia(array $values = []) {
    $media = $this->traitCreateMedia($values);
    $this->markEntityForCleanup($media);
    return $media;
  }

  /**
   * Presses the Save button on a node add/edit form.
   *
   * This is needed because there may be multiple "Save" buttons on the form
   * (probably due to Cohesion interference) and we need to be sure we're
   * pressing the one is that is part of the form's actions area.
   */
  protected function pressSaveButton() : void {
    $this->assertSession()
      ->elementExists('css', '#edit-actions')
      ->pressButton('Save');
  }

  /**
   * Waits for the search container.
   *
   * @return \Drupal\Tests\acquia_cms\ExistingSiteJavascript\Search
   *   A wrapper object for interacting with Cohesion's search container.
   */
  protected function getSearch() : Search {
    $element = $this->waitForElementVisible('css', '.search-toggle-button', $this->getSession()->getPage());
    return new Search($element->getXpath(), $element->getSession());
  }

  /**
   * Data provider for testing administrative edit access to components.
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerEditAccess() {
    return [
      ['site_builder'],
      ['developer'],
    ];
  }

}
