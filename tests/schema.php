<?php

use Cake\Utility\Inflector;

$tables = [];

/**
 * @var \DirectoryIterator<\DirectoryIterator> $iterator
 */
$iterator = new DirectoryIterator(__DIR__ . DS . 'Fixture');
foreach ($iterator as $file) {
	$tables = addTablesFromSchemaFile((string)$file, 'QueueScheduler', $tables);
}

/**
 * @var \DirectoryIterator<\DirectoryIterator> $iterator
 */
$iterator = new DirectoryIterator(dirname(__DIR__) . '/vendor/dereuromark/cakephp-queue/tests/Fixture');
foreach ($iterator as $file) {
	$tables = addTablesFromSchemaFile((string)$file, 'Queue', $tables);
}

return $tables;

function addTablesFromSchemaFile(string $file, string $pluginName, array $tables): array {
	if (!preg_match('/(\w+)Fixture.php$/', $file, $matches)) {
		return $tables;
	}

	$name = $matches[1];
	$class = $pluginName . '\\Test\\Fixture\\' . $name . 'Fixture';
	try {
		$fieldsObject = (new ReflectionClass($class))->getProperty('fields');
		$tableObject = (new ReflectionClass($class))->getProperty('table');
		$tableName = $tableObject->getDefaultValue();
	} catch (ReflectionException $e) {
		return $tables;
	}

	if (!$tableName) {
		$tableName = Inflector::underscore($name);
	}

	$array = $fieldsObject->getDefaultValue();
	$constraints = $array['_constraints'] ?? [];
	$indexes = $array['_indexes'] ?? [];
	unset($array['_constraints'], $array['_indexes'], $array['_options']);
	$table = [
		'table' => $tableName,
		'columns' => $array,
		'constraints' => $constraints,
		'indexes' => $indexes,
	];
	$tables[$tableName] = $table;

	return $tables;
}
