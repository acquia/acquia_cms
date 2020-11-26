<?php

namespace Drupal\acquia_cms\EventSubscriber;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
   * HttpsRedirectSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   A cache backend used to store configuration.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache) {
    $this->config = $config_factory;
    $this->cache = $cache;
  }

  /**
   * Code that should be triggered on event specified.
   */
  public function onRequest(RequestEvent $event) {
    // Get the config value from cache if available.
    $https_status = $this->config->get('acquia_cms.settings')->get('acquia_cms_https');
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
