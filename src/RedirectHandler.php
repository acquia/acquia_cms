<?php

namespace Drupal\acquia_cms;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles special redirection logic for users after they log in.
 *
 * @internal
 *   This is an internal part of Acquia CMS and may be changed in any way, or
 *   removed at any time, without warning. You shouldn't touch it. If you
 *   absolutely must touch it, please copy it into your own code base.
 */
final class RedirectHandler implements ContainerInjectionInterface {

  /**
   * The user entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $userStorage;

  /**
   * Retrieves the currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  private $request;

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  private $pathValidator;

  /**
   * RedirectHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $user_storage
   *   The user entity storage handler.
   * @param \Symfony\Component\HttpFoundation\Request $current_request
   *   The current active request object.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator service.
   */
  public function __construct(EntityStorageInterface $user_storage, Request $current_request, PathValidatorInterface $path_validator) {
    $this->userStorage = $user_storage;
    $this->request = $current_request;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('path.validator')
    );
  }

  /**
   * A form submit handler for redirecting after login.
   *
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public static function submitForm(array &$form, FormStateInterface $form_state) : void {
    \Drupal::classResolver(static::class)->handleRedirect($form_state);
  }

  /**
   * Handles special redirection after user login.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function handleRedirect(FormStateInterface $form_state) {
    // This is set by the user login form.
    // @see \Drupal\user\Form\UserLoginForm::validateAuthentication()
    $user = $this->userStorage->load($form_state->get('uid'));
    assert($user instanceof AccountInterface);

    // If the user is about to be redirected to their user page, do our special
    // sauce redirect handling based on the role(s) the user has.
    if ($this->willRedirectToUserPage()) {
      // Remove the 'destination' query sting parameter, since it will cause our
      // redirect to be totally ignored due to a core quirk.
      // @todo Remove when https://www.drupal.org/project/drupal/issues/2950883
      // is fixed.
      $this->request->query->remove('destination');

      if ($this->isContributor($user)) {
        // @todo Don't redirect if Moderation Dashboard is not enabled.
        $url = Url::fromUri('internal:/user/' . $user->id() . '/moderation/dashboard');
        $form_state->setRedirectUrl($url);
      }
      elseif ($this->isDeveloper($user)) {
        $form_state->setRedirect('cohesion.settings');
      }
      elseif ($this->isUserAdministrator($user)) {
        $form_state->setRedirect('entity.user.collection');
      }
    }
  }

  /**
   * Determines if the user is going to be redirected to their user page.
   *
   * If the 'destination' query string parameter is available, we check to see
   * if it maps to any of the user page routes. Otherwise, we assume that the
   * user is going to be redirected to their user page.
   *
   * @return bool
   *   TRUE if the user will be redirected to their user page, FALSE otherwise.
   */
  private function willRedirectToUserPage() : bool {
    $destination = $this->request->query->get('destination');

    if ($destination) {
      $destination = $this->pathValidator->getUrlIfValid($destination);

      return $destination
        ? in_array($destination->getRouteName(), [
          'user.page',
          'entity.user.canonical',
        ], TRUE)
        : FALSE;
    }
    return TRUE;
  }

  /**
   * Checks if a user is a contributor (i.e., has a content-related role).
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   *
   * @return bool
   *   TRUE if the user is a contributor, FALSE otherwise.
   */
  private function isContributor(AccountInterface $account) : bool {
    return $this->hasAnyRole($account, [
      'content_author',
      'content_editor',
      'content_administrator',
      'administrator',
    ]);
  }

  /**
   * Checks if a user is a developer (i.e., has a site building role).
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   *
   * @return bool
   *   TRUE if the user is a developer, FALSE otherwise.
   */
  private function isDeveloper(AccountInterface $account) : bool {
    return $this->hasAnyRole($account, ['site_builder', 'developer']);
  }

  /**
   * Checks if a user has an administrative user-management role.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   *
   * @return bool
   *   TRUE if the user is a user administrator, FALSE otherwise.
   */
  private function isUserAdministrator(AccountInterface $account) : bool {
    return in_array('user_administrator', $account->getRoles(), TRUE);
  }

  /**
   * Checks if a user account has one of a set of roles.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   * @param string[] $roles
   *   The role IDs to look for.
   *
   * @return bool
   *   TRUE if the user has any of the given roles, FALSE otherwise.
   */
  private function hasAnyRole(AccountInterface $account, array $roles) : bool {
    return (bool) array_intersect($roles, $account->getRoles());
  }

}
