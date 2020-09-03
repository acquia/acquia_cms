<?php

namespace Drupal\Tests\acquia_cms_common\Traits;

/**
 * Provides a framework for asserting a set of links on a page, in order.
 */
trait AssertLinksTrait {

  /**
   * Asserts that a set of links are on the page, in a specific order.
   *
   * @param string[] $expected_links_in_order
   *   (optoinal) The titles of the links we expect to find, in the order that
   *   we expect them to appear on the page. If not provided, this method will
   *   search for links to all published content of the type under test.
   */
  private function assertLinksExistInOrder(array $expected_links_in_order = NULL) : void {
    if ($expected_links_in_order) {
      $count = count($expected_links_in_order);
      $expected_links_in_order = array_intersect($this->getExpectedLinks(), $expected_links_in_order);
      $this->assertCount($count, $expected_links_in_order);
    }
    else {
      $expected_links_in_order = $this->getExpectedLinks();
    }
    $expected_links_in_order = array_values($expected_links_in_order);

    $actual_links = array_intersect($this->getLinks(), $expected_links_in_order);
    $actual_links = array_values($actual_links);

    $this->assertSame($actual_links, $expected_links_in_order);
  }

  /**
   * Returns the text of all links that we expect to be on the page.
   *
   * @return string[]
   *   The text of the links we expect to be on the page, in the order we expect
   *   them to apear.
   */
  abstract protected function getExpectedLinks() : array;

  /**
   * Returns the text all links on the page that we want to assert.
   *
   * @return string[]
   *   The text of the links on the page.
   */
  abstract protected function getLinks() : array;

}
