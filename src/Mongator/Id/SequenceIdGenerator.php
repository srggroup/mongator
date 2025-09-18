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

use InvalidArgumentException;

/**
 * Generates a sequence.
 */
class SequenceIdGenerator extends BaseIdGenerator {


	public function getCode(array $options) {
		$increment = $options['increment'] ?? 1;
		$start = $options['start'] ?? null;

		// increment
		if (!is_int($increment) || $increment === 0) {
			throw new InvalidArgumentException('The option "increment" must be an integer distinct of 0.');
		}

		// start
		if ($start === null) {
			$start = $increment > 0 ? 1 : -1;
		} elseif (!is_int($start) || $start === 0) {
			throw new InvalidArgumentException('The option "start" must be an integer distinct of 0.');
		}

		return <<<EOF
\$serverInfo = \$repository->getConnection()->getMongo()->selectDB('admin')->command(array('buildinfo' => true));
\$mongoVersion = \$serverInfo['version'];

\$commandResult = \$repository->getConnection()->getMongoDB()->command(array(
    'findandmodify' => 'Mongator_sequence_id_generator',
    'query'         => array('_id' => \$repository->getCollectionName()),
    'update'        => array('\$inc' => array('sequence' => $increment)),
    'new'           => true,
));
if (
    (version_compare(\$mongoVersion, '2.0', '<') && \$commandResult['ok'])
    ||
    (version_compare(\$mongoVersion, '2.0', '>=') && null !== \$commandResult['value'])
) {
    %id% = \$commandResult['value']['sequence'];
} else {
    \$id = array('_id' => \$repository->getCollectionName(), 'sequence' => $start);
    \$repository
        ->getConnection()
        ->getMongoDB()
        ->selectCollection('Mongator_sequence_id_generator')
        ->insert(\$id);
    %id% = $start;
}
EOF;
	}


	public function getToMongoCode() {
		return <<<EOF
%id% = (int) %id%;
EOF;
	}


}
