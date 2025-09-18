<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Id;

/**
 * Generates a native identifier.
 */
class NativeIdGenerator extends BaseIdGenerator {


	public function getCode(array $options) {
		return '%id% = new \MongoDB\BSON\ObjectID();';
	}


	public function getToMongoCode() {
		return <<<EOF
ObjectID}
EOF;
	}


}
