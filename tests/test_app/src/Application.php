<?php

namespace TestApp;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;

class Application extends BaseApplication {

	/**
	 * @inheritDoc
	 */
	public function bootstrap(): void {
		$this->addPlugin('Tools');
		$this->addPlugin('Queue');
		$this->addPlugin('QueueScheduler');
	}

	/**
	 * @param \Cake\Routing\RouteBuilder $routes
	 *
	 * @return void
	 */
	public function routes(RouteBuilder $routes): void {
		$routes->prefix('Admin', function (RouteBuilder $builder): void {
			$builder->plugin(
				'QueueScheduler',
				['path' => '/queue-scheduler'],
				function (RouteBuilder $builder): void {
					$builder->connect('/', ['controller' => 'QueueScheduler', 'action' => 'index']);

					$builder->fallbacks();
				},
			);
		});
	}

	/**
	 * @param \Cake\Http\MiddlewareQueue $middlewareQueue
	 *
	 * @return \Cake\Http\MiddlewareQueue
	 */
	public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue {
		$middlewareQueue->add(new RoutingMiddleware($this));

		return $middlewareQueue;
	}

}
