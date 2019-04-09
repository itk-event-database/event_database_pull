<?php

namespace Drupal\event_database_pull\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\event_database_pull\Service\EventDatabase;
use League\Uri\Components\Query;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

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
  public function listAction(Request $request, $page = NUll) {
    $route = \Drupal::routeMatch()->getRouteName();
    $form = \Drupal::formBuilder()->getForm('Drupal\event_database_pull\Form\SearchForm');
    $images = array();
    $query_array = [];

    try {
      $query = $this->getListQuery($request);
      // Add search query on search page.
      if ($route == 'event_database_pull.occurrences_list') {
        $query_array = $this->buildSearchQuery($query);
        if (isset($query['page'])) {
          $query_array['page'] = $query['page'];
        }
        else {
          $query_array['page'] = 1;
        }
      }
      
      $result = $this->eventDatabase->getOccurrences($query_array);
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

      $next = $result->get('next');
      if (!empty($next)) {
        parse_str($next, $parameters_output);
      }

      $nextPage = !empty($parameters_output['page']) ? $parameters_output['page'] : NULL;
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
        '#nextPage' => $nextPage,
      ];

      return $render;
    }
    catch (\Exception $ex) {
      return $this->errorAction($ex);
    }
  }

  /**
   * Get next occurences.
   *
   * @return array
   *   The return value!
   */
  public function getNextOccurences(Request $request, $page = NUll) {
    $images = array();

    try {
      $referer = $request->headers->get('referer');
      $parameters = substr(strstr($referer, '?'), 1);
      parse_str($parameters, $referer_parameters);

      // Add search query on search page.
      $query_array = $this->buildSearchQuery($referer_parameters);
      $query_array['page'] = $page;


      $result = $this->eventDatabase->getOccurrences($query_array);
      $occurrences = $result->getItems();

      foreach ($occurrences as $key => $occurrence) {
        $images[$key] = array(
          '#theme' => 'imagecache_external',
          '#style_name' => 'medium',
          '#uri' => $occurrence->getEvent()->getImage(),
          '#alt' => $occurrence->getEvent()->getName(),
        );
      }

      $next = $result->get('next');
      if (!empty($next)) {
        parse_str($next, $parameters_output);
      }

      $nextPage = !empty($parameters_output['page']) ? $parameters_output['page'] : NULL;
      $render = [];
      $render[] = [
        '#theme' => 'event_database_pull_occurrences_list_next',
        '#occurrences' => $occurrences,
        '#attached' => [
          'library' => [
            'event_database_pull/event_database_pull',
          ],
        ],
        '#cache' => [
          'max-age' => 0,
        ],
        '#searchBox' => '',
        '#images' => $images,
        '#pager' => '',
        '#nextPage' => $nextPage,
      ];


      return new JsonResponse([
        'html' => \Drupal::service('renderer')
          ->renderPlain($render)
          ->__toString()
      ]);
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

  /**
   * Build search query for search page.
   *
   */
  private function buildSearchQuery ($query){
    // Remove empty values
    foreach ($query as $key => $value) {
      if(empty($value)) {
        unset($query[$key]);
      }
    }
    $query_array = [];
    foreach ($query as $key => $value) {
      switch ($key){
        case 'search':
          $query_array['event.name'] = $value;
          break;
        case 'date_from':
          $value = explode('-', $value);
          $query_array['startDate[after]'] = implode('-', array_reverse($value)) . 'T00:00:00.000Z';
          break;
        case 'date_to':
          $value = explode('-', $value);
          $query_array['startDate[before]'] = implode('-', array_reverse($value)) . 'T23:59:59.999Z'; // End of day
          break;
        case 'terms_string':
          $query_array['event.tags'] = [];
          $terms = explode('_', $value);
          foreach ($terms as $term_id) {
            $term = \Drupal\taxonomy\Entity\Term::load($term_id);
            if (isset($term->field_event_database_tags)) {
              foreach ($term->field_event_database_tags->getValue() as $tags_query) {
                $query_array['event.tags'][] = $tags_query['value'];
              }
            }
          }
          break;
      }
    }
    return $query_array;
  }
}
