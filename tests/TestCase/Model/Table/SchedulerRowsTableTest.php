<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Model\Table;

use Cake\Command\CacheClearCommand;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Queue\Queue\Task\ExampleTask;
use QueueScheduler\Model\Entity\SchedulerRow;
use QueueScheduler\Model\Table\SchedulerRowsTable;
use stdClass;

/**
 * QueueScheduler\Model\Table\RowsTable Test Case
 */
class SchedulerRowsTableTest extends TestCase {

	/**
	 * Test subject
	 *
	 * @var \QueueScheduler\Model\Table\SchedulerRowsTable
	 */
	protected $SchedulerRows;

	/**
	 * Fixtures
	 *
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.QueueScheduler.SchedulerRows',
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$config = $this->getTableLocator()->exists('QueueScheduler.SchedulerRows') ? [] : ['className' => SchedulerRowsTable::class];
		$this->SchedulerRows = $this->getTableLocator()->get('QueueScheduler.SchedulerRows', $config);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->SchedulerRows);

		parent::tearDown();
	}

	/**
	 * Test validationDefault method
	 *
	 *@uses \QueueScheduler\Model\Table\SchedulerRowsTable::validationDefault()
	 *
	 * @return void
	 */
	public function testInsert(): void {
		$row = $this->SchedulerRows->newEntity([
			'name' => 'Example Queue Task',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'frequency' => '+30seconds',
			'content' => ExampleTask::class,
			'enabled' => true,
		]);

		$row = $this->SchedulerRows->saveOrFail($row);
		$this->assertNotEmpty($row->next_run);
		$this->assertSame($row->next_run->getTestNow()->toDateTimeString(), $row->next_run->toDateTimeString());

		$row->last_run = (new DateTime())->addDays(1);
		$row->frequency = '+50seconds';
		$row = $this->SchedulerRows->saveOrFail($row);

		$this->assertSame($row->next_run->getTestNow()->addDays(1)->addSeconds(50)->toDateTimeString(), $row->next_run->toDateTimeString());

		$row->last_run = (new DateTime())->addDays(2);
		$row = $this->SchedulerRows->saveOrFail($row);

		$this->assertSame($row->next_run->getTestNow()->addDays(2)->addSeconds(50)->toDateTimeString(), $row->next_run->toDateTimeString());
	}

	/**
	 * Test validationDefault method
	 *
	 *@uses \QueueScheduler\Model\Table\SchedulerRowsTable::validationDefault()
	 *
	 * @return void
	 */
	public function testInsertWithAliasOnly(): void {
		$row = $this->SchedulerRows->newEntity([
			'name' => 'Example Queue Task',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'frequency' => '+30seconds',
			'content' => 'Queue.Example',
			'enabled' => true,
		]);

		$row = $this->SchedulerRows->saveOrFail($row);
		$this->assertSame($row->content, ExampleTask::class);
	}

	/**
	 * @return void
	 */
	public function testValidateFrequency(): void {
		$data = [
			'name' => 'n',
			'content' => 'c',
			'frequency' => '@daily',
		];
		$row = $this->SchedulerRows->newEntity($data);
		$this->assertSame([], $row->getError('frequency'));

		$data = [
			'name' => 'n',
			'content' => 'c',
			'frequency' => '@minutely',
		];
		$row = $this->SchedulerRows->newEntity($data);
		$this->assertSame([], $row->getError('frequency'));

		$data = [
			'name' => 'n',
			'content' => 'c',
			'frequency' => 'daily',
		];
		$row = $this->SchedulerRows->newEntity($data);
		$expected = [
			'validateFrequency' => 'Must be a cron expression ("0 11 * * *"), an @-shortcut ("@daily", "@minutely"), a relative interval ("+30 seconds"), or an ISO 8601 duration ("P2D").',
		];
		$this->assertSame($expected, $row->getError('frequency'));

		$data = [
			'name' => 'n',
			'content' => 'c',
			'frequency' => 'P2D',
		];
		$row = $this->SchedulerRows->newEntity($data);
		$this->assertSame([], $row->getError('frequency'));

		$data = [
			'name' => 'n',
			'content' => 'c',
			'frequency' => '+ 1 hour + 1 minute',
		];
		$row = $this->SchedulerRows->newEntity($data);
		$expected = [];
		$this->assertSame($expected, $row->getError('frequency'));

		$data = [
			'name' => 'n',
			'content' => 'c',
			'frequency' => '+ x',
		];
		$row = $this->SchedulerRows->newEntity($data);
		$expected = [
			'validateFrequency' => 'Must be a cron expression ("0 11 * * *"), an @-shortcut ("@daily", "@minutely"), a relative interval ("+30 seconds"), or an ISO 8601 duration ("P2D").',
		];
		$this->assertSame($expected, $row->getError('frequency'));
	}

	/**
	 * @return void
	 */
	public function testValidateUniqueName(): void {
		$row = $this->SchedulerRows->newEntity([
			'name' => 'My Unique Task',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'frequency' => '@daily',
			'content' => ExampleTask::class,
			'enabled' => true,
		]);
		$this->SchedulerRows->saveOrFail($row);

		// Fixture name should also fail
		$fixtureConflict = $this->SchedulerRows->newEntity([
			'name' => 'Lorem ipsum dolor sit amet',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'frequency' => '@daily',
			'content' => ExampleTask::class,
			'enabled' => true,
		]);
		$expected = ['unique' => 'This name is already in use.'];
		$this->assertSame($expected, $fixtureConflict->getError('name'));

		// Same name should fail
		$duplicate = $this->SchedulerRows->newEntity([
			'name' => 'My Unique Task',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'frequency' => '@daily',
			'content' => ExampleTask::class,
			'enabled' => true,
		]);
		$expected = ['unique' => 'This name is already in use.'];
		$this->assertSame($expected, $duplicate->getError('name'));

		// Different name should pass
		$other = $this->SchedulerRows->newEntity([
			'name' => 'Another Task',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'frequency' => '@daily',
			'content' => ExampleTask::class,
			'enabled' => true,
		]);
		$this->assertSame([], $other->getError('name'));

		// Editing existing row with same name should pass
		$row = $this->SchedulerRows->patchEntity($row, ['name' => 'My Unique Task']);
		$this->assertSame([], $row->getError('name'));
	}

	/**
	 * @return void
	 */
	public function testValidateJobConfig(): void {
		$baseData = [
			'name' => 'JobConfig test',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'content' => ExampleTask::class,
			'frequency' => '@daily',
		];

		$cases = [
			// label => [json, expectValid]
			'empty allowed' => ['', true],
			'valid priority+group' => ['{"priority":5,"group":"batch"}', true],
			'valid priority only' => ['{"priority":1}', true],
			'valid group only' => ['{"group":"nightly"}', true],
			'malformed json' => ['{not json', false],
			'json array not object' => ['[1,2,3]', false],
			'unknown key (typo)' => ['{"priorty":5}', false],
			'unknown key (queue not group)' => ['{"queue":"batch"}', false],
			'priority as string' => ['{"priority":"5"}', false],
			'priority below range' => ['{"priority":0}', false],
			'priority above range' => ['{"priority":11}', false],
			'group not string' => ['{"group":42}', false],
			'group empty string' => ['{"group":""}', false],
		];

		foreach ($cases as $label => [$json, $expectValid]) {
			$data = $baseData;
			if ($json !== '') {
				$data['job_config'] = $json;
			}
			$row = $this->SchedulerRows->newEntity($data);
			$errors = $row->getError('job_config');

			if ($expectValid) {
				$this->assertSame([], $errors, "Expected '$label' ($json) to validate, got: " . json_encode($errors));
			} else {
				$this->assertNotEmpty($errors, "Expected '$label' ($json) to be rejected.");
			}
		}
	}

	/**
	 * job_config is stored as JSON text and exposed as an array — round-trip
	 * through save/reload must preserve the structure verbatim so it can be
	 * merged into createJob()'s $config arg by run().
	 *
	 * @return void
	 */
	public function testJobConfigRoundTripsThroughSave(): void {
		$row = $this->SchedulerRows->newEntity([
			'name' => 'JobConfig flow test',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'content' => ExampleTask::class,
			'frequency' => '@daily',
			'job_config' => '{"priority":5,"group":"batch"}',
			'enabled' => true,
		]);
		$this->SchedulerRows->saveOrFail($row);

		$reloaded = $this->SchedulerRows->get($row->id);
		$this->assertSame(['priority' => 5, 'group' => 'batch'], $reloaded->job_config);
	}

	/**
	 * The migration must keep `job_config` shipped with the JSON column-type
	 * cast wired up. Guards against future "remove the migration but keep the
	 * setColumnType()" drift that would crash on fresh installs.
	 *
	 * @return void
	 */
	public function testJobConfigColumnIsCastToJson(): void {
		$schema = $this->SchedulerRows->getSchema();
		$this->assertTrue($schema->hasColumn('job_config'));
		$this->assertSame('json', $schema->getColumnType('job_config'));
	}

	/**
	 * Bootstrapping the table against a schema that doesn't yet have
	 * `job_config` (e.g. an upgrade where `migrations migrate` hasn't run yet)
	 * must not throw — otherwise the deploy is unrecoverable from stock state.
	 *
	 * @return void
	 */
	public function testInitializeToleratesMissingJobConfigColumn(): void {
		$this->getTableLocator()->remove('QueueScheduler.SchedulerRowsPreMigration');

		$table = $this->getTableLocator()->get('QueueScheduler.SchedulerRowsPreMigration', [
			'className' => SchedulerRowsTable::class,
			'schema' => [
				'id' => ['type' => 'integer'],
				'name' => ['type' => 'string'],
			],
		]);

		$this->assertFalse($table->getSchema()->hasColumn('job_config'));
	}

	/**
	 * @return void
	 */
	public function testValidateWindowFields(): void {
		$baseData = [
			'name' => 'Window validation',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'content' => ExampleTask::class,
			'frequency' => '@daily',
		];

		$valid = $this->SchedulerRows->newEntity($baseData + [
			'window_start_time' => '09:00',
			'window_end_time' => '18:00',
			'window_days_of_week' => '1,2,3,4,5',
		]);
		$this->assertSame([], $valid->getError('window_start_time'));
		$this->assertSame([], $valid->getError('window_end_time'));
		$this->assertSame([], $valid->getError('window_days_of_week'));

		$invalid = $this->SchedulerRows->newEntity($baseData + [
			'name' => 'Window invalid',
			'window_start_time' => '24:00',
			'window_end_time' => '18:61',
			'window_days_of_week' => '1,foo,8',
		]);
		$this->assertNotEmpty($invalid->getError('window_start_time'));
		$this->assertNotEmpty($invalid->getError('window_end_time'));
		$this->assertNotEmpty($invalid->getError('window_days_of_week'));
	}

	/**
	 * `next_run` should move to the first cron slot inside the configured
	 * window, not sit in a permanently overdue state until the window opens.
	 *
	 * @return void
	 */
	public function testCalculateNextRunForCronHonorsWindow(): void {
		DateTime::setTestNow(new DateTime('2026-05-13 12:34:00'));
		try {
			$row = $this->SchedulerRows->newEntity([
				'name' => 'Windowed cron',
				'type' => SchedulerRow::TYPE_QUEUE_TASK,
				'content' => ExampleTask::class,
				'frequency' => '* * * * *',
				'window_start_time' => '23:00',
				'window_end_time' => '23:30',
			]);
			$this->SchedulerRows->saveOrFail($row);

			$this->assertNotNull($row->next_run);
			$this->assertSame('23:00:00', $row->next_run->format('H:i:s'));
			$this->assertTrue($row->isWithinWindow($row->next_run));
		} finally {
			DateTime::setTestNow(new DateTime());
		}
	}

	/**
	 * Interval schedules collapse missed ticks into the first moment the window
	 * reopens, so `next_run` should advance to that opening instant.
	 *
	 * @return void
	 */
	public function testCalculateNextRunForIntervalUsesWindowOpening(): void {
		DateTime::setTestNow(new DateTime('2026-05-13 12:34:00'));
		try {
			$row = $this->SchedulerRows->newEntity([
				'name' => 'Windowed interval',
				'type' => SchedulerRow::TYPE_QUEUE_TASK,
				'content' => ExampleTask::class,
				'frequency' => '+30 seconds',
				'window_start_time' => '23:00',
				'window_end_time' => '23:30',
			]);
			$this->SchedulerRows->saveOrFail($row);

			$this->assertSame('2026-05-13 23:00:00', $row->next_run?->format('Y-m-d H:i:s'));
		} finally {
			DateTime::setTestNow(new DateTime());
		}
	}

	/**
	 * Editing any of the window fields must recompute `next_run`; otherwise the
	 * stored value drifts from the actual dispatch gate until the row fires.
	 *
	 * @return void
	 */
	public function testChangingWindowFieldsRecomputesNextRun(): void {
		DateTime::setTestNow(new DateTime('2026-05-13 12:34:00'));
		try {
			$row = $this->SchedulerRows->newEntity([
				'name' => 'Window retarget',
				'type' => SchedulerRow::TYPE_QUEUE_TASK,
				'content' => ExampleTask::class,
				'frequency' => '* * * * *',
			]);
			$this->SchedulerRows->saveOrFail($row);
			$originalNextRun = $row->next_run;
			$this->assertNotNull($originalNextRun);

			$row = $this->SchedulerRows->patchEntity($row, [
				'window_start_time' => '23:00',
				'window_end_time' => '23:30',
			]);
			$this->SchedulerRows->saveOrFail($row);

			$this->assertNotNull($row->next_run);
			$this->assertNotSame($originalNextRun->format('Y-m-d H:i:s'), $row->next_run->format('Y-m-d H:i:s'));
			$this->assertSame('23:00:00', $row->next_run->format('H:i:s'));
			$this->assertTrue($row->isWithinWindow($row->next_run));
		} finally {
			DateTime::setTestNow(new DateTime());
		}
	}

	/**
	 * @return void
	 */
	public function testValidateContent(): void {
		$data = [
			'name' => 'n',
			'content' => CacheClearCommand::class,
			'type' => SchedulerRow::TYPE_CAKE_COMMAND,
			'frequency' => '',
		];
		$row = $this->SchedulerRows->newEntity($data);
		$this->assertSame([], $row->getError('content'));

		$data = [
			'name' => 'n',
			'content' => ExampleTask::class,
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'frequency' => '',
		];
		$row = $this->SchedulerRows->newEntity($data);
		$this->assertSame([], $row->getError('content'));

	}

	/**
	 * `*Command` classes that don't implement Cake's CommandInterface must be
	 * rejected at save time — the runtime executor would also refuse them, but
	 * persisting saves a round-trip and prevents constructor side effects on
	 * `new $class()` later.
	 *
	 * @return void
	 */
	public function testValidateCakeCommandRejectsNonCommandInterface(): void {
		$data = [
			'name' => 'bad command',
			'content' => stdClass::class . 'Command', // wrong suffix on existing FQCN — class_exists false
			'type' => SchedulerRow::TYPE_CAKE_COMMAND,
			'frequency' => '@daily',
		];
		$row = $this->SchedulerRows->newEntity($data);
		$this->assertNotEmpty($row->getError('content'));

		// A real class with `Command` suffix that is NOT a CommandInterface.
		// We use the SchedulerRowsTable itself with a synthetic alias by
		// asserting that ExampleTask::class (which is a Task, not a Command)
		// is rejected when the type says CAKE_COMMAND.
		$data = [
			'name' => 'task as command',
			'content' => ExampleTask::class . 'Command', // not a real class
			'type' => SchedulerRow::TYPE_CAKE_COMMAND,
			'frequency' => '@daily',
		];
		$row = $this->SchedulerRows->newEntity($data);
		$this->assertNotEmpty($row->getError('content'));
	}

	/**
	 * `*Task` classes that don't extend Queue\Queue\Task must be rejected.
	 *
	 * @return void
	 */
	public function testValidateQueueTaskRejectsNonTask(): void {
		// A class with `Task` suffix that does NOT extend Queue\Queue\Task.
		// `Cake\Console\CommandInterface` matches the suffix regex if we
		// alias-suffix it, but easier: construct an arbitrary FQCN that
		// doesn't exist — class_exists returns false.
		$data = [
			'name' => 'bad task',
			'content' => 'NonExistent\\Pkg\\GhostTask',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'frequency' => '@daily',
		];
		$row = $this->SchedulerRows->newEntity($data);
		$this->assertNotEmpty($row->getError('content'));
	}

	/**
	 * Shell-command authoring is double-gated: even with debug=true in the
	 * test bootstrap, flipping debug off and leaving allowRaw unset must
	 * produce a content-validation error (not a silent accept that the
	 * runner would later filter out).
	 *
	 * @return void
	 */
	public function testValidateShellCommandRequiresAllowRawWhenDebugOff(): void {
		Configure::write('debug', false);
		Configure::delete('QueueScheduler.allowRaw');

		try {
			$data = [
				'name' => 'shell row',
				'content' => 'uname -a',
				'type' => SchedulerRow::TYPE_SHELL_COMMAND,
				'frequency' => '@daily',
			];
			$row = $this->SchedulerRows->newEntity($data);
			$this->assertNotEmpty($row->getError('content'));

			Configure::write('QueueScheduler.allowRaw', true);
			$row = $this->SchedulerRows->newEntity($data);
			$this->assertSame([], $row->getError('content'));
		} finally {
			Configure::write('debug', true);
			Configure::delete('QueueScheduler.allowRaw');
		}
	}

	/**
	 * `[]` typed for a Cake Command param means "no args" — the field already
	 * accepts an empty string for that intent, so normalize the literal at
	 * marshal time instead of bouncing the user with a validation error.
	 *
	 * @return void
	 */
	public function testEmptyJsonArrayParamIsNormalizedToEmptyStringForCakeCommand(): void {
		$data = [
			'name' => 'cake cmd',
			'content' => CacheClearCommand::class,
			'type' => SchedulerRow::TYPE_CAKE_COMMAND,
			'frequency' => '@daily',
			'param' => '[]',
		];
		$row = $this->SchedulerRows->newEntity($data);

		$this->assertSame('', $row->param);
		$this->assertSame([], $row->getError('param'));
	}

	/**
	 * Same normalization for Queue Task: `{}` collapses to empty string so the
	 * row saves with "no payload" rather than failing on the empty-object
	 * literal.
	 *
	 * @return void
	 */
	public function testEmptyJsonObjectParamIsNormalizedToEmptyStringForQueueTask(): void {
		$data = [
			'name' => 'queue task',
			'content' => ExampleTask::class,
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'frequency' => '@daily',
			'param' => '{}',
		];
		$row = $this->SchedulerRows->newEntity($data);

		$this->assertSame('', $row->param);
		$this->assertSame([], $row->getError('param'));
	}

	/**
	 * Whitespace-padded variants (`[ ]`, `{\n}`) decode to empty arrays too —
	 * a string compare against the literal would miss these, but the
	 * `json_decode`-based check catches them.
	 *
	 * @return void
	 */
	public function testWhitespacePaddedEmptyJsonParamIsNormalized(): void {
		$data = [
			'name' => 'cake cmd',
			'content' => CacheClearCommand::class,
			'type' => SchedulerRow::TYPE_CAKE_COMMAND,
			'frequency' => '@daily',
			'param' => "[\n  \n]",
		];
		$row = $this->SchedulerRows->newEntity($data);

		$this->assertSame('', $row->param);
	}

	/**
	 * Real values pass through unchanged — the normalizer only fires on
	 * decoded-empty arrays.
	 *
	 * @return void
	 */
	/**
	 * Regression: `run()` wraps the isQueued → createJob → save chain in a
	 * transaction with a compare-and-swap on `last_run`. Two scheduler ticks
	 * landing on the same row simultaneously must result in EXACTLY ONE
	 * queued job, with the second `run()` call returning false.
	 *
	 * Simulated here by calling `run()` twice in sequence and reloading the
	 * row in between; the second call sees a `last_run` that no longer
	 * matches the first call's snapshot, so the compare-and-swap loses the
	 * race and rolls back the duplicate queued job.
	 *
	 * @return void
	 */
	public function testRunIsIdempotentAcrossOverlappingTicks(): void {
		// Load alongside Tools + QueueScheduler so the plugin registry matches
		// what Application::bootstrap() loads in the test app. Loading only
		// Queue would replace the registry and break later tests that rely
		// on Tools' commands being indexed (e.g. CommandFinderTest::testAll).
		$this->loadPlugins(['Tools', 'Queue', 'QueueScheduler']);
		$row = $this->SchedulerRows->newEntity([
			'name' => 'race-target',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'content' => ExampleTask::class,
			'job_config' => null,
			'job_data' => '',
			'frequency' => '+1 hour',
			'allow_concurrent' => false,
		]);
		$this->SchedulerRows->saveOrFail($row);
		$row = $this->SchedulerRows->get($row->id);
		$originalLastRun = $row->last_run;

		// First tick — should enqueue and advance last_run.
		$this->assertTrue($this->SchedulerRows->run($row));
		$row = $this->SchedulerRows->get($row->id);
		$this->assertNotSame($originalLastRun, $row->last_run);
		$firstQueuedId = $row->last_queued_job_id;
		$this->assertNotNull($firstQueuedId);

		$queuedJobsTable = $this->getTableLocator()->get('Queue.QueuedJobs');
		$this->assertSame(1, $queuedJobsTable->find()->count());

		// Simulate the racing tick: reload a *stale* copy of the row and call
		// run() against it. The compare-and-swap on `last_run IS $oldLastRun`
		// must reject the second write, and the queued job we just inserted
		// in the racing call must be rolled back.
		$staleRow = $this->SchedulerRows->get($row->id);
		// Re-stamp the in-memory copy back to the pre-first-run snapshot so
		// the racing call thinks it saw the original last_run.
		$staleRow->last_run = $originalLastRun;
		$result = $this->SchedulerRows->run($staleRow);

		$this->assertFalse($result, 'losing tick must return false');
		$this->assertSame(1, $queuedJobsTable->find()->count(), 'losing tick must not leave an orphan queued job');
	}

	/**
	 * runOnce() dispatches an ad-hoc job without advancing last_run /
	 * next_run, and uses the row's stored param when no override is
	 * provided. The scheduled cadence stays untouched.
	 *
	 * @return void
	 */
	public function testRunOnceWithoutOverridesUsesStoredParamAndPreservesCadence(): void {
		$this->loadPlugins(['Tools', 'Queue', 'QueueScheduler']);
		$row = $this->SchedulerRows->newEntity([
			'name' => 'override-target',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'content' => ExampleTask::class,
			'param' => '{"original":true}',
			'job_config' => null,
			'frequency' => '+1 hour',
			'allow_concurrent' => true,
		]);
		$this->SchedulerRows->saveOrFail($row);
		$row = $this->SchedulerRows->get($row->id);
		$originalLastRun = $row->last_run;
		$originalNextRun = $row->next_run;

		$this->assertTrue($this->SchedulerRows->runOnce($row));

		$queuedJobsTable = $this->getTableLocator()->get('Queue.QueuedJobs');
		$this->assertSame(1, $queuedJobsTable->find()->count());

		$reloaded = $this->SchedulerRows->get($row->id);
		$this->assertSame(
			$originalLastRun ? $originalLastRun->format('Y-m-d H:i:s') : null,
			$reloaded->last_run ? $reloaded->last_run->format('Y-m-d H:i:s') : null,
			'last_run must NOT advance for ad-hoc override dispatch',
		);
		$this->assertSame(
			$originalNextRun ? $originalNextRun->format('Y-m-d H:i:s') : null,
			$reloaded->next_run ? $reloaded->next_run->format('Y-m-d H:i:s') : null,
			'next_run must NOT advance for ad-hoc override dispatch',
		);
	}

	/**
	 * runOnce() with a job_data override sends the override payload to
	 * the queue, not the row's stored param.
	 *
	 * @return void
	 */
	public function testRunOnceWithJobDataOverrideUsesOverridePayload(): void {
		$this->loadPlugins(['Tools', 'Queue', 'QueueScheduler']);
		$row = $this->SchedulerRows->newEntity([
			'name' => 'override-target',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'content' => ExampleTask::class,
			'param' => '{"original":true}',
			'job_config' => null,
			'frequency' => '+1 hour',
			'allow_concurrent' => true,
		]);
		$this->SchedulerRows->saveOrFail($row);
		$row = $this->SchedulerRows->get($row->id);

		$this->assertTrue($this->SchedulerRows->runOnce($row, [
			'job_data' => ['tenant_id' => 42, 'force' => true],
		]));

		/** @var \Queue\Model\Entity\QueuedJob $queued */
		$queued = $this->getTableLocator()
			->get('Queue.QueuedJobs')
			->find()
			->orderBy(['id' => 'DESC'])
			->first();
		$payload = is_array($queued->data) ? $queued->data : json_decode((string)$queued->data, true);

		$this->assertSame(['tenant_id' => 42, 'force' => true], $payload);
	}

	/**
	 * runOnce() with a partial job_config override merges with the row's
	 * stored config rather than wiping the other keys. An override that
	 * only sets `priority` preserves the stored `group` (and vice versa).
	 *
	 * @return void
	 */
	public function testRunOnceMergesPartialJobConfigOverride(): void {
		$this->loadPlugins(['Tools', 'Queue', 'QueueScheduler']);
		$row = $this->SchedulerRows->newEntity([
			'name' => 'override-target',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'content' => ExampleTask::class,
			'param' => '',
			'job_config' => '{"priority":8,"group":"batch"}',
			'frequency' => '+1 hour',
			'allow_concurrent' => true,
		]);
		$this->SchedulerRows->saveOrFail($row);
		$row = $this->SchedulerRows->get($row->id);

		$this->assertTrue($this->SchedulerRows->runOnce($row, [
			'job_config' => ['priority' => 1],
		]));

		/** @var \Queue\Model\Entity\QueuedJob $queued */
		$queued = $this->getTableLocator()
			->get('Queue.QueuedJobs')
			->find()
			->orderBy(['id' => 'DESC'])
			->first();

		// The queued job's priority should reflect the override; the group
		// from the row's stored config should still be applied.
		$this->assertSame(1, (int)$queued->priority);
	}

	/**
	 * runOnce() respects allow_concurrent the same way the scheduled path
	 * does — an override against a non-concurrent row with an in-flight
	 * job returns false instead of dual-firing.
	 *
	 * @return void
	 */
	public function testRunOnceRespectsAllowConcurrent(): void {
		$this->loadPlugins(['Tools', 'Queue', 'QueueScheduler']);
		$row = $this->SchedulerRows->newEntity([
			'name' => 'override-target',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'content' => ExampleTask::class,
			'param' => '',
			'job_config' => null,
			'frequency' => '+1 hour',
			'allow_concurrent' => false,
		]);
		$this->SchedulerRows->saveOrFail($row);
		$row = $this->SchedulerRows->get($row->id);

		// First dispatch enqueues; second must be blocked by allow_concurrent.
		$this->assertTrue($this->SchedulerRows->runOnce($row));
		$this->assertFalse($this->SchedulerRows->runOnce($row));

		$this->assertSame(1, $this->getTableLocator()->get('Queue.QueuedJobs')->find()->count());
	}

	/**
	 * @return void
	 */
	public function testNonEmptyParamIsLeftAlone(): void {
		$data = [
			'name' => 'cake cmd',
			'content' => CacheClearCommand::class,
			'type' => SchedulerRow::TYPE_CAKE_COMMAND,
			'frequency' => '@daily',
			'param' => '["-q"]',
		];
		$row = $this->SchedulerRows->newEntity($data);

		$this->assertSame('["-q"]', $row->param);
	}

}
