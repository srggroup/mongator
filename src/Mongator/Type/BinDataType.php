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

use MongoBinData;

/**
 * BinDataType.
 */
class BinDataType extends Type {


	public function toMongo($value) {
		if (is_file($value)) {
			$value = file_get_contents($value);
		}

		return new MongoBinData($value, MongoBinData::BYTE_ARRAY);
	}


	public function toPHP($value) {
		return $value->bin;
	}


	public function toMongoInString() {
		return 'if (is_file(%from%)) { %from% = file_get_contents(%from%); } %to% = new \MongoBinData(%from%,  \MongoBinData::BYTE_ARRAY);';
	}


	public function toPHPInString() {
		return '%to% = %from%->bin;';
	}


}
