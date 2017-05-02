<?php

namespace Drupal\contacts\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic local tasks for contact dashboard configuration.
 */
class ContactsDashboardConfigurationLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Construct the contacts dashboard configuration local tasks deriver.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!in_array($this->routeMatch->getRouteName(), $base_plugin_definition['appears_on'])) {
      return $this->derivatives;
    }

    // Show manage and edit links for each profile.
    $profile_types = $this->entityTypeManager->getStorage('profile_type')->loadMultiple();
    foreach ($profile_types as $profile_type) {
      $id = $base_plugin_definition['id'] . '.' . $profile_type->id() . '.edit';
      $this->derivatives[$id] = [
        'id' => $id,
        'title' => new TranslatableMarkup('Manage @label'),
        'title_arguments' => ['@label' => 'profile_type:' . $profile_type->id()],
        'route_name' => 'entity.profile_type.edit_form',
        'route_parameters' => ['profile_type' => $profile_type->id()],
      ] + $base_plugin_definition + ['cache_tags' => []];

      $id = $base_plugin_definition['id'] . '.' . $profile_type->id() . '.fields';
      $this->derivatives[$id] = [
        'id' => $id,
        'title' => new TranslatableMarkup('Manage @label fields'),
        'title_arguments' => ['@label' => 'profile_type:' . $profile_type->id()],
        'route_name' => 'entity.profile.field_ui_fields',
        'route_parameters' => ['profile_type' => $profile_type->id()],
      ] + $base_plugin_definition + ['cache_tags' => []];
    }

    return $this->derivatives;
  }

}
