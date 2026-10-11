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
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
	 * @return mixed
	 */
	public function beforeFilter(EventInterface $event) {
		parent::beforeFilter($event);
		if (Configure::read('Test.eventRedirectScheduler')) {
			$event->setResult($this->redirect('/login'));
			$event->stopPropagation();

			return null;
		}
		if (Configure::read('Test.redirectScheduler')) {
			return $this->redirect('/login');
		}
		if (Configure::read('Test.denyScheduler')) {
			throw new ForbiddenException('Application denied scheduler access.');
		}

		return null;
	}

}
