<?php
/**
 * Quick Add element with autocomplete search.
 *
 * @var \App\View\AppView $this
 */

use QueueScheduler\Model\Entity\SchedulerRow;

/**
 * @var \QueueScheduler\View\Helper\SchedulerHelper $scheduler
 */
$scheduler = $this->Scheduler;

$commands = $scheduler->availableCommands();
$tasks = $scheduler->availableQueueTasks();
?>
<div class="card mb-4">
	<div class="card-body">
		<label class="form-label" for="quick-add-search">
			<i class="fas fa-bolt me-2"></i><?= __('Quick Add') ?>
		</label>
		<input type="text" id="quick-add-search" class="form-control form-control-lg"
			   list="quick-add-options" placeholder="<?= __('Search commands or tasks...') ?>"
			   autocomplete="off">
		<datalist id="quick-add-options">
			<?php foreach ($commands as $name => $command) { ?>
				<option value="<?= h($name) ?> (Command)" data-name="<?= h($name) ?>" data-type="<?= SchedulerRow::TYPE_CAKE_COMMAND ?>" data-content="<?= h($command) ?>">
			<?php } ?>
			<?php foreach ($tasks as $name => $task) { ?>
				<option value="<?= h($name) ?> (Task)" data-name="<?= h($name) ?>" data-type="<?= SchedulerRow::TYPE_QUEUE_TASK ?>" data-content="<?= h($task) ?>">
			<?php } ?>
		</datalist>
		<div class="form-text"><?= __('Select a command or task to pre-fill the form') ?></div>
	</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
	var searchInput = document.getElementById('quick-add-search');
	if (!searchInput) return;

	// Build lookup map from datalist options
	var optionsMap = {};
	document.querySelectorAll('#quick-add-options option').forEach(function(opt) {
		optionsMap[opt.value] = {
			name: opt.dataset.name,
			type: opt.dataset.type,
			content: opt.dataset.content
		};
	});

	function handleSelection() {
		var selected = optionsMap[searchInput.value];
		if (selected) {
			var params = new URLSearchParams({
				type: selected.type,
				content: selected.content,
				name: selected.name
			});
			window.location.href = window.location.pathname + '?' + params.toString();
		}
	}

	searchInput.addEventListener('change', handleSelection);

	// Also handle Enter key for better UX
	searchInput.addEventListener('keydown', function(e) {
		if (e.key === 'Enter') {
			var selected = optionsMap[this.value];
			if (selected) {
				e.preventDefault();
				handleSelection();
			}
		}
	});
});
</script>
