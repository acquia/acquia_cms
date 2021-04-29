<?php

namespace Drupal\acquia_cms_tour;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * SearchSolrBatchOperation is use for handling batch related task.
 */
class SearchSolrBatchOperation {
  use StringTranslationTrait;

  /**
   * Check status of acquia search solr index and show progress.
   */
  public static function checkSolrIndexStatus($notifications, &$context) {

    $cloud_service = \Drupal::service('acquia_cms_tour.cloud_service');
    do {
      $progress = $cloud_service->checkSolrIndexStatus($notifications);
      $context['message'] = $progress . '% completed of 100%';
      $context['results'] = time();
      sleep(10);
    } while ($progress);
  }

  /**
   * Batch Finish callback.
   */
  public function batchFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = 'Search solr index created successfully';
    }
    else {
      $message = $this->t('Finished with an error.');
    }
    \Drupal::messenger()->addMessage($message);
  }

}
