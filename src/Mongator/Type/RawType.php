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
 * RawType.
 */
class RawType extends Type {


	public function toMongo($value) {
		return $value;
	}


	public function toPHP($value) {
		return $value;
	}


	public function toMongoInString() {
		return '%to% = %from%;';
	}


	public function toPHPInString() {
		return '%to% = %from%;';
	}


}
