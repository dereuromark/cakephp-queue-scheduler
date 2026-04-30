<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Model\Table;

use Cake\Command\CacheClearCommand;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Queue\Queue\Task\ExampleTask;
use QueueScheduler\Model\Entity\SchedulerRow;
use QueueScheduler\Model\Table\SchedulerRowsTable;

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

}
