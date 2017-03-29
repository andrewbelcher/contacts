<?php

namespace Drupal\contacts;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\profile\Entity\ProfileTypeInterface;
use Drupal\profile\ProfileAccessControlHandler;
use Drupal\user\UserInterface;

/**
 * Access control handler extension for the profile entity type.
 *
 * Intercepts access checks to make sure we don't work with profiles the user
 * shouldn't have.
 */
class ContactsProfileAccessControlHandler extends ProfileAccessControlHandler {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /* @var \Drupal\profile\Entity\ProfileInterface $entity */

    // Check the type is allowed for the owner.
    if ($owner = $entity->getOwner()) {
      $profile_type = $this->getEntityTypeManager()->getStorage('profile_type')->load($entity->bundle());
      $type_check = $this->checkType($profile_type, $owner);
      if (!$type_check->isAllowed()) {
        return $return_as_object ? $type_check : FALSE;
      }
    }

    // Pass on for regular access checks.
    return parent::access($entity, $operation, $account, $return_as_object);
  }

  /**
   * Checks whether the given profile type is allowed for the given user.
   *
   * @param \Drupal\profile\Entity\ProfileTypeInterface $profile_type
   *   The profile type.
   * @param \Drupal\user\UserInterface $user
   *   The user the profile is for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkType(ProfileTypeInterface $profile_type, UserInterface $user) {
    // The assumption is it is allowed unless something forbids and neutrals are
    // treated as forbids by \Drupal\Core\Access\AccessManager::check, so we
    // must return an allow.
    // @see https://www.drupal.org/node/2861074

    // Pull our third party settings from the profile and see if we need to
    // restrict access.
    $allowed_roles = $profile_type->getRoles();
    if (empty($allowed_roles)) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::allowedIf(array_intersect($allowed_roles, $user->getRoles()));
    }
  }

  /**
   * Get the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected function getEntityTypeManager() {
    if (!isset($this->entityTypeManager)) {
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }
    return $this->entityTypeManager;
  }

  /**
   * Sets the entity type manager for this handler.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

}
