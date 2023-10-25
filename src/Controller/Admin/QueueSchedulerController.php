<?php
declare(strict_types=1);

namespace QueueScheduler\Controller\Admin;

use QueueScheduler\Controller\AppController;

class QueueSchedulerController extends AppController {

	/**
	 * Index method
	 *
	 * @return \Cake\Http\Response|null|void Renders view
	 */
	public function index() {
		$x = null;
		$this->set(compact('x'));
	}

}
