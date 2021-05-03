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
  public static function checkSolrIndexStatus($start_time, $end_time, $notifications, &$context) {

    $cloud_service = \Drupal::service('acquia_cms_tour.cloud_service');
    // We assume that solr index will be created and ready in maximum 3 min,
    // hence we have set this batch to run maximum upto 3 min
    // or as early as api responses true.
    if (empty($context['sandbox'])) {
      $context['sandbox'] = [];
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = 60;
      $context['message'] = 'Creating Index. Please wait...';
    }
    else {
      $context['message'] = "Search Index created successfully. Please wait, while it's being ready.";
    }

    $context['sandbox']['progress'] += 3;
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $finished = $cloud_service->checkSolrIndexStatus($notifications);
      if (!$finished) {
        // Here we are waiting for 3sec after each call.,
        // thi will become 60x3 = 180 i.e 3min.
        sleep(3);
        $now = time();
        $maxTime = $end_time - $start_time;
        $timeElapsed = $end_time - $now;
        $finished = floor((($maxTime - $timeElapsed) * 100) / $maxTime) / 100;
      }
      $context['finished'] = round($finished, 2);
    }
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
