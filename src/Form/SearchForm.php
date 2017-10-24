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
    $form['search'] = array(
      '#type' => 'textfield',
      '#placeholder' => t('Search for events'),
      '#default_value' => !empty($search) ? $search : NULL,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Search'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = array();
    if (!empty($form_state->getValue('search'))) {
      $query['search'] = $form_state->getValue('search');
      $url = Url::fromRoute('event_database_pull.occurrences_list', [], ['query' => ['search' => $query['search']]]);
    }
    else {
      $url = Url::fromRoute('event_database_pull.occurrences_list');
    }
    $form_state->setRedirectUrl($url);
  }
}
?>