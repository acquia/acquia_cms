<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Acquia Tour form.
 */
class InstallationWizardForm extends FormBase {

  private const SECTIONS = [
    'acquia_telemetry' => AcquiaTelemetryForm::class,
    'geocoder' => AcquiaGoogleMapsApiDashboardForm::class,
    'acquia_search_solr' => AcquiaSearchSolrForm::class,
    'google_analytics' => GoogleAnalyticsForm::class,
    'google_tag' => GoogleTagManagerForm::class,
    'recaptcha' => RecaptchaForm::class,
    'acquia_connector' => AcquiaConnectorForm::class,
    'cohesion' => SiteStudioCoreForm::class,
  ];

  /**
   * All steps of the multistep form.
   *
   * @var array
   */
  protected $steps;

  /**
   * All steps of the multistep form.
   *
   * @var bool
   */
  protected $useAjax = TRUE;

  /**
   * The rendered array renderer.
   *
   * @var array
   */
  protected $renderer;

  /**
   * Current step.
   *
   * @var int
   */
  protected $currentStep;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_tour_installation_wizard';
  }

  /**
   * Constructs a new InstallationWizardForm.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   */
  public function __construct(ClassResolverInterface $class_resolver, Renderer $renderer) {
    $this->classResolver = $class_resolver;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('class_resolver'),
      $container->get('renderer')
    );
  }

  /**
   * Returns wrapper for the form.
   */
  public function getFormWrapper() {
    $form_id = $this->getFormId();
    if ($this->useAjax) {
      $form_id = 'ajax_' . $form_id;
    }
    return str_replace('_', '-', $form_id);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    if (is_null($this->currentStep)) {
      // Initialize multistep form.
      $this->initMultistepForm($form, $form_state);
    }

    $form = $this->stepForm($form, $form_state);

    $form['#prefix'] = '<div id=' . $this->getFormWrapper() . '>';
    $form['#suffix'] = '</div>';

    $actions = $this->actionsElement($form, $form_state);
    if (!empty($actions)) {
      $form['actions'] = $actions;
    }

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * Returns the actions form element for the specific step.
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $element = $this->stepActions($form, $form_state);

    if (isset($element['submit'])) {
      // Give the primary submit button a #button_type of primary.
      $element['submit']['#button_type'] = 'primary';
    }

    $count = 0;
    foreach (Element::children($element) as $action) {
      $element[$action] += [
        '#weight' => ++$count * 5,
      ];

      if ($this->useAjax && $action != 'submit') {
        $element[$action] += [
          '#ajax' => [
            'wrapper' => $this->getFormWrapper(),
          ],
        ];
      }
    }

    if (!empty($element)) {
      $element['#type'] = 'actions';
    }

    return $element;
  }

  /**
   * Returns an array of supported actions for the specific step form.
   */
  protected function stepActions(array $form, FormStateInterface $form_state) {
    // Do not show 'back' button on the first step.
    if (!$this->isCurrentStepFirst()) {
      $actions['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('< Back'),
        '#submit' => ['::previousStepSubmit'],
      ];
    }

    // Show skip this step button.
    $actions['skip'] = [
      '#type' => 'submit',
      '#value' => $this->t("Skip this step"),
      '#limit_validation_errors' => [],
      '#attributes' => [
        'class' => ['skip-button'],
      ],
      '#submit' => ['::skipStepSubmit'],
    ];

    // Do not show 'next' button on the last step.
    if (!$this->isCurrentStepLast()) {
      $actions['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next >'),
        '#submit' => ['::nextStepSubmit'],
      ];
    }

    // Show submit button on the last step.
    if ($this->isCurrentStepLast()) {
      $actions['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t("Complete"),
      ];
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function previousStepSubmit(array &$form, FormStateInterface $form_state) {
    $this->copyFormValuesToStorage($form, $form_state);
    $this->currentStep -= 1;
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function nextStepSubmit(array &$form, FormStateInterface $form_state) {
    $this->copyFormValuesToStorage($form, $form_state);
    $this->currentStep += 1;
    $form_state->setRebuild(TRUE);
  }

  /**
   * Skip the current state and mark it as completed.
   */
  public function skipStepSubmit(array &$form, FormStateInterface $form_state) {
    $this->currentStep += 1;
    $form_state->setRebuild(TRUE);
    $form_state->clearErrors();
  }

  /**
   * Checks if the current step is the first step.
   */
  protected function isCurrentStepFirst() {
    return $this->currentStep == 0 ? TRUE : FALSE;
  }

  /**
   * Checks if the current step is the last step.
   */
  protected function isCurrentStepLast() {
    return $this->currentStep == $this->amountSteps() ? TRUE : FALSE;
  }

  /**
   * Returns an amount of the all steps.
   */
  protected function amountSteps() {
    return count($this->steps) - 1;
  }

  /**
   * Returns current step.
   */
  protected function getCurrentStep() {
    return $this->currentStep;
  }

  /**
   * Copies field values to storage of the class.
   *
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function copyFormValuesToStorage(array $form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $values = $form_state->getValues();

    foreach ($values as $field_name => $value) {
      // If field is already stored in storage
      // check if it was changed, if so rewrite value.
      if ((isset($this->storage[$field_name]) && $this->storage[$field_name] != $value) || !isset($this->storage[$field_name])) {
        $this->storage[$field_name] = $value;
      }
    }
  }

  /**
   * Gets the value of the specific field from storage of the class.
   *
   * @param string $field_name
   *   A name of the field.
   * @param mixed $empty_value
   *   The value which will be returned if $field_name is not stored in storage.
   *
   * @return mixed
   *   A field value.
   */
  protected function getFieldValueFromStorage($field_name, $empty_value = NULL) {
    if (isset($this->storage[$field_name])) {
      return $this->storage[$field_name];
    }
    else {
      return $empty_value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $form_state->setRedirect('<front>');
  }

  /**
   * Initialize multistep form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function initMultistepForm(array $form, FormStateInterface $form_state) {
    $this->currentStep = 0;
    $this->steps = $this->getSteps();
    $this->storage = [];
  }

  /**
   * Steps for the multistep form.
   *
   * The class FormMultistepBase provide two steps,
   * considering that the multi step form must consists minimum
   * from two steps. In class the callbacks of the steps are an
   * abstract methods, which means required to implemantation in child class.
   * If you need more then two steps inherit and implement this method
   * in your child class like it done in example module and implement
   * the callbacks defined in this method.
   *
   * @return array
   *   An array of elements (steps), where key of element is
   *   a numeric representation of the step and value is a callback
   *   which will be called to return a $form by the numeric represantation.
   */
  public function getSteps() {
    $steps = [];
    foreach (static::SECTIONS as $controller) {
      $isModuleEnabled = $this->classResolver->getInstanceFromDefinition($controller)->isModuleEnabled();
      if ($isModuleEnabled) {
        $steps[] = $controller;
      }
    }
    return $steps;
  }

  /**
   * The form for the specific step.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function stepForm(array &$form, FormStateInterface $form_state) {
    $formController = $this->steps[$this->currentStep];
    $sections = \array_flip(self::SECTIONS);
    $key = $sections[$formController];
    $form['title_markup'] = [
      '#type' => 'markup',
      '#markup' => $this->getTitleMarkup($key, ($this->currentStep) + 1),
    ];
    $form['sidebar_markup'] = [
      '#type' => 'markup',
      '#markup' => $this->getSideBarMarkup(($this->currentStep) + 1),
    ];
    $form = $this->classResolver->getInstanceFromDefinition($formController)->buildForm($form, $form_state);
    // Change details to fieldset for all form.
    $form[$key]['#type'] = 'fieldset';
    unset($form[$key]['actions']);
    return $form;
  }

  /**
   * Helper method for adding sidebar markup.
   *
   * @param int $current_step
   *   The forms current step.
   *
   * @return string
   *   The render array defining the markup of the sidebar.
   */
  public function getSideBarMarkup(int $current_step) {
    $steps = $this->getSteps();
    $data = [];
    foreach ($steps as $key => $controller) {
      $instance_definition = $this->classResolver->getInstanceFromDefinition($controller);
      $module_machine_name = $instance_definition->getmodule();
      $module_title = $instance_definition->getModuleName();
      if ($instance_definition->isModuleEnabled()) {
        $sr_no = $key + 1;
        $data[$module_machine_name]['sr_no'] = $sr_no;
        $data[$module_machine_name]['title'] = $module_title;
        if ($sr_no == $current_step) {
          $current_class = 'current_step';
        }
        else {
          $current_class = 'item-';
        }
        $data[$module_machine_name]['class'] = $current_class;
      }
    }
    $sidebar_markup = [
      '#theme' => 'acquia_cms_tour_sidebar_markup',
      '#data' => $data,
    ];
    return $this->renderer->render($sidebar_markup);

  }

  /**
   * Helper method for adding title markup.
   *
   * @param string $module
   *   The module machine name.
   * @param int $current_step
   *   The forms current step.
   *
   * @return string
   *   The rendered array defining the markup of the title.
   */
  public function getTitleMarkup(string $module, int $current_step) {
    $module_name = $this->moduleHandler->getName($module);
    $title_markup = [
      '#theme' => 'acquia_cms_tour_title_markup',
      '#module_name' => $module_name,
      '#current_step' => $current_step,
    ];
    return $this->renderer->render($title_markup);
  }

}
