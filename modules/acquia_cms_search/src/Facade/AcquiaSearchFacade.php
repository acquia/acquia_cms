<?php

namespace Drupal\acquia_cms_search\Facade;

use Drupal\acquia_search_solr\Helper\Storage as AcquiaSearch;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Solarium\Exception\ExceptionInterface as SolariumException;
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
   * The view entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $viewStorage;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * AcquiaSearchFacade constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $index_storage
   *   The search index entity storage handler.
   * @param \Drupal\Core\Entity\EntityStorageInterface $server_storage
   *   The search server entity storage handler.
   * @param \Drupal\Core\Entity\EntityStorageInterface $view_storage
   *   The view entity storage handler.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(EntityStorageInterface $index_storage, EntityStorageInterface $server_storage, EntityStorageInterface $view_storage, LoggerChannelInterface $logger) {
    $this->indexStorage = $index_storage;
    $this->serverStorage = $server_storage;
    $this->viewStorage = $view_storage;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');

    return new static(
      $entity_type_manager->getStorage('search_api_index'),
      $entity_type_manager->getStorage('search_api_server'),
      $entity_type_manager->getStorage('view'),
      $container->get('logger.factory')->get('acquia_cms_search')
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
  protected function switchIndexToSolrServer() : void {
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

    // If two indexes are being stored on the same Solr core, Search API might
    // complain mildly about it. Also, it's possible that things might not work
    // as well as they should. To get past that, load the index that ships
    // with Acquia Search Solr and unlink it from the Solr server.
    $index = $this->indexStorage->load('acquia_search_solr_search_api_solr_index');
    if ($index && $index->getServerId() === $server->id()) {
      $index->setServer(NULL);

      try {
        $this->indexStorage->save($index);
      }
      catch (SolariumException $e) {
        // Look...we're just trying to unlink the index from the server, man.
        // Solarium might throw an error if Acquia Search hasn't been properly
        // set up yet, but that's obviously harmless. Then again, we might have
        // also caught a not-so-harmless error condition, so let's just log it
        // and hope for the best.
        $this->logger->warning('An error occurred while unlinking the %server server from the %index index: @error', [
          '%server' => $server->label(),
          '%index' => $index->label(),
          '@error' => $e->getMessage(),
        ]);
      }

      // Disable any views that are using the now-disabled index.
      $views = $this->viewStorage->loadByProperties([
        'status' => TRUE,
        'base_table' => 'search_api_index_' . $index->id(),
      ]);
      /** @var \Drupal\views\ViewEntityInterface $view */
      foreach ($views as $view) {
        $view->disable();
        $this->viewStorage->save($view);
      }
    }
  }

}
