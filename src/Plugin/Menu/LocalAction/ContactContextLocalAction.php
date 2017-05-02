<?php

namespace Drupal\contacts\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Makes local actions aware of the current user context.
 */
class ContactContextLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);

    // Add our base role for our defaults.
    if ($user = $route_match->getParameter('user')) {
      /* @var \Drupal\decoupled_auth\DecoupledAuthUserInterface $user */
      if ($user->hasRole('crm_indiv')) {
        $options['query']['roles'] = ['crm_indiv'];
      }
      elseif ($user->hasRole('crm_org')) {
        $options['query']['roles'] = ['crm_org'];
      }
    }

    return $options;
  }

}
