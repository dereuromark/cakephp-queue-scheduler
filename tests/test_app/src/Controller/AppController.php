<?php

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

class AppController extends Controller {

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('Flash');
		if (Configure::read('Test.loadAuthorization')) {
			$this->loadComponent('Authorization', ['className' => 'TestAuthorization']);
		}
	}

	/**
	 * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);
		if (Configure::read('Test.eventRedirectScheduler')) {
			$event->setResult($this->redirect('/login'));
			$event->stopPropagation();

			return;
		}
		if (Configure::read('Test.redirectScheduler')) {
			$event->setResult($this->redirect('/login'));
			$event->stopPropagation();

			return;
		}
		if (Configure::read('Test.denyScheduler')) {
			throw new ForbiddenException('Application denied scheduler access.');
		}

	}

}
