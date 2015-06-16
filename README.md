# KoolKode BPMN 2.0 Process Engine

[![Build Status](https://travis-ci.org/koolkode/bpmn.svg?branch=master)](https://travis-ci.org/koolkode/bpmn)

**This project is not ready for production usage yet.**

Provides a basic process engine that can load BPMN 2.0 diagrams and execute contained processes. The BPMN engine
requires a relational database that is supported by KoolKode Database in order to persist process definitions, instances
and other runtime data. Like [Activiti](http://activiti.org/) it makes good use of the command pattern during execution
of a process instance.

I recommend [camunda Modeler](http://camunda.org/bpmn/tool/) to create executable process / collaboration diagrams. There is
no need for a graphical editor, but it turns creating process definitions into a more pleasant experience. The engine ships
with a class called `BusinessProcessBuilder` that can be used to create a process without loading it from a BPMN 2.0
XML file.

## Concurrency and Locking

The engine uses pessimistic locking of process instances within the DB to prevent concurrent access to process instances.
This locking strategy may create deadlocks. Try switching to async continuations / service methods when you are in
danger of running into a deadlock (keep in mind that async processing requires a job scheduler).

## Installation

1) Add "koolkode/bpmn" to your composer dependencies.

2) Run `composer update` to install the BPMN engine and it's dependencies.

3) Open a command shell in your project directory and execute `./vendor/bin/kkdb migration:up`.

4) Have the tool generate the `.kkdb.php` config file for you (supported DBs are Sqlite, MySQL / MariaDB and PostgreSQL).

5) Edit `.kkdb.php` and add the path to the `migration` directory of the `koolkode/bpmn` package.

6) Run `./vendor/bin/kkdb migration:up` again to apply DB migrations.

### Sample config file using Sqlite

Copy the code shown into a file called `.kkdb.php` next to your `composer.json`. It configures a
Sqlite connection that stores a DB file in your project directory.
```php
<?php

return [
	'ConnectionManager' => [
		'adapter' => [
			'default' => [
				'dsn' => sprintf('sqlite:%s/bpmn.db', __DIR__)
			]
		],
		'connection' => [
			'default' => [
				'adapter' => 'default'
			]
		]
	],
	'Migration' => [
		'MigrationManager' => [
			'directories' => [
				sprintf('%s/vendor/koolkode/bpmn/migration', __DIR__)
			]
		]
	]
];
```
Run `./vendor/bin/kkdb migration:up` and proceed with creating a process engine instance.

## Creating a Process Engine

You can create a process engine as soon as your DB has been setup as described in the installation instructions. Here is
some sample code, that shows how to create a process engine:
```php
<?php

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Database\ConnectionManager;
use KoolKode\Event\EventDispatcher;
use KoolKode\Expression\ExpressionContextFactory;

require_once __DIR__ . '/vendor/autoload.php';

$manager = ConnectionManager::fromConfigFile(__DIR__ . '/.kkdb.php');
$events = new EventDispatcher();
$expressions = new ExpressionContextFactory();

$engine = new ProcessEngine($manager->getConnection('default'), $events, $expressions);

// Use the repository service to deploy and query process definitions.
$repositoryService = $engine->getRepositoryService();

// Use the runtime service to start process instances and signal / message them.
$runtimeService = $engine->getRuntimeService();

// Use the task service to claim and complete user tasks.
$taskService = $engine->getTaskService();
```
The services that you aquire from the engine object offer all the features that are needed in order to deploy
and run business processes defined using BPMN.

## Supported BPMN 2.0 Elements

As of now KoolKode BPMN supports only a limited sub-set of BPMN elements.

### Gateways

- Exclusive
- Inclusive
- Parallel
- Event-based (exclusive)

### Activities

- Task
- Manual Task
- Human Task
- Service Task
- Script Task (PHP only)
- Send Task
- Receive Task
- Subprocess
- Call Activity
- Event Subprocess

### Events

- **None** - Start / Intermediate / End
- **Link** - Intermediate
- **Terminate** - End
- **Signal** - Start / Intermediate / Boundary / End
- **Messafe** - Start / Intermediate / Boundary / End
- **Timer** - Intermediate
