<?php

namespace Drupal\Tests\acquia_cms_headless\Traits;

use Behat\Mink\Element\NodeElement;

/**
 * Trait to test dashboard section.
 */
trait DashboardSectionTrait {

  /**
   * Visits the headless dashboard page.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function visitHeadlessDashboardAdmin(): void {
    $account = $this->drupalCreateUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    // Visit headless dashboard.
    $this->drupalGet("/admin/headless/dashboard");
  }

  /**
   * Returns the section element.
   */
  public function getSection(): NodeElement {
    $this->assertNotEmpty($this->getSession()->getPage()->find("css", $this->getSectionSelector()));
    return $this->getSession()->getPage()->find("css", $this->getSectionSelector());
  }

  /**
   * Returns the section css selector.
   */
  protected function getSectionSelector(): string {
    $message = [
      "class `" . get_class($this) . "` must define property \$sectionSelector.",
      "Ex: protected string \$sectionSelector = \"#acquia-cms-headless-api-url\";",
    ];
    $this->assertNotEmpty($this->sectionSelector, implode(PHP_EOL, $message));
    return $this->sectionSelector;
  }

  /**
   * Assert the section button.
   *
   * @param string $text
   *   The text.
   */
  public function assertButton(string $text): void {
    $element = $this->getSession()->getPage()->findLink($text);
    $this->assertSame($text, $element->getText());
  }

  /**
   * Asserts that the section has button.
   *
   * @param mixed $section
   *   The section.
   * @param string $buttonLink
   *   An button label.
   */
  public function assertButtonLink(mixed $section, string $buttonLink): void {
    $buttonAction = $section->find('css', '.button')->getAttribute('href');
    $this->assertEquals($buttonAction, $buttonLink);
  }

  /**
   * Tests the section title.
   */
  public function testTitle(): void {
    $message = [
      "class `" . get_class($this) . "` must define property \$sectionTitle.",
      "Ex: protected string \$sectionTitle = \"API URL\";",
    ];
    $this->assertNotEmpty($this->sectionTitle, implode(PHP_EOL, $message));
    $title = $this->getSection()->find("css", ".fieldset__label")->getText();
    $this->assertSame($title, $this->sectionTitle);
  }

  /**
   * Function to test buttons.
   */
  abstract public function testButtons(): void;

  /**
   * Function to test section in dashboard.
   */
  abstract public function testSection(): void;

}
