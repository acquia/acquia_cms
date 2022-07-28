<?php

namespace Drupal\acquia_cms_tour\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A controller for welcome modal.
 */
class WelcomeModalController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The state interface.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(StateInterface $state, FormBuilder $formBuilder) {
    $this->state = $state;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('form_builder')
    );
  }

  /**
   * Callback for opening the welcome modal form.
   */
  public function openStarterModalForm() {
    $response = new AjaxResponse();

    // Get the modal form using the form builder.
    if(!$this->state->get('starter_kit', FALSE)){
      $modal_form = $this->formBuilder->getForm('Drupal\acquia_cms_tour\Form\StarterKitSelectionWizardForm');
      $modal_options = [
        'dialogClass' => 'acms-starter-kit-wizard',
        'width' => 1000,
      ];
      // Add an AJAX command to open a modal dialog with the form as the content.
      $response->addCommand(new OpenModalDialogCommand('', $modal_form, $modal_options));
    }
    return $response;

  }

  /**
   * Callback for opening the welcome modal form.
   */
  public function openWelcomeModalForm() {
    $response = new AjaxResponse();

    // Get the modal form using the form builder.
    $modal_form = $this->formBuilder->getForm('Drupal\acquia_cms_tour\Form\WelcomeModalForm');
    $modal_options = [
      'dialogClass' => 'acms-welcome-modal',
      'width' => 500,
    ];
    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenModalDialogCommand('', $modal_form, $modal_options));

    return $response;
  }
}
