<?php

namespace Drupal\acquia_cms_image\ExistingSite;

use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;

/**
 * Test if Acquia CMS logo exist or not.
 */
class AcquiaCmsLogoTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_image',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);
  }

  /**
   * Test to check if Acquia CMS logo exists with specific uuid.
   */
  public function testAcquiaCmsLogo(): void {

    // Verify if media page is getting opened.
    $this->drupalGet('/admin/content/media');
    $this->assertSession()->statusCodeEquals(200);

    // Load media and verify the uuid is same.
    $media = Media::load(1);
    $this->assertInstanceOf(Media::class, $media, "Media `{1}` should exist.");
    $this->assertEquals($media->get('uuid')->getValue()[0]['value'], '0c6f0f26-9fbb-4c2e-804c-418815aba162');

    // Load node page and verify logo path.
    $this->drupalGet('/node');
    $this->assertSession()->statusCodeEquals(200);
    $site_path = $this->container->getParameter('site.path');
    $this->assertEquals($this->assertSession()->elementExists('css', '#block-olivero-site-branding > div.site-branding__inner > a > img')->getAttribute('src'), "/$site_path/files/media-icons/Acquia-CMS.png");
  }

}
