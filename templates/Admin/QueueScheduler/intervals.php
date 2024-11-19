<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, string> $shortcuts
 * @var string|null $result
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills flex-column">
		<li class="nav-item heading"><?= __('Actions') ?></li>
		<li class="nav-item">
			<?= $this->Html->link(__('Back'), ['controller' => 'SchedulerRows', 'action' => 'index'], ['class' => 'nav-link']) ?>
		</li>
	</ul>
</nav>
<div class="rows index content large-9 medium-8 columns col-sm-8 col-12">

	<h1><?= __('Queue Scheduler') ?></h1>

	<h2><?php echo __('Intervals'); ?></h2>

	<div class="row">
		<div class="col-md-6">

	<h3><?php echo __('Shortcuts available'); ?></h3>
	<ul>
		<?php foreach ($shortcuts as $shortcut) { ?>
			<li>
				<p>
					<code><?php echo $expression = (new \Cron\CronExpression($shortcut));?></code> as <code><?php echo h($shortcut); ?></code> shortcut
				</p>
			</li>
		<?php } ?>
	</ul>

	<h3>DateInterval style</h3>
	<p>
	They either start with a `P` or a `+`. Other values are invalid.
	</p>
	<ul>
		<li>
			<p><code>P1D</code> or <code>+ 1 day</code></p>
		</li>
		<li>
			<p><code>P2W</code> or <code>+ 2 weeks</code></p>
		</li>
	</ul>
	<p>
	You can also define more complex intervals by chaining: <code>+ 1 hour + 5 minutes</code>.
	</p>
	<p>
	See <a href="https://www.php.net/manual/en/dateinterval.createfromdatestring.php" target="_blank">php.net/manual/en/dateinterval.createfromdatestring.php</a>for details.
	</p>

	<h3>Crontab style</h3>

<pre>*    *    *    *    *   /path/to/somecommand.sh
|    |    |    |    |            |
|    |    |    |    |    Command or Script to execute
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
	<p>See <a href="https://crontab.guru/" target="_blank">crontab.guru/</a> for details.</p>

		</div>

		<div class="col-md-6">
			<h3>Translate</h3>

			<?php if (class_exists('Panlatent\CronExpressionDescriptor\ExpressionDescriptor')) { ?>

			<?php echo $this->Form->create();?>
			<fieldset>
				<legend><?php echo __('Crontab');?></legend>
				<?php
				echo $this->Form->control('interval');
				?>
			</fieldset>
			<?php
			echo $this->Form->submit(__('Translate'));
			echo $this->Form->end();
			?>

			<?php if (!empty($result)) { ?>
			<b><?php echo __('Result'); ?>: </b>
				<?php echo h($result); ?>
			<?php } ?>

			<?php } else { ?>
				<i>Requires <code>panlatent/cron-expression-descriptor</code> to be installed.</i>
			<?php } ?>
		</div>
	</div>

</div>
