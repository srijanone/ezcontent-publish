<?php

namespace Drupal\ezcontent_publish_layoutmanager\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "basewidget" plugin.
 *
 * @CKEditorPlugin(
 *   id = "basewidget",
 *   label = @Translation("CKEditor Basewidget"),
 * )
 */
class Basewidget extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    $path = 'libraries/basewidget/plugin.js';
    if (\Drupal::moduleHandler()->moduleExists('libraries')) {
      $path = libraries_get_path('basewidget') . '/plugin.js';
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

}
