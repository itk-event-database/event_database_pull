<?php

namespace Drupal\event_database_pull\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Settings for Event Database module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_database_pull_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('event_database_pull.settings');

    $form['api'] = [
      '#type' => 'fieldset',
      '#title' => t('API'),
      '#tree' => TRUE,

      'url' => [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Url'),
        '#default_value' => $config->get('api.url'),
        '#description' => $this->t('The Event database API url'),
      ],
      'username' => [
        '#type' => 'textfield',
        '#required' => FALSE,
        '#title' => $this->t('Username'),
        '#default_value' => $config->get('api.username'),
        '#description' => $this->t('The Event database API username'),
      ],
      'password' => [
        '#type' => 'textfield',
        '#required' => FALSE,
        '#title' => $this->t('Password'),
        '#default_value' => $config->get('api.password'),
        '#description' => $this->t('The Event database API password'),
      ],
    ];

    $form['list'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Event list'),
      '#tree' => TRUE,

      'items_per_page' => [
        '#type' => 'number',
        '#title' => $this->t('Number of events per page'),
        '#description' => t('The number of events to display per page'),
        '#default_value' => $config->get('list.items_per_page') ?: 5,
        '#size' => 5,
      ],

      'order' => [
        '#type' => 'radios',
        '#title' => $this->t('Order'),
        '#default_value' => $config->get('list.order') ?: 'ASC',
        '#options' => [
          'ASC' => $this->t('Show first upcoming first'),
          'DESC' => $this->t('Show first upcoming last'),
        ],
      ],

      'query' => [
        '#type' => 'textarea',
        '#title' => $this->t('Event query'),
        '#default_value' => $config->get('list.query'),
        '#description' => $this->t('Query parameters (YAML) to add to the Event database query'),
      ],

      'query_occurrences' => [
        '#type' => 'textarea',
        '#title' => $this->t('Occurrences query'),
        '#default_value' => $config->get('list.query_occurrences'),
        '#description' => $this->t('Query parameters (YAML) to add to the Event database query'),
      ],
    ];

    $form['item'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Event item'),
      '#tree' => TRUE,

      'generate_404_on_past_event' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Generate "Page not found" on past event'),
        '#default_value' => $config->get('item.generate_404_on_past_event'),
        '#description' => $this->t('Generate "Page not found" when viewing a finished event'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    try {
      $value = $form_state->getValue(['list', 'query']);
      Yaml::parse($value);
    }
    catch (ParseException $ex) {
      $form_state->setError($form['list']['query'], $this->t('Query must be valid YAML (@message)', ['@message' => $ex->getMessage()]));
    }

    // @todo Test that we can get events from api.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('event_database_pull.settings');

    $config->set('api.url', $form_state->getValue(['api', 'url']));
    $config->set('api.username', $form_state->getValue(['api', 'username']));
    $config->set('api.password', $form_state->getValue(['api', 'password']));

    $config->set('list.items_per_page', $form_state->getValue(['list', 'items_per_page']));
    $config->set('list.order', $form_state->getValue(['list', 'order']));
    $config->set('list.query', $form_state->getValue(['list', 'query']));
    $config->set('list.query_occurrences', $form_state->getValue(['list', 'query_occurrences']));

    $config->set('item.generate_404_on_past_event', $form_state->getValue(['item', 'generate_404_on_past_event']));

    $config->save();
    parent::submitForm($form, $form_state);
    $message = Link::createFromRoute($this->t('View events list'), 'event_database_pull.events_list')->toString();
    drupal_set_message($message);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'event_database_pull.settings',
    ];
  }

}
