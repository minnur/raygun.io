<?php

namespace Drupal\raygun\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Raygun4php\RaygunClient;

/**
 * Send error messages to Raygun.io
 */
class SendError implements EventSubscriberInterface {

  protected $config;
  protected $client;

  public function __construct() {
    $this->config = \Drupal::config('raygun.settings');
    if ($api_key = $this->config->get('raygun_apikey')) {
      $this->client = new RaygunClient($api_key, (bool) $this->config->get('raygun_async_sending', 1));
    }
  }

  /**
   * Send error.
   */
  public function sendErrorToRaygun(GetResponseEvent $event) {
    if ($this->client) {
      $user = \Drupal::currentUser();

      if ($this->config->get('raygun_send_version', 1) && $this->config->get('raygun_application_version', '') != '') {
        $this->client->SetVersion($this->config->get('raygun_application_version', ''));
      }
      if ($this->config->get('raygun_send_email', 1) && $user->id()) {
        $this->client->SetUser($user->getEmail());
      }
      if ($this->config->get('raygun_error_handling', 1)) {
        set_error_handler([$this, 'setErrorHandler']);
      }
      if ($this->config->get('raygun_exceptions', 1)) {
        set_exception_handler([$this, 'setExceptionHandler']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['sendErrorToRaygun', 0];
    $events[KernelEvents::EXCEPTION][] = ['sendErrorToRaygun', 0];
    return $events;
  }

  /**
   * Error handler for Raygun.io.
   */
  public function setErrorHandler($errno, $errstr, $errfile, $errline) {
    $this->client->SendError($errno, $errstr, $errfile, $errline);
  }

  /**
   * Exception handler for Raygun.io.
   */
  public function setExceptionHandler($exception) {
    $this->client->SendException($exception);
  }

}
