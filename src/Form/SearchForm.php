<?php
/**
 * @file
 * Contains \Drupal\event_database_pull\Form\SearchForm.
 */

namespace Drupal\event_database_pull\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\event_database_pull\Service\EventDatabase;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\UrlHelper;
use Itk\EventDatabaseClient\Collection;
use Drupal\Core\Url;


/**
 * Contribute form.
 */
class SearchForm extends FormBase {
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_database_pull_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['search'] = array(
      '#type' => 'textfield',
      '#placeholder' => t('Search for events'),
    );

    $form['submit'] = array(
      '#type' => 'button',
      '#value' => t('Search'),
      '#ajax' =>[
        'callback' => [$this, 'submitForm'],
        'event' => 'click',
        'wrapper' => 'occurrence-list',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Searching..'),
        ],
      ]
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $images = array();
    $query = array();
    if (!empty($form_state->getValue('search'))) {
      $query['event.name'] = $form_state->getValue('search');
    }

    $result = $this->eventDatabase->getOccurrences($query);
    $occurrences = $result->getItems();
    $view = $this->getView($result);

    foreach ($occurrences as $key => $occurrence) {
      $images[$key] = array(
        '#theme' => 'imagecache_external',
        '#style_name' => 'medium',
        '#uri' => $occurrence->getEvent()->getImage(),
        '#alt' => $occurrence->getEvent()->getName(),
      );
    }

    $search_render_array = [
      '#theme' => 'event_database_pull_search_results_occurrences',
      '#occurrences' => $occurrences,
      '#view' => $view,
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
    ];
    $html = \Drupal::service('renderer')->render($search_render_array);
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new HtmlCommand(".occurrence-list", $html));
    return $ajax_response;
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
          $view[$key] = Url::fromRoute('event_database_pull.occurrences_list', $query);
        }
      }
    }

    return $view;
  }
}
?>