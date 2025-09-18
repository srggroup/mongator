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

use Memcached;

/**
 * AbstractCache.
 */
class MemcachedCache extends AbstractCache {


	private $memcached;

	private $keys = [];


	public function __construct(Memcached $memcached) {
		$this->memcached = $memcached;
	}


	public function has($key) {
		return (bool) $this->memcached->get($key);
	}


	public function set($key, $value, $ttl = 0) {
		$this->keys[] = $key;
		$content = $this->pack($key, $value, $ttl);

		$string = serialize($content);

		if ((int) $ttl !== 0) {
			$ttl += time();
		}
		$this->memcached->set($key, $string, (int) $ttl);
	}


	public function remove($key) {
		return $this->memcached->delete($key);
	}


	public function clear() {
		foreach ($this->keys as $key) {
			$this->memcached->delete($key);
		}
	}


	public function info($key) {
		if (!$content = $this->memcached->get($key)) {
			return false;
		}

		return unserialize($content);
	}


}
