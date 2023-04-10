<?php

namespace Drupal\Tests\acquia_cms_headless\Traits;

/**
 * Trait table assertions.
 */
trait HeadlessNextJsTrait {

  /**
   * Function to enable headless mode.
   */
  protected function enableHeadlessMode(): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert */
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet("admin/tour/dashboard");
    $assert->waitForElementVisible('css', '.ui-dialog .acms-welcome-modal');
    $assert->waitForText('Welcome to Acquia CMS.');
    $assert->elementExists('css', '.ui-icon-closethick')->click();
    $assert->elementExists('css', 'summary[role="button"].claro-details__summary')->click();
    $page->checkField('headless_mode');
    $assert->checkboxChecked('edit-headless-mode');
    $page->pressButton('Save');
    $this->drupalGet("admin/content");
  }

  /**
   * Assert new nextjs site.
   */
  protected function assertNewNextJsSites(): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert */
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Visit add nextJs site page.
    $this->drupalGet("admin/config/services/next/sites/add");
    // Fields exists check.
    $assert->fieldExists('label');
    $assert->fieldExists('base_url');
    $assert->fieldExists('preview_url');
    $assert->fieldExists('preview_secret');
    $assert->buttonExists('Save');
    // Setup nextJS site.
    $page->fillField('Label', 'Headless Site One');
    $page->fillField('base_url', 'https://localhost.com:3000');
    $page->fillField('preview_url', 'https://localhost.com:3000/api/preview');
    $page->fillField('preview_secret', 'secret1one');
    $assert->waitForElementVisible('css', '.admin-link');
    $assert->elementExists('named', ['button', 'Save'])->click();

    // Setup another nextJs site.
    $this->drupalGet("admin/config/services/next/sites/add");
    $page->fillField('Label', 'Headless Site Two');
    $page->fillField('base_url', 'https://localhost.com:3001');
    $page->fillField('preview_url', 'https://localhost.com:3001/api/preview');
    $page->fillField('preview_secret', 'secret2two');
    $assert->waitForElementVisible('css', '.admin-link');
    $assert->elementExists('named', ['button', 'Save'])->click();
  }

  /**
   * Assert configure nextJs entity type.
   */
  protected function assertNextJsEntityTypeConfigure(): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert */
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet("admin/config/services/next/entity-types/add");
    $assert->selectExists('id')->selectOption('node.test');
    $assert->waitForElementVisible('css', '.settings-container');
    $this->assertTrue($assert->optionExists('id', 'node.test')->isSelected());
    $assert->selectExists('site_resolver')->selectOption('site_selector');
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForText('Next.js sites');
    $this->assertTrue($assert->optionExists('site_resolver', 'site_selector')->isSelected());
    $page->checkField('sites[headless_site_one]');
    $assert->checkboxChecked('sites[headless_site_one]');
    $page->checkField('sites[headless_site_two]');
    $assert->checkboxChecked('sites[headless_site_two]');
    $assert->buttonExists('Save')->press();
  }

}
