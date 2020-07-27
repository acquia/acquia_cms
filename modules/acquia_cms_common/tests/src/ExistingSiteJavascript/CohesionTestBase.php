<?php

namespace Drupal\Tests\acquia_cms_common\ExistingSiteJavascript;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Behat\Mink\Element\ElementInterface;
use Drupal\Tests\acquia_cms_common\Traits\MediaTestTrait;
use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Defines a base class for testing Acquia CMS's Cohesion integration.
 */
abstract class CohesionTestBase extends ExistingSiteSelenium2DriverTestBase {

  use MediaTestTrait;

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
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The component that has been added to the layout canvas.
   */
  protected function addComponent(ElementInterface $canvas, string $label) : ElementInterface {
    $this->pressAriaButton($canvas, 'Add content');
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
   * Upload Media in component.
   *
   * @param string $media_bundle
   *   Media bundle.
   */
  protected function uploadMediaInComponent($media_bundle) {
    $this->media = $this->createMedia([
      'bundle' => $media_bundle,
    ]);
    $this->switchToMediaLibraryIframe();
    $this->selectMedia(0);
    $this->insertSelectedMedia();

    // Switching from iframe back to component modal.
    $this->getSession()->switchToIFrame();
    $added_media = $this->assertSession()->waitForElementVisible('css', '.coh-file-browser-preview');
    $this->assertNotNull($added_media);
  }

  /**
   * Switch to iframe.
   */
  protected function switchToMediaLibraryIframe() {
    // Load the media library and switch to iframe.
    $this->assertSession()->waitForText('Media Library');
    $iframe = $this->getSession()->getPage()->find('css', 'iframe[title="Media Library"]');

    // NOTE: Media library add form modal is an 'iframe' without the name
    // attribute. Name attribute is a must-have for selenium2-Driver to switch
    // the test control from page to iframe. So we have set an custom 'name'
    // attribute called 'media_library_iframe' which we will be passing to the
    // `switchToIframe()` function.
    $this->getSession()->executeScript("jQuery(document.getElementsByTagName('iframe')).attr('name', 'media_library_iframe')");
    $this->getSession()->switchToIFrame($iframe->getAttribute('name'));
    $this->assertSession()->waitForElementVisible('css', 'dialog-off-canvas-main-canvas');
  }

  /**
   * Selects a media item in the media library.
   *
   * @param int $position
   *   The zero-based index of the media item to select.
   */
  protected function selectMedia($position) {
    $checkbox = $this->assertSession()
      ->waitForElementVisible('named', ['field', "media_library_select_form[$position]"]);
    $this->assertNotEmpty($checkbox);
    $checkbox->check();
  }

  /**
   * Inserts all selected media and switch from iframe.
   */
  protected function insertSelectedMedia() {
    $this->assertSession()
      ->elementExists('css', '.media-library-select.form-submit')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

}
