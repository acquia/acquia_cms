<?php

namespace Drupal\Tests\acquia_cms_person\ExistingSite;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Tests\acquia_cms_common\ExistingSite\ContentTypeListTestBase;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the "all people" listing page.
 *
 * @group acquia_cms_person
 * @group acquia_cms
 * @group low_risk
 * @group pr
 * @group push
 */
#[Group('acquia_cms_person')]
#[Group('acquia_cms')]
#[Group('low_risk')]
#[Group('pr')]
#[Group('push')]
#[RunTestsInSeparateProcesses]
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
