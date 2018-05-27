<?php

namespace Drupal\raygun\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Raygun.io Settings form.
 */
class RaygunSettingsForm extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'raygun_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['raygun.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('raygun.settings');

    $form['common'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Common'),
      '#collapsible' => FALSE,
      '#collapsed'   => FALSE,
    ];

    $form['common']['raygun_apikey'] = [
      '#type'          => 'textfield',
      '#required'      => TRUE,
      '#title'         => $this->t('API key'),
      '#description'   => t('Raygun.io API key for the application.'),
      '#default_value' => $config->get('raygun_apikey', ''),
    ];

    $form['common']['raygun_async_sending'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Use asynchronous sending'),
      '#description'   => t('If checked, the message will be sent asynchronously. This provides a great speedup versus the older cURL method. On some environments (e.g. Windows), you might be forced to uncheck this.'),
      '#default_value' => $config->get('raygun_async_sending', 1),
    ];

    $form['common']['raygun_send_version'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Send application version'),
      '#description'   => t('If checked, all error messages to Raygun.io will include your application version that is currently running. This is optional but recommended as the version number is considered to be first-class data for a message.'),
      '#default_value' => $config->get('raygun_send_version', 1),
    ];

    $form['common']['raygun_application_version'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Application version'),
      '#description'   => t('What is the current version of your Drupal application. This can be any string or number or even a git commit hash.'),
      '#default_value' => $config->get('raygun_application_version', ''),
      '#states' => [
        'invisible' => [
          ':input[name="raygun_send_version"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['common']['raygun_send_email'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Send current user email'),
      '#description'   => t('If checked, all error messages to Raygun.io will include the current email address of any logged in users.  This is optional - if it is not checked, a random identifier will be used.'),
      '#default_value' => $config->get('raygun_send_username', 1),
    ];

    $form['php'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('PHP'),
      '#collapsible' => TRUE,
      '#collapsed'   => FALSE,
    ];

    $form['php']['raygun_exceptions'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Register global exception handler'),
      '#default_value' => $config->get('raygun_exceptions', 1),
    ];

    $form['php']['raygun_error_handling'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Register global error handler'),
      '#default_value' => $config->get('raygun_error_handling', 1),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!preg_match("/^[0-9a-zA-Z\+\/]{22}==$/", $values['raygun_apikey'])) {
      $form_state->setErrorByName('raygun_apikey', $this->t('You must specify a valid Raygun.io API key. You can find this on your dashboard.'));
    }
    $application_version = trim($values['raygun_application_version']);
    if ($values['raygun_send_version'] && empty($application_version)) {
      $form_state->setErrorByName('raygun_application_version', $this->t('You must specify an application version if you are going to send this.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('raygun.settings');
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
