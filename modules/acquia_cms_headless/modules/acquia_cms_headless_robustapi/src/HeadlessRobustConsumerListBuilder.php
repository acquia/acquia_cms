<?php

namespace Drupal\acquia_cms_headless_robustapi;

use Drupal\consumers\ConsumerListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Entity\User;

/**
 * Defines a class to build a listing of Access Token entities.
 *
 * @todo Remove?
 */
class HeadlessRobustConsumerListBuilder extends ConsumerListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['uuid'] = $this->t('UUID');
    $header['label'] = $this->t('Label');
    $header['description'] = $this->t('Description');
    $header['secret'] = $this->t('New Secret');
    $header['user_id'] = $this->t('User');
    // $context = ['type' => 'header'];
    $header = $header + parent::buildHeader();
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $user_storage = \Drupal::service('entity_type.manager')->getStorage('user');
    $result = $user_storage->getQuery();
    $uids = $result
      ->condition('status', '1')
      ->execute();
    $uids = array_keys(['user']);
    // THIS IS YOUR ARRAY OF UIDS.
    $users = User::loadMultiple($uids);
    // EXTRA CODE.
    foreach ($users as $user) {
      $name = $user->name;
    }
    $row['uuid'] = $entity->uuid();
    $row['label'] = $entity->toLink();
    $row['description'] = $entity->get('description')->value;
    $row['secret'] = $entity->get('secret')->value;
    // Stuck here.
    $row['user_id'] = $name->name;
    $ops = [
      '#type' => 'operations',
      '#links' => [
        [
          'title' => $this->t('Make Default'),
          'url' => $entity->toUrl('make-default-form', [
            'query' => $this->getDestinationArray(),
          ]),
        ],
      ],
    ];
    $row['is_default'] = $entity->get('is_default')->value
      ? ['data' => $this->t('Default')]
      : ['data' => $ops];

    // $context = ['type' => 'row', 'entity' => $entity];
    $row = $row + parent::buildRow($entity);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    if (
      $entity->access('update') &&
      $entity->hasLinkTemplate('make-default-form') &&
      !$entity->get('is_default')->value
    ) {
      $operations['make-default'] = [
        'title' => $this->t('Make Default'),
        'weight' => 10,
        'url' => $this->ensureDestination($entity->toUrl('make-default-form')),
      ];
    }
    return $operations;
  }

}
