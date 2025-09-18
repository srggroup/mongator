<?php

/*
 * This file is part of Mongator.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Cache;

/**
 * AbstractCache.
 */
abstract class AbstractCache {


	/**
	 * Sets a value for a key.
	 *
	 * @param string $key   A unique key.
	 * @param mixed  $value The value.
	 */
	abstract public function set($key, $value, $ttl = 0);


	/**
	 * Removes a value from the cache.
	 *
	 * @param string $key A unique key.
	 */
	abstract public function remove($key);


	/**
	 * Clears the cache.
	 */
	abstract public function clear();


	/**
	 * Returns metadata info about given key
	 *
	 * @param string $key A unique key.
	 * @return array
	 */
	abstract public function info($key);


	/**
	 * Checks if the cache has a value for a key.
	 *
	 * @param string $key A unique key.
	 * @return bool Whether the cache has a key.
	 */
	public function has($key) {
		return (bool) $this->get($key);
	}


	/**
	 * Returns the value for a key.
	 *
	 * @param string $key A unique key.
	 * @return mixed The value for a key.
	 */
	public function get($key) {
		if (!$content = $this->info($key)) {
			return null;
		}

		return $this->unpack($content);
	}


	/**
	 * Pack the value in array with metadata
	 *
	 * @param string  $key   A unique key.
	 * @param mixed   $value The value to be cached.
	 * @param int $ttl   (optional) time to life in seconds.
	 * @return array Whether the cache has a key.
	 */
	protected function pack($key, $value, $ttl = 0) {
		return [
			'key'   => $key,
			'time'  => time(),
			'ttl'   => $ttl,
			'value' => $value,
		];
	}


	/**
	 * Unpack the data from cache, and unserialize the value. If ttl is ussed and is expired false is return
	 *
	 * @param array $content Data from cache.
	 * @return mixed
	 */
	protected function unpack($content) {
		if (!is_array($content)) {
			return null;
		}

		if ($content['ttl'] > 0 && time() >= $content['time'] + $content['ttl']) {
			$this->remove($content['key']);

			return null;
		}

		return $content['value'];
	}


}
