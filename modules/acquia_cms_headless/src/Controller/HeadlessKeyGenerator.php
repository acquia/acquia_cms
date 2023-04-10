<?php

namespace Drupal\acquia_cms_headless\Controller;

use Drupal\acquia_cms_headless\Service\StarterkitNextjsService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\simple_oauth\Service\Exception\ExtensionNotLoadedException;
use Drupal\simple_oauth\Service\Exception\FilesystemValidationException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Controller for generating consumer keys and secrets.
 */
class HeadlessKeyGenerator extends ControllerBase {

  use stringtranslationtrait;

  /**
   * Provides Starter Kit Next.js Service.
   *
   * @var \Drupal\acquia_cms_headless\Service\StarterkitNextjsService
   */
  protected $starterKitNextjsService;

  /**
   * Include the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Gets route parameters.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The site path.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * {@inheritdoc}
   */
  public function __construct(StarterkitNextjsService $starterKitNextjsService, MessengerInterface $messenger, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, string $site_path) {
    $this->starterKitNextjsService = $starterKitNextjsService;
    $this->messenger = $messenger;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->sitePath = $site_path;
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
      $container->get('acquia_cms_headless.starterkit_nextjs'),
      $container->get('messenger'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->getParameter('site.path')
    );
  }

  /**
   * Returns a render-able array for a test page.
   *
   * @return array
   *   Returns a build array to display on the custom route.
   */
  public function generateApiKeys(): array {
    // Initialize the build array.
    $build = [];
    // Call the site path service.
    $site_path = $this->sitePath;
    // Separate the site path array.
    $site_path = explode('/', $site_path);
    // Set the path variable.
    $key_path = "/oauth_keys/$site_path[0]/$site_path[1]";
    // Call the dashboard destination service.
    $destination = $this->starterKitNextjsService->dashboardDestination();
    // Create a link to the the oauth settings page.
    $oauth_link = Url::fromRoute('oauth2_token.settings', [], $destination)->toString();

    // Generate the Oauth Keys.
    try {
      $this->starterKitNextjsService->generateOauthKeys();
    }
    catch (ExtensionNotLoadedException | FilesystemValidationException $e) {
      $this->messenger->addError($e);
    }

    // Render the content.
    $build['content'] = [
      '#markup' => $this->t(
        '<p>A new Public and Private API Key pair has been generated and
        stored in code, outside of your site\'s docroot at: <strong>@key_path</strong>. For more details, visit the <a href="@oauth_link">Oauth Settings</a> page.</p>'
      ),
      '#attached' => [
        'placeholders' => [
          '@key_path' => ['#markup' => $key_path],
          '@oauth_link' => ['#markup' => $oauth_link],
        ],
      ],
      '#prefix' => '<div class="headless-dashboard-modal">',
      '#suffix' => '</div>',
    ];

    return $build;
  }

  /**
   * A custom route that generates and applies a new consumer secret.
   *
   * @return array
   *   Returns a build array to display on the custom route.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function generateConsumerSecret(): array {
    // Initialize the build array.
    $build = [];
    // $entity type id.
    $entity_type = 'consumer';
    // Generate a new secret key.
    $secret = $this->starterKitNextjsService->createHeadlessSecret();
    // Get the consumer id from the route.
    $cid = $this->routeMatch->getParameter($entity_type)->id();
    // Get the Consumer name.
    $consumer_name = $this->routeMatch->getParameter($entity_type)->label();
    // Load the consumer.
    /** @var \Drupal\consumers\Entity\Consumer $consumer */
    $consumer = $this->entityTypeManager->getStorage($entity_type)->load($cid);
    // Apply the new secret to the consumer.
    $consumer->set('secret', $secret);
    // Update the consumer.
    $consumer->save();

    // Render the content.
    $build['content'] = [
      '#markup' => $this->t(
        'A secret has been generated for the <strong>@name</strong> consumer: <h2>@secret</h2> Update this value in your .env file.'
      ),
      '#attached' => [
        'placeholders' => [
          '@name' => ['#markup' => $consumer_name],
          '@secret' => ['#markup' => $secret],
        ],
      ],
      '#prefix' => '<div class="headless-dashboard-modal">',
      '#suffix' => '</div>',
    ];

    return $build;
  }

  /**
   * A custom route that generates and applies a new preview secret.
   *
   * @return array
   *   Returns a build array to display on the custom route.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException|\Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Core\Entity\EntityMalformedException|\Drupal\Core\Entity\EntityStorageException
   */
  public function generatePreviewSecret(): array {
    // Initialize the build array.
    $build = [];
    // $entity type id.
    $entity_type = 'next_site';
    // Get the config service.
    $config = $this->configFactory;
    // Call the dashboard destination service.
    $destination = $this->starterKitNextjsService->dashboardDestination();
    // Generate a new secret key.
    $secret = $this->starterKitNextjsService->createHeadlessSecret();
    // Get the next.js site id from the route.
    $next_id = $this->routeMatch->getParameter($entity_type)->id();
    // Get the next.js site name.
    $next_site_name = $this->routeMatch->getParameter($entity_type)->label();
    // Load the next.js site.
    $next_site = $this->entityTypeManager->getStorage($entity_type)->load($next_id);
    // Apply the updated preview secret to the next.js site config.
    $config
      ->getEditable("next.next_site.$next_id")
      ->set('preview_secret', $secret)
      ->save();

    // Build a link to the edit form.
    $next_site_link = Url::fromRoute($next_site->toUrl()->getRouteName(), [$entity_type => $next_id], $destination)->toString();

    // Render the content.
    $build['content'] = [
      '#markup' => $this->t(
        '<p>A preview secret has been generated for the
        <strong>@name</strong> next.js site: <h2>@secret</h2> This value can
        also be retrieved from the <a href="@link">next.js site</a> entity.</p>'
      ),
      '#attached' => [
        'placeholders' => [
          '@name' => ['#markup' => $next_site_name],
          '@secret' => ['#markup' => $secret],
          '@link' => ['#markup' => $next_site_link],
        ],
      ],
      '#prefix' => '<div class="headless-dashboard-modal">',
      '#suffix' => '</div>',
    ];

    return $build;
  }

}
