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

use MongoDB\BSON\UTCDateTime;

/**
 * DateType.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 *
 * @api
 */
class DateType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function toMongo($value)
    {
        if ($value instanceof \DateTime) {
            $value = $value->getTimestamp();
        } elseif (is_string($value)) {
            $value = strtotime($value);
        }

        return new UTCDateTime(intval($value) * 1000);
    }

    /**
     * {@inheritdoc}
     */
    public function toPHP($value)
    {
        $date = new \DateTime();
        $date->setTimestamp($value->sec);

        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public function toMongoInString()
    {
        return '%to% = %from%; if (%from% instanceof \DateTime) { %to% = %from%->getTimestamp(); } elseif (is_string(%from%)) { %to% = strtotime(%from%); } %to% = new \MongoDB\BSON\UTCDateTime(intval(%to%) * 1000);';
    }

    /**
     * {@inheritdoc}
     */
    public function toPHPInString()
    {
        return '%to% = %from%->toDateTime();';
    }
}
