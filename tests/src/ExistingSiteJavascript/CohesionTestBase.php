<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Behat\Mink\Element\ElementInterface;
use Drupal\Tests\acquia_cms_common\Traits\MediaTestTrait;
use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Defines a base class for testing Acquia CMS's Cohesion integration.
 */
abstract class CohesionTestBase extends ExistingSiteSelenium2DriverTestBase {

  use MediaTestTrait {
    createMedia as traitCreateMedia;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // @todo Remove this check when Acquia Cloud IDEs support running functional
    // JavaScript tests.
    if (AcquiaDrupalEnvironmentDetector::isAhIdeEnv()) {
      $this->markTestSkipped('This test cannot run in an Acquia Cloud IDE.');
    }
    parent::setUp();
  }

  /**
   * Adds a component to a layout canvas.
   *
   * @param \Behat\Mink\Element\ElementInterface $canvas
   *   The layout canvas element.
   * @param string $label
   *   The component label.
   * @param string $location
   *   The button location.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The component that has been added to the layout canvas.
   */
  protected function addComponent(ElementInterface $canvas, string $label, string $location = 'component') : ElementInterface {
    $location === 'dropzone' ? $this->pressDropZoneButton($canvas) : $this->pressAriaButton($canvas, 'Add content');
    $element_browser = $this->waitForElementBrowser();

    $selector = sprintf('.coh-layout-canvas-list-item[data-title="%s"]', $label);
    $this->waitForElementVisible('css', $selector, $element_browser)->doubleClick();
    $this->pressAriaButton($element_browser, 'Close sidebar browser');
    return $this->assertComponent($canvas, $label);
  }

  /**
   * Asserts that a component appears in a layout canvas.
   *
   * @param \Behat\Mink\Element\ElementInterface $canvas
   *   The layout canvas element.
   * @param string $label
   *   The component label.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The expected component.
   */
  protected function assertComponent(ElementInterface $canvas, string $label) : ElementInterface {
    $selector = sprintf('.coh-layout-canvas-list-item[data-type="%s"]', $label);
    return $this->waitForElementVisible('css', $selector, $canvas);
  }

  /**
   * Opens the modal edit form for a component.
   *
   * @param \Behat\Mink\Element\ElementInterface $component
   *   The component element.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The modal edit form for the component.
   */
  protected function editComponent(ElementInterface $component) : ElementInterface {
    $this->pressAriaButton($component, 'More actions');
    $this->waitForElementVisible('css', '.coh-layout-canvas-utils-dropdown-menu .coh-edit-btn')->press();

    // Wait for the form wrapper to appear...
    $form = $this->waitForElementVisible('css', '.coh-layout-canvas-settings');
    // ...then wait the form wrapper to load the actual settings form.
    $this->waitForElementVisible('css', 'coh-component-form', $form);
    return $form;
  }

  /**
   * Waits for the element browser sidebar to be visible.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The element browser sidebar.
   */
  protected function waitForElementBrowser() : ElementInterface {
    return $this->waitForElementVisible('css', '.coh-element-browser-modal');
  }

  /**
   * Locates a button by its ARIA label and presses it.
   *
   * @param \Behat\Mink\Element\ElementInterface $container
   *   The element that contains the button.
   * @param string $button_label
   *   The button's ARIA label.
   */
  private function pressAriaButton(ElementInterface $container, string $button_label) : void {
    $selector = sprintf('button[aria-label="%s"]', $button_label);
    $button = $container->find('css', $selector);
    $this->assertInstanceOf(ElementInterface::class, $button);
    $button->press();
  }

  /**
   * Locates the components add button in the dropzone and presses it.
   *
   * @param \Behat\Mink\Element\ElementInterface $container
   *   The element that contains the button.
   */
  private function pressDropZoneButton(ElementInterface $container) : void {
    $selector = 'button[class*="coh-add-btn"]';
    $button = $container->find('css', $selector);
    $this->assertInstanceOf(ElementInterface::class, $button);
    $button->press();
  }

  /**
   * Waits for an element to become visible on the page.
   *
   * @param string $selector
   *   The element selector, e.g. 'css', 'xpath', etc.
   * @param mixed $locator
   *   The element locator, such as a CSS selector or XPath query.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element which will contain the elements we are waiting
   *   for. Defaults to the page.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The element that has become visible.
   */
  protected function waitForElementVisible(string $selector, $locator, ElementInterface $container = NULL) : ElementInterface {
    $container = $container ?: $this->getSession()->getPage();

    $element = $container->waitFor(10, function (ElementInterface $container) use ($selector, $locator) {
      $element = $container->find($selector, $locator);
      return $element && $element->isVisible() ? $element : NULL;
    });

    $this->assertInstanceOf(ElementInterface::class, $element);
    return $element;
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
    $frame = $this->waitForElementVisible('css', $selector);
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
    $this->waitForElementVisible('named', ['field', "media_library_select_form[$position]"])->check();
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

}
