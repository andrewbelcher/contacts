<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Url;

/**
 * Provides a block to view a custom text content.
 *
 * @Block(
 *   id = "contacts_entity_form",
 *   admin_label = @Translation("Entity Form"),
 *   category = @Translation("CRM"),
 *   context = {
 *    "operation" = @ContextDefinition("string", label = @Translation("Operation"), required = FALSE)
 *  }
 * )
 */
class ContactsEntityForm extends BlockBase implements ContextAwarePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->getContextValue('entity');
    $operation = $this->getContextValue('operation') ? $this->getContextValue('operation') : 'default';
    $form = \Drupal::getContainer()->get('entity.form_builder')
      ->getForm($entity, $operation);

    // See if we need to swap out our action.
    $route_match = \Drupal::routeMatch();
    if ($route_match->getRouteName() == 'contacts.ajax_subpage') {
      $action = Url::fromRoute('page_manager.page_view_contacts_dashboard_contact', $route_match->getRawParameters()->all());
      $form['#action'] = $action->toString();
    }

    return ['form' => $form];
  }

}
