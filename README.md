# Template repository for EspoCRM extensions

Create a repository for your extension from this template.

This is fork of: <https://github.com/espocrm/ext-template>

## Start Docker

Run:

```sh
docker-compose up -d
```

### EspoCRM

<http://localhost:8080>

### phpMyAdmin

<http://pma.localhost:8080>

### ngrok

<http://ngrok.localhost:8080>

### smtp4dev

<http://mail.localhost:8080>

### Work inside the PHP container

```sh
docker-compose exec -u devilbox php bash
```

### Stop and remove containers

```sh
docker-compose stop && docker-compose rm -f
```

### Stop, remove containers and remove volumes attached to containers

```sh
docker-compose stop -v && docker-compose rm -fv
```

### Remove unnamed / anonymous volumes

```sh
docker volume rm $(docker volume ls -q | grep -x '.\{64,65\}')
```

## Preparing repository

Run:

```sh
php init.php
```

It will ask to enter an extension name and some other information.

After that, you can remove `init.php` file from your respository. Commit changes and proceed to configuration & building.

## Configuration

Create `config.json` file in the root directory. You can copy `config-default.json` and rename it to `config.json`.

When reading, this config will be merged with `config-default.json`. You can override default parameters in the created config.

Parameters:

* espocrm.repository - from what repository to fetch EspoCRM;
* espocrm.branch - what branch to fetch (`stable` is set by default); you can specify version number instead (e.g. `5.9.2`);
* database - credentials of the dev database;
* install.siteUrl - site url of the dev instance;
* install.defaultOwner - a webserver owner (important to be set right);
* install.defaultGroup - a webserver group (important to be set right).

## Config for EspoCRM instance

You can override EspoCRM config. Create `config.php` in the root directory of the repository. This file will be applied after EspoCRM intallation (when building).

Example:

```php
<?php
return [
    'useCacheInDeveloperMode' => true,
];
```

## Building

After building, EspoCRM instance with installed extension will be available at `site` directory. You will be able to access it with credentials:

* Username: admin
* Password: 1

### Preparation

With this Docker setup you already have all the tools needed to build an instance.

### Full EspoCRM instance building

It will download EspoCRM (from the repository specified in the config), then build and install it. Then it will install the extension.

Command:

```sh
./bin/espo ext:init
```

Note: It will remove a previously installed EspoCRM instance, but keep the database intact.

### Copying extension files to EspoCRM instance

You need to run this command every time you make changes in `src` directory and you want to try these changes on Espo instance.

Command:

```sh
./bin/espo ext:copy
```

### Running after-install script

AfterInstall.php will be applied for EspoCRM instance.

Command:

```sh
./bin/espo ext:after-install
```

### Extension package building

Command:

```sh
./bin/espo ext:build
```

The package will be created in `build` directory.

Note: The version number is taken from `package.json`.

### Installing addition extensions

If your extension requires other extensions, there is a way to install them automatically while building the instance.

Necessary steps:

1. Add the current EspoCRM version to the `config.php`:

```php
<?php
return [
    'version' => '6.2.0',
];

```

1. Create the `extensions` directory in the root directory of your repository.
2. Put needed extensions (e.g. `my-extension-1.0.0.zip`) in this directory.

Extensions will be installed automatically after running the command `./bin/espo ext:init`.

## Development workflow

1. Do development in `src` dir.
2. Run `./bin/espo ext:copy`.
3. Test changes in EspoCRM instance at `site` dir.

## Using composer in extension

If your extension requires to use additional libraries, they can be installed by the composer:

1. Create a file `src/files/custom/Espo/Modules/{ModuleName}/composer.json` with your dependencies.
2. Once you run `./bin/espo ext:init` or `./bin/espo ext:composer-install`, composer dependencies will be automatically installed.

Note: the extension build will contain only the `vendor` directory without `composer.json` file.

## Versioning

The version number is stored in `package.json` and `package-lock.json`.

Bumping version:

```sh
npm version patch
npm version minor
npm version major
```

## Tests

Prepare:

1. `./bin/espo ext:copy`
2. `./bin/espo core:build --build=test`

### Unit

Command to run unit tests:

```sh
./bin/bash -c "cd site;vendor/bin/phpunit tests/unit/Espo/Modules/NbpExchangeRates"
```

### Integration

You need to create a config file `tests/integration/config.php`:

```php
<?php

return [
    'database' => [
        'driver' => 'pdo_mysql',
        'host' => 'localhost',
        'charset' => 'utf8mb4',
        'dbname' => 'TEST_DB_NAME',
        'user' => 'YOUR_DB_USER',
        'password' => 'YOUR_DB_PASSWORD',
    ],
];
```

The file should exist before you run `./bin/espo ext:copy`.

Command to run integration tests:

```sh
./bin/bash -c "cd site;vendor/bin/phpunit tests/integration/Espo/Modules/NbpExchangeRates"
```

## Configuring IDE

You need to set the following paths to be ignored in your IDE:

* `build`
* `site/build`
* `site/custom/Espo/Modules/NbpExchangeRates`
* `site/tests/unit/Espo/Modules/NbpExchangeRates`
* `site/tests/integration/Espo/Modules/NbpExchangeRates`

## License

Change a license in `LICENSE` file. The current license is intended for scripts of this repository. It's not supposed to be used for code of your extension.
