<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Session;
use Drupal\Tests\acquia_cms\Traits\AwaitTrait;
use PHPUnit\Framework\Assert;

/**
 * Base wrapper class for interacting with the Cohesion UI.
 */
abstract class CohesionElement extends NodeElement {

  /**
   * @var \Behat\Mink\Session
   */
  protected $session;

  /**
   * {@inheritdoc}
   */
  public function __construct($xpath, Session $session) {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $session */
    $this->session = $session;
    parent::__construct($xpath, $session);
  }

  use AwaitTrait {
    AwaitTrait::waitForElementVisible as traitWaitForElementVisible;
  }

  /**
   * {@inheritdoc}
   */
  protected function waitForElementVisible(string $selector, $locator, ElementInterface $container = NULL): ElementInterface {
    return $this->traitWaitForElementVisible($selector, $locator, $container ?: $this->session->getPage());
  }

  /**
   * Waits for the element browser sidebar to be visible.
   *
   * @return \Drupal\Tests\acquia_cms\ExistingSiteJavascript\ElementBrowser
   *   A wrapper object for interacting with the element browser.
   */
  protected function waitForElementBrowser() : ElementBrowser {
    $element = $this->waitForElementVisible('css', '#ssa-sidebar-browser');
    return new ElementBrowser($element->getXpath(), $this->session);
  }

  /**
   * Asserts that a component appears in the layout canvas.
   *
   * @param string $label
   *   The component label.
   *
   * @return \Drupal\Tests\acquia_cms\ExistingSiteJavascript\Component
   *   The expected component.
   */
  public function assertComponent(string $label) : Component {
    $selector = sprintf('.ssa-layout-canvas-list-item-type-component[data-title="%s"]', $label);
    $element = $this->waitForElementVisible('css', $selector, $this);

    return new Component($element->getXpath(), $this->session);
  }

  /**
   * Locates a button by its ARIA label and presses it.
   *
   * @param string $button_label
   *   The button's ARIA label.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element that contains the button. Defaults to the called
   *   object.
   */
  protected function pressAriaButton(string $button_label, ElementInterface $container = NULL) : void {
    $selector = sprintf('button[aria-label="%s"]', $button_label);
    $button = ($container ?: $this)->find('css', $selector);
    Assert::assertInstanceOf(ElementInterface::class, $button);
    $button->press();
  }

}
