<?php

namespace Drupal\event_database_pull\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\event_database_pull\Service\EventDatabase;
use League\Uri\Components\Query;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Event database occurrence controller.
 */
class OccurrenceController extends ControllerBase {
  /**
   * The event database service.
   *
   * @var EventDatabase
   */
  protected $eventDatabase;

  /**
   * A logger.
   *
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(EventDatabase $eventDatabase, LoggerInterface $logger) {
    $this->eventDatabase = $eventDatabase;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_database_pull.event_database'),
      $container->get('event_database_pull.logger')
    );
  }

  /**
   * List events.
   *
   * @return array
   *   The return value!
   */
  public function listAction(Request $request) {
    $form = \Drupal::formBuilder()->getForm('Drupal\event_database_pull\Form\SearchForm');
    $images = array();
    try {
      $query = $this->getListQuery($request);
      if(array_key_exists('search', $query)) {
        $query['event.name'] = $query['search'];
        unset($query['search']);
      }
      $result = $this->eventDatabase->getOccurrences($query);
      $occurrences = $result->getItems();
      $number_items = $result->get('totalItems');
      pager_default_initialize($number_items, 20);

      foreach ($occurrences as $key => $occurrence) {
        $images[$key] = array(
          '#theme' => 'imagecache_external',
          '#style_name' => 'medium',
          '#uri' => $occurrence->getEvent()->getImage(),
          '#alt' => $occurrence->getEvent()->getName(),
        );
      }
      $render = [];
      $render[] = [
        '#theme' => 'event_database_pull_occurrences_list',
        '#occurrences' => $occurrences,
        '#attached' => [
          'library' => [
            'event_database_pull/event_database_pull',
          ],
        ],
        '#cache' => [
          'max-age' => 0,
        ],
        '#images' => $images,
        '#searchBox' => $form,
        '#pager' => ['#type' => 'pager'],
      ];
      return $render;
    }
    catch (\Exception $ex) {
      return $this->errorAction($ex);
    }
  }

  /**
   * Show a occurrence details.
   *
   * @param string $id
   *   The occurrence id.
   *
   * @return array
   *   The return value!
   */
  public function showAction($id) {
    \Drupal::service('page_cache_kill_switch')->trigger();

    try {
      $occurrence = $this->eventDatabase->getOccurrence($id);
      $image = array(
        '#theme' => 'imagecache_external',
        '#style_name' => 'large',
        '#uri' => $occurrence->getEvent()->getImage(),
        '#alt' => $occurrence->getEvent()->getName(),
      );

      return [
        '#theme' => 'event_database_pull_occurrence_details',
        '#occurrence' => $occurrence,
        '#attached' => [
          'library' => [
            'event_database_pull/event_database_pull',
          ],
        ],
        '#image' => $image
      ];
    }
    catch (\Exception $ex) {
      return $this->errorAction($ex);
    }
  }

  /**
   * Show an occurrence title
   *
   * @param string $id
   *   The occurrence id.
   * @return string
   *  The event title of the occurrence.
   */
  public function showTitle($id) {
    $occurrence = $this->eventDatabase->getOccurrence($id);

    return $occurrence->getEvent()->getName();
  }

  private function errorAction(\Exception $ex) {
    $this->logger->error($ex->getMessage());
    return [
      '#theme' => 'event_database_pull_error',
      '#message' => $ex->getMessage(),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
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
