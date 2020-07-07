<?php

namespace Drupal\ezcontent_publish_layoutmanager\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "layoutmanager" plugin.
 *
 * @CKEditorPlugin(
 *   id = "layoutmanager",
 *   label = @Translation("CKEditor Layoutmanager"),
 * )
 */
class Layoutmanager extends CKEditorPluginBase {

  /**
   * Get path to library folder.
   */
  public function getLibraryPath() {
    if (\Drupal::hasService('library.libraries_directory_file_finder')) {
      $path = \Drupal::service('library.libraries_directory_file_finder')->find('layoutmanager');
    }
    elseif (\Drupal::moduleHandler()->moduleExists('libraries')) {
      $path = libraries_get_path('layoutmanager');
    }
    else {
      $path = 'libraries/layoutmanager';
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return ['basewidget'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->getLibraryPath() . '/plugin.js';
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
    $path = $this->getLibraryPath();
    return [
      'AddLayout' => [
        'label' => $this->t('Layout Manager'),
        'image' => $path . '/icons/addlayout.png',
      ],
    ];
  }

}
