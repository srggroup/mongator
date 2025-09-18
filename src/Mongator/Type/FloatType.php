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
 * FloatType.
 */
class FloatType extends Type {


	public function toMongo($value) {
		return (float) $value;
	}


	public function toPHP($value) {
		return (float) $value;
	}


	public function toMongoInString() {
		return '%to% = (float) %from%;';
	}


	public function toPHPInString() {
		return '%to% = (float) %from%;';
	}


}
