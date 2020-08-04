<?php

namespace Drupal\acquia_cms_search\Plugin\facets_pretty_paths\coder;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets_pretty_paths\Plugin\facets_pretty_paths\coder\TaxonomyTermCoder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A pretty path coder that uses only a taxonomy term name.
 *
 * The parent class will normally encode these facets as "term_name-term_id".
 * This coder, on the other hand, will return only the term name. When decoding,
 * it will try to determine the term's vocabulary, and then query for the term
 * by its name in an effort to determine the term ID.
 *
 * If neither the vocabularly ID nor term ID can be determined during decoding,
 * this class will let the parent class take over.
 *
 * @todo Ensure that transliterated term names are handled correctly.
 *
 * @FacetsPrettyPathsCoder(
 *   id = "taxonomy_term_name_coder",
 *   label = @Translation("Taxonomy term name"),
 *   description = @Translation("Use term name, e.g. /color/<strong>blue</strong>"),
 * )
 */
final class TaxonomyTermNameCoder extends TaxonomyTermCoder implements ContainerFactoryPluginInterface {

  /**
   * The taxonomy term entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $termStorage;

  /**
   * The facet being encoded or decoded.
   *
   * @var \Drupal\facets\FacetInterface
   */
  private $facet;

  /**
   * TaxonomyTermNameCoder constructor.
   *
   * @param array $configuration
   *   The plugin configuration. Expected to contain a 'facet' key containing
   *   the current facet.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $term_storage
   *   The taxonomy term entity storage handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $term_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->termStorage = $term_storage;
    $this->facet = $configuration['facet'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('taxonomy_term')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function encode($id) {
    return preg_replace('/\-[0-9]+$/', NULL, parent::encode($id));
  }

  /**
   * {@inheritdoc}
   */
  public function decode($alias) {
    return $this->getTerm($alias) ?: parent::decode($alias);
  }

  /**
   * Tries to load the term ID from its pretty-path name.
   *
   * @param string $alias
   *   The pretty-path version of the term name.
   *
   * @return int|null
   *   The term ID, or NULL if it could not be determined.
   */
  private function getTerm(string $alias) : ?int {
    $vocabulary_id = $this->getVocabulary();

    if ($vocabulary_id) {
      $terms = $this->termStorage->getQuery()
        ->condition('vid', $vocabulary_id)
        ->condition('name', [
          $alias,
          str_replace('-', ' ', $alias),
        ], 'IN')
        ->execute();

      return $terms ? (int) reset($terms) : NULL;
    }
    return NULL;
  }

  /**
   * Tries to get the vocabulary ID for the current facet.
   *
   * @return string|null
   *   The vocabulary ID, or NULL if it could not be determined.
   */
  private function getVocabulary() : ?string {
    $field_definition = $this->getFieldDefinition();

    if ($field_definition->getType() === 'entity_reference' && $field_definition->getFieldStorageDefinition()->getSetting('target_type') === 'taxonomy_term') {
      $handler_settings = $field_definition->getSetting('handler_settings');

      if ($handler_settings['target_bundles']) {
        return reset($handler_settings['target_bundles']);
      }
    }
    return NULL;
  }

  /**
   * Tries to return the field definition on which the current facet is based.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *   The field definition, or NULL if it is unavailable.
   */
  private function getFieldDefinition() : ?FieldDefinitionInterface {
    $facet = $this->facet;

    $data_definition = $facet->getFacetSource()
      ->getDataDefinition($facet->getFieldIdentifier());

    if ($data_definition instanceof FieldItemDataDefinitionInterface) {
      return $data_definition->getFieldDefinition();
    }
    return NULL;
  }

}
