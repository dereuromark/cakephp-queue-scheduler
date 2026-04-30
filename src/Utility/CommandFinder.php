<?php declare(strict_types=1);

namespace QueueScheduler\Utility;

use Cake\Core\App;
use Cake\Core\Plugin;
use DirectoryIterator;
use RegexIterator;
use Throwable;

class CommandFinder {

	/**
	 * Per-process cache keyed by the loaded plugin set.
	 *
	 * @var array<string, array<string, string>>
	 */
	protected static array $cache = [];

	/**
	 * @return array<string, string>
	 */
	public function all(): array {
		$plugins = Plugin::loaded();
		$cacheKey = implode(',', $plugins);

		if (isset(static::$cache[$cacheKey])) {
			return static::$cache[$cacheKey];
		}

		$commands = $this->list('Command');
		foreach ($plugins as $plugin) {
			$commands += $this->list('Command', $plugin);
		}

		ksort($commands);

		return static::$cache[$cacheKey] = $commands;
	}

	/**
	 * Clear the in-process cache. Mainly for tests.
	 *
	 * @return void
	 */
	public static function clearCache(): void {
		static::$cache = [];
	}

	/**
	 * @param string $classType
	 * @param string|null $plugin
	 *
	 * @return array<string, string>
	 */
	protected function list(string $classType, ?string $plugin = null): array {
		$paths = App::classPath($classType, $plugin);

		$commands = [];
		foreach ($paths as $path) {
			if (!is_dir($path)) {
				continue;
			}

			$iterator = new DirectoryIterator($path);
			$regexIterator = new RegexIterator($iterator, '/(\w+)Command\.php$/i', RegexIterator::GET_MATCH);
			foreach ($regexIterator as $match) {
				$name = ($plugin ? $plugin . '.' : '') . $match[1];
				try {
					$className = App::className($name, 'Command', 'Command');
				} catch (Throwable) {
					$className = null;
				}

				if (!$className) {
					continue;
				}

				$commands[$name] = $className;
			}
		}

		return $commands;
	}

}
