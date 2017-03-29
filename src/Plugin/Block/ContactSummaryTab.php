<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Render\Markup;
use Drupal\profile\Entity\Profile;
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
      '#content' => [],
      '#attached' => [
        'library' => [
          'contacts/contacts',
          'contacts/demo.sortable',
        ],
      ],
    ];

    $contact = $this->getContextValue('user');

    // Main profile.
    $config = [
      'label_display' => 'visible',
      'label' => 'Summary',
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

    if (!empty($profile)) {
      $edit = \Drupal::request()->query->get('edit');
      /* @var \Drupal\Core\Block\BlockPluginInterface $plugin_block */
      $plugin_block = $this->pluginManagerBlock->createInstance($edit == 'summary' ? 'contacts_entity_form' : 'entity_view:profile', $config);
      $profile_context = new Context(new ContextDefinition('entity:profile'), $profile);
      $plugin_block->setContext('entity', $profile_context);
      $build['#content']['left'] = [
        '#theme' => 'block',
        '#attributes' => [],
        '#plugin_id' => $plugin_block->getPluginId(),
        '#base_plugin_id' => $plugin_block->getBaseId(),
        '#derivative_plugin_id' => $plugin_block->getDerivativeId(),
        '#configuration' => $plugin_block->getConfiguration(),
        'content' => $plugin_block->build(),
        '#edit_name' => 'summary',
      ];
    }

    // Notes.
    $config = [
      'label_display' => 'visible',
      'label' => 'Notes',
      'view_mode' => 'default',
    ];
    $profile = $contact->profile_crm_notes->entity;
    if (empty($profile)) {
      $profile = Profile::create([
        'type' => 'crm_notes',
        'uid' => $contact->id(),
      ]);
      $profile->save();
    }

    $edit = \Drupal::request()->query->get('edit');
    /* @var \Drupal\Core\Block\BlockPluginInterface $plugin_block */
    $plugin_block = $this->pluginManagerBlock->createInstance($edit == 'notes' ? 'contacts_entity_form' : 'entity_view:profile', $config);
    $profile_context = new Context(new ContextDefinition('entity:profile'), $profile);
    $plugin_block->setContext('entity', $profile_context);
    $build['#content']['right'] = [
      '#theme' => 'block',
      '#attributes' => [],
      '#plugin_id' => $plugin_block->getPluginId(),
      '#base_plugin_id' => $plugin_block->getBaseId(),
      '#derivative_plugin_id' => $plugin_block->getDerivativeId(),
      '#configuration' => $plugin_block->getConfiguration(),
      'content' => $plugin_block->build(),
      '#edit_name' => 'notes',
    ];

    return $build;
  }

}
