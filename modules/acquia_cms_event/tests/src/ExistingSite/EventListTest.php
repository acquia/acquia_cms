<?php

namespace Drupal\Tests\acquia_cms_event\ExistingSite;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Tests\acquia_cms_common\ExistingSite\ContentTypeListTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the "all events" listing page.
 *
 * @group acquia_cms
 * @group acquia_cms_event
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
  protected function visitListPage() : void {
    $this->drupalGet('/events');
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuery() : QueryInterface {
    return parent::getQuery()->sort('title');
  }

}
