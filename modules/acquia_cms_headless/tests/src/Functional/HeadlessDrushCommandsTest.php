<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Drupal\Tests\BrowserTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Tests headless drush commands.
 *
 * @group acquia_cms
 * @group acquia_cms_headless
 * @group medium_risk
 * @group push
 */
class HeadlessDrushCommandsTest extends BrowserTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_headless',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function testHeadlessCommands(): void {
    $options = [
      'site-url' => 'http://localhost:3000',
      'site-name' => 'Headless site',
    ];
    // Execute new headless site.
    $this->drush('acms:headless:new-nextjs', [], $options);
    $newNextJsData = $this->getErrorOutputAsList();
    $this->assertEquals("Use these environment variables for your Next.js application. Place them in your .env file:", str_replace("[notice] ", "", $newNextJsData[0]));
    $this->assertEquals("NEXT_PUBLIC_DRUPAL_BASE_URL=http://default", $newNextJsData[1]);
    $this->assertEquals("NEXT_IMAGE_DOMAIN=default", $newNextJsData[2]);
    $this->assertEquals("DRUPAL_SITE_ID=headless_site", $newNextJsData[3]);
    $this->assertEquals("DRUPAL_FRONT_PAGE=/user/login", $newNextJsData[4]);
    $this->assertStringStartsWith("DRUPAL_PREVIEW_SECRET", $newNextJsData[5]);
    $this->assertStringStartsWith("DRUPAL_CLIENT_ID", $newNextJsData[6]);
    $this->assertStringStartsWith("DRUPAL_CLIENT_SECRET", $newNextJsData[7]);

    // Execute headless regenerate environment.
    $this->drush('acms:headless:regenerate-env', [], ['site-url' => 'http://localhost:3000']);
    $regenerateEnvData = $this->getErrorOutputAsList();
    $clientSecret = end($regenerateEnvData);
    array_pop($regenerateEnvData);
    foreach ($regenerateEnvData as $key => $value) {
      $this->assertEquals($newNextJsData[$key], $value);
    }
    // Client secret will be change while regenerating environment.
    $this->assertNotEquals($newNextJsData[7], $clientSecret);
  }

}
