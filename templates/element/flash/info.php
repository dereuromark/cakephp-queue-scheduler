<?php
/**
 * Bootstrap 5 info flash message element.
 *
 * @var \Cake\View\View $this
 * @var array<string, mixed> $params
 * @var string $message
 */
$class = 'alert alert-info alert-dismissible fade show';
if (!empty($params['class'])) {
	$class .= ' ' . $params['class'];
}
if (!isset($params['escape']) || $params['escape'] !== false) {
	$message = h($message);
}
?>
<div class="<?= $class ?>" role="alert">
	<i class="fas fa-info-circle me-2"></i>
	<?= $message ?>
	<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
