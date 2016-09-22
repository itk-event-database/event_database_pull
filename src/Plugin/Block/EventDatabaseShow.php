<?php

namespace Drupal\event_database_pull\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Itk\EventDatabaseClient\Client;
use DateTimeZone;

/**
 * Provides hamburger menu
 *
 * @Block(
 *   id = "event_database_show",
 *   admin_label = @Translation("Event database show"),
 * )
 */
class EventDatabaseShow extends BlockBase implements BlockPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('event_database_pull.settings');
    $url = $config->get('api.url');
    $username = $config->get('api.username');
    $password = $config->get('api.password');

    // Setup query array.
    $blockConfig = $this->getConfiguration();
    parse_str($blockConfig['query'], $query_array);

    try {
      $client = new Client($url, $username, $password);
      $result = $client->getEvents($query_array);
      $events = $result->getItems();

      // Add samedate variable to all occurences.
      $DateTimeZoneUTC = new DateTimeZone('UTC');

      foreach ($events as $event_key => $event) {
        _event_database_pull_set_same_date($event, $DateTimeZoneUTC);
      }

      // @todo Fix bug in pager. (Assumes wrong path)
      $view = array_filter([
        'first' => $result->getFirst(),
        'previous' => $result->getPrevious(),
        'next' => $result->getNext(),
        'last' => $result->getLast(),
      ]);

      return [
        '#theme' => 'event_database_block',
        '#events' => $events,
        '#view' => $view,
        '#attached' => array(
          'library' => array(
            'event_database_pull/event_database_pull',
          ),
        ),
        '#cache' => array(
          'max-age' => 0,
        ),
      ];
    } catch (\Exception $ex) {
      return [
        '#type' => 'markup',
        '#markup' => $ex->getMessage(),
      ];
    }
  }


  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $form['query'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Query'),
      '#description' => t('A query string'),
      '#default_value' => isset($config['query']) ? $config['query'] : '',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('query', $form_state->getValue('query'));
  }
}
?>