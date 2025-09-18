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
 * IntegerType.
 */
class IntegerType extends Type {


	public function toMongo($value) {
		return (int) $value;
	}


	public function toPHP($value) {
		return (int) $value;
	}


	public function toMongoInString() {
		return '%to% = (int) %from%;';
	}


	public function toPHPInString() {
		return '%to% = (int) %from%;';
	}


}
