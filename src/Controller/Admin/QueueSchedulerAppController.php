<?php declare(strict_types=1);

namespace QueueScheduler\Controller\Admin;

use App\Controller\AppController;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Closure;

/**
 * QueueSchedulerAppController
 *
 * Base controller for QueueScheduler admin.
 *
 * Authentication: by default this extends AppController to inherit the host
 * app's auth and components. Set `QueueScheduler.standalone` to `true` for an
 * isolated admin that does not depend on the host app.
 *
 * Authorization: this backend can configure arbitrary scheduled command
 * execution, so the default policy is **deny**. The host application MUST
 * configure `QueueScheduler.adminAccess` as a Closure that receives the
 * current request and returns `true` to grant access; anything else (unset,
 * non-Closure, returns false, or throws) yields a 403.
 *
 * ```php
 * // Allow only admins:
 * Configure::write('QueueScheduler.adminAccess', function (\Cake\Http\ServerRequest $request) {
 *     $identity = $request->getAttribute('identity');
 *     return $identity !== null && in_array('admin', (array)$identity->roles, true);
 * });
 * ```
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

	/**
	 * Default-deny access gate. The plugin can schedule arbitrary code, so
	 * accidental exposure (a logged-in but non-admin user, or a missing
	 * middleware in standalone mode) is treated as harmful by default.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @throws \Cake\Http\Exception\ForbiddenException When access is denied or unconfigured.
	 * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);

		// Coexist with cakephp/authorization: the gate IS the authorization
		// decision for these controllers, so silence the unauthorized check.
		if ($this->components()->has('Authorization') && method_exists($this->components()->get('Authorization'), 'skipAuthorization')) {
			$this->components()->get('Authorization')->skipAuthorization();
		}

		$gate = Configure::read('QueueScheduler.adminAccess');
		if (!($gate instanceof Closure)) {
			throw new ForbiddenException(__d('queue_scheduler', 'QueueScheduler admin backend is not configured. Set QueueScheduler.adminAccess to a Closure that returns true for permitted callers.'));
		}
		if ($gate($this->request) !== true) {
			throw new ForbiddenException(__d('queue_scheduler', 'QueueScheduler admin access denied.'));
		}
	}

}
