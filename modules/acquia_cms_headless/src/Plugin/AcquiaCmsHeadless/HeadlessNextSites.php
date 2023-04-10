<?php

namespace Drupal\acquia_cms_headless\Plugin\AcquiaCmsHeadless;

use Drupal\acquia_cms_headless\Service\StarterkitNextjsService;
use Drupal\acquia_cms_tour\Form\AcquiaCmsDashboardBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the acquia_cms_headless.
 *
 * @AcquiaCmsHeadless(
 *   id = "headless_next_sites",
 *   label = @Translation("Acquia CMS Headless Next.js Sites List"),
 *   weight = 3
 * )
 */
class HeadlessNextSites extends AcquiaCmsDashboardBase {
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
   * Provides the database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Provides Starter Kit Next.js Service.
   *
   * @var \Drupal\acquia_cms_headless\Service\StarterkitNextjsService
   */
  protected $starterKitNextjsService;

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'next';

  /**
   * {@inheritdoc}
   */
  public function __construct(StateInterface $state, ModuleHandlerInterface $module_handler, LinkGeneratorInterface $link_generator, InfoParserInterface $info_parser, Connection $connection, EntityTypeManagerInterface $entity_type_manager, StarterkitNextjsService $starterKitNextjsService) {
    parent::__construct($state, $module_handler, $link_generator, $info_parser);
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->starterKitNextjsService = $starterKitNextjsService;
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
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('acquia_cms_headless.starterkit_nextjs')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_headless_next_sites';
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
    $storage = $this->entityTypeManager->getStorage('next_site');
    $query = $storage->getQuery();
    $query->tableSort($header);
    $query->pager(10);

    return $query->accessCheck(TRUE)->execute();
  }

  /**
   * Build the form table header.
   *
   * @return array
   *   Returns an array with table header data.
   */
  public function buildEntityHeader(): array {
    return [
      'id' => [
        'data' => $this->t('ID'),
        'specifier' => 'id',
        'field' => 't.alpha',
        'sort' => 'asc',
      ],
      'label' => [
        'data' => $this->t('Name'),
        'specifier' => 'label',
      ],
      'base_url' => [
        'data' => $this->t('Site URL'),
        'specifier' => 'base_url',
      ],
      $this->t('Operations'),
    ];
  }

  /**
   * A function that builds an array of entity operation links.
   *
   * @param string $entityType
   *   Accepts an entity type id string value.
   *
   * @return array
   *   Return an array of operation links.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function createOperationLinks(string $entityType): array {
    // Initialize the array that we will eventually return.
    $operations = [];

    // Set some service variables.
    $user_data = $this->getEntityData();
    $storage = $this->entityTypeManager;
    $entityStorage = $storage->getStorage($entityType);
    $entities = $entityStorage->loadMultiple($user_data);
    $destination = $this->starterKitNextjsService->dashboardDestination();

    // Set an array of URI Relationships that will be used to build the
    // operations links.
    $operationLinks = [
      'env_vars' => [
        'title' => $this->t('Environment variables'),
        'route' => 'environment-variables',
      ],
      'edit' => [
        'title' => $this->t('Edit'),
        'route' => 'edit-form',
      ],
      'delete' => [
        'title' => $this->t('Delete'),
        'route' => 'delete-form',
      ],
      'clone' => [
        'title' => $this->t('Clone'),
        'route' => 'clone-form',
      ],
      'preview_secret' => [
        'title' => $this->t('New preview secret'),
        'route' => 'acquia_cms_headless.generate_preview_secret',
      ],
    ];

    foreach ($entities as $entity) {
      $operation = [];
      foreach ($operationLinks as $key => $operationLink) {
        if ($key == 'preview_secret' || 'env_vars') {
          $route_name = ($key == 'preview_secret') ? $operationLink['route'] : $entity->toUrl($operationLink['route'])->getRouteName();
          $operation[$key] = [
            'url' => Url::fromRoute($route_name, [$entityType => $entity->id()], $destination),
            'title' => $operationLink['title'],
            'attributes' => [
              'class' => [
                'use-ajax',
              ],
              'data-dialog-options' => Json::encode([
                'minHeight' => 400,
                'width' => 912,
              ]),
              'data-dialog-type' => 'modal',
              'data-ajax-progress' => "fullscreen",
            ],
          ];
        }
        else {
          $route_name = $entity->toUrl($operationLink['route'])->getRouteName();
          $operation[$key] = [
            'url' => Url::fromRoute($route_name, [$entityType => $entity->id()], $destination),
            'title' => $operationLink['title'],
          ];
        }
      }

      $operations[$entity->id()] = $operation;
    }

    return $operations;
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
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function buildEntityRows(): array {
    $rows = [];
    $entity_type = 'next_site';
    $next_sites = $this->getEntityData();
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $sites = $storage->loadMultiple($next_sites);
    $operations = $this->createOperationLinks($entity_type);

    // Match the data with the columns.
    /** @var \Drupal\next\Entity\NextSiteInterface $site */
    foreach ($sites as $site) {
      $site_link = $site->getBaseUrl();
      $site_uri = Url::fromUri($site_link, ['external' => TRUE]);

      $row = [
        'id' => Link::fromTextAndUrl($site->id(), $operations[$site->id()]['edit']['url']),
        'label' => $site->label(),
        'base_url' => Link::fromTextAndUrl($site_link, $site_uri),
        'operations' => [
          'data' => [
            '#type' => 'dropbutton',
            '#dropbutton_type' => 'small',
            '#links' => $operations[$site->id()],
          ],
        ],
      ];

      $rows[$site->uuid()] = $row;
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#tree'] = FALSE;
    $module = $this->module . '_sites';
    $header = $this->buildEntityHeader();
    $rows = $this->buildEntityRows();

    // Set the destination query array.
    $destination = $this->starterKitNextjsService->dashboardDestination();

    // Add prefix and suffix markup to implement a column layout.
    $form['#prefix'] = '<div class="layout-column layout-column--half">';
    $form['#suffix'] = '</div>';

    $form[$module] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Next.js Sites'),
      '#attributes' => [
        'class' => [],
      ],
    ];

    $form[$module]['admin_links'] = [
      '#type' => 'link',
      '#dropbutton_type' => 'small',
      '#title' => 'Add Next.js site',
      '#url' => Url::fromRoute('entity.next_site.add_form', [], $destination),
      '#attributes' => [
        'class' => [
          'button',
          'button--action',
          'button--primary',
        ],
      ],
      '#prefix' => '<div class="headless-dashboard-admin-links">',
      '#suffix' => '</div>',
    ];

    $form[$module]['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No next.js sites currently exist.'),
    ];

    $form[$module]['pager'] = [
      '#type' => 'pager',
      '#element' => 0,
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
