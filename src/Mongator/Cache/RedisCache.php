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

use Redis;

/**
 * AbstractCache.
 */
class RedisCache extends AbstractCache {


	private $redis;


	/**
	 * @param Redis $redis      the redis instance
	 * @param string $keyPattern (optional) redis format key, printf format
	 */
	public function __construct(Redis $redis, private $keyPattern = '{Mongator}{%s}') {
		$this->redis = $redis;
	}


	public function has($key) {
		$key = $this->getRedisKey($key);

		return (bool) $this->redis->exists($key);
	}


	public function set($key, $value, $ttl = 0) {
		$content = $this->pack($key, $value, $ttl);
		$key = $this->getRedisKey($key);

		$string = serialize($content);
		if ((int) $ttl === 0) {
			$this->redis->set($key, $string);
		} else {
			$this->redis->setex($key, $ttl, $string);
		}
	}


	public function remove($key) {
		$key = $this->getRedisKey($key);

		return (bool) $this->redis->del($key);
	}


	public function clear() {
		$pattern = $this->getRedisKey('*');
		foreach ($this->redis->keys($pattern) as $key) {
			$this->redis->del($key);
		}
	}


	public function info($key) {
		$key = $this->getRedisKey($key);
		if (!$content = $this->redis->get($key)) {
			return false;
		}

		return unserialize($content);
	}


	private function getRedisKey($key) {
		return sprintf($this->keyPattern, $key);
	}


}
