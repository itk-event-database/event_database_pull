<?php

namespace Drupal\event_database_pull\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Itk\EventDatabaseClient\Collection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Itk\EventDatabaseClient\Client;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

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
      $view = $this->getView($result);

      return [
        '#theme' => 'event_database_pull_event_list',
        '#events' => $events,
        '#view' => $view,
      ];
    } catch (\Exception $ex) {
      return [
        '#type' => 'markup',
        '#markup' => $ex->getMessage(),
      ];
    }
  }

  public function showAction($id) {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $config = \Drupal::config('event_database_pull.settings');
    $url = $config->get('api.url');
    $username = $config->get('api.username');
    $password = $config->get('api.password');

    try {
      $client = new Client($url, $username, $password);
      $event = $client->readEvent($id);

      return [
        '#theme' => 'event_database_pull_event_details',
        '#event' => $event,
      ];
    } catch (\Exception $ex) {
      return [
        '#type' => 'markup',
        '#markup' => $ex->getMessage(),
      ];
    }
  }

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

  private function getClient() {
    $url = $this->configuration->get('api.url');
    $username = $this->configuration->get('api.username');
    $password = $this->configuration->get('api.password');
    $client = new Client($url, $username, $password);

    return $client;
  }

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

    if (empty($query)) {
      $query = [];
    }

    $query = array_merge($query, $request->query->all());

    return $query;
  }
}
