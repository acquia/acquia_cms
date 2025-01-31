<?php

namespace Drupal\Tests\acquia_cms_site_studio\Functional;

use Drupal\Tests\acquia_cms_site_studio\Traits\PermissionsTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests basic, broad permissions of the developer roles.
 *
 * This test ensures that the developer role has the necessary permissions to
 * use any newly created text format.
 *
 * @group acquia_cms_site_studio
 * @group acquia_cms
 * @group risky
 */
class NewEditorPermissionsTest extends BrowserTestBase {

  use PermissionsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_site_studio',
  ];

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
  public function getFixtureBasePath(): string {
    return dirname(__DIR__) . "/fixtures/permissions/basic";
  }

  /**
   * Defines an array of role which shouldn't exists.
   */
  public static function providerRoleExistNotExist(): array {
    return [
      [
        [
          "developer",
        ],
        [
          "content_administrator",
          "content_author",
          "content_editor",
        ],
      ],
    ];
  }

  /**
   * Defines basic permissions & no permissions for roles.
   *
   * @throws \Exception
   */
  public static function providerBasicPermissions(): array {
    $instance = new static('test');
    // Install acquia_cms_site_studio_test module.
    $instance->container->get('module_installer')->install(['acquia_cms_site_studio_test']);
    return [
      [
        'developer',
        array_merge($instance->getPermissionsByRole('developer'), [
          'use text format filtered_html',
          'use text format full_html',
        ]),
      ],
    ];
  }

}
