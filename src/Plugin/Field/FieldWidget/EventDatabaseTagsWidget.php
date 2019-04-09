<?php

/**
* @file
* Contains \Drupal\event_database_pull\Plugin\field\FieldWidget\EventDatabaseTagsWidget.
*/

namespace Drupal\event_database_pull\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;

/**
 * Plugin implementation of the 'EventDatabaseTagsWidget' widget.
 *
 * @FieldWidget(
 *   id = "event_database_tags_widget",
 *   label = @Translation("Event database tags"),
 *   field_types = {
 *     "event_database_tags"
 *   },
 *   multiple_values = TRUE
 * )
 */

class EventDatabaseTagsWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    
    $element += [
      '#type' => 'checkboxes',
      '#options' => $this->getOptions($items->getEntity()),
      '#default_value' => $this->getSelectedOptions($items),
      // Do not display a 'multiple' select box if there is only one option.
      '#multiple' => $this->multiple && count($this->options) > 1,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    $options = _event_database_pull_generate_options($entity);
    $this->options = $options;
    return $this->options;
  }
}
