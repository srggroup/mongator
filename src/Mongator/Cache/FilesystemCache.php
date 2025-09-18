<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Cache;

use DirectoryIterator;
use RuntimeException;

/**
 * FilesystemCache.
 */
class FilesystemCache extends AbstractCache {


	private $data = [];


	/**
	 * @param string $dir The directory.
	 */
	public function __construct(private $dir) {
		if (!is_dir($dir) && mkdir($dir, 0777, true) === false) {
			throw new RuntimeException(sprintf('Unable to create the "%s" directory.', $dir));
		}
	}


	public function set($key, $value, $ttl = 0) {
		$content = $this->pack($key, $value, $ttl);
		$file = $this->dir . '/' . $key . '.php';

		$valueExport = var_export($content, true);

		$php = sprintf("<?php\nreturn %s;\n", $valueExport);

		if (file_put_contents($file, $php, LOCK_EX) === false) {
			throw new RuntimeException(sprintf('Unable to write the "%s" file.', $file));
		}

		$this->data[$key] = $content;
	}


	public function remove($key) {
		$file = $this->dir . '/' . $key . '.php';
		if (file_exists($file) && unlink($file) === false) {
			throw new RuntimeException(sprintf('Unable to remove the "%s" file.', $file));
		}

		if (isset($this->data[$key])) {
			unset($this->data[$key]);
		}
	}


	public function clear() {
		$this->data = [];

		if (is_dir($this->dir)) {
			foreach (new DirectoryIterator($this->dir) as $file) {
				if ($file->isFile()) {
					if (unlink($file->getRealPath()) === false) {
						throw new RuntimeException(sprintf('Unable to remove the "%s" file.', $file->getRealPath()));
					}
				}
			}
		}
	}


	public function info($key) {
		if (isset($this->data[$key])) {
			return $this->data[$key];
		}

		$file = $this->dir . '/' . $key . '.php';
		if (!file_exists($file)) {
			return null;
		}

		return $this->data[$key] = require $file;
	}


}
