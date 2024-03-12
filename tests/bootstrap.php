<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Chronos\Chronos;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\SchemaLoader;
use Templating\View\Icon\BootstrapIcon;
use TestApp\Controller\AppController;

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('WINDOWS')) {
	if (DS === '\\' || substr(PHP_OS, 0, 3) === 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

define('PLUGIN_ROOT', dirname(__DIR__));
define('ROOT', PLUGIN_ROOT . DS . 'tests' . DS . 'test_app');
define('TMP', PLUGIN_ROOT . DS . 'tmp' . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('APP', ROOT . DS . 'src' . DS);
define('APP_DIR', 'src');
define('CAKE_CORE_INCLUDE_PATH', PLUGIN_ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . APP_DIR . DS);

define('WWW_ROOT', PLUGIN_ROOT . DS . 'webroot' . DS);
define('TESTS', __DIR__ . DS);
define('CONFIG', TESTS . 'config' . DS);

ini_set('intl.default_locale', 'de-DE');

require PLUGIN_ROOT . '/vendor/autoload.php';
require CORE_PATH . 'config/bootstrap.php';
require CAKE . 'functions.php';

Configure::write('App', [
	'namespace' => 'TestApp',
	'encoding' => 'UTF-8',
	'paths' => [
		'templates' => [
			PLUGIN_ROOT . DS . 'tests' . DS . 'test_app' . DS . 'templates' . DS,
		],
	],
]);
Configure::write('Icon', [
	'sets' => [
		'bs' => BootstrapIcon::class,
	],
]);

Configure::write('debug', true);

$cache = [
	'default' => [
		'engine' => 'File',
		'path' => CACHE,
	],
	'_cake_core_' => [
		'className' => 'File',
		'prefix' => 'crud_myapp_cake_core_',
		'path' => CACHE . 'persistent/',
		'serialize' => true,
		'duration' => '+10 seconds',
	],
	'_cake_model_' => [
		'className' => 'File',
		'prefix' => 'crud_my_app_cake_model_',
		'path' => CACHE . 'models/',
		'serialize' => 'File',
		'duration' => '+10 seconds',
	],
];

Cache::setConfig($cache);

class_alias(AppController::class, 'App\Controller\AppController');

Configure::write('App', [
	'namespace' => 'TestApp',
	'encoding' => 'UTF-8',
	'paths' => [
		'templates' => [
			PLUGIN_ROOT . DS . 'tests' . DS . 'test_app' . DS . 'templates' . DS,
		],
	],
]);

Configure::write('Icon', [
	'sets' => ['bs' => BootstrapIcon::class],
]);

Plugin::getCollection()->add(new QueueScheduler\QueueSchedulerPlugin());
Plugin::getCollection()->add(new Queue\QueuePlugin());
Plugin::getCollection()->add(new Tools\ToolsPlugin());

if (!getenv('DB_URL')) {
	putenv('DB_URL=sqlite:///:memory:');
}

// Uses Travis config then (MySQL, Postgres, ...)
ConnectionManager::setConfig('test', [
	'className' => Connection::class,
	'url' => getenv('DB_URL') ?: null,
	'timezone' => 'UTC',
	'quoteIdentifiers' => false,
	'cacheMetadata' => true,
]);

Chronos::setTestNow(Chronos::now());

if (env('FIXTURE_SCHEMA_METADATA')) {
	$loader = new SchemaLoader();
	$loader->loadInternalFile(env('FIXTURE_SCHEMA_METADATA'));
}
