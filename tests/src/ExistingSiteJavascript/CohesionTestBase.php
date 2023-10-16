<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Element\NodeElement;
use Drupal\Tests\acquia_cms\Traits\AwaitTrait;
use Drupal\Tests\acquia_cms_common\Traits\MediaTestTrait;
use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Defines a base class for testing Acquia CMS's Cohesion integration.
 */
abstract class CohesionTestBase extends ExistingSiteSelenium2DriverTestBase {

  use AwaitTrait;
  use MediaTestTrait {
    MediaTestTrait::createMedia as traitCreateMedia;
  }

  /**
   * The module_installer service object.
   *
   * @var \Drupal\Core\Extension\ModuleInstaller
   */
  protected $moduleInstaller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->moduleInstaller = $this->container->get('module_installer');
    $this->moduleInstaller->install(['sitestudio_claro']);
    // Set a standard window size so that all javascript tests start with the
    // same viewport.
    $this->getDriverInstance()->maximizeWindow();
  }

  /**
   * Waits for a layout canvas to appear.
   *
   * @return \Drupal\Tests\acquia_cms\ExistingSiteJavascript\LayoutCanvas
   *   A wrapper object for interacting with the layout canvas.
   */
  protected function getLayoutCanvas(): LayoutCanvas {
    $element = $this->waitForElementVisible('css', '.ssa-layout-canvas', $this->getSession()->getPage());
    return new LayoutCanvas($element->getXpath(), $this->getSession());
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
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
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
   * Opens the media library from a component edit form.
   *
   * @param \Behat\Mink\Element\ElementInterface $edit_form
   *   The component edit form.
   * @param string $button_text
   *   The text of the button which opens the media library.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function openMediaLibrary(ElementInterface $edit_form, string $button_text): void {
    /** @var \Behat\Mink\Element\TraversableElement $edit_form */
    $edit_form->pressButton($button_text);
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $this->assertTrue($assertSession->waitForText('Media Library'));
    $damAuthorizeScreen = $assertSession->waitForElementVisible("css", "#acquia-dam-user-authorization-skip");
    // First time DAM show confirmation screen to authorize access.
    // We will press skip button only if it appears.
    if ($damAuthorizeScreen instanceof NodeElement) {
      $damAuthorizeScreen->click();
    }
    $assertSession->waitForElementVisible("css", ".media-library-content #acquia-dam-source-menu-wrapper");
  }

  /**
   * Selects a media item in the media library.
   *
   * @param int $position
   *   The zero-based index of the media item to select.
   * @param string $mediaType
   *   The media type.
   */
  protected function selectMedia(int $position, string $mediaType = ''): void {
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();
    if ($mediaType) {
      $element = $page->find("css", '#media-library-wrapper .media-library-menu li a[data-title="' . $mediaType . '"]');
      if ($element instanceof NodeElement) {
        $element->click();
      }
    }
    /** @var \Behat\Mink\Element\NodeElement $waitElement */
    $waitElement = $this->waitForElementVisible('named', ['field', "media_library_select_form[$position]"], $page);
    $waitElement->check();
  }

  /**
   * Inserts the selected media items and exits the media library's iFrame.
   */
  protected function insertSelectedMedia(): void {
    $this->getSession()->wait(10000);
    $this->getSession()->getPage()->find("css", '.ui-dialog-buttonset .media-library-select')->click();
  }

  /**
   * Selects the media source from dropdown option. Default is DAM option.
   *
   * @param string $source
   *   Media source option i.e. DAM or Media Types (for core media).
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function selectMediaSource(string $source = "DAM"): void {
    $field = $this->getSession()->getPage()->find('css', '.js-acquia-dam-source-field');
    $field->selectOption($source);
    // Wait while container is rendered based on selected Media Source.
    $this->waitForElementVisible('css', '#media-library-view', $this->getSession()->getPage());
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
  protected function pressSaveButton(): void {
    $this->assertSession()
      ->elementExists('css', '#edit-submit')
      ->pressButton('Save');
  }

  /**
   * Waits for the search container.
   *
   * @return \Drupal\Tests\acquia_cms\ExistingSiteJavascript\Search
   *   A wrapper object for interacting with Cohesion's search container.
   */
  protected function getSearch(): Search {
    $element = $this->waitForElementVisible('css', '.search-toggle-button', $this->getSession()->getPage());
    return new Search($element->getXpath(), $this->getSession());
  }

  /**
   * Data provider for testing administrative edit access to components.
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerEditAccess(): array {
    return [
      ['site_builder'],
      ['developer'],
    ];
  }

}
