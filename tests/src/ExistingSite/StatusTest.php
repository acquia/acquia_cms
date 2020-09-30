<?php

namespace Drupal\Tests\acquia_cms\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests that the site can bootstrap and return a 200 status.
 *
 * @group acquia_cms
 */
class StatusTest extends ExistingSiteBase {

  /**
   * Tests status codes of key Drupal routes.
   *
   * After a recent drupal/webform update that caused fatals on the
   * block admin page, these tests are intended to bootstrap Drupal
   * and sniff test key URLs to ensure that the status code reflects
   * a 200 (and not a 500 or other error that might indicate
   * breakage from custom or contrib code).
   *
   * @param array $paths
   *   The Drupal path(s) that we will test for a 200 status code.
   *
   * @dataProvider statusCodeData
   */
  public function testStatusCodes(array $paths) {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->setCurrentUser($account);

    $assert_session = $this->assertSession();
    foreach ($paths as $path) {
      $this->drupalGet($path);
      $assert_session->statusCodeEquals(200);
    }
  }

  /**
   * Data provider for ::testStatusCodes().
   *
   * @return array
   *   Sets of arguments to pass to the test method.
   */
  public function statusCodeData() {
    return [
      "/admin/content",
      "/admin/structure/blocks",
      'administrator', "/admin/people",
    ];
  }

}
