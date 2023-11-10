<?php

namespace Drupal\event_database_pull\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\options\Plugin\Field\FieldType\ListStringItem;

/**
 * Plugin implementation of the 'event_database_tags' field type.
 *
 * @FieldType(
 *   id = "event_database_tags",
 *   label = @Translation("Eventdatabase tags"),
 *   description = @Translation("Tags from eventdatabase"),
 *   default_widget = "event_database_tags_widget",
 *   default_formatter = "event_database_tags_formatter"
 * )
 */
class EventDatabaseTags extends ListStringItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    return $schema;
  }

}
