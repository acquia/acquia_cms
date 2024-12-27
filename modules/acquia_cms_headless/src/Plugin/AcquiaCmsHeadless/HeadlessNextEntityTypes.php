<?php

namespace Drupal\acquia_cms_headless\Plugin\AcquiaCmsHeadless;

use Drupal\acquia_cms_headless\Service\StarterkitNextjsService;
use Drupal\acquia_cms_tour\Form\AcquiaCmsDashboardBase;
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
 *   id = "headless_next_entity_types",
 *   label = @Translation("Acquia CMS Headless Next.js Entity Types"),
 *   weight = 5
 * )
 */
class HeadlessNextEntityTypes extends AcquiaCmsDashboardBase {

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
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->starterKitNextjsService = $container->get('acquia_cms_headless.starterkit_nextjs');

    return $instance;
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
    ];

    foreach ($entities as $entity) {
      $operation = [];
      foreach ($operationLinks as $key => $operationLink) {
        if ($entity->hasLinkTemplate($operationLink['route'])) {
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
    $entity_type = 'next_entity_type_config';
    $next_entities = $this->getEntityData();
    $next_type_storage = $this->entityTypeManager->getStorage('next_entity_type_config');
    $next_types = $next_type_storage->loadMultiple($next_entities);
    $next_sites = $this->entityTypeManager->getStorage('next_site');
    $node_types = $this->entityTypeManager->getStorage('node_type');
    $operations = $this->createOperationLinks($entity_type);
    // Call the dashboard destination service.
    $destination = $this->starterKitNextjsService->dashboardDestination();
    /** @var \Drupal\next\Entity\NextEntityTypeConfigInterface $next_type */
    foreach ($next_types as $next_type) {
      // Init some variables.
      $site_data = '';
      $sites = $next_type->getConfiguration()['sites'];

      // The Next Entity Type id is formatted as EntityType.Bundle.  We need
      // to separate these values into something more usable.
      $entity_data = explode('.', $next_type->id());

      // Iterate through site data via referenced site id in order to get
      // labels, etc. from next sites.
      if ($sites) {
        foreach ($sites as $site) {
          $site_data = $next_sites->load($site)->label();
        }
      }

      // If the entity type is a node, get the entity type label.
      if ($node_types && $entity_data[0] === 'node') {
        $node_type = $node_types->load($entity_data[1]);
        $bundle_label = $node_type->label();
        $bundle_uri = $node_type->toUrl('edit-form', $destination);
        $bundle = Link::fromTextAndUrl($bundle_label, $bundle_uri);
      }
      // Else return a capitalized version of the bundle id.
      else {
        $bundle = ucwords($entity_data[1]);
      }

      // Match the data with the columns.
      $row = [
        'entity_type' => Link::fromTextAndUrl(ucfirst($entity_data[0]), $operations[$next_type->id()]['edit']['url']),
        'bundle' => $bundle,
        'site' => "Site: $site_data",
        'operations' => [
          'data' => [
            '#type' => 'dropbutton',
            '#dropbutton_type' => 'small',
            '#links' => $operations[$next_type->id()],
          ],
        ],
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

    // Set the destination query array.
    $destination = $this->starterKitNextjsService->dashboardDestination();

    // Add prefix and suffix markup to implement a column layout.
    $form['#prefix'] = '<div class="layout-column layout-column--half">';
    $form['#suffix'] = '</div>';

    $form[$module] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Next.js Entity Types'),
      '#attributes' => [
        'class' => [],
      ],
    ];

    $form[$module]['admin_links'] = [
      '#type' => 'link',
      '#title' => 'Add Next.js entity type',
      '#url' => Url::fromUri('internal:/admin/config/services/next/entity-types/add', $destination),
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
