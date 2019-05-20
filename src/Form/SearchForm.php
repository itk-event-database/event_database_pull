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
    $search = \Drupal::request()->query->get('search');
    $from = \Drupal::request()->query->get('date_from');
    $to = \Drupal::request()->query->get('date_to');
    $terms_string = \Drupal::request()->query->get('terms_string');
    $queryTerms = explode('_', $terms_string);
    $form['#attached']['library'][] = 'event_database_pull/event_database_pull_search';
    $form['search'] = array(
      '#title' => t('Title'),
      '#type' => 'textfield',
      '#placeholder' => t('Type the event title here'),
      '#default_value' => !empty($search) ? $search : NULL,
    );

    $term_data = [];
    $vid = 'event_tags';
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    foreach ($terms as $term) {
      $term_data[$term->tid] = $term->name;
    }

    $form['search_date'] = array(
      '#type' => 'container',
      '#attributes' => ['class' => ['date-wrapper']],
    );

    $form['search_date']['date_from'] = array(
      '#title' => t('From'),
      '#type' => 'textfield',
      '#attributes' => ['class' => ['js-date-popup date-from'], 'readonly' => 'true'],
      '#default_value' => !empty($from) ? $from : NULL,
      '#placeholder' => date('d') . '-' . date('m') . '-' . date('Y'),
      '#prefix' => '<div class="date-from">',
      '#suffix' => '<i class="far fa-calendar-alt"></i></div>',
    );

    $form['search_date']['date_to'] = array(
      '#title' => t('To'),
      '#type' => 'textfield',
      '#attributes' => ['class' => ['js-date-popup date-to'], 'readonly' => 'true'],
      '#default_value' => !empty($to) ? $to : NULL,
      '#placeholder' => t('Select date'),
      '#prefix' => '<div class="date-to">',
      '#suffix' => '<i class="far fa-calendar-alt"></i></div>',
    );

    $form['search_tags'] = array(
      '#title' => t('Categories'),
      '#type' => 'select',
      '#options' => $term_data,
      '#multiple' => TRUE,
      '#attributes' => ['class'=> ['js-select2']],
      '#default_value' => $queryTerms,
      '#prefix' => '<div class="search-tags">',
      '#suffix' => '<i class="fas fa-layer-group"></i></div>',
    );

    $form['action_wrapper'] = array(
      '#type' => 'container',
      '#attributes' => ['class' => ['action-wrapper']],
    );
    $form['action_wrapper']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Search'),
      '#attributes' => ['disabled' => 'disabled'],
    );

    $form['action_wrapper']['reset'] = [
      '#type' => 'html_tag',
      '#tag' => 'a',
      '#value' => $this->t('Reset search'),
      '#attributes' => ['href' => '/events', 'class'=> ['js-reset-search']],
      '#prefix' => '<div class="reset-search">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = array();
    $values = $form_state->getValues();

    // We only want user submitted values.
    $remove = ['submit', 'op', 'form_build_id', 'form_token', 'form_id'];
    $values['terms_string'] = '';
    $values = array_diff_key($values, array_flip($remove));
    foreach ($values['search_tags'] as $term_id) {
      $values['terms_string'] .= $term_id . '_';
    }
    unset($values['search_tags']);

    // Check for empty.
    $inputEmpty = TRUE;
    foreach ($values as $input) {
      if (!empty($input)) {
        $inputEmpty = FALSE;
      }
    }

    if (!$inputEmpty) {
      $query['search'] = $form_state->getValue('search');
      $url = Url::fromRoute('event_database_pull.occurrences_list', [], ['query' => $values]);
    }
    else {
      $url = Url::fromRoute('event_database_pull.occurrences_list');
    }
    $form_state->setRedirectUrl($url);
  }
}
?>