<?php

namespace Drupal\acquia_cms_search\Facade;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\facets\Entity\Facet;
use Drupal\facets\FacetInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade for integrating with Search API.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class FacetFacade implements ContainerInjectionInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a ModuleHandlerInterface object.
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
   * Creates a new facet if not created already.
   *
   * @param string $facet_id
   *   Facet ID.
   * @param string $facet_name
   *   Facet Name.
   * @param string $url_alias
   *   URL Alias of the Facet.
   * @param string $field_identifier
   *   Field identifier fo the facet.
   * @param string $coder
   *   Coder for the facet field.
   * @param string $facet_source_id
   *   Source ID of the Facet.
   */
  public function addFacet(string $facet_id,
  string $facet_name,
  string $url_alias,
  string $field_identifier,
  string $coder,
  string $facet_source_id) {

    // Load the category facet if it exists.
    $facet = Facet::load($facet_id);
    // Create a new facet if the category facet doesn't exists.
    if (empty($facet)) {
      // Programmatically creating the facet.
      $facet = Facet::create([
        'id' => $facet_id,
        'name' => $facet_name,
        'url_alias' => $url_alias,
        'field_identifier' => $field_identifier,
      ]
      );
      // Set category widget & related settings.
      $facet->setWidget('links', [
        'show_numbers' => TRUE,
        'soft_limit' => 0,
        'soft_limit_settings' => [
          'show_less_label' => 'Show less',
          'show_more_label' => 'Show more',
        ],
        'show_reset_links' => 'false',
        'reset_text' => 'Show all',
        'hide_reset_when_no_selection' => 0,
      ]);
      $facet->setWeight(0);

      // Set default empty behaviour & other settings.
      $facet->setEmptyBehavior(['behavior' => 'none']);
      if ($this->moduleHandler->moduleExists('facets_pretty_paths')) {
        $facet->setThirdPartySetting('facets_pretty_paths', 'coder', $coder);
      }
      $facet->setOnlyVisibleWhenFacetSourceIsVisible(TRUE);
      $facet->setQueryOperator('or');
      $facet->setHardLimit(0);
      if (empty($facet->getFacetSourceId())) {
        $facet->setFacetSourceId($facet_source_id);
      }

      // Add Facet processors.
      $this->addFacetProcessor(
        $facet,
       'count_widget_order',
        ['sort' => 30],
        ['sort' => 'DESC']
      );
      $this->addFacetProcessor(
        $facet,
        'display_value_widget_order',
        ['sort' => 40],
        ['sort' => 'ASC']
      );
      $this->addFacetProcessor(
        $facet,
        'active_widget_order',
        ['sort' => 20],
        ['sort' => 'DESC']
      );
      $this->addFacetProcessor(
        $facet,
        'url_processor_handler',
        ['pre_query' => 50, 'build' => 15],
        []
      );
      $this->addFacetProcessor(
        $facet,
        'translate_entity',
        ['build' => 5],
        []
      );

      $facet->save();
    }
  }

  /**
   * Function that adds the processor to the facet.
   *
   * @param \Drupal\facets\Entity\FacetInterface $facet
   *   Facet Object that helps add processors.
   * @param string $processor_id
   *   Processor Name or ID for identification.
   * @param array $weights
   *   Weight setting array.
   * @param array $settings
   *   Facet Processor Settings.
   */
  private function addFacetProcessor(FacetInterface $facet, string $processor_id, array $weights, array $settings) {
    $facet->addProcessor([
      'processor_id' => $processor_id,
      'weights' => $weights,
      'settings' => $settings,
    ]);
  }

}
