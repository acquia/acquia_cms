<?php

namespace Drupal\acquia_cms_headless_ui\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides a method to redirect anon users to login page.
 */
class RedirectAnonymousSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * A function that checks current user type and directs when Anon.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Passes in the event request.
   */
  public function checkAnonymous(RequestEvent $event) {
    // Get current request.
    $request = $event->getRequest();
    // Get current path.
    $current_path = $request->getPathInfo();
    // Check if this is /user/rest path.
    $user_reset = preg_match('/user/reset/*', $current_path);

    if ($this->currentUser->isAnonymous()
      && $current_path != '/user/login'
      && $current_path != '/user/password'
      && $current_path != '/register'
      && $user_reset != TRUE
    ) {
      $event->setResponse(new RedirectResponse('/user/login', 301));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkAnonymous', 100];
    return $events;
  }

}
