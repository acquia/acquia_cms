<?php

namespace Drupal\acquia_cms_headless_robustapi;

use Drupal\Core\Entity\EntityInterface;
use Drupal\next\NextSiteListBuilder;

/**
 * Defines a class to build a listing of next_site entities.
 *
 * @see \Drupal\next\Entity\NextSite
 * @todo Remove?
 */
class HeadlessRobustNextSiteListBuilder extends NextSiteListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['uuid'] = $this->t('UUID');
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Label');
    $header['base_url'] = $this->t('Base URL');
    $header['preview_secret'] = $this->t('Preview secret');
    $header['preview_url'] = $this->t('Preview URL');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\next\Entity\NextSiteInterface $entity */
    $row['uuid'] = $entity->uuid();
    $row['id'] = $entity->id();
    $row['label'] = $entity->label();
    $row['base_url'] = $entity->getBaseUrl();
    $row['preview_secret'] = $entity->getPreviewSecret();
    $row['preview_url'] = $entity->getPreviewUrl();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $operations['environment_variables'] = [
      'title' => $this->t('Environment variables'),
      'url' => $entity->toUrl('environment-variables'),
    ];

    return $operations;
  }

}
