<?php

namespace Drupal\contacts\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\contacts\Controller\DashboardController;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteMatchInterface;
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('current_route_match')
    );
  }

  /**
   * Construct the contacts dashboard configuration local tasks deriver.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!in_array($this->routeMatch->getRouteName(), $base_plugin_definition['appears_on'])) {
      return $this->derivatives;
    }

    $tabs = DashboardController::getTabs();

    foreach ($tabs as $tab) {
      /* @var \Drupal\Core\Link $link */
      foreach ($tab['manage links'] as $name => $link) {
        $id = $base_plugin_definition['id'] . '.' . $name;
        $this->derivatives[$id] = [
          'id' => $id,
          'title' => $link->getText(),
          'title_arguments' => $link->getUrl()->getOption('title_arguments'),
          'route_name' => $link->getUrl()->getRouteName(),
          'route_parameters' => $link->getUrl()->getRouteParameters(),
        ] + $base_plugin_definition + ['cache_tags' => []];

        // Add any title argument cache tags.
        foreach ($this->derivatives[$id]['title_arguments'] as $argument) {
          $this->derivatives[$id]['cache_tags'][] = $argument;
        }
      }
    }

    return $this->derivatives;
  }

}
