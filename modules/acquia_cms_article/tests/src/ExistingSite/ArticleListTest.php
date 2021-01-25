<?php

namespace Drupal\Tests\acquia_cms_article\ExistingSite;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Tests\acquia_cms_common\ExistingSite\ContentTypeListTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the "all articles" listing page.
 *
 * @group acquia_cms_article
 * @group acquia_cms
 * @group risky
 */
class ArticleListTest extends ContentTypeListTestBase {

  /**
   * {@inheritdoc}
   */
  protected $nodeType = 'article';

  /**
   * {@inheritdoc}
   */
  protected function getView() : View {
    return View::load('articles');
  }

  /**
   * {@inheritdoc}
   */
  protected function visitListPage($langcode = NULL) : void {
    $page = $langcode ? "/$langcode/articles" : "/articles";
    $this->drupalGet($page);
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuery(): QueryInterface {
    return parent::getQuery()->sort('created', 'DESC');
  }

}
