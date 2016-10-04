<?php

namespace Drupal\event_database_pull\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\event_database_pull\Service\EventDatabase;
use League\Uri\Components\Query;
use Itk\EventDatabaseClient\Collection;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Event database block.
 *
 * @Block(
 *   id = "event_database_show",
 *   admin_label = @Translation("Event database show"),
 * )
 */
class EventDatabaseShow extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {
  /**
   * The event database service.
   *
   * @var \Drupal\event_database_pull\Service\EventDatabase
   */
  private $eventDatabase;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDatabase $eventDatabase) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->eventDatabase = $eventDatabase;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_database_pull.event_database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Setup query array.
    $blockConfig = $this->getConfiguration();

    $userQuery = new Query($blockConfig['query']);
    $query = $userQuery->toArray();

    if (!empty($blockConfig['count'])) {
      $query['items_per_page'] = $blockConfig['count'];
    }

    if (!empty($blockConfig['order'])) {
      $query['order[occurrences.startDate]'] = $blockConfig['order'];
    }

    try {
      $result = $this->eventDatabase->getEvents($query);
      $events = $result->getItems();
      $view = $this->getView($result);

      return [
        '#theme' => 'event_database_block',
        '#events' => $events,
        '#attached' => array(
          'library' => array(
            'event_database_pull/event_database_pull',
          ),
        ),
        '#view' => $view,
        '#cache' => array(
          'max-age' => 0,
        ),
      ];
    }
    catch (\Exception $ex) {
      return [
        '#type' => 'markup',
        '#markup' => $ex->getMessage(),
      ];
    }
  }

  /**
   * Get paging view for a collection of events.
   *
   * @param \Itk\EventDatabaseClient\Collection $collection
   *   The collection.
   *
   * @return array
   *   The view.
   */
  private function getView(Collection $collection) {
    $view = [];

    foreach (['first', 'previous', 'next', 'last'] as $key) {
      $url = $collection->get($key);
      if ($url) {
        $info = parse_url($url);
        if (!empty($info['query'])) {
          parse_str($info['query'], $query);
          $view[$key] = Url::fromRoute('event_database_pull.events_list', $query);
        }
      }
    }

    return $view;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $form['event_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Display settings'),
      '#open' => TRUE,
    );

    $form['event_settings']['query'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Query'),
      '#description' => t('A query string'),
      '#default_value' => isset($config['query']) ? $config['query'] : '',
      '#size' => 100,
    );

    $form['event_settings']['number_of_events'] = array(
      '#type' => 'number',
      '#title' => $this->t('Number of events'),
      '#description' => t('The number of events to display in the block'),
      '#default_value' => isset($config['count']) ? $config['count'] : 5,
      '#size' => 5,
    );

    $form['event_settings']['order'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Order'),
      '#default_value' => isset($config['order']) ? $config['order'] : 'asc',
      '#options' => array(
        'asc' => $this->t('Show first upcoming first'),
        'desc' => $this->t('Show first upcoming last'),
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $block_settings = $form_state->getValues('event_settings');
    $this->setConfigurationValue('query', $block_settings['event_settings']['query']);
    $this->setConfigurationValue('count', $block_settings['event_settings']['number_of_events']);
    $this->setConfigurationValue('order', $block_settings['event_settings']['order']);
  }

}
