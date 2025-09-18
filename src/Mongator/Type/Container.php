<?php

//@phpcs:ignoreFile WebimpressCodingStandard.Formatting.StringClassReference.Found

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Type;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

/**
 * Container of types.
 */
class Container {


	private static $map = [
		'bin_data'      => 'Mongator\Type\BinDataType',
		'boolean'       => 'Mongator\Type\BooleanType',
		'date'          => 'Mongator\Type\DateType',
		'float'         => 'Mongator\Type\FloatType',
		'integer'       => 'Mongator\Type\IntegerType',
		'raw'           => 'Mongator\Type\RawType',
		'object'        => 'Mongator\Type\ObjectType',
		'array'         => 'Mongator\Type\ArrayType',
		'referenceOne'  => 'Mongator\Type\ReferenceOneType',
		'referenceMany' => 'Mongator\Type\ReferenceManyType',
		'serialized'    => 'Mongator\Type\SerializedType',
		'string'        => 'Mongator\Type\StringType',
	];

	private static $types = [];


	/**
	 * Returns if exists a type by name.
	 *
	 * @param string $name The type name.
	 * @return bool Returns if the type exists.
	 */
	public static function has($name) {
		return isset(static::$map[$name]);
	}


	/**
	 * Add a type.
	 *
	 * @param string $name  The type name.
	 * @param string $class The type class.
	 * @throws InvalidArgumentException If the type already exists.
	 * @throws InvalidArgumentException If the class is not a subclass of Mongator\Type\Type.
	 * @throws ReflectionException
	 */
	public static function add($name, $class) {
		if (static::has($name)) {
			throw new InvalidArgumentException(sprintf('The type "%s" already exists.', $name));
		}

		$r = new ReflectionClass($class);
		if (!$r->isSubclassOf('Mongator\Type\Type')) {
			throw new InvalidArgumentException(sprintf('The class "%s" is not a subclass of Mongator\Type\Type.', $class));
		}

		static::$map[$name] = $class;
	}


	/**
	 * Returns a type.
	 *
	 * @param string $name The type name.
	 * @return Type The type.
	 * @throws InvalidArgumentException If the type does not exists.
	 */
	public static function get($name) {
		if (!isset(static::$types[$name])) {
			if (!static::has($name)) {
				throw new InvalidArgumentException(sprintf('The type "%s" does not exists.', $name));
			}

			static::$types[$name] = new static::$map[$name]();
		}

		return static::$types[$name];
	}


	/**
	 * Remove a type.
	 *
	 * @param string $name The type name.
	 * @throws InvalidArgumentException If the type does not exists.
	 */
	public static function remove($name) {
		if (!static::has($name)) {
			throw new InvalidArgumentException(sprintf('The type "%s" does not exists.', $name));
		}

		unset(static::$map[$name], static::$types[$name]);
	}


	/**
	 * Reset the types.
	 */
	public static function reset() {
		static::$map = [
			'bin_data'      => 'Mongator\Type\BinDataType',
			'boolean'       => 'Mongator\Type\BooleanType',
			'date'          => 'Mongator\Type\DateType',
			'float'         => 'Mongator\Type\FloatType',
			'integer'       => 'Mongator\Type\IntegerType',
			'raw'           => 'Mongator\Type\RawType',
			'referenceOne'  => 'Mongator\Type\ReferenceOneType',
			'referenceMany' => 'Mongator\Type\ReferenceManyType',
			'serialized'    => 'Mongator\Type\SerializedType',
			'string'        => 'Mongator\Type\StringType',
		];

		static::$types = [];
	}


}
