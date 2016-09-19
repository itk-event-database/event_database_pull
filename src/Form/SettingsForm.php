<?php

namespace Drupal\event_database_pull\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
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
        '#required' => TRUE,
        '#title' => $this->t('Username'),
        '#default_value' => $config->get('api.username'),
        '#description' => $this->t('The Event database API username'),
      ],
      'password' => [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Password'),
        '#default_value' => $config->get('api.password'),
        '#description' => $this->t('The Event database API password'),
      ],
    ];

    $form['list'] = [
      '#type' => 'fieldset',
      '#title' => t('Event list'),
      '#tree' => TRUE,

      'query' => [
        '#type' => 'textarea',
        '#title' => t('Query'),
        '#default_value' => $config->get('list.query'),
        '#description' => $this->t('Query parameters (YAML) to add to the Event database query'),
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
    } catch (ParseException $ex) {
      $form_state->setError($form['list']['query'], $this->t('Query must be valid YAML (@message)', ['@message' => $ex->getMessage()]));
    }

    // @TODO: Test that we can get events from api.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('event_database_pull.settings');

    $config->set('api.url', $form_state->getValue(['api', 'url']));
    $config->set('api.username', $form_state->getValue(['api', 'username']));
    $config->set('api.password', $form_state->getValue(['api', 'password']));

    $config->set('list.query', $form_state->getValue(['list', 'query']));

    $config->save();
    return parent::submitForm($form, $form_state);
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
