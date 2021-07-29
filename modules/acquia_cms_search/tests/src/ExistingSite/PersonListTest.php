<?php

namespace Drupal\Tests\acquia_cms_search\ExistingSite;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Tests\acquia_cms_common\ExistingSite\ContentTypeListTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the "all people" listing page.
 *
 * @group acquia_cms_search
 * @group acquia_cms
 * @group low_risk
 * @group pr
 * @group push
 */
class PersonListTest extends ContentTypeListTestBase {

  /**
   * {@inheritdoc}
   */
  protected $nodeType = 'person';

  /**
   * {@inheritdoc}
   */
  protected function getView() : View {
    return View::load('people');
  }

  /**
   * {@inheritdoc}
   */
  protected function visitListPage($langcode = NULL) : void {
    $page = $langcode ? "/$langcode/people" : "/people";
    $this->drupalGet($page);
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuery() : QueryInterface {
    return parent::getQuery()->sort('title');
  }

}
