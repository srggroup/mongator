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
use MongoDB\Model\BSONArray;

/**
 * ArrayType.
 *
 * @author Adam.Balint <adam.balint@srg.hu>
 *
 * @api
 */
class ArrayType extends ArrayObjectType
{
    /**
     * {@inheritdoc}
     */
    public function toMongo($value)
    {
    	if(empty($value)) $value = [];
    	else if(!is_array($value)) $value = [$value];
    	
    	return new BSONArray(array_values($value));
    }

    /**
     * {@inheritdoc}
     */
    public function toPHP($value)
    {
		/**
		 * @var $value BSONArray
		 */
		return ArrayObjectType::BSONToArrayRecursive($value);
    }

    /**
     * {@inheritdoc}
     */
    public function toMongoInString()
    {
		return '%to% = %from%; if (empty(%to%)) { %to% = []; } elseif (!is_array(%to%)) { %to% = [%to%]; } %to% = new \MongoDB\Model\BSONArray(array_values(%to%));';
    }

    /**
     * {@inheritdoc}
     */
    public function toPHPInString()
    {
		return '%to% = \Mongator\Type\ArrayObjectType::BSONToArrayRecursive(%from%);';
    }
}
