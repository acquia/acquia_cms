<?php

namespace Drupal\acquia_cms_tour;

use Drupal\acquia_cms_search\Facade\AcquiaSearchFacade;
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
    $count = 0;
    do {
      $count++;
      $progress = $cloud_service->checkSolrIndexStatus($notifications);
      $context['message'] = 'Search index creation in progress, please wait...';
      $context['results'] = $count;
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
      // Once index is created and active let's switch
      // the index to use Solr Server.
      \Drupal::classResolver(AcquiaSearchFacade::class)->submitSettingsForm();
    }
    else {
      \Drupal::messenger()->addMessage($this->t('Finished with an error.'));
    }
  }

}
