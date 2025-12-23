<?php

namespace Drupal\searchstax_connection_override\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SearchStaxSyncEventSubscriber implements EventSubscriberInterface {

  protected $configFactory;
  protected $logger;
  protected $messenger;

  public function __construct(
    ConfigFactoryInterface $config_factory, 
    LoggerChannelFactoryInterface $logger_factory,
    MessengerInterface $messenger
  ) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('searchstax_override');
    $this->messenger = $messenger;
  }

  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onEventTrigger', 300],
    ];
  }

  public function onEventTrigger($event = NULL) {
    if ($event instanceof RequestEvent && !$event->isMainRequest()) {
      return;
    }
    $this->executeSync();
  }

  public function executeSync() {
    $server_configs = $this->configFactory->listAll('search_api.server.');

    foreach ($server_configs as $config_name) {
      $runtime_config = $this->configFactory->get($config_name);
      
      if ($runtime_config->get('backend') !== 'search_api_solr' || 
          $runtime_config->get('backend_config.connector') !== 'searchstax') {
        continue;
      }

      $editable_config = $this->configFactory->getEditable($config_name);
      $endpoint_key = 'backend_config.connector_config.update_endpoint';
      
      $override_value = $runtime_config->get($endpoint_key);
      $database_value = $editable_config->get($endpoint_key);

      if ($override_value !== NULL && $override_value !== $database_value) {
        $editable_config->set($endpoint_key, (string) $override_value);
        $editable_config->save();

        $message = "Updating server $config_name to the endpoint $override_value to avoid overwriting the wrong index.";

        $this->logger->notice($message);

        if (PHP_SAPI === 'cli' && class_exists('\Drush\Drush')) {
          \Drush\Drush::output()->writeln("<info>[SearchStax Sync]</info> $message");
        }

        $this->messenger->addStatus($message);
      }
    }
  }
}