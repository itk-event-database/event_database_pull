<?php

namespace Drupal\event_database_pull\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Itk\EventDatabaseClient\Client;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use DateTimeZone;
use DateTime;

class EventController extends ControllerBase {
  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configuration;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configuration = $configFactory->get('event_database_pull.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * @return array
   */
  public function listAction(Request $request) {
    \Drupal::service('page_cache_kill_switch')->trigger();

    try {
      $client = $this->getClient();
      $query = $this->getListQuery($request);
      $result = $client->getEvents($query);
      $events = $result->getItems();

      // Add samedate variable to all occurences.
      $DateTimeZoneUTC = new DateTimeZone('UTC');

      foreach ($events as $event_key => $event) {
        foreach($event->getOccurrences() as $occurrence_key => $occurrence) {
          $startDate = DateTime::CreateFromFormat('Y-m-d\TH:i:s\+00:00', $occurrence->get('startDate'), $DateTimeZoneUTC);
          $endDate = DateTime::CreateFromFormat('Y-m-d\TH:i:s\+00:00', $occurrence->get('endDate'), $DateTimeZoneUTC);
          if ($startDate && $endDate) {
            $startdate = \Drupal::service('date.formatter')
              ->format($startDate->getTimestamp(), 'custom', 'dmY');
            $enddate = \Drupal::service('date.formatter')
              ->format($endDate->getTimestamp(), 'custom', 'dmY');
            $occurrence->samedate = ($startdate == $enddate);
          }
        }
      }

      // @todo Fix bug in pager. (Assumes wrong path)
      $view = array_filter([
        'first' => $result->getFirst(),
        'previous' => $result->getPrevious(),
        'next' => $result->getNext(),
        'last' => $result->getLast(),
      ]);

      return [
        '#theme' => 'event_database_pull_event_list',
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
   * @return \Itk\EventDatabaseClient\Client
   */
  private function getClient() {
    $url = $this->configuration->get('api.url');
    $username = $this->configuration->get('api.username');
    $password = $this->configuration->get('api.password');
    $client = new Client($url, $username, $password);

    return $client;
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return array|mixed
   */
  private function getListQuery(Request $request) {
    $query = [];

    $configQuery = $this->configuration->get('list.query');
    if ($configQuery) {
      try {
        $query = Yaml::parse($configQuery);
      } catch (ParseException $ex) {
        $query = [];
      }
    }

    $query = array_merge($query, $request->query->all());

    return $query;
  }

  /**
   * @param $id
   * @return array
   */
  public function showAction($id) {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $config = \Drupal::config('event_database_pull.settings');
    $url = $config->get('api.url');
    $username = $config->get('api.username');
    $password = $config->get('api.password');

    try {
      $client = new Client($url, $username, $password);
      $event = $client->readEvent($id);

      // Add samedate variable to all occurences.
      $DateTimeZoneUTC = new DateTimeZone('UTC');

      foreach($event->getOccurrences() as $occurrence_key => $occurrence) {
        $startDate = DateTime::CreateFromFormat('Y-m-d\TH:i:s\+00:00', $occurrence->get('startDate'), $DateTimeZoneUTC);
        $endDate = DateTime::CreateFromFormat('Y-m-d\TH:i:s\+00:00', $occurrence->get('endDate'), $DateTimeZoneUTC);
        if ($startDate && $endDate) {
          $startdate = \Drupal::service('date.formatter')
            ->format($startDate->getTimestamp(), 'custom', 'dmY');
          $enddate = \Drupal::service('date.formatter')
            ->format($endDate->getTimestamp(), 'custom', 'dmY');
          $occurrence->samedate = ($startdate == $enddate);
        }
      }

      return [
        '#theme' => 'event_database_pull_event_details',
        '#event' => $event,
        '#attached' => array(
          'library' => array(
            'event_database_pull/event_database_pull',
          ),
        ),
      ];
    } catch (\Exception $ex) {
      return [
        '#type' => 'markup',
        '#markup' => $ex->getMessage(),
      ];
    }
  }
}
