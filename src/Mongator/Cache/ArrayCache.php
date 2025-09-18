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
class ArrayCache extends AbstractCache {


	private $data = [];


	public function set($key, $value, $ttl = 0) {
		$this->data[$key] = $this->pack($key, $value, $ttl);
	}


	public function remove($key) {
		unset($this->data[$key]);
	}


	public function info($key) {
		if (!isset($this->data[$key])) {
			return null;
		}

		return $this->data[$key];
	}


	public function clear() {
		$this->data = [];
	}


}
