<?php

namespace Drupal\Tests\acquia_cms_article\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests installation of Acquia CMS Articles.
 *
 * @group acquia_cms
 * @group acquia_cms_content
 */
class ArticleTest extends ExistingSiteBase {

  /**
   * Tests that Article nodes are available at expected path.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testArticleContent() {

    global $base_url;

    // Creates a user. Will be automatically cleaned up at the end of the test.
    $author = $this->createUser([], NULL, TRUE);

    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Article Test Title',
      'moderation_state' => 'published',
      'uid' => $author->id(),
    ]);
    $node->save();
    $this->assertEquals($author->id(), $node->getOwnerId());
    // TODO: What other content should we test here?
    // Test that pathauto is working as expected.
    $this->drupalGet($base_url . '/article/article-test-title');
    $this->assertSession()->statusCodeEquals(200);
  }

}
