<?php declare(strict_types=1);

namespace QueueScheduler;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventManager;
use Cake\Routing\RouteBuilder;
use QueueScheduler\Command\RunCommand;
use QueueScheduler\Event\QueueJobListener;

/**
 * Plugin for QueueScheduler
 */
class QueueSchedulerPlugin extends BasePlugin {

	/**
	 * @var bool
	 */
	protected bool $bootstrapEnabled = true;

	/**
	 * Hook the Queue.Job.completed / failed / maxAttemptsExhausted events
	 * so the run-history table follows what queue workers actually did.
	 *
	 * @param \Cake\Core\PluginApplicationInterface $app
	 *
	 * @return void
	 */
	public function bootstrap(PluginApplicationInterface $app): void {
		parent::bootstrap($app);
		EventManager::instance()->on(new QueueJobListener());
	}

	/**
	 * @var bool
	 */
	protected bool $middlewareEnabled = false;

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
		$commands->add('scheduler run', RunCommand::class);

		return $commands;
	}

	/**
	 * Register application container services.
	 *
	 * @link https://book.cakephp.org/5/en/development/dependency-injection.html#dependency-injection
	 *
	 * @param \Cake\Core\ContainerInterface $container The Container to update.
	 *
	 * @return void
	 */
	public function services(ContainerInterface $container): void {
		// Add your services here
	}

}
