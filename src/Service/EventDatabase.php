<?php

namespace Drupal\event_database_pull\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Itk\EventDatabaseClient\Client;
use Itk\EventDatabaseClient\Collection;
use Itk\EventDatabaseClient\Item\Event;
use Itk\EventDatabaseClient\Item\Occurrence;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Event database service.
 */
class EventDatabase {
  /**
   * The configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private ImmutableConfig $configuration;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configuration = $configFactory->get('event_database_pull.settings');
  }

  /**
   * Get events.
   *
   * @param array<string, mixed> $query
   *   The query to filter events by.
   * @param bool $mergeQuery
   *   Whether to merge query.
   *
   * @return \Itk\EventDatabaseClient\Collection
   *   The events.
   */
  public function getEvents(array $query, bool $mergeQuery = TRUE): Collection {
    $client = $this->getClient();
    $query = $this->getListQuery($query, $mergeQuery);
    $result = $client->getEvents($query);

    foreach ($result->getItems() as &$event) {
      $this->augment($event);
    }

    return $result;
  }

  /**
   * Get occurrences.
   *
   * @param array<string, mixed> $query
   *   The query to filter events by.
   * @param bool $mergeQuery
   *   Whether to merge query.
   *
   * @return \Itk\EventDatabaseClient\Collection
   *   The occurrences.
   */
  public function getOccurrences(array $query, bool $mergeQuery = TRUE): Collection {
    $client = $this->getClient();
    $query = $this->getOccurrencesListQuery($query, $mergeQuery);
    // Align Drupals pager to event database query.
    if (array_key_exists('page', $query)) {
      $query['page'] = $query['page'];
    }
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
  public function getOccurrence(string $id): Occurrence {
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
  public function getEvent(string $id): ?Event {
    $client = $this->getClient();
    $event = $client->readEvent($id);

    if ($this->configuration->get('item.generate_404_on_past_event')
      && $this->isPastEvent($event)) {
      return NULL;
    }

    $this->augment($event);

    return $event;
  }

  /**
   * Decide if an event is in the past.
   *
   * @param \Itk\EventDatabaseClient\Item\Event $event
   *   An event db event.
   *
   * @return bool
   *   Whether the event is in the past.
   *
   * @throws \Exception
   */
  private function isPastEvent(Event $event): bool {
    $now = new \DateTime('now', new \DateTimeZone('UTC'));
    $minEndTime = max(array_map(function ($occurrence) use ($now) {
      $endTime = $occurrence->getEndDate();

      return $endTime ? new \DateTime($endTime) : $now;
    }, $event->getOccurrences()));

    return $minEndTime < $now;
  }

  /**
   * Add some extra data to an event.
   *
   * @param \Itk\EventDatabaseClient\Item\Event $event
   *   The event.
   */
  private function augment(Event $event): void {
    // Add "same date" property to occurrences.
    foreach ($event->getOccurrences() as $occurrence) {
      $this->determineSameDate($occurrence);
    }
  }

  /**
   * Add some extra data to an occurrence.
   *
   * @param \Itk\EventDatabaseClient\Item\Occurrence $occurrence
   *   The occurrence.
   */
  private function augmentOccurrence(Occurrence $occurrence): void {
    // Add "same date" property to occurrences.
    $this->determineSameDate($occurrence);
  }

  /**
   * Get event database client.
   *
   * @return \Itk\EventDatabaseClient\Client
   *   The client.
   */
  private function getClient(): Client {
    $url = $this->configuration->get('api.url');
    $username = $this->configuration->get('api.username');
    $password = $this->configuration->get('api.password');

    return new Client($url, $username, $password);
  }

  /**
   * Get query for getting events.
   *
   * @param array<string, mixed> $userQuery
   *   The initial query.
   * @param bool $mergeQuery
   *   Whether to merge the query or not.
   *
   * @return array<string, mixed>
   *   The query;
   */
  private function getListQuery(array $userQuery, bool $mergeQuery): array {
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
   * @param array<string, mixed> $userQuery
   *   The initial query.
   * @param bool $mergeQuery
   *   Whether to merge the query query.
   *
   * @return array<string, mixed>
   *   The query.
   */
  private function getOccurrencesListQuery(array $userQuery, bool $mergeQuery): array {
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

  /**
   * Determine same date form start and end date on occurrence.
   *
   * @param \Itk\EventDatabaseClient\Item\Occurrence $occurrence
   *   The occurrence.
   */
  private function determineSameDate(Occurrence $occurrence): void {
    $startDate = $occurrence->get('startDate');
    $endDate = $occurrence->get('endDate');
    if ($startDate && $endDate) {
      $formattedStartDate = \DateTime::CreateFromFormat('Y-m-d\TH:i:s\+00:00',
        $startDate);
      $formattedEndDate = \DateTime::CreateFromFormat('Y-m-d\TH:i:s\+00:00',
        $endDate);
      $occurrence->set('samedate', $formattedStartDate == $formattedEndDate);
    }
    else {
      $occurrence->set('samedate', FALSE);
    }
  }

}
