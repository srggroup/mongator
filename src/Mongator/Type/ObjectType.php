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

use MongoDB\Model\BSONDocument;

/**
 * ObjectType.
 */
class ObjectType extends ArrayObjectType {


	public function toMongo($value) {
		if (empty($value)) {
			$value = [];
		} elseif (!is_array($value)) {
			$value = [$value];
		}

		return new BSONDocument($value);
	}


	public function toPHP($value) {
		/**
		 * @var BSONDocument $value
		 */
		return ArrayObjectType::BSONToArrayRecursive($value);
	}


	public function toMongoInString() {
		return '%to% = %from%; if (empty(%to%)) { %to% = []; } elseif (!is_array(%to%)) { %to% = [%to%]; } %to% = new \MongoDB\Model\BSONDocument(%to%);';
	}


	public function toPHPInString() {
		return '%to% = \Mongator\Type\ArrayObjectType::BSONToArrayRecursive(%from%);';
	}


}
