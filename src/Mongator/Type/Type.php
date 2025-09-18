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
 * Base class for Mongator types.
 */
abstract class Type {


	/**
	 * Convert a PHP value to a Mongo value.
	 *
	 * @param mixed $value The PHP value.
	 * @return mixed The Mongo value.
	 */
	abstract public function toMongo($value);


	/**
	 * Convert a Mongo value to a PHP value.
	 *
	 * @param mixed $value The Mongo value.
	 * @return mixed The PHP value.
	 */
	abstract public function toPHP($value);


	/**
	 * Convert a PHP value to a Mongo value (in string).
	 *
	 * @return string Code to convert the value.
	 */
	abstract public function toMongoInString();


	/**
	 * Convert a Mongo value to a PHP value (in string).
	 *
	 * @return mixed Code to conver the value.
	 */
	abstract public function toPHPInString();


}
