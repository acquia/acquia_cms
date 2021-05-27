<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create welcome modal form.
 */
class WelcomeModalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acms_welcome_modal_form';
  }

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
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
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
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
    $acms_logo = drupal_get_path('profile', 'acquia_cms') . '/acquia_cms.png';
    $form['tour-dashboard'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'tour-dashboard',
        ],
      ],
    ];
    $form['tour-dashboard']['logo'] = [
      '#type' => 'markup',
      '#markup' => '<img src="/' . $acms_logo . '" width="80">',
    ];
    $form['tour-dashboard']['title'] = [
      '#type' => 'markup',
      '#markup' => '<h3>' . $this->t('Welcome to Acquia CMS') . '</h3>',
    ];
    $form['tour-dashboard']['message'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t("We've created an easy step by step installation wizard to guide you through the necessary configurations") . '</p>',
    ];
    $form['tour-dashboard']['actions'] = ['#type' => 'actions'];
    $form['tour-dashboard']['actions']['open_wizard'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get Started with Wizard'),
      '#attributes' => [
        'class' => [
          'button button--primary',
        ],
      ],
      '#submit' => ['::submitOpenWizard'],
    ];
    $form['tour-dashboard']['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Setup Manually'),
      '#attributes' => [
        'class' => [
          'setup-manually',
        ],
      ],
    ];
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitOpenWizard(array &$form, FormStateInterface $form_state) {
    $this->state->set('show_welcome_modal', FALSE);
    $form_state->setRedirect('acquia_cms_tour.enabled_modules');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->state->set('show_wizard_modal', FALSE);
    $this->state->set('show_welcome_modal', FALSE);
    $form_state->setRedirect('acquia_cms_tour.enabled_modules');
  }

}
