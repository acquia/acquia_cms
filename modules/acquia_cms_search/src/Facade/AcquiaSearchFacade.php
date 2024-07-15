<?php

namespace Drupal\acquia_cms_search\Facade;

use Drupal\acquia_connector\Subscription;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\State\StateInterface;
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
   * The config factory service object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /**
   * The acquia subscription service.
   *
   * @var \Drupal\acquia_connector\Subscription
   */
  private $acquiaSubscription;

  /**
   * AcquiaSearchFacade constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   * @param \Drupal\acquia_connector\Subscription $subscription
   *   The acquia subscription service object.
   * @param \Drupal\Core\Entity\EntityStorageInterface $index_storage
   *   The search index entity storage handler.
   * @param \Drupal\Core\Entity\EntityStorageInterface $server_storage
   *   The search server entity storage handler.
   * @param \Drupal\Core\Entity\EntityStorageInterface $view_storage
   *   The view entity storage handler.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    StateInterface $state,
    LoggerChannelInterface $logger,
    Subscription $subscription,
    EntityStorageInterface $index_storage,
    EntityStorageInterface $server_storage,
    EntityStorageInterface $view_storage,
  ) {
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->logger = $logger;
    $this->indexStorage = $index_storage;
    $this->serverStorage = $server_storage;
    $this->viewStorage = $view_storage;
    $this->acquiaSubscription = $subscription;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');

    return new static(
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('logger.factory')->get('acquia_cms_search'),
      $container->get('acquia_connector.subscription'),
      $entity_type_manager->getStorage('search_api_index'),
      $entity_type_manager->getStorage('search_api_server'),
      $entity_type_manager->getStorage('view')
    );
  }

  /**
   * Submit handler for the Acquia Search settings form.
   */
  public static function submitSettingsForm(): void {
    $class_resolver = \Drupal::classResolver(static::class);
    if ($class_resolver->isConfigured()) {
      $class_resolver->ensureActiveSubscription();
      $class_resolver->switchIndexToSolrServer();
    }
  }

  /**
   * Checks if Acquia Search has been configured.
   *
   * @return bool
   *   TRUE if Acquia Search has been configured satisfactorily, otherwise
   *   FALSE.
   */
  protected function isConfigured(): bool {
    $api_host = $this->configFactory->getEditable('acquia_search.settings')->get('api_host');
    $api_key = $this->state->get('acquia_connector.key');
    $identifier = $this->state->get('acquia_connector.identifier');
    $uuid = $this->state->get('acquia_connector.application_uuid');
    if ($api_host && $api_key && $identifier && $uuid) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Configures the content index to use the Acquia Search server.
   */
  protected function switchIndexToSolrServer(): void {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->indexStorage->load('content');
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $this->serverStorage->load('acquia_search_server');
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
    // Database server is now idle so disbale the database server.
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->indexStorage->load('acquia_search_index');
    if ($index && $index->getServerId() === $server->id()) {
      $index->disable()->setServer(NULL);
      $this->indexStorage->save($index);

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

    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $databaseServer */
    $databaseServer = $this->serverStorage->load('database');
    try {
      $databaseServer->disable()->save();
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

  }

  /**
   * Make sure acquia subscription is active.
   */
  protected function ensureActiveSubscription(): void {
    if (!$this->acquiaSubscription->isActive()) {
      $this->acquiaSubscription->populateSettings();
      $this->acquiaSubscription->getSubscription(TRUE);
    }
  }

}
