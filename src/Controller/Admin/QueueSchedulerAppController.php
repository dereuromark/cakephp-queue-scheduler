<?php declare(strict_types=1);

namespace QueueScheduler\Controller\Admin;

use App\Controller\AppController;
use Cake\Controller\Controller;
use Cake\Core\Configure;

/**
 * QueueSchedulerAppController
 *
 * Base controller for QueueScheduler admin.
 *
 * By default, extends AppController to inherit app authentication, components, and configuration.
 * Set `QueueScheduler.standalone` to `true` for an isolated admin that doesn't depend on the host app.
 */
class QueueSchedulerAppController extends AppController {

	use LoadHelperTrait;

	/**
	 * @return void
	 */
	public function initialize(): void {
		if (Configure::read('QueueScheduler.standalone')) {
			// Standalone mode: skip app's AppController, initialize independently
			Controller::initialize();
			$this->loadComponent('Flash');
		} else {
			// Default: inherit app's full controller setup
			parent::initialize();
		}

		$this->loadHelpers();

		// Layout configuration:
		// - null (default): Uses 'QueueScheduler.queue_scheduler' isolated Bootstrap 5 layout
		// - false: Disables plugin layout, uses app's default layout
		// - string: Uses specified layout (e.g., 'QueueScheduler.queue_scheduler' or custom)
		$layout = Configure::read('QueueScheduler.adminLayout');
		if ($layout !== false) {
			$this->viewBuilder()->setLayout($layout ?: 'QueueScheduler.queue_scheduler');
		}
	}

}
