<?php

namespace Drupal\acquia_cms_tour\Controller;

use Drupal\acquia_cms_tour\Form\AcquiaCmsToolChecklistForm;
use Drupal\acquia_cms_tour\Form\GoogleApiChecklistForm;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Route controller providing a simple tour dashboard for disabled tools.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class DashboardDisabledController extends ControllerBase {

  /**
   * The checklists data to be fetched to build the page sections.
   *
   * @var array
   */
  private const SECTIONS = [
    'acquia_cms_checklist' => AcquiaCmsToolChecklistForm::class,
    'google_api_checklist' => GoogleApiChecklistForm::class,
  ];

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  private $moduleExtensionList;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  private $resolver;

  /**
   * Constructs a new DashboardDisabledController.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $resolver
   *   Class Resolver.
   */
  public function __construct(ModuleExtensionList $extension_list_module, ClassResolverInterface $resolver) {
    $this->moduleExtensionList = $extension_list_module;
    $this->resolver = $resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.list.module'),
      $container->get('class_resolver')
    );
  }

  /**
   * Returns a renderable array for a tour dashboard disabled tools page.
   */
  public function content() {
    $data = [];
    $build = [];

    // Build page sections.
    foreach (static::SECTIONS as $key => $section) {
      $checklist = $this->resolver->getInstanceFromDefinition($section);

      $data[$key]['heading'] = $checklist->getChecklistTitle();
      $data[$key]['description'] = $checklist->getChecklistDescription();

      $list = $checklist->getChecklistModules();
      foreach ($list as $module => $route) {
        if (!$this->moduleHandler()->moduleExists($module)) {
          $module_details = $this->moduleExtensionList->get($module);

          $data[$key]['items'][] = [
            'name' => $module_details->info['name'],
            'description' => $module_details->info['description'],
          ];
        }
      }

      if (isset($data[$key]['items']) && count($data[$key]['items'])) {
        $build[] = [
          '#theme' => 'acquia_cms_tour_dashboard_disabled',
          '#section' => $data[$key],
          '#attached' => ['library' => 'acquia_cms_tour/styling'],
        ];
      }
    }

    return $build;
  }

}
