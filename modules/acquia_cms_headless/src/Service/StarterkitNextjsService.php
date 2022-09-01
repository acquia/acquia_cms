<?php

namespace Drupal\acquia_cms_headless\Service;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\consumers\Entity\Consumer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Password\DefaultPasswordGenerator;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\next\Entity\NextSite;
use Drupal\simple_oauth\Service\Exception\FilesystemValidationException;
use Drupal\simple_oauth\Service\KeyGeneratorService;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A service for the initialization of the Headless Next.js starter kit.
 *
 * Provides a series of helper functions for setting up the Next.js starter kit
 * and the various entity types used by it.
 */
class StarterkitNextjsService {
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
   * The site path.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * The file system interface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Generated consumer secret.
   *
   * @var string
   */
  protected $consumerSecret;

  /**
   * Injects various services used in the Next.js starter kit Service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Gets config data.
   * @param \Drupal\Core\Password\DefaultPasswordGenerator $defaultPasswordGenerator
   *   Calls the core password generator in order to create secret keys.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Used to obtain data from various entity types.
   * @param \Drupal\simple_oauth\Service\KeyGeneratorService $key_generator_service
   *   Allows us to programmatically generate public and private oauth keys.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Allows us to pass messages to the user.
   * @param string $site_path
   *   Gets the site path, useful in cases of multi-site arrangements.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   Lets us create a directory.
   * @param \Drupal\Core\Http\RequestStack $request_stack
   *   The current request.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DefaultPasswordGenerator $defaultPasswordGenerator, EntityTypeManagerInterface $entity_type_manager, KeyGeneratorService $key_generator_service, MessengerInterface $messenger, string $site_path, FileSystemInterface $file_system, RequestStack $request_stack) {
    $this->configFactory = $config_factory;
    $this->defaultPasswordGenerator = $defaultPasswordGenerator;
    $this->entityTypeManager = $entity_type_manager;
    $this->keyGeneratorService = $key_generator_service;
    $this->messenger = $messenger;
    $this->sitePath = $site_path;
    $this->fileSystem = $file_system;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Sets the container for our injected services.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Uses the container interface.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('password_generator'),
      $container->get('entity_type.manager'),
      $container->get('simple_oauth.key.generator'),
      $container->get('messenger'),
      $container->getParameter('site.path'),
      $container->get('file_system'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Create a new consumer for Headless.
   *
   * @param array $consumer_data
   *   The consumer data.
   */
  public function createHeadlessConsumer(array $consumer_data) {
    try {
      $consumers = $this->getHeadlessConsumerData($consumer_data['site-name']);
      $user = $this->getHeadlessUserData();

      if (!empty($user) && empty($consumers)) {
        $this->consumerSecret = $this->createHeadlessSecret();
        $consumer = Consumer::create();
        $consumer->set('label', $consumer_data['site-name']);
        $consumer->set('secret', $this->consumerSecret);
        $consumer->set('description', 'This client is provided by the acquia_cms_headless module.');
        $consumer->set('is_default', TRUE);
        $consumer->set('redirect', $consumer_data['site-url']);
        $consumer->set('roles', 'headless');
        $consumer->set('user_id', $user->id());
        $consumer->save();
      }
    }
    catch (EntityStorageException | InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->messenger->addError($e);
    }
  }

  /**
   * Set consumer secret.
   *
   * @param string $secret
   *   The consumer secret.
   */
  public function setConsumerSecret(string $secret) {
    $this->consumerSecret = $secret;
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
   * @param string $site_id
   *   The site id.
   * @param array $site_data
   *   The site data.
   *
   * @return \Drupal\next\Entity\NextSite
   *   The Next.js site object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function createHeadlessSite(string $site_id, array $site_data) {
    $next_site = $this->getHeadlessSite($site_id);
    $next_object = $this->entityTypeManager->getStorage('next_site');
    $preview_secret = $this->createHeadlessSecret();
    if (!$next_site) {
      $next_object->create([
        'id' => $site_id,
        'label' => $site_data['site-name'],
        'base_url' => $site_data['site-url'],
        'preview_url' => $site_data['site-url'] . '/api/preview/',
        'preview_secret' => $preview_secret,
      ])->save();
    }
    return $this->getHeadlessSite($site_id);
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

      // Check to see if any content types are available.
      if (!empty($types)) {
        // Iterate through each content type so that we can create the next
        // entity types.
        foreach ($types as $type) {
          // Set a variable for our content type machinename.
          $nodeTypeId = $type->id();
          // Get the storage for the Next entity type.
          $nextEntityObject = $this->entityTypeManager->getStorage('next_entity_type_config');
          // Check to see if the next.js entity type already exists.
          if (empty($nextEntityObject->load("node.$nodeTypeId"))) {
            // Create a Next.js Entity type for each content type that's
            // available.
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
    }
  }

  /**
   * Creates a new user for Headless Role.
   */
  public function createHeadlessUser() {
    try {
      $user = $this->getHeadlessUserData();
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
    $user = $this->getHeadlessUserData();

    if ($user) {
      $uid = $userStorage->load($user->id());
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

    // Get oauth_keys_directory path from settings.php if available.
    $dir = Settings::get('oauth_keys_directory');
    if (!$dir) {
      $dir = $this->getDefaultOauthKeysDirectory();
    }

    $this->generateOauthKeysDirectory($dir);
    // Generate a public and private oauth key.
    $this->keyGeneratorService->generateKeys($dir);

    // Update oauth settings.
    $oauthObject = $this->configFactory->getEditable('simple_oauth.settings');
    $oauthObject
      ->set('public_key', "$dir/public.key")
      ->set('private_key', "$dir/private.key")
      ->save();
  }

  /**
   * Get default directory path based on environment.
   *
   * @return string
   *   The default directory path.
   */
  private function getDefaultOauthKeysDirectory(): string {
    // Call the site path service.
    $site_path = $this->sitePath;
    // Separate the site path array.
    $site_path = explode('/', $site_path);

    // Set the base path of the oauth_keys directory.
    $default_dir = "../oauth_keys/$site_path[0]/$site_path[1]";

    // Build default oauth_keys dir path for acquia cloud environment.
    if (AcquiaDrupalEnvironmentDetector::isAhEnv()) {
      $site_group = AcquiaDrupalEnvironmentDetector::getAhGroup();
      $env = AcquiaDrupalEnvironmentDetector::getAhEnv();
      $default_dir = "/mnt/gfs/$site_group.$env/nobackup/oauth_keys/$site_path[0]/$site_path[1]";
    }
    return $default_dir;
  }

  /**
   * Generates & returns the OAuth keys directory path.
   *
   * @param string $dir
   *   The oauth directory.
   *
   * @throws \Drupal\simple_oauth\Service\Exception\FilesystemValidationException
   */
  public function generateOauthKeysDirectory(string $dir) {
    // Create the directory if it doesn't already exist.
    if (!is_dir($dir)) {
      $created = $this->fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
      if (!$created) {
        throw new FilesystemValidationException(
          strtr("The specified directory '@path' is not properly configured. This may be caused by a problem with directory permissions.", ['@path' => $dir])
        );
      }
    }
    if (is_dir($dir) && !is_writable($dir)) {
      throw new FilesystemValidationException(
        strtr('The specified directory "@path" is not writable.', ['@path' => $dir])
      );
    }
  }

  /**
   * A function that obtains our init headless consumer data.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Returns a consumer object or a NULL response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getHeadlessConsumerData($site_name) {
    $consumerStorage = $this->entityTypeManager->getStorage('consumer');
    $query = $consumerStorage->getQuery();
    $cids = $query
      ->condition('label', $site_name)
      ->range(0, 1)
      ->execute();
    $cid = array_keys($cids);

    return !empty($cid) ? $consumerStorage->load($cid[0]) : NULL;
  }

  /**
   * Get consumer based on redirect uri.
   *
   * @param string $redirect_uri
   *   The redirect url.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Returns a consumer object or a NULL response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getHeadlessConsumerDataByUri(string $redirect_uri) {
    $consumerStorage = $this->entityTypeManager->getStorage('consumer');
    $query = $consumerStorage->getQuery();
    $cids = $query
      ->condition('redirect', $redirect_uri)
      ->range(0, 1)
      ->execute();
    $cid = array_keys($cids);

    return !empty($cid) ? $consumerStorage->load($cid[0]) : NULL;
  }

  /**
   * Get User ID for Headless user.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Returns a data user or a NULL value.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getHeadlessUserData() {
    $userStorage = $this->entityTypeManager->getStorage('user');
    $query = $userStorage->getQuery();
    $uids = $query
      ->condition('name', 'Headless')
      ->range(0, 1)
      ->execute();
    $uid = array_keys($uids);

    return !empty($uid) ? $userStorage->load($uid[0]) : NULL;
  }

  /**
   * Check to see if our default next.js site exists.
   *
   * @param string $site_id
   *   The site id.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The next site object or null.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getHeadlessSite(string $site_id) {
    return $this->entityTypeManager->getStorage('next_site')->load($site_id);
  }

  /**
   * Get next.js site based on url.
   *
   * @param string $base_url
   *   The next.js base url.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The next.js site object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getHeadlessSiteByBaseUrl(string $base_url) {
    $site = $this->entityTypeManager->getStorage('next_site')->loadByProperties(['base_url' => $base_url]);
    return reset($site) ?? NULL;
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

  /**
   * A method that initializes the Next.js starter kit.
   *
   * @param string $site_id
   *   The site id.
   * @param array $site_data
   *   The site data.
   *
   * @return \Drupal\next\Entity\NextSite
   *   The next site object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\simple_oauth\Service\Exception\ExtensionNotLoadedException|FilesystemValidationException
   */
  public function initStarterkitNextjs(string $site_id, array $site_data) {

    // Check to see if Headless user still exists, and if not, recreate it.
    $this->createHeadlessUser();

    // Generate Public & Private OAUTH keys.
    $this->generateOauthKeys();

    // Remove "is default" status from Default Consumer.
    $this->updateDefaultConsumer(FALSE);

    // Create a new Next.js Consumer.
    $this->createHeadlessConsumer($site_data);

    // Create a Next.js Site.
    $site = $this->createHeadlessSite($site_id, $site_data);

    // Create a set of Next.js Entity types based on available Node Types.
    $this->createHeadlessSiteEntities();

    // Add User and Consumer UUIDs to headless config.
    $config = $this->configFactory->getEditable('acquia_cms_headless.settings');
    if (!empty($this->getHeadlessConsumerData($site_data['site-name']))) {
      $config->set('consumer_uuid', $this->getHeadlessConsumerData($site_data['site-name'])->uuid());
    }
    if (!empty($this->getHeadlessUserData())) {
      $config->set('user_uuid', $this->getHeadlessUserData()->uuid());
    }
    // Call this function in case of UI.
    if (PHP_SAPI !== 'cli') {
      $this->displayEnvironmentVariables($site);
    }
    return $site;
  }

  /**
   * Displays the Next.js environment variables for the generated NextSite.
   *
   * @param \Drupal\next\Entity\NextSite $next_site
   *   The NextSite to build environment variables for.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function displayEnvironmentVariables(NextSite $next_site) {
    $this->messenger->addStatus($this->t("Use these environment variables for your Next.js application. Place them in your .env file: <pre class='codesnippet'>@code</pre>", [
      '@code' => $this->getEnvironmentVariablesAsString($next_site),
    ]));

    if (!isset($this->consumerSecret)) {
      $this->messenger->addWarning($this->t("The consumer secret cannot be retrieved. If you do not know this value, you can <a href=':link'>set a new secret</a>.", [
        ':link' => Url::fromRoute('entity.consumer.edit_form',
          [
            'consumer' => $this->getHeadlessConsumerData($next_site->label())->id(),
            ['destination' => Url::createFromRequest($this->request)->toString()],
          ])->toString(),
      ]));
    }
  }

  /**
   * Build the Next.js environment variables for the generated NextSite.
   *
   * @param \Drupal\next\Entity\NextSite $next_site
   *   The NextSite to build environment variables for.
   *
   * @return string
   *   The generated environment variables.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEnvironmentVariablesAsString(NextSite $next_site): string {
    $variables = [
      'NEXT_PUBLIC_DRUPAL_BASE_URL' => $this->request->getSchemeAndHttpHost(),
      'NEXT_IMAGE_DOMAIN' => $this->request->getHost(),
      'DRUPAL_SITE_ID' => $next_site->id(),
      'DRUPAL_FRONT_PAGE' => $this->configFactory->get('system.site')->get('page.front'),
    ];

    if ($secret = $next_site->getPreviewSecret()) {
      $consumer = $this->getHeadlessConsumerData($next_site->label());
      $variables += [
        'DRUPAL_PREVIEW_SECRET' => $secret,
        'DRUPAL_CLIENT_ID' => $consumer->uuid(),
        'DRUPAL_CLIENT_SECRET' => $this->consumerSecret ?? 'insert secret here',
      ];
    }
    $code = '';
    foreach ($variables as $key => $value) {
      $code .= $this->t("@key=@value\n", [
        '@key' => $key,
        '@value' => $value,
      ]);
    }
    return $code;
  }

  /**
   * A method resets the Next.js starter kit to its pre init state.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function resetStarterkitNextjs() {
    $config = $this->configFactory->getEditable('acquia_cms_headless.settings');

    // Remove the headless user if it exists.
    $this->deleteHeadlessUser();

    // Remove the headless user role.
    $this->deleteHeadlessRole();

    // Get all consumer entities other than the Default Consumer.
    $this->deleteHeadlessConsumers();

    // Restore "is default" status from Default Consumer.
    $this->updateDefaultConsumer(TRUE);

    // Remove any next.js sites and site entity types.
    $this->deleteHeadlessSites();

    // Remove consumer and user uuids from headless config.
    $config->set('consumer_uuid', '');
    $config->set('user_uuid', '');
  }

  /**
   * Dashboard destination helper function.
   *
   * @return array[]
   *   Returns a destination query array.
   */
  public function dashboardDestination(): array {
    // Set the destination query array.
    return [
      'query' => [
        'destination' => Url::fromRoute('acquia_cms_headless.dashboard')->toString(),
      ],
    ];
  }

}
