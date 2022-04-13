<?php

namespace Drupal\acquia_cms_headless_robustapi;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\consumers\Entity\Consumer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Password\DefaultPasswordGenerator;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\simple_oauth\Service\KeyGeneratorService;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Install handlers for Headless Robust API.
 *
 * Provides a series of helper functions for setting up the Robust API and
 * the various entity types used by it.
 */
class HeadlessRobustApiInstallHandler {
  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Default Password Generator.
   *
   * @var \Drupal\Core\Password\DefaultPasswordGenerator
   */
  protected $defaultPasswordGenerator;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Simple OAUTH Key Generator service.
   *
   * @var \Drupal\simple_oauth\Service\KeyGeneratorService
   */
  protected $keyGeneratorService;

  /**
   * Include the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, DefaultPasswordGenerator $defaultPasswordGenerator, EntityTypeManagerInterface $entity_type_manager, KeyGeneratorService $key_generator_service, MessengerInterface $messenger) {
    $this->configFactory = $config_factory;
    $this->defaultPasswordGenerator = $defaultPasswordGenerator;
    $this->entityTypeManager = $entity_type_manager;
    $this->keyGeneratorService = $key_generator_service;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('password_generator'),
      $container->get('entity_type.manager'),
      $container->get('simple_oauth.key.generator'),
      $container->get('messenger')
    );
  }

  /**
   * Create a new consumer for Headless.
   */
  public function createHeadlessConsumer() {
    try {
      $secret = $this->createHeadlessSecret();
      $user = $this->getHeadlessUserId();
      if (!empty($user)) {
        $consumer = Consumer::create();
        $consumer->set('label', 'Headless Site 1');
        $consumer->set('secret', $secret);
        $consumer->set('description', 'This client is provided by the acquia_cms_headless_robustapi module.');
        $consumer->set('is_default', TRUE);
        $consumer->set('redirect', 'http://localhost:3000');
        $consumer->set('roles', 'headless');
        $consumer->set('user_id', $user);
        $consumer->save();

        // Provide a one time message to the admin so they can save the
        // consumer secret before it's stored in a hash key.
        $this->messenger->addStatus($this->t('Your Oauth consumer secret has been generated for you and is: <h2>@secret</h2> Please store this value as it cannot be retrieved. Alternatively you can generate a new secret via the <a href="@link">Consumer UI</a>', [
          '@secret' => $secret,
          '@link' => Url::fromRoute('consumers.admin.config.services'),
        ]));
      }
    }
    catch (EntityStorageException | InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->messenger->addError($e);
    }
  }

  /**
   * Creates a Headless secret key.
   *
   * @return string
   *   Returns a 21 character secret key string.
   */
  public function createHeadlessSecret(): string {
    return $this->defaultPasswordGenerator->generate(21);
  }

  /**
   * Creates a new headless Next.js site entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createHeadlessSite() {
    $next_site_id = $this->getHeadlessSiteId();
    $next_object = $this->entityTypeManager->getStorage('next_site');
    $preview_secret = $this->createHeadlessSecret();
    if (!$next_site_id) {
      $next_object->create([
        'id' => 'headless',
        'label' => 'Headless Site 1',
        'base_url' => 'http://localhost:3000/',
        'preview_url' => 'http://localhost:3000/api/preview/',
        'preview_secret' => $preview_secret,
      ])->save();
    }
  }

  /**
   * Creates Next.js headless site entity types.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function createHeadlessSiteEntities() {
    // Init the sites object variable.
    $sitesObject = [];

    // Get entity storage for our content types.
    $types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $sites = $this->entityTypeManager->getStorage('next_site')->loadMultiple();

    // Check to see if any next js site entities are available.
    if (!empty($sites)) {
      // Iterate through each next js site so that we can build a new sites
      // array to pass into our next_entity_type_config.confuration.sites array.
      foreach ($sites as $site) {
        // Add to the sites object where both the key and the value is the next
        // js site entity id.
        $sitesObject[$site->id()] = $site->id();
      }
    }
    // Check to see if any content types are available.
    if (!empty($types)) {
      // Iterate through each content type so that we can create the next entity
      // types.
      foreach ($types as $type) {
        // Set a variable for our content type machinename.
        $nodeTypeId = $type->id();
        // Get the storage for the Next entity type.
        $nextEntityObject = $this->entityTypeManager->getStorage('next_entity_type_config');
        // Create a Next.js Entity type for each content type that's available.
        $nextEntityObject->create([
          'id' => "node.$nodeTypeId",
          'site_resolver' => 'site_selector',
          'configuration' => [
            'sites' => $sitesObject,
          ],
        ])->save();
      }
    }
  }

  /**
   * Creates a new user for Headless Role.
   */
  public function createHeadlessUser() {
    try {
      $user = $this->getHeadlessUserId();
      if (empty($user)) {
        $language = 'en';
        $email = 'no-reply@example.com';
        $user_name = 'Headless';

        $user = User::create();
        $user->enforceIsNew();
        $user->setPassword('Password');
        $user->setEmail($email);
        $user->setUsername($user_name);
        $user->set("langcode", $language);
        $user->set('timezone', '');
        $user->set("init", $email);
        $user->set("preferred_langcode", $language);
        $user->activate();
        $user->addRole('headless');
        $user->save();
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException $e) {
      $this->messenger->addError($e);
    }
  }

  /**
   * Delete all consumers expect for the Default consumer.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteHeadlessConsumers() {
    $consumerStorage = $this->entityTypeManager->getStorage('consumer');
    $consumerQuery = $consumerStorage->getQuery();
    $cids = $consumerQuery
      ->condition('label', 'Default Consumer', 'NOT IN')
      ->execute();

    if (!empty($cids)) {
      $consumers = $consumerStorage->loadMultiple($cids);

      foreach ($consumers as $consumer) {
        $consumer->delete();
      }
    }
  }

  /**
   * Delete the headless role.
   */
  public function deleteHeadlessRole() {
    $config = $this->configFactory->getEditable('user.role.headless');
    $config->delete();
  }

  /**
   * Delete Next js sites and site entity types.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function deleteHeadlessSites() {
    $sites = $this->entityTypeManager->getStorage('next_site')->loadMultiple();
    $entities = $this->entityTypeManager->getStorage('next_entity_type_config')->loadMultiple();

    if (!empty($sites)) {
      foreach ($sites as $site) {
        $site->delete();
      }
    }

    if (!empty($sites)) {
      foreach ($entities as $entity) {
        $entity->delete();
      }
    }
  }

  /**
   * Deletes the Headless User.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteHeadlessUser() {
    $userStorage = $this->entityTypeManager->getStorage('user');
    $user = $this->getHeadlessUserId();

    if ($user) {
      $uid = $userStorage->load($user);
      $uid->delete();
    }
  }

  /**
   * Generates OAuth keys and Updates OAuth Settings.
   *
   * @throws \Drupal\simple_oauth\Service\Exception\FilesystemValidationException
   * @throws \Drupal\simple_oauth\Service\Exception\ExtensionNotLoadedException
   */
  public function generateOauthKeys() {
    // Generate a public and private oauth key.
    // @todo Revisit where these key files are stored.
    $dir = '../config/oauth';
    $this->keyGeneratorService->generateKeys($dir);

    // Update oauth settings.
    $oauthObject = $this->configFactory->getEditable('simple_oauth.settings');
    $oauthObject
      ->set('public_key', "$dir/public.key")
      ->set('private_key', "$dir/private.key")
      ->save();
  }

  /**
   * Get User ID for Headless user.
   *
   * @return int|string|null
   *   Returns a user id or a NULL value.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getHeadlessUserId() {
    $userStorage = $this->entityTypeManager->getStorage('user');
    $query = $userStorage->getQuery();
    $uids = $query
      ->condition('name', 'Headless')
      ->range(0, 1)
      ->execute();
    $uid = array_keys($uids);

    return !empty($uid) ? $userStorage->load($uid[0])->id() : NULL;
  }

  /**
   * Check to see if our default next.js site exists.
   *
   * @return bool
   *   Returns a True/False value.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getHeadlessSiteId() {
    $next_site = $this->entityTypeManager->getStorage('next_site')->load('headless');

    return !empty($next_site);
  }

  /**
   * Updates the "Is Default" status from the Default Consumer.
   *
   * @param bool $isDefault
   *   Expects a true or false value with FALSE being the default.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function updateDefaultConsumer(bool $isDefault = FALSE) {
    $consumerStorage = $this->entityTypeManager->getStorage('consumer');
    $consumerQuery = $consumerStorage->getQuery();

    // Find the Default Consumer entity.
    $cids = $consumerQuery
      ->condition('label', "Default Consumer")
      ->execute();
    $cid = array_keys($cids);

    // If it exists, update the "is default" status based on a true/false value
    // that is passed to the $isDefault var.
    if (!empty($cid)) {
      $consumer = $consumerStorage->load($cid[0]);
      $consumer
        ->set('is_default', $isDefault)
        ->save();
    }
  }

}
