<?php

namespace Drupal\contacts\Entity;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Contact tab entity.
 *
 * @ConfigEntityType(
 *   id = "contact_tab",
 *   label = @Translation("Contact tab"),
 *   handlers = {
 *     "list_builder" = "Drupal\contacts\ContactTabListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\contacts\Form\ContactTabForm",
 *       "delete" = "Drupal\contacts\Form\ContactTabDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "contact_tab",
 *   admin_permission = "administer contacts",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/contact-tabs/{contact_tab}",
 *     "edit-form" = "/admin/structure/contact-tabs/{contact_tab}/edit",
 *     "delete-form" = "/admin/structure/contact-tabs/{contact_tab}/delete",
 *     "collection" = "/admin/structure/contact-tabs"
 *   }
 * )
 */
class ContactTab extends ConfigEntityBase implements ContactTabInterface {

  /**
   * The tab ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The tab label.
   *
   * @var string
   */
  protected $label;

  /**
   * The tab path part.
   *
   * @var string
   */
  protected $path;

  /**
   * The contexts in which to show the tab.
   *
   * @var string[]
   */
  protected $contexts = [];

  /**
   * The relationships for the tab.
   *
   * An array including:
   *   - id: The relationship plugin id.
   *   - name: The name for the context.
   *   - source: The name of the source context.
   *
   * @var array
   */
  protected $relationships = [];

  /**
   * The block configuration.
   *
   * An array including:
   *   - id: The block plugin id.
   *   - context_mapping: Any relevant context mapping.
   *
   * @var array
   */
  protected $block;

  /**
   * The block plugin for this tab.
   *
   * @var \Drupal\Core\Block\BlockPluginInterface
   */
  protected $blockPlugin;

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($path) {
    $this->path = $path;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts() {
    return $this->contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function setContexts(array $contexts) {
    $this->contexts = $contexts;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function showInContext($context) {
    // No contexts means always show.
    if (empty($this->contexts)) {
      return TRUE;
    }

    // See if the context is one of ours.
    return in_array($context, $this->contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationships() {
    return $this->relationships;
  }

  /**
   * {@inheritdoc}
   */
  public function setRelationships(array $relationships) {
    $this->relationships = $relationships;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlock() {
    return $this->block;
  }

  /**
   * {@inheritdoc}
   */
  public function setBlock(array $block) {
    if (empty($block['id'])) {
      throw new \InvalidArgumentException('Missing required ID for block settings.');
    }
    $this->block = $block;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockPlugin() {
    return $this->blockPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setBlockPlugin(BlockPluginInterface $block) {
    $this->blockPlugin = $block;
    return $this;
  }

  /**
   * Load a tab by path.
   *
   * @param string $path
   *   The path to load by.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   The entity for the given path, or FALSE if none is found.
   */
  public static function loadByPath($path) {
    $entity_type_id = \Drupal::service('entity_type.repository')->getEntityTypeFromClass(get_called_class());
    $tabs = \Drupal::entityTypeManager()->getStorage($entity_type_id)->loadByProperties([
      'path' => $path,
    ]);
    return reset($tabs);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    $config = $this->getBlock();
    $block = \Drupal::service('plugin.manager.block')->createInstance($config['id'], $config);
    $this->calculatePluginDependencies($block);

    return $this;
  }

}
