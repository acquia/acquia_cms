<?php

namespace Drupal\Tests\acquia_cms_common\FunctionalJavascript;

/**
 * Tests the CKEditor5 configuration shipped with Acquia CMS.
 *
 * @group acquia_cms
 * @group acquia_cms_common
 * @group medium_risk
 * @group push
 */
class Ckeditor5ConfigurationTest extends Ckeditor5ConfigurationTestBase {

  /**
   * {@inheritdoc}
   */
  public function test() {
    parent::test();
    $this->verifyTextAlignments();
  }

  /**
   * Verify the text alignment in CkEditor.
   */
  protected function verifyTextAlignments(): void {
    $this->getEditorButton('Text alignment')->click();
    $this->getEditorButton('Align center');
    $this->getEditorButton('Align right');
    $this->getEditorButton('Justify');
  }

  /**
   * Provides an array of CkEditor plugin names.
   *
   * @return string[]
   *   Returns an array of ckeditor5 enabled toolbar plugin.
   */
  protected function getEditorButtons(): array {
    return [
      "Bold",
      "Italic",
      "Underline",
      "Strikethrough",
      "Superscript",
      "Subscript",
      "Paragraph",
      "Block quote",
      "Code",
      "Source",
    ];
  }

}
