<?php

namespace Drupal\event_database_pull\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Itk\EventDatabaseClient\Client;
use Itk\EventDatabaseClient\Item\Event;
use Symfony\Component\Yaml\Yaml;
use DateTime;

/**
 * Event database service.
 */
class EventDatabase {
  /**
   * The configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $configuration;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configuration = $configFactory->get('event_database_pull.settings');
  }

  /**
   * Get events.
   *
   * @param array $query
   *   The query to filter events by.
   *
   * @return \Itk\EventDatabaseClient\Collection
   *   The events.
   */
  public function getEvents(array $query) {
    $client = $this->getClient();
    $query = $this->getListQuery($query);
    $result = $client->getEvents($query);

    foreach ($result->getItems() as &$event) {
      $this->augment($event);
    }

    return $result;
  }

  /**
   * Get a event details.
   *
   * @param string $id
   *   The event id.
   *
   * @return \Itk\EventDatabaseClient\Item\Event
   *   The event.
   */
  public function getEvent($id) {
    $client = $this->getClient();
    $event = $client->readEvent($id);

    $this->augment($event);

    return $event;
  }

  /**
   * Add some extra data to an event.
   *
   * @param Event $event
   *   The event.
   */
  private function augment(Event $event) {
    // Add "samedate" property to occurrences.
    foreach ($event->getOccurrences() as $occurrence) {
      $startDate = $occurrence->get('startDate');
      $endDate = $occurrence->get('endDate');
      if ($startDate && $endDate) {
        $formattedStartDate = DateTime::CreateFromFormat('Y-m-d\TH:i:s\+00:00',
          $startDate);
        $formattedEndDate = DateTime::CreateFromFormat('Y-m-d\TH:i:s\+00:00',
          $endDate);
        $occurrence->set('samedate', $formattedStartDate == $formattedEndDate);
      }
      else {
        $occurrence->set('samedate', FALSE);
      }
    }
  }

  /**
   * Get event database client.
   *
   * @return \Itk\EventDatabaseClient\Client
   *   The client.
   */
  private function getClient() {
    $url = $this->configuration->get('api.url');
    $username = $this->configuration->get('api.username');
    $password = $this->configuration->get('api.password');
    $client = new Client($url, $username, $password);

    return $client;
  }

  /**
   * Get query for getting events.
   *
   * @param array $userQuery
   *   The initial query.
   *
   * @return array
   *   The query;
   */
  private function getListQuery(array $userQuery) {
    $query = [];

    $config = $this->configuration->get('list');

    if (isset($config['query'])) {
      try {
        $query = Yaml::parse($config['query']);
      }
      catch (ParseException $ex) {
      }
    }

    if (empty($query)) {
      $query = [];
    }

    if ($userQuery) {
      $query = array_merge($query, $userQuery);
    }

    return $query;
  }

}
