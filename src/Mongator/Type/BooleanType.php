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
 * BooleanType.
 */
class BooleanType extends Type {


	public function toMongo($value) {
		return (bool) $value;
	}


	public function toPHP($value) {
		return (bool) $value;
	}


	public function toMongoInString() {
		return '%to% = (bool) %from%;';
	}


	public function toPHPInString() {
		return '%to% = (bool) %from%;';
	}


}
