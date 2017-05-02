<?php

namespace Drupal\contacts\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes local actions aware of the current user context.
 */
class ContactManageLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    $title = $this->pluginDefinition['title'];

    // See if we need to do a replacement.
    if (!empty($this->pluginDefinition['title_arguments']) && $title instanceof TranslatableMarkup) {
      $rebuild_title = FALSE;
      $args = $title->getArguments();

      // Loop over the title arguments and see if we have any replacements.
      foreach ($this->pluginDefinition['title_arguments'] as $key => $argument) {
        try {
          list($entity_type, $entity_id) = explode(':', $argument);
          $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
          if ($entity) {
            $rebuild_title = TRUE;
            $args[$key] = $entity->label();
          }
        }
        catch (\Exception $exception) {
        }
      }

      // If we're rebuilding a title, build it.
      if ($rebuild_title) {
        $title = new TranslatableMarkup($title->getUntranslatedString(), $args, $title->getOptions());
      }
    }

    return (string) $title;
  }

}
