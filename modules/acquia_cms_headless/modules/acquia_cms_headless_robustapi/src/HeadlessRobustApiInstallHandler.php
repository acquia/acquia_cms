<?php

namespace Drupal\acquia_cms_headless_robustapi;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\consumers\Entity\Consumer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Install handlers for Headless Robust API.
 *
 * Provides a series of helper functions for setting up the Robust API and
 * the various entity types used by it.
 */
class HeadlessRobustApiInstallHandler {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Include the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

  /**
   * Create a new consumer for Headless.
   */
  public function createHeadlessConsumer() {
    try {
      $user = $this->getHeadlessUserId();
      if (!empty($user)) {
        Consumer::create([
          'label' => 'Headless Site 1',
          'description' => 'This client is provided by the acquia_cms_headless_robustapi module.',
          'is_default' => TRUE,
          'redirect' => 'http://localhost:3000',
          'roles' => 'headless',
          'user_id' => $user,
        ])->save();
      }
    }
    catch (EntityStorageException | InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->messenger->addError($e);
    }
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

    if (!$next_site_id) {
      $next_object->create([
        'id' => 'headless',
        'label' => 'Headless Site 1',
        'base_url' => 'http://localhost:3000/',
        'preview_url' => '/api/preview',
        // @todo do something with the preview secret.
        'preview_secret' => '',
      ])->save();
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
   * Delete the headless roll.
   */
  public function deleteHeadlessRole() {
    $config = $this->configFactory->getEditable('user.role.headless');
    $config->delete();
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
