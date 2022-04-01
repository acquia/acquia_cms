<?php

namespace Drupal\acquia_cms_headless_ui\Controller;

use Drupal\acquia_cms_headless_ui\Form\UserLoginForm;
use Drupal\Core\Controller\ControllerBase;

/**
 * Defines a controller for the front page of the site.
 *
 * @internal
 *   This is an internal part of Acquia CMS Headless and may be changed or
 *   removed at any time without warning. External code should not extend or
 *   use this class in any way!
 */
class FrontController extends ControllerBase {

  /**
   * Displays the login form on the homepage and redirects authenticated users.
   */
  public function frontpage() {
    $build = [];
    if ($this->currentUser()->isAnonymous()) {
      $build['heading'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Please log in for access to the content repository.'),
      ];
      $build['form'] = $this->formBuilder()->getForm(UserLoginForm::class);
    }
    else {
      if ($this->currentUser()->hasPermission('access content overview')) {
        // Permitted users are directed to the admin content page.
        return $this->redirect('view.content.page_1');
      }
      $build['heading'] = [
        '#type' => 'markup',
        '#markup' => $this->t('This site has no homepage content.'),
      ];
    }
    return $build;
  }

}
