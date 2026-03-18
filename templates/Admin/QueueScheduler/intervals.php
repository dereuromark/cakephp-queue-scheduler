<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, string> $shortcuts
 * @var string|null $result
 */
?>
<div class="scheduler-intervals">
	<div class="d-flex justify-content-between align-items-center mb-4">
		<h2 class="mb-0">
			<i class="fas fa-clock me-2"></i><?= __('Intervals Reference') ?>
		</h2>
		<div>
			<?= $this->Html->link(
				'<i class="fas fa-arrow-left me-1"></i>' . __('Back'),
				['controller' => 'SchedulerRows', 'action' => 'index'],
				['class' => 'btn btn-secondary', 'escape' => false],
			) ?>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-6 mb-4">
			<div class="card h-100">
				<div class="card-header">
					<i class="fas fa-hashtag me-2"></i><?= __('Cron Shortcuts') ?>
				</div>
				<div class="card-body">
					<ul class="list-unstyled mb-0">
						<?php foreach ($shortcuts as $shortcut) { ?>
							<li class="mb-2">
								<?php
								$cronExpression = $shortcut === '@minutely' ? '* * * * *' : $shortcut;
								$expression = new \Cron\CronExpression($cronExpression);
								?>
								<code><?= $expression ?></code> as <code><?= h($shortcut) ?></code>
							</li>
						<?php } ?>
					</ul>
				</div>
			</div>
		</div>

		<div class="col-lg-6 mb-4">
			<div class="card h-100">
				<div class="card-header">
					<i class="fas fa-language me-2"></i><?= __('Translate Expression') ?>
				</div>
				<div class="card-body">
					<?php if (class_exists('Panlatent\CronExpressionDescriptor\ExpressionDescriptor')) { ?>
						<?= $this->Form->create() ?>
						<div class="mb-3">
							<?= $this->Form->control('interval', [
								'label' => __('Cron Expression'),
								'placeholder' => '* * * * *',
								'class' => 'form-control',
							]) ?>
						</div>
						<?= $this->Form->button(__('Translate'), ['class' => 'btn btn-primary']) ?>
						<?= $this->Form->end() ?>

						<?php if (!empty($result)) { ?>
							<div class="alert alert-success mt-3 mb-0">
								<strong><?= __('Result') ?>:</strong> <?= h($result) ?>
							</div>
						<?php } ?>
					<?php } else { ?>
						<div class="alert alert-info mb-0">
							<i class="fas fa-info-circle me-1"></i>
							<?= __('Requires {0} to be installed.', '<code>panlatent/cron-expression-descriptor</code>') ?>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-6 mb-4">
			<div class="card h-100">
				<div class="card-header">
					<i class="fas fa-calendar-day me-2"></i><?= __('DateInterval Style') ?>
				</div>
				<div class="card-body">
					<p><?= __('They either start with a {0} or a {1}. Other values are invalid.', '<code>P</code>', '<code>+</code>') ?></p>
					<ul class="mb-3">
						<li><code>P1D</code> or <code>+ 1 day</code></li>
						<li><code>P2W</code> or <code>+ 2 weeks</code></li>
					</ul>
					<p class="mb-2"><?= __('You can also define more complex intervals by chaining:') ?></p>
					<code>+ 1 hour + 5 minutes</code>
					<p class="mt-3 mb-0">
						<a href="https://www.php.net/manual/en/dateinterval.createfromdatestring.php" target="_blank" class="btn btn-sm btn-outline-secondary">
							<i class="fas fa-external-link-alt me-1"></i><?= __('PHP Documentation') ?>
						</a>
					</p>
				</div>
			</div>
		</div>

		<div class="col-lg-6 mb-4">
			<div class="card h-100">
				<div class="card-header">
					<i class="fas fa-terminal me-2"></i><?= __('Crontab Style') ?>
				</div>
				<div class="card-body">
					<pre class="mb-3">*    *    *    *    *   /path/to/command
|    |    |    |    |            |
|    |    |    |    |    Command or Script
|    |    |    |    |
|    |    |    | Day of week(0-6 | Sun-Sat)
|    |    |    |
|    |    |  Month(1-12)
|    |    |
|    |  Day of Month(1-31)
|    |
|   Hour(0-23)
|
Min(0-59)</pre>
					<a href="https://crontab.guru/" target="_blank" class="btn btn-sm btn-outline-secondary">
						<i class="fas fa-external-link-alt me-1"></i>crontab.guru
					</a>
				</div>
			</div>
		</div>
	</div>
</div>
