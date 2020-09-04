<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Element\NodeElement;
use Drupal\Tests\acquia_cms\Traits\AwaitTrait;
use PHPUnit\Framework\Assert;

/**
 * Base wrapper class for interacting with the Cohesion UI.
 */
abstract class CohesionElement extends NodeElement {

  use AwaitTrait {
    waitForElementVisible as traitWaitForElementVisible;
  }

  /**
   * {@inheritdoc}
   */
  protected function waitForElementVisible(string $selector, $locator, ElementInterface $container = NULL) : ElementInterface {
    return $this->traitWaitForElementVisible($selector, $locator, $container ?: $this->getSession()->getPage());
  }

  /**
   * Waits for the element browser sidebar to be visible.
   *
   * @return \Drupal\Tests\acquia_cms\ExistingSiteJavascript\ElementBrowser
   *   A wrapper object for interacting with the element browser.
   */
  protected function waitForElementBrowser() : ElementBrowser {
    $element = $this->waitForElementVisible('css', '.coh-element-browser-modal');
    return new ElementBrowser($element->getXpath(), $this->getSession());
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
    $selector = sprintf('.coh-layout-canvas-list-item[data-type="%s"]', $label);
    $element = $this->waitForElementVisible('css', $selector, $this);

    return new Component($element->getXpath(), $element->getSession());
  }

  /**
   * Locates a button by its ARIA label and presses it.
   *
   * @param string $button_label
   *   The button's ARIA label.
   */
  protected function pressAriaButton(string $button_label) : void {
    $selector = sprintf('button[aria-label="%s"]', $button_label);
    $button = $this->find('css', $selector);
    Assert::assertInstanceOf(ElementInterface::class, $button);
    $button->press();
  }

}
