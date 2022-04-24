<?php

namespace Drupal\acquia_cms_headless\Controller;

use Drupal\acquia_cms_headless\Service\RobustApiService;
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
   * Provides Robust API Service.
   *
   * @var \Drupal\acquia_cms_headless\Service\RobustApiService
   */
  protected $robustApiService;

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
   * The site path.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * {@inheritdoc}
   */
  public function __construct(RobustApiService $robustApiService, MessengerInterface $messenger, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, string $site_path) {
    $this->robustApiService = $robustApiService;
    $this->messenger = $messenger;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('acquia_cms_headless.robustapi'),
      $container->get('messenger'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->getParameter('site.path')
    );
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
    // Generate a new secret key.
    $secret = $this->robustApiService->createHeadlessSecret();
    // Get the consumer id from the route.
    $cid = $this->routeMatch->getParameter('consumer')->id();
    // Get the Consumer name.
    $consumer_name = $this->routeMatch->getParameter('consumer')->label();
    // Load the consumer.
    $consumer = $this->entityTypeManager->getStorage('consumer')->load($cid);
    // Apply the new secret to the consumer.
    $consumer->secret = $secret;
    // Update the consumer.
    $consumer->save();

    // Render the content.
    $build['content'] = [
      '#markup' => $this->t(
        'A secret has been generated for the <strong>@name</strong> consumer: <h2>@secret</h2> Please store this value as it cannot be retrieved.'
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
    $destination = $this->robustApiService->dashboardDestination();
    // Create a link to the the oauth settings page.
    $oauth_link = Url::fromRoute('oauth2_token.settings', [], $destination)->toString();

    // Generate the Oauth Keys.
    try {
      $this->robustApiService->generateOauthKeys();
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

}
