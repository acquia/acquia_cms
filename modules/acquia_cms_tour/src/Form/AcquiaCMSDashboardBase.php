<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Dashboard form implementation.
 */
abstract class AcquiaCMSDashboardBase extends ConfigFormBase implements AcquiaDashboardInterface {

  /**
   * Module name which is responsible for this form.
   *
   * @var string
   */
  protected $module;


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
   * Constructs a new AcquiaConnectorForm.
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
  public function isModuleEnabled() {
    if ($this->module_handler->moduleExists($this->module)) {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getModule() {
    return $this->module;
  }

  /**
   * Get human readable module name.
   */
  public function getModuleName() {
    return $this->module_handler->getName($this->module);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationState() {
    $state = $this->state->get($this->getStateName(), FALSE);
    if (!$state && $this->checkMinConfiguration()) {
      $state = TRUE;
      $this->setConfigurationState();
    }
    return $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateName() {
    return 'acms_' . $this->module . '_configured';
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigurationState($status = TRUE) {
    $this->state->set($this->getStateName(), $status);
  }

  /**
   * Check if the minimum require configuration are already in place or not.
   *
   * @return bool
   *   Returns the state of min required configurations.
   */
  abstract public function checkMinConfiguration();

}
