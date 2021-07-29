<?php

namespace Drupal\Tests\acquia_cms_search\ExistingSite;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\acquia_cms_common\ExistingSite\ContentTypeListTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the "all events" listing page.
 *
 * @group acquia_cms
 * @group acquia_cms_search
 * @group low_risk
 * @group pr
 * @group push
 */
class EventListTest extends ContentTypeListTestBase {

  /**
   * {@inheritdoc}
   */
  protected $nodeType = 'event';

  /**
   * {@inheritdoc}
   */
  protected function getView() : View {
    return View::load('events');
  }

  /**
   * {@inheritdoc}
   */
  protected function visitListPage($langcode = NULL) : void {
    $page = $langcode ? "/$langcode/events" : "/events";
    $this->drupalGet($page);
  }

  /**
   * {@inheritdoc}
   */
  protected function updateNodeFieldValues() : void {
    $time = time();
    $ids = parent::getQuery()->execute();

    // Update event nodes with random start date.
    $nodes = Node::loadMultiple($ids);
    foreach ($nodes as $node) {
      $node->set('field_event_start', date('Y-m-d\TH:i:s', $time + rand(10000, 999999)));
      $node->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuery() : QueryInterface {
    return parent::getQuery()->sort('field_event_start')->sort('title');
  }

}
