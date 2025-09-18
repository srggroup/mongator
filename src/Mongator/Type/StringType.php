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
 * StringType.
 */
class StringType extends Type {


	public function toMongo($value) {
		return (string) $value;
	}


	public function toPHP($value) {
		return (string) $value;
	}


	public function toMongoInString() {
		return '%to% = (string) %from%;';
	}


	public function toPHPInString() {
		return '%to% = (string) %from%;';
	}


}
