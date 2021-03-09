<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to configure SiteStudioCore.
 */
final class SiteStudioCoreForm extends ConfigFormBase {

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
   * Constructs a new SiteStudioCoreForm.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info file parser.
   */
  public function __construct(ModuleHandlerInterface $module_handler, LinkGeneratorInterface $link_generator, InfoParserInterface $info_parser) {
    $this->module_handler = $module_handler;
    $this->linkGenerator = $link_generator;
    $this->infoParser = $info_parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('link_generator'),
      $container->get('info_parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_site_studio_core_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cohesion.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = FALSE;
    $module = 'cohesion';
    if ($this->module_handler->moduleExists($module)) {
      $module_path = $this->module_handler->getModule($module)->getPathname();
      $module_info = $this->infoParser->parse($module_path);
      $form['cohesion'] = [
        '#type' => 'fieldset',
        '#title' => $module_info['name'],
        '#description' => $module_info['description'],
        '#open' => TRUE,
      ];
      $form['cohesion']['api_key'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('API key'),
        '#default_value' => $this->config('cohesion.settings')->get('api_key'),
      ];
      $form['cohesion']['agency_key'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Agency key'),
        '#default_value' => $this->config('cohesion.settings')->get('organization_key'),
      ];
      $form['cohesion']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => 'Save',
        '#button_type' => 'primary',
      ];
      $form['cohesion']['actions']['advanced'] = [
        '#markup' => $this->linkGenerator->generate(
          'Advanced',
          Url::fromRoute('cohesion.configuration.account_settings')
        ),
        '#prefix' => '<span class= "button advanced-button">',
        '#suffix' => "</span>",
      ];

      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cohesion_api_key = $form_state->getValue(['api_key']);
    $cohesion_agency_key = $form_state->getValue(['agency_key']);
    $this->configFactory->getEditable('cohesion.settings')->set('api_key', $cohesion_api_key)->save();
    $this->configFactory->getEditable('cohesion.settings')->set('organization_key', $cohesion_agency_key)->save();
    $this->messenger()->addStatus('The configuration options have been saved.');
  }

}
