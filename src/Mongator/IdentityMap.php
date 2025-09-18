<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator;

use Mongator\Document\Document;

/**
 * The identity map class.
 */
class IdentityMap implements IdentityMapInterface {


	private $documents;


	public function __construct() {
		$this->documents = [];
	}


	public function set($id, Document $document) {
		$this->documents[(string) $id] = $document;
	}


	public function has($id) {
		return isset($this->documents[(string) $id]);
	}


	public function get($id) {
		return $this->documents[(string) $id];
	}


	public function all() {
		return $this->documents;
	}


	public function &allByReference() {
		return $this->documents;
	}


	public function remove($id) {
		unset($this->documents[(string) $id]);
	}


	public function clear() {
		$this->documents = [];
	}


}
