<?php

namespace Drupal\ezcontent_publish\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user_shortcut\Entity\UserShortcutSetStorageInterface;
use Drupal\user_shortcut\UserShortcutSetActiveMap;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;


/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "user_shortcut_block",
 *   admin_label = @Translation("User Shortcut Block"),
 * )
 */
class UserShortcutBlock extends BlockBase implements ContainerFactoryPluginInterface {

   /**
   * @var AccountInterface $account
   */
  protected $account;

    /**
   * The shortcut set storage.
   *
   * @var \Drupal\user_shortcut\Entity\UserShortcutSetStorageInterface
   */
  protected $shortcutSetStorage;

  /**
   * The user shortcut set active map registry.
   *
   * @var \Drupal\user_shortcut\UserShortcutSetActiveMap
   */
  private $activeMap;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $account, UserShortcutSetStorageInterface $shortcutSetStorage, UserShortcutSetActiveMap $activeMap) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->shortcutSetStorage = $shortcutSetStorage;
    $this->activeMap = $activeMap;
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
      $container->get('entity_type.manager')->getStorage('user_shortcut_set'),
      $container->get('user_shortcut.registry.active_map')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Getting current user details.
    $current_userid = \Drupal::currentUser()->id();
    $current_username = \Drupal::currentUser()->getUsername();

    // Getting current user shortcut set.
    $currentuser_shortcutset_id = \Drupal::entityTypeManager()->getListBuilder('user_shortcut_set')->renderForUser($current_userid)['table']['#rows'];

    if ($currentuser_shortcutset_id == NULL) {
      // Creating default shortcut set for new user.
      $set = $this->shortcutSetStorage->create([
        'name' => 'Default Shortcut Set for ' . $current_username,
        'user_id' => $current_userid,
      ]);
      $set->save();
      
      // Getting current user newly created shortcut set.
      $currentuser_shortcutset_id = \Drupal::entityTypeManager()->getListBuilder('user_shortcut_set')->renderForUser($current_userid)['table']['#rows'];

      // Getting core shortcuts.
      $core_shortcuts = \Drupal::entityTypeManager()->getStorage('shortcut')->loadMultiple();
      
      foreach ($core_shortcuts as $key => $shortcut_obj) {
        $coreshortcut_title = $shortcut_obj->get('title')->getValue()[0]['value'];
        $coreshortcut_link = $shortcut_obj->get('link')->getValue()[0]['uri'];

        // Copying core shorcuts to user shortcuts.
        $user_shortcuts = \Drupal::entityTypeManager()->getStorage('user_shortcut')->create([
          'title' => $coreshortcut_title,
          'link' => $coreshortcut_link,
          'user_shortcut_set' => array_key_first($currentuser_shortcutset_id)
          ]);

         $user_shortcuts->save();
      }
    } else {
        // Getting core shortcuts.
      $core_shortcuts = \Drupal::entityTypeManager()->getStorage('shortcut')->loadMultiple();
    }




//      $link = \Drupal::entityTypeManager()->getStorage('user_shortcut')->loadByProperties(["id"=>15]);
//  kint($link);die;
  
  //   $user_shortcuts = \Drupal::entityTypeManager()->getStorage('user_shortcut')->create([
  //   'title' => 'Dummy URL',
  //   'link' => 'internal:/',
  //   'user_shortcut_set' => 20
  //   ]);
  //   //kint($user_shortcuts);die;
  //  $user_shortcuts->save();
    
  
  // loadByProperties(['id' => 5]);
    //kint($user_shortcuts);die;


    // Creating HTML for all the links.
    $usershortcut_linkobj = \Drupal::entityTypeManager()->getStorage('user_shortcut')->loadMultiple();

    $core_shortcuts = \Drupal::entityTypeManager()->getStorage('shortcut')->loadMultiple();
    $render_html = '<div class="usershortcut-wrapper">';
    foreach ($core_shortcuts as $key => $shortcut_obj) {
      $shortcut_title = $shortcut_obj->get('title')->getValue()[0]['value'];
      $shortcut_link = $shortcut_obj->get('link')->getValue()[0]['uri'];
      $render_html .= '<div class="btn"><a href = "' . $shortcut_link . '">' . $shortcut_title . '</a></div>';
    }
    // $shorcutsets_withlink = \Drupal::entityTypeManager()->getStorage('user_shortcut_set')->loadByProperties(["id"=>array_key_first($currentuser_shortcutset_id)]);
    // $user_shortcutob = \Drupal::entityTypeManager()->getStorage('user_shortcut');
    // //$user_shortcutlinks = $user_shortcutob->getQuery()->condition('user_shortcut_set', 27)->execute();


    // kint($user_shortcutob);die;


    // $user_storage = \Drupal::service('entity_type.manager')->getStorage('user');

    // $ids = $user_storage->getQuery()
    //   ->condition('status', 1)
    //   ->condition('roles', 'moderator')
    //   ->execute();
    // $users = $user_storage->loadMultiple($ids);


   



  //   $render_html .= $this->t("<a href=':link'>Add a shortcut set.</a>", [':link' => Url::fromRoute('user_shortcut.user.link_add', ['user'=>\Drupal::currentUser()->id()])->toString(), 'user_shortcut_set'=>27]);
  //  // Url::fromRoute('user_shortcut.user.link_add', ['user'=> \Drupal::currentUser()->id(), 'user_shortcut_set']),
  //   //print render($link);
  
  
//    $shortcut_link = \Drupal::entityTypeManager()->getStorage('entity.user_shortcut_set.customize_form')->loadMultiple();
// // // kint('aaa');
//   kint($shortcut_link);die;

// $render_html .= $shortcut_link[array_key_first($currentuser_shortcutset_id)]->getUrlGenerator()->generateFromRoute('user_shortcut.user.link_add',
//         [
//           'user' => $current_userid,
//           'user_shortcut_set' => currentuser_shortcutset_id,
//         ]);

  $render_html .= '</div>';



    return [
      '#markup' => $render_html,
    ];
  }

}
