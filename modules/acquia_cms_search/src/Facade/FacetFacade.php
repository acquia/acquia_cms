<?php

namespace Drupal\acquia_cms_search\Facade;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\facets\FacetInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an ACMS facet facade for integrating with Search API.
 */
final class FacetFacade implements ContainerInjectionInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The facets_facet entity object.
   *
   * @var \Drupal\facets\Entity\Facet
   */
  protected $facetEntity;

  /**
   * The logger channel interface object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a ModuleHandlerInterface object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   An entity_type_manager service object.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel service object.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger) {
    $this->moduleHandler = $module_handler;
    $this->facetEntity = $entity_type_manager->getStorage('facets_facet');
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('acquia_cms_search')
    );
  }

  /**
   * An array of default values to create facet entity.
   *
   * @return array
   *   Returns an array of facet config values.
   */
  public function defaultValues() {
    $defaultValues = [
      'facet_source_id' => 'search_api:views_page__search__search',
      'empty_behavior' => ['behavior' => 'none'],
      'query_operator' => 'or',
      'use_hierarchy' => FALSE,
      'keep_hierarchy_parents_active' => FALSE,
      'expand_hierarchy' => FALSE,
      'show_title' => FALSE,
      'enable_parent_when_child_gets_disabled' => TRUE,
      'hard_limit' => 0,
      'exclude' => FALSE,
      'only_visible_when_facet_source_is_visible' => TRUE,
      'weight' => 0,
      'widget' => [
        'type' => 'links',
        'config' => [
          'show_numbers' => TRUE,
          'soft_limit' => 0,
          'soft_limit_settings' => [
            'show_less_label' => 'Show less',
            'show_more_label' => 'Show more',
          ],
          'show_reset_links' => 'false',
          'reset_text' => 'Show all',
          'hide_reset_when_no_selection' => 0,
        ],
      ],
      'processor_configs' => [
        'count_widget_order' => [
          'processor_id' => 'count_widget_order',
          'weights' => ['sort' => 30],
          'settings' => ['sort' => 'DESC'],
        ],
        'display_value_widget_order' => [
          'processor_id' => 'display_value_widget_order',
          'weights' => ['sort' => 40],
          'settings' => ['sort' => 'ASC'],
        ],
        'active_widget_order' => [
          'processor_id' => 'active_widget_order',
          'weights' => ['sort' => 20],
          'settings' => ['sort' => 'DESC'],
        ],
        'url_processor_handler' => [
          'processor_id' => 'url_processor_handler',
          'weights' => ['pre_query' => 50, 'build' => 15],
          'settings' => [],
        ],
        'translate_entity' => [
          'processor_id' => 'translate_entity',
          'weights' => ['build' => 5],
          'settings' => [],
        ],
      ],
    ];
    if ($this->moduleHandler->moduleExists('facets_pretty_paths')) {
      $defaultValues['third_party_settings'] = [
        'facets_pretty_paths' => ['coder' => 'taxonomy_term_coder'],
      ];
    }
    return $defaultValues;
  }

  /**
   * Merges passed values & default values.
   *
   * @param array $values
   *   An array of values to create facet entity.
   *
   * @return array
   *   Returns an array of values.
   */
  public function mergeValues(array $values) {
    return $values + $this->defaultValues();
  }

  /**
   * Create a facet entity object (if not exist).
   *
   * @param array $values
   *   An array of values to create facet.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addFacet(array $values) {
    if (!isset($values['id'])) {
      $this->logger->error('Facet id `@facet` not defined.', [
        '@facet' => $values['id'],
      ]);
      return;
    }
    // Load the facet (if it exists).
    $facet = $this->facetEntity->load($values['id']);

    if ($facet instanceof FacetInterface) {
      return;
    }
    $values = $this->mergeValues($values);
    $this->facetEntity->create($values)->save();
    $this->logger->info('Created new facet with id: `@id`.', ['@id' => $values['id']]);
  }

}
