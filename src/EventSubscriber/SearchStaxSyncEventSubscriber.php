<?php

namespace Drupal\searchstax_connection_override\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Console\ConsoleEvents;

/**
 * Syncs SearchStax overrides from settings.php to the database.
 */
class SearchStaxSyncEventSubscriber implements EventSubscriberInterface {

  protected $configFactory;
  protected $logger;


  /**
   * Constructs an SearchStaxSyncEventSubscriber instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */

  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('searchstax_override');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // For Browser visits.
      KernelEvents::REQUEST => ['onEventTrigger', 300],
      // For Drush commands. This is the fix for "drush status".
      ConsoleEvents::COMMAND => ['onEventTrigger', 300],
    ];
  }

  public function onEventTrigger(RequestEvent $event = NULL) {
    if ($event instanceof RequestEvent && !$event->isMainRequest()) {
      return;
    }

    // Identify all Search API servers.
    $server_configs = $this->configFactory->listAll('search_api.server.');

    foreach ($server_configs as $config_name) {

      $runtime_config = $this->configFactory->get($config_name);
      
      // Verification Level 1: Must be Solr.
      if ($runtime_config->get('backend') !== 'search_api_solr') {
        continue;
      }

      // Verification Level 2: Must be the SearchStax connector.
      if ($runtime_config->get('backend_config.connector') !== 'searchstax') {
        continue;
      }

      $editable_config = $this->configFactory->getEditable($config_name);
      $needs_save = FALSE;

      $keys = [
        'backend_config.connector_config.update_endpoint',
        'backend_config.connector_config.update_token',
      ];

      foreach ($keys as $key) {
        $override_value = $runtime_config->get($key);
        $database_value = $editable_config->get($key);

        // Sync if settings.php has a value AND it differs from the DB.
        if ($override_value !== NULL && $override_value !== $database_value) {
          $editable_config->set($key, (string) $override_value);
          $needs_save = TRUE;
        }
      }

      if ($needs_save) {
        $editable_config->save();
        $this->logger->notice('Updated DB config for @name to match settings.php overrides.', [
          '@name' => $config_name,
        ]);
      }
    }
  }
}