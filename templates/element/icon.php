<?php
/**
 * Icon element with Font Awesome fallback when Icon helper is not available.
 *
 * @var \App\View\AppView $this
 * @var string $name Icon name
 * @var array<string, mixed> $options Icon options
 * @var array<string, mixed> $attributes Icon attributes
 */

$options ??= [];
$attributes ??= [];

$fontAwesomeMap = [
	'view' => 'fas fa-eye',
	'edit' => 'fas fa-edit',
	'delete' => 'fas fa-trash',
	'play-circle' => 'fas fa-play-circle',
	'stop-circle' => 'fas fa-stop-circle',
	'yes' => 'fas fa-check',
	'no' => 'fas fa-times',
];

$fallbackTextMap = [
	'view' => 'View',
	'edit' => 'Edit',
	'delete' => 'Del',
	'play-circle' => 'Run',
	'stop-circle' => 'Stop',
	'yes' => 'Yes',
	'no' => 'No',
];

if ($this->helpers()->has('Icon')) {
	echo $this->Icon->render($name, $options, $attributes);
} elseif (isset($fontAwesomeMap[$name])) {
	$title = $attributes['title'] ?? $fallbackTextMap[$name] ?? ucfirst($name);
	$class = $fontAwesomeMap[$name];
	if (!empty($attributes['class'])) {
		$class .= ' ' . $attributes['class'];
	}
	echo '<i class="' . h($class) . '" title="' . h($title) . '"></i>';
} else {
	$title = $attributes['title'] ?? $fallbackTextMap[$name] ?? ucfirst($name);
	echo '<span title="' . h($title) . '">[' . h($fallbackTextMap[$name] ?? ucfirst($name)) . ']</span>';
}
