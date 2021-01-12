<?php

namespace Drupal\Tests\acquia_cms_support\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests integration with Acquia configuration synchronisation.
 *
 * @group acquia_cms
 * @group acquia_cms_support
 */
class AcquiaCmsConfigSyncTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Disable strict config schema checks in this test.
   *
   * Cohesion has a lot of config schema errors, and until they are all fixed,
   * this test cannot pass unless we disable strict config schema checking
   * altogether. Since strict config schema isn't critically important in
   * testing this functionality, it's okay to disable it for now, but it should
   * be re-enabled (i.e., this property should be removed) as soon as possible.
   *
   * @var bool
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_support',
    'acquia_cms_common',
  ];

  /**
   * Tests acquia config sync pages with administrator role.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testAcquiaConfigSyncPages() {
    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/development/acquia_cms_configuration_inspector');

    $this->assertSession()->statusCodeEquals(200);

    // Check that both tabs link are available.
    $page = $this->getSession()->getPage();
    $page->findLink('Overridden Configurations');
    $page->findLink('Unchanged Configurations');

    // Check that 'Overridden Configurations' link is accessible.
    $page->clickLink('Overridden Configurations');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/admin/config/development/acquia_cms_configuration_inspector');
    // Check that 'Overridden Configurations' link is accessible.
    $page->clickLink('Unchanged Configurations');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests acquia config sync overridden page.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAcquiaConfigSyncOverriddenPage() {
    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/development/acquia_cms_configuration_inspector/overridden');

    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);

    // Check that both tabs link are available.
    $page = $this->getSession()->getPage();
    $page->findLink('Overridden Configurations');
    $page->findLink('Unchanged Configurations');

  }

  /**
   * Tests acquia config sync pages with non administer role.
   *
   * @param string[]|null $roles
   *   The user role(s) to test with, or NULL to test as an anonymous user. If
   *   this is an empty array, the test will run as an authenticated user with
   *   no additional roles.
   *
   * @dataProvider providerAcquiaConfigSyncPagesForNonAdmin
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAcquiaConfigSyncPagesForNonAdmin(?array $roles) {
    if (isset($roles)) {
      $account = $this->createUser();
      array_walk($roles, [$account, 'addRole']);
      $account->save();
      $this->drupalLogin($account);
    }

    $this->drupalGet('/admin/config/development/acquia_cms_configuration_inspector');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/admin/config/development/acquia_cms_configuration_inspector/overridden');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/admin/config/development/acquia_cms_configuration_inspector/unchanged');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Data provider for ::testAcquiaConfigSyncPagesForNonAdmin().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerAcquiaConfigSyncPagesForNonAdmin() {
    return [
      'anonymous user' => [NULL],
      'authenticated user' => [
        [],
      ],
      'content author' => [
        ['content_author'],
      ],
      'content editor' => [
        ['content_editor'],
      ],
      'content administrator' => [
        ['content_administrator'],
      ],
    ];
  }

}
