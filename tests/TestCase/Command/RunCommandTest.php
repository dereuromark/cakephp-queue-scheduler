<?php
declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Command;

use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * QueueScheduler\Command\RunCommand Test Case
 *
 * @uses \QueueScheduler\Command\RunCommand
 */
class RunCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->useCommandRunner();
	}

}
