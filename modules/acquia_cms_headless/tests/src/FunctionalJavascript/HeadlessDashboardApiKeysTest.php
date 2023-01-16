<?php

namespace Drupal\Tests\acquia_cms_headless\FunctionalJavascript;

/**
 * Tests headless dashboard API key.
 *
 * @group acquia_cms
 * @group acquia_cms_headless
 * @group medium_risk
 * @group push
 */
class HeadlessDashboardApiKeysTest extends HeadlessDashboardTestBase {

  /**
   * Test  API Keys section exists.
   */
  public function testSectionAvailable(): void {
    $this->assertSession()->elementExists('css', 'form#acquia-cms-headless-api-keys');
    $consumers_fieldset = $this->assertSession()->elementExists('css', '#edit-consumers-api-keys');
    $this->assertEquals($consumers_fieldset->find('css', 'span')->getText(), ' API Keys ');

    // Check button with label 'Create new consumer'.
    $consumers_fieldset->findButton('Create new consumer');
  }

}
