<?php

namespace Drupal\Tests\acquia_cms_headless\Traits;

use Behat\Mink\Element\NodeElement;

/**
 * Trait table assertions.
 */
trait DashboardTableTrait {

  /**
   * Gets the table header column element.
   *
   * @param int $columnKey
   *   A column index key.
   */
  protected function getTableHeaderColumn(int $columnKey): NodeElement {
    $header = $this->getHeader();
    $this->assertArrayHasKey($columnKey, $header);
    return $header[$columnKey];
  }

  /**
   * Gets the table body column element.
   *
   * @param int $columnKey
   *   A column index key.
   * @param int $row
   *   A row index key.
   */
  protected function getTableBodyColumn(int $columnKey, int $row = 1): NodeElement {
    $body = $this->getBody($row);
    $this->assertArrayHasKey($columnKey, $body);
    return $body[$columnKey];
  }

  /**
   * Returns an array of table header element.
   */
  protected function getHeader(): array {
    return $this->getSection()->findAll("css", "thead > tr > th");
  }

  /**
   * Returns an array of table body element.
   *
   * @param int $row
   *   A row index key.
   */
  protected function getBody(int $row = 1): array {
    return $this->getSection()->findAll("css", "tbody > tr:nth-child($row) > td");
  }

  /**
   * Tests the table headers.
   *
   * @param array $headers
   *   An array of table headers element.
   *
   * @dataProvider getHeaders
   */
  public function testTableHeaders(array $headers): void {
    foreach ($headers as $key => $text) {
      $element = $this->getTableHeaderColumn($key);
      $this->assertStringStartsWith($text, $element->getText());
    }
  }

  /**
   * Returns an array of table headers element.
   */
  abstract public static function getHeaders(): array;

}
