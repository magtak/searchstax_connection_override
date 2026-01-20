# SearchStax Connection Override

## Purpose
Allow hardcoded SearchStax connection settings in settings.php to avoid accidental index corruptions when copying databases

## How it Works
If the values in `settings.php` differ from the ones in the database, the module saves the ones in settings.php into the database. This will only happen on the first request or drush command following a DB copy. This is only done to avoid end user confusing when looking at the UI, as without saving the values, they would be seeing the database values rather than the overridden values. If you don't mind the Drupal admin UI showing the non-overridden values, then you can simply copy the settings.php snippet and use that to override the actual connection.

* **Web Requests**: An `EventSubscriber` listens to `KernelEvents::REQUEST` to sync values on page load.
* **CLI / Drush**: A Drush Command Hook (`@hook pre-command *`) triggers the sync before any Drush command (like `status` or `cget`) executes.

## Installation
1. Enable the module:
   ```bash
   drush en searchstax_connection_override
   ```

As discussed above, enabling the module is not required if you don't mind your Drupal UI values not reflecting your overridden settings.php values

## settings.php overrides

Replace SERVER_MACHINE_NAME with your actual server's machine name

```

$config['search_api.server.SERVER_MACHINE_NAME']['backend_config']['connector_config']['update_token'] = 'AEDJER-1238932-DUMMY-CREDS';
$config['search_api.server.SERVER_MACHINE_NAME']['backend_config']['connector_config']['host'] = 'searchcloud-1337-eu-west-1.searchstax.com';
$config['search_api.server.SERVER_MACHINE_NAME']['backend_config']['connector_config']['context'] = '123456';
$config['search_api.server.SERVER_MACHINE_NAME']['backend_config']['connector_config']['core'] = 'core-123';

//update_endpoint is a composite of host, context and core

$config['search_api.server.SERVER_MACHINE_NAME']['backend_config']['connector_config']['update_endpoint'] = 
'https://' . 
$config['search_api.server.SERVER_MACHINE_NAME']['backend_config']['connector_config']['host'] . '/' . 
$config['search_api.server.SERVER_MACHINE_NAME']['backend_config']['connector_config']['context'] . '/' . 
$config['search_api.server.SERVER_MACHINE_NAME']['backend_config']['connector_config']['core'] . 
'/update';
```