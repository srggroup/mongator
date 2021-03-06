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
 *
 * @author Pablo Díez <pablodip@gmail.com>
 */
class NativeIdGenerator extends BaseIdGenerator
{
    /**
     * {@inheritdoc}
     */
    public function getCode(array $options)
    {
        return '%id% = new \MongoDB\BSON\ObjectID();';
    }

    /**
     * {@inheritdoc}
     */
    public function getToMongoCode()
    {
        return <<<EOF
if (!%id% instanceof \MongoDB\BSON\ObjectID) {
    %id% = new \MongoDB\BSON\ObjectID(%id%);
}
EOF;
    }
}
