<?php

namespace QueueScheduler\Utility;

use Cake\Core\App;
use Cake\Core\Plugin;
use DirectoryIterator;
use RegexIterator;

class CommandFinder {

	/**
	 * @return array<string, string>
	 */
	public function all(): array {
		$commands = $this->list('Command');
		$plugins = Plugin::loaded();
		foreach ($plugins as $plugin) {
			$commands += $this->list('Command', $plugin);
		}

		return $commands;
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
				$commands[$name] = $name;
			}
		}

		return $commands;
	}

}
