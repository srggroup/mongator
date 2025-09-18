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
 * Does not generate anything.
 *
 * You can put your own identifiers or rely on Mongo.
 */
class NoneIdGenerator extends BaseIdGenerator {


	public function getCode(array $options) {
		return <<<EOF
if (null !== \$document->getId()) {
    %id% = \$document->getId();
}
EOF;
	}


	public function getToMongoCode() {
		return '';
	}


}
