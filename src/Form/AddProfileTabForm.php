<?php

namespace Drupal\contacts\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\profile\Form\ProfileTypeForm;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form controller for the add profile tab form.
 */
class AddProfileTabForm extends ProfileTypeForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Constrcuts the add profile tab form.
   */
  public function __construct(RequestStack $request_stack) {
    $this->setRequestStack($request_stack);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['#title'] = $this->t('Add tab');

    // Adjust some defaults.
    $form['roles']['#default_value'] = $this->getRequest()->query->get('roles', []);

    // Hide all unwanted elements.
    $keep = ['label', 'id', 'actions'];
    foreach (Element::children($form) as $key) {
      if (!in_array($key, $keep)) {
        $form[$key]['#access'] = FALSE;
      }
    }

    $form['owner_permissions'] = [
      '#type' => 'checkbox',
      '#title' => t('Show on the user dashboard'),
      '#default_value' => TRUE,
    ];

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    // Save the permissions on the role.
    if ($form_state->getValue('owner_permissions')) {
      $type_id = $this->entity->id();
      $role = $this->entityTypeManager->getStorage('user_role')->load(RoleInterface::AUTHENTICATED_ID);
      $role->grantPermission("add own {$type_id} profile");
      $role->grantPermission("edit own {$type_id} profile");
      $role->grantPermission("view own {$type_id} profile");
      $role->save();
    }
  }

  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#access'] = FALSE;
    return $actions;
  }

}
