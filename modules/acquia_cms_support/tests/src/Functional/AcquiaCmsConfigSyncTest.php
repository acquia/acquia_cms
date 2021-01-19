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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Update configuration to cover a simulated
    // config delta calculation test.
    $this->container->get('config.factory')
      ->getEditable('user.role.content_author')
      ->set('label', 'Content creator')
      ->save();
  }

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
    $this->drupalGet('/admin/config/development/acquia-cms-support');

    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);

    // Assert that overridden configurations link is available.
    $assert_session->linkExists('Overridden Configurations');

    // Assert that unchanged configurations link is available.
    $assert_session->linkExists('Unchanged Configurations');

    // Check that 'Overridden Configurations' link is accessible.
    $page = $this->getSession()->getPage();
    $page->clickLink('Overridden Configurations');
    $assert_session->statusCodeEquals(200);

    // Check that 'Overridden Configurations' link is accessible.
    $this->drupalGet('/admin/config/development/acquia-cms-support');
    $page->clickLink('Unchanged Configurations');
    $assert_session->statusCodeEquals(200);
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
    $this->drupalGet('/admin/config/development/acquia-cms-support/overridden-config');

    $this->assertSession()->statusCodeEquals(200);

    $page = $this->getSession()->getPage();
    $td = $page->find('xpath', "//table/tbody/tr/td[contains(text(),'user.role.content_author')]");
    if ($td) {
      // Asset that expected configuration is changed.
      $this->assertTrue($page->find('xpath', "//table/tbody/tr/td[contains(text(),'user.role.content_author')]")->getText() == 'user.role.content_author');
      // Verify simulated config delta % is expected.
      $tr = $td->getParent();
      $this->assertTrue($tr->find('xpath', 'td[3]')->getText() == '98 %');
    }
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

    $this->drupalGet('/admin/config/development/acquia-cms-support');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/admin/config/development/acquia-cms-support/overridden-config');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/admin/config/development/acquia-cms-support/unchanged-config');
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
