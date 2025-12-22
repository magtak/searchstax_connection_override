<?php

namespace Drupal\searchstax_connection_override\Drush\Commands;

use Drush\Commands\DrushCommands;
use Drupal\searchstax_connection_override\EventSubscriber\SearchStaxSyncEventSubscriber;

/**
 * Ensures SearchStax sync happens on every Drush command.
 */
class SyncStaxCommands extends DrushCommands {

  /**
   * @hook pre-command *
   *
   * This "pre-command" hook with a wildcard (*) runs before 
   * EVERY single drush command, including st and cget.
   */
  public function preCommand() {
    $subscriber = \Drupal::service('searchstax_connection_override.searchstax_config_sync_subscriber');
    if ($subscriber instanceof SearchStaxSyncEventSubscriber) {
      $subscriber->onEventTrigger();
    }
  }
}