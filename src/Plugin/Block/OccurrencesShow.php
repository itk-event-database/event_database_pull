<?php

namespace Drupal\event_database_pull\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\event_database_pull\Service\EventDatabase;
use Itk\EventDatabaseClient\Collection;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Event database occurrences block.
 *
 * @Block(
 *   id = "occurrences_show",
 *   admin_label = @Translation("Occurrences show"),
 * )
 */
class OccurrencesShow extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {
  /**
   * The event database service.
   *
   * @var \Drupal\event_database_pull\Service\EventDatabase
   */
  private $eventDatabase;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDatabase $eventDatabase, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->eventDatabase = $eventDatabase;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_database_pull.event_database'),
      $container->get('event_database_pull.logger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Setup query array.
    $config = $this->getConfiguration();
    $query = $this->getQuery($config);

    try {
      $result = $this->eventDatabase->getOccurrences($query, $config['inherit_module_configuration']);
      $occurrences = $result->getItems();
      $view = $this->getView($result);
      $view['more_link'] = $config['show_all_occurrences_link'];
      
      return [
        '#theme' => 'event_database_occurrences_block',
        '#occurrences' => $occurrences,
        '#attached' => [
          'library' => [
            'event_database_pull/event_database_pull',
          ],
        ],
        '#view' => $view,
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
    catch (\Exception $ex) {
      $this->logger->error($ex->getMessage());
      return [
        '#theme' => 'event_database_block_error',
        '#message' => $ex->getMessage(),
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
  }

  /**
   * Get event database client query from configuration.
   *
   * @param array $config
   *   The configuration.
   *
   * @return array
   *   The query.
   */
  private function getQuery(array $config) {
    $query = [];

    if (isset($config['items_per_page'])) {
      $query['items_per_page'] = $config['items_per_page'];
    }

    if (isset($config['order'])) {
      $query['order[startDate]'] = $config['order'];
    }

    if (isset($config['query'])) {
      try {
        $value = Yaml::parse($config['query']);
        if (is_array($value)) {
          $query = array_merge($query, $value);
        }
      }
      catch (ParseException $ex) {
      }
    }

    return $query;
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

    $form['list'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Occurrences list'),
      '#tree' => TRUE,

      'inherit_module_configuration' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Inherit module event list configuration'),
        '#description' => $this->t('If set, the settings below will be added on top of the module configuration. Otherwise, the module configuration will be ignored.'),
        '#default_value' => !isset($config['inherit_module_configuration']) || $config['inherit_module_configuration'],
      ],

      'items_per_page' => [
        '#type' => 'number',
        '#title' => $this->t('Number of events'),
        '#description' => t('The number of events to display'),
        '#default_value' => isset($config['items_per_page']) ? $config['items_per_page'] : 5,
        '#size' => 5,
      ],

      'order' => [
        '#type' => 'radios',
        '#title' => $this->t('Order'),
        '#default_value' => isset($config['order']) ? $config['order'] : 'ASC',
        '#options' => [
          'ASC' => $this->t('Show first upcoming first'),
          'DESC' => $this->t('Show first upcoming last'),
        ],
      ],

      'query' => [
        '#type' => 'textarea',
        '#title' => $this->t('Query'),
        '#default_value' => $config['query'],
        '#description' => $this->t('Query parameters (YAML) to add to the Event database query'),
      ],

      'show_all_occurrences_link' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Show all occurrences link'),
        '#description' => $this->t('If set, shows a link to the occurrences page.'),
        '#default_value' => !isset($config['show_all_occurrences_link']) || $config['show_all_occurrences_link'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $settings = $form_state->getValues('list');
    $this->setConfigurationValue('inherit_module_configuration', $settings['list']['inherit_module_configuration']);
    $this->setConfigurationValue('items_per_page', $settings['list']['items_per_page']);
    $this->setConfigurationValue('order', $settings['list']['order']);
    $this->setConfigurationValue('query', $settings['list']['query']);
    $this->setConfigurationValue('show_all_occurrences_link', $settings['list']['show_all_occurrences_link']);
  }

}
