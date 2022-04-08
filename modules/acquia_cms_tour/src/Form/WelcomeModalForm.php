<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\File\FileUrlGenerator;
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
   * The profile extension list object.
   *
   * @var \Drupal\Core\Extension\ProfileExtensionList
   */
  protected $profileExtensionList;

  /**
   * The state interface.
   *
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  protected $file;

  /**
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\State\ProfileExtensionList $profile_extension_list
   *   The profile extension list object.
   * @param \Drupal\Core\File\FileUrlGenerator $file
   *   File Generator.
   */
  public function __construct(StateInterface $state, ProfileExtensionList $profile_extension_list, FileUrlGenerator $file) {
    $this->state = $state;
    $this->profileExtensionList = $profile_extension_list;
    $this->file = $file;
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
      $container->get('extension.list.profile'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
    $acms_logo = $this->file->generateString(theme_get_setting('logo.url'));
    $logo = $this->file->transformRelative($acms_logo);
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
      '#markup' => '<img src="' . $logo . '" width="80">',
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
