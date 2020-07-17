<?php

namespace Drupal\acquia_cms_tour\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;

/**
 * Defines a controller to help users configure Google Analytics.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class GoogleAnalytics extends ControllerBase {

  /**
   * Builds a renderable array containing helpful info about Google Analytics.
   *
   * @return array
   *   The renderable array.
   */
  public function build() : array {
    $section = [
      '#type' => 'details',
      '#title' => $this->t('Google Analytics'),
      '#open' => TRUE,
    ];

    return $this->moduleHandler()->moduleExists('google_analytics')
      ? $this->enabled($section)
      : $this->disabled($section);
  }

  /**
   * Builds the content when Google Analytics is enabled.
   *
   * @param array $section
   *   The renderable array being built by this controller.
   *
   * @return array
   *   The renderable array.
   */
  private function enabled(array $section) : array {
    $user_can_configure = $this->currentUser()->hasPermission('administer google analytics');
    $ga_account = (bool) $this->config('google_analytics.settings')->get('account');
    if ($ga_account) {
      $message = $this->t('Google Analytics is enabled and configured.');
      $message_type = 'status';
      $section['#open'] = FALSE;

      if ($user_can_configure) {
        $section['message']['#markup'] = Link::createFromRoute($this->t('Configure Google Analytics now.'), 'google_analytics.admin_settings_form')->toString();
      }
    }
    elseif ($user_can_configure) {
      $link = Link::createFromRoute($this->t('Please configure the API key.'), 'google_analytics.admin_settings_form');
      $message = $this->t('Google Analytics is enabled. @link', [
        '@link' => $link->toString(),
      ]);
      $message_type = 'warning';
    }
    else {
      $message = $this->t('Google Analytics is enabled. Please ask your site administrator to configure the API key.');
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
   * Builds the content when Google Analytics is disabled.
   *
   * @param array $section
   *   The renderable array being built by this controller.
   *
   * @return array
   *   The renderable array.
   */
  private function disabled(array $section) : array {
    if ($this->currentUser()->hasPermission('administer modules')) {
      $link = Link::createFromRoute($this->t('Visit the Modules page to enable it.'), 'system.modules_list');
      $message = $this->t('Google Analytics is disabled. @link', [
        '@link' => $link->toString(),
      ]);
    }
    else {
      $message = $this->t('Google Analytics is disabled. Please contact your site administrator.');
    }
    $section['message']['#markup'] = $message;
    $this->messenger()->addWarning($message);

    return $section;
  }

}
