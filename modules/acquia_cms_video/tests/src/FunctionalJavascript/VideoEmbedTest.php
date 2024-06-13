<?php

namespace Drupal\Tests\acquia_cms_video\FunctionalJavascript;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\Tests\acquia_cms_common\FunctionalJavascript\MediaEmbedTestBase;

/**
 * Tests embedding video media in CKEditor.
 *
 * @group acquia_cms
 * @group acquia_cms_video
 * @group medium_risk
 * @group push
 */
class VideoEmbedTest extends MediaEmbedTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['acquia_cms_video'];

  /**
   * {@inheritdoc}
   */
  protected $mediaType = 'video';

  /**
   * {@inheritdoc}
   */
  public function testEmbedMedia(): void {
    if (AcquiaDrupalEnvironmentDetector::isAhIdeEnv()) {
      $this->markTestSkipped('This cannot be run in a Cloud IDE right now');
    }
    $node_type = $this->drupalCreateContentType()->id();
    user_role_grant_permissions('content_author', [
      "create $node_type content",
    ]);

    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet("/node/add/$node_type");
    $this->doTestCreateMedia();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function addMedia(): void {
    $this->getSession()
      ->getPage()->fillField('Add Video via URL', 'https://youtu.be/lg879YYbihU');
    $this->getSession()->getPage()->pressButton('Add');
  }

}
