# SearchStax Connection Override

## Purpose
Allow hardcoded SearchStax connection settings in settings.php to avoid accidental index corruptions when copying databases

## How it Works
The module hooks into the Drupal lifecycle at the earliest possible moments:

* **Web Requests**: An `EventSubscriber` listens to `KernelEvents::REQUEST` to sync values on page load.
* **CLI / Drush**: A Drush Command Hook (`@hook pre-command *`) triggers the sync before any Drush command (like `status` or `cget`) executes.
* **Persistence**: If the values in `settings.php` differ from the database, the module uses `$config->getEditable()` to update the active configuration permanently.


## Installation
1. Enable the module:
   ```bash
   drush en searchstax_connection_override
   ```

## settings.php overrides

Replace SERVER_MACHINE_NAME with your actual server's machine name

```
$config['search_api']['server'][SERVER_MACHINE_NAME]['backend_config']['connector_config']['update_endpoint'] = 'https://foo.com/solr';
$config['search_api']['server'][SERVER_MACHINE_NAME]['backend_config']['connector_config']['update_token'] = 'AEDJER-1238932-DUMMY-CREDS';
```