<?php

namespace Drupal\Tests\acquia_cms\Traits;

use Behat\Mink\Element\ElementInterface;
use PHPUnit\Framework\Assert;

/**
 * Contains helpful methods for waiting on various conditions.
 */
trait AwaitTrait {

  /**
   * Waits for an element to become visible.
   *
   * @param string $selector
   *   The element selector, e.g. 'css', 'xpath', etc.
   * @param mixed $locator
   *   The element locator, such as a CSS selector or XPath query.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   The element which will contain the elements we are waiting for.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The element that has become visible.
   */
  protected function waitForElementVisible(string $selector, $locator, ElementInterface $container) : ElementInterface {
    $element = $container->waitFor(10, function (ElementInterface $container) use ($selector, $locator) {
      $element = $container->find($selector, $locator);
      return $element && $element->isVisible() ? $element : NULL;
    });

    Assert::assertInstanceOf(ElementInterface::class, $element);
    return $element;
  }

}
