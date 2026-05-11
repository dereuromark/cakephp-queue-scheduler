<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Model\Table;

use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use QueueScheduler\Model\Entity\SchedulerRun;
use QueueScheduler\Model\Table\SchedulerRunsTable;

class SchedulerRunsTableTest extends TestCase {

	/**
	 * @var \QueueScheduler\Model\Table\SchedulerRunsTable
	 */
	protected SchedulerRunsTable $SchedulerRuns;

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.QueueScheduler.SchedulerRuns',
	];

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$config = $this->getTableLocator()->exists('QueueScheduler.SchedulerRuns')
			? []
			: ['className' => SchedulerRunsTable::class];
		$this->SchedulerRuns = $this->getTableLocator()->get('QueueScheduler.SchedulerRuns', $config);
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->SchedulerRuns);
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testRecordDispatchCreatesQueuedRow(): void {
		$run = $this->SchedulerRuns->recordDispatch(42, 100);

		$this->assertNotNull($run->id);
		$this->assertSame(42, $run->scheduler_row_id);
		$this->assertSame(100, $run->queued_job_id);
		$this->assertSame(SchedulerRun::STATUS_QUEUED, $run->status);
		$this->assertNotNull($run->dispatched_at);
		$this->assertNull($run->completed_at);
		$this->assertNull($run->duration_ms);
	}

	/**
	 * @return void
	 */
	public function testRecordDispatchAcceptsNullQueuedJobId(): void {
		$run = $this->SchedulerRuns->recordDispatch(42, null);

		$this->assertNotNull($run->id);
		$this->assertNull($run->queued_job_id);
	}

	/**
	 * markResolved() updates the most recent queued run for a queued_job_id
	 * and computes duration_ms. Time stamps in datetime resolution mean
	 * the duration assertion is just "non-negative integer."
	 *
	 * @return void
	 */
	public function testMarkResolvedAdvancesStatusAndComputesDuration(): void {
		$run = $this->SchedulerRuns->recordDispatch(42, 100);
		// Backdate dispatched_at so duration > 0.
		$run->dispatched_at = (new DateTime())->subSeconds(5);
		$this->SchedulerRuns->saveOrFail($run);

		$applied = $this->SchedulerRuns->markResolved(100, SchedulerRun::STATUS_COMPLETED);
		$this->assertTrue($applied);

		$run = $this->SchedulerRuns->get($run->id);
		$this->assertSame(SchedulerRun::STATUS_COMPLETED, $run->status);
		$this->assertNotNull($run->completed_at);
		$this->assertNotNull($run->duration_ms);
		$this->assertGreaterThanOrEqual(0, $run->duration_ms);
	}

	/**
	 * Failure path captures the failure_message so the history survives
	 * the queued_jobs cleanup that purges the original row.
	 *
	 * @return void
	 */
	public function testMarkResolvedCapturesFailureMessage(): void {
		$this->SchedulerRuns->recordDispatch(42, 100);

		$this->SchedulerRuns->markResolved(100, SchedulerRun::STATUS_FAILED, 'connection refused');

		/** @var \QueueScheduler\Model\Entity\SchedulerRun $run */
		$run = $this->SchedulerRuns->find()
			->where(['queued_job_id' => 100])
			->orderBy(['id' => 'DESC'])
			->first();

		$this->assertSame(SchedulerRun::STATUS_FAILED, $run->status);
		$this->assertSame('connection refused', $run->failure_message);
	}

	/**
	 * No matching queued run → returns false, doesn't crash.
	 *
	 * @return void
	 */
	public function testMarkResolvedReturnsFalseForUnknownJob(): void {
		$applied = $this->SchedulerRuns->markResolved(999999, SchedulerRun::STATUS_COMPLETED);
		$this->assertFalse($applied);
	}

	/**
	 * Only the most recent queued run for a given queued_job_id gets
	 * resolved — previous runs (different queued_job_id) are untouched.
	 *
	 * @return void
	 */
	public function testMarkResolvedOnlyTouchesMatchingRun(): void {
		$a = $this->SchedulerRuns->recordDispatch(42, 100);
		$b = $this->SchedulerRuns->recordDispatch(42, 101);

		$this->SchedulerRuns->markResolved(100, SchedulerRun::STATUS_COMPLETED);

		$aReloaded = $this->SchedulerRuns->get($a->id);
		$bReloaded = $this->SchedulerRuns->get($b->id);

		$this->assertSame(SchedulerRun::STATUS_COMPLETED, $aReloaded->status);
		$this->assertSame(SchedulerRun::STATUS_QUEUED, $bReloaded->status);
	}

	/**
	 * @return void
	 */
	public function testFindRecentForRowOrdersNewestFirst(): void {
		$older = $this->SchedulerRuns->recordDispatch(42, 100);
		$older->dispatched_at = (new DateTime())->subSeconds(60);
		$this->SchedulerRuns->saveOrFail($older);

		$newer = $this->SchedulerRuns->recordDispatch(42, 101);

		$results = $this->SchedulerRuns->find('recentForRow', scheduler_row_id: 42)->toArray();

		$this->assertCount(2, $results);
		$this->assertSame($newer->id, $results[0]->id);
		$this->assertSame($older->id, $results[1]->id);
	}

}
