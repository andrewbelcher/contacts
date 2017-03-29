<?php

namespace Drupal\contacts\Controller;

use Drupal\Component\Utility\SortArray;
use Drupal\contacts\Ajax\ContactsTab;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\profile\Entity\Profile;
use Drupal\user\UserInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\Context;

/**
 * Controller routines for contact dashboard tabs and ajax.
 */
class DashboardController extends ControllerBase {

  /**
   * Return the AJAX command for changing tab.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user we are viewing.
   * @param string $subpage
   *   The subpage we want to view.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response commands.
   */
  public function ajaxTab(UserInterface $user, $subpage) {
    $tabs = self::getTabs($user);

    $url = Url::fromRoute('page_manager.page_view_contacts_dashboard_contact', [
      'user' => $user->id(),
      'subpage' => $subpage,
    ]);

    $content = [
      '#theme' => 'contacts_summary',
      '#content' => [],
    ];

    if (isset($tabs[$subpage])) {
      $content['#content']['block'] = $tabs[$subpage]['block']->build();
    }
    else {
      drupal_set_message($this->t('Page not found.'), 'warning');
    }

    // Prepend the content with system messages.
    $content['#content']['messages'] = [
      '#type' => 'status_messages',
      '#weight' => -99,
    ];

    // Create AJAX Response object.
    $response = new AjaxResponse();
    $response->addCommand(new ContactsTab($subpage, $url->toString()));
    $response->addCommand(new HtmlCommand('#contacts-tabs-content', $content));

    // Return ajax response.
    return $response;
  }

  /**
   * Get the contact dashboard tabs.
   *
   * @param \Drupal\user\UserInterface $contact
   *   The contact we want the tabs for. If none provided, all possible tabs
   *   will be returned. Access checks will not be performed if there is no
   *   contact.
   * @param \Drupal\Core\Session\AccountInterface|NULL $account
   *   The account who's access to check. If not provided, we use the currently
   *   logged in user.
   *
   * @return array
   *   The blocks for the tabs. Keys are the url stubs and each item contains:
   *   - title: The title for the tab/page.
   *   - weight: The weight for the tab.
   *   - block: \Drupal\Core\Block\BlockPluginInterface The block plugin context
   *     set, if $contact is provided.
   *   - manage links: An array of \Drupal\Core\Link for managing the tab. Keys
   *     are unique across all tabs.
   */
  public static function getTabs(UserInterface $contact = NULL, AccountInterface $account = NULL) {
    // Check that we have a user for access.
    if ($contact && !isset($account)) {
      $account = \Drupal::currentUser();
    }

    $user_context = new Context(new ContextDefinition('entity:user', 'Contact'), $contact);
    $active_context = self::getActiveContext();

    /* @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');
    $tabs = [];

    /*/ Summary tab.
    $block = $block_manager->createInstance('contact_summary_tab', [
      'label' => 'Summary',
    ]);
    $block->setContext('user', $user_context);
    $tabs['summary'] = [
      'title' => 'Summary',
      'weight' => -99,
      'block' => $block,
      'manage links' => [],
    ];*/

    // One for each of the profile types.
    /* @var \Drupal\profile\Entity\ProfileTypeInterface[] $profile_types */
    $profile_types = \Drupal::entityTypeManager()->getStorage('profile_type')->loadMultiple();
    $weight = 0;
    foreach ($profile_types as $profile_type) {
      // Get the URL stub for the profile.
      $url_stub = str_replace('_', '-', $profile_type->id());
      if (substr($url_stub, 0, 4) == 'crm-') {
        $url_stub = substr($url_stub, 4);
      }

      $block_config = [
        'label' => $profile_type->label(),
        'label_display' => 'hidden',
      ];

      // Get our profile.
      $profile = $contact ? $contact->{'profile_' . $profile_type->id()}->entity : NULL;

      // If we don't have a profile or can edit it, we'll show a form.
      if (!$contact) {
        $block = NULL;
      }
      elseif (!$profile || $profile->access('edit')) {
        // If we don't have a profile, check create access and create one.
        if (!$profile) {
          /* @var \Drupal\contacts\ContactsProfileAccessControlHandler $access_handler */
          $access_handler = \Drupal::entityTypeManager()->getAccessControlHandler('profile');
          if (!$access_handler->createAccess($profile_type->id()) || !$access_handler->checkType($profile_type, $contact)->isAllowed()) {
            // If we don't have access to create, there is nothing we can do.
            continue;
          }

          $profile = Profile::create([
            'type' => $profile_type->id(),
            'uid' => $contact->id(),
          ]);
        }

        $block = $block_manager->createInstance('contacts_entity_form', $block_config);
        $block->setContextValue('operation', 'crm_dashboard');

        $profile_context = new Context(new ContextDefinition('entity:profile'), $profile);
        $block->setContext('entity', $profile_context);
      }
      // Otherwise render a view block, if we have access.
      elseif ($profile->access('view')) {
        $block = $block_manager->createInstance('entity_view:profile', $block_config);
        $block->setContextValue('entity', $contact->{'profile_' . $profile_type->id()}->entity);
      }
      // Otherwise just skip this profile type.
      else {
        continue;
      }

      // If we have a block, check our profile's context.
      if ($block) {
        $allowed_contexts = array_filter($profile_type->getThirdPartySetting('contacts', 'context', []));
        if ($allowed_contexts && !in_array($active_context, $allowed_contexts)) {
          continue;
        }
      }

      $tabs[$url_stub] = [
        'title' => $profile_type->label(),
        'weight' => $weight++,
        'block' => $block,
        'manage links' => [],
      ];

      $tabs[$url_stub]['manage links'][$profile_type->id() . '.edit'] = Link::createFromRoute(
        t('Manage @label', ['@label' => $profile_type->label()]),
        'entity.profile_type.edit_form',
        ['profile_type' => $profile_type->id()],
        ['title_arguments' => ['@label' => 'profile_type:' . $profile_type->id()]]);
      $tabs[$url_stub]['manage links'][$profile_type->id() . '.fields'] = Link::createFromRoute(
        t('Manage @label fields', ['@label' => $profile_type->label()]),
        'entity.profile.field_ui_fields',
        ['profile_type' => $profile_type->id()],
        ['title_arguments' => ['@label' => 'profile_type:' . $profile_type->id()]]);
    }

    // Bump notes to the end.
    if (isset($tabs['notes'])) {
      $tabs['notes']['weight'] = 99;
    }

    // Web account tab.
    $block = $block_manager->createInstance('contacts_entity_form', [
      'label' => 'User account',
      'label_display' => 'hidden',
    ]);
    $block->setContextValue('operation', 'default');
    $contact_context = new Context(new ContextDefinition('entity:user'), $contact);
    $block->setContext('entity', $contact_context);
    $tabs['account'] = [
      'title' => 'User account',
      'weight' => -10,
      'block' => $block,
      'manage links' => [],
    ];

    // Run our access checks on the tabs.
    foreach ($tabs as $key => $tab) {
      if ($contact && !$tab['block']->access($account)) {
        unset($tabs[$key]);
      }
    }

    // Sort our tabs.
    uasort($tabs, [SortArray::class, 'sortByWeightElement']);

    // Return our tabs.
    return $tabs;
  }

  /**
   * Retreive the active context.
   *
   * @return string
   *   The role ID of the active context.
   */
  public static function getActiveContext() {
    /* @var \Drupal\user\PrivateTempStore $tempstore */
    $tempstore = \Drupal::service('user.private_tempstore')->get('contacts');
    $request = \Drupal::request();

    $active_context = $request->query->get('context');
    if ($active_context) {
      $tempstore->set('active_context', $request->query->get('context'));
    }

    if (!$active_context) {
      $active_context = $tempstore->get('active_context');

      if (!$active_context) {
        $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple(\Drupal::currentUser()->getRoles(TRUE));
        foreach ($roles as $role) {
          // Skip any CRM or admin roles.
          if (substr($role->id(), 0, 4) == 'crm_' || $role->isAdmin()) {
            continue;
          }

          $active_context = $role->id();
          $tempstore->set('active_context', $active_context);
          break;
        }
      }
    }

    return $active_context;
  }

}
