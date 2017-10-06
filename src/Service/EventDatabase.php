<?php

namespace Drupal\event_database_pull\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Itk\EventDatabaseClient\Client;
use Itk\EventDatabaseClient\Item\Event;
use Itk\EventDatabaseClient\Item\Occurrence;
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
  public function getEvents(array $query, $mergeQuery = TRUE) {
    $client = $this->getClient();
    $query = $this->getListQuery($query, $mergeQuery);
    $result = $client->getEvents($query);

    foreach ($result->getItems() as &$event) {
      $this->augment($event);
    }

    return $result;
  }

  /**
   * Get occurences.
   *
   * @param array $query
   *   The query to filter events by.
   *
   * @return \Itk\EventDatabaseClient\Collection
   *   The occurrences.
   */
  public function getOccurrences(array $query, $mergeQuery = TRUE) {
    $client = $this->getClient();
    $query = $this->getOccurrencesListQuery($query, $mergeQuery);
    $result = $client->getOccurrences($query);

    foreach ($result->getItems() as &$occurrence) {
      $this->augmentOccurrence($occurrence);
    }

    return $result;
  }

  /**
   * Get an occurrence details.
   *
   * @param string $id
   *   The event id.
   *
   * @return \Itk\EventDatabaseClient\Item\Occurrence
   *   The event.
   */
  public function getOccurrence($id) {
    $client = $this->getClient();
    $occurrence = $client->readOccurrence($id);

    $this->augmentOccurrence($occurrence);

    return $occurrence;
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
   * Add some extra data to an occurrence.
   *
   * @param Event $event
   *   The event.
   */
  private function augmentOccurrence(Occurrence $occurrence) {
    // Add "samedate" property to occurrences.
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
  private function getListQuery(array $userQuery, $mergeQuery) {
    $query = [];

    if ($mergeQuery) {
      $config = $this->configuration->get('list');

      if (isset($config['items_per_page'])) {
        $query['items_per_page'] = $config['items_per_page'];
      }

      if (isset($config['order'])) {
        $query['order[occurrences.startDate]'] = $config['order'];
      }

      if (isset($config['query'])) {
        try {
          $configQuery = Yaml::parse($config['query']);
          if (is_array($configQuery)) {
            $query = array_merge($query, $configQuery);
          }
        }
        catch (ParseException $ex) {
        }
      }
    }
    if ($userQuery) {
      $query = array_merge($query, $userQuery);
    }

    return $query;
  }

  /**
   * Get query for getting occurrences.
   *
   * @param array $userQuery
   *   The initial query.
   *
   * @return array
   *   The query;
   */
  private function getOccurrencesListQuery(array $userQuery, $mergeQuery) {
    $query = [];

    if ($mergeQuery) {
      $config = $this->configuration->get('list');

      if (isset($config['items_per_page'])) {
        $query['items_per_page'] = $config['items_per_page'];
      }

      if (isset($config['order'])) {
        $query['order[startDate]'] = $config['order'];
      }

      if (isset($config['query_occurrences'])) {
        try {
          $configQuery = Yaml::parse($config['query_occurrences']);
          if (is_array($configQuery)) {
            $query = array_merge($query, $configQuery);
          }
        }
        catch (ParseException $ex) {
        }
      }
    }
    if ($userQuery) {
      $query = array_merge($query, $userQuery);
    }

    return $query;
  }

}
