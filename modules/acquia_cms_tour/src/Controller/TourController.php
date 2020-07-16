<?php

namespace Drupal\acquia_cms_tour\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\google_tag\Entity\ContainerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a route controller providing a simple tour of Acquia CMS.
 */
final class TourController extends ControllerBase {

  /**
   * The Google tag container manager.
   *
   * @var \Drupal\google_tag\Entity\ContainerManager
   */
  protected $googleTagContainerManager;

  /**
   * Class constructor.
   */
  public function __construct(ContainerManager $conainer_manager) {
    $this->googleTagContainerManager = $conainer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_tag.container_manager')
    );
  }

  /**
   * Returns a renderable array for a tour page.
   */
  public function tour() {
    $tour = [];
    $tour['google_analytics'] = $this->buildGoogleAnalyticsSection();
    $tour['google_tag_manager'] = $this->buildGoogleTagManagerSection();
    return $tour;
  }

  /**
   * Add link to the google analytics configuration page.
   */
  protected function buildGoogleAnalyticsSection() {
    $link = [];
    if ($this->moduleHandler()->moduleExists('google_analytics')) {
      $ga_account = $this->config('google_analytics.settings')->get('account');
      $user_has_ga_permission = $this->currentUser()->hasPermission('administer google analytics');
      $message = $this->t('Google Analytics is enabled');
      if ($user_has_ga_permission) {
        $link['details'] = [
          '#type' => 'details',
          '#open' => $ga_account ? FALSE : TRUE,
          '#title' => $this->t('Google Analytics'),
        ];
        $link['details']['link'] = [
          '#title' => $this->t('Google Analytics'),
          '#type' => 'link',
          '#url' => Url::fromRoute('google_analytics.admin_settings_form'),
        ];
        $message = $this->t('Google Analytics is enabled. @link', [
          '@link' => Link::createFromRoute('Please configure the API key.', 'google_analytics.admin_settings_form')->toString(),
        ]);
      }
      if ($ga_account) {
        $this->messenger()->addStatus($this->t('Google Analytics is enabled and configured.'));
      }
      else {
        $this->messenger()->addWarning($message);
      }
    }
    else {
      $user_has_module_permission = $this->currentUser()->hasPermission('administer modules');
      $message = $this->t('Google Analytics is disabled');
      if ($user_has_module_permission) {
        $message = $this->t('Google Analytics is disabled. @link.', [
          '@link' => Link::createFromRoute('Visit the Modules page to enable it', 'system.modules_list')->toString(),
        ]);
      }
      $this->messenger()->addWarning($message);
    }
    return $link;
  }

  /**
   * Add link to the google tag manager configuation page.
   */
  protected function buildGoogleTagManagerSection() {
    $link = [];
    if ($this->moduleHandler->moduleExists('google_tag')) {
      $gtm_container_id = $this->googleTagContainerManager->loadContainerIDs();
      $user_has_gtm_permission = $this->currentUser()->hasPermission('administer google tag manager');
      $message = $this->t('Google Tag Manager is enabled');
      if ($user_has_gtm_permission) {
        $message = $this->t('Google Tag Manager is enabled. @link', [
          '@link' => Link::createFromRoute('Please configure the API key.', 'entity.google_tag_container.collection')->toString(),
        ]);
        $link['details'] = [
          '#type' => 'details',
          '#open' => empty($gtm_container_id) ? TRUE : FALSE,
          '#title' => $this->t('Google Tag Manager'),
        ];
        $link['details']['link'] = [
          '#title' => $this->t('Google Tag Manager'),
          '#type' => 'link',
          '#url' => Url::fromRoute('entity.google_tag_container.collection'),
        ];
      }
      if (!empty($gtm_container_id)) {
        $this->messenger()->addStatus($this->t('Google Tag Manager is enabled and configured.'));
      }
      else {
        $this->messenger()->addWarning($message);
      }
    }
    else {
      $user_has_module_permission = $this->currentUser()->hasPermission('administer modules');
      $message = $this->t('Google Tag Manager is disabled');
      if ($user_has_module_permission) {
        $message = $this->t('Google Tag Manager is disabled. @link.', [
          '@link' => Link::createFromRoute('Visit the Modules page to enable it', 'system.modules_list')->toString(),
        ]);
      }
      $this->messenger()->addWarning($message);
    }
    return $link;
  }

}
