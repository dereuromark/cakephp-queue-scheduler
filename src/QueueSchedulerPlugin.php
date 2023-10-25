<?php
declare(strict_types=1);

namespace QueueScheduler;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use Cake\Routing\RouteBuilder;
use QueueScheduler\Command\RunCommand;

/**
 * Plugin for QueueScheduler
 */
class QueueSchedulerPlugin extends BasePlugin {

	/**
	 * @var bool
	 */
	protected $bootstrapEnabled = false;

	/**
	 * @var bool
	 */
	protected $middlewareEnabled = false;

	/**
	 * Add routes for the plugin.
	 *
	 * If your plugin has many routes and you would like to isolate them into a separate file,
	 * you can create `$plugin/config/routes.php` and delete this method.
	 *
	 * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
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

		parent::routes($routes);
	}

	/**
	 * Add commands for the plugin.
	 *
	 * @param \Cake\Console\CommandCollection $commands The command collection to update.
	 *
	 * @return \Cake\Console\CommandCollection
	 */
	public function console(CommandCollection $commands): CommandCollection {
		$commands = parent::console($commands);
		$commands->add('scheduler run', RunCommand::class);

		return $commands;
	}

	/**
	 * Register application container services.
	 *
	 * @link https://book.cakephp.org/4/en/development/dependency-injection.html#dependency-injection
	 *
	 * @param \Cake\Core\ContainerInterface $container The Container to update.
	 *
	 * @return void
	 */
	public function services(ContainerInterface $container): void {
		// Add your services here
	}

}
