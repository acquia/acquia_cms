<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides config checklist base form.
 */
abstract class EnabledConfigChecklistBaseForm extends FormBase {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The info file parser.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

  /**
   * Constructs a new EnabledConfigChecklistBaseForm.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info file parser.
   */
  public function __construct(StateInterface $state, ModuleHandlerInterface $module_handler, LinkGeneratorInterface $link_generator, InfoParserInterface $info_parser) {
    $this->state = $state;
    $this->module_handler = $module_handler;
    $this->linkGenerator = $link_generator;
    $this->infoParser = $info_parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('module_handler'),
      $container->get('link_generator'),
      $container->get('info_parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_checklist_base_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#theme'] = 'acquia_cms_tour_checklist_form';
    $form['#attached']['library'][] = 'acquia_cms_tour/styling';

    $form['#tree'] = TRUE;

    $form['checklist_heading']['#markup'] = $this->getChecklistTitle();
    $form['checklist_description']['#markup'] = $this->getChecklistDescription();

    $form_id = $this->getFormId();
    $config_state = $this->state->get('acquia_cms_tour.' . $form_id);

    // Set initial state of the checklist progress.
    $form['check_count'] = [
      '#type' => 'value',
      '#value' => 0,
    ];
    $form['check_total'] = [
      '#type' => 'value',
      '#value' => 0,
    ];
    $form['show_progress'] = [
      '#type' => 'value',
      '#value' => TRUE,
    ];

    // Build checklist.
    $list = $this->getChecklistModules();
    foreach ($list as $module => $route) {
      if ($this->module_handler->moduleExists($module)) {
        $module_path = $this->module_handler->getModule($module)->getPathname();
        $module_info = $this->infoParser->parse($module_path);

        $form['items'][$module] = [
          'check' => [
            '#type' => 'checkbox',
            '#default_value' => $config_state[$module],
            '#title' => $module_info['name'],
            '#description' => $module_info['description'],
            '#ajax' => [
              'callback' => '::updateChecklistState',
              'progress' => [
                'type' => 'throbber',
                'message' => NULL,
              ],
            ],
          ],
          'link' => [
            '#markup' => $this->linkGenerator->generate(
              'Configure',
              Url::fromRoute($route ? $route : $module_info['configure'])
            ),
          ],
        ];

        $form['check_total']['#value']++;
        $form['check_count']['#value'] = isset($config_state[$module]) && $config_state[$module]
          ? $form['check_count']['#value'] + 1
          : $form['check_count']['#value'];
      }
    }

    return $form;
  }

  /**
   * Checklist title.
   */
  abstract public function getChecklistTitle(): string;

  /**
   * Checklist description.
   */
  abstract public function getChecklistDescription(): string;

  /**
   * Checklist modules.
   */
  abstract public function getChecklistModules(): array;

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Submit handler to update checklist state.
   *
   * @param array $form
   *   The form components.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function updateChecklistState(array $form, FormStateInterface $form_state) {
    $items = $form_state->getValue('items');

    // Save checklist state.
    $count = 0;
    $config_state = [];
    foreach ($items as $key => $item) {
      $config_state[$key] = $item['check'];
      if ($item['check']) {
        $count++;
      }
    }

    $form_id = $this->getFormId();
    $this->state->set('acquia_cms_tour.' . $form_id, $config_state);

    // Update the progress details.
    $ajax_response = new AjaxResponse();

    $item_count = count($items);
    $percent = $item_count ? ($count * 100) / $item_count : 0;

    $ajax_response->addCommand(new HtmlCommand('.tour-checklist.' . $form_id . ' .progess-info-percent', round($percent) . '%'));
    $ajax_response->addCommand(new HtmlCommand('.tour-checklist.' . $form_id . ' .progess-info-configured', '(' . $count . ' of ' . $item_count . ' configured)'));
    $ajax_response->addCommand(new CssCommand('.tour-checklist.' . $form_id . ' .progress__bar', ['width' => $percent . '%']));

    return $ajax_response;
  }

}
