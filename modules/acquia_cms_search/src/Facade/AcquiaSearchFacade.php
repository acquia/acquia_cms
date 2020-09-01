<?php

namespace Drupal\acquia_cms_search\Facade;

use Drupal\acquia_search_solr\Helper\Storage as AcquiaSearch;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade for integrating with the Acquia Search Solr module.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class AcquiaSearchFacade implements ContainerInjectionInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The search index entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $indexStorage;

  /**
   * The search server entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $serverStorage;

  /**
   * AcquiaSearchFacade constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $index_storage
   *   The search index entity storage handler.
   * @param \Drupal\Core\Entity\EntityStorageInterface $server_storage
   *   The search server entity storage handler.
   */
  public function __construct(EntityStorageInterface $index_storage, EntityStorageInterface $server_storage) {
    $this->indexStorage = $index_storage;
    $this->serverStorage = $server_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');

    return new static(
      $entity_type_manager->getStorage('search_api_index'),
      $entity_type_manager->getStorage('search_api_server')
    );
  }

  /**
   * Submit handler for the Acquia Search settings form.
   */
  public static function submitSettingsForm() : void {
    if (static::isConfigured()) {
      \Drupal::classResolver(static::class)->switchIndexToSolrServer();
    }
  }

  /**
   * Checks if Acquia Search has been configured.
   *
   * @return bool
   *   TRUE if Acquia Search has been configured satisfactorily, otherwise
   *   FALSE.
   */
  private static function isConfigured() : bool {
    return (
      (bool) AcquiaSearch::getIdentifier() &&
      (bool) AcquiaSearch::getApiKey() &&
      (bool) AcquiaSearch::getApiHost() &&
      (bool) AcquiaSearch::getUuid()
    );
  }

  /**
   * Configures the content index to use the Acquia Search server.
   */
  private function switchIndexToSolrServer() : void {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->indexStorage->load('content');
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $this->serverStorage->load('acquia_search_solr_search_api_solr_server');

    if ($index && $server && $index->getServerId() === 'database') {
      $index->setServer($server)->reindex();
      $this->indexStorage->save($index);

      $message = $this->t('The %index search index is now using the %server server. All content will be reindexed.', [
        '%index' => $index->label(),
        '%server' => $server->label(),
      ]);
      $this->messenger()->addStatus($message);
    }
  }

}
