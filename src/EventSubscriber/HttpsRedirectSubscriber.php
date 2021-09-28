<?php

namespace Drupal\acquia_cms\EventSubscriber;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event Subscriber KernelEvents::Request event.
 */
class HttpsRedirectSubscriber implements EventSubscriberInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * The instantiated Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * The Request URI Service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * HttpsRedirectSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   A cache backend used to store configuration.
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request URI service to get request URL.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache, RequestStack $request_stack) {
    $this->config = $config_factory;
    $this->cache = $cache;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Code that should be triggered on event specified.
   */
  public function onRequest(RequestEvent $event) {
    // Match the pattern to see if the address is that of a localhost.
    // Avoids execution if host matches or relates to 127.0.0.1 and so on.
    $hostPattern = "/^((https?|http?|ftp)\:\/\/)?(127\.0{0,3}\.0{0,3}.0{0,2}1|localhost)?(:\d+)?$/";
    $host = $this->request->getSchemeAndHttpHost();
    // Get the config value from cache if available.
    $https_status = $this->config->get('acquia_cms.settings')->get('acquia_cms_https');
    // Allow only if the host is not a localhost.
    if (!preg_match($hostPattern, $host)) {
      if ($https_status) {
        $request = $event->getRequest();
        // Do not redirect from HTTPS requests.
        if ($request->isSecure()) {
          return;
        }
        $url = Url::fromUri("internal:{$request->getPathInfo()}");
        $url->setOption('absolute', TRUE)
          ->setOption('external', FALSE)
          ->setOption('https', TRUE)
          ->setOption('query', $request->query->all());

        $status = $this->getRedirectStatus($event);
        $url = $this->secureUrl($url->toString());
        $response = new TrustedRedirectResponse($url, $status);
        $event->setResponse($response);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    return $events;
  }

  /**
   * Rewrites a URL to use the secure base URL.
   */
  public function secureUrl($url) {
    global $base_path, $base_secure_url;
    // Set the request url to use secure base URL in place of base path.
    if (strpos($url, $base_path) === 0) {
      $base_url = $this->config->get('base_url') ?: $base_secure_url;
      return substr_replace($url, $base_url, 0, strlen($base_path) - 1);
    }
    // Or if a different domain is being used, forcibly rewrite to HTTPS.
    return str_replace('http://', 'https://', $url);
  }

  /**
   * Determines proper redirect status based on request method.
   */
  public function getRedirectStatus(RequestEvent $event) {
    return $event->getRequest()->isMethodCacheable() ? RedirectResponse::HTTP_MOVED_PERMANENTLY : RedirectResponse::HTTP_PERMANENTLY_REDIRECT;
  }

}
