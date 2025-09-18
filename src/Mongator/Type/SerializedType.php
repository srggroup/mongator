<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Type;

/**
 * SerializedType.
 */
class SerializedType extends Type {


	public function toMongo($value) {
		return serialize($value);
	}


	public function toPHP($value) {
		return unserialize($value);
	}


	public function toMongoInString() {
		return '%to% = serialize(%from%);';
	}


	public function toPHPInString() {
		return '%to% = unserialize(%from%);';
	}


}
