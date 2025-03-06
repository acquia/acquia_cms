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
    // Validate required parametes on false positive.
    $this->drush('acms:headless:new-nextjs', [], ['site-url' => 'http://localhost:3000'], NULL, NULL, 1);
    $this->assertEquals('[error]  Missing required parameter site name.', $this->getErrorOutput());
    $this->drush('acms:headless:new-nextjs', [], ['site-name' => 'Headless site'], NULL, NULL, 1);
    $this->assertEquals('[error]  Missing required parameter site URL.', $this->getErrorOutput());

    // Execute new headless site.
    $this->drush('acms:headless:new-nextjs', [], ['site-url' => 'http://localhost:3000', 'site-name' => 'Headless site']);
    $newNextJsData = $this->getErrorOutputAsList();
    $this->assertEquals("Use these environment variables for your Next.js application. Place them in your .env file:", str_replace("[notice] ", "", $newNextJsData[0]));
    $this->assertEquals("NEXT_PUBLIC_DRUPAL_BASE_URL=http://default", $newNextJsData[1]);
    $this->assertEquals("NEXT_IMAGE_DOMAIN=default", $newNextJsData[2]);
    $this->assertEquals("DRUPAL_SITE_ID=headless_site", $newNextJsData[3]);
    $this->assertEquals("DRUPAL_FRONT_PAGE=/user/login", $newNextJsData[4]);
    $this->assertStringStartsWith("DRUPAL_CLIENT_ID", $newNextJsData[5]);
    $this->assertStringStartsWith("DRUPAL_PREVIEW_SECRET", $newNextJsData[6]);
    $this->assertStringStartsWith("DRUPAL_CLIENT_SECRET", $newNextJsData[7]);

    // Validate for same site-name.
    $this->drush('acms:headless:new-nextjs', [], ['site-url' => 'http://localhost:3000', 'site-name' => 'Headless site', 'env-file' => '../frontend/.env.local'], NULL, NULL, 1);
    $this->assertEquals('[error]  Site with name [Headless site] already exists!', $this->getErrorOutput());

    // Execute drush new nextjs site with env file parameter.
    $this->drush('acms:headless:new-nextjs', [], ['site-url' => 'http://localhost:3001', 'site-name' => 'Headless next site', 'env-file' => '../frontend/.env.local']);
    $this->assertEquals('[success] Environment variables were written to ../frontend/.env.local', $this->getErrorOutput());

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

    // Execute drush regenerate environment
    // for nextjs site with env file parameter.
    $this->drush('acms:headless:regenerate-env', [], ['site-url' => 'http://localhost:3001', 'env-file' => '../frontend/.env.local']);
    $this->assertEquals('[success] Environment variables were written to ../frontend/.env.local', $this->getErrorOutput());
  }

}
