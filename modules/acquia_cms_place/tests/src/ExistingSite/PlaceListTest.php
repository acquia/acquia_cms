<?php

namespace Drupal\Tests\acquia_cms_place\ExistingSite;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Tests\acquia_cms_common\ExistingSite\ContentTypeListTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the "all places" listing page.
 *
 * @group acquia_cms
 * @group acquia_cms_place
 * @group low_risk
 * @group pr
 * @group push
 */
class PlaceListTest extends ContentTypeListTestBase {

  /**
   * {@inheritdoc}
   */
  protected $nodeType = 'place';

  /**
   * {@inheritdoc}
   */
  protected function getView() : View {
    return View::load('places');
  }

  /**
   * {@inheritdoc}
   */
  protected function visitListPage($langcode = NULL) : void {
    $page = $langcode ? "/$langcode/places" : "/places";
    $this->drupalGet($page);
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuery() : QueryInterface {
    return parent::getQuery()->sort('title');
  }

}
