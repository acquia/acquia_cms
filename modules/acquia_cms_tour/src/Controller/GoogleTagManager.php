<?php

namespace Drupal\acquia_cms_tour\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to help users configure Google Tag Manager.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class GoogleTagManager extends ControllerBase {

  /**
   * The Google Tag Manager container manager service.
   *
   * @var \Drupal\google_tag\Entity\ContainerManagerInterface
   */
  private $containerManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $service_id = 'google_tag.container_manager';
    if ($container->has($service_id)) {
      $instance->containerManager = $container->get($service_id);
    }
    return $instance;
  }

  /**
   * Builds a renderable array containing helpful info about Google Tag Manager.
   *
   * @return array
   *   The renderable array.
   */
  public function build() : array {
    $section = [
      '#type' => 'details',
      '#title' => $this->t('Google Tag Manager'),
      '#open' => TRUE,
    ];

    return $this->moduleHandler()->moduleExists('google_tag')
      ? $this->enabled($section)
      : $this->disabled($section);
  }

  /**
   * Builds the content when Google Tag Manager is enabled.
   *
   * @param array $section
   *   The renderable array being built by this controller.
   *
   * @return array
   *   The renderable array.
   */
  public function enabled(array $section) : array {
    $user_can_configure = $this->currentUser()->hasPermission('administer google tag manager');
    $gtm_container_id = $this->containerManager->loadContainerIDs();
    if ($gtm_container_id) {
      $message = $this->t('Google Tag Manager is enabled and configured.');
      $message_type = 'status';
      $section['#open'] = FALSE;

      if ($user_can_configure) {
        $section['message']['#markup'] = Link::createFromRoute($this->t('Configure Google Tag Manager now.'), 'entity.google_tag_container.collection')->toString();
      }
    }
    elseif ($user_can_configure) {
      $link = Link::createFromRoute('Please configure the API key.', 'entity.google_tag_container.collection');
      $message = $this->t('Google Tag Manager is enabled. @link', [
        '@link' => $link->toString(),
      ]);
      $message_type = 'warning';
    }
    else {
      $message = $this->t('Google Tag Manager is enabled. Please ask your site administrator to cofigure the API key.');
      $message_type = 'warning';
    }
    $section += [
      'message' => [
        '#markup' => $message,
      ],
    ];
    $this->messenger()->addMessage($message, $message_type);

    return $section;
  }

  /**
   * Builds the content when Google Tag Manager is disabled.
   *
   * @param array $section
   *   The renderable array being built by this controller.
   *
   * @return array
   *   The renderable array.
   */
  public function disabled(array $section) : array {
    if ($this->currentUser()->hasPermission('administer modules')) {
      $link = Link::createFromRoute($this->t('Visit the Modules page to enable it.'), 'system.modules_list');
      $message = $this->t('Google Tag Manager is disabled. @link', [
        '@link' => $link->toString(),
      ]);
    }
    else {
      $message = $this->t('Google Tag Manager is disabled. Please contact your site administrator.');
    }
    $section['message']['#markup'] = $message;
    $this->messenger()->addWarning($message);

    return $section;
  }

}
