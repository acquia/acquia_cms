<?php

namespace Drupal\acquia_cms_headless\Plugin\AcquiaCmsHeadless;

use Drupal\acquia_cms_tour\Form\AcquiaCMSDashboardBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the acquia_cms_headless.
 *
 * @AcquiaCmsHeadless(
 *   id = "headless_next_entity_types",
 *   label = @Translation("Acquia CMS Headless Next.js Entity Types"),
 *   weight = 5
 * )
 */
class HeadlessNextEntityTypes extends AcquiaCMSDashboardBase {
  /**
   * The state interface.
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
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'next';

  /**
   * {@inheritdoc}
   */
  public function __construct(StateInterface $state, ModuleHandlerInterface $module_handler, LinkGeneratorInterface $link_generator, InfoParserInterface $info_parser, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($state, $module_handler, $link_generator, $info_parser);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('module_handler'),
      $container->get('link_generator'),
      $container->get('info_parser'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_headless_next_entity_types';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['acquia_cms_headless.settings'];
  }

  /**
   * Gets Entity data.
   *
   * @return array|int
   *   Returns an array of entity ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntityData() {
    $header = $this->buildEntityHeader();
    $storage = $this->entityTypeManager->getStorage('next_entity_type_config');
    $query = $storage->getQuery();
    $query->tableSort($header);
    $query->pager(2);

    return $query->execute();
  }

  /**
   * Build the form table header.
   *
   * @return array
   *   Returns an array with table header data.
   */
  public function buildEntityHeader(): array {
    return [
      'entity_type' => [
        'data' => $this->t('Entity Type'),
        'specifier' => 'entity_type',
      ],
      'bundle' => [
        'data' => $this->t('Bundle'),
        'specifier' => 'bundle',
        'field' => 't.alpha',
        'sort' => 'asc',
      ],
      'site' => [
        'data' => $this->t('Site'),
        'specifier' => 'site',
      ],
    ];
  }

  /**
   * Builds rows for the Form Table.
   *
   * @return array
   *   Returns an array of rows.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function buildEntityRows(): array {
    // @todo Add operations links.
    $rows = [];
    $next_entities = $this->getEntityData();
    $next_type_storage = $this->entityTypeManager->getStorage('next_entity_type_config');
    $next_types = $next_type_storage->loadMultiple($next_entities);
    $next_sites = $this->entityTypeManager->getStorage('next_site');
    $node_types = $this->entityTypeManager->getStorage('node_type');

    foreach ($next_types as $next_type) {
      // Init some variables.
      $site_data = '';
      $sites = $next_type->getTypedData()->get('configuration')->getValue()['sites'];

      // The Next Entity Type id is formatted as EntityType.Bundle.  We need
      // to separate these values into something more usable.
      $entity_data = explode('.', $next_type->id());

      // Iterate through site data via referenced site id in order to get
      // labels, etc. from next sites.
      if (!empty($sites)) {
        foreach ($sites as $site) {
          $site_data = $next_sites->load($site)->label();
        }
      }

      // If the entity type is a node, get the entity type label.
      if (!empty($node_types)) {
        $bundle = $node_types->load($entity_data[1])->label();
      }
      // Else return a capitalized version of the bundle id.
      else {
        $bundle = ucwords($entity_data[1]);
      }

      // Match the data with the columns.
      $row = [
        'entity_type' => ucfirst($entity_data[0]),
        'bundle' => $bundle,
        'site' => "Site: $site_data",
      ];

      $rows[$next_type->uuid()] = $row;
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#tree'] = FALSE;
    $module = $this->module . '_entity_types';
    $header = $this->buildEntityHeader();
    $rows = $this->buildEntityRows();

    // Add prefix and suffix markup to implement a column layout.
    $form['#prefix'] = '<div class="layout-column layout-column--half">';
    $form['#suffix'] = '</div>';

    $form[$module] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Next.js Entity Types'),
      '#attributes' => [
        'class' => ['use-ajax'],
      ],
    ];

    $form[$module]['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No next.js entity types currently exist.'),
    ];

    $form[$module]['pager'] = [
      '#type' => 'pager',
      '#element' => 2,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set the config state.
    $this->setConfigurationState();
  }

  /**
   * {@inheritdoc}
   */
  public function ignoreConfig(array &$form, FormStateInterface $form_state) {
    $this->setConfigurationState();
  }

  /**
   * {@inheritdoc}
   */
  public function checkMinConfiguration(): bool {
    return TRUE;
  }

}
