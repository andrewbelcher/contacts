<?php

namespace Drupal\contacts\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\profile\Entity\ProfileTypeInterface;
use Drupal\user\UserInterface;

/**
 * Checks access to profiles, respecting the type of user we're adding for.
 */
class ContactProfileAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ContactProfileAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function access(ProfileTypeInterface $profile_type, UserInterface $user) {
    /* @var \Drupal\contacts\ContactsProfileAccessControlHandler $access_control_handler */
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('profile');
    return $access_control_handler->checkType($profile_type, $user);
  }

}
