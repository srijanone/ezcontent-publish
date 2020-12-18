<?php

namespace Drupal\ezcontent_publish\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a block with a user wise shortcut links.
 *
 * @Block(
 *   id = "ezcontent_user_shortcut_block",
 *   admin_label = @Translation("EzContent User Shortcut Block"),
 * )
 */
class EzContentUserShortcutBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $account, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    // Getting current user role.
    $currentUserRoles = $this->account->getRoles();
    if ($this->account->hasPermission('ezcontent shortcut set creation') && $this->account->id() != '1') {
      if (!in_array('administrator', $currentUserRoles)) {
        $currentUserId = $this->account->id();
        $currentUsername = $this->account->getUsername();
        $currentShortcutset = $this->entityTypeManager
          ->getStorage('shortcut_set')
          ->loadByProperties(['id' => 'shortcut_set_' . $currentUsername . '_' . $currentUserId]);
        // Getting current user shortcut set.
        $shortcutSet = $this->entityTypeManager
          ->getListBuilder('shortcut_set');
        if (!$currentShortcutset) {
          // Creating default shortcut set for new user.
          $set = $shortcutSet->getStorage()->create([
            'id' => 'shortcut_set_' . $currentUsername . '_' . $currentUserId,
            'label' => $this->t('Default Shortcut Set for @currentUsername', ['@currentUsername' => $currentUsername]),
          ]);
          $set->save();
        }
        $shortcutSetId = 'shortcut_set_' . $currentUsername . '_' . $currentUserId;
        $shortcutObj = $this->getUserShortcutLinks($shortcutSetId);
      }
      else {
        $shortcutSetId = 'default';
        $shortcutObj = $this->getUserShortcutLinks($shortcutSetId);
      }

      $build = [
        '#theme' => 'user_shortcut_block_template',
        '#attached' => [
          'library' => [
            'core/drupal.dialog.ajax',
          ],
        ],
        '#data' => $shortcutObj,
        '#cache' => [
          'tags' => ['config:shortcut.set.' . $shortcutSetId],
        ],
      ];
      return $build;
    }
    return $build;
  }

  /**
   * Implements get_usershortcut_links().
   */
  public function getUserShortcutLinks($currentUserShortcutSet) {
    $currentUrl = ltrim(\Drupal::service('path.current')->getPath(), '/');
    // Building Add link URL.
    $addShortcutUrl = Url::fromRoute('ezcontent_publish.shortcut.link_add', ['shortcut_set' => $currentUserShortcutSet], ['query' => ['destination' => $currentUrl]]);
    $addShortcutLink = Link::fromTextAndUrl(t('Add Shortcut'), $addShortcutUrl)->toRenderable();
    // Building Operation link URL.
    $operationShortcutUrl = Url::fromRoute('ezcontent_publish.shortcut_set.customize_form', ['shortcut_set' => $currentUserShortcutSet]);
    $operationShortcutLink = Link::fromTextAndUrl(t('Visit Shortcuts'), $operationShortcutUrl)->toRenderable();
    // Loading current user shortcuts for display.
    $currentUserShortcuts = $this->entityTypeManager
      ->getStorage('shortcut')
      ->loadByProperties(['shortcut_set' => $currentUserShortcutSet]);
    foreach ($currentUserShortcuts as $key => $currentUserShortcutObj) {
      $shortcutTitle = $currentUserShortcutObj->get('title')->value;
      $shortcutLink = $currentUserShortcutObj->get('link')->first()->getUrl();
      $shortcutObj['links'][] = Link::fromTextAndUrl($shortcutTitle, $shortcutLink)->toRenderable();
    }
    $addShortcutLink['#attributes'] = [
      'class' => ['use-ajax'],
      'data-dialog-type' => 'dialog',
      'data-dialog-renderer' => 'off_canvas',
      'data-dialog-options' => json_encode([
        'width' => '50%',
      ]),
    ];
    $shortcutObj['operations']['add_link'] = $addShortcutLink;

    $operationShortcutLink['#attributes'] = [
      'class' => ['use-ajax'],
      'data-dialog-type' => 'dialog',
      'data-dialog-renderer' => 'off_canvas',
      'data-dialog-options' => json_encode([
        'width' => '40%',
      ]),
    ];
    $shortcutObj['operations']['manage_link'] = $operationShortcutLink;

    return $shortcutObj;
  }

}
