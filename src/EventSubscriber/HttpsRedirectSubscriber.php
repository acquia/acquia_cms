<?php

namespace Drupal\acquia_cms\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event Subscriber MyEventSubscriber.
 */
class HttpsRedirectSubscriber implements EventSubscriberInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * HttpsRedirectSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory;
  }

  /**
   * Code that should be triggered on event specified.
   */
  public function onRequest(RequestEvent $event) {
    if ($this->config->get('acquia_cms.settings')->get('acquia_cms_https')) {
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
    // For this example I am using KernelEvents constants
    // (see below a full list).
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    return $events;
  }

  /**
   * Rewrites a URL to use the secure base URL.
   */
  public function secureUrl($url) {
    global $base_path, $base_secure_url;
    // Set the form action to use secure base URL in place of base path.
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
    // If necessary, use a 308 redirect to avoid losing POST data.
    return $event->getRequest()->isMethodCacheable() ? RedirectResponse::HTTP_MOVED_PERMANENTLY : RedirectResponse::HTTP_PERMANENTLY_REDIRECT;
  }

}
