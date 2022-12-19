<?php

namespace Drupal\acquia_cms_site_studio\Helper;

/**
 * Helper class defining all Site Studio permissions.
 */
class SiteStudioPermissionHelper {

  /**
   * Defines Basic Site Studio permissions.
   */
  public static function basicComponentPermissions(): array {
    return [
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
  }

  /**
   * Defines additional Site Studio Component Category permissions.
   */
  public static function additionalComponentCategoryPermissions(): array {
    return [
      'access cpt_cat_accordion_components cohesion_component_category group',
      'access cpt_cat_basic_components cohesion_component_category group',
      'access cpt_cat_card_components cohesion_component_category group',
      'access cpt_cat_feature_sections cohesion_component_category group',
      'access cpt_cat_read_more_components cohesion_component_category group',
      'access cpt_cat_slider_components cohesion_component_category group',
      'access cpt_cat_tab_components cohesion_component_category group',
    ];
  }

  /**
   * Defines additional Site Studio Component Helper permissions.
   */
  public static function additionalComponentHelperPermissions(): array {
    return [
      'access accordion_sections cohesion_helper_category group',
      'access card_sections cohesion_helper_category group',
      'access hlp_cat_hero_sections cohesion_helper_category group',
      'access hlp_cat_miscellaneous cohesion_helper_category group',
      'access hlp_cat_page_layouts cohesion_helper_category group',
      'access hlp_cat_slider_sections cohesion_helper_category group',
      'access hlp_cat_text_sections cohesion_helper_category group',
      'access tabbed_sections cohesion_helper_category group',
    ];
  }

  /**
   * Defines basic Site Studio Component Category permissions.
   */
  public static function basicComponentCategoryHelperPermissions(): array {
    return [
      'access cpt_cat_dynamic_components cohesion_component_category group',
      'access cpt_cat_layout_components cohesion_component_category group',
      'access cpt_cat_map_components cohesion_component_category group',
      'access hlp_cat_dynamic_helpers cohesion_helper_category group',
      'access hlp_cat_layout_helpers cohesion_helper_category group',
      'access hlp_cat_media_helpers cohesion_helper_category group',
    ];
  }

  /**
   * Finds the Site Studio permissions of given role.
   *
   * @param string $role_name
   *   A string role name.
   *
   * @return array|string[]
   *   Returns an array of permissions for given role.
   */
  public static function getSiteStudioPermissionsByRole(string $role_name): array {
    switch ($role_name) {
      case 'content_administrator':
      case 'content_author':
        return array_merge(
          self::basicComponentPermissions(),
          ['access visual page builder'],
        );

      case 'content_editor':
        return self::basicComponentPermissions();

      case 'site_builder':
        return [
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
          'use text format cohesion',
        ];

      default:
        return [];
    }
  }

  /**
   * Static Dynamic Permission by role function.
   *
   * @param string $role_name
   *   Role name.
   *
   * @return array
   *   List of permissions based on role.
   */
  public static function getDynamicPermissionsByRole(string $role_name): array {
    switch ($role_name) {
      case 'content_administrator':
      case 'content_author':
        return array_merge(
          self::basicComponentCategoryHelperPermissions(),
          self::additionalComponentHelperPermissions(),
          self::additionalComponentCategoryPermissions(),
        );

      case 'content_editor':
        return self::basicComponentCategoryHelperPermissions();

      default:
        return [];
    }
  }

}
