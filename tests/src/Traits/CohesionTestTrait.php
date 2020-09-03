<?php

namespace Drupal\Tests\acquia_cms\Traits;

/**
 * Provides helper methods for tests which need to deal with Cohesion quirks.
 */
trait CohesionTestTrait {

  /**
   * Asserts that a link exists with the given title attribute.
   *
   * This is needed because, in certain cases (like teasers on a search page),
   * links to content are only identifiable by their 'title' attribute.
   *
   * @param string $title
   *   The title of the link.
   */
  private function assertLinkExistsByTitle(string $title) : void {
    $this->assertSession()->elementExists('css', 'a.coh-link[title="' . $title . '"]');
  }

  /**
   * Asserts that a link with the given title attribute doesn't exist.
   *
   * This is needed because, in certain cases (like teasers on a search page),
   * links to content are only identifiable by their 'title' attribute.
   *
   * @param string $title
   *   The title of the link.
   */
  private function assertLinkNotExistsByTitle(string $title) : void {
    $this->assertSession()->elementNotExists('css', 'a.coh-link[title="' . $title . '"]');
  }

}
