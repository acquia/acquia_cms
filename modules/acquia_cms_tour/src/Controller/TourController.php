<?php

namespace Drupal\acquia_cms_tour\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a route controller providing a simple tour of Acquia CMS.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class TourController extends ControllerBase {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountProxy $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Returns a renderable array for a tour page.
   */
  public function build() {
    $showButton = 0;
    if ($this->currentUser->hasPermission('access acquia cms tour dashboard')) {
      $showButton = 1;
    }
    return [
      '#theme' => 'acquia_cms_tour',
      '#attached' => [
        'library' => [
          'acquia_cms_tour/acquia_cms_tour',
        ],
      ],
      '#data' => [
        'showButton' => $showButton,
      ],
    ];
  }

}
