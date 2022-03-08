<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\acquia_cms_tour\AcquiaCmsTourManager;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Renderer;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Acquia Tour form.
 */
class InstallationWizardForm extends FormBase {

  /**
   * All steps of the multistep form.
   *
   * @var array
   */
  protected $steps;

  /**
   * All steps of the multistep form.
   *
   * @var array
   */
  protected $storage;

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
   * The state interface.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The acquia cms tour manager.
   *
   * @var \Drupal\acquia_cms_tour\AcquiaCmsTourManager
   */
  protected $acquiaCmsTourManager;

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
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state interface.
   * @param \Drupal\acquia_cms_tour\AcquiaCmsTourManager $acquia_cms_tour_manager
   *   The acquia cms tour manager class.
   */
  public function __construct(ClassResolverInterface $class_resolver, Renderer $renderer, StateInterface $state, AcquiaCmsTourManager $acquia_cms_tour_manager) {
    $this->classResolver = $class_resolver;
    $this->renderer = $renderer;
    $this->state = $state;
    $this->acquiaCmsTourManager = $acquia_cms_tour_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('class_resolver'),
      $container->get('renderer'),
      $container->get('state'),
      $container->get('plugin.manager.acquia_cms_tour')
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
      // If the user resumes the wizard later, lets take them
      // to the appropriate config form.
      $current_wizard_step = $this->state->get('current_wizard_step', NULL);
      if ($current_wizard_step && $current_wizard_step != 'completed') {
        $this->setCurrentStep($current_wizard_step);
      }
    }

    // Generate side nav in wizard.
    $form['nav-item'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ol',
      '#items' => $this->getItemList(),
      '#wrapper_attributes' => ['class' => 'tour-sidebar'],
    ];
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
  protected function actionsElement(array $form, FormStateInterface $form_state): array {
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
      // Let's remove ajax call for last step.
      if ($this->useAjax && $action != 'submit' && !$this->isCurrentStepLast()) {
        $element[$action] += [
          '#ajax' => [
            'wrapper' => $this->getFormWrapper(),
          ],
        ];
      }
      // Let's add ajax call for back button on last step.
      if ($this->useAjax && $action == 'back' && $this->isCurrentStepLast()) {
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
  protected function stepActions(array $form, FormStateInterface $form_state): array {
    // Do not show 'back' button on the first step.
    if (!$this->isCurrentStepFirst()) {
      $actions['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('< Back'),
        '#limit_validation_errors' => [],
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

    // Call submitForm of corresponding form.
    $formController = $this->getCurrentFormController()['class'];
    $this->classResolver->getInstanceFromDefinition($formController)->submitForm($form, $form_state);

    $this->currentStep += 1;
    $this->state->set('current_wizard_step', $this->currentStep);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Skip the current state and mark it as completed.
   */
  public function skipStepSubmit(array &$form, FormStateInterface $form_state) {
    // Call default submitForm in case of last step.
    if ($this->isCurrentStepLast()) {
      $this->submitForm($form, $form_state);
    }
    else {
      // Call ignoreConfig of corresponding form.
      $formController = $this->getCurrentFormController()['class'];
      $this->classResolver->getInstanceFromDefinition($formController)->ignoreConfig($form, $form_state);

      $this->currentStep += 1;
      $this->state->set('current_wizard_step', $this->currentStep);
      $form_state->setRebuild(TRUE);
      $form_state->clearErrors();
    }
  }

  /**
   * Checks if the current step is the first step.
   */
  protected function isCurrentStepFirst(): bool {
    return $this->currentStep == 0;
  }

  /**
   * Checks if the current step is the last step.
   */
  protected function isCurrentStepLast(): bool {
    return $this->currentStep == $this->amountSteps();
  }

  /**
   * Returns an amount of the all steps.
   */
  protected function amountSteps(): int {
    return count($this->steps) - 1;
  }

  /**
   * Returns current step.
   */
  protected function getCurrentStep(): int {
    return $this->currentStep;
  }

  /**
   * Set current step.
   *
   * @param int $step
   *   The step to set.
   */
  protected function setCurrentStep(int $step) {
    $this->currentStep = $step;
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
  protected function getFieldValueFromStorage(string $field_name, $empty_value = NULL) {
    return $this->storage[$field_name] ?? $empty_value;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $formController = $this->getCurrentFormController()['class'];
    $this->classResolver->getInstanceFromDefinition($formController)->submitForm($form, $form_state);
    $this->state->set('wizard_completed', TRUE);
    $this->state->set('current_wizard_step', 'completed');
    $this->messenger()->addStatus($this->t('The configuration options have been saved.'));
    $form_state->setRedirect('acquia_cms_tour.enabled_modules');
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
   * considering that the multistep form must consists minimum
   * from two steps. In class the callbacks of the steps are an
   * abstract methods, which means required to implementation in child class.
   * If you need more then two steps inherit and implement this method
   * in your child class like it done in example module and implement
   * the callbacks defined in this method.
   *
   * @return array
   *   An array of elements (steps), where key of element is
   *   a numeric representation of the step and value is a callback
   *   which will be called to return a $form by the numeric representation.
   */
  public function getSteps(): array {
    $steps = [];
    foreach ($this->acquiaCmsTourManager->getDefinitions() as $definition) {
      $isModuleEnabled = $this->classResolver->getInstanceFromDefinition($definition['class'])->isModuleEnabled();
      if ($isModuleEnabled) {
        $steps[] = $definition;
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
   *
   * @throws \Exception
   */
  public function stepForm(array &$form, FormStateInterface $form_state): array {
    $plugin = $this->getCurrentFormController();
    $key = $plugin['id'];
    $formControllerDefinition = $this->classResolver->getInstanceFromDefinition($plugin['class']);
    $module_title = $formControllerDefinition->getModuleName();
    $form = $formControllerDefinition->buildForm($form, $form_state);
    $form[$key]['title_markup'] = [
      '#type' => 'markup',
      '#markup' => $this->getTitleMarkup($module_title, ($this->currentStep) + 1),
      '#weight' => -1,
    ];
    // Change details to fieldset for all form.
    $form[$key]['#type'] = 'fieldset';
    unset($form[$key]['actions']);
    unset($form[$key]['#title']);
    return $form;
  }

  /**
   * Helper method for sidebar nav item list.
   *
   * @return array
   *   The render array defining the markup of the sidebar.
   */
  protected function getItemList(): array {
    $items = [];
    $steps = $this->getSteps();
    foreach ($steps as $key => $plugin) {
      $module_machine_name = $plugin['id'];
      $formControllerDefinition = $this->classResolver->getInstanceFromDefinition($plugin['class']);
      $module_title = $formControllerDefinition->getModuleName();
      $sr_no = $key + 1;
      if ($sr_no < ($this->currentStep) + 1) {
        $current_class = ['item', 'step-complete'];
      }
      elseif ($sr_no == ($this->currentStep) + 1) {
        $current_class = ['item', 'current-step'];
      }
      else {
        $current_class = ['item'];
      }
      $items[$module_machine_name] = [
        '#wrapper_attributes' => [
          'class' => $current_class,
        ],
        '#children' => $module_title,
      ];
    }
    return $items;
  }

  /**
   * Helper method for adding title markup.
   *
   * @param string $module_title
   *   The module human-readable name.
   * @param int $current_step
   *   The forms current step.
   *
   * @return string
   *   The rendered array defining the markup of the title.
   *
   * @throws \Exception
   */
  public function getTitleMarkup(string $module_title, int $current_step): string {
    $title_markup = [
      '#theme' => 'acquia_cms_tour_title_markup',
      '#module_name' => $module_title,
      '#current_step' => $current_step,
    ];
    return $this->renderer->render($title_markup);
  }

  /**
   * Helper to get current formController based on step.
   *
   * @return array
   *   The array of module name & Form class object.
   */
  private function getCurrentFormController() {
    return $this->steps[$this->currentStep];
  }

}
