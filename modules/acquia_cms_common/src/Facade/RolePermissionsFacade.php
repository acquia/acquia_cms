<?php

namespace Drupal\acquia_cms_common\Facade;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade for integrating with RolesPermissions.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class RolePermissionsFacade implements ContainerInjectionInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * RolePermissionsFacade constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * An array of default permissions based on roles and modules.
   *
   * @return array
   *   Returns an array of roles and permissions.
   */
  public function defaultRolePermissions(string $module): array {
    // Check for module exists and installed.
    if ($this->moduleHandler->moduleExists($module)) {
      // General permissions for admin and author.
      $acms_general_admin_author_permissions = [
        'access content overview',
        'access media overview',
        'clone node entity',
        'use editorial transition create_new_draft',
        'use editorial transition review',
        'use moderation dashboard',
        'use moderation sidebar',
        'view latest version',
      ];

      // Covered site-builder and developer permissions.
      $acms_common_permissions = [
        'use text format filtered_html',
        'use text format full_html',
        'view the administration theme',
      ];
      $acms_content_editor_permissions = array_merge($acms_general_admin_author_permissions,
        $acms_common_permissions,
        [
          'schedule publishing of nodes',
          'use editorial transition archive',
          'view any unpublished content',
          'view scheduled content',
        ]);
      $acms_content_admin_permissions = array_merge($acms_content_editor_permissions,
        [
          'access taxonomy overview',
          'administer media',
          'administer moderation sidebar',
          'administer nodes',
          'administer taxonomy',
          'bypass node access',
          'schedule publishing of nodes',
          'use editorial transition archived_published',
          'use editorial transition publish',
          'view any moderation dashboard',
        ]);
      $acms_content_author_permissions = array_merge($acms_general_admin_author_permissions,
        $acms_common_permissions,
        [
          'administer menu',
          'view own unpublished content',
        ]);

      $acms_user_admin_permissions = [
        'administer CAPTCHA settings',
        'administer honeypot',
        'administer recaptcha',
        'administer seckit',
        'administer shield',
        'administer site configuration',
        'administer users',
        'manage password reset',
        'view the administration theme',
      ];

      // Conditional check for specific module.
      if ($module === 'acquia_cms_headless') {
        // Mapping roles with their permissions.
        $roles_permissions = [
          'site_builder' => $acms_common_permissions,
          'user_administrator' => $acms_user_admin_permissions,
          'headless' => [
            'access acquia cms headless api dashboard',
            'access user profiles',
            'administer acquia cms headless configuration',
            'administer acquia cms headless keys',
            'bypass node access',
            'issue subrequests',
          ],
          'frontend_preview_headless' => [
            'view latest version',
            'view any unpublished content',
          ],
        ];
      }
      elseif ($module === 'acquia_cms_site_studio') {
        // Define site studio basic permissions.
        $basic_site_studio_permissions = [
          'access animate on view',
          'access component builder elements group',
          'access component content',
          'access components',
          'access content elements group',
          'access custom elements group',
          'access drupal core elements group',
          'access elements',
          'access fields',
          'access form fields fields group',
          'access form help fields group',
          'access form layout fields group',
          'access helpers',
          'access interactive elements group',
          'access layout elements group',
          'access media elements group',
          'access menu elements group',
          'access view elements group',
          'use text format cohesion',
        ];

        // Site-studio component and helper group permissions.
        $components_helpers_permissions = array_merge($basic_site_studio_permissions,
          [
            'access cpt_cat_dynamic_components cohesion_component_category group',
            'access cpt_cat_general_components cohesion_component_category group',
            'access cpt_cat_hero_components cohesion_component_category group',
            'access cpt_cat_interactive_components cohesion_component_category group',
            'access cpt_cat_layout_components cohesion_component_category group',
            'access cpt_cat_map_components cohesion_component_category group',
            'access cpt_cat_media_components cohesion_component_category group',
            'access cpt_cat_template_components cohesion_component_category group',
            'access hlp_cat_dynamic_helpers cohesion_helper_category group',
            'access hlp_cat_general_helpers cohesion_helper_category group',
            'access hlp_cat_interactive_helpers cohesion_helper_category group',
            'access hlp_cat_layout_helpers cohesion_helper_category group',
            'access hlp_cat_media_helpers cohesion_helper_category group',
          ]);

        // Define site studio content administration permissions.
        $content_admin_author_permissions = array_merge($components_helpers_permissions,
          [
            'access accordion_sections cohesion_helper_category group',
            'access card_sections cohesion_helper_category group',
            'access cpt_cat_accordion_components cohesion_component_category group',
            'access cpt_cat_basic_components cohesion_component_category group',
            'access cpt_cat_card_components cohesion_component_category group',
            'access cpt_cat_feature_sections cohesion_component_category group',
            'access cpt_cat_read_more_components cohesion_component_category group',
            'access cpt_cat_slider_components cohesion_component_category group',
            'access cpt_cat_tab_components cohesion_component_category group',
            'access hlp_cat_miscellaneous cohesion_helper_category group',
            'access hlp_cat_page_layouts cohesion_helper_category group',
            'access hlp_cat_slider_sections cohesion_helper_category group',
            'access hlp_cat_text_sections cohesion_helper_category group',
            'access tabbed_sections cohesion_helper_category group',
            'access visual page builder',
          ]
        );

        // Site-studio Site-builder permissions.
        $site_building_administrative_permissions = [
          'access cohesion sync',
          'administer cohesion',
          'administer component categories',
          'administer component content',
          'administer components',
          'administer custom styles',
          'administer helper categories',
          'administer helpers',
          'administer style helpers',
          'administer style_guide',
        ];

        // Mapping roles with their permissions.
        $roles_permissions = [
          'content_administrator' => $content_admin_author_permissions,
          'content_author' => $content_admin_author_permissions,
          'content_editor' => $components_helpers_permissions,
          'developer' => array_merge($basic_site_studio_permissions,
            $acms_common_permissions,
            $site_building_administrative_permissions,
            [
              'access analytics',
              'access color_picker',
              'access context visibility',
              'access hide no data',
              'access markup',
              'access seo',
              'access styles',
              'access tokens',
              'administer base styles',
              'administer cohesion settings',
              'administer content templates',
              'administer master templates',
              'administer menu templates',
              'administer view templates',
              'administer website settings',
            ]),
          'site_builder' => array_merge($site_building_administrative_permissions,
            $acms_common_permissions,
            ['use text format cohesion']),
          'user_administrator' => $acms_user_admin_permissions,
        ];
      }
      elseif ($module === 'acquia_cms_tour') {
        $roles_permissions = [
          'content_administrator' => array_merge($acms_content_admin_permissions,
            [
              'access acquia cms tour',
              'access acquia cms tour dashboard',
            ]),
          'content_author' => array_merge($acms_content_author_permissions,
            ['access acquia cms tour']),
          'content_editor' => array_merge($acms_content_editor_permissions,
            ['access acquia cms tour']),
          'site_builder' => $acms_common_permissions,
          'user_administrator' => $acms_user_admin_permissions,
        ];
      }
      elseif ($module === 'acquia_cms_toolbar') {
        $roles_permissions = [
          'content_administrator' => array_merge($acms_content_admin_permissions,
            ['access toolbar']),
          'content_author' => array_merge($acms_content_author_permissions,
            ['access toolbar']),
          'content_editor' => array_merge($acms_content_editor_permissions,
            ['access toolbar']),
          'site_builder' => array_merge($acms_common_permissions,
            ['access toolbar']),
          'user_administrator' => array_merge($acms_user_admin_permissions,
            ['access toolbar']),
        ];
      }
      else {
        // Mapping roles with their permissions.
        $roles_permissions = [
          'content_administrator' => $acms_content_admin_permissions,
          'content_author' => $acms_content_author_permissions,
          'content_editor' => $acms_content_editor_permissions,
          'site_builder' => $acms_common_permissions,
          'user_administrator' => $acms_user_admin_permissions,
        ];
      }
    }

    return $roles_permissions ?? [];
  }

}
