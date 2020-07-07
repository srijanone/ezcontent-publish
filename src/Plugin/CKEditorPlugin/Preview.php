<?php

namespace Drupal\ezcontent_publish\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "preview" plugin.
 *
 * @CKEditorPlugin(
 *   id = "preview",
 *   label = @Translation("CKEditor Preview"),
 * )
 */
class Preview extends CKEditorPluginBase {

  /**
   * Get path to library folder.
   */
  public function getLibraryPath() {
    if (\Drupal::hasService('library.libraries_directory_file_finder')) {
      $path = \Drupal::service('library.libraries_directory_file_finder')->find('preview');
    }
    elseif (\Drupal::moduleHandler()->moduleExists('libraries')) {
      $path = libraries_get_path('preview');
    }
    else {
      $path = 'libraries/preview';
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
      'Preview' => [
        'label' => $this->t('Preview'),
        'image' => $path . '/icons/preview.png',
      ],
    ];
  }

}
