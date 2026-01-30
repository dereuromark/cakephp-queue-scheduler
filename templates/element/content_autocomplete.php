<?php
/**
 * @var \App\View\AppView $this
 */

/**
 * @var \QueueScheduler\View\Helper\SchedulerHelper $scheduler
 */
$scheduler = $this->Scheduler;
?>
<?= $scheduler->contentDatalists() ?>
<?= $scheduler->frequencyDatalist() ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	var typeField = document.getElementById('type');
	var contentField = document.getElementById('content');
	if (!typeField || !contentField) {
		return;
	}

	var datalistMap = {
		'0': 'content-queue-tasks',
		'1': 'content-commands'
	};

	function updateDatalist() {
		var listId = datalistMap[typeField.value] || '';
		contentField.setAttribute('list', listId);
	}

	typeField.addEventListener('change', updateDatalist);
	updateDatalist();
});
</script>
