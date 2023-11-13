<?php

namespace Drupal\event_database_pull\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'event_database_tags_formatter'.
 *
 * @FieldFormatter(
 *   id = "event_database_tags_formatter",
 *   label = @Translation("Event database tags"),
 *   field_types = {
 *     "event_database_tags",
 *   }
 * )
 */
class EventDatabaseTagsFormatter extends FormatterBase {

  /**
   * View elements method.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   A list of field items.
   * @param string $langcode
   *   The langcode.
   *
   * @return array[]
   *   A list of elements.
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $output = [];
    foreach ($items as $delta => $item) {
      $build['type'] = [
        '#markup' => $item->type,
      ];
      $output[$delta] = $build;
    }
    return $output;
  }

}
