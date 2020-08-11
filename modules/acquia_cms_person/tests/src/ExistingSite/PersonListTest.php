<?php

namespace Drupal\Tests\acquia_cms_person\ExistingSite;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Tests\acquia_cms_common\ExistingSite\ContentTypeListTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the "all people" listing page.
 *
 * @group acquia_cms_person
 * @group acquia_cms
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
  protected function visitListPage() : void {
    $this->drupalGet('/people');
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuery() : QueryInterface {
    return parent::getQuery()->sort('title');
  }

}
