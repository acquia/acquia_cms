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
 * Provides a form to configure the Recaptcha module.
 */
final class RecaptchaForm extends ConfigFormBase {


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
   * Constructs a new RecaptchaForm.
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
      $container->get('info_parser'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_recaptcha_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'recaptcha.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = FALSE;
    $module = 'recaptcha';
    if ($this->module_handler->moduleExists($module)) {
      $module_path = $this->module_handler->getModule($module)->getPathname();
      $module_info = $this->infoParser->parse($module_path);
      $form['recaptcha']['description'] = [
        '#type' => 'markup',
        '#markup' => '',
        '#prefix' => $module_info['name'],
        '#description' => $module_info['description'],
      ];

      $form['recaptcha']['site_key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Site key'),
        '#default_value' => $this->config('recaptcha.settings')->get('site_key'),
      ];
      $form['recaptcha']['secret_key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Secret key'),
        '#default_value' => $this->config('recaptcha.settings')->get('secret_key'),
      ];
      $form['recaptcha']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => 'Save',
        '#button_type' => 'primary',
      ];
      $form['recaptcha']['actions']['advanced'] = [
        '#markup' => $this->linkGenerator->generate(
          'Advanced',
          Url::fromRoute($module_info['configure'])
        ),
        '#prefix' => '<span class= "button advanced-button">',
        '#suffix' => "</span>",
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $recaptcha_site_key = $form_state->getValue(['site_key']);
    $recaptcha_secret_key = $form_state->getValue(['secret_key']);
    $this->config('recaptcha.settings')->set('site_key', $recaptcha_site_key)->save();
    $this->config('recaptcha.settings')->set('secret_key', $recaptcha_secret_key)->save();
    $this->messenger()->addStatus('The configuration options have been saved.');
  }

}
