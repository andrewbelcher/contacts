<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockManager;

/**
 * Block for the contact summary tab.
 *
 * @Block(
 *  id = "contact_summary_tab",
 *  admin_label = @Translation("Contact summary tab"),
 * )
 */
class ContactSummaryTab extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Block\BlockManager definition.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $pluginManagerBlock;

  /**
   * Construct the Contact Summary Tab block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockManager $plugin_manager_block) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginManagerBlock = $plugin_manager_block;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.block')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#theme' => 'contacts_summary',
      '#content' => [
        'right' => '<div><h2>Summary Operations</h2><p>This block contains a list of useful operations to perform on the contact.</p></div>',
      ],
      '#attached' => [
        'library' => ['contacts/contacts-dashboard'],
      ],
    ];

    $contact = $this->getContextValue('user');
    $config = [
      'label_display' => 'visible',
      'view_mode' => 'default',
    ];

    if ($contact->hasRole('crm_indiv')) {
      $config['label'] = 'Person Summary';
      $profile = $contact->profile_crm_indiv->entity;
    }
    elseif ($contact->hasRole('crm_org')) {
      $config['label'] = 'Organisation Summary';
      $profile = $contact->profile_crm_org->entity;
    }

    // Add our context and build it..
    if (!empty($profile)) {
      $plugin_block = $this->pluginManagerBlock->createInstance('entity_view:profile', $config);
      $plugin_block->setContextValue('entity', $profile);
      $build['#content']['left'] = $plugin_block->build();
    }

    return $build;
  }

}
