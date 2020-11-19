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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
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

    // Getting current user role.
    $currentUserRoles = $this->account->getRoles();
    $shortcutObj = [];

    if (!in_array('administrator', $currentUserRoles)) {
      $currentUserId = $this->account->id();
      $currentUsername = $this->account->getUsername();
      $currentShortcutset = $this->entityTypeManager
        ->getStorage('shortcut_set')
        ->loadByProperties(['id' => 'shortcut_set_' . $currentUsername . $currentUserId]);

      // Getting current user shortcut set.
      $currentuserShortcutsetId = $this->entityTypeManager
        ->getListBuilder('shortcut_set');

      if (!$currentShortcutset) {
        // Creating default shortcut set for new user.
        $set = $currentuserShortcutsetId->getStorage()->create([
          'id' => 'shortcut_set_' . $currentUsername . $currentUserId,
          'label' => 'Default Shortcut Set for ' . $currentUsername,
        ]);
        $set->save();
      }

      // Loading current user shortcuts for display.
      $currentUserShortcuts = $this->entityTypeManager
        ->getStorage('shortcut')
        ->loadByProperties(['shortcut_set' => 'shortcut_set_' . $currentUsername . $currentUserId]);

      // Building Add link URL.
      $addShortcutUrl = Url::fromRoute('ezcontent_publish.shortcut.link_add', ['shortcut_set' => 'shortcut_set_' . $currentUsername . $currentUserId]);
      $addShortcutLink = Link::fromTextAndUrl(t('Add Shortcut'), $addShortcutUrl);
      $addShortcutLink = $addShortcutLink->toRenderable();

      // Building Operation link URL.
      $operationShortcutUrl = Url::fromRoute('ezcontent_publish.shortcut_set.customize_form', ['shortcut_set' => 'shortcut_set_' . $currentUsername . $currentUserId]);
      $operationShortcutLink = Link::fromTextAndUrl(t('Visit Shortcuts'), $operationShortcutUrl);
      $operationShortcutLink = $operationShortcutLink->toRenderable();

      foreach ($currentUserShortcuts as $key => $currentUserShortcutObj) {
        $shortcutTitle = $currentUserShortcutObj->get('title')->getValue()[0]['value'];
        $shortcutLink = $currentUserShortcutObj->get('link')->getValue()[0]['uri'];
        $shortcutObj[0][$shortcutTitle] = $shortcutLink;
      }

    }
    else {
      $defaultShortcuts = $this->entityTypeManager
        ->getStorage('shortcut')
        ->loadByProperties(['shortcut_set' => 'default']);

      // Building Add link URL.
      $addShortcutUrl = Url::fromRoute('ezcontent_publish.shortcut.link_add', ['shortcut_set' => 'default']);
      $addShortcutLink = Link::fromTextAndUrl(t('Add Shortcut'), $addShortcutUrl);
      $addShortcutLink = $addShortcutLink->toRenderable();

      // Building Operation link URL.
      $operationShortcutUrl = Url::fromRoute('ezcontent_publish.shortcut_set.customize_form', ['shortcut_set' => 'default']);
      $operationShortcutLink = Link::fromTextAndUrl(t('Manage Shortcuts'), $operationShortcutUrl);
      $operationShortcutLink = $operationShortcutLink->toRenderable();

      foreach ($defaultShortcuts as $key => $defaultShortcutObj) {
        $defaultShortcutTitle = $defaultShortcutObj->get('title')->getValue()[0]['value'];
        $defaultShortcutLink = $defaultShortcutObj->get('link')->getValue()[0]['uri'];
        $shortcutObj[0][$defaultShortcutTitle] = $defaultShortcutLink;
      }
    }
    $shortcutObj[1]['add_link'] = $addShortcutLink;
    $shortcutObj[1]['manage_link'] = $operationShortcutLink;
    return [
      '#theme' => 'usershortcutblock_template',
      '#data' => $shortcutObj,
    ];
  }

}
