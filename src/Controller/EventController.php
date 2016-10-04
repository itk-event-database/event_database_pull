<?php

namespace Drupal\event_database_pull\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\event_database_pull\Service\EventDatabase;
use Itk\EventDatabaseClient\Collection;
use League\Uri\Components\Query;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Event database controller.
 */
class EventController extends ControllerBase {
  /**
   * The event database service.
   *
   * @var EventDatabase
   */
  protected $eventDatabase;

  /**
   * {@inheritdoc}
   */
  public function __construct(EventDatabase $eventDatabase) {
    $this->eventDatabase = $eventDatabase;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_database_pull.event_database')
    );
  }

  /**
   * List events.
   *
   * @return array
   *   The return value!
   */
  public function listAction(Request $request) {
    try {
      $query = $this->getListQuery($request);
      $result = $this->eventDatabase->getEvents($query);
      $events = $result->getItems();
      $view = $this->getView($result);

      return [
        '#theme' => 'event_database_pull_event_list',
        '#events' => $events,
        '#view' => $view,
        '#attached' => array(
          'library' => array(
            'event_database_pull/event_database_pull',
          ),
        ),
        '#cache' => [
          'max-age' => 0,
        ],
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
   * Show a event details.
   *
   * @param string $id
   *   The event id.
   *
   * @return array
   *   The return value!
   */
  public function showAction($id) {
    \Drupal::service('page_cache_kill_switch')->trigger();

    try {
      $event = $this->eventDatabase->getEvent($id);

      return [
        '#theme' => 'event_database_pull_event_details',
        '#event' => $event,
        '#attached' => [
          'library' => [
            'event_database_pull/event_database_pull',
          ],
        ],
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
   * Get list query from request.
   *
   * @return array
   *   The query.
   */
  private function getListQuery(Request $request) {
    $query = new Query($request->getQueryString());

    return $query->toArray();
  }

}
