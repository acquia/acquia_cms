<?php

namespace Drupal\acquia_claro;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides pre-render callback functions for the acquia_claro theme.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class PreRender implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['managedFile'];
  }

  /**
   * Pre-render callback for managed_file form elements.
   *
   * @param array $element
   *   The element's render array.
   *
   * @return array
   *   The element's modified render array.
   */
  public static function managedFile(array $element) : array {
    // Claro normally wraps all managed_file elements in open <details> wrapper.
    // @see \Drupal\claro\ClaroPreRender::managedFile()
    unset($element['#theme_wrappers']['details']['#attributes']['open']);
    return $element;
  }

}
