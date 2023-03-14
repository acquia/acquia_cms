<?php

namespace Drupal\acquia_cms_common\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event that is fired after config import/export.
 */
class PostConfigEvent extends Event {

  /**
   * Post config import event.
   */
  const ACMS_POST_CONFIG_IMPORT = 'acms_post_config_import';

  /**
   * Post config export event.
   */
  const ACMS_POST_CONFIG_EXPORT = 'acms_post_config_export';

}
