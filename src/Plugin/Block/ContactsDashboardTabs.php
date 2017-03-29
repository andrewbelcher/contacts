<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\contacts\Controller\DashboardController;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a block to view contact dashboard tabs.
 *
 * @Block(
 *   id = "tabs",
 *   category = @Translation("Contacts"),
 *   deriver = "Drupal\contacts\Plugin\Deriver\ContactsDashboardTabsDeriver",
 * )
 */
class ContactsDashboardTabs extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The block controller.
   *
   * @var \Drupal\contacts\Controller\DashboardController
   */
  protected $blockController;

  /**
   * The block machine name.
   *
   * @var string
   */
  protected $subpage;

  /**
   * The contact user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Whether we are building tabs via AJAX.
   *
   * @var bool
   */
  protected $ajax;

  /**
   * The tabs for this contact.
   *
   * @var array
   */
  protected $tabs;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockController = new DashboardController();
    $this->ajax = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /* @var $entity \Drupal\Core\Entity\EntityInterface */
    $build = [];
    $this->subpage = $this->getContextValue('subpage');
    $this->user = $this->getContextValue('user');

    $this->buildTabs($build);
    $this->buildContent($build);

    return $build;
  }

  /**
   * Adds the tabs section to the renderable array for this block plugin.
   *
   * @param array $build
   *   Drupal renderable array being added to.
   */
  public function buildTabs(array &$build) {
    global $base_path;

    // @TODO Permission check.

    // Build content array.
    $content = [
      '#theme' => 'contacts_dash_tabs',
      '#weight' => -1,
      '#tabs' => [],
      '#attached' => [
        'library' => ['contacts/tabs'],
      ],
    ];

    foreach ($this->getTabs() as $url_stub => $tab) {
      $content['#tabs'][$url_stub] = [
        'text' => $tab['title'],
        'link' => Url::fromRoute('page_manager.page_view_contacts_dashboard_contact', [
          'user' => $this->user->id(),
          'subpage' => $url_stub,
        ]),
      ];

      // Swap links for AJAX request links.
      if ($this->ajax) {
        $content['#tabs'][$url_stub]['link_attributes']['data-ajax-url'] = Url::fromRoute('contacts.ajax_subpage', [
          'user' => $this->user->id(),
          'subpage' => $url_stub,
        ])->toString();
        $content['#tabs'][$url_stub]['link_attributes']['class'][] = 'use-ajax';
        $content['#tabs'][$url_stub]['link_attributes']['data-ajax-progress'] = 'fullscreen';
      }
    }

    // Add subpage class to current tab.
    $content['#tabs'][$this->subpage]['attributes']['class'][] = 'is-active';
    $content['#tabs'][$this->subpage]['link_attributes']['class'][] = 'is-active';

    $build['tabs'] = $content;
  }

  /**
   * Adds the content section to the renderable array for this block plugin.
   *
   * @param array $build
   *   Drupal renderable array being added to.
   */
  public function buildContent(array &$build) {
    $tabs = $this->getTabs();

    $build['content'] = [
      '#theme' => 'contacts_summary',
      '#content' => [],
      '#attributes' => [
        'id' => 'contacts-tabs-content',
        'class' => ['contacts-tabs-content', 'flex-fill'],
      ],
    ];

    if (isset($tabs[$this->subpage])) {
      $build['content']['#content']['block'] = $tabs[$this->subpage]['block']->build();
    }
    else {
      drupal_set_message($this->t('Page not found.'), 'warning');
    }

    $build['content']['#content']['messages'] = [
      '#type' => 'status_messages',
      '#weight' => -99,
    ];
  }

  /**
   * Get the tabs for this contact.
   *
   * @return array
   */
  protected function getTabs() {
    if (!isset($this->tabs)) {
      $this->tabs = DashboardController::getTabs($this->user);
    }
    return $this->tabs;
  }

}
