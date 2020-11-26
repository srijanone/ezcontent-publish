<?php

namespace Drupal\ezcontent_publish\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Cache\Cache;
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
   * @var AccountInterface $account
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
          'label' => $this->t('Default Shortcut Set for @currentUsername', ['@currentUsername' =>$currentUsername])
        ]);
        $set->save();
      }
      $shortcutObj = $this->getUserShortcutLinks('shortcut_set_' . $currentUsername . '_' . $currentUserId);
    } else {
      $shortcutObj = $this->getUserShortcutLinks('default');
    }
    return [
      '#theme' => 'user_shortcut_block_template',
      '#data' => $shortcutObj,
    ];
  }
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['ezcontent_user_shortcut_block']);
  }

  /**
   * Implements get_usershortcut_links().
   */
  function getUserShortcutLinks($currentUserShortcutSet) {
    // Building Add link URL.
    $addShortcutUrl = Url::fromRoute('ezcontent_publish.shortcut.link_add', array('shortcut_set' => $currentUserShortcutSet));
    $addShortcutLink = Link::fromTextAndUrl(t('Add Shortcut'), $addShortcutUrl)->toRenderable();
    // Building Operation link URL.
    $operationShortcutUrl = Url::fromRoute('ezcontent_publish.shortcut_set.customize_form', array('shortcut_set' => $currentUserShortcutSet));
    $operationShortcutLink = Link::fromTextAndUrl(t('Visit Shortcuts'), $operationShortcutUrl)->toRenderable();
    // Loading current user shortcuts for display.
    $currentUserShortcuts =  $this->entityTypeManager
      ->getStorage('shortcut')
      ->loadByProperties(['shortcut_set' => $currentUserShortcutSet]);
    foreach ($currentUserShortcuts as $key => $currentUserShortcutObj) {
      $shortcutTitle = $currentUserShortcutObj->get('title')->value;
      $shortcutLink =$currentUserShortcutObj->get('link')->first()->getUrl();
      $shortcutObj['links'][] = Link::fromTextAndUrl($shortcutTitle, $shortcutLink)->toRenderable();
    }
    $shortcutObj['operations']['add_link'] = $addShortcutLink;
    $shortcutObj['operations']['manage_link'] = $operationShortcutLink;

    return $shortcutObj;
  }

}
