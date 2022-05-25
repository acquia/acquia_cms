<?php

namespace Drupal\acquia_cms_headless\Controller;

use Drupal\acquia_cms_headless\AcquiaCmsHeadlessManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a route controller providing a headless api dashboard of Acquia CMS.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class HeadlessDashboardController extends ControllerBase {

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The state interface.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The acquia cms headless manager.
   *
   * @var \Drupal\acquia_cms_headless\AcquiaCmsHeadlessManager
   */
  protected $acquiaCmsHeadlessManager;

  /**
   * Constructs a new ProgressBarForm.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\acquia_cms_headless\AcquiaCmsHeadlessManager $acquia_cms_headless_manager
   *   The acquia cms headless manager class.
   */
  public function __construct(StateInterface $state, ClassResolverInterface $class_resolver, AcquiaCmsHeadlessManager $acquia_cms_headless_manager) {
    $this->state = $state;
    $this->classResolver = $class_resolver;
    $this->acquiaCmsHeadlessManager = $acquia_cms_headless_manager;
  }

  /**
   * Invokes a plugin and returns its output.
   *
   * @param string $plugin_class
   *   The plugin class.
   *
   * @return mixed
   *   The markup/output of the plugin class.
   */
  private function getSectionOutput(string $plugin_class) {
    if (is_a($plugin_class, 'Drupal\Core\Form\FormInterface', TRUE)) {
      return $this->formBuilder()->getForm($plugin_class);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('class_resolver'),
      $container->get('plugin.manager.acquia_cms_headless')
    );
  }

  /**
   * Returns a renderable array for a headless dashboard page.
   */
  public function content() {
    $build = [];
    $build['wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'acms-headless-dashboard-wrapper',
          'layout-row',
          'clearfix',
        ],
      ],
    ];
    $form = [];
    $form['#attached']['library'][] = 'acquia_cms_tour/styling';
    $form['#tree'] = TRUE;

    $form['help_text'] = [
      '#type' => 'markup',
      // @todo Update description for the API Dashboard.
      '#markup' => $this->t(
        "ACMS organizes its features into individual components. The API
        dashboard, organizes the necessary components for operating a partially
        or fully decoupled site."),
    ];

    // Delegate building each section using plugin class.
    foreach ($this->acquiaCmsHeadlessManager->getDefinitions() as $definition) {
      $instance_definition = $this->classResolver->getInstanceFromDefinition($definition['class']);
      if ($instance_definition->isModuleEnabled()) {
        $build['wrapper'][$definition['id']] = $this->getSectionOutput($definition['class']);
      }
    }

    array_unshift($build, $form);

    // Attach acquia_cms_tour_dashboard library.
    $build['#attached'] = [
      'library' => [
        'acquia_cms_tour/acquia_cms_tour_dashboard',
        'acquia_cms_headless/acquia_cms_headless_dashboard',
      ],
    ];

    return $build;
  }

}
