<?php

namespace Drupal\acquia_cms_image\ExistingSite;

use Drupal\media\Entity\Media;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test if Acquia CMS logo exist or not.
 */
class AcquiaCmsLogoTest extends ExistingSiteBase {

  /**
   * Test to check if Acquia CMS logo exists with specific uuid.
   */
  public function testAcquiaCmsLogo(): void {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    // Verify if media page is getting opened.
    $this->drupalGet('/admin/content/media');
    $this->assertSession()->statusCodeEquals(200);

    // Load media and verify the uuid is same.
    $media = Media::load(1);
    $this->assertInstanceOf(Media::class, $media, "Media `{1}` should exist.");
    $this->assertEquals($media->get('uuid')->getValue()[0]['value'], '0c6f0f26-9fbb-4c2e-804c-418815aba162');
  }

}
